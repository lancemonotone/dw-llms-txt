<?php
/**
 * Registers and sanitizes llms.txt settings.
 *
 * @package Llms_Txt
 */

declare(strict_types=1);

namespace Llms_Txt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings API wiring.
 */
final class Settings {

	public const GROUP = 'llms_txt';

	private Options $options;

	public function __construct() {
		$this->options = new Options();

		add_action( 'admin_init', array( $this, 'register' ) );
	}

	public function register(): void {
		register_setting(
			self::GROUP,
			Options::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * @param mixed $input Raw form input.
	 * @return array{menu_location: string, post_types: list<string>, include_sitemap: bool}
	 */
	public function sanitize( $input ): array {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$menu = isset( $input['menu_location'] ) ? sanitize_key( (string) $input['menu_location'] ) : '';
		$assigned = $this->options->assigned_menu_locations();

		if ( $menu !== '' && ! isset( $assigned[ $menu ] ) ) {
			$menu = '';
		}

		$catalog = $this->options->catalog_post_types();
		$types   = array();

		if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
			foreach ( $input['post_types'] as $slug ) {
				if ( ! is_string( $slug ) ) {
					continue;
				}
				$slug = sanitize_key( $slug );
				if ( isset( $catalog[ $slug ] ) ) {
					$types[] = $slug;
				}
			}
		}

		$types = array_values( array_unique( $types ) );

		$sitemap = ! empty( $input['include_sitemap'] );

		$clean = array(
			'menu_location'   => $menu,
			'post_types'      => $types,
			'include_sitemap' => $sitemap,
		);

		do_action( 'llms_txt_settings_updated', $clean );

		return $clean;
	}
}

new Settings();
