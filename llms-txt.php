<?php
/**
 * Plugin Name:       llms.txt
 * Description:       Serves a dynamic llms.txt map of your WordPress site for AI agents.
 * Version:           1.2.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Rus Miller
 * Text Domain:       llms-txt
 *
 * @package Llms_Txt
 */

declare(strict_types=1);

namespace Llms_Txt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LLMS_TXT_VERSION', '1.2.0' );
define( 'LLMS_TXT_FILE', __FILE__ );
define( 'LLMS_TXT_DIR', \plugin_dir_path( __FILE__ ) );
define( 'LLMS_TXT_URL', \plugin_dir_url( __FILE__ ) );

/**
 * Single source for public filename and internal prefixes.
 */
final class Plugin {

	/**
	 * Public document filename (https://llmstxt.org/).
	 */
	public const DOCUMENT = 'llms.txt';

	/**
	 * Admin page / text-domain style slug.
	 */
	public const SLUG = 'llms-txt';

	/**
	 * Underscore prefix for options, hooks, query vars, transients.
	 */
	public const PREFIX = 'llms_txt';

	public const OPTION = self::PREFIX . '_settings';

	public const GROUP = self::PREFIX;

	public const CACHE_KEY = self::PREFIX . '_body';

	public const QUERY_VAR = self::PREFIX;

	/**
	 * Prefixed action/filter name.
	 *
	 * Example: hook( 'document_sections' ) → llms_txt_document_sections.
	 */
	public static function hook( string $suffix ): string {
		$suffix = ltrim( $suffix, '_' );

		return '' === $suffix ? self::PREFIX : self::PREFIX . '_' . $suffix;
	}

	/**
	 * Public URL path including leading slash.
	 */
	public static function path(): string {
		return '/' . self::DOCUMENT;
	}

	/**
	 * Rewrite rule regex for the public document.
	 */
	public static function rewrite_regex(): string {
		return '^' . preg_quote( self::DOCUMENT, '/' ) . '$';
	}

	/**
	 * Rewrite query for the public document.
	 */
	public static function rewrite_query(): string {
		return 'index.php?' . self::QUERY_VAR . '=1';
	}
}

foreach ( \glob( LLMS_TXT_DIR . 'classes/class.*.php' ) as $filename ) {
	require_once $filename;
}

\register_activation_hook(
	__FILE__,
	static function (): void {
		Endpoint::activate();
	}
);

\register_deactivation_hook(
	__FILE__,
	static function (): void {
		\flush_rewrite_rules();
	}
);
