<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://antlive.pro
 * @since             1.0.0
 * @package           Ba_Login_Monitor
 *
 * @wordpress-plugin
 * Plugin Name:       SEO Monitor
 * Plugin URI:        https://antlive.pro
 * Description:       SEO monitoring and site health checks.
 * Version:           1.2.11
 * Author:            BA Dev
 * Author URI:        https://antlive.pro/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ba-login-monitor
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'BA_LOGIN_MONITOR_VERSION', '1.2.11' );

require_once plugin_dir_path( __FILE__ ) . 'includes/ba-agent.php';

if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ba-login-monitor-activator.php
 */
function activate_ba_login_monitor() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ba-login-monitor-activator.php';
	Ba_Login_Monitor_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ba-login-monitor-deactivator.php
 */
function deactivate_ba_login_monitor() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ba-login-monitor-deactivator.php';
	Ba_Login_Monitor_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ba_login_monitor' );
register_deactivation_hook( __FILE__, 'deactivate_ba_login_monitor' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ba-login-monitor.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_ba_login_monitor() {

	$plugin = new Ba_Login_Monitor();
	$plugin->run();

}
run_ba_login_monitor();
