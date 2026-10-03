<?php

/**
 * Registers the options page and Settings API option.
 *
 * @package Llms_Txt
 */

declare(strict_types=1);

namespace Llms_Txt;

if (! defined('ABSPATH')) {
	exit;
}

final class Options {

	public function __construct() {
		add_action('admin_menu', array($this, 'menu'));
		add_action('admin_init', array($this, 'register'));
	}

	/**
	 * Settings page. Markup comes from the options_page action.
	 */
	public function menu(): void {
		add_options_page(
			Plugin::DOCUMENT,
			Plugin::DOCUMENT,
			'manage_options',
			Plugin::SLUG,
			static function (): void {
				do_action(Plugin::hook('options_page'));
			}
		);
	}

	public function register(): void {
		register_setting(
			Plugin::GROUP,
			Plugin::OPTION,
			array(
				'sanitize_callback' => array($this, 'sanitize'),
			)
		);
	}

	/**
	 * Sanitize submitted settings and bust the document cache.
	 *
	 * @param mixed $input Raw form input.
	 * @return array{configured: int, menu_location: string, post_types: string[], taxonomies: string[], include_sitemap: int}
	 */
	public function sanitize($input): array {
		if (! is_array($input)) {
			$input = array();
		}

		$catalog  = new Catalog();
		$menu     = isset($input['menu_location']) ? sanitize_key((string) $input['menu_location']) : '';
		$assigned = $catalog->assigned_menu_locations();

		if ($menu !== '' && ! isset($assigned[$menu])) {
			$menu = '';
		}

		$clean = array(
			'configured'      => 1,
			'menu_location'   => $menu,
			'post_types'      => $this->allowed_slugs($input['post_types'] ?? null, $catalog->post_types()),
			'taxonomies'      => $this->allowed_slugs($input['taxonomies'] ?? null, $catalog->taxonomies()),
			'include_sitemap' => ! empty($input['include_sitemap']) ? 1 : 0,
		);

		do_action(Plugin::hook('settings_updated'), $clean);

		return $clean;
	}

	/**
	 * @param mixed                 $raw     Submitted slugs.
	 * @param array<string, string> $allowed Catalog slug => label.
	 * @return string[]
	 */
	private function allowed_slugs($raw, array $allowed): array {
		if (! is_array($raw)) {
			return array();
		}

		$slugs = array();

		foreach ($raw as $slug) {
			if (! is_string($slug)) {
				continue;
			}
			$slug = sanitize_key($slug);
			if (isset($allowed[$slug])) {
				$slugs[] = $slug;
			}
		}

		return array_values(array_unique($slugs));
	}
}

new Options();
