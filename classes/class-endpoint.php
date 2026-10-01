<?php
/**
 * Public /llms.txt response.
 *
 * @package LlmsTxt
 */

namespace LlmsTxt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Endpoint {

	public static function register_rewrite(): void {
		add_rewrite_rule( '^llms\.txt$', 'index.php?llms_txt=1', 'top' );
	}

	public function register(): void {
		add_action( 'init', array( self::class, 'register_rewrite' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'template_redirect', array( $this, 'maybe_render' ) );
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
		echo Cache::get(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text document body.
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
