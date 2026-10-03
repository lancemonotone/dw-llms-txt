<?php

/**
 * Stored option values (read path).
 *
 * @package Llms_Txt
 */

declare(strict_types=1);

namespace Llms_Txt;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Reads saved plugin options.
 */
final class Config {

	/**
	 * Normalized settings. Unconfigured installs: empty selections, no sitemap.
	 *
	 * @return array{menu_location: string, post_types: string[], taxonomies: string[], include_sitemap: bool}
	 */
	public function all(): array {
		$raw = get_option(Plugin::OPTION, null);

		if (! is_array($raw) || empty($raw['configured'])) {
			return array(
				'menu_location'   => '',
				'post_types'      => array(),
				'taxonomies'      => array(),
				'include_sitemap' => false,
			);
		}

		$menu = isset($raw['menu_location']) ? sanitize_key((string) $raw['menu_location']) : '';

		return array(
			'menu_location'   => $menu,
			'post_types'      => $this->slug_list($raw['post_types'] ?? null),
			'taxonomies'      => $this->slug_list($raw['taxonomies'] ?? null),
			'include_sitemap' => ! empty($raw['include_sitemap']),
		);
	}

	/**
	 * Whether settings have been saved at least once.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		$raw = get_option(Plugin::OPTION, null);

		return is_array($raw) && ! empty($raw['configured']);
	}

	/**
	 * Theme menu location slug, or empty for none.
	 *
	 * @return string
	 */
	public function menu_location(): string {
		return $this->all()['menu_location'];
	}

	/**
	 * Selected content-type slugs (empty when none or unconfigured).
	 *
	 * @return string[]
	 */
	public function post_types(): array {
		return $this->all()['post_types'];
	}

	/**
	 * Selected taxonomy slugs (empty when none or unconfigured).
	 *
	 * @return string[]
	 */
	public function taxonomies(): array {
		return $this->all()['taxonomies'];
	}

	/**
	 * Whether the Optional sitemap link is on.
	 *
	 * @return bool
	 */
	public function include_sitemap(): bool {
		return $this->all()['include_sitemap'];
	}

	/**
	 * @param mixed $raw Raw option value.
	 * @return string[]
	 */
	private function slug_list($raw): array {
		if (! is_array($raw)) {
			return array();
		}

		$slugs = array();

		foreach ($raw as $slug) {
			if (is_string($slug) && $slug !== '') {
				$slugs[] = sanitize_key($slug);
			}
		}

		return array_values(array_unique($slugs));
	}
}
