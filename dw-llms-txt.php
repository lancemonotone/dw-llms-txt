<?php
/**
 * Plugin Name: D|W llms.txt
 * Description: Serves a dynamic llms.txt map of Destination Williamstown content.
 * Version:     1.0.0
 * Author:      Rus Miller
 *
 * @package DW\LlmsTxt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DW_LLMS_TXT_VERSION', '1.0.0' );
define( 'DW_LLMS_TXT_FILE', __FILE__ );
define( 'DW_LLMS_TXT_PATH', plugin_dir_path( __FILE__ ) );

require_once DW_LLMS_TXT_PATH . 'includes/class-document.php';
require_once DW_LLMS_TXT_PATH . 'includes/class-cache.php';
require_once DW_LLMS_TXT_PATH . 'includes/class-endpoint.php';
require_once DW_LLMS_TXT_PATH . 'includes/class-admin.php';
require_once DW_LLMS_TXT_PATH . 'includes/class-plugin.php';

register_activation_hook(
	__FILE__,
	static function () {
		\DW\LlmsTxt\Endpoint::register_rewrite();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		flush_rewrite_rules();
	}
);

\DW\LlmsTxt\Plugin::instance();
