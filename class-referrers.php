<?php
/**
 * Perform actions for website referrers.
 */
namespace AwesomeAnalytics;

class Referrer {

    public static function create_or_update( string $referrer_url, bool $unique=false ) {
        date_default_timezone_set('America/Los_Angeles');
        global $wpdb;
        $table = TABLE_ANALYTICS_REFERRERS;
        $created_at = date("Y-m-d");
        $referrer_data = Referrer::parse_referrer_url( $referrer_url );
        if( is_bool($unique) === false ) {
            $unique = true;
        }
        $referrer = Referrer::get( $referrer_data['referrer_domain'] );
        if( $referrer ) {
            if( $unique == true ) {
                $referrer->unique_visitors = $referrer->unique_visitors + 1;
                $referrer->visitors = $referrer->visitors + 1;
                $result = $wpdb->update(
                    $table,
                    array(
                        'unique_visitors' => $referrer->unique_visitors,
                        'visitors' => $referrer->visitors
                    ),
                    array(
                        'ID' => $referrer->ID
                    ),
                    array(
                        '%d',
                    )
                );
                if($result !== false) {
                    return $referrer->ID;
                } else {
                    return null;
                }
            } else {
                $referrer->visitors = $referrer->visitors + 1;
                $result = $wpdb->update(
                    $table,
                    array(
                        'visitors' => $referrer->visitors
                    ),
                    array(
                        'ID' => $referrer->ID
                    ),
                    array(
                        '%d',
                    )
                );
                if($result !== false) {
                    return $referrer->ID;
                } else {
                    return null;
                }
            }
            return $referrer->ID;
        } else {
            if( $unique == true ) {
                $unique_visitors = 1;
            } else {
                $unique_visitors = 0;
            }
            $result = $wpdb->insert(
                $table,
                array(
                    'created_at' => $created_at,
                    'referrer_domain' => Helpers::clean_string($referrer_data['referrer_domain']),
                    'referrer_path' => Helpers::clean_string($referrer_data['referrer_path']),
                    'search_engine' => $referrer_data['search_engine'],
                    'social_network' => $referrer_data['social_network'],
                    'unique_visitors' => $unique_visitors,
                    'visitors' => 1
                ),
                array(
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d'
                )
            );
            if($result == 1) {
                return array('id' => $wpdb->insert_id, 'keywords' =>  $referrer_data['keywords']);
            } else {
                return null;
            }
        }
    }


    //Get Referrer
    public static function get( string $referrer_domain ) {
        date_default_timezone_set('America/Los_Angeles');
        $created_at = date("Y-m-d");
        global $wpdb;
        $table = TABLE_ANALYTICS_REFERRERS;
        $referrer_domain = esc_sql(Helpers::clean_string($referrer_domain));
        $referrer = $wpdb->get_row( " SELECT * FROM {$table} WHERE created_at ='{$created_at}' AND referrer_domain = '{$referrer_domain}'" );
        if( null !== $referrer ) {
            return $referrer;
        } else {
            return false;
        }
    }
 
    public static function parse_referrer_url( string $referrer_url ) {
        $referrer_data = array(
            'referrer_domain' => '',
            'referrer_path' => null,
            'search_engine' => null,
            'social_network' => null,
            'keywords' => null,
        );
        $url_parts = parse_url($referrer_url);
        $referrer_data['referrer_domain'] = $url_parts['host'];
        $referrer_path = trim(ltrim(str_replace( $url_parts['scheme'] . "://" . $url_parts['host'], '', $referrer_url ), '/'), '/');
        if($referrer_path) {
            $referrer_data['referrer_path'] = Helpers::clean_string($referrer_path);
        }
        $keywords =  Referrer::get_search_data( $referrer_url );
        if(isset($keywords[0])) {
            $referrer_data['search_engine'] = $keywords[0];
        }
        if(isset($keywords[1])) {
            $referrer_data['keywords'] = Helpers::clean_string($keywords[1]);
        }
        $referrer_data['social_network'] = Referrer::get_social_network($referrer_url);
        return $referrer_data;
    }


    public static function get_social_network($referrer_url) {
        $network = '';
        $networks = array(
            'facebook.com'    => 'Facebook',
            'instagram.com'   => 'Instagram',
            'linkedin.com'    => 'LinkedIn',
            'youtube.com'     => 'Youtube',
            'pinterest.com'   => 'Pinterest',
            'yelp.com'        => 'Yelp',
        );
        $host = parse_url($referrer_url, PHP_URL_HOST);
        foreach($networks as $network => $name) {
            if (Helpers::ends_with($host, $network)) {
                return $name;
            }   
        }
        return;
    }


    public static function get_search_data($referrer_url) {
        $search_phrase = '';
        $engines = array(
            array(
                'engine' => '360.cn',
                'url' => '360.cn',
                'param' => 'q'
            ),
            array(
                'engine' => 'Alice',
                'url' => 'alice.com',
                'param' => 'qs'
            ),
            array(
                'engine' => 'Alice',
                'url' => 'aliceadsl.fr',
                'param' => 'qs'
            ),
            array(
                'engine' => 'Alltheweb',
                'url' => 'www.alltheweb.com',
                'param' => 'q'
            ),
            array(
                'engine' => 'Altavista',
                'url' => 'www.altavista.com',
                'param' => 'q'
            ),
            array(
                'engine' => 'AOL',
                'url' => 'www.aol.com',
                'param' => 'q'
            ),
            array(
                'engine' => 'Ask',
                'url' => 'www.ask.com',
                'param' => 'a'
            ),
            array(
                'engine' => 'Ask',
                'url' => 'search.aol.fr',
                'param' => 'q'
            ),
            array(
                'engine' => 'Ask',
                'url' => 'alicesuche.aol.de',
                'param' => 'q'
            ),
            array(
                'engine' => 'Auone',
                'url' => 'search.auone.jp',
                'param' => 'q'
            ),
            array(
                'engine' => 'Avg',
                'url' => 'isearch.avg.com ',
                'param' => 'q'
            ),
            array(
                'engine' => 'Babylon',
                'url' => 'search.babylon.com ',
                'param' => 'q'
            ),
            array(
                'engine' => 'Baidu',
                'url' => 'www.baidu.com',
                'param' => 'word'
            ),
            array(
                'engine' => 'Biglobe',
                'url' => 'biglobe.ne.jp',
                'param' => 'q'
            ),
            array(
                'engine' => 'Bing',
                'url' => 'www.bing.com',
                'param' => 'q'
            ),
            array(
                'engine' => 'Centrum.cz',
                'url' => 'search.centrum.cz',
                'param' => 'q'
            ),
            array(
                'engine' => 'Comcast',
                'url' => 'search.comcast.net',
                'param' => 'q'
            ),
            array(
                'engine' => 'Conduit',
                'url' => 'search.conduit.com ',
                'param' => 'q'
            ),
            array(
                'engine' => 'CNN',
                'url' => 'www.cnn.com/SEARCH',
                'param' => 'query'
            ),
            array(
                'engine' => 'Daum',
                'url' => 'www.daum.net',
                'param' => 'q'
            ),
            array(
                'engine' => 'DuckDuckGo',
                'url' => 'duckduckgo.com',
                'param' => 'q'
            ),
            array(
                'engine' => 'Ecosia',
                'url' => 'www.ecosia.org',
                'param' => 'q'
            ),
            array(
                'engine' => 'Ekolay',
                'url' => 'www.ekolay.net',
                'param' => 'q'
            ),
            array(
                'engine' => 'Eniro',
                'url' => 'www.eniro.se',
                'param' => 'search_word'
            ),
            array(
                'engine' => 'Globo',
                'url' => 'www.globo.com/busca',
                'param' => 'q'
            ),
            array(
                'engine' => 'go.mail.ru',
                'url' => 'go.mail.ru',
                'param' => 'q'
            ),
            array(
                'engine' => 'Google',
                'url' => 'www.google.com',
                'param' => 'q'
            ),
            array(
                'engine' => 'goo.ne',
                'url' => 'goo.ne.jp',
                'param' => 'MT'
            ),
            array(
                'engine' => 'haosou.com',
                'url' => 'www.haosou.com/s',
                'param' => 'q'
            ),
            array(
                'engine' => 'Incredimail',
                'url' => 'search.incredimail.com',
                'param' => 'q'
            ),
            array(
                'engine' => 'Kvasir',
                'url' => 'www.kvasir.no',
                'param' => 'q'
            ),
            array(
                'engine' => 'Live',
                'url' => 'www.bing.com',
                'param' => 'q'
            ),
            array(
                'engine' => 'Lycos',
                'url' => 'www.lycos.com',
                'param' => 'q'
            ),
            array(
                'engine' => 'Lycos',
                'url' => 'search.lycos.de',
                'param' => 'query'
            ),
            array(
                'engine' => 'Mamma',
                'url' => 'www.mamma.com',
                'param' => 'query'
            ),
            array(
                'engine' => 'MSN',
                'url' => 'www.msn.com',
                'param' => 'q'
            ),
            array(
                'engine' => 'MSN',
                'url' => 'money.msn.com',
                'param' => 'q'
            ),
            array(
                'engine' => 'MSN',
                'url' => 'local.msn.com',
                'param' => 'q'
            ),
            array(
                'engine' => 'Mynet',
                'url' => 'www.mynet.com',
                'param' => 'q'
            ),
            array(
                'engine' => 'Najdi',
                'url' => 'najdi.si',
                'param' => 'q'
            ),
            array(
                'engine' => 'Naver',
                'url' => 'www.naver.com',
                'param' => 'query'
            ),
            array(
                'engine' => 'Netscape',
                'url' => 'search.netscape.com',
                'param' => 'query'
            ),
            array(
                'engine' => 'ONET',
                'url' => 'szukaj.onet.pl',
                'param' => 'q'
            ),
            array(
                'engine' => 'Ozu',
                'url' => 'www.ozu.es',
                'param' => 'q'
            ),
            array(
                'engine' => 'Rakuten',
                'url' => 'rakuten.co.jp',
                'param' => 'qt'
            ),
            array(
                'engine' => 'Rambler',
                'url' => 'rambler.ru',
                'param' => 'query'
            ),
            array(
                'engine' => 'Search-results',
                'url' => 'search-results.com ',
                'param' => 'q'
            ),
            array(
                'engine' => 'search.smt.docomo',
                'url' => 'search.smt.docomo.ne.jp',
                'param' => 'MT'
            ),
            array(
                'engine' => 'Sesam',
                'url' => 'sesam.no',
                'param' => 'q'
            ),
            array(
                'engine' => 'Seznam',
                'url' => 'www.seznam.cz',
                'param' => 'q'
            ),
            array(
                'engine' => 'So.com',
                'url' => 'www.so.com/s ',
                'param' => 'q'
            ),
            array(
                'engine' => 'Sogou',
                'url' => 'www.sogou.com',
                'param' => 'query'
            ),
            array(
                'engine' => 'Startsiden',
                'url' => 'www.startsiden.no/sok ',
                'param' => 'q'
            ),
            array(
                'engine' => 'Szukacz',
                'url' => 'www.szukacz.pl',
                'param' => 'q'
            ),
            array(
                'engine' => 'Terra',
                'url' => 'buscador.terra.com.br',
                'param' => 'query'
            ),
            array(
                'engine' => 'Tut.by',
                'url' => 'search.tut.by',
                'param' => 'query'
            ),
            array(
                'engine' => 'Ukr',
                'url' => 'search.ukr.net',
                'param' => 'q'
            ),
            array(
                'engine' => 'Virgilio',
                'url' => 'search.virgilio.it',
                'param' => 'qs'
            ),
            array(
                'engine' => 'Voila',
                'url' => 'www.voila.fr',
                'param' => 'rdata'
            ),
            array(
                'engine' => 'Wirtulana Polska',
                'url' => 'www.wp.pl',
                'param' => 'szukaj'
            ),
            array(
                'engine' => 'Yahoo',
                'url' => 'www.yahoo.com',
                'param' => 'p'
            ),
            array(
                'engine' => 'Yahoo',
                'url' => 'yahoo.cn',
                'param' => 'p'
            ),
            array(
                'engine' => 'Yahoo',
                'url' => 'm.yahoo.com',
                'param' => 'p'
            ),
            array(
                'engine' => 'Yandex',
                'url' => 'www.yandex.com',
                'param' => 'text'
            ),
            array(
                'engine' => 'Yandex',
                'url' => 'yandex.ru',
                'param' => 'text'
            ),
            array(
                'engine' => 'Yam',
                'url' => 'www.yam.com',
                'param' => 'k'
            )
        );
    
        foreach($engines as $engine) {
            if (stripos($referrer_url, $engine['url']) !== false) {
                parse_str( parse_url( $referrer_url, PHP_URL_QUERY ), $query );
                if(isset($query[$engine['param']]) && !empty($query[$engine['param']])) {
                    $search_phrase = urldecode($query[$engine['param']]);
                }
                return array($engine['engine'], $search_phrase);
            }if (stripos($referrer_url, 'buyerlink') !== false) {
                return array('AE Network', '');
            }   
        }
        return;
    }

    /**
	 *  Get the server referrer
	 *
	 * @return void
	 */
    public static function get_server_referrer() {
        $domain = (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : "";
        $referrer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : "";

        if (empty($referrer) || stripos($referrer, $domain) !== false ) {
            return false;
        }

        if (stripos($referrer, "https://") === false && stripos($referrer, "http://") === false) {
            $referrer = "https://" . $referrer;
        }

        if(parse_url($referrer,PHP_URL_HOST) != null) {
            $check_spam = Referrer::check_referrer_spam( $referrer );
            if( $check_spam ){
                return false;
            }
            return Helpers::clean_string($referrer);
        } else {
            return false;
        }
    }

    /**
	 *  Check Referrer Spam
	 *
	 * @return boolean whether it's spam or not
	 */
    public static function check_referrer_spam( $referrer ) {
        $spam = file( plugin_dir_path( __FILE__ ) .  'referrer-spam.txt', FILE_IGNORE_NEW_LINES );
        foreach($spam as $spam_item){
            if( stripos( $referrer, $spam_item ) !== false ){
                return true;
            }
        }
        return false;
    }

}