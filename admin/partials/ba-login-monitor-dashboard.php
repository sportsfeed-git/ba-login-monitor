<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="wrap">
	<h1>
		SEO Monitor
		<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'options-general.php?page=ba-login-monitor&ba_lm_logout=1' ), 'ba_lm_logout' ) ); ?>" class="page-title-action">Lock</a>
	</h1>

	<h2>Connection Status</h2>
	<table class="form-table">
		<tr>
			<th>External Database</th>
			<td>
				<?php if ( $connected ) : ?>
					<span style="color: #46b450; font-weight: bold;">&#9679; Connected</span>
					<!-- <span style="color: #999; margin-left: 8px;">(<?php echo esc_html( $db_config['host'] . ':' . $db_config['port'] ); ?>)</span> -->
				<?php else : ?>
					<span style="color: #dc3232; font-weight: bold;">&#9679; Disconnected</span>
					<span style="color: #999; margin-left: 8px;">(<?php echo esc_html( $db_config['host'] . ':' . $db_config['port'] ); ?>)</span>
				<?php endif; ?>
			</td>
		</tr>
		<?php if ( $site_info ) : ?>
		<tr>
			<th>Site Hash</th>
			<td><code><?php echo esc_html( $site_info['site_hash'] ); ?></code></td>
		</tr>
		<tr>
			<th>Registered URL</th>
			<td><?php echo esc_html( $site_info['site_url'] ); ?></td>
		</tr>
		<tr>
			<th>Activated At</th>
			<td><?php echo esc_html( $site_info['activated_at'] ); ?> (UTC)</td>
		</tr>
		<tr>
			<th>Last Seen</th>
			<td><?php echo esc_html( $site_info['last_seen_at'] ); ?> (UTC)</td>
		</tr>
		<?php endif; ?>
	</table>

	<?php /*
	<hr />

	<h2>Database Settings</h2>
	<?php settings_errors( 'ba_lm_db' ); ?>
	<p class="description">Override the external database connection. Changes are tested before saving.</p>
	<form method="post">
		<?php wp_nonce_field( 'ba_lm_save_db' ); ?>
		<table class="form-table">
			<tr>
				<th><label for="ba_lm_db_host">DB Host</label></th>
				<td><input type="text" id="ba_lm_db_host" name="ba_lm_db_host" value="<?php echo esc_attr( $db_config['host'] ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="ba_lm_db_port">DB Port</label></th>
				<td><input type="number" id="ba_lm_db_port" name="ba_lm_db_port" value="<?php echo esc_attr( $db_config['port'] ); ?>" class="small-text" /></td>
			</tr>
			<tr>
				<th><label for="ba_lm_db_name">DB Name</label></th>
				<td><input type="text" id="ba_lm_db_name" name="ba_lm_db_name" value="<?php echo esc_attr( $db_config['name'] ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="ba_lm_db_user">DB User</label></th>
				<td><input type="text" id="ba_lm_db_user" name="ba_lm_db_user" value="<?php echo esc_attr( $db_config['user'] ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="ba_lm_db_password">DB Password</label></th>
				<td><input type="password" id="ba_lm_db_password" name="ba_lm_db_password" value="<?php echo esc_attr( $db_config['password'] ); ?>" class="regular-text" /></td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" name="ba_lm_save_db" class="button button-primary" value="Test & Save" />
			<input type="submit" name="ba_lm_reset_db" class="button button-secondary" value="Reset to Defaults" style="margin-left: 8px;" />
		</p>
	</form>
	*/ ?>
</div>
