<?php
/**
 * Perform database query for data.
 */
namespace AwesomeAnalytics;

class DataQuery {

    /**
	 * Queries for the Top Bar of the Admin Page
	 *
	 * @return array stats for various data points
	 */
    public static function quick_stats( $start_date, $end_date ) {
        global $wpdb;
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        $sessions = [
            'sessions' => [
                'title' => 'Total Sessions',
            ],
            'avg_sessions' => [
                'title' => 'Average Session Duration',
            ],
            'visits' => [
                'title' => 'Total Visits',
            ],
            'pageviews' => [
                'title' => 'Total Page Views',
            ],
            'action' => [
                'title' => 'Average Actions',
            ],
            'reads' => [
                'title' => 'Total Page Reads',
            ],
            'avg_reads' => [
                'title' => 'Average Page Reads',
            ],
        ];

        $start_date = date("Y-m-d H:i:s", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));
        
        $days = Helpers::count_days_between_dates( $start_date, $end_date );
        $previous_start_date = date("Y-m-d H:i:s", strtotime($start_date) - ($days * DAY_IN_SECONDS));

        $sessions_results = $wpdb->get_results( " SELECT COUNT(unique_session) AS total_unique, AVG(visit_duration) AS avg_total_unique, COUNT(guid) AS total_visits, SUM(pages_visited) AS total_page_views, SUM(page_reads) AS total_page_reads, AVG(page_reads) AS avg_page_reads, AVG(actions) AS avg_actions, searches AS searches FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' " );

        $previous_sessions_results = $wpdb->get_results( " SELECT COUNT(unique_session) AS total_unique, AVG(visit_duration) AS avg_total_unique, COUNT(guid) AS total_visits, SUM(pages_visited) AS total_page_views, SUM(page_reads) AS total_page_reads, AVG(page_reads) AS avg_page_reads, AVG(actions) AS avg_actions, searches AS searches FROM {$sessions_table} WHERE created_at BETWEEN '{$previous_start_date}' AND '{$start_date}' " );

        if(empty($sessions_results) || empty($previous_sessions_results)) {
            return $sessions;
        }

        $session_stats = DataQuery::calculate_quick_stats( $sessions_results[0]->total_unique, $previous_sessions_results[0]->total_unique );

        $avg_session_stats = DataQuery::calculate_quick_stats( (($sessions_results[0]->avg_total_unique)/60), (($previous_sessions_results[0]->avg_total_unique)/60) );

        $avg_session_stats['total'] = number_format($avg_session_stats['total'], 1 );
        
        $visits_stats = DataQuery::calculate_quick_stats( $sessions_results[0]->total_visits, $previous_sessions_results[0]->total_visits );
        
        $reads_stats = DataQuery::calculate_quick_stats( $sessions_results[0]->total_page_reads, $previous_sessions_results[0]->total_page_reads );
        
        $avg_reads_stats = DataQuery::calculate_quick_stats( $sessions_results[0]->avg_page_reads, $previous_sessions_results[0]->avg_page_reads );
        
        $avg_reads_stats['total'] = number_format($avg_reads_stats['total'], 1 );
        
        $pageviews_stats = DataQuery::calculate_quick_stats( $sessions_results[0]->total_page_views, $previous_sessions_results[0]->total_page_views );
        
        $actions = DataQuery::calculate_quick_stats( $sessions_results[0]->avg_actions, $previous_sessions_results[0]->avg_actions );
        
        $actions['total'] = number_format($actions['total'], 1 );

        // if( !empty($sessions_results[0]->searches) && !empty($previous_sessions_results) && $sessions_results[0]->searches != 0 ){
        //     $searches = DataQuery::calculate_quick_stats( $sessions_results[0]->searches, $previous_sessions_results[0]->searches );
        //     $sessions['search'] = ['title' => 'Total Searches'];
        //     $sessions['search'] = array_merge($sessions['search'], $searches);
        // }

        $sessions['sessions'] = array_merge($sessions['sessions'], $session_stats);
        $sessions['avg_sessions'] = array_merge($sessions['avg_sessions'], $avg_session_stats);
        $sessions['visits'] = array_merge($sessions['visits'], $visits_stats);
        $sessions['pageviews'] = array_merge($sessions['pageviews'], $pageviews_stats);
        $sessions['reads'] = array_merge($sessions['reads'], $reads_stats);
        $sessions['avg_reads'] = array_merge($sessions['avg_reads'], $avg_reads_stats);
        $sessions['action'] = array_merge($sessions['action'], $actions);

        return $sessions;
    }

    public static function get_all_days_from_date_range( $start_date, $end_date ) {
        $start = new \DateTime($start_date);
        $end = new \DateTime($end_date);
        $interval = new \DateInterval('P1D');
        $dateRange = new \DatePeriod($start, $interval, $end);
        $days = array();
        foreach ($dateRange as $date) {
            $days[] = $date->format('Y-m-d');
        }
        return $days;
    }

    public static function calculate_bar_charts($start_date, int $timeframe){
        global $wpdb;
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        date_default_timezone_set('America/Los_Angeles');
    
        $start_date = date("Y-m-d", strtotime($start_date));
        $end_date = date("Y-m-d", strtotime("{$start_date} -" . $timeframe . " days"));

        $bar_charts = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'time_frame' => $timeframe,
            'avg_stats' => [
                'total_visits' => '',
                'total_actions' => '',
                'avg_actions' => '',
                'avg_duration' => ''
            ],
            'records' => []
        ];

        $dateArray = DataQuery::get_all_days_from_date_range( $end_date, $start_date );

        foreach( $dateArray as $date ) {
            $bar_charts['records'][$date] = [
                    'date' => $date,
                    'unique_visitors' => 0,
                    'returning_visitors' => 0,
                    'page_reads' => 0
            ];
        }

        $query_dates = "'" . implode( "','", esc_sql($dateArray) ) . "'";

        $avg_results = $wpdb->get_results(" SELECT created_at, SUM(actions) AS result_actions, AVG(visit_duration) AS result_duration, COUNT(guid) AS result_guids, COUNT(*) AS result_visits, COUNT(page_reads) AS result_reads FROM {$sessions_table} WHERE created_at BETWEEN '{$end_date}' AND '{$start_date}' ORDER BY created_at ASC ");

        if( !empty( $avg_results ) ) {
            $total_actions = $avg_results[0]->result_actions;
            $total_guids = $avg_results[0]->result_guids;

            $avg_actions = 0;
            if( $total_actions > 0 && $total_guids > 0 ){
                $avg_actions = round( ($total_actions / $total_guids), 2 );
            }

            // $duration = round( ($avg_results[0]->result_duration / 60), 2 );
            // $minutes = floor($duration);
            // $seconds = $duration - $minutes;
            // $avg_duration = $minutes . 'm ' . ($seconds * 100) . 's';
            $duration = (!empty($avg_results[0]->result_duration)) ? $avg_results[0]->result_duration : 0;
            $avg_duration = Helpers::seconds_to_time($duration);

            $bar_charts['avg_stats'] = [
                'total_visits' => !empty($avg_results[0]->result_visits) ? $avg_results[0]->result_visits : '---',
                'total_actions' => !empty($total_actions) ? $total_actions : '---',
                'avg_actions' => !empty($avg_actions) ? $avg_actions : '---',
                'avg_duration' => $avg_duration == '0m 0s' ? '---' : $avg_duration,
                'total_reads' => !empty($avg_results[0]->result_reads) ? $avg_results[0]->result_reads : '---',
            ];
        }

        $returning_visits_results = $wpdb->get_results(" SELECT COUNT(*) as returning_visitors, date(created_at) as created_at FROM {$sessions_table} WHERE unique_session IS NULL AND created_at BETWEEN '{$end_date}' AND '{$start_date}' GROUP BY DATE(created_at) ORDER BY created_at ASC");
        $unique_visits_results = $wpdb->get_results(" SELECT COUNT(*) as unique_visitors, date(created_at) as created_at FROM {$sessions_table} WHERE unique_session = 1 AND created_at BETWEEN '{$end_date}' AND '{$start_date}' GROUP BY DATE(created_at) ORDER BY created_at ASC");
        $page_reads_results = $wpdb->get_results(" SELECT COUNT(page_reads) as page_reads, date(created_at) as created_at FROM {$sessions_table} WHERE created_at BETWEEN '{$end_date}' AND '{$start_date}' GROUP BY DATE(created_at) ORDER BY created_at ASC");
        $merged_array = array_merge($returning_visits_results, $unique_visits_results, $page_reads_results);

        if( !empty( $merged_array ) ) {

            foreach($merged_array as $bar_chart) {

                $created_at = date("Y-m-d", strtotime($bar_chart->created_at));

                $bar_charts['records'][$created_at]['date'] = $created_at;
                if(isset($bar_chart->unique_visitors)) {
                    $bar_charts['records'][$created_at]['unique_visitors'] = $bar_chart->unique_visitors;
                }
                if (isset($bar_chart->returning_visitors)) {
                    $bar_charts['records'][$created_at]['returning_visitors'] = $bar_chart->returning_visitors;
                }
                if (isset($bar_chart->page_reads)) {
                    $bar_charts['records'][$created_at]['page_reads'] = $bar_chart->page_reads;
                }

            }
            
        }
        return $bar_charts;
    }
    /**
	 * Format Decimals 
	 *
	 * @return integer 
	 */
    public static function format_decimals( $number ){

        if( is_float($number) && $number >= 0.5 ){
            $number = ceil($number);
        } else if( is_float($number) && $number < 0.5 ){
            $number = number_format( $number, 1 );
        } else {
            $number = $number;
        }

        return $number;
    }

    /**
	 * Calculate Quick Stats 
	 *
	 * @return array 
	 */
    public static function calculate_quick_stats( $current_stat, $previous_stat ) {
        $statArray = [];
        $percent = 0;
        $difference = $current_stat - $previous_stat;
        
        //Check Total
        if( $current_stat === 0 || $current_stat === null ){
            $current_stat = 0;
        }
        
        //Format Difference
        //Check Difference
        if( $difference < 0 ) {
            $percent = ( $difference / $previous_stat ) * 100;
            $difference = DataQuery::format_decimals( $difference );
            $statArray['difference'] = abs($difference) . ' Less than Last Period';
        } else if($difference > 0) {
            $percent = ( $difference / $current_stat ) * 100;
            $difference = DataQuery::format_decimals( $difference );
            $statArray['difference'] = $difference . ' More than Last Period';
        } else {
            $statArray['difference'] = '- -';
        }

        //Check Percent
        if( $percent < 0 ){
            $statArray['class'] = 'down';
            $percent = '- ' . floor(abs($percent)) . '%';
        } else if( $percent > 0 ) {
            $statArray['class'] = 'up';
            $percent = '+ ' . ceil(abs($percent)) . '%';
        } else {
            $statArray['class'] = 'no-change';
            $percent = '- -';
        }

        $statArray['percentage'] = $percent;
        $statArray['total'] = $current_stat;
        
        return $statArray;
    }
    /**
	 * Queries 
	 *
	 * @return array 
	 */
    public static function query_entry_pages_titles( $start_date, $end_date, int $per_page=10, int $offset=0 ) {

        global $wpdb;
        date_default_timezone_set('America/Los_Angeles');
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        $query_offset = $offset * $per_page;
        $start_date = date("Y-m-d H:i:s", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));

        //Entry Pages query
        $entry_pages_titles_results = $wpdb->get_results("SELECT entry_page_title, COUNT(entry_page_title) AS result_titles FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY entry_page_title ORDER BY result_titles DESC LIMIT {$per_page} OFFSET {$query_offset}");

        //Loop through the data and store as variables
        $entries = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'per_page' => $per_page,
            'offset' => $offset,
            'selector' => 'entry-pages',
            'data_view' => 'titles',
            'description' => 'This is the first page of your site which a visitor sees, also known as a landing page.',
            'total_records' => 0,
            'total_visitors' => 0,
            'records' => [],
            'result_counts' => $entry_pages_titles_results
        ];


        $query_titles = [];
        if(!empty($entry_pages_titles_results)) {
            foreach( $entry_pages_titles_results as $entry_page_title ) {
                $query_titles[] = $entry_page_title->entry_page_title;

                $title = ucwords(strtolower($entry_page_title->entry_page_title));
                if(!isset($entries['records'][$title])) {
                    $entries['records'][$title] = [
                        'title' => $title,
                        'total_visitors' => 0,
                        'total_unique_visitors' => 0,
                        'bounces' => 0,
                    ];
                }

            }
        }
        $query_titles = "'" . implode( "','", esc_sql($query_titles) ) . "'";

        $total_records = $wpdb->get_results("SELECT count(distinct entry_page_title) as entry_page_total, SUM(pages_visited) as result_visitors FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'");

        $entries['total_visitors'] = $total_records[0]->result_visitors;
        $entries['total_records'] = $total_records[0]->entry_page_total;

        //Entry Pages query
        $session_results = $wpdb->get_results("SELECT * FROM {$sessions_table} WHERE entry_page_title IN ({$query_titles}) AND created_at BETWEEN '{$start_date}' AND '{$end_date}'");

        if(!empty($session_results)) {
            foreach( $session_results as $session ) {

                $title = ucwords(strtolower($session->entry_page_title));

                if(!isset($entries['records'][$title])) {
                    $entries['records'][$title] = [
                        'title' => $title,
                        'total_visitors' => 0,
                        'total_unique_visitors' => 0,
                        'bounces' => 0,
                    ];
                }
                $entries['records'][$title]['total_visitors'] += 1;
                if($session->unique_session) {
                    $entries['records'][$title]['total_unique_visitors'] += 1;
                }
                
                // adds bounce data to entry pages
                if($session->actions == 1) {
                    $entries['records'][$title]['bounces']++;
                }
            }
        }

        return $entries;

    }
    /**
	 * Queries 
	 *
	 * @return array 
	 */
    public static function query_entry_pages_paths( $start_date, $end_date, int $per_page=10, int $offset=0 ) {

        global $wpdb;
        date_default_timezone_set('America/Los_Angeles');
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        $is_index = false;
        $query_offset = $offset * $per_page;
        $start_date = date("Y-m-d H:i:s", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));

         //Loop through the data and store as variables
        $entries = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'per_page' => $per_page,
            'offset' => $offset,
            'selector' => 'entry-pages',
            'data_view' => 'paths',
            'description' => 'This is the first page of your site which a visitor sees, also known as a landing page.',
            'total_records' => 0,
            'total_visitors' => 0,
            'records' => []
        ];

        //Entry Pages query
        $entry_pages_paths_results = $wpdb->get_results("SELECT entry_page_path, COUNT(entry_page_path) AS result_entry_page_path FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY entry_page_path ORDER BY result_entry_page_path DESC LIMIT {$per_page} OFFSET {$query_offset}");
        $query_paths = [];
        if(!empty($entry_pages_paths_results)) {
            foreach( $entry_pages_paths_results as $entry_page_path ) {
                if(!$entry_page_path->entry_page_path) {
                    $is_index = true;
                } else {
                    $query_paths[] = $entry_page_path->entry_page_path;
                }

                if(!empty($entry_page_path->entry_page_path)) {
                    $page_path = strtolower($entry_page_path->entry_page_path);
                } else {
                    $page_path = "/";
                }
                
                if(!isset($entries['records'][$page_path])) {
                    $entries['records'][$page_path] = [
                        'path' => $page_path,
                        'total_visitors' => 0,
                        'total_unique_visitors' => 0,
                        'bounces' => 0,
                    ];
                }
            }
        }

        //echo "<pre>", print_r($query_paths,1), "</pre>";

        $query_paths = "'" . implode( "','", esc_sql($query_paths) ) . "'";
        
        $entry_pages_results = $wpdb->get_results("SELECT * FROM {$sessions_table} WHERE entry_page_path IN ({$query_paths}) AND created_at BETWEEN '{$start_date}' AND '{$end_date}' ");

        $entry_pages_index_results = [];

        if($is_index) {
            $entry_pages_index_results = $wpdb->get_results("SELECT * FROM {$sessions_table} WHERE entry_page_path IS NULL AND created_at BETWEEN '{$start_date}' AND '{$end_date}' ");
        }

        $entry_pages_results = array_merge($entry_pages_results, $entry_pages_index_results);

        //echo "<pre>", print_r($entry_pages_results,1), "</pre>";


        $total_records = $wpdb->get_results("SELECT count(distinct entry_page_url) as entry_page_total, SUM(entry_page_path) as result_visitors FROM {$sessions_table} WHERE entry_page_path IS NOT NULL AND created_at BETWEEN '{$start_date}' AND '{$end_date}';");
        
        $entries['total_records'] = $total_records[0]->entry_page_total;
        $entries['total_visitors'] = $total_records[0]->result_visitors;
        //Entry Pages query
        $session_results = $wpdb->get_results("SELECT * FROM {$sessions_table} WHERE entry_page_path IN ({$query_paths}) AND created_at BETWEEN '{$start_date}' AND '{$end_date}'");


        if(!empty($session_results)) {
           
            foreach( $session_results as $session ) {
                //
                if(!empty($session->entry_page_path)) {
                    $page_path = strtolower($session->entry_page_path);
                } else {
                    $page_path = "/";
                }

                if(!isset($entries['records'][$page_path])) {
                    $entries['records'][$page_path] = [
                        'path' => $page_path,
                        'total_visitors' => 0,
                        'total_unique_visitors' => 0,
                        'bounces' => 0,
                    ];
                }

                if($session->actions == 1) {
                    $entries['records'][$page_path]['bounces']++;
                }

                if($session->unique_session) {
                    $entries['records'][$page_path]['total_unique_visitors'] += 1;
                }

                $entries['records'][$page_path]['total_visitors'] ++;
                
            
                if(!empty($session->entry_page_query)) {
                    if(!isset($entries['records'][$page_path]['query'][$session->entry_page_query])) {
                        $entries['records'][$page_path]['query'][$session->entry_page_query] = [
                            'query' => $session->entry_page_query,
                            'total_visitors' => 0,
                            'total_unique_visitors' => 0,
                            'bounces' => 0,
                        ];
                    }
                    if($session->unique_session) {
                        $entries['records'][$page_path]['query'][$session->entry_page_query]['total_unique_visitors'] += 1;
                    }
                    $entries['records'][$page_path]['query'][$session->entry_page_query]['total_visitors'] ++;
                    if($session->actions == 1) {
                        $entries['records'][$page_path]['query'][$session->entry_page_query]['bounces']++;
                    }
                
                }else {
                    if(!isset($entries['records'][$page_path]['query'][$session->entry_page_query])) {
                        $entries['records'][$page_path]['query'][$session->entry_page_query] = [
                            'query' => '/',
                            'total_visitors' => 0,
                            'total_unique_visitors' => 0,
                            'bounces' => 0,
                        ];
                    }
                    if($session->unique_session) {
                        $entries['records'][$page_path]['query'][$session->entry_page_query]['total_unique_visitors'] += 1;
                    }
                    $entries['records'][$page_path]['query'][$session->entry_page_query]['total_visitors'] ++;
                    if($session->actions == 1) {
                        $entries['records'][$page_path]['query'][$session->entry_page_query]['bounces']++;
                    }
                }
            }

            uasort($entries['records'], function($a, $b) {
                return $b['total_visitors'] <=> $a['total_visitors'];
            });

            foreach($entries['records'] as $path => $value) {
                if(isset($value['query'])) {
                    uasort($entries['records'][$path]['query'], function($a, $b) {
                        return $b['total_visitors'] <=> $a['total_visitors'];
                    });
                }
            }
        }

        return $entries;

    }
    /**
	 * Queries 
	 *
	 * @return array 
	 */
    public static function query_exit_pages_titles( $start_date, $end_date, int $per_page=10, int $offset=0 ) {

        global $wpdb;
        date_default_timezone_set('America/Los_Angeles');
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        $query_offset = $offset * $per_page;
        $start_date = date("Y-m-d H:i:s", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));
        
        
        //Entry Pages query
        $exit_pages_titles_results = $wpdb->get_results("SELECT exit_page_title, COUNT(exit_page_title) AS result_titles FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY exit_page_title ORDER BY result_titles DESC LIMIT {$per_page} OFFSET {$query_offset}");
        
        //Loop through the data and store as variables
        $entries = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'per_page' => $per_page,
            'offset' => $offset,
            'selector' => 'exit-pages',
            'data_view' => 'titles',
            'description' => 'This is how many people leave your site from a particular page.',
            'total_records' => 0,
            'total_visitors' => 0,
            'records' => [],
            'result_counts' => $exit_pages_titles_results
        ];
        
        
        $query_titles = [];
        if(!empty($exit_pages_titles_results)) {
            foreach( $exit_pages_titles_results as $exit_page_title ) {
                $query_titles[] = $exit_page_title->exit_page_title;
        
                $title = ucwords(strtolower($exit_page_title->exit_page_title));
                if(!isset($entries['records'][$title])) {
                    $entries['records'][$title] = [
                        'title' => $title,
                        'total_visitors' => 0,
                        'total_unique_visitors' => 0,
                        'bounces' => 0,
                    ];
                }
        
            }
        }
        $query_titles = "'" . implode( "','", esc_sql($query_titles) ) . "'";
        
        $total_records = $wpdb->get_results("SELECT count(distinct exit_page_title) as exit_page_total, SUM(pages_visited) as result_visitors FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'");

        $entries['total_records'] = $total_records[0]->exit_page_total;
        $entries['total_visitors'] = $total_records[0]->result_visitors;
        
        //Entry Pages query
        $session_results = $wpdb->get_results("SELECT * FROM {$sessions_table} WHERE exit_page_title IN ({$query_titles}) AND created_at BETWEEN '{$start_date}' AND '{$end_date}'");
        
        if(!empty($session_results)) {
            foreach( $session_results as $session ) {
        
                $title = ucwords(strtolower($session->exit_page_title));
        
                if(!isset($entries['records'][$title])) {
                    $entries['records'][$title] = [
                        'title' => $title,
                        'total_visitors' => 0,
                        'total_unique_visitors' => 0,
                        'bounces' => 0,
                    ];
                }
                $entries['records'][$title]['total_visitors'] += 1;
                if($session->unique_session) {
                    $entries['records'][$title]['total_unique_visitors'] += 1;
                }
                
                // adds bounce data to entry pages
                if($session->actions == 1) {
                    $entries['records'][$title]['bounces']++;
                }
            }
        }
    
    return $entries;
    
    }
    /**
	 * Queries 
	 *
	 * @return array 
	 */ 
    public static function query_exit_pages_paths( $start_date, $end_date, int $per_page=10, int $offset=0 ) {
        
        global $wpdb;
        date_default_timezone_set('America/Los_Angeles');
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        $is_index = false;
        $query_offset = $offset * $per_page;
        $start_date = date("Y-m-d H:i:s", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));

         //Loop through the data and store as variables
        $entries = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'per_page' => $per_page,
            'offset' => $offset,
            'selector' => 'exit-pages',
            'data_view' => 'paths',
            'description' => 'This is how many people leave your site from a particular page.',
            'total_records' => 0,
            'total_visitors' => 0,
            'records' => []
        ];
        
        //Exit Pages query
        $exit_pages_paths_results = $wpdb->get_results("SELECT exit_page_path, COUNT(exit_page_path) AS result_exit_page_path FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY exit_page_path ORDER BY result_exit_page_path DESC LIMIT {$per_page} OFFSET {$query_offset}");
        $query_paths = [];
        if(!empty($exit_pages_paths_results)) {
            foreach( $exit_pages_paths_results as $exit_page_path ) {
                if(!$exit_page_path->exit_page_path) {
                    $is_index = true;
                } else {
                    $query_paths[] = $exit_page_path->exit_page_path;
                }
                if(!empty($exit_page_path->exit_page_path)) {
                    $page_path = strtolower($exit_page_path->exit_page_path);
                } else {
                    $page_path = "/";
                }
                if(!isset($entries['records'][$page_path])) {
                    $entries['records'][$page_path] = [
                        'path' => $page_path,
                        'total_visitors' => 0,
                        'total_unique_visitors' => 0,
                        'bounces' => 0,
                    ];
                }
            }
        }
        
        //echo "<pre>", print_r($query_paths,1), "</pre>";
        
        $query_paths = "'" . implode( "','", esc_sql($query_paths) ) . "'";
        $exit_pages_results = $wpdb->get_results("SELECT * FROM {$sessions_table} WHERE exit_page_path IN ({$query_paths}) AND created_at BETWEEN '{$start_date}' AND '{$end_date}' ");
        
        $exit_pages_index_results = [];
        
        if($is_index) {
            $exit_pages_index_results = $wpdb->get_results("SELECT * FROM {$sessions_table} WHERE exit_page_path IS NULL AND created_at BETWEEN '{$start_date}' AND '{$end_date}' ");
        }
        
        $exit_pages_results = array_merge($exit_pages_results, $exit_pages_index_results);
        
        $total_records = $wpdb->get_results("SELECT count(distinct exit_page_path) as exit_page_total,  SUM(exit_page_path) as result_visitors FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'");
        $entries['total_records'] = $total_records[0]->exit_page_total;
        $entries['total_visitors'] = $total_records[0]->result_visitors;
        
        
        //Entry Pages query
        $session_results = $wpdb->get_results("SELECT * FROM {$sessions_table} WHERE exit_page_path IN ({$query_paths}) AND created_at BETWEEN '{$start_date}' AND '{$end_date}'");

        
        if(!empty($session_results)) {
            foreach( $session_results as $session ) {
        
                if(!empty($session->exit_page_path)) {
                    $page_path = strtolower($session->exit_page_path);
                } else {
                    $page_path = "/";
                }
                
                if(!isset($entries['records'][$page_path])) {
                    $entries['records'][$page_path] = [
                        'path' => $page_path,
                        'total_visitors' => 0,
                        'total_unique_visitors' => 0,
                        'bounces' => 0,
                    ];
                }
        
                if($session->actions == 1) {
                    $entries['records'][$page_path]['bounces']++;
                }
        
                if($session->unique_session) {
                    $entries['records'][$page_path]['total_unique_visitors'] += 1;
                }
        
                $entries['records'][$page_path]['total_visitors'] += 1;
                
            
                if(!empty($session->exit_page_query)) {
                    if(!isset($entries['records'][$page_path]['query'][$session->exit_page_query])) {
                        $entries['records'][$page_path]['query'][$session->exit_page_query] = [
                            'query' => $session->exit_page_query,
                            'total_visitors' => 0,
                            'total_unique_visitors' => 0,
                            'bounces' => 0,
                        ];
                    }
                    if($session->unique_session) {
                        $entries['records'][$page_path]['query'][$session->exit_page_query]['total_unique_visitors'] += 1;
                    }
                    $entries['records'][$page_path]['query'][$session->exit_page_query]['total_visitors'] += 1;
                    if($session->actions == 1) {
                        $entries['records'][$page_path]['query'][$session->exit_page_query]['bounces']++;
                    }
                } else {
                    if(!isset($entries['records'][$page_path]['query'][$session->exit_page_query])) {
                        $entries['records'][$page_path]['query'][$session->exit_page_query] = [
                            'query' => '/',
                            'total_visitors' => 0,
                            'total_unique_visitors' => 0,
                            'bounces' => 0,
                        ];
                    }
                    if($session->unique_session) {
                        $entries['records'][$page_path]['query'][$session->exit_page_query]['total_unique_visitors'] += 1;
                    }
                    $entries['records'][$page_path]['query'][$session->exit_page_query]['total_visitors'] += 1;
                    if($session->actions == 1) {
                        $entries['records'][$page_path]['query'][$session->exit_page_query]['bounces']++;
                    }
                }
            }
        
            uasort($entries['records'], function($a, $b) {
                return $b['total_visitors'] <=> $a['total_visitors'];
            });
        
            foreach($entries['records'] as $path => $value) {
                if(isset($value['query'])) {
                    uasort($entries['records'][$path]['query'], function($a, $b) {
                        return $b['total_visitors'] <=> $a['total_visitors'];
                    });
                }
            }
        }
        
        return $entries;
        
    }
    /**
	 * Queries 
	 *
	 * @return array 
	 */
    public static function query_page_visits_titles( $start_date, $end_date, int $per_page=10, int $offset=0 ){

        global $wpdb;
        date_default_timezone_set('America/Los_Angeles');
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        $page_visits_table = TABLE_ANALYTICS_PAGE_VISITS;
        
        $query_offset = $offset * $per_page;
        $start_date = date("Y-m-d H:i:s", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));

        $entries = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'per_page' => $per_page,
            'offset' => $offset,
            'selector' => 'page-visits',
            'data_view' => 'titles',
            'description' => 'Overview of the visitors to your website.',
            'total_records' => 0,
            'total_visitors' => 0,
            'records' => []
        ];

        $page_visits_results = $wpdb->get_results("SELECT *, SUM(page_views) AS result_views, SUM(visitors) AS result_visitors, SUM(page_reads) AS result_page_reads, SUM(claps) AS result_page_claps FROM {$page_visits_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY page_title ORDER BY result_views DESC LIMIT {$per_page} OFFSET {$query_offset}");
        
        $query_urls = [];
        if(!empty($page_visits_results)) {
            foreach( $page_visits_results as $page_visit ) {
                $query_urls[] = $page_visit->page_url;

                $url = ucwords(strtolower($page_visit->page_url));
                if(!isset($entries['records'][$url])) {
                    $entries['records'][$url] = [
                        'title' => $page_visit->page_title,
                        'total_page_views' => $page_visit->result_views,
                        'total_unique_visitors' => $page_visit->result_visitors,
                        'total_page_reads' => (($page_visit->result_page_reads) ? $page_visit->result_page_reads : 0),
                        'bounces' => 0,
                        'total_claps'=> (($page_visit->result_page_claps) ? $page_visit->result_page_claps : 0)
                    ];
                }
            }
        }

        $query_urls = "'" . implode( "','", esc_sql($query_urls) ) . "'";
        $session_results = $wpdb->get_results("SELECT entry_page_url, COUNT(entry_page_url) AS result_bounce_rate FROM {$sessions_table} WHERE entry_page_url IN ({$query_urls}) AND actions = 1 AND created_at BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY entry_page_url ORDER BY result_bounce_rate");

        if(!empty($session_results)) {
            foreach( $session_results as $session ) {
                $url = ucwords(strtolower($session->entry_page_url));
                if( isset($entries['records'][$url]) ) {
                    $entries['records'][$url]['bounces'] = $session->result_bounce_rate;
                }
            }
        }

        $total_records = $wpdb->get_results("SELECT count(distinct page_title) as page_total, SUM(visitors) AS result_visitors FROM {$page_visits_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'");
        
        if($total_records) {
            $entries['total_records'] = $total_records[0]->page_total;
            $entries['total_visitors'] = $total_records[0]->result_visitors;
        }
        

        return $entries;

    }
    /**
	 * Queries 
	 *
	 * @return array 
	 */
    public static function query_page_visits_paths( $start_date, $end_date, int $per_page=10, int $offset=0 ){

        global $wpdb;
        date_default_timezone_set('America/Los_Angeles');
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        $page_visits_table = TABLE_ANALYTICS_PAGE_VISITS;
        
        $query_offset = $offset * $per_page;
        $is_index = false;
        $start_date = date("Y-m-d H:i:s", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));

        $entries = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'per_page' => $per_page,
            'offset' => $offset,
            'selector' => 'page-visits',
            'data_view' => 'paths',
            'description' => 'Overview of the visitors to your website.',
            'total_records' => 0,
            'total_visitors' => 0,
            'records' => []
        ];

        $page_visits_results = $wpdb->get_results("SELECT page_path, SUM(page_views) AS result_views, SUM(visitors) AS result_visitors, SUM(page_reads) AS result_page_reads,SUM(claps) AS result_page_claps FROM {$page_visits_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY page_path ORDER BY result_views DESC LIMIT {$per_page} OFFSET {$query_offset}");

        $query_paths = [];

        if(!empty($page_visits_results)) {
            foreach( $page_visits_results as $page_visit ) {

                $page_view_path = '';

                if( !empty( $page_visit->page_path ) ){
                    $page_view_path = $page_visit->page_path;
                    $query_paths[] = $page_visit->page_path;
                } else {
                    $page_view_path = '/';
                    $is_index = true;
                }

                if(!isset($entries['records'][$page_view_path])) {
                    $entries['records'][$page_view_path] = [
                        'path' => $page_view_path,
                        'total_page_views' => $page_visit->result_views,
                        'total_unique_visitors' => $page_visit->result_visitors,
                        'total_page_reads' => (($page_visit->result_page_reads) ? $page_visit->result_page_reads : 0),
                        'bounces' => 0,
                        'query' => [],
                        'claps' => (($page_visit->result_page_claps) ? $page_visit->result_page_claps : 0)
                    ];
                }
            }
        }

        $query_paths = "'" . implode( "','", esc_sql($query_paths) ) . "'";
        $page_view_query = $wpdb->get_results("SELECT * FROM {$page_visits_table} WHERE page_path IN ({$query_paths}) AND created_at BETWEEN '{$start_date}' AND '{$end_date}' ");

        $sessions = $wpdb->get_results("SELECT entry_page_path, entry_page_query FROM {$sessions_table} WHERE entry_page_path IN ({$query_paths}) AND actions = 1 AND created_at BETWEEN '{$start_date}' AND '{$end_date}' ");

        if($is_index) {
            $page_visits_index_results = $wpdb->get_results("SELECT page_path, SUM(page_views) AS result_views, SUM(visitors) AS result_visitors FROM {$page_visits_table} WHERE page_path IS NULL AND created_at BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY page_path ");
            $entries['records']['/']['total_page_views'] = $page_visits_index_results[0]->result_views;
            $entries['records']['/']['total_unique_visitors'] = $page_visits_index_results[0]->result_visitors;
            $page_visits_index_results = [];
            $page_view_index_query = $wpdb->get_results("SELECT * FROM {$page_visits_table} WHERE page_path IS NULL AND created_at BETWEEN '{$start_date}' AND '{$end_date}' ");
            $page_view_query = array_merge($page_view_query, $page_view_index_query);
        }

        foreach( $page_view_query as $page_query ) {

            if( !empty( $page_query->page_path ) ){
                $page_view_path = $page_query->page_path;
            } else {
                $page_view_path = '/';
            }

            if( !isset( $entries['records'][$page_view_path]['query'][$page_query->page_query] ) ){
                $entries['records'][$page_view_path]['query'][$page_query->page_query] = [
                    'query' => $page_query->page_query,
                    'total_page_views' => 0,
                    'total_unique_visitors' => 0,
                    'total_page_reads' => (($page_visit->result_page_reads) ? $page_visit->result_page_reads : 0),
                    'bounces' => 0,
                    'claps' => 0,
                ];
            }
            $entries['records'][$page_view_path]['query'][$page_query->page_query]['total_page_views'] += $page_query->page_views;
            $entries['records'][$page_view_path]['query'][$page_query->page_query]['total_unique_visitors'] += $page_query->visitors;
            $entries['records'][$page_view_path]['query'][$page_query->page_query]['claps'] += $page_query->claps;
        }

        foreach($entries['records'] as $path => $value) {
            if(isset($value['query'])) {
                uasort($entries['records'][$path]['query'], function($a, $b) {
                    return $b['total_page_views'] <=> $a['total_page_views'];
                });
            }
        }

        foreach($sessions as $session) {
            if(isset($entries['records'][$session->entry_page_path])) {
                $entries['records'][$session->entry_page_path]['bounces']++;
            }
            if(isset($entries['records'][$session->entry_page_path]['query'][$session->entry_page_query])) {
                $entries['records'][$session->entry_page_path]['query'][$session->entry_page_query]['bounces']++;
            }
        }

        $total_records = $wpdb->get_results("SELECT count(distinct page_path) as page_total FROM {$page_visits_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'");
        
        if ($total_records) {
            $entries['total_records'] = $total_records[0]->page_total;
        }

        $total_records_visitors = $wpdb->get_results("SELECT count(distinct page_url) as page_total, SUM(visitors) AS result_visitors FROM {$page_visits_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'");
        
        if ($total_records_visitors) {
            $entries['total_visitors'] = $total_records_visitors[0]->result_visitors;
        }

        return $entries;

    }

    /**
	 * Queries 
	 *
	 * @return array 
	 */
    public static function query_blog_visits_titles( $start_date, $end_date, int $per_page=10, int $offset=0, array $post_ids=[] ){

        global $wpdb;
        date_default_timezone_set('America/Los_Angeles');
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        $page_visits_table = TABLE_ANALYTICS_PAGE_VISITS;
        
        $query_offset = $offset * $per_page;
        $start_date = date("Y-m-d H:i:s", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));

        $entries = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'per_page' => $per_page,
            'offset' => $offset,
            'selector' => 'blog-visits',
            'data_view' => 'titles',
            'description' => 'Overview of the blog traffic on your website.',
            'total_records' => 0,
            'total_visitors' => 0,
            'records' => []
        ];

        if(!empty($post_ids)) {
            $posts = "'" . implode( "','", esc_sql($post_ids) ) . "'";
            $page_visits_results = $wpdb->get_results("SELECT *, SUM(page_views) AS result_views, post_id, SUM(visitors) AS result_visitors, SUM(page_reads) AS result_page_reads,SUM(claps) AS result_page_claps FROM {$page_visits_table} WHERE post_id IN ({$posts}) GROUP BY page_title ORDER BY result_views DESC LIMIT {$per_page} OFFSET {$query_offset}");
        } else {
            $page_visits_results = $wpdb->get_results("SELECT *, SUM(page_views) AS result_views, post_id, SUM(visitors) AS result_visitors, SUM(page_reads) AS result_page_reads, SUM(claps) AS result_page_claps FROM {$page_visits_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY page_title ORDER BY result_views DESC LIMIT {$per_page} OFFSET {$query_offset}");
        }
        
        $query_urls = [];
        if(!empty($page_visits_results)) {
            foreach( $page_visits_results as $page_visit ) {
                
                $url = ucwords(strtolower($page_visit->page_url));
                    $query_urls[] = $page_visit->page_url;
                    if(!isset($entries['records'][$url])) {
                        $entries['records'][$url] = [
                            'title' => $page_visit->page_title,
                            'post_id' => $page_visit->post_id,
                            'total_page_views' => $page_visit->result_views,
                            'total_unique_visitors' => $page_visit->result_visitors,
                            'total_page_reads' => (($page_visit->result_page_reads) ? $page_visit->result_page_reads : 0),
                            'bounces' => 0,
                            'claps' => (($page_visit->result_page_claps) ? $page_visit->result_page_claps : 0),
                        ];
                    }    

            }
        }
        $query_urls = "'" . implode( "','", esc_sql($query_urls) ) . "'";
        $session_results = $wpdb->get_results("SELECT entry_page_url, COUNT(entry_page_url) AS result_bounce_rate FROM {$sessions_table} WHERE entry_page_url IN ({$query_urls}) AND actions = 1 AND created_at BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY entry_page_url ORDER BY result_bounce_rate");

        if(!empty($session_results)) {
            foreach( $session_results as $session ) {
                $url = ucwords(strtolower($session->entry_page_url));
                if( isset($entries['records'][$url]) ) {
                    $entries['records'][$url]['bounces'] = $session->result_bounce_rate;
                }
            }
        }

        // $total_records = $wpdb->get_results("SELECT count(distinct page_title) as page_total, SUM(page_views) AS result_views FROM {$page_visits_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'");
        
        // if($total_records) {
        //     $entries['total_records'] = $total_records[0]->page_total;
        //     $entries['total_visitors'] = $total_records[0]->result_views;
        // }

        $total_records = $wpdb->get_results("SELECT count(distinct page_path) as page_total FROM {$page_visits_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'");
        
        if ($total_records) {
            $entries['total_records'] = $total_records[0]->page_total;
        }

        $total_records_visitors = $wpdb->get_results("SELECT SUM(visitors) AS result_visitors FROM {$page_visits_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'");
        
        if ($total_records_visitors) {
            $entries['total_visitors'] = $total_records_visitors[0]->result_visitors;
        }
        
        return $entries;

    }

    /**
	 * Queries 
	 *
	 * @return array 
	 */
    public static function query_blog_visits_paths( $start_date, $end_date, int $per_page=10, int $offset=0, array $post_ids=[] ){

        global $wpdb;
        date_default_timezone_set('America/Los_Angeles');
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        $page_visits_table = TABLE_ANALYTICS_PAGE_VISITS;
        
        $query_offset = $offset * $per_page;
        $is_index = false;
        $start_date = date("Y-m-d H:i:s", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));

        $entries = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'per_page' => $per_page,
            'offset' => $offset,
            'selector' => 'blog-visits',
            'data_view' => 'paths',
            'description' => 'Overview of the blog traffic on your website.',
            'total_records' => 0,
            'total_visitors' => 0,
            'records' => []
        ];

        if(!empty($post_ids)) {
            $posts = "'" . implode( "','", esc_sql($post_ids) ) . "'";
            $page_visits_results = $wpdb->get_results("SELECT page_path, SUM(page_views) AS result_views, post_id, SUM(visitors) AS result_visitors, SUM(page_reads) AS result_page_reads, SUM(claps) AS result_page_claps FROM {$page_visits_table} WHERE post_id IN ({$posts}) GROUP BY page_path ORDER BY result_views DESC LIMIT {$per_page} OFFSET {$query_offset}");
        } else {
            $page_visits_results = $wpdb->get_results("SELECT page_path, SUM(page_views) AS result_views, post_id, SUM(visitors) AS result_visitors, SUM(page_reads) AS result_page_reads, SUM(claps) AS result_page_claps FROM {$page_visits_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY page_path ORDER BY result_views DESC LIMIT {$per_page} OFFSET {$query_offset}");
        }

        $query_paths = [];

        if(!empty($page_visits_results)) {
            foreach( $page_visits_results as $page_visit ) {

                $page_view_path = '';

                if( !empty( $page_visit->page_path ) ){
                    $page_view_path = $page_visit->page_path;
                    $query_paths[] = $page_visit->page_path;
                } else {
                    $page_view_path = '/';
                    $is_index = true;
                }

                if(!isset($entries['records'][$page_view_path])) {
                    $entries['records'][$page_view_path] = [
                        'path' => $page_view_path,
                        'post_id' => $page_visit->post_id,
                        'total_page_views' => $page_visit->result_views,
                        'total_unique_visitors' => $page_visit->result_visitors,
                        'total_page_reads' => (($page_visit->result_page_reads) ? $page_visit->result_page_reads : 0),
                        'bounces' => 0,
                        'query' => [],
                        'claps' => $page_visit->result_page_claps,
                    ];
                }
            }
        }

        

        $query_paths = "'" . implode( "','", esc_sql($query_paths) ) . "'";
        $page_view_query = $wpdb->get_results("SELECT * FROM {$page_visits_table} WHERE page_path IN ({$query_paths}) AND created_at BETWEEN '{$start_date}' AND '{$end_date}' ");

        $sessions = $wpdb->get_results("SELECT entry_page_path, entry_page_query FROM {$sessions_table} WHERE entry_page_path IN ({$query_paths}) AND actions = 1 AND created_at BETWEEN '{$start_date}' AND '{$end_date}' ");

        if($is_index) {
            $page_visits_index_results = $wpdb->get_results("SELECT page_path, SUM(page_views) AS result_views, SUM(visitors) AS result_visitors FROM {$page_visits_table} WHERE page_path IS NULL AND created_at BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY page_path ");
            $entries['records']['/']['total_page_views'] = $page_visits_index_results[0]->result_views;
            $entries['records']['/']['total_unique_visitors'] = $page_visits_index_results[0]->result_visitors;
            $page_visits_index_results = [];
            $page_view_index_query = $wpdb->get_results("SELECT * FROM {$page_visits_table} WHERE page_path IS NULL AND created_at BETWEEN '{$start_date}' AND '{$end_date}' ");
            $page_view_query = array_merge($page_view_query, $page_view_index_query);
        }

        foreach( $page_view_query as $page_query ) {

            if( !empty( $page_query->page_path ) ){
                $page_view_path = $page_query->page_path;
            } else {
                $page_view_path = '/';
            }

            if( !isset( $entries['records'][$page_view_path]['query'][$page_query->page_query] ) ){
                $entries['records'][$page_view_path]['query'][$page_query->page_query] = [
                    'query' => $page_query->page_query,
                    'title' => $page_query->page_title,
                    'total_page_views' => 0,
                    'total_unique_visitors' => 0,
                    'total_page_reads' => (($page_visit->result_page_reads) ? $page_visit->result_page_reads : 0),
                    'bounces' => 0,
                    'claps' => 0
                ];
            }
            $entries['records'][$page_view_path]['query'][$page_query->page_query]['total_page_views'] += $page_query->page_views;
            $entries['records'][$page_view_path]['query'][$page_query->page_query]['total_unique_visitors'] += $page_query->visitors;
            $entries['records'][$page_view_path]['query'][$page_query->page_query]['claps'] += $page_query->claps;
        }

        foreach($entries['records'] as $path => $value) {
            if(isset($value['query'])) {
                uasort($entries['records'][$path]['query'], function($a, $b) {
                    return $b['total_page_views'] <=> $a['total_page_views'];
                });
            }
        }

        foreach($sessions as $session) {
            if(isset($entries['records'][$session->entry_page_path])) {
                $entries['records'][$session->entry_page_path]['bounces']++;
            }
            if(isset($entries['records'][$session->entry_page_path]['query'][$session->entry_page_query])) {
                $entries['records'][$session->entry_page_path]['query'][$session->entry_page_query]['bounces']++;
            }
        }

        $total_records = $wpdb->get_results("SELECT count(distinct page_path) as page_total FROM {$page_visits_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'");
        
        if ($total_records) {
            $entries['total_records'] = $total_records[0]->page_total;
        }

        $total_records_visitors = $wpdb->get_results("SELECT SUM(visitors) AS result_visitors FROM {$page_visits_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'");
        
        if ($total_records_visitors) {
            $entries['total_visitors'] = $total_records_visitors[0]->result_visitors;
        }

        return $entries;

    }

    //Referrers
    public static function query_referrers( $start_date, $end_date, int $per_page=10, int $offset=0 ){
        global $wpdb;
        date_default_timezone_set('America/Los_Angeles');
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        $referrers_table = TABLE_ANALYTICS_REFERRERS;
        
        $query_offset = $offset * $per_page;
        $is_index = false;
        $start_date = date("Y-m-d H:i:s", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));

        $entries = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'per_page' => $per_page,
            'offset' => $offset,
            'selector' => 'referrers',
            'data_view' => 'referrers',
            'description' => 'Incoming traffic as a result of clicking on a URL on some other site.',
            'total_records' => 0,
            'total_visitors' => 0,
            'records' => []
        ];

        //This string is used for the DB query to keep out unwanted URL's
        $blacklist = "('agentelite.lightning.force.com', 'm.facebook.com')";

        $referrer_results = $wpdb->get_results(" SELECT *, SUM(unique_visitors) AS result_unique_visitors, SUM(visitors) AS result_visitors FROM {$referrers_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' AND referrer_domain NOT IN {$blacklist} GROUP BY referrer_domain ORDER BY result_visitors DESC LIMIT {$per_page} OFFSET {$query_offset}");

        $referrer_ids = [];
        foreach( $referrer_results as $referrer){
                if( !isset($entries['records'][$referrer->referrer_domain]) ) {
                $social_network = $referrer->social_network;
                $search_engine = $referrer->search_engine;
                $referrer_path = $referrer->referrer_path;
                
                $entries['records'][$referrer->referrer_domain] = [
                    'referrer_domain' => $referrer->referrer_domain,
                    'social_network' => $social_network,
                    'search_engine' => $search_engine,
                    'referrer_path' => $referrer_path,
                    'visitors' => $referrer->result_visitors,
                    'unique_visitors' => $referrer->result_unique_visitors,
                    'actions' => 0,
                    'ID' => $referrer->ID
                ];
   
                if( !empty( $referrer->referrer_domain ) ){
                        $referrer_ids[] = $referrer->ID;
                }

            }
        }

        $referrer_ids = "'" . implode( "','", esc_sql($referrer_ids) ) . "'";
        //return $referrer_ids;

        $session_actions = $wpdb->get_results("SELECT {$sessions_table}.created_at, {$sessions_table}.referrer_id, {$sessions_table}.actions, {$referrers_table}.referrer_domain, {$referrers_table}.ID FROM {$sessions_table}, {$referrers_table} WHERE {$sessions_table}.referrer_id IN ({$referrer_ids}) AND {$sessions_table}.referrer_id = {$referrers_table}.ID AND {$sessions_table}.created_at BETWEEN '{$start_date}' AND '{$end_date}' ");

        if( !empty( $session_actions ) ){
            foreach( $session_actions as $session_action ){
                $entries['records'][$session_action->referrer_domain]['actions'] = $session_action->actions;
            }
        }

        
        $total_records = $wpdb->get_results("SELECT count(distinct referrer_domain) as result_domain_total, SUM(visitors) as result_total_visitors FROM {$referrers_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'");
        
        
        if($total_records) {
            $entries['total_records'] = $total_records[0]->result_domain_total;
            $entries['total_visitors'] = $total_records[0]->result_total_visitors;
        } else {
            $entries['total_records'] = 0;
            $entries['total_visitors'] = 0;
        }

        return $entries;
    }

    //Pretty Referrers
    public static function query_pretty_referrers( $start_date, $end_date, int $per_page=10, int $offset=0 ){
        global $wpdb;
        date_default_timezone_set('America/Los_Angeles');
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        $referrers_table = TABLE_ANALYTICS_REFERRERS;
        
        $query_offset = $offset * $per_page;
        $is_index = false;
        $start_date = date("Y-m-d H:i:s", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));

        $entries = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'per_page' => $per_page,
            'offset' => $offset,
            'selector' => 'referrers',
            'data_view' => 'referrers',
            'description' => 'Incoming traffic as a result of clicking on a URL on some other site.',
            'total_records' => 0,
            'total_visitors' => 0,
            'records' => []
        ];

        //This string is used for the DB query to keep out unwanted URL's
        $blacklist = "('agentelite.lightning.force.com', 'm.facebook.com')";
        $referrer_results = $wpdb->get_results(" SELECT *, SUM(unique_visitors) AS result_unique_visitors, SUM(visitors) AS result_visitors FROM {$referrers_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' AND referrer_domain NOT IN {$blacklist} GROUP BY referrer_domain ORDER BY result_visitors DESC LIMIT {$per_page} OFFSET {$query_offset}");
        //write_log($referrer_results);
        $referrer_ids = [];
        foreach( $referrer_results as $referrer){
            
            $social_network = strpos($referrer->referrer_domain, 'buyerlink') ? 'AE Network' : $referrer->social_network;
            $search_engine = $referrer->search_engine;
            $referrer_path = $referrer->referrer_path;
            $key = $referrer->referrer_domain;
            if(!empty($search_engine) && $search_engine !== null){
                $key = $search_engine;
            }if(!empty($social_network) && $social_network !== null){
                $key = $social_network;
            }

            $entries['records'][$key] = [
                'referrer_domain' => $referrer->referrer_domain,
                'social_network' => $social_network,
                'search_engine' => $search_engine,
                'referrer_path' => $referrer_path,
                'visitors' => $referrer->result_visitors,
                'unique_visitors' => $referrer->result_unique_visitors,
                'actions' => 0,
                'ID' => $referrer->ID
            ];

            if( !empty( $referrer->referrer_domain ) ){
                $referrer_ids[] = $referrer->ID;
            }
            
        }

        $referrer_ids = "'" . implode( "','", esc_sql($referrer_ids) ) . "'";

        $session_actions = $wpdb->get_results("SELECT {$sessions_table}.created_at, {$sessions_table}.referrer_id, {$sessions_table}.actions, {$referrers_table}.referrer_domain, {$referrers_table}.ID FROM {$sessions_table}, {$referrers_table} WHERE {$sessions_table}.referrer_id IN ({$referrer_ids}) AND {$sessions_table}.referrer_id = {$referrers_table}.ID AND {$sessions_table}.created_at BETWEEN '{$start_date}' AND '{$end_date}' ");

        if( !empty( $session_actions ) ){
            foreach( $session_actions as $session_action ){
                $entries['records'][$session_action->referrer_domain]['actions'] = $session_action->actions;
            }
        }

        $total_records = $wpdb->get_results("SELECT count(distinct referrer_domain) as result_domain_total, SUM(visitors) as result_total_visitors FROM {$referrers_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'");
        
        if($total_records) {
            $entries['total_records'] = $total_records[0]->result_domain_total;
            $entries['total_visitors'] = $total_records[0]->result_total_visitors;
        } else {
            $entries['total_records'] = 0;
            $entries['total_visitors'] = 0;
        }

        return $entries;
    }

    //Keywords Query
    public static function query_keywords( $start_date, $end_date, int $per_page=10, int $offset=0 ){
        global $wpdb;
        date_default_timezone_set('America/Los_Angeles');
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        
        $query_offset = $offset * $per_page;
        $is_index = false;
        $start_date = date("Y-m-d H:i:s", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));

        $entries = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'per_page' => $per_page,
            'offset' => $offset,
            'selector' => 'keywords',
            'data_view' => 'keywords',
            'description' => 'A keyword is one or more words typed as a search query in a search engine (like Google).',
            'total_records' => 0,
            'records' => []
        ];

        $keywords_results = $wpdb->get_results("SELECT referrer_keywords, referrer_id, SUM(pages_visited) AS results_visits FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY referrer_keywords ORDER BY results_visits DESC LIMIT {$per_page} OFFSET {$query_offset}");

        $keywords = '' ;
        if( !empty( $keywords_results ) ){
            foreach( $keywords_results as $keyword ){
                if( empty($keyword->referrer_keywords) ){
                    $keywords = 'Keyword Not Defined';
                } else {
                    $keywords = $keyword->referrer_keywords;
                }
                $entries['records'][$keywords] = [
                    'keywords' => $keywords,
                    'visitors' => $keyword->results_visits
                ];
            }
        }
        $total_records = $wpdb->get_results("SELECT count(distinct referrer_keywords) AS result_keywords_total FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'"); 
        if($total_records) {
            $entries['total_records'] = $total_records[0]->result_keywords_total;
        }

        return $entries;
    }

    //Networks Query
    public static function query_networks( $start_date, $end_date, int $per_page=10, int $offset=0 ){
        global $wpdb;
        date_default_timezone_set('America/Los_Angeles');
        $referrers_table = TABLE_ANALYTICS_REFERRERS;
        
        $query_offset = $offset * $per_page;
        $is_index = false;
        $start_date = date("Y-m-d H:i:s", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));

        $entries = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'per_page' => $per_page,
            'offset' => $offset,
            'selector' => 'engines',
            'data_view' => 'social_networks',
            'description' => 'Traffic coming from a social network.',
            'total_records' => 0,
            'records' => [
                'total_network_visitors' => 0,
                'social_networks' => []
            ]
        ];
        $network = '';
        $network_total = 0;

        $social_networks_results = $wpdb->get_results("SELECT created_at, referrer_domain, referrer_path, social_network, unique_visitors, SUM(unique_visitors) AS result_unique_visits FROM {$referrers_table} WHERE social_network IS NOT NULL AND unique_visitors >= 1 AND created_at BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY social_network ORDER BY result_unique_visits DESC");
        // return $social_networks_results;

        if( !empty($social_networks_results) ){
            $entries['total_records'] = 1;
            foreach($social_networks_results as $social_network){

                if( empty($social_network->social_network) ){
                    $network = 'no_network';
                } else {
                    $network = $social_network->social_network;
                    $network_total += $social_network->result_unique_visits;
                }
                $entries['records']['social_networks'][$network] = [
                    'social_network' => $network,
                    'unique_visitors' => $social_network->result_unique_visits
                ];
                //return branding colors array
                switch ($network) {
                    case 'Facebook':
                        $entries['records']['social_networks'][$network]['color'] = '#71A4B7';
                    break;
                    case 'Pinterest':
                        $entries['records']['social_networks'][$network]['color'] = '#E8E479';
                    break;
                    case 'LinkedIn':
                        $entries['records']['social_networks'][$network]['color'] = '#145B71';
                    break;
                    case 'Youtube':
                        $entries['records']['social_networks'][$network]['color'] = '#DDAE52';
                    break;
                    default:
                    $entries['records']['social_networks'][$network]['color'] = '#39697D';
                }

            }
            $entries['records']['total_network_visitors'] = $network_total;
        }
        
        return $entries;
    }

    //Search Engines Query
    public static function query_engines( $start_date, $end_date, int $per_page=10, int $offset=0 ){
        global $wpdb;
        date_default_timezone_set('America/Los_Angeles');
        $referrers_table = TABLE_ANALYTICS_REFERRERS;
        
        $query_offset = $offset * $per_page;
        $is_index = false;
        $start_date = date("Y-m-d H:i:s", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));

        $entries = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'per_page' => $per_page,
            'offset' => $offset,
            'selector' => 'engines',
            'data_view' => 'search_engines',
            'description' => 'Traffic coming from search engines.',
            'total_records' => 0,
            'records' => [
                'total_engine_visitors' => 0,
                'search_engines' => []
            ]
        ];
        $engine = '';
        $engine_total = 0;

        $search_engines_results = $wpdb->get_results("SELECT *, SUM(unique_visitors) AS result_unique_visits FROM {$referrers_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY search_engine ORDER BY result_unique_visits DESC");
        // return $search_engines_results;
        if( !empty($search_engines_results) ){
            $entries['total_records'] = 1;
            foreach($search_engines_results as $search_engine){
                if( empty($search_engine->search_engine) ){
                    $engine = 'No Engine';
                } else {
                    $engine = $search_engine->search_engine;
                    $engine_total += $search_engine->result_unique_visits;
                }
                $entries['records']['search_engines'][$engine] = [
                    'search_engine' => $engine,
                    'unique_visitors' => $search_engine->result_unique_visits
                ];
                switch ($engine) {
                case 'Google':
                        $entries['records']['search_engines'][$engine]['color'] = '#71A4B7';
                break;
                case 'Bing':
                        $entries['records']['search_engines'][$engine]['color'] = '#E8E479';
                break;
                case 'Yahoo':
                        $entries['records']['search_engines'][$engine]['color'] = '#145B71';
                break;
                case 'duckduckgo':
                        $entries['records']['search_engines'][$engine]['color'] = '#DDAE52';
                break;
                default:
                    $entries['records']['search_engines'][$engine]['color'] = '#39697D';
            }
            }
            $entries['records']['total_engine_visitors'] = $engine_total;
        }
        return $entries;
    }

    

    public static function query_visit_overview( $start_date, $end_date) {
        global $wpdb;
        date_default_timezone_set('America/Los_Angeles');
        $sessions_table = TABLE_ANALYTICS_SESSIONS;

        $start_date = date("Y-m-d H:i:s", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));

        $entries = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'selector' => 'visit-overview',
            'description' => 'An overview of unique visitors versus returning visitors.',
            'total_records' => 0,
            'records' => []
        ];
        $visit_total_results = $wpdb->get_results("SELECT guid, COUNT(guid) AS result_visits FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'");

        $visit_unique_results = $wpdb->get_results("SELECT guid, COUNT(guid) AS result_visits FROM {$sessions_table} WHERE unique_session = 1 AND created_at BETWEEN '{$start_date}' AND '{$end_date}'");

        if( $visit_total_results[0]->result_visits > 1 ){
            $entries['total_records'] = 1;
            $total_visits = $visit_total_results[0]->result_visits;
            $total_unique_visits = $visit_unique_results[0]->result_visits;
            $difference = $visit_total_results[0]->result_visits - $visit_unique_results[0]->result_visits;

            $entries['records']['total_visitors'] = $total_visits;
            $entries['records']['total_unique_visitors'] = $total_unique_visits;
            $entries['records']['difference'] = $difference;
        }
        return $entries;
    }

    public static function query_domain_metrics()
    {
        $domain_metrics = new \DomainMetrics(get_bloginfo( 'url' ));
        return [
            'selector' => 'domain-metrics',
            'description' => 'Overview of your website\'s domain name information.',
            'metrics' => $domain_metrics->getMetrics(),
        ];
    }

    public static function query_user_overview( $start_date, $end_date) {
        global $wpdb;
        $users_table = TABLE_ANALYTICS_USERS;

        $new_registered = $wpdb->get_var("SELECT COUNT(*) FROM $users_table WHERE `registered` BETWEEN '{$start_date}' AND '{$end_date}'");
        $user_deleted = $wpdb->get_var("SELECT COUNT(*) FROM $users_table WHERE `deleted` BETWEEN '{$start_date}' AND '{$end_date}'");
        $user_count = $wpdb->get_var("SELECT COUNT(*) FROM $users_table WHERE `registered`  < '{$end_date}'");
        $sources_results = $wpdb->get_results("SELECT source, COUNT(*) as total FROM $users_table WHERE `registered` BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY source");

        $sources = [];

        if(!empty($sources_results)) {
            foreach($sources_results as $source) {
                $sources[strtolower($source->source)] = [
                    'total' => $source->total,
                ];
            }

        }

        $entries = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'new_registered' => $new_registered,
            'user_deleted' => $user_deleted,
            'user_count' => $user_count,
            'sources' => $sources
        ];


        return $entries;
    }


    public static function query_reads_on_days_of_week($start_date, $end_date) {
        global $wpdb;
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        date_default_timezone_set('America/Los_Angeles');
        $start_date = date("Y-m-d", strtotime($start_date));
        $end_date = date("Y-m-d",strtotime($end_date));
        $total_reads = $wpdb->get_results(" SELECT COUNT(*) as total_visits, DAYNAME(created_at) as weekday, COUNT(page_reads) as page_reads FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY DAYOFWEEK(created_at)", ARRAY_A);
        
        return $total_reads;
    }

    public static function query_sessions_created_at_in_time_period($start_date, $end_date) {
        global $wpdb;
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        date_default_timezone_set('America/Los_Angeles');
        $start_date = date("Y-m-d", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));

        $page_visits = $wpdb->get_results("SELECT created_at FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'", ARRAY_A);

       
        return $page_visits;
        
    }

    public static function query_blog_visits_in_time_period($start_date, $end_date, $post_name) {
        global $wpdb;
        $page_visits_table = TABLE_ANALYTICS_PAGE_VISITS;
        date_default_timezone_set('America/Los_Angeles');
        $start_date = date("Y-m-d", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));

        $post_visits_results = $wpdb->get_results("SELECT created_at, page_title as title, page_views , page_reads  FROM {$page_visits_table} WHERE page_title = '{$post_name}' AND created_at BETWEEN '{$start_date}' AND '{$end_date}'", ARRAY_A);
        

        $entries = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'selector' => 'single-blog-post-visits',
            'records' => [],
        ];
     
        if(!empty($post_visits_results)){
            foreach($post_visits_results as $post_visit){
        
                $created_at = $post_visit['created_at'];
                
                $page_views = (!empty($post_visit['page_views'])) ? $post_visit['page_views'] : 0;
                $page_reads = (!empty($post_visit['page_reads'])) ? $post_visit['page_reads'] : 0;
                
                $entries['records'][$created_at] = [

                    'title' => $post_visit['title'],
                    'page_views' => $page_views,
                    'page_reads' =>$page_reads,
                    'created_at' =>$created_at
                ];

            }
    
        }

        return $entries;
        
    }

    public static function query_campaign_data($start_date, $end_date, int $per_page=10, int $offset=0 ) {
        global $wpdb;
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        date_default_timezone_set('America/Los_Angeles');
        $query_offset = $offset * $per_page;
        $start_date = date("Y-m-d", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));

        $campaign_results= $wpdb->get_results("SELECT utm_campaign, utm_term,  COUNT(utm_campaign) AS sessions FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'  GROUP BY utm_campaign ORDER BY sessions DESC LIMIT {$per_page} OFFSET {$query_offset}", ARRAY_A);
        

        $total_campaign_results= $wpdb->get_results("SELECT count(distinct utm_campaign) as campaigns FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' ");

        $total_campaign_sessions = 0;
        if(!empty($total_campaign_results)){
            $total_campaign_sessions = $total_campaign_results[0]->campaigns;
        }


        $campaign_data = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'per_page' => $per_page,
            'offset' => $offset,
            'selector' => 'campaign-data',
            'total_records' => $total_campaign_sessions,
            'records' => [],
            'data_view' => 'campaign-data',
            'description' => 'This is how many people visited your site through a specific campaign.'
        ];

       if(!empty($campaign_results)){
            $campaign_title ='';
            $campaign_term='';
            $campaign_sessions =0;


            foreach($campaign_results as $campaign_result){

                if(isset($campaign_result['utm_term'])){
                    $campaign_term = date("m-d-Y",strtotime($campaign_result['utm_term']));
                }

                if(isset($campaign_result['sessions'])){
                    $campaign_sessions = $campaign_result['sessions'];
                }

                if(isset($campaign_result['utm_campaign'])){
                    $campaign_title = ucwords(str_replace("_"," ",$campaign_result['utm_campaign']));
                }
                $campaign_data['records'][$campaign_title] =
                    [
                        'title' => $campaign_title,
                        'term' => $campaign_term,
                        'sessions' => $campaign_sessions
                    ];

            }
       }
       return $campaign_data;

    }//end of campaign data

    public static function query_campaign_mediums($start_date, $end_date) {
        global $wpdb;
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        date_default_timezone_set('America/Los_Angeles');
        $start_date = date("Y-m-d", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));
   
       $campaign_medium_results = $wpdb->get_results("SELECT utm_medium, COUNT(created_at) AS sessions FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}'  AND '{$end_date}'  AND utm_medium IS NOT NULL GROUP BY utm_medium ORDER BY sessions DESC", ARRAY_A);

       $total_campaign_medium_sessions = $wpdb->get_results("SELECT COUNT(created_at) AS sessions FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' AND utm_medium IS NOT NULL");

        

        if(!empty($total_campaign_medium_sessions)){
            $total_medium_sessions = $total_campaign_medium_sessions[0]->sessions;
        }
        

       $campaign_mediums_data= [
        'start_date' => $start_date,
        'end_date' => $end_date,
        'selector' => 'campaign-mediums-sources',
        'data_view' => 'campaign_mediums',
        'campaign_medium_records' => [],
        'total_sessions' => (int)$total_medium_sessions   
        ];

        
       if(!empty($campaign_medium_results)){

            $campaign_medium='';
            $campaign_medium_sessions=0;

            foreach($campaign_medium_results as $campaign_medium_result){

                if (isset($campaign_medium_result['utm_medium'])){
                    $campaign_medium = ucwords(str_replace("_"," ",$campaign_medium_result['utm_medium']));
                }

                if (isset($campaign_medium_result['sessions'])){
                    $campaign_medium_sessions= $campaign_medium_result['sessions'];
                }

                $campaign_mediums_data['campaign_medium_records'][$campaign_medium] = [
                    'medium' => $campaign_medium,
                    'sessions' => $campaign_medium_sessions
                ];
    
            }
        }
        
        return $campaign_mediums_data;
    }//end of campaign mediums

    public static function query_campaign_sources($start_date, $end_date) {
        global $wpdb;
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        date_default_timezone_set('America/Los_Angeles');
        $start_date = date("Y-m-d", strtotime($start_date));
        $end_date = date("Y-m-d",strtotime($end_date));

        $campaign_source_results = $wpdb->get_results("SELECT utm_source, COUNT(created_at) AS sessions FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}'  AND utm_source IS NOT NULL GROUP BY utm_source ORDER BY sessions DESC", ARRAY_A);

        $total_campaign_source_sessions = $wpdb->get_results("SELECT COUNT(created_at) AS sessions FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' AND utm_source IS NOT NULL");
        

        if(!empty($total_campaign_source_sessions)){
            $total_source_sessions = $total_campaign_source_sessions[0]->sessions;
        }
        
        $campaign_source_data= [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'selector' => 'campaign-mediums-sources',
            'data_view' => 'campaign_sources',
            'campaign_source_records' => [],
            'total_sessions' => $total_source_sessions
            ];
        
        if(!empty($campaign_source_results)){

            $campaign_source='';
            $campaign_source_sessions=0;

            foreach($campaign_source_results as $campaign_source_result){

                if (isset($campaign_source_result['utm_source'])){
                    $campaign_source = ucwords($campaign_source_result['utm_source']);
                }

                if (isset($campaign_source_result['sessions'])){
                    $campaign_source_sessions= $campaign_source_result['sessions'];
                }

                $campaign_source_data['campaign_source_records'][$campaign_source] = [
                    'source' => $campaign_source,
                    'sessions' => $campaign_source_sessions
                ];
    
            }

        }

      return $campaign_source_data;
        
    }//end of sources

    public static function query_session_locations($start_date, $end_date,int $per_page=10, int $offset=0 ){
        global $wpdb;
        $sessions_table = TABLE_ANALYTICS_SESSIONS;
        date_default_timezone_set('America/Los_Angeles');
        $start_date = date("Y-m-d", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));
        $query_offset = $offset * $per_page;

        $session_locations_results = $wpdb->get_results(" SELECT COUNT(created_at) as sessions, timezone, city, region_name, country_name, zip_code FROM {$sessions_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' AND timezone IS NOT NULL AND city IS NOT NULL AND region_name IS NOT NULL AND country_name IS NOT NULL AND zip_code GROUP BY timezone, city, region_name, country_name, zip_code ORDER BY sessions DESC LIMIT {$per_page} OFFSET {$query_offset}", ARRAY_A);

        $total_sessions_locations_results = $wpdb->get_results("SELECT count(distinct city) as sessions_total FROM {$sessions_table} WHERE city IS NOT NULL AND created_at BETWEEN '{$start_date}' AND '{$end_date}'");

        $session_locations_data= [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'selector' => 'sessions-locations',
            'data-view' => 'sessions-locations',
            'records' => [],
            'total_records' => $total_sessions_locations_results[0]->sessions_total,
            'offset' => $offset,
            'per_page' =>$per_page,
            'description' => 'This is how many people visited your site from a specific location.'
        ];

        if(!empty($session_locations_results)){

            $sessions= '';
            $session_city = '';
            $session_region_name = '';
            $session_country_name = '';
            $session_zip_code = '';
            $session_timezone = '';

            foreach($session_locations_results as $session_locations_result){

                if (isset($session_locations_result['sessions'])){
                    $sessions = $session_locations_result['sessions'];
                }

                if (isset($session_locations_result['city'])){
                    $session_city = $session_locations_result['city'];
                }

                if (isset($session_locations_result['region_name'])){
                    $session_region_name = $session_locations_result['region_name'];
                }
                if (isset($session_locations_result['country_name'])){
                    $session_country_name = $session_locations_result['country_name'];
                }
                if (isset($session_locations_result['zip_code'])){
                    $session_zip_code = $session_locations_result['zip_code'];
                }
                if (isset($session_locations_result['timezone'])){
                    $session_timezone = $session_locations_result['timezone'];
                }

                $session_locations_data['records'][$session_city.', '.$session_region_name.', '.$session_country_name] = [

                    'location' => $session_city.', '.$session_region_name.', '.$session_country_name,
                    'zip_code' => $session_zip_code,
                    'timezone' => $session_timezone,
                    'sessions' => $sessions,

                ];
            }
        }
        

        return $session_locations_data;
    }

    public static function query_user_locations($per_page, $offset){
        global $wpdb;
        $users_table = TABLE_ANALYTICS_USERS;
        date_default_timezone_set('America/Los_Angeles');   

        $query_offset = $offset * $per_page;

        $user_locations_results = $wpdb->get_results(" SELECT COUNT(wp_user_id) as users, timezone, city, region_name, country_name, zip_code FROM {$users_table} WHERE timezone IS NOT NULL AND city IS NOT NULL AND region_name IS NOT NULL AND country_name IS NOT NULL AND zip_code GROUP BY timezone, city, region_name, country_name, zip_code ORDER BY users DESC LIMIT {$per_page} OFFSET {$query_offset}", ARRAY_A);

        $total_users_locations_results = $wpdb->get_results("SELECT count(distinct city) as users_total FROM {$users_table} WHERE city IS NOT NULL");
        

        $user_locations_data= [
            'selector' => 'user-locations',
            'data-view' => 'user-locations',
            'records' => [],
            'total_records' => $total_users_locations_results[0]->users_total,
            'offset' => $offset,
            'per_page' =>$per_page,
            'description' => 'This is how many registered users are from a specific location.'
        ];

        if(!empty($user_locations_results)){

            $users= '';
            $user_city = '';
            $user_region_name = '';
            $user_country_name = '';
            $user_zip_code = '';
            $user_timezone = '';

            foreach($user_locations_results as $user_locations_result){

                if (isset($user_locations_result['users'])){
                    $users = $user_locations_result['users'];
                }

                if (isset($user_locations_result['city'])){
                    $user_city = $user_locations_result['city'];
                }

                if (isset($user_locations_result['region_name'])){
                    $user_region_name = $user_locations_result['region_name'];
                }
                if (isset($user_locations_result['country_name'])){
                    $user_country_name = $user_locations_result['country_name'];
                }
                if (isset($user_locations_result['zip_code'])){
                    $user_zip_code = $user_locations_result['zip_code'];
                }
                if (isset($user_locations_result['timezone'])){
                    $user_timezone = $user_locations_result['timezone'];
                }

                $user_locations_data['records'][$user_city.', '.$user_region_name.', '.$user_country_name] = [

                    'location' => $user_city.', '.$user_region_name.', '.$user_country_name,
                    'zip_code' => $user_zip_code,
                    'timezone' => $user_timezone,
                    'users' => $users,

                ];
            }
        }
        


        return $user_locations_data;
    }

    public static function query_blog_category_visits($start_date, $end_date, $per_page, $offset){
        global $wpdb;
        $visits_table = TABLE_ANALYTICS_PAGE_VISITS;
        date_default_timezone_set('America/Los_Angeles');   
        $start_date = date("Y-m-d", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));
        $query_offset = $offset * $per_page;

        $post_results = $wpdb->get_results("SELECT post_id, page_title, SUM(page_views) AS page_views, SUM(page_reads) AS page_reads, SUM(claps) AS claps, SUM(visitors) AS unique_visitors FROM {$visits_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY post_id DESC LIMIT {$per_page} OFFSET {$query_offset}", ARRAY_A);

        $post_results_data= [
            'selector' => 'blog-category-visits',
            'data-view' => 'blog-category-visits',
            'records' => [],
            'total_page_views' => 0,
            'total_page_reads' => 0,
            'total_records' => 0,
            'total_visitors' => 0,
            'offset' => $offset,
            'per_page' => $per_page,
            'description' => 'This is the results for most viewed Categories.'
        ];

        if(!empty($post_results)){

            foreach($post_results as $post_result){

                $post_id = isset($post_result['post_id']) ? $post_result['post_id'] : 0;
                $page_views = isset($post_result['page_views']) ? $post_result['page_views'] : 0;
                $page_reads = isset($post_result['page_reads']) ? $post_result['page_reads'] : 0;
                $unique_visitors = isset($post_result['unique_visitors']) ? $post_result['unique_visitors'] : 0;
                $claps = isset($post_result['claps']) ? $post_result['claps'] : 0;
                $page_title = isset($post_result['page_title']) ? $post_result['page_title'] : '';

                if((!empty($post_id) && $post_id !== 0)){
                    if(empty($page_title)){
                        $page_title = empty(get_the_title($post_id)) ?  'Unknown ID: ' . $post_id : $page_title;
                    }
                    $title = empty($page_title) ? 'Post ID: '. $post_id : $page_title;
                    $categories = get_the_category($post_id);
                    $post_type = get_post_type($post_id);
                    
                    if((!empty($post_type) && ($post_type !== 'post' && $post_type !== 'user_account' && $post_type !== 'page')) && empty($categories)){
                        $categories = strpos($post_type , '_') ? ucwords(str_replace('_', ' ', $post_type)) : ucwords($post_type);
                    }if(empty($post_type) && empty($categories)){
                        $categories = 'Unknown Category: ' . $title;
                    }

                    if(is_array($categories)){
                        foreach ($categories as $category) {
                            $slug = isset($category->slug) ? $category->slug: $post_id;
                            $post_results_data['records'][$category->name]['posts'][$page_title] = [
                                'post_id' => $post_id,
                                'page_title' => $page_title,
                                'page_views' => $page_views,
                                'page_reads' => $page_reads,
                                'unique_visitors' => $unique_visitors,
                                'claps' => $claps,
                            ];

                            if(isset($post_results_data['records'][$category->name]['totals'])){
                                $post_results_data['records'][$category->name]['totals']['total_page_views'] += $page_views;
                                $post_results_data['records'][$category->name]['totals']['total_page_reads'] += $page_reads;
                                $post_results_data['records'][$category->name]['totals']['total_unique_visitors'] += $unique_visitors;
                                $post_results_data['records'][$category->name]['totals']['total_claps'] += $claps;
                            }else{
                                $post_results_data['records'][$category->name]['totals'] = [
                                    'total_page_views' => $page_views,
                                    'total_page_reads' => $page_reads,
                                    'total_unique_visitors' => $unique_visitors,
                                    'total_claps' => $claps,
                                    'slug' => $slug
                                ];
                            }
                        }
                    }else{
                        $slug = 'unknown_' . $post_id;
                        $title = empty($page_title) ? 'Post ID: '. $post_id : $page_title;
                        $post_results_data['records'][$categories] = [
                            'posts' => [
                                $title => [
                                    'post_id' => $post_id,
                                    'page_title' => $page_title,
                                    'page_views' => $page_views,
                                    'page_reads' => $page_reads,
                                    'unique_visitors' => $unique_visitors,
                                    'claps' => $claps,
                                ],
                            ],
                            'totals'=>[
                                'total_page_views' => $page_views,
                                'total_page_reads' => $page_reads,
                                'total_unique_visitors' => $unique_visitors,
                                'total_claps' => $claps,
                                'slug' => $slug
                            ]
                        ];
                    }
                }
            }
            
        }

        foreach ($post_results_data['records'] as $result) {
            $post_results_data['total_records'] += count($result['posts']);
            $post_results_data['total_page_views'] += $result['totals']['total_page_views']; 
            $post_results_data['total_visitors'] += $result['totals']['total_unique_visitors']; 
            $post_results_data['total_page_reads'] += $result['totals']['total_page_reads']; 
        }

        return $post_results_data;
    }

    public static function query_track_clicks( $start_date, $end_date, $per_page, $offset ) {
        global $wpdb;
        $track_clicks_table = TABLE_ANALYTICS_TRACK_LINKS;
        date_default_timezone_set('America/Los_Angeles');   
        $start_date = date("Y-m-d", strtotime($start_date));
        $end_date = date("Y-m-d H:i:s", strtotime("{$end_date} 11:59:59 pm"));
        $query_offset = $offset * $per_page;

        $clicks_results = $wpdb->get_results("SELECT page_title, page_url, click_type, link_content, COUNT(ID) AS clicks FROM {$track_clicks_table} WHERE created_at BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY page_title, page_url, click_type, link_content ORDER BY clicks DESC LIMIT {$per_page} OFFSET {$query_offset}", ARRAY_A);
        $clicks_results_data= [
            'selector' => 'track-clicks',
            'data-view' => 'track-clicks',
            'records' => [],
            'total_records' => 0,
            'offset' => $offset,
            'per_page' => $per_page,
            'description' => 'This is the results for clicked phone and email links.'
        ];

        if ( ! empty($clicks_results ) ) {
            $record_count = 0;
            foreach ( $clicks_results as $clicks_result ) {
                $page_title   = isset($clicks_result['page_title']) ? $clicks_result['page_title'] : '';
                $page_url     = isset($clicks_result['page_url']) ? $clicks_result['page_url'] : '';
                $click_type   = isset($clicks_result['click_type']) ? $clicks_result['click_type'] : '';
                $link_content = isset($clicks_result['link_content']) ? $clicks_result['link_content'] : '';
                $clicks        = isset($clicks_result['clicks']) ? $clicks_result['clicks'] : 0;

                if($page_title == '' )
                    $page_title = 'Home';

                if($page_url == '')
                    $page_url = home_url();

                $clicks_results_data['records'][$page_title . ' - ' . $click_type ] = [
                    'page_title' => $page_title,
                    'page_url' => $page_url,
                    'click_type' => $click_type,
                    'link_content' => $link_content,
                    'clicks' => $clicks
                ];

                $record_count += 1;
            }

            $clicks_results_data['total_records'] = $record_count;
        }
        return $clicks_results_data;
        
    }
}
