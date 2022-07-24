<?php
/* ------------------------------------------------------- */
/* Icon
/* ------------------------------------------------------- */
if ( ! function_exists( 'goso_icon_shortcode' ) ) {
	function goso_icon_shortcode( $atts, $content ) {
		extract( shortcode_atts( array(
			'name' => '',
		), $atts ) );

		$return = '';
		if ( $name ) {
			$return .=  function_exists( 'goso_fawesome_icon' ) ? goso_fawesome_icon( 'fa fa-' . $name ) : '<i class="fa fa-' . $name . '"></i>';
		}

		return $return;
	}
}