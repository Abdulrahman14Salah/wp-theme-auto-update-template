<?php

if (!defined('ABSPATH')) {
    exit;
}

class MyTheme_GitHub_Updater
{

    private const RELEASE_TRANSIENT_KEY = 'mytheme_github_release_';
    private const RELEASE_CACHE_TTL = 6 * HOUR_IN_SECONDS;

    private $theme_slug;
    private $theme_data;
    private $github_api_url;
    private $github_repo;

    public function __construct()
    {

        $this->theme_slug = get_template();
        $this->theme_data = wp_get_theme($this->theme_slug);
        $this->github_api_url = MYTHEME_UPDATE_API;
        $this->github_repo = MYTHEME_GITHUB_REPO;

        add_filter(
            'pre_set_site_transient_update_themes',
            [$this, 'check_update']
        );

        add_filter(
            'themes_api',
            [$this, 'theme_info'],
            10,
            3
        );

        add_filter(
            'http_request_args',
            [$this, 'add_github_auth_headers'],
            10,
            2
        );

        add_filter(
            'upgrader_source_selection',
            [$this, 'fix_source_folder'],
            10,
            4
        );

        add_action(
            'upgrader_process_complete',
            [$this, 'clear_release_cache_after_theme_upgrade'],
            10,
            2
        );

        // Force WordPress's background auto-update cron to install updates
        // for this theme on its own, without relying on the admin's
        // "Enable auto-updates" toggle (themes don't always surface that
        // link in wp-admin the way plugins do).
        add_filter(
            'auto_update_theme',
            [$this, 'force_auto_update'],
            10,
            2
        );
    }

    public function force_auto_update($update, $item)
    {

        if (isset($item->theme) && $item->theme === $this->theme_slug) {
            return true;
        }

        return $update;
    }

    public function check_update($transient)
    {

        if (empty($transient->checked) || !is_object($transient)) {
            return $transient;
        }

        $release = $this->get_latest_release();

        if (!$release) {
            return $transient;
        }

        $latest_version  = ltrim((string) $release->tag_name, 'v');
        $current_version = $this->theme_data->get('Version');

        if ($latest_version === '' || !version_compare($current_version, $latest_version, '<')) {
            return $transient;
        }

        $package = $this->resolve_package_url($release);

        if (!$package) {
            return $transient;
        }

        $update = [
            'theme'       => $this->theme_slug,
            'new_version' => $latest_version,
            'url'         => esc_url_raw((string) ($release->html_url ?? '')),
            'package'     => esc_url_raw($package),
        ];

        $transient->response[$this->theme_slug] = $update;

        return $transient;
    }

    public function theme_info($result, $action, $args)
    {

        if ($action !== 'theme_information') {
            return $result;
        }

        if (!isset($args->slug) || $args->slug !== $this->theme_slug) {
            return $result;
        }

        $release = $this->get_latest_release();

        if (!$release) {
            return $result;
        }

        $latest_version = ltrim((string) $release->tag_name, 'v');

        $info = new stdClass();

        $info->name     = $this->theme_data->get('Name');
        $info->slug     = $this->theme_slug;
        $info->version  = $latest_version;
        $info->author   = $this->theme_data->get('Author');
        $info->homepage = esc_url_raw((string) ($release->html_url ?? ''));

        $release_notes = isset($release->body)
            ? wp_kses_post(nl2br((string) $release->body))
            : '';

        $info->sections = [
            'description' => wp_kses_post($this->theme_data->get('Description')),
            'changelog'   => $release_notes,
        ];

        return $info;
    }

    public function add_github_auth_headers($args, $url)
    {

        // Only this repository's API calls: never send the token anywhere else.
        $repo_api = 'https://api.github.com/repos/' . MYTHEME_GITHUB_USER . '/' . MYTHEME_GITHUB_REPO . '/';

        if (!is_string($url) || stripos($url, $repo_api) !== 0) {
            return $args;
        }

        if (!isset($args['headers']) || !is_array($args['headers'])) {
            $args['headers'] = [];
        }

        $args['headers']['User-Agent'] = 'WordPress/' . get_bloginfo('version') . '; ' . home_url('/');
        $args['headers']['Accept'] = 'application/vnd.github+json';

        if (!empty(MYTHEME_GITHUB_TOKEN)) {
            $args['headers']['Authorization'] = 'Bearer ' . MYTHEME_GITHUB_TOKEN;
        }

        if (strpos($url, '/releases/assets/') !== false) {
            $args['headers']['Accept'] = 'application/octet-stream';
        }

        return $args;
    }

    /**
     * GitHub ZIPs may extract to a folder named after the repo or tag. Rename it
     * to the installed theme's folder so the update replaces the theme instead
     * of installing a second copy next to it.
     */
    public function fix_source_folder($source, $remote_source, $upgrader, $hook_extra = [])
    {
        global $wp_filesystem;

        if (!is_array($hook_extra) || ($hook_extra['theme'] ?? '') !== $this->theme_slug) {
            return $source;
        }

        if (is_wp_error($source) || !$wp_filesystem) {
            return $source;
        }

        $expected = trailingslashit($remote_source) . $this->theme_slug . '/';

        if (untrailingslashit($source) === untrailingslashit($expected)) {
            return $source;
        }

        if (!$wp_filesystem->move(untrailingslashit($source), untrailingslashit($expected), true)) {
            return new WP_Error(
                'mytheme_rename_failed',
                __('The theme update could not be installed: its folder could not be renamed.', 'my-theme')
            );
        }

        return $expected;
    }

    public function clear_release_cache_after_theme_upgrade($upgrader, $hook_extra)
    {

        if (!is_array($hook_extra) || ($hook_extra['type'] ?? '') !== 'theme') {
            return;
        }

        if (($hook_extra['action'] ?? '') !== 'update') {
            return;
        }

        delete_transient(self::RELEASE_TRANSIENT_KEY . $this->theme_slug);
    }

    private function get_latest_release()
    {

        $release = get_transient(self::RELEASE_TRANSIENT_KEY . $this->theme_slug);

        if ($release) {
            return $release;
        }

        $response = wp_safe_remote_get(
            esc_url_raw($this->github_api_url),
            [
                'timeout' => 15,
                'headers' => [
                    'Accept' => 'application/vnd.github+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url('/'),
                ],
            ]
        );

        if (is_wp_error($response)) {
            return false;
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);

        if ($status_code < 200 || $status_code >= 300) {
            return false;
        }

        $release = json_decode(wp_remote_retrieve_body($response));

        if (!$release || !isset($release->tag_name)) {
            return false;
        }

        set_transient(
            self::RELEASE_TRANSIENT_KEY . $this->theme_slug,
            $release,
            self::RELEASE_CACHE_TTL
        );

        return $release;
    }

    private function resolve_package_url($release)
    {

        $expected_assets = array_filter([
            $this->theme_slug . '.zip',
            $this->github_repo . '.zip',
        ]);

        if (!empty($release->assets) && is_array($release->assets)) {
            foreach ($release->assets as $asset) {
                $asset_name = (string) ($asset->name ?? '');

                if (!in_array($asset_name, $expected_assets, true)) {
                    continue;
                }

                if (!empty($asset->url)) {
                    return (string) $asset->url;
                }

                if (!empty($asset->browser_download_url)) {
                    return (string) $asset->browser_download_url;
                }
            }
        }

        // No built theme ZIP on the release means no update. The source zipball
        // contains the whole repo in a differently named folder.
        return '';
    }
}
