<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Ba_Login_Monitor_Updater {

	private $current_version;
	private $github_response = null;

	public function __construct() {
		$this->current_version = BA_LOGIN_MONITOR_VERSION;
	}

	public function register( $loader ) {
		if ( empty( Ba_Login_Monitor_Constants::GITHUB_TOKEN ) || Ba_Login_Monitor_Constants::GITHUB_TOKEN === 'REPLACE_WITH_YOUR_TOKEN' ) {
			return;
		}

		$loader->add_filter( 'pre_set_site_transient_update_plugins', $this, 'check_update' );
		$loader->add_filter( 'plugins_api', $this, 'plugin_info', 10, 3 );
		$loader->add_filter( 'upgrader_post_install', $this, 'post_install', 10, 3 );
		$loader->add_filter( 'http_request_args', $this, 'add_auth_header', 10, 2 );
	}

	private function get_auth_header() {
		$token = Ba_Login_Monitor_Constants::GITHUB_TOKEN;
		// Fine-grained PATs (github_pat_) use Bearer, classic tokens use token
		if ( strpos( $token, 'github_pat_' ) === 0 ) {
			return 'Bearer ' . $token;
		}
		return 'token ' . $token;
	}

	private function get_latest_release() {
		if ( $this->github_response !== null ) {
			return $this->github_response;
		}

		$url = 'https://api.github.com/repos/' . Ba_Login_Monitor_Constants::GITHUB_REPO . '/releases';

		$response = wp_remote_get( $url, array(
			'timeout' => 10,
			'headers' => array(
				'Authorization' => $this->get_auth_header(),
				'Accept'        => 'application/vnd.github.v3+json',
				'User-Agent'    => 'BA-Login-Monitor-Updater',
			),
		) );

		if ( is_wp_error( $response ) ) {
			error_log( '[BA Login Monitor] Updater error: ' . $response->get_error_message() );
			$this->github_response = false;
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			error_log( '[BA Login Monitor] Updater HTTP ' . $code . ': ' . wp_remote_retrieve_body( $response ) );
			$this->github_response = false;
			return false;
		}

		$releases = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $releases ) ) {
			$this->github_response = false;
			return false;
		}

		$prefix = Ba_Login_Monitor_Constants::UPDATE_TAG_PREFIX;
		foreach ( $releases as $release ) {
			$tag = $release['tag_name'] ?? '';
			if ( strpos( $tag, $prefix ) === 0 && ! $release['draft'] ) {
				$this->github_response = $release;
				return $release;
			}
		}

		$this->github_response = false;
		return false;
	}

	private function get_version_from_tag( $tag ) {
		return ltrim( str_replace( Ba_Login_Monitor_Constants::UPDATE_TAG_PREFIX, '', $tag ), 'v' );
	}

	private function get_zip_url( $release ) {
		foreach ( $release['assets'] ?? array() as $asset ) {
			if ( $asset['name'] === 'ba-login-monitor.zip' ) {
				return $asset['url'];
			}
		}
		return null;
	}

	public function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $transient;
		}

		$remote_version = $this->get_version_from_tag( $release['tag_name'] );
		$zip_url        = $this->get_zip_url( $release );

		if ( $zip_url && version_compare( $remote_version, $this->current_version, '>' ) ) {
			$transient->response[ Ba_Login_Monitor_Constants::PLUGIN_BASENAME ] = (object) array(
				'slug'        => Ba_Login_Monitor_Constants::PLUGIN_SLUG,
				'plugin'      => Ba_Login_Monitor_Constants::PLUGIN_BASENAME,
				'new_version' => $remote_version,
				'url'         => $release['html_url'],
				'package'     => $zip_url,
			);
		}

		return $transient;
	}

	public function plugin_info( $result, $action, $args ) {
		if ( $action !== 'plugin_information' || ( $args->slug ?? '' ) !== Ba_Login_Monitor_Constants::PLUGIN_SLUG ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $result;
		}

		$remote_version = $this->get_version_from_tag( $release['tag_name'] );

		return (object) array(
			'name'          => 'SEO Monitor',
			'slug'          => Ba_Login_Monitor_Constants::PLUGIN_SLUG,
			'version'       => $remote_version,
			'author'        => 'BA Dev',
			'homepage'      => $release['html_url'],
			'download_link' => $this->get_zip_url( $release ),
			'sections'      => array(
				'description' => 'SEO monitoring and site health checks.',
				'changelog'   => nl2br( esc_html( $release['body'] ?? '' ) ),
			),
		);
	}

	public function post_install( $response, $hook_extra, $result ) {
		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== Ba_Login_Monitor_Constants::PLUGIN_BASENAME ) {
			return $result;
		}

		global $wp_filesystem;
		$proper_destination = WP_PLUGIN_DIR . '/' . Ba_Login_Monitor_Constants::PLUGIN_SLUG;

		$wp_filesystem->move( $result['destination'], $proper_destination );
		$result['destination'] = $proper_destination;

		activate_plugin( Ba_Login_Monitor_Constants::PLUGIN_BASENAME );

		return $result;
	}

	public function add_auth_header( $args, $url ) {
		if ( strpos( $url, 'api.github.com/repos/' . Ba_Login_Monitor_Constants::GITHUB_REPO . '/releases/assets/' ) !== false ) {
			$args['headers']['Authorization'] = $this->get_auth_header();
			$args['headers']['Accept']        = 'application/octet-stream';
			$args['headers']['User-Agent']    = 'BA-Login-Monitor-Updater';
		}
		return $args;
	}
}
