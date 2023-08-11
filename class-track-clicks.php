<?php
/**
 * Records clicks on phone and email links
 */

namespace AwesomeAnalytics;

class TrackClicks {
    public static function record_click_event($page_url, $page_title, $click_type, $link_content) {
        date_default_timezone_set('America/Los_Angeles');
        global $wpdb;
        $table = TABLE_ANALYTICS_TRACK_LINKS;
        $created_at = date("Y-m-d H:i:s");
        $result = $wpdb->insert(
            $table,
            array(
                'created_at' => $created_at,
                'page_title' => Helpers::clean_string($page_title),
                'page_url' => $page_url,
                'click_type' => $click_type,
                'link_content' => $link_content,
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );
        if($result == 1) {
            return $wpdb->insert_id;
        } else {
            return;
        }
    }
}

$TrackClicks = new TrackClicks;