<?php
/**
 * Settings screen, preview, and notices.
 *
 * @package Llms_Txt
 */

declare(strict_types=1);

namespace Llms_Txt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings → llms.txt.
 */
final class Admin {

	private Options $options;

	public function __construct() {
		$this->options = new Options();

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

		if ( isset( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin notice flag from options.php redirect.
			wp_cache_delete( Options::OPTION, 'options' );
			wp_cache_delete( 'alloptions', 'options' );
			delete_transient( 'llms_txt_body' );
		}

		$settings = $this->options->all();
		$menus    = $this->options->assigned_menu_locations();
		$catalog  = $this->options->catalog_post_types();
		$selected = $settings['post_types'];

		if ( $selected === null ) {
			$selected = array_keys( $catalog );
		}

		$url  = esc_url( home_url( '/llms.txt' ) );
		$body = esc_textarea( ( new Document() )->render() );

		$menu_options = $this->menu_options_html( $menus, $settings['menu_location'] );
		$type_checks  = $this->post_type_checks_html( $catalog, $selected );
		$sitemap_on   = $settings['include_sitemap'] ? ' checked="checked"' : '';
		$option_name  = esc_attr( Options::OPTION );

		settings_errors( Options::OPTION );

		echo <<<HTML
<div class="wrap">
	<h1>llms.txt</h1>
	<p><a href="{$url}">View public llms.txt</a></p>

	<form method="post" action="options.php">
HTML;

		settings_fields( Settings::GROUP );

		echo <<<HTML
		<input type="hidden" name="{$option_name}[configured]" value="1" />
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="llms-txt-menu-location">Navigation menu</label></th>
				<td>
					<select name="{$option_name}[menu_location]" id="llms-txt-menu-location">
						{$menu_options}
					</select>
					<p class="description">Which assigned theme menu feeds the Navigation section. Auto picks the first preferred location (filterable).</p>
				</td>
			</tr>
			<tr>
				<th scope="row">Content types</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">Content types</legend>
						{$type_checks}
					</fieldset>
					<p class="description">Only types with a public archive URL are listed. Uncheck to omit from Content types.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">Optional</th>
				<td>
					<label>
						<input type="checkbox" name="{$option_name}[include_sitemap]" value="1"{$sitemap_on} />
						Link to WordPress sitemap index (<code>/wp-sitemap.xml</code>)
					</label>
				</td>
			</tr>
		</table>
HTML;

		submit_button( 'Save settings' );

		echo <<<HTML
	</form>

	<h2>Preview</h2>
	<p class="description">Updates after you save. Unchecking the sitemap or a listed content type should change this text.</p>
	<textarea readonly="readonly" rows="28" class="large-text code">{$body}</textarea>
</div>
HTML;
	}

	/**
	 * @param array<string, string> $menus
	 */
	private function menu_options_html( array $menus, string $current ): string {
		$auto_selected = $current === '' ? ' selected="selected"' : '';
		$html          = '<option value=""' . $auto_selected . '>' . esc_html( 'Auto (preferred locations)' ) . '</option>';

		foreach ( $menus as $slug => $label ) {
			$selected = $slug === $current ? ' selected="selected"' : '';
			$html    .= '<option value="' . esc_attr( $slug ) . '"' . $selected . '>' . esc_html( $label . ' (' . $slug . ')' ) . '</option>';
		}

		return $html;
	}

	/**
	 * @param array<string, string> $catalog
	 * @param list<string>          $selected
	 */
	private function post_type_checks_html( array $catalog, array $selected ): string {
		if ( $catalog === array() ) {
			return '<p>' . esc_html( 'No public post types available.' ) . '</p>';
		}

		$selected_map = array_fill_keys( $selected, true );
		$html         = '';

		foreach ( $catalog as $slug => $label ) {
			$checked = isset( $selected_map[ $slug ] ) ? ' checked="checked"' : '';
			$name    = esc_attr( Options::OPTION . '[post_types][]' );
			$html   .= '<label>';
			$html   .= '<input type="checkbox" name="' . $name . '" value="' . esc_attr( $slug ) . '"' . $checked . ' /> ';
			$html   .= esc_html( $label . ' (' . $slug . ')' );
			$html   .= '</label><br />';
		}

		return $html;
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
