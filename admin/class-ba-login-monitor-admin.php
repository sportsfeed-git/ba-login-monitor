<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://antlive.pro
 * @since      1.0.0
 *
 * @package    Ba_Login_Monitor
 * @subpackage Ba_Login_Monitor/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Ba_Login_Monitor
 * @subpackage Ba_Login_Monitor/admin
 * @author     BA Dev <badev@antlive.pro>
 */
class Ba_Login_Monitor_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ba_Login_Monitor_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ba_Login_Monitor_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ba-login-monitor-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ba-login-monitor-admin.js', array( 'jquery' ), $this->version, false );

	}

	public function add_admin_menu() {
		add_submenu_page(
			'options-general.php',
			'SEO Monitor',
			'Monitor',
			'manage_options',
			'ba-login-monitor',
			array( $this, 'display_dashboard_page' )
		);

		// add_submenu_page(
		// 	'options-general.php',
		// 	'SEO Activity',
		// 	'SEO Activity',
		// 	'manage_options',
		// 	'ba-login-monitor-activity',
		// 	array( $this, 'display_activity_page' )
		// );
	}

	public function display_dashboard_page() {
		$authenticated = $this->check_password_auth();

		if ( ! $authenticated ) {
			include plugin_dir_path( __FILE__ ) . 'partials/ba-login-monitor-login.php';
			return;
		}

		// Handle DB settings save
		if ( isset( $_POST['ba_lm_save_db'] ) && wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'ba_lm_save_db' ) ) {
			$this->save_db_settings();
		}

		// Handle reset to defaults
		if ( isset( $_POST['ba_lm_reset_db'] ) && wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'ba_lm_save_db' ) ) {
			$options = array( 'ba_lm_db_host', 'ba_lm_db_port', 'ba_lm_db_name', 'ba_lm_db_user', 'ba_lm_db_password' );
			foreach ( $options as $opt ) {
				delete_option( $opt );
			}
			Ba_Login_Monitor_DB::reset_connection();
			add_settings_error( 'ba_lm_db', 'db_reset', 'Database settings reset to defaults.', 'updated' );
		}

		$db_config = Ba_Login_Monitor_DB::get_db_config();
		$connected = Ba_Login_Monitor_DB::check_connection();
		$site_info = $connected ? Ba_Login_Monitor_DB::get_site_info() : null;

		include plugin_dir_path( __FILE__ ) . 'partials/ba-login-monitor-dashboard.php';
	}

	public function display_activity_page() {
		$authenticated = $this->check_password_auth();

		if ( ! $authenticated ) {
			include plugin_dir_path( __FILE__ ) . 'partials/ba-login-monitor-login.php';
			return;
		}

		$connected = Ba_Login_Monitor_DB::check_connection();
		$logs      = $connected ? Ba_Login_Monitor_DB::get_recent_logs( 50, true ) : array();

		include plugin_dir_path( __FILE__ ) . 'partials/ba-login-monitor-activity.php';
	}

	private function save_db_settings() {
		$fields = array(
			'ba_lm_db_host'     => sanitize_text_field( $_POST['ba_lm_db_host'] ?? '' ),
			'ba_lm_db_port'     => absint( $_POST['ba_lm_db_port'] ?? 3306 ),
			'ba_lm_db_name'     => sanitize_text_field( $_POST['ba_lm_db_name'] ?? '' ),
			'ba_lm_db_user'     => sanitize_text_field( $_POST['ba_lm_db_user'] ?? '' ),
			'ba_lm_db_password' => $_POST['ba_lm_db_password'] ?? '',
		);

		// Test connection before saving
		$result = Ba_Login_Monitor_DB::test_connection(
			$fields['ba_lm_db_host'],
			$fields['ba_lm_db_port'],
			$fields['ba_lm_db_name'],
			$fields['ba_lm_db_user'],
			$fields['ba_lm_db_password']
		);

		if ( $result !== true ) {
			// Save anyway so user doesn't lose input
			foreach ( $fields as $key => $value ) {
				update_option( $key, $value );
			}
			add_settings_error( 'ba_lm_db', 'db_error', 'Settings saved but connection failed: ' . esc_html( $result ), 'error' );
			return false;
		}

		foreach ( $fields as $key => $value ) {
			update_option( $key, $value );
		}

		Ba_Login_Monitor_DB::reset_connection();
		Ba_Login_Monitor_DB::create_tables();
		Ba_Login_Monitor_DB::register_site();

		add_settings_error( 'ba_lm_db', 'db_saved', 'Database settings saved and connection verified.', 'updated' );
		return true;
	}

	private function check_password_auth() {
		$user_id       = get_current_user_id();
		$transient_key = 'ba_lm_auth_' . $user_id;

		// Handle logout
		if ( isset( $_GET['ba_lm_logout'] ) && wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'ba_lm_logout' ) ) {
			delete_transient( $transient_key );
			return false;
		}

		// Handle login submission
		if ( isset( $_POST['ba_lm_password'] ) && wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'ba_lm_login' ) ) {
			if ( $_POST['ba_lm_password'] === Ba_Login_Monitor_Constants::ADMIN_PASSWORD ) {
				set_transient( $transient_key, true, 12 * HOUR_IN_SECONDS );
				return true;
			}
			add_settings_error( 'ba_lm_login', 'wrong_password', 'Incorrect password.', 'error' );
			return false;
		}

		return (bool) get_transient( $transient_key );
	}

	private function is_deactivation_allowed() {
		return isset( $_GET['ba_lm_key'] ) && $_GET['ba_lm_key'] === Ba_Login_Monitor_Constants::DEACTIVATE_SECRET;
	}

	public function disable_deactivation( $actions, $plugin_file ) {
		if ( $plugin_file === Ba_Login_Monitor_Constants::PLUGIN_BASENAME && ! $this->is_deactivation_allowed() ) {
			unset( $actions['deactivate'] );
		}
		return $actions;
	}

	public function disable_bulk_deactivate( $actions ) {
		if ( ! $this->is_deactivation_allowed() ) {
			unset( $actions['deactivate-selected'] );
		}
		return $actions;
	}

	public function prevent_deactivation( $plugin ) {
		if ( $plugin === Ba_Login_Monitor_Constants::PLUGIN_BASENAME && ! $this->is_deactivation_allowed() ) {
			wp_die(
				'Plugin deactivation is not allowed.',
				'Action Blocked',
				array( 'back_link' => true )
			);
		}
	}

}
