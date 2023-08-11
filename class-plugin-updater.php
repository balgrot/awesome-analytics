<?php
/**
 * WordPress Github Plugin Updater.
 *
 * Allows A WordPress plugin to update from a Github repository release.
 *
 */

class AwesomeAnalytics_Plugin_Updater {

    protected $file;
    protected $plugin;
    protected $basename;
    protected $active;
    private $github_response;
    private $request_uri;
    private $authorization_header;

    /**
     * The username the repository belongs to. If it belongs to an organization, use the organization name.
     *
     * @var string $username The Github username/ organization name.
     */
    private $username = "agenteliteteam";

    /**
     * Repository name.
     *
     * @var string $repository Name of repository.
     */
    private $repository = "awesome-analytics";

    /**
     * If using a private repo, an access token is required to authenticate.
     *
     * @var string $authorize_token The personal access token.
     */
    private $authorize_token = "1dba90e048b339b12a0126c12de2c69304c493e9";

    /**
     * Summary.
     *
     * @since x.x.x
     * @var string $file Absolute path to the main plugin file.
     */
    public function __construct( $file ) {
        $this->file = $file;
        $this->set_request_uri();
        $this->set_request_headers();

        /**
         * Filter the transient used to check for plugin updates and attach our custom plugin information.
         *
         * @link https://developer.wordpress.org/reference/functions/is_multisite/
         * @link https://make.wordpress.org/core/2020/07/30/recommended-usage-of-the-updates-api-to-support-the-auto-updates-ui-for-plugins-and-themes-in-wordpress-5-5/
         */
        if(is_multisite()) {
            add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'modify_transient' ), 10, 1 );
        } else {
            add_filter( 'site_transient_update_plugins', array( $this, 'modify_transient' ), 10, 1 );
        }

        /**
         * Filter the plugins API call and add our info instead, this would normally talk to the official WordPress Plugin Repository.
         *
         * @link https://developer.wordpress.org/reference/functions/plugins_api/
         */
        add_filter( 'plugins_api', array( $this, 'plugin_popup' ), 10, 3);

        /**
         * Move the plugin files and activate if needed.
         *
         * @link https://developer.wordpress.org/reference/hooks/upgrader_post_install/
         */
        add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );

        /**
         * Filter the plugin update request from WordPress so we can add the authorization header for private repos.
         *
         * @link https://developer.wordpress.org/reference/hooks/pre_http_request/
         */
        add_filter('http_request_args', array( $this,  'filter_plugin_update_request' ), 10, 2 );

    }

    /**
     * Set details about the plugin calling this class.
     */
    public function set_plugin_properties() {

        /**
         * Parses the plugin contents to retrieve plugin’s metadata.
         *
         * @link https://developer.wordpress.org/reference/functions/get_plugin_data/
         */

        if(is_admin()) {
            $this->plugin   = get_plugin_data( $this->file );
        }
        
        /**
         * Gets the basename of a plugin.
         *
         * @link https://developer.wordpress.org/reference/functions/plugin_basename/
         */
        $this->basename = plugin_basename( $this->file );

        /**
         * Determines whether a plugin is active.
         *
         * @link https://developer.wordpress.org/reference/functions/is_plugin_active/
         */
        if(is_admin()) {
            $this->active   = is_plugin_active( $this->basename );
        }
    }

    /**
     * Set details about the plugin calling this class.
     */
    public function set_request_uri() {
        $this->request_uri = sprintf( 'https://api.github.com/repos/%s/%s/releases/latest', $this->username, $this->repository );
    }

    /**
     * Set the request headers.
     */
    public function set_request_headers() {
        if( $this->authorize_token ) {
            $this->authorization_header = "Authorization: token {$this->authorize_token}";
        } else {
            $this->authorization_header = '';
        }
    }

    /**
     * Summary.
     *
     * @since x.x.x
     * @var type $var Description.
     */
    private function get_repository_info() {
        if ( is_null( $this->github_response ) ) { // Do we have a response?
            
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->request_uri,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/vnd.github.v3+json',
                    "User-Agent: {$this->username}",
                    $this->authorization_header
                ),
            ));

            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if($httpcode == 200) {
                $this->github_response = json_decode($response, true);
            }

        }
    }

    /**
     * Summary.
     *
     * @since x.x.x
     * @var type $var Description.
     */
    public function modify_transient( $transient ) {

        if(!is_object($transient) && !class_exists($transient) || !is_admin()) {
            return $transient;
        }
        if( property_exists( $transient, 'checked') ) { // Check if transient has a checked property
            if( $checked = $transient->checked ) { // Did WordPress check for updates?
                $this->set_plugin_properties();
                $this->get_repository_info(); // Get the repo info
                if(!$this->github_response || empty($checked[$this->basename])) {
                    return $transient;
                }
                $out_of_date = version_compare( $this->github_response['tag_name'], $checked[$this->basename], 'gt' ); // Check if we're out of date
                if( $out_of_date ) {
                $new_files = $this->github_response['zipball_url']; // Get the ZIP
                $slug = current( explode('/', $this->basename ) ); // Create valid slug
                $plugin = array( // setup our plugin info
                    'url' => $this->plugin["PluginURI"],
                    'slug' => $slug,
                    'package' => $new_files,
                    'new_version' => $this->github_response['tag_name']
                );
                $transient->response[ $this->basename ] = (object) $plugin; // Return it in response
                }
            }
        }
        return $transient; // Return filtered transient
    }

    /**
     * Summary.
     *
     * @since x.x.x
     * @var type $var Description.
     */
    public function plugin_popup( $result, $action, $args ) {
        if( ! empty( $args->slug ) ) { // If there is a slug
            $this->set_plugin_properties();
            if( $args->slug === current( explode( '/' , $this->basename ) ) ) { // And it's our slug
                $this->get_repository_info(); // Get our repo info
                $plugin = array(
                    'name'              => $this->plugin["Name"],
                    'slug'              => $this->basename,
                    'version'           => $this->github_response['tag_name'],
                    'author'            => $this->plugin["AuthorName"],
                    'author_profile'    => $this->plugin["AuthorURI"],
                    'last_updated'      => $this->github_response['published_at'],
                    'homepage'          => $this->plugin["PluginURI"],
                    'short_description' => $this->plugin["Description"],
                    'sections'          => array( 
                        'Updates'       => $this->github_response['body'],
                        'Description'   => $this->plugin["Description"],
                    ),
                    'download_link'     => $this->github_response['zipball_url']
                );
                return (object) $plugin; // Return the data
            }
        }   
        return $result; // Otherwise return default
    }

    /**
     * Summary.
     *
     * @since x.x.x
     * @var type $var Description.
     */
    public function after_install( $response, $hook_extra, $result ) {
        global $wp_filesystem; // Get global FS object

        $install_directory = plugin_dir_path( $this->file ); // Our plugin directory 
        $wp_filesystem->move( $result['destination'], $install_directory ); // Move files to the plugin dir
        $result['destination'] = $install_directory; // Set the destination for the rest of the stack

        if ( $this->active ) { // If it was active
            activate_plugin( $this->basename ); // Reactivate
        }
        return $result;
    }

    /**
     * Filters the preemptive return value of an HTTP request.
     *
     * @var array $request Description.
     */
    function filter_plugin_update_request( array $args, string $url ) {

        if( stripos( $url, $this->username . "/" . $this->repository ) !== false ) {
            $this->set_request_headers();
            $args['headers'] = array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => $this->username,
                'Authorization' => "token {$this->authorize_token}"
            );
            return $args;
        }   

        return $args;
    }

}

