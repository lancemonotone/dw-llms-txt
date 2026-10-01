<?php
/**
 * Bootstrap.
 *
 * @package LlmsTxt
 */

namespace LlmsTxt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	private static ?Plugin $instance = null;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		( new Endpoint() )->register();
		( new Admin() )->register();

		add_action( 'save_post', array( $this, 'forget_on_post' ) );
		add_action( 'deleted_post', array( Cache::class, 'forget' ) );
		add_action( 'wp_update_nav_menu', array( Cache::class, 'forget' ) );
		add_action( 'created_term', array( Cache::class, 'forget' ) );
		add_action( 'edited_term', array( Cache::class, 'forget' ) );
		add_action( 'delete_term', array( Cache::class, 'forget' ) );
		add_action( 'update_option_blogdescription', array( Cache::class, 'forget' ) );
		add_action( 'update_option_blogname', array( Cache::class, 'forget' ) );
	}

	public function forget_on_post( int $post_id ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		Cache::forget();
	}
}
