<?php
/**
 * Perform actions for website sessions.
 */
namespace AwesomeAnalytics;

class Session {
    /**
	 * Start performing actions.
	 *
	 * @return void
	 */
    function __construct() {

        add_action( 'wp_ajax_ajax_save_actions', array( $this, 'ajax_save_actions' ));
        add_action( 'wp_ajax_nopriv_ajax_save_actions', array( $this, 'ajax_save_actions' ));

        add_action( 'wp_ajax_ajax_save_searches', array($this, 'ajax_save_searches') );
        add_action( 'wp_ajax_nopriv_ajax_save_searches', array($this, 'ajax_save_searches') );
    }

    public static function create( string $page_title, string $page_url, string $unique='yes', $referrer=null, $searches=false, $ip=null, int $claps = 0) {
        date_default_timezone_set('America/Los_Angeles');
        global $wpdb;
        $created_at = date("Y-m-d H:i:s");
        $table = TABLE_ANALYTICS_SESSIONS;
       
        $pages_visited = 1;
        $actions = 1;
        $visit_duration = 0;
        if(is_string($unique) === false) {
            $unique = 'yes';
        }
        if($unique === 'yes') {
            $unique = true;
        } else {
            $unique = null;
        }

        if( is_user_logged_in() ) {
            $logged_in = 1;
            $user = User::get(get_current_user_ID());
            $user_id = ($user) ? $user->ID : null;
        } else {
            $logged_in = null;
            $user_id = null;
        }

        $url_parts = Helpers::parse_url_parts($page_url);
        $page_path = ($url_parts['path']) ? $url_parts['path'] : null;
        $page_query = ($url_parts['query']) ? $url_parts['query'] : null;
        $parse_query = parse_str($page_query, $query_parts);

        $session_source = null;
        $session_medium = null;
        $session_campaign = null;
        $session_term = null;
        $session_content = null;
        $referrer_id = null;
        $referrer_keywords = null;
        $search = 0;
        $guid = Helpers::get_session_guid();
        $user_agent = null;

        if(!empty($query_parts['?utm_source'])){
            $session_source = $query_parts['?utm_source'];
        } 
        
        if(!empty($query_parts['utm_medium'])){
            $session_medium = $query_parts['utm_medium'];
        }

        if(!empty($query_parts['utm_campaign'])){
            $session_campaign = $query_parts['utm_campaign'];
        } 

        if(!empty($query_parts['utm_term'])){
            $session_term = $query_parts['utm_term'];
        }

        if(!empty($query_parts['utm_content'])){
            $session_content = $query_parts['utm_content'];
        }

        if(isset($referrer['id'])) {
            $referrer_id = $referrer['id'];
        } 

        if(isset($referrer['keywords'])) {
            $referrer_keywords = $referrer['keywords'];
        } 

        if($searches) {
            $search = 1;
        } 

        if(isset($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = Helpers::clean_string($_SERVER['HTTP_USER_AGENT']);
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://freegeoip.app/json/".$ip ,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "accept: application/json",
            "content-type: application/json"
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        
        $session_location_data= json_decode($response);
        $session_timezone =  null; 
        $session_city = null; 
        $session_region_code = null; 
        $session_region_name = null; 
        $session_zip_code = null; 
        $session_country_code = null; 
        $session_country_name = null;
        $session_latitude = "0"; 
        $session_longitude = "0"; 

        if (!empty($session_location_data)){
      
            if(!empty($session_location_data->time_zone)){
                $session_timezone = $session_location_data->time_zone; 
            } 
            if(!empty($session_location_data->city)){
                $session_city = $session_location_data->city; 
            }
            if(!empty($session_location_data->region_code)){
                $session_region_code = $session_location_data->region_code; 
            } 
            if(!empty($session_location_data->region_name)){
                $session_region_name = $session_location_data->region_name; 
            } 
            if(!empty($session_location_data->zip_code)){
                $session_zip_code = $session_location_data->zip_code; 
            }  
            if(!empty($session_location_data->country_code)){
                $session_country_code = $session_location_data->country_code; 
            }
            if(!empty($session_location_data->country_name)){
                $session_country_name = $session_location_data->country_name; ;
            }
            if(!empty($session_location_data->latitude)){
                $session_latitude = $session_location_data->latitude; 
            }
            if(!empty($session_location_data->longitude)){
                $session_longitude = $session_location_data->longitude; 
            }

        }
       
        $result = $wpdb->insert( 
            $table,
            array( 
                'guid' => $guid, 
                'created_at' => $created_at, 
                'pages_visited' => $pages_visited,
                'entry_page_path' => $page_path,
                'entry_page_query' => $page_query,
                'entry_page_title' => Helpers::clean_string($page_title),
                'entry_page_url' => $page_path . '/' . $page_query,
                'exit_page_path' => $page_path,
                'exit_page_query' => $page_query,
                'exit_page_title' => Helpers::clean_string($page_title),
                'exit_page_url' => $page_path . '/' . $page_query,
                'referrer_id' => $referrer_id,
                'referrer_keywords' => $referrer_keywords,
                'actions' => $actions, 
                'searches' => $search,
                'user_agent' => $user_agent,
                'visit_duration' => $visit_duration,
                'unique_session' => $unique,
                'logged_in' => $logged_in,
                'user_id' => $user_id,
                'ip' => $ip,
                'utm_source' => $session_source ,
                'utm_medium' => $session_medium,
                'utm_campaign' => $session_campaign,
                'utm_term' => $session_term,
                'utm_content' => $session_content,
                'claps' => $claps,
                'timezone' => $session_timezone,
		        'city' => $session_city,
	            'region_code' => $session_region_code,
	            'region_name' => $session_region_name,
	            'zip_code' => $session_zip_code,
	            'country_code' => $session_country_code,
	            'country_name' => $session_country_name,
	            'latitude' => $session_latitude,
	            'longitude' => $session_longitude,
            ),
            array(
                '%s',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
                '%s',
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );
        if($result == 1) {
            return $guid;
        } else {
            return;
        }
    }

    public static function update( string $guid, string $page_title, string $page_url, int $actions=0, $searches=false, int $page_reads=0, string $unique='yes', int $claps=0 ) {
        date_default_timezone_set('America/Los_Angeles');
        global $wpdb;
        $table = TABLE_ANALYTICS_SESSIONS;
        $session = Session::get($guid);

        if ( false !== $session ) {
           
            if($searches) {
                $session->searches = $session->searches + 1;
            }
            if($page_reads != 0) {
                $session->page_reads = $session->page_reads + 1;
                $session->pages_visited = $session->pages_visited;
            }else{
                $session->pages_visited = $session->pages_visited + 1;
            }
            if($claps != 0) {
                $session->claps = $session->claps + $claps;
            }
            if(is_numeric($actions)) {
                $session->actions = $session->actions + $actions;
            }
            if( is_user_logged_in() ) {
                $logged_in = 1;
                $user = User::get(get_current_user_ID());
                $user_id = ($user) ? $user->ID : null;
            } else {
                $logged_in = null;
                $user_id = null;
            }

            if(is_string($unique) === false) {
                $unique = 'yes';
            }
            if($unique === 'yes') {
                $unique = true;
            } else {
                $unique = null;
            }

            $url_parts = Helpers::parse_url_parts($page_url);
            $page_path = ($url_parts['path']) ? $url_parts['path'] : null;
            $page_query = ($url_parts['query']) ? $url_parts['query'] : null;
            $session->visit_duration = strtotime(date("Y-m-d H:i:s")) - strtotime( $session->created_at );
            $parse_query = parse_str($page_query, $query_parts);
            $session_source = null;
            $session_medium = null;
            $session_campaign = null;
            $session_term = null;
            $session_content = null;
            $page_title = '';
            if(!empty($query_parts['?utm_source'])){
                $session_source = $query_parts['?utm_source'];
            }
            if(!empty($query_parts['utm_medium'])){
                $session_medium = $query_parts['utm_medium'];
            } 
            if(!empty($query_parts['utm_campaign'])){
                $session_campaign = $query_parts['utm_campaign'];
            }
            if(!empty($query_parts['utm_term'])){
                $session_term = $query_parts['utm_term'];
            }
            if(!empty($query_parts['utm_content'])){
                $session_content = $query_parts['utm_content'];
            }

            $result = $wpdb->update(
                $table,
                array(
                    'pages_visited' => $session->pages_visited, 
                    'exit_page_path' => $page_path,
                    'exit_page_query' => $page_query,
                    'exit_page_title' => Helpers::clean_string($page_title),
                    'exit_page_url' => $page_path . '/' . $page_query,
                    'actions' => $session->actions,
                    'searches' => $session->searches,
                    'visit_duration' => $session->visit_duration,
                    'logged_in' => $logged_in,
                    'page_reads' => $session->page_reads,
                    'unique_session' => $unique,
                    'user_id' => $user_id,
                    'utm_source' => $session_source ,
                    'utm_medium' => $session_medium,
                    'utm_campaign' => $session_campaign,
                    'utm_term' => $session_term,
                    'utm_content' => $session_content,
                    'claps' => $session->claps,
                ),
                array( 'ID' => $session->ID ),
                array(
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                )
            );
            if($result !== false) {
                return $guid;
            } else {
                return;
            }
        } else {
            return false;
        }
    }

    public static function update_action_and_searches( string $guid, $action = NULL, $search = NULL ) {
        global $wpdb;
        $table = TABLE_ANALYTICS_SESSIONS;
        $session = Session::get($guid);
        if ( false !== $session ) {
            if($action){
                $session->actions = $session->actions + 1;
            }
            if($search){
                $session->searches = $session->searches + 1;
            }
            $session->visit_duration = strtotime(date("Y-m-d H:i:s")) - strtotime( $session->created_at );
            $result = $wpdb->update(
                $table,
                array(
                    'actions' => $session->actions,
                    'searches' => $session->searches,
                    'visit_duration' => $session->visit_duration
                ),
                array( 'ID' => $session->ID ),
                array(
                    '%d'
                )
            );
            if($result !== false) {
                return $guid;
            } else {
                return;
            }
        } else {
            return false;
        }
    }

    function ajax_save_actions() {
        check_ajax_referer( 'save_actions_nonce', 'security' );
        if(isset($_REQUEST)) {
            $guid = esc_sql(Helpers::clean_string($_POST['guid'])); 
            Session::update_action_and_searches( $guid, true, NULL);
        }
        wp_die();
    }
    
    function ajax_save_searches() {
        check_ajax_referer( 'save_searches_nonce', 'security' );
        if(isset($_REQUEST)) {
            $session_data = Helpers::get_session_data();
            $ciphertext = stripslashes($_COOKIE[$session_data['session_cookie_name']]);
            $decrypted_string = openssl_decrypt($ciphertext, "AES-128-ECB", $session_data['secret_encrypt_token']);
            $cookie_json = json_decode($decrypted_string);

            if(!isset($cookie_json->guid)) {
                return;
            }

            $guid = esc_sql(Helpers::clean_string($cookie_json->guid)); 
            Session::update_action_and_searches( $guid, NULL, true);
        }
        wp_die();
    }

    function rest_save_searches() {
        //check_ajax_referer( 'save_searches_nonce', 'security' );
        if(isset($_REQUEST)) {
            $session_data = Helpers::get_session_data();
            $ciphertext = stripslashes($_COOKIE[$session_data['session_cookie_name']]);
            $decrypted_string = openssl_decrypt($ciphertext, "AES-128-ECB", $session_data['secret_encrypt_token']);
            $cookie_json = json_decode($decrypted_string);

            if(!isset($cookie_json->guid)) {
                return;
            }

            $guid = esc_sql(Helpers::clean_string($cookie_json->guid)); 
            Session::update_action_and_searches( $guid, NULL, true);
        }
        wp_die();
    }

    public static function get( string $guid ) {
        date_default_timezone_set('America/Los_Angeles');
        global $wpdb;
        $table = TABLE_ANALYTICS_SESSIONS;
        $guid = esc_sql(Helpers::clean_string($guid));
        $session = $wpdb->get_row(" SELECT * FROM {$table} WHERE guid = '{$guid}'" );
        if ( null !== $session ) {
            return $session;
        } else {
            return false;
        }
    }


}

$Session = new Session;