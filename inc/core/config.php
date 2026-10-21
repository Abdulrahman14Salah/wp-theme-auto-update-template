<?php

if (! defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Theme Configuration
|--------------------------------------------------------------------------
*/

define('MYTHEME_NAME', 'My Theme'); // <-- CHANGE ME

define('MYTHEME_SLUG', wp_get_theme()->get_stylesheet());


/*
|--------------------------------------------------------------------------
| GitHub Repository
|--------------------------------------------------------------------------
*/

define('MYTHEME_GITHUB_USER', 'YOUR_GITHUB_USERNAME'); // <-- CHANGE ME

define('MYTHEME_GITHUB_REPO', 'YOUR_REPO_NAME'); // <-- CHANGE ME

// Only needed for a PRIVATE repo. Never hardcode the token here — set it in
// the site's wp-config.php instead: putenv('MYTHEME_GITHUB_TOKEN=ghp_xxx');
// Use a fine-grained Personal Access Token scoped read-only to
// Contents + Releases on this ONE repo only.
define('MYTHEME_GITHUB_TOKEN', getenv('MYTHEME_GITHUB_TOKEN') ?: '');


/*
|--------------------------------------------------------------------------
| Update API — pick ONE of the two lines below
|--------------------------------------------------------------------------
|
| PUBLIC repo (default): raw.githubusercontent.com reads a small static
| manifest file (published by the GitHub Action on every release) through
| GitHub's CDN, with an effectively unlimited rate limit. Needed because
| shared hosting shares its outbound IP with many other tenant sites, so
| the unauthenticated api.github.com quota (60 req/hour, per IP) gets
| exhausted by neighbours and update checks silently fail.
|
| PRIVATE repo: raw.githubusercontent.com can't serve a private repo's
| files without auth, so use the real GitHub API instead — it works fine
| here because add_github_auth_headers() in updater.php sends
| MYTHEME_GITHUB_TOKEN as a Bearer token automatically, and an
| authenticated request gets 5000 req/hour (not the shared 60/hour), so
| the rate-limit problem doesn't apply.
|
*/

// PUBLIC repo:
define('MYTHEME_UPDATE_API', 'https://raw.githubusercontent.com/' . MYTHEME_GITHUB_USER . '/' . MYTHEME_GITHUB_REPO . '/main/update-manifest.json');

// PRIVATE repo (comment the line above, uncomment this one):
// define('MYTHEME_UPDATE_API', 'https://api.github.com/repos/' . MYTHEME_GITHUB_USER . '/' . MYTHEME_GITHUB_REPO . '/releases/latest');


/*
|--------------------------------------------------------------------------
| Theme Website (future license server)
|--------------------------------------------------------------------------
*/

define('MYTHEME_THEME_SITE', 'https://your-domain.com'); // <-- CHANGE ME
