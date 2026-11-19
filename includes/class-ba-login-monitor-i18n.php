<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://antlive.pro
 * @since      1.0.0
 *
 * @package    Ba_Login_Monitor
 * @subpackage Ba_Login_Monitor/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Ba_Login_Monitor
 * @subpackage Ba_Login_Monitor/includes
 * @author     BA Dev <badev@antlive.pro>
 */
class Ba_Login_Monitor_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'ba-login-monitor',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
