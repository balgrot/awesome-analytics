<?php
/**
 *  Output tracking post content
 */
namespace AwesomeAnalytics;

class TrackingHelpers {
    /**
	 * Start performing actions.
	 *
     * @return void
	 */
    function __construct() {
        
        add_filter('the_content', array($this, 'my_added_page_content'));
        
    }

    /**
     * Output tracking post content
	 *
	 */
    function my_added_page_content($content){
        global $post;
        if(!empty($post->ID)) {
            if( !is_feed() ) {
                return $content . '<p style="visibility:hidden;height:0;margin:0px;padding:0px" post-id="'. $post->ID.'" read-article></p>';
            }
        }
        return $content;
    }
}



$TrackingHelpers = new TrackingHelpers;
