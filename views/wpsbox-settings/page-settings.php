<div class="wrap">
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2><?php esc_html_e( WPSBOX_NAME ); ?> Settings</h2>
	
	<p>In order to display the latest information from the windowsphonestore.com, please set how long you want to keep the cache data.</p>
	
	<form method="post" action="options.php">
		<?php settings_fields( $class::SETTING_SLUG ); ?>
		<?php do_settings_sections( $class::SETTING_SLUG ); ?>

		<?php submit_button(); ?>
	</form>
</div> <!-- .wrap -->