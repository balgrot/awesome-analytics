<?php
/**
 * Track visitors using cookies.
 */
namespace AwesomeAnalytics;

class CleanUp {
    /**
	 * Start performing actions.
	 *
     * @return void
	 */
    function __construct() {
        
        add_action('wp_footer', array($this, 'cleanUp_page_visits'), 20);
        add_action('wp_footer', array($this, 'cleanUp_page_referrers'), 20);
        add_action('wp_footer', array($this, 'cleanUp_page_sessions'), 20);
        
    }
    
    function cleanUp_page_visits ($max=20){
        global $wpdb;
        date_default_timezone_set('America/Los_Angeles');
        $page_visits_table = TABLE_ANALYTICS_PAGE_VISITS;
        $max = (!empty($max)) ? $max : 20;

        $wpdb->get_results("DELETE FROM {$page_visits_table} WHERE DATE(created_at) <= CURDATE() - INTERVAL 120 DAY LIMIT {$max};");
        
    }

    function cleanUp_page_referrers ($max=20){
        global $wpdb;
        date_default_timezone_set('America/Los_Angeles');
        $page_referrers_table = TABLE_ANALYTICS_REFERRERS;
        $max = (!empty($max)) ? $max : 20;
        
        $wpdb->get_results("DELETE FROM {$page_referrers_table} WHERE DATE(created_at) <= CURDATE() - INTERVAL 120 DAY LIMIT {$max};");
        
    }

    function cleanUp_page_sessions($max=20){
        global $wpdb;
        date_default_timezone_set('America/Los_Angeles');
        $page_sessions_table = TABLE_ANALYTICS_SESSIONS;
        $max = (!empty($max)) ? $max : 20;
        
        $wpdb->get_results("DELETE FROM {$page_sessions_table} WHERE DATE(created_at) <= CURDATE() - INTERVAL 120 DAY LIMIT {$max};");
        
    }

}

$CleanUp = new CleanUp;

