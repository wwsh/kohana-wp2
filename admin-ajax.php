<?php

/**
 * Custom Kohana AJAXes.
 *
 * A bridge between WP AJAX and Kohana AJAX calls.
 *
 * Actually the superfast edition of the original wp-admin/admin-ajax.php script.
 * Loading required code and performs outright quick.
 *
 * CAVEAT EMPTOR: the code is ugly, experimental and may stop working with a new
 * WordPress release.
 *
 * @link https://codex.wordpress.org/AJAX_in_Plugins
 */

define('DOING_AJAX', true);
if (!defined('WP_ADMIN')) {
    define('WP_ADMIN', false);
}

define('WP_DEBUG', false);

/** Load WordPress Bootstrap - turbo edition */
$WP_INCLUDE_DIR = realpath(__DIR__ . '/../../../wp-includes');
$WP_CONTENT_DIR = realpath(__DIR__ . '/../../../wp-content');
$WP_PLUGIN_DIR  = $WP_CONTENT_DIR . '/plugins';

if (!defined('ABSPATH')) {
    define('ABSPATH', realpath($WP_INCLUDE_DIR . '/..') . '/');
}

if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

require_once $WP_INCLUDE_DIR . '/version.php';
require_once $WP_INCLUDE_DIR . '/compat.php';
require_once $WP_INCLUDE_DIR . '/capabilities.php';
require_once $WP_INCLUDE_DIR . '/pluggable.php';
require_once $WP_INCLUDE_DIR . '/plugin.php';
require_once $WP_INCLUDE_DIR . '/http.php';
require_once $WP_INCLUDE_DIR . '/user.php';
require_once $WP_INCLUDE_DIR . '/functions.php';
require_once $WP_INCLUDE_DIR . '/load.php';
require_once $WP_INCLUDE_DIR . '/default-constants.php';
require_once $WP_INCLUDE_DIR . '/cache.php';
require_once $WP_INCLUDE_DIR . '/class-wp-error.php';
require_once $WP_INCLUDE_DIR . '/formatting.php';
require_once $WP_INCLUDE_DIR . '/meta.php';
require_once $WP_INCLUDE_DIR . '/session.php';
require_once $WP_INCLUDE_DIR . '/link-template.php';
require_once $WP_INCLUDE_DIR . '/class-wp-user.php';
require_once $WP_INCLUDE_DIR . '/class-wp-user-query.php';
require_once $WP_INCLUDE_DIR . '/class-wp-meta-query.php';
$loadMe = $WP_PLUGIN_DIR . '/buddypress/bp-forums/bbpress/bb-includes/backpress/class.wp-users.php';
if (file_exists($loadMe)) {
    require_once $loadMe; // we need the user class definition
}
require_once $WP_INCLUDE_DIR . '/class-wp-roles.php';
require_once $WP_INCLUDE_DIR . '/class-wp-role.php';
require_once $WP_INCLUDE_DIR . '/class-wp-widget.php';


// Ugly, dirty way of getting the connector. We need to skip wp-settings.php loading!
load_connector_defines();
// now the connector data is defined
$table_prefix = $GLOBALS['table_prefix'] = get_table_prefix();
// the following is copied from other official wordpress files....
require_wp_db();
wp_set_wpdb_vars();
wp_cache_init();
wp_plugin_directory_constants();
wp_initial_constants();
wp_cookie_constants();


global $current_user;

// validation of the user
add_filter('determine_current_user', 'wp_validate_logged_in_cookie', 20);
add_filter('determine_current_user', 'wp_validate_auth_cookie');

// load auth data
get_currentuserinfo();

/** Allow for cross-domain requests (from the frontend). */
send_origin_headers();

// Require an action parameter
if (empty($_REQUEST['action'])) {
    die('0');
}

@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
@header('X-Robots-Tag: noindex');

send_nosniff_header();
nocache_headers();

add_action('wp_ajax_nopriv_heartbeat', 'wp_ajax_nopriv_heartbeat', 1);

// Load the plugin

// Define and enforce our SSL constants
wp_ssl_constants();

// Create common globals.
require_once $WP_INCLUDE_DIR . '/post.php';
@require_once $WP_INCLUDE_DIR . '/vars.php';
require_once $WP_INCLUDE_DIR . '/pomo/translations.php';
require_once $WP_INCLUDE_DIR . '/l10n.php';
require_once $WP_INCLUDE_DIR . '/taxonomy.php';
require_once $WP_INCLUDE_DIR . '/theme.php';
require_once $WP_INCLUDE_DIR . '/widgets.php';

// Make taxonomies and posts available to plugins and themes.
// @plugin authors: warning: these get registered again on the init hook.
create_initial_taxonomies();
create_initial_post_types();

// Register the default theme directory root
register_theme_directory(get_theme_root());

// Load Kohana Plugin
$plugin = realpath(WP_CONTENT_DIR . '/plugins/kohana-wp2/kohana-wp2.php');
wp_register_plugin_realpath($plugin);
include_once($plugin);

// load BuddyPress stuff, if present
$loadMe = WP_CONTENT_DIR . '/plugins/buddypress/bp-core/classes/class-bp-core-user.php';
if (file_exists($loadMe)) {
    include_once($loadMe);
}
$loadMe = WP_CONTENT_DIR . '/plugins/buddypress/bp-core/bp-core-cache.php';
if (file_exists($loadMe)) {
    include_once($loadMe);
}

do_action('plugins_loaded');

if (!isset($_REQUEST['action']) || $_REQUEST['action'] !== 'kohana_ajax') {
    die;
}

// help kohana find proper files
define('APPPATH', realpath(WP_CONTENT_DIR . '/kohana/site/application') . DIRECTORY_SEPARATOR);

$params = $_REQUEST;

$filterOutKeys = [
    'action',
    'route',
    'kohana_action'
];

$params = array_diff_key($params, array_flip($filterOutKeys));

$GLOBALS['KOHANA_REQ_PARAMS'] = $params;

$response = KWP_Request::execute_route($_REQUEST['route']);

if (class_exists('BP_Core_User')) {
    // faking the buddypress stuff
    function buddypress()
    {
        global $wpdb, $current_user;
        return (object)[
            'members' => (object)[
                'table_name_last_activity' => $wpdb->prefix . 'bp_activity',
                'id'                       => 'members'
            ]
        ];
    }

// we need to log user activity even if there is just the page open.
    if ($current_user->ID) {
        BP_Core_User::update_last_activity($current_user->ID, current_time('mysql', true));
    }

}

// Actual Kohana execution
$headers = $response->headers();
$body    = $response->body();

foreach ($headers as $key => $value) {
    header($key . ': ' . $value);
}
die ($body);

// Dirty, undocumented way of extracting the DB connector data
// without firing wp-config.php code.
function load_connector_defines()
{
    $configPhp     = file_get_contents(ABSPATH . 'wp-config.php');
    $markedContent = explode('#####', $configPhp);
    if (!isset($markedContent[1])) {
        die ('Internal config error');
    }
    eval($markedContent[1]);
}

// Same as above, only this time we need the table prefix.
function get_table_prefix()
{
    $configPhp = file_get_contents(ABSPATH . 'wp-config.php');
    preg_match('/\$table_prefix\s+=\s+\'([a-z_]+)\'/', $configPhp, $matches);
    if (!isset($matches[1])) {
        die ('Internal config error');
    }
    return $matches[1];
}
