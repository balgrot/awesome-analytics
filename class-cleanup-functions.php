<?php
/**
 * Delete old analytics data.
 */
namespace AwesomeAnalytics;

class DeleteQuery {

    /**
	 * Start performing actions.
	 *
	 * @return void
	 */
    function __construct() {

        // set up an endpoint to access all live domains
        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', '/analytics', array(
                'methods' => 'DELETE',
                'callback' => array( $this, 'cleanup_old_analytics_data'),
                'show_in_index' => false,
                'permission_callback' => '__return_true',
                'args' => array(
                    'key' => array(
                        'required' => true,
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    ),
                    ),
                )
            );
        });

		
        
	}

    /**
	 * Delete all data from 120 days and before
	 *
	 * @return void
	 */
    public static function delete_records() {
        global $wpdb;
        date_default_timezone_set('America/Los_Angeles');
        $sessions_table  = TABLE_ANALYTICS_SESSIONS;
        $pagevisits_table  = TABLE_ANALYTICS_PAGE_VISITS;
        $referrers_table  = TABLE_ANALYTICS_REFERRERS;
        $delete_date = date("Y-m-d", strtotime('-120 days')); //Go Back 120 days from today's date
        //Delete Records after 120 days
        $delete_sessions = $wpdb->get_results(" DELETE FROM $sessions_table WHERE created_at < '{$delete_date}' ");
        $delete_pagevisits = $wpdb->get_results(" DELETE FROM $pagevisits_table WHERE created_at < '{$delete_date}' ");
        $delete_referrers = $wpdb->get_results(" DELETE FROM $referrers_table WHERE created_at < '{$delete_date}' ");
    }

    
    function cleanup_old_analytics_data( $data ) {

        if( AWESOME_ANALYTICS_REST_API_TOKEN !== $data['key'] ) {

            return new \WP_Error( 'not_authorized', 'You are not authorized', array( 'status' => 404 ) );

        } else {

            $current_hour = date("G");

            if($current_hour > 3) {
                return new \WP_REST_Response( 'Cleanup called at wrong time.', 200 );
            }

            if( is_multisite() ) {

                global $wpdb;

                $blog_ids = $wpdb->get_col( "SELECT blog_id FROM `wp_blogs`");

                if(!empty($blog_ids)) {

                    foreach( $blog_ids as $blog_id ) {

                        switch_to_blog( $blog_id );

                        DeleteQuery::delete_records();

                        restore_current_blog();

                    }

                    return new \WP_REST_Response( "Cleanup finished!", 200 );

                }

                return new \WP_Error( 'no_results', 'No sites found in multisite.', array( 'status' => 200 ) );

            } else {

                DeleteQuery::delete_records();
                
                return new \WP_REST_Response( "Cleanup finished!", 200 );

            }
  
        }

        return new \WP_Error( 'no_results', 'Nothing was found', array( 'status' => 200 ) );

    }

}


$DeleteQuery = new DeleteQuery;

