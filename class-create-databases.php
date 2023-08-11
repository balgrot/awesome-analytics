<?php
/**
 * Create database tables for the analytics to store data.
 */
namespace AwesomeAnalytics;

class CreateDatabases {
    /**
	 * Create the page visits table.
	 *
	 * @return void
	 */
    function create_page_visits_table() {
        global $wpdb;
        $table_name = TABLE_ANALYTICS_PAGE_VISITS;
        if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                ID bigint(20) NOT NULL AUTO_INCREMENT,
                created_at date DEFAULT '0000-00-00' NOT NULL,
                post_id bigint(20) NULL,
                page_path text NULL,
                page_query text NULL,
                page_title text NOT NULL,
                page_url text NULL,
                visitors mediumint(5) default 0 NOT NULL,
                page_views mediumint(5) default 0 NOT NULL,
                page_reads mediumint(5) default 0 NOT NULL,
                claps mediumint(5) default 0 NOT NULL,
                PRIMARY KEY  (ID)
            ) $charset_collate;";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }

        $new_columns = [
            [
                "name" => 'page_reads',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'claps',
                "structure" => 'mediumint(5) default 0 NOT NULL'
            ]
        ];

        $table = $wpdb->get_results("SELECT * FROM {$table_name} LIMIT 1", ARRAY_A);
        if(!empty($table)) {
            foreach($new_columns as $column) {
                if(!array_key_exists($column['name'], $table[0])){
                    $wpdb->query("ALTER TABLE {$table_name} ADD {$column['name']} {$column['structure']}");
                }
            }
        } else {
            $table = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = Database() AND TABLE_NAME = '{$table_name}';", ARRAY_A);
            if(!empty($table)) {
                $columns = [];
                foreach($table as $key => $value) {
                    $columns[] = $value['COLUMN_NAME'];
                }
                foreach($new_columns as $column) {
                    if(!in_array($column['name'], $columns)){
                        $wpdb->query("ALTER TABLE {$table_name} ADD {$column['name']} {$column['structure']}");
                    }
                }
            }
        }
    }
    /**
	 * Create the referrers table.
	 *
	 * @return void
	 */
    function create_referrers_table() {
        global $wpdb;
        $table_name = TABLE_ANALYTICS_REFERRERS;
        if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                ID bigint(20) NOT NULL AUTO_INCREMENT,
                created_at date DEFAULT '0000-00-00' NOT NULL,
                referrer_domain text NOT NULL,
                referrer_path text NULL,
                search_engine text NULL,
                social_network text NULL,
                unique_visitors mediumint(5) default 0 NULL,
                visitors mediumint(5) default 0 NOT NULL,
                PRIMARY KEY  (ID)
            ) $charset_collate;";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
    }
    /**
	 * Create the sessions table.
	 *
	 * @return void
	 */
    function create_sessions_table() {
        global $wpdb;
        $table_name = TABLE_ANALYTICS_SESSIONS;
        if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                ID bigint(20) NOT NULL AUTO_INCREMENT,
                guid varchar(64) NOT NULL,
                created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                pages_visited tinyint default 0 NOT NULL,
                actions smallint default 0 NULL,
                searches smallint default 0 NULL,
                referrer_id bigint(20) NULL,  
                user_agent text NULL,
                referrer_keywords text NULL,
                entry_page_path text NULL,
                entry_page_query text NULL,
                entry_page_title text NOT NULL,
                entry_page_url text NULL,
                exit_page_path text NULL,
                exit_page_query text NULL,
                exit_page_title text NOT NULL,
                exit_page_url text NULL,
                visit_duration bigint(20) default 0 NOT NULL,
                unique_session boolean NULL,
                logged_in boolean NULL,
                ip text NULL,
                page_reads mediumint(5) default 0 NOT NULL,
                user_id bigint(20) NULL,
                utm_source text NULL,
                utm_medium text NULL,
                utm_campaign text NULL,
                utm_term text NULL,
                utm_content text NULL,
                claps mediumint(5) default 0 NOT NULL,
                timezone text NULL,
                city text NULL,
                region_code text NULL,
                region_name text NULL,
                country_code text NULL,
                zip_code text NULL,
                country_name text NULL,
                latitude text NULL,
                longitude text NULL,
                PRIMARY KEY  (ID)
            ) $charset_collate;";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }

        $new_columns = [
            [
                "name" => 'ip',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'page_reads',
                "structure" => 'mediumint(5) default 0 NOT NULL'
            ],
            [
                "name" => 'user_id',
                "structure" => 'bigint(20) NULL'
            ],
            [
                "name" => 'claps',
                "structure" => 'mediumint(5) default 0 NOT NULL'
            ],
            [
                "name" => 'utm_source',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'utm_medium',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'utm_campaign',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'utm_term',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'utm_content',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'timezone',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'city',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'region_code',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'region_name',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'zip_code',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'country_code',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'country_name',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'latitude',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'longitude',
                "structure" => 'text NULL'
            ],
            
        ];

        $table = $wpdb->get_results("SELECT * FROM {$table_name} LIMIT 1", ARRAY_A);
        if(!empty($table)) {
            foreach($new_columns as $column) {
                if(!array_key_exists($column['name'], $table[0])){
                    $wpdb->query("ALTER TABLE {$table_name} ADD {$column['name']} {$column['structure']}");
                }
            }
        } else {
            $table = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = Database() AND TABLE_NAME = '{$table_name}';", ARRAY_A);
            if(!empty($table)) {
                $columns = [];
                foreach($table as $key => $value) {
                    $columns[] = $value['COLUMN_NAME'];
                }
                foreach($new_columns as $column) {
                    if(!in_array($column['name'], $columns)){
                        $wpdb->query("ALTER TABLE {$table_name} ADD {$column['name']} {$column['structure']}");
                    }
                }
            }
        }
    }

    /**
	 * Create the users table.
	 *
	 * @return void
	 */
    function create_users_table() {
        global $wpdb;
        $table_name = TABLE_ANALYTICS_USERS;
        if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                ID bigint(20) NOT NULL AUTO_INCREMENT,
                wp_user_id bigint(20) NOT NULL,
                registered datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                deleted datetime NULL,
		last_login datetime NULL,
                source text NULL,
                timezone text NULL,
                city text NULL,
                region_code text NULL,
                region_name text NULL,
                country_code text NULL,
                zip_code text NULL,
                country_name text NULL,
                latitude text NULL,
                longitude text NULL,
                PRIMARY KEY  (ID)
            ) $charset_collate;";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
        $new_columns = [
                   [
                "name" => 'timezone',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'city',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'region_code',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'region_name',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'zip_code',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'country_code',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'country_name',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'latitude',
                "structure" => 'text NULL'
            ],
            [
                "name" => 'longitude',
                "structure" => 'text NULL'
            ],
		[
                "name" => 'last_login',
                "structure" => 'datetime NULL'
            ],
        ];

        $table = $wpdb->get_results("SELECT * FROM {$table_name} LIMIT 1", ARRAY_A);
        if(!empty($table)) {
            foreach($new_columns as $column) {
                if(!array_key_exists($column['name'], $table[0])){
                    $wpdb->query("ALTER TABLE {$table_name} ADD {$column['name']} {$column['structure']}");
                }
            }
        } else {
            $table = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = Database() AND TABLE_NAME = '{$table_name}';", ARRAY_A);
            if(!empty($table)) {
                $columns = [];
                foreach($table as $key => $value) {
                    $columns[] = $value['COLUMN_NAME'];
                }
                foreach($new_columns as $column) {
                    if(!in_array($column['name'], $columns)){
                        $wpdb->query("ALTER TABLE {$table_name} ADD {$column['name']} {$column['structure']}");
                    }
                }
            }
        }
    }

    /**
	 * Create the users table.
	 *
	 * @return void
	 */
    function create_short_links_table() {
        global $wpdb;
        $table_name = TABLE_ANALYTICS_SHORT_LINKS;
        if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                ID bigint(20) NOT NULL AUTO_INCREMENT,
                post_id bigint(20) NULL,
                post_params text NULL,
                redirect_url text NULL,
                code text NOT NULL,
                count bigint(20) DEFAULT 0 NOT NULL,
                created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY  (ID)
            ) $charset_collate;";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }

        
    }
    // /**
	//  * Create the exit survey answers table.
	//  *
	//  * @return void
	//  */
    // function create_exit_survey_table(){
    //     global $wpdb;
    //     $table_name = TABLE_ANALYTICS_EXIT_SURVEY;
    //     if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
    //         $charset_collate = $wpdb->get_charset_collate();
    //         $sql = "CREATE TABLE $table_name (
    //             ID bigint(20) NOT NULL AUTO_INCREMENT,
    //             created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    //             page_title text NOT NULL,
    //             page_url text NULL,
    //             exit_reason text NULL,
    //             PRIMARY KEY  (ID)
    //         ) $charset_collate;";
    //         require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    //         dbDelta( $sql );
    //     }
    // }

    /**
	 * Create the tracking click table.
	 *
	 * @return void
	 */
    function create_track_links_table(){
        global $wpdb;
        $table_name = TABLE_ANALYTICS_TRACK_LINKS;
        if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                ID bigint(20) NOT NULL AUTO_INCREMENT,
                created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                page_title text NOT NULL,
                page_url text NULL,
                click_type text NULL,
                link_content text NULL,
                PRIMARY KEY  (ID)
            ) $charset_collate;";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
    }

}
