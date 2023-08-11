<?php
/**
 * Add custom meta tags to posts, pages, etc.
 */
namespace AwesomeAnalytics;

class AwesomeMetaTags {
    function __construct() {
        add_action('wp_head', array( $this, 'aa_output_meta_tags' ) );
    }

    function aa_output_meta_tags() {

        if ( is_home() ) {

            $url = home_url();
            $title = get_bloginfo( 'name' );
            //echo '<meta property="aa:post_id" content="' . $post_id . '"/>';
            echo '<meta property="aa:title" content="' . $title . '"/>';
            echo '<meta property="aa:url" content="' . $url . '"/>';
        } else {

            global $post;
            $post_id = get_the_id();
            $title   = get_the_title( $post_id );
            $url     = get_permalink( $post_id );
            
            echo '<meta property="aa:post_id" content="' . $post_id . '"/>';
            echo '<meta property="aa:title" content="' . $title . '"/>';
            echo '<meta property="aa:url" content="' . $url . '"/>';
        }
    }
}

$AwesomeMetaTags = new AwesomeMetaTags;