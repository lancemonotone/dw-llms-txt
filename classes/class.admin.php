<?php
/**
 * Preview screen and notices.
 *
 * @package Llms_Txt
 */

declare(strict_types=1);

namespace Llms_Txt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings preview + admin notices.
 */
final class Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_notices', array( $this, 'notices' ) );
	}

	public function menu(): void {
		add_options_page(
			'llms.txt',
			'llms.txt',
			'manage_options',
			'llms-txt',
			array( $this, 'render' )
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$public_url = home_url( '/llms.txt' );
		$body       = ( new Document() )->render();

		echo '<div class="wrap">';
		echo '<h1>llms.txt</h1>';
		echo '<p><a href="' . esc_url( $public_url ) . '">View public llms.txt</a></p>';
		echo '<textarea readonly="readonly" rows="28" class="large-text code">' . esc_textarea( $body ) . '</textarea>';
		echo '</div>';
	}

	public function notices(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( file_exists( ABSPATH . 'llms.txt' ) ) {
			echo '<div class="notice notice-warning"><p>';
			echo esc_html( 'A physical llms.txt file is in the site root. The web server may serve that file instead of this plugin. Remove it to use the dynamic endpoint.' );
			echo '</p></div>';
		}
	}
}

new Admin();
