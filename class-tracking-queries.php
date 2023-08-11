<?php
/**
 * 
 */
namespace AwesomeAnalytics;
use WP_REST_Response;
use WP_REST_Request;

class TrackingAjaxQuery {
    /**
	 * Start performing actions.
	 *
	 * @return void
	 */
    function __construct() {
        add_action('wp_ajax_ajax_record_session', array( $this, 'ajax_record_session'));
        add_action('wp_ajax_nopriv_ajax_record_session',  array( $this, 'ajax_record_session'));
        add_action('wp_ajax_nopriv_ajax_record_visit', array($this, 'ajax_record_visit'));
        add_action('wp_ajax_ajax_record_visit', array($this, 'ajax_record_visit'));

        add_action('wp_ajax_nopriv_ajax_record_exit_survey', array($this, 'ajax_record_exit_survey'));
        add_action('wp_ajax_ajax_record_exit_survey', array($this, 'ajax_record_exit_survey'));

        add_action('wp_ajax_nopriv_ajax_update_session', array($this, 'ajax_update_session'));
        add_action('wp_ajax_ajax_update_session', array($this, 'ajax_update_session'));
        add_action('wp_ajax_nopriv_ajax_record_article_read', array($this, 'ajax_record_article_read'));
        add_action('wp_ajax_ajax_record_article_read', array($this, 'ajax_record_article_read'));

        //REST Endpoints
        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', '/analytics/recordsession', array(
              'methods' => 'POST',
              'callback' => array($this, 'rest_record_session'),
              'show_in_index' => false,
              'permission_callback' => function (WP_REST_Request $request) {
                $nonce = $request->get_header('x-wp-nonce');
                return wp_verify_nonce($nonce, 'wp_rest');
            }
              ) );
            }
        );
        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', '/analytics/updatesession', array(
                'methods' => 'POST',
                'callback' => array($this, 'rest_update_session'),
                'show_in_index' => false,
                'permission_callback' => function (WP_REST_Request $request) {
                    $nonce = $request->get_header('x-wp-nonce');
                    return wp_verify_nonce($nonce, 'wp_rest');
                }
              ) );
            }
        );
        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', '/analytics/recordvisit', array(
              'methods' => 'POST',
              'callback' => array($this, 'rest_record_visit'),
              'show_in_index' => false,
              'permission_callback' => function (WP_REST_Request $request) {
                $nonce = $request->get_header('x-wp-nonce');
                return wp_verify_nonce($nonce, 'wp_rest');
            }
              ) );
            }
        );
        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', '/analytics/articleread', array(
              'methods' => 'POST',
              'callback' => array($this, 'rest_record_article_read'),
              'show_in_index' => false,
              'permission_callback' => function (WP_REST_Request $request) {
                $nonce = $request->get_header('x-wp-nonce');
                return wp_verify_nonce($nonce, 'wp_rest');
            }
              ) );
            }
        );
        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', '/analytics/savesearches', array(
              'methods' => 'POST',
              'callback' => array($this, 'rest_save_searches'),
              'show_in_index' => false,
              'permission_callback' => function (WP_REST_Request $request) {
                $nonce = $request->get_header('x-wp-nonce');
                return wp_verify_nonce($nonce, 'wp_rest');
            }
              ) );
            }
        );
        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', '/analytics/recordclicks', array(
              'methods' => 'POST',
              'callback' => array($this, 'rest_record_clicks'),
              'show_in_index' => false,
              'permission_callback' => function (WP_REST_Request $request) {
                $nonce = $request->get_header('x-wp-nonce');
                return wp_verify_nonce($nonce, 'wp_rest');
            }
              ) );
            }
        );
    }

    function ajax_record_session() {
        check_ajax_referer('ajax_record_session_nonce', 'security');

        $page_url = (!empty($_POST['page_url'])) ? $_POST['page_url'] : '';
        $post_id = (!empty($_POST['post_id'])) ? $_POST['post_id'] : 0;
        $referrer_id = (!empty($_POST['referrer_id'])) ? $_POST['referrer_id'] : '';
        $referrer_keywords = (!empty($_POST['referrer_keywords'])) ? $_POST['referrer_keywords'] : '';
        $search = (!empty($_POST['search'])) ? $_POST['search'] : '';
        $ip_address = (!empty($_POST['ip_address'])) ? $_POST['ip_address'] : '';
        $unique = (!empty($_POST['unique']) && $_POST['unique'] === 'yes') ? 'yes' : 'no';
        $referrer = [
            'id' => $referrer_id,
            'keywords' =>  $referrer_keywords,
        ];
      
        $page_title = ($post_id !== 0 || !empty($post_id)) ? get_the_title($post_id) : '';
         
        $guid = Session::create($page_title, $page_url, $unique, $referrer, $search, $ip_address); 

        $toCookie = array( 
            "unique" => $unique, 
            "guid" => $guid
        );

        $session_data = Helpers::get_session_data();
        $string_to_encrypt = json_encode($toCookie);
        $encrypted_string = openssl_encrypt($string_to_encrypt, "AES-128-ECB", $session_data['secret_encrypt_token']);
        echo $encrypted_string;
        wp_die();

    }

    function rest_record_session() {
        $page_url = (!empty($_POST['page_url'])) ? $_POST['page_url'] : '';
        $post_id = (!empty($_POST['post_id'])) ? $_POST['post_id'] : 0;
        $referrer_id = (!empty($_POST['referrer_id'])) ? $_POST['referrer_id'] : '';
        $referrer_keywords = (!empty($_POST['referrer_keywords'])) ? $_POST['referrer_keywords'] : '';
        $search = (!empty($_POST['search'])) ? $_POST['search'] : '';
        $ip_address = (!empty($_POST['ip_address'])) ? $_POST['ip_address'] : '';
        $unique = (!empty($_POST['unique']) && $_POST['unique'] === 'yes') ? 'yes' : 'no';
        $referrer = [
            'id' => $referrer_id,
            'keywords' =>  $referrer_keywords,
        ];
      
        $page_title = ($post_id !== 0 || !empty($post_id)) ? get_the_title($post_id) : '';
         
        $guid = Session::create($page_title, $page_url, $unique, $referrer, $search, $ip_address); 

        $toCookie = array( 
            "unique" => $unique, 
            "guid" => $guid
        );

        $session_data = Helpers::get_session_data();
        $string_to_encrypt = json_encode($toCookie);
        $encrypted_string = openssl_encrypt($string_to_encrypt, "AES-128-ECB", $session_data['secret_encrypt_token']);
        return new WP_REST_Response( $encrypted_string, 200 );
        wp_die();

    }

    function ajax_update_session(){
        $update_session_nonce = check_ajax_referer('ajax_update_session_nonce', 'security', false);
        $update_session_clap_nonce = check_ajax_referer('ajax_update_session_clap_nonce', 'security', false);
        $update_session_survey_nonce = check_ajax_referer('ajax_update_session_exit_survey_nonce', 'security', false);
        if ($update_session_nonce === false && $update_session_clap_nonce === false && $update_session_survey_nonce === false){
            wp_die();
        }

        $session_data = Helpers::get_session_data();
        if(!isset($_COOKIE[$session_data['session_cookie_name']])) {
            wp_die();
        }

        $ciphertext = stripslashes($_COOKIE[$session_data['session_cookie_name']]);
        $decrypted_string = openssl_decrypt($ciphertext, "AES-128-ECB", $session_data['secret_encrypt_token']);
        $cookie_json = json_decode($decrypted_string);
        if(!isset($cookie_json->guid)) {
            wp_die();
        }

        $page_url = (!empty($_POST['page_url'])) ? $_POST['page_url'] : '';
        $post_id = (!empty($_POST['post_id'])) ? $_POST['post_id'] : '';
        $unique = (!empty($_POST['unique']) && $_POST['unique'] === 'yes') ? 'yes' : 'no';
        $claps = (!empty($_POST['claps'])) ? $_POST['claps'] : 0 ;
        $exit_reason =(!empty($_POST['exit_reason'])) ? $_POST['exit_reason'] : '';
        $page_title = ($post_id !== 0 || !empty($post_id)) ? get_the_title($post_id) : '';

        Session::update( $cookie_json->guid, $page_title, $page_url, 1, $session_data['search'], 0, $unique, $claps, $exit_reason);
        wp_die();
    }
    function rest_update_session(){
        // $update_session_nonce = check_ajax_referer('ajax_update_session_nonce', 'security', false);
        // $update_session_clap_nonce = check_ajax_referer('ajax_update_session_clap_nonce', 'security', false);
        // $update_session_survey_nonce = check_ajax_referer('ajax_update_session_exit_survey_nonce', 'security', false);
        // if ($update_session_nonce === false && $update_session_clap_nonce === false && $update_session_survey_nonce === false){
        //     wp_die();
        // }

        $session_data = Helpers::get_session_data();
        if(!isset($_COOKIE[$session_data['session_cookie_name']])) {
            wp_die();
        }

        $ciphertext = stripslashes($_COOKIE[$session_data['session_cookie_name']]);
        $decrypted_string = openssl_decrypt($ciphertext, "AES-128-ECB", $session_data['secret_encrypt_token']);
        $cookie_json = json_decode($decrypted_string);
        if(!isset($cookie_json->guid)) {
            wp_die();
        }

        $page_url = (!empty($_POST['page_url'])) ? $_POST['page_url'] : '';
        $post_id = (!empty($_POST['post_id'])) ? $_POST['post_id'] : '';
        $unique = (!empty($_POST['unique']) && $_POST['unique'] === 'yes') ? 'yes' : 'no';
        $claps = (!empty($_POST['claps'])) ? $_POST['claps'] : 0 ;
        $exit_reason =(!empty($_POST['exit_reason'])) ? $_POST['exit_reason'] : '';
        $page_title = ($post_id !== 0 || !empty($post_id)) ? get_the_title($post_id) : '';

        Session::update( $cookie_json->guid, $page_title, $page_url, 1, $session_data['search'], 0, $unique, $claps, $exit_reason);
        return new WP_REST_Response(null, 204);
        wp_die();
    }

    function ajax_record_visit(){
        $record_visit_nonce = check_ajax_referer('ajax_record_visit_nonce', 'security',false);
        $record_visit_claps_nonce = check_ajax_referer('ajax_record_visit_clap_nonce', 'security',false);
        if ($record_visit_nonce === false && $record_visit_claps_nonce === false){
            wp_die();
        }
 
        $post_id = (!empty($_POST['post_id'])) ? (integer)$_POST['post_id'] : 0;
        $page_url = (!empty($_POST['page_url'])) ? $_POST['page_url'] : '';
        $unique = (!empty($_POST['unique']) && $_POST['unique'] === 'yes') ? 'yes' : 'no';
        $claps = (!empty($_POST['claps'])) ? $_POST['claps'] : 0;

        Visit::create_or_update('', $page_url, $unique, $post_id, $claps);
        wp_die();
    }  

    function rest_record_visit(){
        // $record_visit_nonce = check_ajax_referer('ajax_record_visit_nonce', 'security',false);
        // $record_visit_claps_nonce = check_ajax_referer('ajax_record_visit_clap_nonce', 'security',false);
        // if ($record_visit_nonce === false && $record_visit_claps_nonce === false){
        //     wp_die();
        // }
 
        $post_id = (!empty($_POST['post_id'])) ? (integer)$_POST['post_id'] : 0;
        $page_url = (!empty($_POST['page_url'])) ? $_POST['page_url'] : '';
        $unique = (!empty($_POST['unique']) && $_POST['unique'] === 'yes') ? 'yes' : 'no';
        $claps = (!empty($_POST['claps'])) ? $_POST['claps'] : 0;

        Visit::create_or_update('', $page_url, $unique, $post_id, $claps);
        return new WP_REST_Response(null, 204);
        wp_die();
    }  

    function ajax_record_article_read(){
        check_ajax_referer('ajax_record_article_read_nonce', 'security');

        $post_id = (!empty($_POST['post_id'])) ? $_POST['post_id'] : '';
        Visit::update_page_read($post_id);

        $session_data = Helpers::get_session_data();
        if(!isset($_COOKIE[$session_data['session_cookie_name']])) {
            wp_die();
        }

        $ciphertext = stripslashes($_COOKIE[$session_data['session_cookie_name']]);
        $decrypted_string = openssl_decrypt($ciphertext, "AES-128-ECB", $session_data['secret_encrypt_token']);
        $cookie_json = json_decode($decrypted_string);
        if(!isset($cookie_json->guid)) {
            wp_die();
        }

        $page_title = ($post_id !== 0 || !empty($post_id)) ? get_the_title($post_id) : '';
        $page_url = (!empty($_POST['page_url'])) ? $_POST['page_url'] : '';
        Session::update( $cookie_json->guid, $page_title, $page_url, 1, $session_data['search'], 1);
        wp_die();
    }

    function rest_record_article_read(){
        //check_ajax_referer('ajax_record_article_read_nonce', 'security');

        $post_id = (!empty($_POST['post_id'])) ? $_POST['post_id'] : '';
        Visit::update_page_read( intval( $post_id) );

        $session_data = Helpers::get_session_data();
        if(!isset($_COOKIE[$session_data['session_cookie_name']])) {
            wp_die();
        }

        $ciphertext = stripslashes($_COOKIE[$session_data['session_cookie_name']]);
        $decrypted_string = openssl_decrypt($ciphertext, "AES-128-ECB", $session_data['secret_encrypt_token']);
        $cookie_json = json_decode($decrypted_string);
        if(!isset($cookie_json->guid)) {
            wp_die();
        }

        $page_title = ($post_id !== 0 || !empty($post_id)) ? get_the_title($post_id) : '';
        $page_url = (!empty($_POST['page_url'])) ? $_POST['page_url'] : '';
        Session::update( $cookie_json->guid, $page_title, $page_url, 1, $session_data['search'], 1);
        return new WP_REST_Response(null, 204);
        wp_die();
    }

    function rest_record_clicks() {
        $page_url = (!empty($_POST['page_url'])) ? $_POST['page_url'] : '';
        $page_title = (!empty($_POST['page_title'])) ? $_POST['page_title'] : '';
        $click_type = (!empty($_POST['click_type'])) ? $_POST['click_type'] : '';
        $link_content = (!empty($_POST['link_content'])) ? $_POST['link_content'] : '';

        TrackClicks::record_click_event($page_url, $page_title, $click_type, $link_content);
        $response = array('message' => 'success');
        return new WP_REST_Response(json_encode($response), 200);
        wp_die();
    }
}

$TrackingAjaxQuery = new TrackingAjaxQuery;
