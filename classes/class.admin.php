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

		$url  = esc_url( home_url( '/llms.txt' ) );
		$body = esc_textarea( ( new Document() )->render() );

		echo <<<HTML
<div class="wrap">
	<h1>llms.txt</h1>
	<p><a href="{$url}">View public llms.txt</a></p>
	<textarea readonly="readonly" rows="28" class="large-text code">{$body}</textarea>
</div>
HTML;
	}

	public function notices(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! file_exists( ABSPATH . 'llms.txt' ) ) {
			return;
		}

		$message = esc_html(
			'A physical llms.txt file is in the site root. The web server may serve that file instead of this plugin. Remove it to use the dynamic endpoint.'
		);

		echo <<<HTML
<div class="notice notice-warning"><p>{$message}</p></div>
HTML;
	}
}

new Admin();
