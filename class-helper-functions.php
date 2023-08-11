<?php
/**
 * Helper functions.
 */
namespace AwesomeAnalytics;

class Helpers {
    
    public static function generate_random_token($length = 32) {
        if(!isset($length) || intval($length) <= 8 ){
        $length = 32;
        }
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length));
        }
        if (function_exists('mcrypt_create_iv')) {
            return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length));
        }
    }


    public static function get_session_data($unique = false) {

        $site_url = Helpers::awesome_analytics_get_url();

        if(is_single()|| is_front_page()){
            global $post;
        }

        $data = [
            'secret_encrypt_token' => $site_url . '_agentelite_analytics',
            'long_lived_cookie_name' => "wp-long-" . md5($site_url),
            'session_cookie_name' => "wp-" . md5($site_url),
            'clap_cookie_name' => "wp-clp-". md5($site_url),
            'exit_survey_cookie_name' => 'wp-es-'. md5($site_url),
            'hash' => md5($site_url),
            'page_title' => 'Page Title Unknown',
            'page_url' => AWESOME_ANALYTICS_CURRENT_URL,
            'unique' => false,
            'post_id' => (isset($post->ID) ? $post->ID : 0),
            'referrer' => null,
            'domain' => $_SERVER['SERVER_NAME'],
            'search' => '',
            'ip_address' => Helpers::get_ip(),
            'claps' => 0,
        ];
        
        if( is_404()) {
            $data['page_title'] = 'Page Not Found';
        }
        if(!empty($post->post_title)&& get_option('show_on_front') !== 'posts') {
            $data['page_title'] = str_replace(["'", '"'], "", $post->post_title);
        }
        if(get_option('show_on_front') === 'posts'){
            $data['page_title'] = get_option('blogname');
        }
     
        if( !isset( $_COOKIE[$data['long_lived_cookie_name']] ) ) {
            $data['unique'] = true;
        }
        
        $referrer_url = Referrer::get_server_referrer();
        if($referrer_url) {
            $data['referrer'] = Referrer::create_or_update( $referrer_url, $data['unique'] );
        }

        if(isset($_GET['propertyType']) || isset($_GET['s']) || isset($_GET['q']) || isset($_GET['searchField']) || isset($_GET['listingIdList'])) {
            $data['search'] = true;
        } else {
            $data['search'] = false;
        }

        return $data;

    }

    
    public static function get_session_guid() {

        global $wpdb;
        $unique = false;
        $tested = [];

        do{
            // Generate random string of characters
            $random = Helpers::generate_random_token(16);
            // Check if it's already testing
            // If so, don't query the database again
            if( in_array($random, $tested) ) {
                continue;
            }
            $table_name = TABLE_ANALYTICS_SESSIONS;
            // Check if it is unique in the database
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE guid='{$random}' ");
            // Store the random character in the tested array
            // To keep track which ones are already tested
            $tested[] = $random;
            // String appears to be unique
            if( $count == 0){
                // Set unique to true to break the loop
                $unique = true;
            }
            // If unique is still false at this point
            // it will just repeat all the steps until
            // it has generated a random string of characters
        }
        while(!$unique);
        return $random;
    }


  
    public static function ends_with($haystack, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }

    public static function parse_url_parts( string $page_url ) {
        $data = array(
            'path' => null,
            'query' => null
        );
        $query = parse_url($page_url, PHP_URL_QUERY);
        if($query) {
            $data['query'] = '?' . Helpers::clean_string($query);
        }
        $host = trim(str_replace($query, "", $page_url), '?');
        $stripped = trim(ltrim(str_replace(AWESOME_ANALYTICS_PROTOCOL . "//$_SERVER[HTTP_HOST]", "", $host), '/'), '/');
        $url_parts = explode('/', $stripped, 2);
        if(isset($url_parts[0])) {
            $data['path'] = Helpers::clean_string($url_parts[0]);
        }
        if(isset($url_parts[1])) {
            if($query) {
                $data['query'] = Helpers::clean_string($url_parts[1]) . '/' . $data['query'];
            } else {
                $data['query'] = Helpers::clean_string($url_parts[1]);
            }	
        }
        return $data;
    }


    public static function clean_string($string) {
        $string = strip_tags($string);
        return str_replace(array('"', "'"), "", $string);
    }

    public static function multi_stripos($haystack, $needles) {
        foreach($needles as $needle) {
            if (stripos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
	 * Queries 
	 *
	 * @return array 
	 */
    public static function count_days_between_dates( $start_date, $end_date ) {
        $start = strtotime($start_date);
        $end = strtotime($end_date);
        if($start == $end) {
            return 1;
        }
        $diff = $end - $start;
        $days = $diff / (60 * 60 * 24);
        return ceil($days);
    }

    /**
	 *  Check URL Spam
	 *
	 * @return boolean whether it's spam or not
	 */
    public static function check_url_spam() {
        $spam = file( plugin_dir_path( __FILE__ ) .  'url-spam.txt', FILE_IGNORE_NEW_LINES );
        global $post;
        foreach($spam as $spam_item) {
            if(!empty($post)) {
                if( stripos( strtolower($post->post_title), $spam_item ) !== false ) {
                    return false; // allow the words through if they are in the post title
                }
            }
            if( stripos( AWESOME_ANALYTICS_CURRENT_URL, $spam_item ) !== false ){
                return true;
            }
        }
        return false;
    }

    /**
	 *  Check User Agent Spam
	 *
	 * @return boolean whether it's spam or not
	 */
    public static function check_user_agent_spam() {
        $spam = file( plugin_dir_path( __FILE__ ) .  'user-agent-spam.txt', FILE_IGNORE_NEW_LINES );
        foreach($spam as $spam_item) {
            if( stripos( $_SERVER['HTTP_USER_AGENT'], $spam_item ) !== false ){
                return true;
            }
        }
        return false;
    }

    /**
	 *  Convert seconds to time
	 *
	 * @return string the time string
	 */
    public static function seconds_to_time( int $seconds) {
        $seconds = (int) number_format($seconds, 2);
        if($seconds < 1) {
            return "less than a second";
        } else {
            $seconds = round($seconds);
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds / 60) % 60);
            $seconds = $seconds % 60;
            return "{$minutes}m, {$seconds}s";
        }
    }

    
    /**
	 *  Convert seconds to time
	 *
	 * @return string the time string
	 */
    public static function get_ip() {
        // Get real visitor IP behind CloudFlare network
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
        $client  = (isset($_SERVER['HTTP_CLIENT_IP'])) ? $_SERVER['HTTP_CLIENT_IP'] : '';
        $forward = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
        $remote  = $_SERVER['REMOTE_ADDR'];

        if(filter_var($client, FILTER_VALIDATE_IP))
        {
            $ip = $client;
        }
        elseif(isset($forward) && filter_var($forward, FILTER_VALIDATE_IP))
        {
            $ip = $forward;
        }
        else
        {
            $ip = $remote;
        }

        $ip = array_values(array_filter(explode(',',$ip)));

        return trim($ip[0]);
    }


    public static function write_log($log = null, $trace = null)
    {
        if ($trace) {
            error_log('Called on: ' . debug_backtrace()[1]['line'] . ' in method ' . debug_backtrace()[1]['function']);
        }
        if (is_array($log) || is_object($log)) {
            error_log(print_r($log, true));
        } else {
            error_log($log);
        }
    }

    public static function get_days_from_date_range( $start_date, $end_date ) {

        $start = new \DateTime($start_date);
        $end_date = date("Y-m-d",strtotime("+1 day", strtotime($end_date)));
        $end = new \DateTime($end_date);
        $interval = new \DateInterval('P1D');
        $dateRange = new \DatePeriod($start, $interval, $end);

        $days = array();
        foreach ($dateRange as $date) {
            $days[] = $date->format('Y-m-d');
        }

        return $days;

    }
    
    public static function awesome_analytics_get_url() {
        $protocol = ( is_ssl() ) ? 'https://' : 'http://';
        return  $protocol . $_SERVER['HTTP_HOST'];
    }

}



