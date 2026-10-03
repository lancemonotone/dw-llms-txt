<?php

/**
 * Public document endpoint.
 *
 * @package Llms_Txt
 */

declare(strict_types=1);

namespace Llms_Txt;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Rewrite registration and plain-text response.
 */
final class Endpoint {

	public function __construct() {
		add_action('init', array($this, 'register_rewrite'));
		add_filter('query_vars', array($this, 'query_vars'));
		add_action('template_redirect', array($this, 'maybe_render'));
	}

	/**
	 * Registers the rewrite and flushes permalinks (activation).
	 */
	public static function activate(): void {
		add_rewrite_rule(Plugin::rewrite_regex(), Plugin::rewrite_query(), 'top');
		flush_rewrite_rules();
	}

	public function register_rewrite(): void {
		add_rewrite_rule(Plugin::rewrite_regex(), Plugin::rewrite_query(), 'top');
	}

	/**
	 * @param string[] $vars Public query vars.
	 * @return string[]
	 */
	public function query_vars(array $vars): array {
		$vars[] = Plugin::QUERY_VAR;

		return $vars;
	}

	/**
	 * Print the cached document when this request matches the rewrite.
	 */
	public function maybe_render(): void {
		if (! $this->is_document_request()) {
			return;
		}

		status_header(200);
		nocache_headers();
		header('Content-Type: text/plain; charset=utf-8');
		echo Cache::get(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text document body.
		exit;
	}

	/**
	 * Whether this request matched the document rewrite.
	 */
	private function is_document_request(): bool {
		return (string) get_query_var(Plugin::QUERY_VAR) === '1';
	}
}

new Endpoint();
