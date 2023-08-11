<?php
/**
 * Perform actions for Website users.
 */
namespace AwesomeAnalytics;

class User {

    /**
	 * Start performing actions.
	 *
	 * @return void
	 */
    function __construct() {

        add_action( 'user_register', array( $this, 'user_registered' ));
        add_action( 'delete_user', array( $this, 'user_deleted' ), 10, 3);
        add_action( 'wp_login', array( $this, 'user_login' ), 10, 2 );

    }

    public static function create( int $wp_user_id, string $source = "Website", string $ip ) {

        global $wpdb;
        $table = TABLE_ANALYTICS_USERS;
        date_default_timezone_set('America/Los_Angeles');
        
        //get the ip location data
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
        $user_location_data= json_decode($response);
        
        $user_timezone = null;
        $user_city = null;
        $user_region_code = null;
        $user_region_name = null;
        $user_zip_code = null;
        $user_country_code = null;
        $user_country_name = null;
        $user_latitude = null;
        $user_longitude = null;
        
        if (!empty($user_location_data)){
            
            if(!empty($user_location_data->time_zone)){
                $user_timezone = $user_location_data->time_zone; 
            } 
            if(!empty($user_location_data->city)){
                $user_city = $user_location_data->city; 
            }
            if(!empty($user_location_data->region_code)){
                $user_region_code = $user_location_data->region_code; 
            } 
            if(!empty($user_location_data->region_name)){
                $user_region_name = $user_location_data->region_name; 
            } 
            if(!empty($user_location_data->zip_code)){
                $user_zip_code = $user_location_data->zip_code; 
            }  
            if(!empty($user_location_data->country_code)){
                $user_country_code = $user_location_data->country_code; 
            }
            if(!empty($user_location_data->country_name)){
                $user_country_name = $user_location_data->country_name; ;
            }
            if(!empty($user_location_data->latitude)){
                $user_latitude = $user_location_data->latitude; 
            }
            if(!empty($user_location_data->longitude)){
                $user_longitude = $user_location_data->longitude; 
            }
        }

        $wp_user = get_user_by('id', $wp_user_id);
        $registered = $wp_user->user_registered;
        $login_at = date("Y-m-d H:i:s");

        $result = $wpdb->insert( 
            $table,
            array( 
                'wp_user_id' => $wp_user_id, 
                'registered' => $registered,
                'source' => $source,
                'timezone' => $user_timezone,
		        'city' => $user_city,
	            'region_code' => $user_region_code,
	            'region_name' => $user_region_name,
	            'zip_code' => $user_zip_code,
	            'country_code' => $user_country_code,
	            'country_name' => $user_country_name,
	            'latitude' => $user_latitude,
	            'longitude' => $user_longitude,
                'last_login' => $login_at,
            ),
            array(
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
            )
        );
        if($result == 1) {
            return $wp_user_id;
        } else {
            return;
        }
    }

    public static function update( int $wp_user_id, $login_at = null, $deleted_at = null, $source = null ) {

        global $wpdb;
        $table = TABLE_ANALYTICS_USERS;
        $user = User::get($wp_user_id);

        if(empty($user)) {
            return;
        }

        if(!$deleted_at) {
            $deleted_at = $user->deleted;
        }

        if(!$login_at) {
            $login_at = $user->last_login;
        }

        if(!$source) {
            $source = $user->source;
        }

        if ( false !== $user ) {
            $result = $wpdb->update(
                $table,
                array(
                    'last_login' => $login_at,
                    'deleted' => $deleted_at,
                    'source' => $source,
                ),
                array( 'ID' => $user->ID ),
                array(
                    '%s',
                    '%s',
                    '%s',
                )
            );
            if($result !== false) {
                return $wp_user_id;
            } else {
                return;
            }
        } else {
            return false;
        }
    }

    public static function get( int $wp_user_id ) {
        global $wpdb;
        $table = TABLE_ANALYTICS_USERS;
        $user = $wpdb->get_row(" SELECT * FROM {$table} WHERE wp_user_id = '{$wp_user_id}'" );
        if ( null !== $user ) {
            return $user;
        } else {
            return false;
        }
    }

    function user_registered( $user_id ) {
        $ip = Helpers::get_ip();
        User::create( $user_id, "Website", $ip );
    }

    function user_deleted( int $id, $reassign, $user ) {
        date_default_timezone_set('America/Los_Angeles');
        $deleted_at = date("Y-m-d H:i:s");
        User::update( $id, null, $deleted_at );
    }

    function user_login( string $user_login, $user ) {
        date_default_timezone_set('America/Los_Angeles');
        $login_at = date("Y-m-d H:i:s");
        $new_user = User::get($user->ID);
        if(empty($new_user)) {
            $ip = Helpers::get_ip();
            User::create( $user->ID, "Website", $ip );
        }
        User::update( $user->ID, $login_at );
    }

}

$User = new User;

