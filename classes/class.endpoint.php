<?php
/**
 * Public /llms.txt response.
 *
 * @package Llms_Txt
 */

declare(strict_types=1);

namespace Llms_Txt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rewrite + plain-text response for /llms.txt.
 */
final class Endpoint {

	public const RULE_REGEX = '^llms\.txt$';

	public const RULE_QUERY = 'index.php?llms_txt=1';

	private Cache $cache;

	public function __construct() {
		$this->cache = new Cache();

		// Never call add_rewrite_rule() at construct: plugins load before $wp_rewrite exists.
		add_action( 'init', array( $this, 'register_rewrite' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'template_redirect', array( $this, 'maybe_render' ) );
	}

	public function register_rewrite(): void {
		add_rewrite_rule( self::RULE_REGEX, self::RULE_QUERY, 'top' );
	}

	/**
	 * @param array<int, string> $vars
	 * @return array<int, string>
	 */
	public function query_vars( array $vars ): array {
		$vars[] = 'llms_txt';

		return $vars;
	}

	public function maybe_render(): void {
		if ( ! $this->is_llms_request() ) {
			return;
		}

		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo $this->cache->get(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text document body.
		exit;
	}

	private function is_llms_request(): bool {
		if ( (string) get_query_var( 'llms_txt' ) === '1' ) {
			return true;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path        = wp_parse_url( $request_uri, PHP_URL_PATH );

		return is_string( $path ) && untrailingslashit( $path ) === '/llms.txt';
	}
}

new Endpoint();
