<?php
/* ------------------------------------------------------- */
/* Date
/* ------------------------------------------------------- */
if ( ! function_exists( 'goso_goso_date_shortcode' ) ) {
	function goso_goso_date_shortcode( $atts, $content ) {
		extract( shortcode_atts( array(
			'format' => 'l, F j Y'
		), $atts ) );

		$return = '';
		if( ! $format ): $format = 'l, F j Y'; endif;
		
		if( function_exists( 'wp_date' ) ){
			$return = wp_date( $format );
		}

		return $return;
	}
}