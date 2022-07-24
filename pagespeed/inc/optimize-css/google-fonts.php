<?php

namespace Authow\PageSpeed\OptimizeCss;

/**
 * Add a few Google Font optimizations.
 *
 * @author  asadkn
 * @modified gosodesign
 * @since   1.0.0
 */
class GoogleFonts {
	public $active = false;
	protected $renders = [];

	public function enable() {
		$this->active = true;
	}

	/**
	 * Late processing and injecting data in HTML DOM.
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public function render_in_dom( $html ) {
		if ( $this->renders ) {
			$html = str_replace( '<head>', '<head>' . implode( '', $this->renders ), $html );
		}

		$html = $this->add_hints( $html );

		return $html;
	}

	/**
	 * Add resource hints for preconnect and so on.
	 *
	 * @param string $html Full DOM HTML.
	 *
	 * @return void
	 */
	public function add_hints( $html ) {
		if ( ! $this->active || ! get_theme_mod( 'goso_speed_optimize_gfonts' ) ) {
			return $html;
		}

		// preconnect with dns-prefetch fallback for Firefox. Due to safari bug, can't be in same rel.
		$hint = '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />';
		$hint .= '<link rel="dns-prefetch" href="https://fonts.gstatic.com" />';

		// Only needed if not inlined.
		if ( ! get_theme_mod( 'goso_speed_optimize_css' ) || ! get_theme_mod( 'goso_speed_optimize_gfonts_inline' ) ) {
			$hint .= '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin />';
			$hint .= '<link rel="dns-prefetch" href="https://fonts.googleapis.com" />';
		}

		// Add in to head.
		$html = str_replace( '<head>', '<head>' . $hint, $html );

		// No longer needed as preconnect's better and we always want to use https.
		$html = str_replace( "<link rel='dns-prefetch' href='//fonts.googleapis.com' />", '', $html );

		return $html;
	}

	public function optimize( Stylesheet $sheet ) {
		if ( ! $sheet->is_google_fonts() ) {
			return;
		}

		if ( get_theme_mod('goso_speed_optimize_gfonts_delay')) {
			$sheet->delay_type = 'preload';
			return;
		}

		// Set normal render if optimizations are disabled.
		if ( ! get_theme_mod( 'goso_speed_optimize_gfonts' ) ) {
			$sheet->render_type = 'normal';

			return;
		}

		$sheet->delay_type = 'preload';
		if ( strpos( $sheet->url, 'display=' ) === false ) {
			$sheet->url .= '&display=swap';
		}
	}

	public function do_render( Stylesheet $sheet ) {
		return $sheet->render();

		// $orig_media = $sheet->media;

		// // If no display= or if display=auto.
		// if (!preg_match('/display=(?!auto)/', $sheet->url)) {
		// 	$sheet->media = 'x';
		// }

		// // Add the JS to render with display: swap on mobile.
		// $extra = "<script>const e = document.currentScript.previousElementSibling; window.innerWidth > 1024 || (e.href+='&display=swap'); e.media='" . esc_js($orig_media) ."'</script>";
		// $this->renders[] = $sheet->render() . $extra;

		// // Return empty space to strip out the tag.
		// return ' ';
	}
}
