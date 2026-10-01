<?php
/**
 * Transient cache for the rendered llms.txt body.
 *
 * @package Llms_Txt
 */

declare(strict_types=1);

namespace Llms_Txt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Document body cache and invalidation.
 */
final class Cache {

	private const KEY = 'llms_txt_body';

	/**
	 * Registers invalidation hooks.
	 */
	public function __construct() {
		add_action( 'save_post', array( $this, 'forget_on_post' ) );
		add_action( 'deleted_post', array( $this, 'forget' ) );
		add_action( 'wp_update_nav_menu', array( $this, 'forget' ) );
		add_action( 'created_term', array( $this, 'forget' ) );
		add_action( 'edited_term', array( $this, 'forget' ) );
		add_action( 'delete_term', array( $this, 'forget' ) );
		add_action( 'update_option_blogdescription', array( $this, 'forget' ) );
		add_action( 'update_option_blogname', array( $this, 'forget' ) );
		add_action( 'llms_txt_settings_updated', array( $this, 'forget' ) );
	}

	/**
	 * Returns the cached body, rendering and storing it when missing.
	 */
	public static function get(): string {
		$cached = get_transient( self::KEY );

		if ( is_string( $cached ) && $cached !== '' ) {
			return $cached;
		}

		$body = ( new Document() )->render();
		set_transient( self::KEY, $body, DAY_IN_SECONDS );

		return $body;
	}

	/**
	 * Deletes the cached body.
	 */
	public function forget(): void {
		delete_transient( self::KEY );
	}

	/**
	 * Clears cache after a non-revision post save.
	 *
	 * @param int $post_id Saved post ID.
	 */
	public function forget_on_post( int $post_id ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$this->forget();
	}
}

new Cache();
