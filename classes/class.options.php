<?php
/**
 * Stored llms.txt settings (read path).
 *
 * @package Llms_Txt
 */

declare(strict_types=1);

namespace Llms_Txt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Option getters. No hooks — safe to `new Options()` from Document.
 *
 * Unconfigured (option missing): auto menu, all eligible content types, sitemap on.
 * After save: use stored values only (empty CPT list = no Content types section).
 */
final class Options {

	public const OPTION = 'llms_txt_settings';

	/**
	 * @return array{menu_location: string, post_types: list<string>|null, include_sitemap: bool}
	 */
	public function all(): array {
		$raw = get_option( self::OPTION, null );

		if ( ! is_array( $raw ) ) {
			return array(
				'menu_location'   => '',
				'post_types'      => null,
				'include_sitemap' => true,
			);
		}

		$menu = isset( $raw['menu_location'] ) ? sanitize_key( (string) $raw['menu_location'] ) : '';

		$types = null;
		if ( array_key_exists( 'post_types', $raw ) ) {
			$types = array();
			if ( is_array( $raw['post_types'] ) ) {
				foreach ( $raw['post_types'] as $slug ) {
					if ( is_string( $slug ) && $slug !== '' ) {
						$types[] = sanitize_key( $slug );
					}
				}
			}
			$types = array_values( array_unique( $types ) );
		}

		$sitemap = true;
		if ( array_key_exists( 'include_sitemap', $raw ) ) {
			$sitemap = (bool) $raw['include_sitemap'];
		}

		return array(
			'menu_location'   => $menu,
			'post_types'      => $types,
			'include_sitemap' => $sitemap,
		);
	}

	public function is_configured(): bool {
		return is_array( get_option( self::OPTION, null ) );
	}

	public function menu_location(): string {
		return $this->all()['menu_location'];
	}

	/**
	 * null = include every eligible type; list = only those slugs.
	 *
	 * @return list<string>|null
	 */
	public function post_types(): ?array {
		return $this->all()['post_types'];
	}

	public function include_sitemap(): bool {
		return $this->all()['include_sitemap'];
	}

	/**
	 * Public types that can appear under Content types (excludes page + attachment).
	 *
	 * @return array<string, string> slug => label
	 */
	public function catalog_post_types(): array {
		$objects = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		if ( ! is_array( $objects ) ) {
			return array();
		}

		$out = array();

		foreach ( $objects as $slug => $object ) {
			if ( ! is_string( $slug ) || ! $object instanceof \WP_Post_Type ) {
				continue;
			}
			if ( in_array( $slug, array( 'attachment', 'page' ), true ) ) {
				continue;
			}

			$label         = isset( $object->labels->name ) ? (string) $object->labels->name : $slug;
			$out[ $slug ] = $label;
		}

		return $out;
	}

	/**
	 * Theme menu locations currently assigned a menu.
	 *
	 * @return array<string, string> location slug => label
	 */
	public function assigned_menu_locations(): array {
		$locations = get_nav_menu_locations();
		$registered = get_registered_nav_menus();

		if ( ! is_array( $locations ) || $locations === array() ) {
			return array();
		}

		if ( ! is_array( $registered ) ) {
			$registered = array();
		}

		$out = array();

		foreach ( $locations as $slug => $menu_id ) {
			if ( ! is_string( $slug ) || (int) $menu_id <= 0 ) {
				continue;
			}
			$label = isset( $registered[ $slug ] ) ? (string) $registered[ $slug ] : $slug;
			$out[ $slug ] = $label;
		}

		return $out;
	}
}
