<?php

namespace Authow\PageSpeed\RemoveCss;

use Sabberworm\CSS\CSSList\AtRuleBlockList;
use Sabberworm\CSS\CSSList\CSSBlockList;
use Sabberworm\CSS\CSSList\Document;
use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\Parser as CSSParser;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use Sabberworm\CSS\Settings;
use Sabberworm\CSS\Value\URL;
use Authow\PageSpeed\OptimizeCss\Stylesheet;
use Authow\PageSpeed\Util;

/**
 * Sanitizer removes the unnecessary CSS, provided a stylesheet.
 *
 * @author  asadkn
 * @modified gosodesign
 * @since   1.0.0
 */
class Sanitizer {
	/**
	 * @var Stylesheet
	 */
	protected $sheet;

	protected $css;
	protected $used_markup = [
		'classes' => [],
		'tags'    => [],
		'ids'     => []
	];

	protected $allow_selectors = [];

	/**
	 * @param Stylesheet $sheet
	 * @param array $used_markup {
	 *
	 * @type array $classes
	 * @type array $tags
	 * @type array $ids
	 * }
	 */
	public function __construct( Stylesheet $sheet, array $used_markup, $allow = [] ) {
		$this->sheet       = $sheet;
		$this->css         = $sheet->content;
		$this->used_markup = array_replace( $this->used_markup, $used_markup );

		$this->allow_selectors = $allow;

	}

	public function set_cache( $data ) {
		$this->cache = $data;
	}

	public function sanitize() {
		$data = $this->sheet->parsed_data ?: [];
		if ( ! $data ) {

			// Strip the dreaded UTF-8 byte order mark (BOM, \uFEFF). Ref: https://github.com/sabberworm/PHP-CSS-Parser/issues/150
			$this->css = preg_replace( '/^\xEF\xBB\xBF/', '', $this->css );

			$config = Settings::create()->withMultibyteSupport( false );
			$parser = new CSSParser( $this->css, $config );
			$parsed = $parser->parse();

			// Fix relative URLs.
			$this->convert_urls( $parsed );
			$data = $this->transform_data( $parsed );

			$this->sheet->parsed_data = $data;
		}

		$this->process_allowed_selectors();

		return $this->render_css( $data );
	}

	/**
	 * Convert relative URLs to full URLs for inline inclusion or changed paths.
	 *
	 * @param Document $data
	 *
	 * @return void
	 */
	public function convert_urls( Document $data ) {
		$base_url = preg_replace( '#[^/]+\?.*$#', '', $this->sheet->url );

		$values = $data->getAllValues();
		foreach ( $values as $value ) {
			if ( ! ( $value instanceof URL ) ) {
				continue;
			}

			$url = $value->getURL()->getString();
			// if (substr($url, 0, 5) === 'data:') {
			// 	continue;
			// }
			if ( preg_match( '/^(https?|data):/', $url ) ) {
				continue;
			}

			$parsed_url = parse_url( $url );

			// Skip known host and protocol-relative paths.
			if ( ! empty( $parsed_url['host'] ) || empty( $parsed_url['path'] ) || $parsed_url['path'][0] === '/' ) {
				continue;
			}

			$new_url = $base_url . $url;
			$value->getUrl()->setString( $new_url );
		}
	}

	/**
	 * Transform data structure to store in our format. This data will be used without
	 * loading CSS Parser on further requests.
	 *
	 * @param CSSBlockList $data
	 *
	 * @return array
	 */
	public function transform_data( CSSBlockList $data ) {
		$items = [];
		foreach ( $data->getContents() as $content ) {
			if ( $content instanceof AtRuleBlockList ) {
				$items[] = [
					'rulesets' => $this->transform_data( $content ),
					'at_rule'  => "@{$content->atRuleName()} {$content->atRuleArgs()}",
				];
			} else {
				$item = [
					//'css' => $content->render(OutputFormat::createPretty())
					'css' => $content->render( OutputFormat::createCompact() )
				];

				if ( $content instanceof DeclarationBlock ) {
					$item['selectors'] = $this->parse_selectors( $content->getSelectors() );
				}

				$items[] = $item;
			}
		}

		return $items;
	}

	/**
	 * Parse selectors to get classes, id, tags and attrs.
	 *
	 * @param array $selectors
	 *
	 * @return array
	 */
	protected function parse_selectors( $selectors ) {
		$selectors = array_map(
			function ( $sel ) {
				return $sel->__toString();
			},
			$selectors
		);

		$selectors_data = [];
		foreach ( $selectors as $selector ) {
			$data = [
				'classes'  => [],
				'ids'      => [],
				'tags'     => [],
				// 'pseudo'   => [],
				'attrs'    => [],
				'selector' => trim( $selector ),
			];

			// if (strpos($selector, ':root') !== false) {
			// 	$data['pseudo'][':root'] = 1;
			// }

			// Based on AMP plugin.
			// Handle :not() and pseudo selectors to eliminate false negatives.
			$selector = preg_replace( '/(?<!\\\\)::?[a-zA-Z0-9_-]+(\(.+?\))?/', '', $selector );

			// Get attributes but remove them from the selector to prevent false positives 
			// from within attribute selector.
			$selector = preg_replace_callback(
				'/\[([A-Za-z0-9_:-]+)(\W?=[^\]]+)?\]/',
				function ( $matches ) use ( &$data ) {
					$data['attrs'][] = $matches[1];

					return '';
				},
				$selector
			);

			// Extract class names.
			$selector = preg_replace_callback(
			// The `\\\\.` will allow any char via escaping, like the colon in `.lg\:w-full`.
				'/\.((?:[a-zA-Z0-9_-]+|\\\\.)+)/',
				function ( $matches ) use ( &$data ) {
					$data['classes'][] = stripslashes( $matches[1] );

					return '';
				},
				$selector
			);

			// Extract IDs.
			$selector = preg_replace_callback(
				'/#([a-zA-Z0-9_-]+)/',
				function ( $matches ) use ( &$data ) {
					$data['ids'][] = $matches[1];

					return '';
				},
				$selector
			);

			// Extract tag names.
			$selector = preg_replace_callback(
				'/[a-zA-Z0-9_-]+/',
				function ( $matches ) use ( &$data ) {
					$data['tags'][] = $matches[0];

					return '';
				},
				$selector
			);

			$selectors_data[] = array_filter( $data );
		}

		return array_filter( $selectors_data );
	}

	/**
	 * Pre-process allowed selectors.
	 *
	 * Convert data structures in proper format, mainly for performance.
	 *
	 * @return void
	 */
	protected function process_allowed_selectors() {
		foreach ( $this->allow_selectors as $key => $value ) {

			// Check if selector rule valid for current sheet.
			if ( isset( $value['sheet'] ) && ! Util\asset_match( $value['sheet'], $this->sheet ) ) {
				unset( $this->allow_selectors[ $key ] );
				continue;
			}

			$value = $this->add_search_regex( $value );
			$regex = $value['search_regex'] ?? '';

			// Pre-compute the matching regex for performance.
			if ( isset( $value['search'] ) ) {
				$value['search'] = array_filter( (array) $value['search'] );

				// If we still have something.
				if ( $value['search'] ) {
					$loose_regex = '(' . implode( '|', array_map( 'preg_quote', $value['search'] ) ) . ')(?=\s|\.|\:|,|\[|$)';

					// Combine with search_regex if available.
					$regex = $regex ? "($loose_regex|$regex)" : $loose_regex;
				}
			}

			if ( $regex ) {
				$value['computed_search_regex'] = $regex;
			}

			$this->allow_selectors[ $key ] = $value;
		}
	}

	/**
	 * Add search regex to array by converting astrisks to proper regex search.
	 *
	 * @param array $value
	 *
	 * @return array
	 */
	protected function add_search_regex( array $value ) {
		if ( isset( $value['search_regex'] ) ) {
			return $value;
		}

		if ( isset( $value['search'] ) ) {

			$value['search'] = (array) $value['search'];
			$regex           = [];

			foreach ( $value['search'] as $key => $search ) {
				if ( strpos( $search, '*' ) !== false ) {
					$search = trim( $search );

					// Optimize regex for starting.
					// Note: Ending asterisk removal isn't necessary. PCRE engine is optimized for that.
					$has_first_asterisk = 0;
					$search             = preg_replace( '/^\*(.+?)/', '\\1', $search, 1, $has_first_asterisk );

					// 1. Space and asterisk matches a class itself, followed by space (child), or comma separator.
					// 2. Only asterisk is considered more of a prefix/suffix and .class* will match .classname too.
					$search = preg_quote( $search );
					$search = str_replace( ' \*', '(\s|$|,|\:).*?', $search );
					$search = str_replace( '\*', '.*?', $search );

					// Note: To prevent ^(.*?) which is slow, we add starting position match only
					// if the search doesn't start with asterisk match.
					$regex[] = ( $has_first_asterisk ? '' : '^' ) . $search;

					unset( $value['search'][ $key ] );
				}
			}

			if ( $regex ) {
				$value['search_regex'] = '(' . implode( '|', $regex ) . ')';
			}
		}

		return $value;
	}

	public function render_css( $data ) {
		$rendered = [];
		foreach ( $data as $item ) {

			// Has CSS.
			if ( isset( $item['css'] ) ) {
				$css = $item['css'];

				// Render only if at least one selector meets the should_include criteria.
				$should_render = ! isset( $item['selectors'] ) ||
					0 !== count(
						array_filter(
							$item['selectors'],
							function ( $selector ) {
								return $this->should_include( $selector );
							}
						)
					);

				if ( $should_render && $this->should_render_props( $css ) ) {
					$rendered[] = $css;
				}

				continue;
			}

			// Nested ruleset.
			if ( ! empty( $item['rulesets'] ) ) {
				$child_rulesets = $this->render_css( $item['rulesets'] );

				if ( $child_rulesets && $this->should_render_props( $child_rulesets ) ) {
					$rendered[] = sprintf(
						'%s { %s }',
						$item['at_rule'],
						$child_rulesets
					);
				}
			}
		}

		return implode( "", $rendered );
	}

	/**
	 * Whether to include a selector in the output.
	 *
	 * @param array $selector {
	 *
	 * @type string[]|null $classes
	 * @type string[]|null $ids
	 * @type string[]|null $tags
	 * }
	 * @return boolean
	 */
	public function should_include( $selector ) {
		// :root is always valid.
		// Note: Selectors of type `:root .class` will not match this but will be validated below
		// if .class is used, as intended.
		if ( $selector['selector'] === ':root' ) {
			return true;
		}

		// If it's an attribute selector with nothing else, it should be kept. Perhaps *[attr] or [attr].
		if ( ! empty( $selector['attrs'] )
			&& ( empty( $selector['classes'] ) && empty( $selector['ids'] ) && empty( $selector['tags'] ) )
		) {
			return true;
		}

		// Check allow list.
		// @todo move to cached pre-processed. Clear on settings change.

		// $this->allow_selectors = [
		// 	[
		// 		'type'  => 'any',
		// 		'search' => '.auth-modal',
		// 	],
		// 	[
		// 		'type'  => 'prefix',
		// 		'class' => 's-dark'
		// 	],
		// 	[
		// 		'type'   => 'class',
		// 		'class'  => 'has-lb',
		// 		'search'  => ['.mfp-']
		// 	],
		// 	[
		// 		'type'   => 'any',
		// 		'class'  => 'has-lb',
		// 		'search'  => ['.mfp-']
		// 	],
		// ];

		if ( $this->allow_selectors ) {
			foreach ( $this->allow_selectors as $include ) {

				/**
				 * Prefix-based + all other classes/tags/etc. in selector exist in doc.
				 *
				 * Note: It's basically to ignore the first class and include the sub-classes based
				 * on their existence in doc. Example: .scheme-dark or .scheme-light.
				 */
				if ( $include['type'] === 'prefix' ) {

					// Check if exact match.
					if ( ( '.' . $include['class'] ) === $selector['selector'] ) {
						return true;
					}

					// Check if first class matches.
					$has_prefix = $include['class'] === substr( $selector['selector'], 1, strlen( $include['class'] ) );
					if ( $has_prefix ) {

						// Will check for validity later below. Remove first class as it's allowed.
						if ( isset( $selector['classes'] ) ) {
							$selector['classes'] = array_diff( $selector['classes'], [ $include['class'] ] );
						}

						// WARNING: Due to this break, if there's a rule to allow all selectors of this prefix
						// that appear later, it won't be validated.
						// @todo Sort prefixes to be at the end or run them later.
						break;
					}

					continue;
				}

				// Check if a class exists in document.
				if ( $include['type'] === 'class' ) {
					if ( ! $this->is_used( $include['class'], 'classes' ) ) {
						continue;
					}
				}

				// Simple search selector string.
				// $search = !empty($include['search']) ? (array) $include['search'] : [];

				// Any type, normal selector string match.
				// Note: The regex is equal at n=1 and faster at n>1, surprisingly.
				// if ($search) {
				// foreach ($search as $to_match) {
				// 	if (strpos($selector['selector'], $to_match) !== false) {
				// 		return true;
				// 	}
				// }
				// }

				// Pre-computed regex - combined 'search' and 'search_regex'.
				if ( ! empty( $include['computed_search_regex'] ) ) {
					if ( preg_match( '#' . $include['computed_search_regex'] . '#', $selector['selector'] ) ) {
						return true;
					}
				}
			}
		}

		$valid = true;
		if (
			// Check if all classes are used.
			( ! empty( $selector['classes'] ) && ! $this->is_used( $selector['classes'], 'classes' ) )

			// Check if all the ids are used.
			|| ( ! empty( $selector['ids'] ) && ! $this->is_used( $selector['ids'], 'ids' ) )

			// Check for the target tags in used.
			|| ( ! empty( $selector['tags'] ) && ! $this->is_used( $selector['tags'], 'tags' ) )
		) {
			$valid = false;
		}

		return $valid;
	}

	/**
	 * Test if a selector classes, ids, or tags are used in the doc (provided in $this->used_markup).
	 *
	 * @param string|array $targets
	 * @param string $type 'classes', 'tags', or 'ids'.
	 *
	 * @return boolean
	 */
	public function is_used( $targets, $type = '' ) {
		if ( ! $type ) {
			return false;
		}

		if ( ! is_array( $targets ) ) {
			$targets = (array) $targets;
		}

		foreach ( $targets as $target ) {
			// All targets must exist.
			if ( ! isset( $this->used_markup[ $type ][ $target ] ) ) {
				return false;
			}
		}

		return true;
	}

	public function should_render_props( $prop ) {
		$render = true;
		if ( get_theme_mod( 'goso_speed_disable_first_screen' ) && strpos( $prop, 'background-image' ) !== false ) {
			$render = false;
		}

		return $render;
	}

}
