<?php
/**
 * Track visitors using cookies.
 */
namespace AwesomeAnalytics;

class TrackVisitorsJS {
    /**
	 * Start performing actions.
	 *
     * @return void
	 */
    function __construct() {

        if ( true === AWESOME_ANALYTICS_REST_API_FEATURE ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'user_shadow' ) );
        } else {
            add_action('wp_footer', array($this, 'scroll_actions'), 20);
            add_action('wp_footer', array( $this, 'record_actions' ), 20);
        }
        
    }

    function user_shadow() {
        $do_not_track_logged_in_users = apply_filters('disable_logged_in_users_tracking', false);
        if ($do_not_track_logged_in_users == true && is_user_logged_in()) {
            return;
        }

        $disable_tracking = apply_filters('disable_awesome_analytics_tracking', false);
        if ($disable_tracking == true || defined('DOING_AJAX') || defined('DOING_CRON') || is_admin() || Helpers::check_url_spam() == true || isset($_GET['p'])  || is_customize_preview()) {
            return;
        }
        $session_data = Helpers::get_session_data();
        $post_id = $session_data['post_id'];
        $referrer_id = '';
        $referrer_keywords = '';
        if(!empty($session_data['referrer'])){
            if(isset($session_data['referrer']['id'])){
                $referrer_id = $session_data['referrer']['id'];
            }if(isset($session_data['referrer']['keywords'])){
                $referrer_keywords = $session_data['referrer']['keywords'];
            }
        }
        $exit_survey_toggle_check = apply_filters('exit_survey_toggle', true);
        $input_options = ["I'll be right back!","I don't have time right now","I didn't find what I was looking for","The content isn't relevant"];
        $exit_survey_options = apply_filters('exit_survey_options', $input_options);

        // Registier, localize and enqueue user-shadow script.
        // Added axios for adding security to the api
        wp_enqueue_script( 'axios', 'https://unpkg.com/axios/dist/axios.min.js' );
        wp_register_script( 'user-shadow', plugins_url('/js/user-shadow.js', __FILE__), array( 'jquery','wp-api-request'), 1, true );
        $script_data_array = array(
            'domain'                   => $session_data['domain'],
            'clap_cookie_name'         => $session_data['clap_cookie_name'],
            'has_utm'                  => (!empty($_GET['utm_campaign']) || ($do_not_track_logged_in_users == false && is_user_logged_in())) ? 1: 0,
            'has referrer'             => (Referrer::get_server_referrer() ? 1: 0),
            'referrer_id'              => $referrer_id,
            'referrer_keywords'        => $referrer_keywords,
            'search'                   => $session_data['search'],
            'ip_address'               => $session_data['ip_address'],
            'post_id'                  => $session_data['post_id'],
            'exit_survey_cookie_name'  => $session_data['exit_survey_cookie_name'],
            'exit_survey_value'        => $session_data['hash'],
            'session_cookie_name'      => $session_data['session_cookie_name'],
            'long_lived_cookie_name'   => $session_data['long_lived_cookie_name'],
            'long_lived_cookie_value'  => $session_data['hash'],
            'exit_survey_toggle_check' => $exit_survey_toggle_check,
            'inputOptionsArray'        => json_encode($exit_survey_options),
            'security'                 => wp_create_nonce( 'user_shadow_security' ),
            );
        wp_localize_script( 'user-shadow', 'user_shadow', $script_data_array );
        wp_enqueue_script( 'user-shadow' );
    }

    /**
     * Record Scrolls
	 *
	 */
    function scroll_actions() {

        $do_not_track_logged_in_users = apply_filters('disable_logged_in_users_tracking', false);
        if ($do_not_track_logged_in_users == true && is_user_logged_in()) {
            return;
        }

        $disable_tracking = apply_filters('disable_awesome_analytics_tracking', false);
        if ($disable_tracking == true || defined('DOING_AJAX') || defined('DOING_CRON') || is_admin() || Helpers::check_url_spam() == true || isset($_GET['p'])  || is_customize_preview()) {
            return;
        }
        
        // // basic crawler detection and block script (no legit browser should match this)
        if (!empty($_SERVER['HTTP_USER_AGENT']) && Helpers::check_user_agent_spam() == true) {
            return;
        }
        
        $session_data = Helpers::get_session_data();
        $post_id = $session_data['post_id'];
        $referrer_id = '';
        $referrer_keywords = '';
        if(!empty($session_data['referrer'])){
            if(isset($session_data['referrer']['id'])){
                $referrer_id = $session_data['referrer']['id'];
            }if(isset($session_data['referrer']['keywords'])){
                $referrer_keywords = $session_data['referrer']['keywords'];
            }
        }
        ?>
    
        <script> 
        jQuery(window).on("load", function() {
            
            window.addEventListener('scroll', scroll_tracking);
            window.addEventListener('touchmove', scroll_tracking);
            window.addEventListener('keydown', scroll_tracking);
            window.addEventListener('keyup', scroll_tracking);
            window.addEventListener('keypress', scroll_tracking);
            window.addEventListener('touchstart', scroll_tracking);
            window.addEventListener('mousedown', scroll_tracking);
            window.addEventListener('mouseup', scroll_tracking);

            var initial_scroll = document.innerHTML = window.pageYOffset;
            var domain = '<?php echo $session_data['domain']; ?>';
            //var postID = jQuery('[read-article]').attr('post-id');
            var postID = jQuery("meta[property='og:post_id']").attr("content");
            //console.log(postID);
            postID = (postID == '' || postID == undefined || postID == null) ? 0 : postID;

            // EXISTS Cookie function
            function cookieExists(cookie_name) {
                var cookie_value = document.cookie
                    .split('; ')
                    .find(row => row.startsWith(cookie_name))
                if('undefined' != typeof cookie_value || cookie_value === "") {
                    var exists_cookie_value = cookie_value.split('=')[1];
                    return exists_cookie_value;
                }else{
                    return "";
                }
            }

            // SET Cookie function
            function setCookie(cookie) {
                var cookie_name = cookie["name"];
                var cookie_value = cookie["value"];
                var expires = cookie["expires"];
                var path =  cookie["path"];
                var domain = cookie["domain"];
                document.cookie = cookie_name + "=" + cookie_value + "; domain=" + domain + "; path=" + path + "; expires=" + expires + ";"  ;
            }

            // Clap Cookie
            var claps = 0;
            var clapped = false;
            var clap_cookie_name = "<?php echo $session_data['clap_cookie_name'];?>";
            var clap_cookie_value = cookieExists(clap_cookie_name);
            current_post = JSON.parse('<?= json_encode($post_id); ?>')
            if (clap_cookie_value.length !== 0){
                clap_cookie_value_array = clap_cookie_value.split(',');
                //loop through cookie value to see if current post matches 
                clap_cookie_value_array.forEach(function(element, index){
                    //if cookie for current cookie exists - fill in hand icon and prevent ajax request
                    if(element == current_post){
                        jQuery('.claps-button path').removeClass('claps-button');
                        jQuery('.claps-button path').addClass('claps-button-active');
                        clapped = true;
                    }
                });
            }

            // Exit Survey Cookie
            var exit_survey_cookie_name = "<?php echo $session_data['exit_survey_cookie_name'];?>";
            var exit_survey_cookie_check_value = cookieExists(exit_survey_cookie_name);

            // Session Cookie
            var session_cookie_name = "<?php echo $session_data['session_cookie_name'];?>";
            var session_cookie_exists = false;
            var session_cookie_check_value = cookieExists(session_cookie_name);

            // Long Lived Cookie
            var long_lived_cookie_name = "<?php echo $session_data['long_lived_cookie_name'];?>";
            var long_lived_cookie_value= "<?php echo $session_data['hash'];?>";
            var long_lived_cookie_exists = false;
            var long_lived_cookie_check_value = cookieExists(long_lived_cookie_name);
            // Long Lived Cookie Expiration
            var current_date = new Date();
            var month_away = 90 * 24 * 60 * 60 * 1000;
            var long_lived_cookie_expire = new Date(current_date.getTime() + month_away);

            // check is visit is unique
            var unique = true;
            if(long_lived_cookie_check_value.length > 0) {
                unique = false;
            }
            
            //check for referrers, campaigns and logged in users
            var has_utm = <?php echo (!empty($_GET['utm_campaign']) || ($do_not_track_logged_in_users == false && is_user_logged_in())) ? 1: 0; ?>;
            var has_referrer = <?php  echo (Referrer::get_server_referrer() ? 1: 0);?>;
            if(has_utm == 1 || has_referrer == 1) {
                scroll_tracking();
            }

            function scroll_tracking() {
                var scroll = document.innerHTML = window.pageYOffset;
                if((initial_scroll != scroll && scroll >= 200) || has_utm == 1 || has_referrer == 1){
                    window.removeEventListener('scroll', scroll_tracking);
                    window.removeEventListener('touchmove', scroll_tracking);
                    window.removeEventListener('keydown', scroll_tracking);
                    window.removeEventListener('keyup', scroll_tracking);
                    window.removeEventListener('keypress', scroll_tracking);
                    window.removeEventListener('touchstart', scroll_tracking);
                    window.removeEventListener('mousedown', scroll_tracking);
                    window.removeEventListener('mouseup', scroll_tracking);

                    long_lived_cookie = {
                        name: long_lived_cookie_name,
                        value: long_lived_cookie_value,
                        expires: long_lived_cookie_expire,
                        path: "/",
                        domain: domain,
                    }
                    long_lived_cookie_check_value.length === 0 ? setCookie(long_lived_cookie) : "'";

                    session_cookie = {
                        name: session_cookie_name,
                        value: "",
                        expires: "",
                        path: "/",
                        domain: domain,
                    }
                    // Session Cookie Exists, but value is empty, set Session to value
                    if(session_cookie_check_value === undefined || session_cookie_check_value.length === 0){
                            // AJAX to update existing session
                            jQuery.ajax({
                                url: '<?php echo admin_url( 'admin-ajax.php'); ?>',
                                type: "POST",
                                data: {
                                    action: 'ajax_record_session',
                                    security: '<?php echo wp_create_nonce('ajax_record_session_nonce'); ?>',
                                    //page_url: window.location.href,  
                                    page_url: jQuery("meta[property='og:url']").attr("content"),
                                    post_id: postID,
                                    referrer_id: '<?php echo $referrer_id; ?>',
                                    referrer_keywords: '<?php echo $referrer_keywords; ?>',
                                    search: '<?php echo $session_data['search']; ?>',
                                    ip_address: '<?php echo $session_data['ip_address']; ?>',
                                    unique: unique,
                                }, 
                                success: function (data) {
                                    var current_date = new Date();
                                    var minutes_away =  20 * 30 * 60000;
                                    var session_cookie_expire = new Date(current_date.getTime() + minutes_away);
                                    session_cookie = {
                                        name: session_cookie_name,
                                        value: data,
                                        expires: session_cookie_expire,
                                        path: "/",
                                        domain: domain,
                                    }
                                    setCookie(session_cookie);
                                },
                                error: function (xhr, ajaxOptions, thrownError) {
                                    //console.log(thrownError);
                                },

                            });
                    } else {
                        jQuery.ajax({
                            url: '<?php echo admin_url( 'admin-ajax.php'); ?>',
                            type: "POST",
                            data: {
                                action: 'ajax_update_session',
                                security: '<?php echo wp_create_nonce('ajax_update_session_nonce'); ?>',
                                post_id: postID,
                                //page_url: window.location.href,
                                page_url: jQuery("meta[property='og:url']").attr("content"),
                                unique: unique,
                            }, 
                            success: function (data) {
                                var current_date = new Date();
                                var minutes_away =  20 * 30 * 60000;
                                var session_cookie_expire = new Date(current_date.getTime() + minutes_away);
                                session_cookie = {
                                    name: session_cookie_name,
                                    value: session_cookie_check_value,
                                    expires: session_cookie_expire,
                                    path: "/",
                                    domain: domain,
                                }
                                setCookie(session_cookie);
                            },
                            error: function (xhr, ajaxOptions, thrownError) {
                                //console.log(thrownError);
                            },

                        });
                    }

                    jQuery.ajax({
                        url: '<?php echo admin_url( 'admin-ajax.php'); ?>',
                        type: "POST",
                        data: {
                            action: 'ajax_record_visit',
                            security: '<?php echo wp_create_nonce('ajax_record_visit_nonce'); ?>',
                            post_id: postID,
                            page_url: window.location.href,
                            unique: unique,
                        }, 
                        success: function (data) {
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            //console.log(thrownError);
                        },
                    });
                }
                
            }// end of scroll tracking

            window.addEventListener('scroll', record_article_read);
            window.addEventListener('touchmove', record_article_read);
            window.addEventListener('keydown', record_article_read);
            window.addEventListener('keyup', record_article_read);
            window.addEventListener('keypress', record_article_read);
            window.addEventListener('touchstart', record_article_read);
            window.addEventListener('mousedown', record_article_read);
            window.addEventListener('mouseup', record_article_read);

            var time_spent = 0;
            function checkVisible(elm) {
                var rect = elm.getBoundingClientRect();
                var viewHeight = Math.max(document.documentElement.clientHeight, window.innerHeight);
                return !(rect . bottom < 0 || (rect . top - 250) - viewHeight >= 0);
            }

            function record_article_read() {
                var scroll = document.innerHTML = window.pageYOffset;
                if(initial_scroll != scroll && scroll >= 10) {

                    function timeView(){
                        time_spent = time_spent + 1;
                    }
                    setInterval(timeView, 1000);

                    if(time_spent >= 2) {
                        jQuery('[read-article]').each(function (index, value) { 
                            var postID = jQuery(this).attr('post-id');
                            var boundingClientRect = jQuery(this).position().top;
                            
                            var in_view = checkVisible(jQuery(this)[0]);

                            if (in_view) {
                                jQuery(this).removeAttr( "read-article" );
                                jQuery(this).attr('read-article-loaded', '');
                        
                                jQuery.ajax({
                                    url: '<?php echo admin_url( 'admin-ajax.php'); ?>',
                                    type: "POST",
                                    data: {
                                        action: 'ajax_record_article_read',
                                        security: '<?php echo wp_create_nonce('ajax_record_article_read_nonce'); ?>',
                                        post_id: postID,
                                        page_url: window.location.href,
                                    }, 
                                    success: function (data) {
                                        //console.log(data)
                                        clearInterval(timeView);
                                    },
                                    error: function (xhr, ajaxOptions, thrownError) {
                                        //console.log(thrownError);
                                    },
                                });
                            }
                        });
                    }

                }

            }

            // Record Claps
            jQuery('.claps-button-container').click (function (){   
                claps= claps +1 ;
                if (clapped === false){
                    //fill in hand svg 
                    jQuery('.claps-button path').removeClass('claps-button');
                    jQuery('.claps-button path').addClass('claps-button-filled');
                
                    //Record clap in sessions
                    jQuery.ajax({
                        url: '<?php echo admin_url( 'admin-ajax.php'); ?>',
                        type: "POST",
                        data: {
                            action: 'ajax_update_session',
                            security: '<?php echo wp_create_nonce('ajax_update_session_clap_nonce'); ?>',
                            page_url: window.location.href,
                            post_id: postID,
                            claps: claps,
                        }, 
                        success: function (data) {
                            // check if clap cookie exists
                            if(clap_cookie_value.length == 0){
                                clap_cookie_value = [];
                                clap_cookie_value.push(current_post);
                            }else{
                                clap_cookie_value_array = clap_cookie_value.split(',');
                                clap_cookie_value_array.forEach(function(element, index){
                                    //check if current post is in cookie value - if not add to cookie value 
                                    if(element !== current_post){
                                        clap_cookie_value_array.push(current_post);
                                        clap_cookie_value = clap_cookie_value_array;
                                    }
                                });
                            }
                            //prevent ajax from running again 
                            clapped = true;
                            clap_cookie = {
                                name: clap_cookie_name,
                                value: clap_cookie_value,
                                expires: long_lived_cookie_expire,
                                path: "/",
                                domain: domain,
                            }
                            setCookie(clap_cookie);
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            //console.log(thrownError);
                        },

                    });

                    //Record clap in page visits
                    jQuery.ajax({
                        url: '<?php echo admin_url( 'admin-ajax.php'); ?>',
                        type: "POST",
                        data: {
                            action: 'ajax_record_visit',
                            security: '<?php echo wp_create_nonce('ajax_record_visit_clap_nonce'); ?>',
                            post_id: postID,
                            page_url:  window.location.href,
                            claps: claps,
                        }, 
                        success: function (data) {
                            //prevent ajax from running again 
                            clapped = true;
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                        //console.log(thrownError);
                        },
                    });        
                }
            }); 

            <?php

            $exit_survey_toggle_check = apply_filters('exit_survey_toggle', true);
            $input_options = ["I'll be right back!","I don't have time right now","I didn't find what I was looking for","The content isn't relevant"];
            $exit_survey_options = apply_filters('exit_survey_options', $input_options);

            if ($exit_survey_toggle_check === true && !is_user_logged_in()) { 
                ?>
                //check if session cookie already exists
                if (exit_survey_cookie_check_value.length === 0){
                    //on body movement track y position of mouse
                    jQuery("body").mouseleave(function(e){
            
                        jQuery("body").off();
                        inputOptionsArray = <?php echo json_encode($exit_survey_options); ?>;
                        const inputOptions= {};
                        jQuery.each(inputOptionsArray, function(key, value){
                        inputOptions[value] = value;
                        })
                        
                        Swal.fire({
                            html: '<p>Before you go, can you answer a quick question?</p><h2>Why are you leaving?</h2>',
                            input: 'radio',
                            inputOptions: inputOptions,
                            showConfirmButton: false,
                            showCancelButton: true,
                            customClass: {
                                container: 'exit_popup_container',
                                content: 'exit_popup_content',
                                htmlContainer: 'exit_popup_html_content',
                                input: 'exit_popup_radio_buttons',
                                inputLabel: 'exit_popup_radio_label',
                            }
                        }).then((result) => {
                            set_exit_survey_cookie()
                        })

                        //on radio button selection
                        jQuery('.exit_popup_radio_buttons input').change( function(){
                            //css and disable after selection
                            jQuery(this).parent().css("background-color","#4EBEC8");
                            jQuery('.exit_popup_radio_buttons input').each(function(){
                                jQuery(this).attr('disabled',true);
                            });
                            var exit_reason = jQuery(this).val();
                            jQuery.ajax({
                                type: "POST",
                                url: '<?php echo admin_url( 'admin-ajax.php'); ?>',
                                data: {
                                    action: 'ajax_record_exit_survey',
                                    security: '<?php echo wp_create_nonce('ajax_record_exit_survey_nonce'); ?>',
                                    page_url: window.location.href,
                                    post_id: postID,
                                    exit_reason: exit_reason,
                                },
                                success: function(data){
                                    set_exit_survey_cookie()
                                    Swal.fire({
                                        text: 'Thanks for your feedback!',
                                        showConfirmButton: false,
                                        timer: 2000,
                                        icon: 'success',
                                    });
                                }
                            });
                        });
                    });
                }
                <?php 
            } 

            ?>
            function set_exit_survey_cookie() {
                var exit_survey_value = "<?php echo $session_data['hash'];?>";

                var current_date = new Date();
                var minutes_away =  20 * 30 * 60000;
                var exit_survey_cookie_expire = new Date(current_date.getTime() + minutes_away);

                exit_survey_cookie = {
                    name: exit_survey_cookie_name,
                    value: exit_survey_value,
                    expires: exit_survey_cookie_expire,
                    path: "/",
                    domain: domain,
                }
                //set or update cookie
                setCookie(exit_survey_cookie);
            }

        }); //end of on load

        </script>   
        <?php
    }

    /**
     * Record Actions
	 *
	 */

    function record_actions() {
        $disable_tracking = apply_filters( 'disable_awesome_analytics_tracking', false );
        if( $disable_tracking == true ) {
            return;
        }

        ?>
        <script> 
        jQuery(document).ready(function($) {

            jQuery('#ihf-location').one('input', function() {
                send_search(true);
            });
            jQuery('.widget_ihomefinderquicksearchwidget').one('input', function() {
                send_search(true);
            });
            jQuery('body').one('input', '.ihf-search .ihf-select-input input', function () {
                send_search(true);
            });

            function send_search(search) {

                jQuery.ajax({
                    url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                    type: "POST",
                    data: {
                        action  : 'ajax_save_searches',
                        security : '<?php echo wp_create_nonce('save_searches_nonce'); ?>',
                    },
                    success:function(data) {
                        // console.log('success ' + data);
                    },
                    error: function(errorThrown){
                        // console.log('error ' + JSON.stringify(errorThrown));
                    }
                });
            }

        });
        </script>
        <?php

    }

}


$TrackVisitorsJS = new TrackVisitorsJS;

