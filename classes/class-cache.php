<?php
/**
 * Transient cache for the rendered llms.txt body.
 *
 * @package LlmsTxt
 */

namespace LlmsTxt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Cache {

	private const KEY = 'llms_txt_body';

	public static function get(): string {
		$cached = get_transient( self::KEY );

		if ( is_string( $cached ) && $cached !== '' ) {
			return $cached;
		}

		$body = ( new Document() )->render();
		set_transient( self::KEY, $body, DAY_IN_SECONDS );

		return $body;
	}

	public static function forget(): void {
		delete_transient( self::KEY );
	}
}
