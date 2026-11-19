<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://antlive.pro
 * @since      1.0.0
 *
 * @package    Ba_Login_Monitor
 * @subpackage Ba_Login_Monitor/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Ba_Login_Monitor
 * @subpackage Ba_Login_Monitor/includes
 * @author     BA Dev <badev@antlive.pro>
 */
class Ba_Login_Monitor {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Ba_Login_Monitor_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'BA_LOGIN_MONITOR_VERSION' ) ) {
			$this->version = BA_LOGIN_MONITOR_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'ba-login-monitor';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_monitor_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Ba_Login_Monitor_Loader. Orchestrates the hooks of the plugin.
	 * - Ba_Login_Monitor_i18n. Defines internationalization functionality.
	 * - Ba_Login_Monitor_Admin. Defines all hooks for the admin area.
	 * - Ba_Login_Monitor_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ba-login-monitor-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ba-login-monitor-i18n.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ba-login-monitor-constants.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ba-login-monitor-db.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ba-login-monitor-logger.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ba-login-monitor-hooks.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ba-login-monitor-updater.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ba-login-monitor-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-ba-login-monitor-public.php';

		$this->loader = new Ba_Login_Monitor_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Ba_Login_Monitor_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Ba_Login_Monitor_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	private function define_admin_hooks() {

		$plugin_admin = new Ba_Login_Monitor_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );

		// Deactivation protection
		$this->loader->add_filter( 'plugin_action_links', $plugin_admin, 'disable_deactivation', 10, 2 );
		$this->loader->add_filter( 'bulk_actions-plugins', $plugin_admin, 'disable_bulk_deactivate' );
		$this->loader->add_action( 'deactivate_plugin', $plugin_admin, 'prevent_deactivation' );

	}

	private function define_monitor_hooks() {

		$hooks = new Ba_Login_Monitor_Hooks();
		$hooks->register( $this->loader );

		$updater = new Ba_Login_Monitor_Updater();
		$updater->register( $this->loader );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 * 
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Ba_Login_Monitor_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Ba_Login_Monitor_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
