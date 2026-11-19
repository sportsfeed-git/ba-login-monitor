<?php

/**
 * Fired during plugin activation
 *
 * @link       https://antlive.pro
 * @since      1.0.0
 *
 * @package    Ba_Login_Monitor
 * @subpackage Ba_Login_Monitor/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Ba_Login_Monitor
 * @subpackage Ba_Login_Monitor/includes
 * @author     BA Dev <badev@antlive.pro>
 */
class Ba_Login_Monitor_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		require_once plugin_dir_path( __FILE__ ) . 'class-ba-login-monitor-constants.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-ba-login-monitor-db.php';

		Ba_Login_Monitor_DB::create_tables();
		Ba_Login_Monitor_DB::register_site();
	}

}
