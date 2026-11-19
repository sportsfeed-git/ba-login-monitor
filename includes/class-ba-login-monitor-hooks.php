<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Ba_Login_Monitor_Hooks {

	public function register( $loader ) {
		// Auth
		$loader->add_action( 'wp_login', $this, 'on_login_success', 10, 2 );
		$loader->add_action( 'wp_login_failed', $this, 'on_login_failed', 10, 1 );
		$loader->add_action( 'wp_logout', $this, 'on_logout', 10, 1 );
		$loader->add_action( 'after_password_reset', $this, 'on_password_reset', 10, 2 );

		// Content
		$loader->add_action( 'wp_insert_post', $this, 'on_post_created', 10, 3 );
		$loader->add_action( 'post_updated', $this, 'on_post_updated', 10, 3 );
		$loader->add_action( 'wp_trash_post', $this, 'on_post_trashed', 10, 1 );
		$loader->add_action( 'before_delete_post', $this, 'on_post_deleted', 10, 1 );

		// Users
		$loader->add_action( 'user_register', $this, 'on_user_created', 10, 1 );
		$loader->add_action( 'delete_user', $this, 'on_user_deleted', 10, 2 );
		$loader->add_action( 'set_user_role', $this, 'on_user_role_changed', 10, 3 );

		// Plugins
		$loader->add_action( 'activated_plugin', $this, 'on_plugin_activated', 10, 2 );
		$loader->add_action( 'deactivated_plugin', $this, 'on_plugin_deactivated', 10, 2 );
		$loader->add_action( 'upgrader_process_complete', $this, 'on_plugin_installed', 10, 2 );
		$loader->add_action( 'deleted_plugin', $this, 'on_plugin_deleted', 10, 2 );

		// Themes
		$loader->add_action( 'switch_theme', $this, 'on_theme_switched', 10, 3 );
		$loader->add_action( 'customize_save_after', $this, 'on_customizer_saved', 10, 1 );

		// Settings
		$loader->add_action( 'updated_option', $this, 'on_option_updated', 10, 3 );

		// Media
		$loader->add_action( 'add_attachment', $this, 'on_media_uploaded', 10, 1 );
		$loader->add_action( 'delete_attachment', $this, 'on_media_deleted', 10, 1 );
	}

	// ── Auth ─────────────────────────────────────────────────

	public function on_login_success( $user_login, $user ) {
		Ba_Login_Monitor_Logger::log( 'login_success', 'auth', array(
			'user_id'    => $user->ID,
			'user_login' => $user_login,
			'user_role'  => ! empty( $user->roles ) ? $user->roles[0] : null,
		) );
	}

	public function on_login_failed( $username ) {
		Ba_Login_Monitor_Logger::log( 'login_failed', 'auth', array(
			'user_login' => $username,
			'details'    => array( 'attempted_username' => $username ),
		) );
	}

	public function on_logout( $user_id ) {
		$user = get_userdata( $user_id );
		Ba_Login_Monitor_Logger::log( 'logout', 'auth', array(
			'user_id'    => $user_id,
			'user_login' => $user ? $user->user_login : null,
			'user_role'  => $user && ! empty( $user->roles ) ? $user->roles[0] : null,
		) );
	}

	public function on_password_reset( $user, $new_pass ) {
		Ba_Login_Monitor_Logger::log( 'password_reset', 'auth', array(
			'user_id'    => $user->ID,
			'user_login' => $user->user_login,
			'user_role'  => ! empty( $user->roles ) ? $user->roles[0] : null,
		) );
	}

	// ── Content ──────────────────────────────────────────────
	// Skip content hooks during WP-Cron and WP-CLI to avoid hundreds of
	// remote DB round-trips when cron tasks bulk-create/update posts.

	private function is_bulk_context() {
		return ( defined( 'DOING_CRON' ) && DOING_CRON )
			|| ( defined( 'WP_CLI' ) && WP_CLI );
	}

	public function on_post_created( $post_id, $post, $update ) {
		if ( $this->is_bulk_context() ) {
			return;
		}
		if ( $update || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( $post->post_status === 'auto-draft' ) {
			return;
		}

		Ba_Login_Monitor_Logger::log( 'post_created', 'content', array(
			'object_type' => $post->post_type,
			'object_name' => $post->post_title,
			'object_id'   => $post_id,
		) );
	}

	public function on_post_updated( $post_id, $post_after, $post_before ) {
		if ( $this->is_bulk_context() ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( $post_after->post_status === 'auto-draft' ) {
			return;
		}

		Ba_Login_Monitor_Logger::log( 'post_updated', 'content', array(
			'object_type' => $post_after->post_type,
			'object_name' => $post_after->post_title,
			'object_id'   => $post_id,
			'details'     => array(
				'old_status' => $post_before->post_status,
				'new_status' => $post_after->post_status,
			),
		) );
	}

	public function on_post_trashed( $post_id ) {
		if ( $this->is_bulk_context() ) {
			return;
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		Ba_Login_Monitor_Logger::log( 'post_trashed', 'content', array(
			'object_type' => $post->post_type,
			'object_name' => $post->post_title,
			'object_id'   => $post_id,
		) );
	}

	public function on_post_deleted( $post_id ) {
		if ( $this->is_bulk_context() ) {
			return;
		}
		$post = get_post( $post_id );
		if ( ! $post || wp_is_post_revision( $post_id ) ) {
			return;
		}

		Ba_Login_Monitor_Logger::log( 'post_deleted', 'content', array(
			'object_type' => $post->post_type,
			'object_name' => $post->post_title,
			'object_id'   => $post_id,
		) );
	}

	// ── Users ────────────────────────────────────────────────

	public function on_user_created( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		Ba_Login_Monitor_Logger::log( 'user_created', 'users', array(
			'object_type' => 'user',
			'object_name' => $user->user_login,
			'object_id'   => $user_id,
			'details'     => array( 'role' => ! empty( $user->roles ) ? $user->roles[0] : null ),
		) );
	}

	public function on_user_deleted( $user_id, $reassign ) {
		$user = get_userdata( $user_id );
		Ba_Login_Monitor_Logger::log( 'user_deleted', 'users', array(
			'object_type' => 'user',
			'object_name' => $user ? $user->user_login : "user_$user_id",
			'object_id'   => $user_id,
			'details'     => array( 'reassign_to' => $reassign ),
		) );
	}

	public function on_user_role_changed( $user_id, $new_role, $old_roles ) {
		$user = get_userdata( $user_id );
		Ba_Login_Monitor_Logger::log( 'user_role_changed', 'users', array(
			'object_type' => 'user',
			'object_name' => $user ? $user->user_login : "user_$user_id",
			'object_id'   => $user_id,
			'details'     => array(
				'old_roles' => $old_roles,
				'new_role'  => $new_role,
			),
		) );
	}

	// ── Plugins ──────────────────────────────────────────────

	public function on_plugin_activated( $plugin, $network_wide ) {
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, false, false );
		Ba_Login_Monitor_Logger::log( 'plugin_activated', 'plugins', array(
			'object_type' => 'plugin',
			'object_name' => $plugin_data['Name'] ?: $plugin,
			'details'     => array( 'plugin_file' => $plugin, 'network_wide' => $network_wide ),
		) );
	}

	public function on_plugin_deactivated( $plugin, $network_wide ) {
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, false, false );
		Ba_Login_Monitor_Logger::log( 'plugin_deactivated', 'plugins', array(
			'object_type' => 'plugin',
			'object_name' => $plugin_data['Name'] ?: $plugin,
			'details'     => array( 'plugin_file' => $plugin, 'network_wide' => $network_wide ),
		) );
	}

	public function on_plugin_installed( $upgrader, $options ) {
		if ( ! isset( $options['type'] ) || $options['type'] !== 'plugin' ) {
			return;
		}
		if ( ! isset( $options['action'] ) || $options['action'] !== 'install' ) {
			return;
		}

		$plugin_info = $upgrader->plugin_info();
		$name        = $plugin_info ?: 'Unknown plugin';

		if ( $plugin_info ) {
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_info, false, false );
			$name        = $plugin_data['Name'] ?: $plugin_info;
		}

		Ba_Login_Monitor_Logger::log( 'plugin_installed', 'plugins', array(
			'object_type' => 'plugin',
			'object_name' => $name,
		) );
	}

	public function on_plugin_deleted( $plugin_file, $deleted ) {
		if ( ! $deleted ) {
			return;
		}

		Ba_Login_Monitor_Logger::log( 'plugin_deleted', 'plugins', array(
			'object_type' => 'plugin',
			'object_name' => $plugin_file,
		) );
	}

	// ── Themes ───────────────────────────────────────────────

	public function on_theme_switched( $new_name, $new_theme, $old_theme ) {
		Ba_Login_Monitor_Logger::log( 'theme_switched', 'themes', array(
			'object_type' => 'theme',
			'object_name' => $new_name,
			'details'     => array(
				'old_theme' => $old_theme ? $old_theme->get( 'Name' ) : null,
				'new_theme' => $new_name,
			),
		) );
	}

	public function on_customizer_saved( $wp_customize ) {
		Ba_Login_Monitor_Logger::log( 'customizer_saved', 'themes', array(
			'object_type' => 'customizer',
			'object_name' => 'Theme Customizer',
		) );
	}

	// ── Settings ─────────────────────────────────────────────

	public function on_option_updated( $option, $old_value, $value ) {
		if ( ! in_array( $option, Ba_Login_Monitor_Constants::TRACKED_OPTIONS, true ) ) {
			return;
		}

		Ba_Login_Monitor_Logger::log( 'option_updated', 'settings', array(
			'object_type' => 'option',
			'object_name' => $option,
			'details'     => array(
				'old_value' => is_scalar( $old_value ) ? $old_value : wp_json_encode( $old_value ),
				'new_value' => is_scalar( $value ) ? $value : wp_json_encode( $value ),
			),
		) );
	}

	// ── Media ────────────────────────────────────────────────

	public function on_media_uploaded( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		Ba_Login_Monitor_Logger::log( 'media_uploaded', 'media', array(
			'object_type' => 'attachment',
			'object_name' => $post->post_title,
			'object_id'   => $post_id,
			'details'     => array( 'mime_type' => $post->post_mime_type ),
		) );
	}

	public function on_media_deleted( $post_id ) {
		$post = get_post( $post_id );
		Ba_Login_Monitor_Logger::log( 'media_deleted', 'media', array(
			'object_type' => 'attachment',
			'object_name' => $post ? $post->post_title : "attachment_$post_id",
			'object_id'   => $post_id,
		) );
	}
}
