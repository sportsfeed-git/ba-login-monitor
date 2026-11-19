<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="wrap">
	<h1>
		Activity Log
		<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'options-general.php?page=ba-login-monitor&ba_lm_logout=1' ), 'ba_lm_logout' ) ); ?>" class="page-title-action">Lock</a>
	</h1>

	<?php if ( ! $connected ) : ?>
		<div class="notice notice-error"><p>External database is not connected. Check your <a href="<?php echo esc_url( admin_url( 'admin.php?page=ba-login-monitor' ) ); ?>">connection settings</a>.</p></div>
	<?php elseif ( empty( $logs ) ) : ?>
		<p>No activity logs found.</p>
	<?php else : ?>
		<table class="widefat striped" style="margin-top: 16px;">
			<thead>
				<tr>
					<th>Time (UTC)</th>
					<th>Site</th>
					<th>Event</th>
					<th>Category</th>
					<th>User</th>
					<th>Object</th>
					<th>IP Address</th>
					<th>Location</th>
					<th>Details</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $logs as $log ) : ?>
				<tr>
					<td style="white-space: nowrap;"><?php echo esc_html( $log['created_at'] ); ?></td>
					<td style="white-space: nowrap;"><?php echo esc_html( $log['site_name'] ?? ( '#' . $log['site_id'] ) ); ?></td>
					<td><code><?php echo esc_html( $log['event_type'] ); ?></code></td>
					<td><?php echo esc_html( $log['event_category'] ); ?></td>
					<td><?php echo esc_html( $log['user_login'] ?: '-' ); ?></td>
					<td><?php echo esc_html( $log['object_name'] ?: '-' ); ?></td>
					<td style="white-space: nowrap;"><?php echo esc_html( $log['ip_address'] ?: '-' ); ?></td>
					<td>
						<?php
						$location = array_filter( array( $log['city'], $log['country'] ) );
						echo esc_html( $location ? implode( ', ', $location ) : '-' );
						?>
					</td>
					<td>
						<?php if ( ! empty( $log['details'] ) ) : ?>
							<code style="font-size: 11px; word-break: break-all;"><?php echo esc_html( $log['details'] ); ?></code>
						<?php else : ?>
							-
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description" style="margin-top: 12px;">Showing last 50 entries for this site.</p>
	<?php endif; ?>
</div>
