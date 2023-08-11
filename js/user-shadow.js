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
    var domain = user_shadow.domain;
    //var postID = jQuery('[read-article]').attr('post-id');
    var postID = jQuery("meta[property='aa:post_id']").attr("content");
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
    var clap_cookie_name = user_shadow.clap_cookie_name;
    var clap_cookie_value = cookieExists(clap_cookie_name);
    current_post = JSON.parse(JSON.stringify(user_shadow.post_id));
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

    // Session Cookie
    var session_cookie_name = user_shadow.session_cookie_name;
    var session_cookie_exists = false;
    var session_cookie_check_value = cookieExists(session_cookie_name);

    // Long Lived Cookie
    var long_lived_cookie_name = user_shadow.long_lived_cookie_name;
    var long_lived_cookie_value= user_shadow.long_lived_cookie_value;
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
    var has_utm = user_shadow.has_utm;
    var has_referrer = user_shadow.has_referrer;
    if(has_utm == 1 || has_referrer == 1) {
        scroll_tracking();
    }

    function scroll_tracking() {
        //console.log('Scroll tracking');
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

                    var data = {
                        page_url: jQuery("meta[property='aa:url']").attr("content"),
                        post_id: postID,
                        referrer_id: user_shadow.referrer_id,
                        referrer_keywords: user_shadow.referrer_keywords,
                        search: user_shadow.search,
                        ip_address: user_shadow.ip_address,
                        unique: unique,
                    };

                    axios.post(wpApiSettings.root + 'api/v1/analytics/recordsession', data, { 
                        headers: { 'X-WP-Nonce' : wpApiSettings.nonce }
                    }).then(function(data){
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
                    }).catch(function(error){
                        console.log(error);
                    });
            } else {
                var data = {
                    post_id: postID,
                    page_url: jQuery("meta[property='aa:url']").attr("content"),
                    unique: unique,
                };
                axios.post(wpApiSettings.root + 'api/v1/analytics/updatesession', data, { 
                    headers: { 'X-WP-Nonce' : wpApiSettings.nonce }
                }).then(function(data){
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
                }).catch(function(error){
                    console.log(error);
                });
            }

            var data = {
                post_id: postID,
                page_url: jQuery("meta[property='aa:url']").attr("content"),
                unique: unique,
                
            };

            axios.post(wpApiSettings.root + 'api/v1/analytics/recordvisit', data, {
                headers: { 'X-WP-Nonce' : wpApiSettings.nonce }
            }).then(function(data){

            }).catch(function(error){
                console.log(error);
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
                    var postID = jQuery("meta[property='aa:post_id']").attr("content");
                    var boundingClientRect = jQuery(this).position().top;
                    
                    var in_view = checkVisible(jQuery(this)[0]);

                    if (in_view) {
                        jQuery(this).removeAttr( "read-article" );
                        jQuery(this).attr('read-article-loaded', '');

                        var data = {
                            post_id: postID,
                            page_url: jQuery("meta[property='aa:url']").attr("content"),
                        };
                        console.log(postID);

                        axios.post(wpApiSettings.root + 'api/v1/analytics/articleread', data, {
                            headers: { 'X-WP-Nonce' : wpApiSettings.nonce }
                        }).then(function(data){
                            clearInterval(timeView);
                        }).catch(function(error){
                            console.log(error);
                        });
                    }
                });
            }

        }

    }

}); //end of on load

jQuery(document).ready(function() {

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

        var data = {};

        axios.post(wpApiSettings.root + 'api/v1/analytics/savesearches', data, {
            headers: { 'X-WP-Nonce' : wpApiSettings.nonce }
        }).then(function(data){
            // console.log('success ' + data);
        }).catch(function(error){
            // console.log(error);
        })
    }

    jQuery('a').on('click', function(){
        var href = jQuery(this).attr('href');
        var click_type = '';
        var link_content = '';
        if( href.includes('mailto:') || href.includes('tel:') ) {
            if ( href.includes('mailto:') ) {
                click_type = 'email';
                link_content = href.substring(7);
            }

            if ( href.includes('tel:') ) {
                click_type = 'phone';
                link_content = href.substring(5);
            }

            var data = {
                page_url: jQuery("meta[property='aa:url']").attr("content"),
                page_title: jQuery("meta[property='aa:title']").attr("content"),
                link_content: link_content,
                click_type: click_type,
            };

            axios.post(wpApiSettings.root + 'api/v1/analytics/recordclicks', data, {
                headers: { 'X-WP-Nonce' : wpApiSettings.nonce }
            }).then(function(data){
                //console.log('Click recorded');
            }).catch(function(error){
                console.log(error);
            });
        }
    });

});