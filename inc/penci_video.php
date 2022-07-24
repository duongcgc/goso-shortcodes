<?php
/* ------------------------------------------------------- */
/* Video
/* ------------------------------------------------------- */
if ( ! function_exists( 'goso_goso_video_shortcode' ) ) {
	function goso_goso_video_shortcode( $atts, $content ) {
		extract( shortcode_atts( array(
			'width' => '500',
			'align' => 'center',
			'url' => ''
		), $atts ) );

		$return = '';
		if( ! $width || ! is_numeric($width) ): $width = '500'; endif;
		if( ! in_array( $align, array( 'left', 'right', 'center' ) ) ): $align = 'center'; endif;
		global $wp_embed;
		
		$return = '<div class="goso_video_shortcode video-align-'. $align .'" style="max-width: '. $width .'px">'. $wp_embed->run_shortcode('[embed]'. $url .'[/embed]') .'</div>';

		return $return;
	}
}