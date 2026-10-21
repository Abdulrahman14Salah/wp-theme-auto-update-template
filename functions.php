<?php

if (! defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Theme Constants
|--------------------------------------------------------------------------
*/

define('MYTHEME_VERSION', wp_get_theme()->get('Version'));
define('MYTHEME_DIR', get_template_directory());
define('MYTHEME_URI', get_template_directory_uri());
define('MYTHEME_INC', MYTHEME_DIR . '/inc');


/*
|--------------------------------------------------------------------------
| Autoload PHP Files from /inc
|--------------------------------------------------------------------------
*/

function mytheme_autoload_inc()
{

    $inc_dir = get_template_directory() . '/inc';

    if (! is_dir($inc_dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($inc_dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {

        if ($file->getExtension() === 'php') {
            require_once $file->getRealPath();
        }
    }
}

mytheme_autoload_inc();


/*
|--------------------------------------------------------------------------
| GitHub Auto-Updater
|--------------------------------------------------------------------------
*/

add_action('after_setup_theme', function () {

    if (class_exists('MyTheme_GitHub_Updater')) {
        new MyTheme_GitHub_Updater();
    }
});
