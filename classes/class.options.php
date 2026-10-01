<?php
/**
 * Registers the llms.txt options page and Settings API option.
 *
 * @package Llms_Txt
 */

declare(strict_types=1);

namespace Llms_Txt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Options page setup (not UI markup).
 */
final class Options {

	public const OPTION = 'llms_txt_settings';

	public const GROUP = 'llms_txt';

	/**
	 * Hooks options page and Settings API registration.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
	}

	/**
	 * Adds the settings page under Settings. UI renders via llms_txt_options_page.
	 */
	public function menu(): void {
		add_options_page(
			'llms.txt',
			'llms.txt',
			'manage_options',
			'llms-txt',
			static function (): void {
				do_action( 'llms_txt_options_page' );
			}
		);
	}

	/**
	 * Registers the plugin option with the Settings API.
	 */
	public function register(): void {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);
	}

	/**
	 * Sanitizes submitted settings and fires the cache-bust action.
	 *
	 * @param mixed $input Raw form input.
	 * @return array{configured: int, menu_location: string, post_types: list<string>, include_sitemap: int}
	 */
	public function sanitize( $input ): array {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$catalog  = new Catalog();
		$menu     = isset( $input['menu_location'] ) ? sanitize_key( (string) $input['menu_location'] ) : '';
		$assigned = $catalog->assigned_menu_locations();

		if ( $menu !== '' && ! isset( $assigned[ $menu ] ) ) {
			$menu = '';
		}

		$types_catalog = $catalog->post_types();
		$types         = array();

		if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
			foreach ( $input['post_types'] as $slug ) {
				if ( ! is_string( $slug ) ) {
					continue;
				}
				$slug = sanitize_key( $slug );
				if ( isset( $types_catalog[ $slug ] ) ) {
					$types[] = $slug;
				}
			}
		}

		$types = array_values( array_unique( $types ) );

		$clean = array(
			'configured'      => 1,
			'menu_location'   => $menu,
			'post_types'      => $types,
			'include_sitemap' => ! empty( $input['include_sitemap'] ) ? 1 : 0,
		);

		do_action( 'llms_txt_settings_updated', $clean );

		return $clean;
	}
}

new Options();
