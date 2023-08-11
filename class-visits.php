<?php
/**
 * Perform actions for website visits.
 */
namespace AwesomeAnalytics;

class Visit {

    public static function create_or_update( string $page_title, string $page_url, string $unique='yes', $post_id = 0,int $claps = 0 ) {
        date_default_timezone_set('America/Los_Angeles');
        global $wpdb;
        $table = TABLE_ANALYTICS_PAGE_VISITS;
        $created_at = date("Y-m-d");
        
        $page_title = ($post_id !== 0 || !empty($post_id)) ? get_the_title($post_id) : $page_title;

        $url_parts = Helpers::parse_url_parts($page_url);
        $page_path = ($url_parts['path']) ? $url_parts['path'] : null;
        $page_query = ($url_parts['query']) ? $url_parts['query'] : null;

        if(is_string($unique) === false) {
            $unique = 'yes';
        }
        if($unique === 'yes') {
            $unique = true;
        } else {
            $unique = null;
        }
        
        $page_visit = Visit::get( $page_path, $page_query );

        if( $page_visit ) {
            $page_visit->page_views = $page_visit->page_views + 1;

            $page_visit->claps = $page_visit->claps + $claps;

            if($unique == true) {
                $page_visit->visitors = $page_visit->visitors + 1;
            }
        
            $result = $wpdb->update(
                $table,
                array(
                    'visitors' => $page_visit->visitors,
                    'page_views' => $page_visit->page_views,
                    'claps' => $page_visit->claps
                ),
                array( 'ID' => $page_visit->ID ),
                array(
                    '%d',
                    '%d',
                    '%d',
                )
            );	
            if($result !== false) {
                return $page_visit->ID;
            } else {
                return;
            }

        } else {
            $result = $wpdb->insert(
                $table,
                array(
                    'created_at' => $created_at,
                    'post_id' => $post_id,
                    'page_path' => $page_path,
                    'page_query' => $page_query,
                    'page_title' => Helpers::clean_string($page_title),
                    'page_url' => $page_path . '/' . $page_query,
                    'visitors' => 1,
                    'page_views' => 1,
                    'claps' => 0
                ),
                array(
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%d'
                )
            );
            if($result == 1) {
                return $wpdb->insert_id;
            } else {
                return;
            }
        }
    }


    public static function update_page_read( int $post_id ) {
        date_default_timezone_set('America/Los_Angeles');
        global $wpdb;
        $table = TABLE_ANALYTICS_PAGE_VISITS;
        $page_visit = Visit::get_read_visit($post_id);

        if( $page_visit ) {
            $page_visit->page_reads = $page_visit->page_reads + 1;
        
            $result = $wpdb->update(
                $table,
                array(
                    'page_reads' => $page_visit->page_reads
                ),
                array( 'ID' => $page_visit->ID ),
                array(
                    '%d',
                )
            );	
            if($result !== false) {
                return $page_visit->ID;
            } else {
                return;
            }

        }
    }



    public static function get( string $page_path=null, string $page_query=null ) {
        date_default_timezone_set('America/Los_Angeles');
        $created_at = date("Y-m-d");
        global $wpdb;
        $table = TABLE_ANALYTICS_PAGE_VISITS;
        $page_path = esc_sql(Helpers::clean_string($page_path));
        $page_query = esc_sql(Helpers::clean_string($page_query));
        $sql = '';
        if($page_path == null) {
            $sql .= " AND page_path IS NULL";
        } else {
            $sql .= " AND page_path LIKE '%{$page_path}%'";
        }
        if($page_query == null) {
            $sql .= " AND page_query IS NULL";
        } else {
            $sql .= " AND page_query LIKE '%{$page_query}%'";
        }
        $page_view = $wpdb->get_row( "SELECT * FROM {$table} WHERE created_at ='{$created_at}' {$sql} " );
        if( null !== $page_view ) {
            return $page_view;
        } else {
            return false;
        }
    }

    public static function get_read_visit( int $post_id ) {
        date_default_timezone_set('America/Los_Angeles');
        $created_at = date("Y-m-d");
        global $wpdb;
        $table = TABLE_ANALYTICS_PAGE_VISITS;
        $post_id = esc_sql(Helpers::clean_string($post_id));
        $sql = " AND post_id = '{$post_id}'";
        $page_view = $wpdb->get_row( "SELECT * FROM {$table} WHERE created_at ='{$created_at}' {$sql} " );
        if( null !== $page_view ) {
            return $page_view;
        } else {
            return false;
        }
    }

    public static function get_post_claps( int $post_id ) {
        date_default_timezone_set('America/Los_Angeles');
        $created_at = date("Y-m-d");
        global $wpdb;
        $table = TABLE_ANALYTICS_PAGE_VISITS;

        $post_id = esc_sql(Helpers::clean_string($post_id));

        $totalClaps = $wpdb->get_row( "SELECT SUM(claps) as claps FROM {$table} WHERE post_id = {$post_id}" );
        
        $claps = (!empty($totalClaps->claps)) ? $totalClaps->claps : 0;

        if( $claps > 0 ) {
            return $claps;
        } else {
            return 0;
        }
    }
}