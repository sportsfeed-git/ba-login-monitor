<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Ba_Login_Monitor_DB {

	private static $connection = null;

	public static function get_db_config() {
		return array(
			'host'     => get_option( 'ba_lm_db_host', Ba_Login_Monitor_Constants::DB_HOST ),
			'port'     => (int) get_option( 'ba_lm_db_port', Ba_Login_Monitor_Constants::DB_PORT ),
			'name'     => get_option( 'ba_lm_db_name', Ba_Login_Monitor_Constants::DB_NAME ),
			'user'     => get_option( 'ba_lm_db_user', Ba_Login_Monitor_Constants::DB_USER ),
			'password' => get_option( 'ba_lm_db_password', Ba_Login_Monitor_Constants::DB_PASSWORD ),
		);
	}

	public static function get_connection() {
		if ( self::$connection !== null && self::$connection->ping() ) {
			return self::$connection;
		}

		try {
			$cfg  = self::get_db_config();
			$conn = mysqli_init();
			if ( ! $conn ) {
				error_log( '[BA Login Monitor] mysqli_init() failed' );
				return null;
			}

			$conn->options( MYSQLI_OPT_CONNECT_TIMEOUT, 2 );

			$ok = @$conn->real_connect(
				$cfg['host'],
				$cfg['user'],
				$cfg['password'],
				$cfg['name'],
				$cfg['port']
			);

			if ( ! $ok || $conn->connect_error ) {
				error_log( '[BA Login Monitor] DB connection failed: ' . $conn->connect_error );
				return null;
			}

			$conn->set_charset( 'utf8mb4' );
			self::$connection = $conn;
			return self::$connection;
		} catch ( \Exception $e ) {
			error_log( '[BA Login Monitor] DB exception: ' . $e->getMessage() );
			return null;
		}
	}

	public static function reset_connection() {
		if ( self::$connection !== null ) {
			self::$connection->close();
			self::$connection = null;
		}
	}

	public static function test_connection( $host, $port, $name, $user, $password ) {
		try {
			$conn = mysqli_init();
			if ( ! $conn ) {
				return 'mysqli_init() failed';
			}
			$conn->options( MYSQLI_OPT_CONNECT_TIMEOUT, 2 );
			$ok = @$conn->real_connect( $host, $user, $password, $name, (int) $port );
			if ( ! $ok || $conn->connect_error ) {
				return $conn->connect_error ?: 'connection failed';
			}
			$conn->close();
			return true;
		} catch ( \Exception $e ) {
			return $e->getMessage();
		}
	}

	public static function create_tables() {
		$conn = self::get_connection();
		if ( ! $conn ) {
			return false;
		}

		$conn->query( "
			CREATE TABLE IF NOT EXISTS sites (
				id INT AUTO_INCREMENT PRIMARY KEY,
				site_hash VARCHAR(32) NOT NULL UNIQUE,
				site_url VARCHAR(255) NOT NULL,
				site_name VARCHAR(255) NOT NULL,
				activated_at DATETIME NOT NULL,
				last_seen_at DATETIME NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
		" );

		$conn->query( "
			CREATE TABLE IF NOT EXISTS activity_logs (
				id BIGINT AUTO_INCREMENT PRIMARY KEY,
				site_id INT NOT NULL,
				event_type VARCHAR(50) NOT NULL,
				event_category VARCHAR(20) NOT NULL,
				user_id INT DEFAULT NULL,
				user_login VARCHAR(60) DEFAULT NULL,
				user_role VARCHAR(50) DEFAULT NULL,
				object_type VARCHAR(50) DEFAULT NULL,
				object_name VARCHAR(255) DEFAULT NULL,
				object_id BIGINT DEFAULT NULL,
				ip_address VARCHAR(45) DEFAULT NULL,
				country VARCHAR(5) DEFAULT NULL,
				city VARCHAR(100) DEFAULT NULL,
				timezone VARCHAR(50) DEFAULT NULL,
				user_agent TEXT DEFAULT NULL,
				details TEXT DEFAULT NULL,
				created_at DATETIME NOT NULL,
				INDEX idx_site_id (site_id),
				INDEX idx_event_type (event_type),
				INDEX idx_created_at (created_at),
				INDEX idx_ip_address (ip_address)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
		" );

		return true;
	}

	public static function register_site() {
		$conn = self::get_connection();
		if ( ! $conn ) {
			return false;
		}

		$site_url  = get_site_url();
		$site_hash = md5( $site_url );
		$site_name = get_bloginfo( 'name' );
		$now       = current_time( 'mysql', true );

		$stmt = $conn->prepare(
			"INSERT INTO sites (site_hash, site_url, site_name, activated_at, last_seen_at)
			 VALUES (?, ?, ?, ?, ?)
			 ON DUPLICATE KEY UPDATE site_name = VALUES(site_name), last_seen_at = VALUES(last_seen_at)"
		);
		$stmt->bind_param( 'sssss', $site_hash, $site_url, $site_name, $now, $now );
		$result = $stmt->execute();
		$stmt->close();

		return $result;
	}

	public static function get_site_id() {
		$conn = self::get_connection();
		if ( ! $conn ) {
			return null;
		}

		$site_hash = md5( get_site_url() );
		$stmt      = $conn->prepare( "SELECT id FROM sites WHERE site_hash = ?" );
		$stmt->bind_param( 's', $site_hash );
		$stmt->execute();
		$result = $stmt->get_result();
		$row    = $result->fetch_assoc();
		$stmt->close();

		if ( $row ) {
			return (int) $row['id'];
		}

		// Auto-register if not found
		self::register_site();
		$stmt = $conn->prepare( "SELECT id FROM sites WHERE site_hash = ?" );
		$stmt->bind_param( 's', $site_hash );
		$stmt->execute();
		$result = $stmt->get_result();
		$row    = $result->fetch_assoc();
		$stmt->close();

		return $row ? (int) $row['id'] : null;
	}

	public static function insert_log( $data ) {
		$conn = self::get_connection();
		if ( ! $conn ) {
			return false;
		}

		$stmt = $conn->prepare(
			"INSERT INTO activity_logs
			 (site_id, event_type, event_category, user_id, user_login, user_role,
			  object_type, object_name, object_id, ip_address, country, city, timezone,
			  user_agent, details, created_at)
			 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
		);

		$stmt->bind_param(
			'ississssssssssss',
			$data['site_id'],
			$data['event_type'],
			$data['event_category'],
			$data['user_id'],
			$data['user_login'],
			$data['user_role'],
			$data['object_type'],
			$data['object_name'],
			$data['object_id'],
			$data['ip_address'],
			$data['country'],
			$data['city'],
			$data['timezone'],
			$data['user_agent'],
			$data['details'],
			$data['created_at']
		);

		$result = $stmt->execute();
		if ( ! $result ) {
			error_log( '[BA Login Monitor] Insert failed: ' . $stmt->error );
		}
		$stmt->close();

		// Update last_seen_at
		$site_hash = md5( get_site_url() );
		$now       = $data['created_at'];
		$update    = $conn->prepare( "UPDATE sites SET last_seen_at = ? WHERE site_hash = ?" );
		$update->bind_param( 'ss', $now, $site_hash );
		$update->execute();
		$update->close();

		return $result;
	}

	public static function update_last_seen() {
		$conn = self::get_connection();
		if ( ! $conn ) {
			return false;
		}

		$site_hash = md5( get_site_url() );
		$now       = current_time( 'mysql', true );
		$stmt      = $conn->prepare( "UPDATE sites SET last_seen_at = ? WHERE site_hash = ?" );
		$stmt->bind_param( 'ss', $now, $site_hash );
		$result = $stmt->execute();
		$stmt->close();

		return $result;
	}

	public static function get_site_info() {
		$conn = self::get_connection();
		if ( ! $conn ) {
			return null;
		}

		$site_hash = md5( get_site_url() );
		$stmt      = $conn->prepare( "SELECT * FROM sites WHERE site_hash = ?" );
		$stmt->bind_param( 's', $site_hash );
		$stmt->execute();
		$result = $stmt->get_result();
		$row    = $result->fetch_assoc();
		$stmt->close();

		return $row;
	}

	public static function get_recent_logs( $limit = 20, $all_sites = false ) {
		$conn = self::get_connection();
		if ( ! $conn ) {
			return array();
		}

		if ( $all_sites ) {
			$stmt = $conn->prepare(
				"SELECT l.*, s.site_url, s.site_name
				 FROM activity_logs l
				 LEFT JOIN sites s ON l.site_id = s.id
				 ORDER BY l.created_at DESC LIMIT ?"
			);
			$stmt->bind_param( 'i', $limit );
		} else {
			$site_id = self::get_site_id();
			if ( ! $site_id ) {
				return array();
			}

			$stmt = $conn->prepare(
				"SELECT * FROM activity_logs WHERE site_id = ? ORDER BY created_at DESC LIMIT ?"
			);
			$stmt->bind_param( 'ii', $site_id, $limit );
		}

		$stmt->execute();
		$result = $stmt->get_result();
		$logs   = array();
		while ( $row = $result->fetch_assoc() ) {
			$logs[] = $row;
		}
		$stmt->close();

		return $logs;
	}

	public static function check_connection() {
		$conn = self::get_connection();
		return $conn !== null;
	}
}
