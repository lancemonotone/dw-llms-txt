<?php
/**
 * Stored llms.txt option values (read path).
 *
 * @package Llms_Txt
 */

declare(strict_types=1);

namespace Llms_Txt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads saved plugin options.
 */
final class Config {

	/**
	 * Normalized settings. Unconfigured sites get auto menu, all types, sitemap on.
	 *
	 * @return array{menu_location: string, post_types: list<string>|null, include_sitemap: bool}
	 */
	public function all(): array {
		$raw = get_option( Options::OPTION, null );

		if ( ! is_array( $raw ) || empty( $raw['configured'] ) ) {
			return array(
				'menu_location'   => '',
				'post_types'      => null,
				'include_sitemap' => true,
			);
		}

		$menu = isset( $raw['menu_location'] ) ? sanitize_key( (string) $raw['menu_location'] ) : '';

		$types = array();
		if ( isset( $raw['post_types'] ) && is_array( $raw['post_types'] ) ) {
			foreach ( $raw['post_types'] as $slug ) {
				if ( is_string( $slug ) && $slug !== '' ) {
					$types[] = sanitize_key( $slug );
				}
			}
		}
		$types = array_values( array_unique( $types ) );

		return array(
			'menu_location'   => $menu,
			'post_types'      => $types,
			'include_sitemap' => ! empty( $raw['include_sitemap'] ),
		);
	}

	/**
	 * Whether settings have been saved at least once.
	 */
	public function is_configured(): bool {
		$raw = get_option( Options::OPTION, null );

		return is_array( $raw ) && ! empty( $raw['configured'] );
	}

	/**
	 * Selected theme menu location slug, or empty for auto.
	 */
	public function menu_location(): string {
		return $this->all()['menu_location'];
	}

	/**
	 * Selected content-type slugs, or null when every eligible type should be included.
	 *
	 * @return list<string>|null
	 */
	public function post_types(): ?array {
		return $this->all()['post_types'];
	}

	/**
	 * Whether the Optional sitemap link is enabled.
	 */
	public function include_sitemap(): bool {
		return $this->all()['include_sitemap'];
	}
}
