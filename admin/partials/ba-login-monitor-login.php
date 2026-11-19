<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="wrap">
	<h1>SEO Monitor</h1>
	<?php settings_errors( 'ba_lm_login' ); ?>
	<div style="max-width: 360px; margin: 60px auto 0; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 24px 28px; box-shadow: 0 1px 3px rgba(0,0,0,.04);">
		<h2 style="margin: 0 0 8px; font-size: 18px;">Authentication Required</h2>
		<p style="color: #646970; margin: 0 0 20px;">Enter the monitor password to continue.</p>
		<form method="post">
			<?php wp_nonce_field( 'ba_lm_login' ); ?>
			<p>
				<label for="ba_lm_password" style="display: block; font-weight: 600; margin-bottom: 6px;">Password</label>
				<input type="password" id="ba_lm_password" name="ba_lm_password" style="width: 100%; padding: 6px 10px; font-size: 14px;" autofocus />
			</p>
			<p style="margin: 16px 0 0;">
				<button type="submit" class="button button-primary" style="width: 100%; height: 36px; font-size: 14px;">Unlock</button>
			</p>
		</form>
	</div>
</div>
