<?php
/**
 * Plugin Name: WP Soap API
 * Plugin URI: http://infobeans.com
 * Description: A plugin to expose wordpress soap webservice. It includes users, posts, options, taxonomies, terms. Uses PSR-2 Coding standards.
 * Version: 1.0
 * Author: Kapil yadav
 * Author URI: http://infobeans.com/
 */
//error_reporting('E_ALL');
//ini_set('display_errors', true);

/**
 * WPsoap Class Wrapper
 *
 * @author kapil
 */
class WpSoap {

    /**
     * Class Member variables
     * @since   1.0
     */
    public $config;
    public $errors;

    /**
     * Class Constructor 
     * @since   1.0
     */
    public function __construct() {

        //Set Admin Menu Page Configuration Parameters
        $this->config = array(
            'page_title' => 'WP Soap API',
            'menu_title' => 'WP Soap API',
            'capability' => 'manage_options',
            'menu_slug' => 'wp_soap',
            'dashicons' => 'dashicons-networking'
        );

        //Set Soap Server Errors Parameters
        $this->errors = array(
            '100' => 'Post Object Not Found',
            '101' => 'Email address not registered',
            '102' => 'Invalid Email Address',
            '103' => 'Invalid number of arguments',
            '104' => 'Authentication Error',
            '105' => 'Invalid/Expired Authentication Token',
            '106' => 'Invalid Option Key',
            '107' => 'Invalid Term ID',
            '108' => 'Invalid Taxonomy Name',
            '109' => 'Error in Fetching Post Objects',
            '110' => 'Invalid Data Type'
        );

        //Initialize Settings
        add_action('init', array($this, '_wpSoapinit'));
        add_action('init', array($this, '_wpSoapGetEndPoint'));
    }

    /**
     * Initialize the Settings 
     *
     * @since   11.1
     */
    public function _wpSoapinit() {

        //Admin menu and assets load
        add_action('admin_menu', array($this, '_wpSoapaddMenuPage'));
        add_action('admin_enqueue_scripts', array($this, '_wpSoapLoadAssets'));
        //Identify Request Endpoint and Initalize Soap Server
        add_action('template_redirect', array($this, '_wpSoapGetEndPointData'));
    }

    /**
     * Idnetify Soap Request Endpoint and Perform URL Pattern Rewrite Rules Validation Checks
     * @since   1.0
     */
    public function _wpSoapGetEndPoint() {
        global $wp_rewrite;
        add_rewrite_tag('%api%', 'soap');
        add_rewrite_tag('%version%', 'v1');
        add_rewrite_rule('api/?$', 'index.php?api=$matches[1]&version=$matches[2]', 'top');
        $wp_rewrite->flush_rules();
    }

    /**
     * Idnetify Soap Request Endpoint and Handle with Soap Server Handle
     * @since   1.0
     */
    public function _wpSoapGetEndPointData() {

        //Remove whitespaces if any
        ob_clean();

        //Globals to get wp_query
        global $wp_query;

        //Get query params and identify valid endpoint
        $api = $wp_query->get('api');
        $version = $wp_query->get('version');

        if ($api == 'soap' && $version == 'v1') {

            $server = new SoapServer(null, array('uri' => plugins_url() . '/wpsoap/wsdl/wpsoap.wsdl'));
            $server->setClass('WpSoap');
            $server->handle();
            exit();
        }
    }

    /* Add Admin WpSoap Plugin setting Menu Page
     * @since 1.0
     */

    public function _wpSoapaddMenuPage() {
        $wpsoap = add_menu_page($this->config['page_title'], $this->config['menu_title'], $this->config['capability'], $this->config['menu_slug'], array($this, '_wpSoapAdminUi'), $this->config['dashicons']);
    }

    /**
     * WpSoap Admin Setting UI 
     * 
     * @since   1.0
     */
    public function _wpSoapAdminUi() {
        ?>
        <div class = "wrap wpsoap" style="overflow: auto;">
            <h4><span class="dashicons-before dashicons-networking"></span> WP Soap API </h4>
            <p class='description space10'> Exposes Wordpress Core Methods Via Soap Web Services.</p>
            <div class="alert alert-info" role="alert"><div class="dashicons-before dashicons-editor-ul pull-left"></div>&nbsp;Available Soap API Methods</div>
            <?php
            ini_set("soap.wsdl_cache_enabled", 0);
            $client = new SoapClient(plugins_url() . "/wpsoap/wsdl/wpsoap.wsdl", array(' cache_wsdl ' => WSDL_CACHE_NONE));
            $count = 1;
            ?>
            <ul class="list-group">
                <?php foreach ($client->__getFunctions() as $method) { ?>
                    <li class="list-group-item"><?php echo $method; ?><span class="badge pull-left p-a-3"><?php echo $count; ?></span> </li>
                    <?php
                    $count++;
                }
                ?>
            </ul>
            <div class="alert alert-info" role="alert"><div class="dashicons-before dashicons-menu pull-left"></div>&nbsp;Settings</div>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">WSDL LOCATION</th>
                    <td><input type="text" name="wpsoap_wsdl_url" value="<?php echo esc_attr(plugins_url() . "/wpsoap/wsdl/wpsoap.wsdl"); ?>" readonly /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">SOAP SERVER LOCATION</th>
                    <td><input type="text" name="wpsoap_soapserver_url" value="<?php echo esc_attr(trailingslashit(site_url()) . "?api=soap&version=v1"); ?>" readonly /></td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Load Assets CSS and JS
     * @since   1.0
     */
    public function _wpSoapLoadAssets() {

        wp_enqueue_style('wpsoap', plugins_url() . "/wpsoap/assets/css/wpsoap.css");
        wp_enqueue_style('wpsoap_bootstrapstyle', plugins_url() . "/wpsoap/assets/css/bootstrap.min.css");
        wp_enqueue_script('wpsoap_bootstrapscript', plugins_url() . "/wpsoap/assets/js/bootstrap.min.js", array('jquery'));
    }

    /**
     * Get post object by id
     * @param   $id     Post object id
     * @since   1.0
     */
    public function getPost($token = null, $id = null) {

        //Validate auth token
        if ($this->validateRequestToken($token) === false) {
            return new SoapFault('105', $this->errors['105']);
        }

        //Data type validation
        if (filter_var($id, FILTER_VALIDATE_INT) === false) {
            return new SoapFault('110', $this->errors['110']);
        }

        if (!is_object(get_post($id))) {
            return new SoapFault('100', $this->errors['100']);
        }

        return get_post($id);
    }

    /**
     * Get User object by email id
     * @param   $id     User email id
     * @since   1.0
     */
    public function getUser($token = null, $email = null) {

        //Validate auth token
        if ($this->validateRequestToken($token) === false) {
            return new SoapFault('105', $this->errors['105']);
        }

        //Validate Email
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return new SoapFault('102', $this->errors['102']);
        }

        //Check Email Exist
        if (!(email_exists($email))) {
            return new SoapFault('101', $this->errors['101']);
        }

        //Return User Object
        $user = get_user_by('ID', email_exists($email));
        unset($user->data->user_pass);
        return $user->data;
    }

    /**
     * Get option by key
     * @param   $key    option key
     * @since   1.0
     */
    public function getOption($token = null, $key = null) {

        //Validate auth token
        if ($this->validateRequestToken($token) === false) {
            return new SoapFault('105', $this->errors['105']);
        }

        if (false === get_option($key)) {
            return new SoapFault('106', $this->errors['106']);
        }

        return get_option($key);
    }

    /**
     * Get Term by ID
     * @param   $token, $term_id, $taxonomy  
     * @since   1.0
     */
    public function getTerm($token = null, $term_id = null, $taxonomy = null) {

        //Validate auth token
        if ($this->validateRequestToken($token) === false) {
            return new SoapFault('105', $this->errors['105']);
        }

        if (NULL === get_term($term_id, $taxonomy) || is_wp_error(get_term($term_id, $taxonomy))) {
            return new SoapFault('107', $this->errors['107']);
        }

        return get_term($term_id, $taxonomy);
    }

    /**
     * Get Taxonomy by key
     * @param   $token, $name    Taxonomy name
     * @since   1.0
     */
    public function getTaxonomy($token = null, $name = null) {

        //Validate auth token
        if ($this->validateRequestToken($token) === false) {
            return new SoapFault('105', $this->errors['105']);
        }

        if (false === get_taxonomy($name)) {
            return new SoapFault('108', $this->errors['108']);
        }

        return get_taxonomy($name);
    }

    /*
     * Authenticate user
     * 
     * @param1  string  Registered Email address
     * @param2  string  Password asscoiated with email address
     * 
     * @since   1.0
     */

    public function authenticate($email = null, $password = null) {

        //Validate number of arguments
        if (count($args = func_get_args()) != 2 || ($args[0] === '') || ($args[1] === '')) {
            return new SoapFault('103', $this->errors['103']);
        }

        //Validate first argument as valid email
        if (filter_var($args[0], FILTER_VALIDATE_EMAIL) === false) {
            return new SoapFault('102', $this->errors['102']);
        }

        //Sign on User
        $creds = array(
            'user_login' => $email,
            'user_password' => $password,
            'remember' => true
        );

        if (is_wp_error(wp_signon($creds, false))) {
            return new SoapFault('104', $this->errors['104']);
        }

        //Save transieant and return token
        $hash = "wpsoap_" . $this->generateSecureToken(8);
        set_transient($hash, 1, 60 * 600);
        return $hash;
    }

    /*
     * Generate Token ( Default 8 alphanumeric digits )
     * @param int: 8
     * @return alphnumeric string: length(8)
     * @since   1.0
     */

    public function generateSecureToken($length = 8, $charset = 'abcdefghijkmnopqrpdctvwxyz3456789') {
        $str = '';
        $count = strlen($charset);
        while ($length--) {
            $str .= $charset[mt_rand(0, $count - 1)];
        }
        return $str;
    }

    /**
     * Validate Session
     * @return BOOLEAN  Returns the boolean session
     * @since   1.0
     */
    public function validateRequestToken($token = null) {

        //validate args
        if (count($args = func_get_args()) != 1) {
            return false;
        }

        //validate transient
        if (get_transient($token) != 1) {
            return false;
        }

        return true;
    }

}

new WpSoap();
