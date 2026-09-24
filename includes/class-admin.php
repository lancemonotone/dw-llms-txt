<?php
/**
 * Preview screen and editor notices.
 *
 * @package DW\LlmsTxt
 */

namespace DW\LlmsTxt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_notices', array( $this, 'notices' ) );
	}

	public function menu(): void {
		add_options_page(
			'llms.txt',
			'llms.txt',
			'manage_options',
			'dw-llms-txt',
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
			echo esc_html( 'A physical llms.txt file is in the site root. The web server will serve that file instead of this plugin. Remove it after Yoast llms.txt is turned off.' );
			echo '</p></div>';
		}

		if ( $this->yoast_llms_enabled() ) {
			echo '<div class="notice notice-warning"><p>';
			echo esc_html( 'Yoast llms.txt is still enabled. Turn it off in Yoast SEO so it does not write a new root llms.txt file.' );
			echo '</p></div>';
		}
	}

	private function yoast_llms_enabled(): bool {
		$option = get_option( 'wpseo_llmstxt' );

		if ( ! is_array( $option ) || ! array_key_exists( 'enable_llms_txt', $option ) ) {
			return false;
		}

		return filter_var( $option['enable_llms_txt'], FILTER_VALIDATE_BOOLEAN );
	}
}
