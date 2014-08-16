<input
	type="text"
	id="<?php echo esc_attr( $class::SETTING_PREFIX . $setting ); ?>"
	name="<?php echo esc_attr( WPSBox::PREFIX ); ?>settings[<?php echo esc_attr( $class ); ?>][<?php echo esc_attr( $setting ); ?>] ); ?>"
	class="regular-text"
	value="<?php echo esc_attr( $class::get_instance()->settings[ $class ][ $setting ] ); ?>"
/>