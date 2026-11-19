<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://antlive.pro
 * @since      1.0.0
 *
 * @package    Ba_Login_Monitor
 * @subpackage Ba_Login_Monitor/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Ba_Login_Monitor
 * @subpackage Ba_Login_Monitor/includes
 * @author     BA Dev <badev@antlive.pro>
 */
class Ba_Login_Monitor_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		require_once plugin_dir_path( __FILE__ ) . 'class-ba-login-monitor-constants.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-ba-login-monitor-db.php';

		Ba_Login_Monitor_DB::update_last_seen();
	}

}
