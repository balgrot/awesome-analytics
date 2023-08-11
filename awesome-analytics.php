<?php
/**
 * Plugin Name: #Awesome Analytics
 * Plugin URI:        https://github.com/agenteliteteam/awesome-analytics.git
 * Description:       Adds amazing analytics features, super optimized and fast.
 * Version:           1.7.3
 * Requires at least: 5.4
 * Requires PHP:      7.3
 * Author:            Jeff Miller & Michael Markoski & Charlotte Mountain & Ben Sevcik & Daniel Cronin & Natalie Birch & Jorge Fernandez
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       awesome-analytics
 */

namespace AwesomeAnalytics;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $wpdb;

define('TABLE_ANALYTICS_PAGE_VISITS', $wpdb->prefix . 'analytics_page_visits');
define('TABLE_ANALYTICS_REFERRERS', $wpdb->prefix . 'analytics_referrers');
define('TABLE_ANALYTICS_SESSIONS', $wpdb->prefix . 'analytics_sessions');
define('TABLE_ANALYTICS_USERS', $wpdb->prefix . 'analytics_users');
define('TABLE_ANALYTICS_SHORT_LINKS', $wpdb->prefix . 'analytics_short_links');
define('TABLE_ANALYTICS_TRACK_LINKS', $wpdb->prefix . 'analytics_track_links');
( is_ssl() ) ? define( "AWESOME_ANALYTICS_PROTOCOL", 'https:' ) : define( "AWESOME_ANALYTICS_PROTOCOL", 'http:' );
$actual_link = AWESOME_ANALYTICS_PROTOCOL . "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
define("AWESOME_ANALYTICS_CURRENT_URL", $actual_link);
define('AWESOME_ANALYTICS_REST_API_TOKEN', '32874623046230462348068096');
define('AWESOME_ANALYTICS_REST_API_FEATURE', true );

require 'class-admin-page.php';
require 'class-cleanup-functions.php';
require 'class-cookie-tracking-js.php';
require 'class-create-databases.php';
require 'class-data-ajax-queries.php';
require 'class-data-queries.php';
require 'class-data-rest-queries.php';
require 'class-helper-functions.php';
require 'class-referrers.php';
require 'class-sessions.php';
require 'class-users.php';
require 'class-visits.php';
require 'class-domain-metrics.php';
require 'class-tracking-helpers.php';
require 'class-tracking-queries.php';
require 'class-cleanup.php';
require 'class-track-clicks.php';
require 'class-awesome-meta-tags.php';


//register_activation_hook( __FILE__, __NAMESPACE__ . '\analytics_run_on_activation' );

add_action( "init", __NAMESPACE__ . "\analytics_run_on_activation" );

function analytics_run_on_activation() {

    add_action('wp_enqueue_scripts', __NAMESPACE__ . '\analytics_include_jquery');
    add_action('wp_enqueue_scripts', __NAMESPACE__ . '\exit_popup_scripts_styles');

    /**
     * Include the Github plugin updater class.
     *
     * @param string  __FILE__ The absolute file path to the plugin.
     */
    include_once( plugin_dir_path( __FILE__ ) . 'class-plugin-updater.php');
    $updater = new \AwesomeAnalytics_Plugin_Updater( __FILE__ );

}


add_action( "wp_footer", __NAMESPACE__ . "\analytics_create_database_tables", 100 );
add_action( "admin_footer", __NAMESPACE__ . "\analytics_create_database_tables", 100 );

function analytics_create_database_tables() {

    $databases = new \AwesomeAnalytics\CreateDatabases;
    $databases->create_page_visits_table();
    $databases->create_referrers_table();
    $databases->create_sessions_table();
    $databases->create_users_table();
    $databases->create_short_links_table();
    $databases->create_track_links_table();

}

function analytics_include_jquery()
{
    wp_enqueue_script('jquery');
}

function exit_popup_scripts_styles(){
    wp_enqueue_style(__NAMESPACE__.'-exit-popup', plugins_url('/css/exit-popup.css',__FILE__ ));
    wp_enqueue_script( __NAMESPACE__.'-sweetalerts2', plugins_url('/js/sweetalerts2.js',__FILE__ ));
    wp_enqueue_style(__NAMESPACE__.'-sweetalerts2', plugins_url('/css/sweetalerts2.css',__FILE__ ));
}
