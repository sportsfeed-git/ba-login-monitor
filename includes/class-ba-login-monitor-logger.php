<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Ba_Login_Monitor_Logger {

	private static $geo_cache = null;
	private static $site_id_cache = null;

	public static function log( $event_type, $event_category, $extra = array() ) {
		try {
			$site_id = self::get_site_id();
			if ( ! $site_id ) {
				return;
			}

			$user    = wp_get_current_user();
			$geo     = self::get_geo_ip();
			$ip      = self::get_client_ip();

			$data = array(
				'site_id'        => $site_id,
				'event_type'     => $event_type,
				'event_category' => $event_category,
				'user_id'        => $user->ID ?: ( $extra['user_id'] ?? null ),
				'user_login'     => $user->user_login ?: ( $extra['user_login'] ?? null ),
				'user_role'      => ! empty( $user->roles ) ? $user->roles[0] : ( $extra['user_role'] ?? null ),
				'object_type'    => $extra['object_type'] ?? null,
				'object_name'    => $extra['object_name'] ?? null,
				'object_id'      => $extra['object_id'] ?? null,
				'ip_address'     => $ip,
				'country'        => $geo['country'] ?? null,
				'city'           => $geo['city'] ?? null,
				'timezone'       => $geo['timezone'] ?? null,
				'user_agent'     => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( $_SERVER['HTTP_USER_AGENT'], 0, 500 ) : null,
				'details'        => isset( $extra['details'] ) ? wp_json_encode( $extra['details'] ) : null,
				'created_at'     => current_time( 'mysql', true ),
			);

			Ba_Login_Monitor_DB::insert_log( $data );
		} catch ( \Exception $e ) {
			error_log( '[BA Login Monitor] Logger error: ' . $e->getMessage() );
		}
	}

	private static function get_site_id() {
		if ( self::$site_id_cache !== null ) {
			return self::$site_id_cache;
		}
		self::$site_id_cache = Ba_Login_Monitor_DB::get_site_id();
		return self::$site_id_cache;
	}

	private static function get_geo_ip() {
		if ( self::$geo_cache !== null ) {
			return self::$geo_cache;
		}

		// Try Cloudflare headers first (most accurate, no extra API call)
		$cf_country = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null;
		if ( $cf_country && $cf_country !== 'XX' ) {
			self::$geo_cache = array(
				'country'  => $cf_country,
				'city'     => null,
				'timezone' => null,
			);
			// Still try geo-IP API for city/timezone, but don't block on it
		}

		$ip = self::get_client_ip();
		if ( ! $ip || $ip === '127.0.0.1' || $ip === '::1' ) {
			if ( self::$geo_cache === null ) {
				self::$geo_cache = array();
			}
			return self::$geo_cache;
		}

		$url      = Ba_Login_Monitor_Constants::GEO_IP_URL . '?ip=' . urlencode( $ip );
		$response = wp_remote_get( $url, array(
			'timeout' => 3,
			'headers' => array( 'X-Forwarded-For' => $ip ),
		) );

		if ( is_wp_error( $response ) ) {
			error_log( '[BA Login Monitor] Geo-IP error: ' . $response->get_error_message() );
			if ( self::$geo_cache === null ) {
				self::$geo_cache = array();
			}
			return self::$geo_cache;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $data ) {
			$api_country = $data['countryCode'] ?? $data['country'] ?? null;
			self::$geo_cache = array(
				'country'  => $cf_country ?: $api_country,
				'city'     => $data['city'] ?? null,
				'timezone' => $data['timezone'] ?? null,
			);
		} elseif ( self::$geo_cache === null ) {
			self::$geo_cache = array();
		}

		return self::$geo_cache;
	}

	private static function get_client_ip() {
		$headers = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = $_SERVER[ $header ];
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return $_SERVER['REMOTE_ADDR'] ?? null;
	}
}
