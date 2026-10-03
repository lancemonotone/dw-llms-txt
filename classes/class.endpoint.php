<?php

/**
 * Public /llms.txt endpoint.
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

	public const RULE_REGEX = '^llms\.txt$';

	public const RULE_QUERY = 'index.php?llms_txt=1';

	public function __construct() {
		add_action('init', array($this, 'register_rewrite'));
		add_filter('query_vars', array($this, 'query_vars'));
		add_action('template_redirect', array($this, 'maybe_render'));
	}

	public function register_rewrite(): void {
		add_rewrite_rule(self::RULE_REGEX, self::RULE_QUERY, 'top');
	}

	/**
	 * @param string[] $vars Public query vars.
	 * @return string[]
	 */
	public function query_vars(array $vars): array {
		$vars[] = 'llms_txt';

		return $vars;
	}

	/**
	 * Print the cached document when this request is /llms.txt.
	 */
	public function maybe_render(): void {
		if (! $this->is_llms_request()) {
			return;
		}

		status_header(200);
		nocache_headers();
		header('Content-Type: text/plain; charset=utf-8');
		echo Cache::get(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text document body.
		exit;
	}

	/**
	 * Whether this request matched the llms.txt rewrite.
	 *
	 * @return bool
	 */
	private function is_llms_request(): bool {
		return (string) get_query_var('llms_txt') === '1';
	}
}

new Endpoint();
