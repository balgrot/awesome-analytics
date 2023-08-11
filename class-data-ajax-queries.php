<?php
/**
 * Initialize ajax methods for ajax requests.
 */
namespace AwesomeAnalytics;

class AjaxQuery {
    /**
	 * Start performing actions.
	 *
	 * @return void
	 */
    function __construct() {
        add_action( 'wp_ajax_ajax_load_all_analytics_data', array( $this, 'ajax_load_all_analytics_data' ) );
        add_action( 'wp_ajax_ajax_update_analytics_data', array( $this, 'ajax_update_analytics_data' ) );
        add_action('wp_ajax_ajax_autocomplete_search_posts', array( $this,'ajax_autocomplete_search_posts') );
        //add_action( 'wp_ajax_ajax_analytics_query_entry_pages', array( $this, 'ajax_analytics_query_entry_pages' ) );
        
    }
    function ajax_load_all_analytics_data() {

        check_ajax_referer( 'analytics_nonce', 'security' );

        if( isset($_REQUEST) ) {

            date_default_timezone_set('America/Los_Angeles'); // PST

            $start_date = (isset($_POST['start_date'])) ? $_POST['start_date'] : date("Y-m-d H:i:s", strtotime("yesterday"));
            $end_date = (isset($_POST['end_date'])) ? $_POST['end_date'] : date("Y-m-d H:i:s", strtotime("yesterday"));
            $function_name = (isset($_POST['function_name'])) ? $_POST['function_name'] : '';
            $per_page = (isset($_POST['per_page'])) ? $_POST['per_page'] : 10;
            $offset = (isset($_POST['offset'])) ? $_POST['offset'] : 0;
            $data_view = (isset($_POST['data_view'])) ? $_POST['data_view'] : 'titles';
            $engines_view = (isset($_POST['data_view'])) ? $_POST['data_view'] : 'social_networks';
            $campaign_view = (isset($_POST['data_view'])) ? $_POST['data_view'] : 'campaign_mediums';
            $post_name = (isset($_POST['post_name'])) ? $_POST['post_name'] : '';

            $today = strtotime('today');
            $end = strtotime($_POST['end_date']);

            if( $end < $today ) {
                $expiration = 86400; // 24 hours
            } else {
                $expiration = 300; // 5 minutes
            }

            switch ($function_name) {
                case "quick-stats":
                    if ( false === ( $result = get_transient( 'awesome-analytics-quick-stats-' . $start_date . '-' . $end_date ) ) ) {
                        $result = json_encode( array('result' => DataQuery::quick_stats( $start_date, $end_date )) );
                        set_transient('awesome-analytics-quick-stats-' . $start_date . '-' . $end_date, $result, $expiration);
                    }
                    $result = get_transient('awesome-analytics-quick-stats-' . $start_date . '-' . $end_date);
                    echo $result;
                    break;
                case 'visit-overview':
                    if ( false === ( $result = get_transient( 'awesome-analytics-visit-overview-' . $start_date . '-' . $end_date ) ) ) {
                        $result = json_encode( array('result' => DataQuery::query_visit_overview( $start_date, $end_date)) );
                        set_transient('awesome-analytics-visit-overview-' . $start_date . '-' . $end_date, $result, $expiration);
                    }
                    $result = get_transient('awesome-analytics-visit-overview-' . $start_date . '-' . $end_date);
                    echo $result;
                    break;
                case "page-visits":
                    if($data_view === 'titles') {
                        if ( false === ( $result = get_transient( 'awesome-analytics-page-visits-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset.'-titles' ) ) ) {
                            $result = json_encode( array('result' => DataQuery::query_page_visits_titles( $start_date, $end_date, $per_page, $offset )) );
                            set_transient('awesome-analytics-page-visits-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-titles', $result, $expiration);
                        }
                        $result = get_transient( 'awesome-analytics-page-visits-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-titles' );
                    } else if($data_view === 'paths') {
                        if ( false === ( $result = get_transient( 'awesome-analytics-page-visits-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-paths' ) ) ) {
                            $result = json_encode( array('result' => DataQuery::query_page_visits_paths( $start_date, $end_date, $per_page, $offset )) );
                            set_transient('awesome-analytics-page-visits-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-paths', $result, $expiration);
                        }
                        $result = get_transient( 'awesome-analytics-page-visits-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-paths' ); 
                    }
                    echo $result;
                    break;
                case "entry-pages":
                    if($data_view === 'titles') {
                        if ( false === ( $result = get_transient( 'awesome-analytics-entry-pages-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-titles' ) ) ) {
                            $result = json_encode( array('result' => DataQuery::query_entry_pages_titles( $start_date, $end_date, $per_page, $offset )) );
                            set_transient('awesome-analytics-entry-pages-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-titles', $result, $expiration);
                        }
                        $result = get_transient( 'awesome-analytics-entry-pages-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-titles' );
                        
                    } else if($data_view === 'paths') {
                        if ( false === ( $result = get_transient( 'awesome-analytics-entry-pages-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-paths' ) ) ) {
                            $result = json_encode( array('result' => DataQuery::query_entry_pages_paths( $start_date, $end_date, $per_page, $offset )) );
                            set_transient('awesome-analytics-entry-pages-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-paths', $result, $expiration);
                        }
                        $result = get_transient( 'awesome-analytics-entry-pages-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-paths' );
                        
                    }
                    echo $result;
                    break;
                
                case "exit-pages":
                    if($data_view === 'titles') {
                        if ( false === ( $result = get_transient( 'awesome-analytics-exit-pages-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-titles' ) ) ) {
                            $result = json_encode( array('result' => DataQuery::query_exit_pages_titles( $start_date, $end_date, $per_page, $offset )) );
                            set_transient('awesome-analytics-exit-pages-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-titles', $result, $expiration);
                        }
                        $result = get_transient( 'awesome-analytics-exit-pages-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-titles' );
                    } else if($data_view === 'paths') {
                        if ( false === ( $result = get_transient( 'awesome-analytics-exit-pages-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-paths' ) ) ) {
                            $result = json_encode( array('result' => DataQuery::query_exit_pages_paths( $start_date, $end_date, $per_page, $offset )) );
                            set_transient('awesome-analytics-exit-pages-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-paths', $result, $expiration);
                        }
                        $result = get_transient( 'awesome-analytics-exit-pages-' . $start_date . '-' .'-'.$per_page.'-'.$offset. $end_date . '-paths' );
                    }
                    echo $result;
                    break;
                
                case "blog-visits":
                    if($data_view === 'titles') {
                        if ( false === ( $result = get_transient( 'awesome-analytics-blog-visits-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-titles' ) ) ) {
                            $result = json_encode( array('result' => DataQuery::query_blog_visits_titles( $start_date, $end_date, $per_page, $offset )) );
                            set_transient('awesome-analytics-blog-visits-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-titles', $result, $expiration);
                        }
                        $result = get_transient( 'awesome-analytics-blog-visits-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-titles' );
                    } else if($data_view === 'paths') {
                        if ( false === ( $result = get_transient( 'awesome-analytics-blog-visits-' . $start_date . '-' .'-'.$per_page.'-'.$offset. $end_date . '-paths' ) ) ) {
                            $result = json_encode( array('result' => DataQuery::query_blog_visits_paths( $start_date, $end_date, $per_page, $offset )) );
                            set_transient('awesome-analytics-blog-visits-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-paths', $result, $expiration);
                        }
                        $result = get_transient( 'awesome-analytics-blog-visits-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-paths' );
                    }
                    echo $result;
                    break;
                case 'single-blog-post-visits':
                    if ( false === ( $result = get_transient( 'awesome-analytics-single-blog-post-visits-' . $start_date . '-' . $end_date ) ) ) {

                        $total_days = Helpers::count_days_between_dates($start_date, $end_date);
                        if ($total_days < 7){
                            $start_date = date("Y-m-d",strtotime("-7 days", strtotime($end_date)));
                        }
                        $blog_visit_data = DataQuery::query_blog_visits_in_time_period( $start_date, $end_date, $post_name);
                        $result = json_encode( array('result' => $blog_visit_data));

                        set_transient('awesome-analytics-single-blog-post-visits-' . $start_date . '-' . $end_date, $result, $expiration);
                    }
                    $result = get_transient('awesome-analytics-single-blog-post-visits-' . $start_date . '-' . $end_date);
                    echo $result;
                    break;
                case "referrers":
                    if ( false === ( $result = get_transient( 'awesome-analytics-referrers-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset) ) ) {
                        $result = json_encode( array('result' => DataQuery::query_pretty_referrers( $start_date, $end_date, $per_page, $offset )) );
                        set_transient('awesome-analytics-referrers-' . $start_date . '-' . $end_date.'-'.$per_page.'-'.$offset, $result, $expiration);
                    }
                    $result = get_transient('awesome-analytics-referrers-' . $start_date . '-' . $end_date.'-'.$per_page.'-'.$offset);
                    echo $result;
                    break;
                case "vists-per-day":
                    if ( false === ( $result = get_transient( 'awesome-analytics-visits-per-day-' . $start_date . '-' . $end_date ) ) ) {

                        $days_of_week_labels = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
                        $days_of_week_visits = [0,0,0,0,0,0,0];
                        $days_of_week_reads = [0,0,0,0,0,0,0];
                        $total_days = Helpers::count_days_between_dates($start_date, $end_date);
                        if ($total_days < 7){
                            $start_date = date("Y-m-d",strtotime("-7 days", strtotime($end_date)));
                        }
                        $days_of_week_data = DataQuery::query_reads_on_days_of_week($start_date, $end_date);
                        
                        if(!empty($days_of_week_data)) {

                            foreach($days_of_week_data as $stat) {

                                $day_key = array_search($stat['weekday'], $days_of_week_labels);
                                $days_of_week_visits[$day_key] = $stat['total_visits'];
                                $days_of_week_reads[$day_key] = $stat['page_reads'];
                            
                            }

                        }

                        $result = json_encode( array('result' => array('labels' => $days_of_week_labels, 'visits' => $days_of_week_visits, 'reads' => $days_of_week_reads)));

                        set_transient('awesome-analytics-visits-per-day-' . $start_date . '-' . $end_date, $result, $expiration);
                    }
                    $result = get_transient('awesome-analytics-visits-per-day-' . $start_date . '-' . $end_date);
                    echo $result;
                    break;
                case 'campaign-mediums-sources':
                    if( $campaign_view === 'campaign_mediums' ){
                        if ( false === ( $result = get_transient( 'awesome-analytics-campaign-medium-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-mediums' ) ) ) {
                            $result = json_encode( array('result' => DataQuery::query_campaign_mediums( $start_date, $end_date)) );
                            set_transient('awesome-analytics-campaign-medium-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-mediums', $result, $expiration);
                        }
                        $result = get_transient('awesome-analytics-campaign-medium-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-mediums');
                    } else if ( $campaign_view === 'campaign_sources' ) {
                        if ( false === ( $result = get_transient( 'awesome-analytics-campaign-mediums-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-sources' ) ) ) {
                            $result = json_encode( array('result' => DataQuery::query_campaign_sources( $start_date, $end_date)) );
                            set_transient('awesome-analytics-campaign-mediums-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset. '-sources', $result, $expiration);
                        }
                        $result = get_transient('awesome-analytics-campaign-mediums-' . $start_date . '-' . $end_date . '-' . $per_page . '-' . $offset . '-sources');
                    }
                    echo $result;
                    break;
                case 'campaign-data':
                    if ( false === ( $result = get_transient( 'awesome-analytics-campaign-data-' . $start_date . '-' . $end_date.'-'.$per_page.'-'.$offset ) ) ) {
                        $result = json_encode( array('result' => DataQuery::query_campaign_data( $start_date, $end_date, $per_page, $offset)) );
                        set_transient('awesome-analytics-campaign-data-' . $start_date . '-' . $end_date.'-'.$per_page.'-'.$offset , $result, $expiration);
                    }
                    $result = get_transient('awesome-analytics-campaign-data-' . $start_date . '-' . $end_date.'-'.$per_page.'-'.$offset );
                    echo $result;
                    break;
                case 'sessions-locations':
                    if ( false === ( $result = get_transient( 'awesome-analytics-session-locations-' . $start_date . '-' . $end_date . '-' . $per_page . '-' . $offset) ) ) {
                        $result = json_encode( array('result' => DataQuery::query_session_locations( $start_date, $end_date, $per_page, $offset)) );
                        set_transient('awesome-analytics-session-locations-' . $start_date . '-' . $end_date . '-' . $per_page . '-' . $offset , $result, $expiration);
                    }
                    $result = get_transient('awesome-analytics-session-locations-' . $start_date . '-' . $end_date . '-' . $per_page . '-' . $offset);
                    echo $result;
                    break;
                case 'user-locations':
                    if ( false === ( $result = get_transient( 'awesome-analytics-user-locations-' . $start_date . '-' . $end_date . '-' . $per_page . '-' . $offset ) ) ) {
                        $result = json_encode( array('result' => DataQuery::query_user_locations($per_page, $offset)) );
                        set_transient('awesome-analytics-user-locations-' . $start_date . '-' . $end_date.'-'.$per_page.'-'.$offset  , $result, $expiration);
                    }
                    $result = get_transient('awesome-analytics-user-locations-' . $start_date . '-' . $end_date.'-'.$per_page.'-'.$offset );
                    echo $result;
                    break;
                case 'domain-metrics':
                    if ( false === ( $result = get_transient( 'awesome-analytic-domain-metrics-' . $start_date . '-' . $end_date ) ) ) {
                        $result = json_encode( array('result' => DataQuery::query_domain_metrics()) );
                        set_transient('awesome-analytic-domain-metrics-' . $start_date . '-' . $end_date , $result, $expiration);
                    }
                    $result = get_transient('awesome-analytic-domain-metrics-' . $start_date . '-' . $end_date );
                    echo $result;
                    break;
                case "keywords":
                    if ( false === ( $result = get_transient( 'awesome-analytics-keywords-' . $start_date . '-' . $end_date.'-'.$per_page.'-'.$offset ) ) ) {
                        $result = json_encode( array('result' => DataQuery::query_keywords( $start_date, $end_date, $per_page, $offset )) );
                        set_transient('awesome-analytics-keywords-' . $start_date . '-' . $end_date.'-'.$per_page.'-'.$offset , $result, $expiration);
                    }
                    $result = get_transient('awesome-analytics-keywords-' . $start_date . '-' . $end_date.'-'.$per_page.'-'.$offset );
                    echo $result;

                break;
                case 'engines':
                    if( $engines_view === 'social_networks' ){
                        if ( false === ( $result = get_transient( 'awesome-analytics-engines-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset.'-social-networks' ) ) ) {
                            $result = json_encode( array('result' => DataQuery::query_networks( $start_date, $end_date, $per_page, $offset )) );
                            set_transient('awesome-analytics-engines-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset.'-social-networks' , $result, $expiration);
                        }
                        $result = get_transient('awesome-analytics-engines-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset.'-social-networks');
                    } else {
                        if ( false === ( $result = get_transient( 'awesome-analytics-engines-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset.'-search-networks' ) ) ) {
                            $result = json_encode( array('result' => DataQuery::query_engines( $start_date, $end_date, $per_page, $offset )) );
                            set_transient('awesome-analytics-engines-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset.'-search-networks' , $result, $expiration);
                        }
                        $result = get_transient('awesome-analytics-engines-' . $start_date . '-' . $end_date .'-'.$per_page.'-'.$offset.'-search-networks');
                    }
                    echo $result;
                break;
                
                case 'user-accounts':

                    if ( false === ( $result = get_transient( 'awesome-analytics-user-accounts-' . $start_date . '-' . $end_date  ) ) ) {
                        $report_start = date("Y-m-d", strtotime($start_date));
                        $report_end = date("Y-m-d", strtotime($end_date));
                        $days = Helpers::count_days_between_dates( $start_date, $end_date );
                        $users_deleted =0;
                        $historical = [];
                        $all_sources = [];
                        $max_users = 0;
                        $labels = [];

                        if ($days == 6){
                            $report_start = $report_end; 
                            $report_end = date("Y-m-d", strtotime("-1 day", strtotime($report_start)));
                        }
                        else if ($days < 56 && $days > 6){
                            $report_start = date("Y-m-d", strtotime("-1 week", strtotime($report_end))); 
                        }else if ($days > 56){
                            $report_start = date("Y-m-d", strtotime("-1 month", strtotime($report_end)));
                        }

                        for ($i = 0; $i < 7; $i++) {

                            if ($days < 6){
                                $labels[] = $report_start; 
                                $report_end = $report_start;
                                $report_start = date("Y-m-d", strtotime("-1 day", strtotime($report_start)));
                            }else if($days == 6){
                                $labels[] = $report_start;
                                $report_end  = $report_start;
                                $report_start = date("Y-m-d", strtotime("-1 day", strtotime($report_start)));
                            } 
                            else if($days < 56){ 
                                $labels[] = $report_start. " - " .$report_end;
                                $report_end  = date("Y-m-d", strtotime("-8 days", strtotime($report_end)));
                                $report_start = date("Y-m-d", strtotime("-8 days", strtotime($report_start)));
                            }
                            else{
                                $labels[] = date("F", strtotime($report_start)). " - " .date("F", strtotime($report_end));
                                $report_end  = date("Y-m-d", strtotime("-1 month", strtotime($report_end)));
                                $report_start = date("Y-m-d", strtotime("-1 month", strtotime($report_start)));
                            }

                            $result = DataQuery::query_user_overview( $report_start, $report_end);

                            $historical[] = $result;
                            $group_users = 0;

                            if(!empty($result['sources'])) {
                                foreach($result['sources'] as $source => $total) {
                                    $all_sources[] = $source; 
                                    $group_users = $group_users + (int) $total['total'];  
                                    
                                }
                            }
                            if($group_users > $max_users) {
                                $max_users = $group_users;
                            } 

                        } //End of Loop
                    
                        if($days !== 6){
                            $historical = array_reverse($historical);
                            $labels = array_reverse($labels);
                        }

                        $all_sources = array_unique($all_sources);
                        $dataset = [];
                        $deleted_data = [];
                        
                        for ($i = 0; $i < 7; $i++) {
                    
                            foreach($all_sources as $source) {
                                if(isset($historical[$i]['sources'][$source])) {
                                    $dataset[$source][] = $historical[$i]['sources'][$source]['total'];
                                } else {
                                    $dataset[$source][] = 0;
                                }
                            }

                            if(isset($historical[$i]['user_deleted'])) {
                                $deleted_data[$users_deleted][] = $historical[$i]['user_deleted'];
                                
                            } else {
                                $deleted_data[$users_deleted][] = 0;
                            }

                        }
                        
                        $data = [];
                        $colors = ["#2D8F00","#4B89AA","#76c893", "#546a79","#3AB800" ];
                        $count = 0;
                        // registered users and sources
                        foreach($dataset as $source => $set) {
                    
                            $data[] = [
                                "label" => ucwords($source),
                                "backgroundColor" => $colors[$count],
                                "data" => $set,
                                "stack" => '0'
                                
                            ];
                            $count++;
                        }
                        //deleted users
                        foreach($deleted_data as $deleted => $set) {
                            
                            $data[] = [
                                "label" => 'Deleted Users',
                                "backgroundColor" => "#e76f51",
                                "data" => $set,
                                "stack" => '1'
                                
                            ];
                            $count++;
                        }
                    $result = json_encode( array('result' => array('labels' => $labels, 'data' => $data, 'maxUsers' => $max_users)));
                    set_transient('awesome-analytics-user-accounts-' . $start_date . '-' . $end_date  , $result, $expiration);
                    }
                    $result = get_transient('awesome-analytics-user-accounts-' . $start_date . '-' . $end_date );
                    echo $result;

                break;
                case 'visits-per-hour':
                    if ( false === ( $result = get_transient( 'awesome-analytics-visits-per-hour-' . $start_date . '-' . $end_date ) ) ) {
                        $days_of_week_labels = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
                        $table_data =[
                            'results' =>[],
                            'legend_max' => 0,
                            'legend_array' => [
                                [
                                    'min' => 0,
                                    'max' => 0,
                                    'color' =>'#94d4eb'
                                ],
                                [
                                    'min' => 0,
                                    'max' => 0,
                                    'color' => '#48a4f6'
                                ],
                                [
                                    'min' => 0,
                                    'max' => 0,
                                    'color' => '#4087f3'
                                ],
                                [
                                    'min' => 0,
                                    'max' => 0,
                                    'color' => '#3b77d1'
                                ]
                            ],
                            'days_of_week_labels' => $days_of_week_labels,
                            'start_date' =>$start_date,
                            'end_date' =>$end_date
                        ];
                        $table_data['results'] = ["12am"=>[0,0,0,0,0,0,0],"1am"=>[0,0,0,0,0,0,0],
                                        "2am"=>[0,0,0,0,0,0,0],"3am"=>[0,0,0,0,0,0,0],
                                        "4am"=>[0,0,0,0,0,0,0],"5am"=>[0,0,0,0,0,0,0],
                                        "6am"=>[0,0,0,0,0,0,0],"7am"=>[0,0,0,0,0,0,0],
                                        "8am"=>[0,0,0,0,0,0,0],"9am"=>[0,0,0,0,0,0,0],
                                        "10am"=>[0,0,0,0,0,0,0],"11am"=>[0,0,0,0,0,0,0],
                                        "12pm"=>[0,0,0,0,0,0,0],"1pm"=>[0,0,0,0,0,0,0],
                                        "2pm"=>[0,0,0,0,0,0,0],"3pm"=>[0,0,0,0,0,0,0],
                                        "4pm"=>[0,0,0,0,0,0,0],"5pm"=>[0,0,0,0,0,0,0],
                                        "6pm"=>[0,0,0,0,0,0,0],"7pm"=>[0,0,0,0,0,0,0],
                                        "8pm"=>[0,0,0,0,0,0,0],"9pm"=>[0,0,0,0,0,0,0],
                                        "10pm"=>[0,0,0,0,0,0,0],"11pm"=>[0,0,0,0,0,0,0]];

                        $total_days = Helpers::count_days_between_dates($start_date, $end_date);
                        if ($total_days < 7){
                            $start_date = date("Y-m-d",strtotime("-7 days", strtotime($end_date)));
                        }

                        $sessions = DataQuery::query_sessions_created_at_in_time_period($start_date, $end_date);
                        
                        if(!empty($sessions)) {

                            foreach ($sessions as $session){
                                $hour = date("g", strtotime($session['created_at']));
                                $time_of_day = date("a", strtotime($session['created_at']));
                                $hour_key= $hour.$time_of_day;

                                $day_of_week = date('l', strtotime($session['created_at']));
                                $day_key = '';
                                switch ($day_of_week){
                                    case 'Sunday':
                                        $day_key =0;
                                    break;
                                    case 'Monday':
                                        $day_key =1;
                                    break;
                                    case 'Tuesday':
                                        $day_key =2;
                                    break;
                                    case 'Wednesday':
                                        $day_key =3;
                                    break;
                                    case 'Thursday':
                                        $day_key =4;
                                    break;
                                    case 'Friday':
                                        $day_key =5;
                                    break;
                                    case 'Saturday':
                                        $day_key =6;
                                    break;
                                }
                                $table_data['results'][$hour_key][$day_key] = $table_data['results'][$hour_key][$day_key]+1;


                            }//end of sessions loop
                            

                            foreach ($table_data['results'] as $labels){
                                foreach ($labels as $label){
                                if($label > $table_data['legend_max']){
                                    $table_data['legend_max'] = $label;
                                }
                            }
                            }

                        }

                        $table_data['legend_max'] = ceil($table_data['legend_max']/4)*4;

                        $divided_number = $table_data['legend_max'] /4;

                        $table_data['legend_array'][0]['min'] = 0;
                        $table_data['legend_array'][0]['max'] = $divided_number ;
                        $table_data['legend_array'][1]['min'] = $divided_number +1;
                        $table_data['legend_array'][1]['max'] = $divided_number *2;
                        $table_data['legend_array'][2]['min'] = ($divided_number *2) +1;
                        $table_data['legend_array'][2]['max'] = $divided_number *3;
                        $table_data['legend_array'][3]['min'] = ($divided_number *3)+1;
                        $table_data['legend_array'][3]['max'] = $table_data['legend_max'];
                        
                        $result = json_encode( array('result' => $table_data));
                        
                        set_transient('awesome-analytics-visits-per-hour-' . $start_date . '-' . $end_date, $result, $expiration);
                    }
                    $result = get_transient('awesome-analytics-visits-per-hour-' . $start_date . '-' . $end_date);
                    echo $result;    
                break;
            case 'blog-category-visits':
                if ( false === ( $result = get_transient( 'awesome-analytics-blog-category-visits-' . $start_date . '-' . $end_date . '-' . $per_page . '-' . $offset ) ) ) {
                    $result = json_encode( array('result' => DataQuery::query_blog_category_visits($start_date, $end_date, $per_page, $offset)) );
                    set_transient('awesome-analytics-blog-category-visits-' . $start_date . '-' . $end_date.'-'.$per_page.'-'.$offset  , $result, $expiration);
                }
                $result = get_transient('awesome-analytics-blog-category-visits-' . $start_date . '-' . $end_date.'-'.$per_page.'-'.$offset );
                echo $result;
                break;
            }
            wp_die();
        }

        echo json_encode( array('error' => 'Nothing happened.'));

        wp_die();

    }

    function ajax_update_analytics_data(){

        if( isset($_REQUEST) ) {

            $start_date = (isset($_POST['start_date'])) ? $_POST['start_date'] : date("Y-m-d H:i:s", strtotime("yesterday"));
            $end_date = (isset($_POST['end_date'])) ? $_POST['end_date'] : date("Y-m-d H:i:s", strtotime("yesterday"));
            $function_name = (isset($_POST['function_name'])) ? $_POST['function_name'] : '';

            $per_page = ( isset($_POST['per_page']) ) ? $_POST['per_page'] : 10;
            //$offset = ;

            switch ($function_name) {
                case "entry-pages":
                    echo json_encode( array('result' => DataQuery::query_entry_pages_titles( $start_date, $end_date, $per_page )) );
                    break;
            }

            wp_die();
        }

        echo json_encode( array('error' => 'Nothing happened.'));

        wp_die();

    }

    function ajax_analytics_query_entry_pages() {

        check_ajax_referer( 'admin_nonce', 'security' );

        if(isset($_REQUEST)) {

            $start_date = (isset($_POST['start_date'])) ? $_POST['start_date'] : date("Y-m-d H:i:s", strtotime("yesterday"));
            $end_date = (isset($_POST['end_date'])) ? $_POST['end_date'] : date("Y-m-d H:i:s", strtotime("yesterday"));
            $per_page = (isset($_POST['per_page'])) ? $_POST['per_page'] : 10;
            $offset = (isset($_POST['offset'])) ? $_POST['offset'] : 0;
            $type = (isset($_POST['type'])) ? $_POST['type'] : 'title';

            if($type === 'titles') {
                echo json_encode( array( 'result' => DataQuery::query_entry_pages_titles( $start_date, $end_date, $per_page, $offset ) ) );
            } else if($type === 'paths') {
                echo json_encode( array( 'result' => DataQuery::query_entry_pages_paths( $start_date, $end_date, $per_page, $offset ) ) );
            }
        }

        wp_die();

    }

    function ajax_autocomplete_search_posts(){
        
        check_ajax_referer('posts_search_nonce', 'security');
        if (isset($_REQUEST)) {
            $search = (isset($_POST["search"])) ? $_POST["search"] : '';

            if (empty($search)) {
                echo json_encode("no search sent");
                wp_die();
            }
            $query = new \WP_Query( array( 's' => $search, 'posts_per_page' => 15, 'post_status' => 'publish' ) );

            $results = [];
            if ( $query->have_posts() ) {
                $posts = $query->posts;
                foreach($posts as $post) {
                    $results[] = array("label" => $post->post_title, "value" => $post->post_title);
                }
            }
            /* Restore original Post Data */
            wp_reset_postdata();

            echo json_encode($results);
        
        }
        wp_die();
    }
}

$AjaxQuery = new AjaxQuery;

