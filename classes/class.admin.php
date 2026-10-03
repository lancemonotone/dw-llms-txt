<?php

/**
 * Options page UI, preview, and notices.
 *
 * @package Llms_Txt
 */

declare(strict_types=1);

namespace Llms_Txt;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Settings screen markup.
 */
final class Admin {

	public function __construct() {
		add_action(Plugin::hook('options_page'), array($this, 'render'));
		add_action('admin_notices', array($this, 'notices'));
	}

	/**
	 * Settings form and live document preview.
	 */
	public function render(): void {
		if (! current_user_can('manage_options')) {
			return;
		}

		if (isset($_GET['settings-updated'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- flag from options.php redirect.
			wp_cache_delete(Plugin::OPTION, 'options');
			wp_cache_delete('alloptions', 'options');
		}

		$config   = new Config();
		$catalog  = new Catalog();
		$settings = $config->all();

		$title            = esc_html(Plugin::DOCUMENT);
		$url              = esc_url(home_url(Plugin::path()));
		$body             = esc_textarea((new Document())->render());
		$menu_field_id    = esc_attr(Plugin::SLUG . '-menu-location');
		$types_field_id   = esc_attr(Plugin::SLUG . '-post-types');
		$tax_field_id     = esc_attr(Plugin::SLUG . '-taxonomies');
		$menu_options     = $this->menu_options_html($catalog->assigned_menu_locations(), $settings['menu_location']);
		$type_options     = $this->multi_options_html($catalog->post_types(), $settings['post_types']);
		$tax_options      = $this->multi_options_html($catalog->taxonomies(), $settings['taxonomies']);
		$types_empty      = $catalog->post_types() === array() ? '<p>' . esc_html('No public post types available.') . '</p>' : '';
		$tax_empty        = $catalog->taxonomies() === array() ? '<p>' . esc_html('No public taxonomies available.') . '</p>' : '';
		$sitemap_on       = $settings['include_sitemap'] ? ' checked="checked"' : '';
		$option_name      = esc_attr(Plugin::OPTION);
		$types_select     = $types_empty !== '' ? $types_empty : '<select name="' . $option_name . '[post_types][]" id="' . $types_field_id . '" multiple="multiple" size="6" class="regular-text">' . $type_options . '</select>';
		$tax_select       = $tax_empty !== '' ? $tax_empty : '<select name="' . $option_name . '[taxonomies][]" id="' . $tax_field_id . '" multiple="multiple" size="6" class="regular-text">' . $tax_options . '</select>';

		settings_errors(Plugin::OPTION);

		echo <<<HTML
<div class="wrap">
	<h1>{$title}</h1>
	<p><a href="{$url}">View public {$title}</a></p>

	<form method="post" action="options.php">
HTML;

		settings_fields(Plugin::GROUP);

		echo <<<HTML
		<input type="hidden" name="{$option_name}[configured]" value="1" />
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="{$menu_field_id}">Navigation menu</label></th>
				<td>
					<select name="{$option_name}[menu_location]" id="{$menu_field_id}">
						{$menu_options}
					</select>
					<p class="description">Theme menu location for the Navigation section. Leave as None to omit that section.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="{$types_field_id}">Content types</label></th>
				<td>
					{$types_select}
					<p class="description">Public post types with an archive URL. Hold Ctrl/Cmd to select multiple.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="{$tax_field_id}">Taxonomies</label></th>
				<td>
					{$tax_select}
					<p class="description">Public taxonomies. Each selected taxonomy becomes its own section of term links. Hold Ctrl/Cmd to select multiple.</p>
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

		submit_button('Save settings');

		echo <<<HTML
	</form>

	<h2>Preview</h2>
	<textarea readonly="readonly" rows="28" class="large-text code">{$body}</textarea>
</div>
HTML;
	}

	/**
	 * Select options for assigned menu locations.
	 *
	 * @param array<string, string> $menus   Location slug => label.
	 * @param string                $current Selected location slug.
	 * @return string HTML <option> list.
	 */
	private function menu_options_html(array $menus, string $current): string {
		$none_selected = $current === '' ? ' selected="selected"' : '';
		$html          = '<option value=""' . $none_selected . '>' . esc_html('None') . '</option>';

		foreach ($menus as $slug => $label) {
			$selected = $slug === $current ? ' selected="selected"' : '';
			$html    .= '<option value="' . esc_attr($slug) . '"' . $selected . '>' . esc_html($label . ' (' . $slug . ')') . '</option>';
		}

		return $html;
	}

	/**
	 * Options for a multi-select.
	 *
	 * @param array<string, string> $catalog  Slug => label.
	 * @param string[]              $selected Selected slugs.
	 * @return string HTML <option> list.
	 */
	private function multi_options_html(array $catalog, array $selected): string {
		$selected_map = array_fill_keys($selected, true);
		$html         = '';

		foreach ($catalog as $slug => $label) {
			$is_selected = isset($selected_map[$slug]) ? ' selected="selected"' : '';
			$html       .= '<option value="' . esc_attr($slug) . '"' . $is_selected . '>' . esc_html($label . ' (' . $slug . ')') . '</option>';
		}

		return $html;
	}

	/**
	 * Warn if a physical root document would shadow the plugin endpoint.
	 */
	public function notices(): void {
		if (! current_user_can('manage_options')) {
			return;
		}

		if (! file_exists(ABSPATH . Plugin::DOCUMENT)) {
			return;
		}

		$message = esc_html(
			sprintf(
				'A physical %s file is in the site root. The web server may serve that file instead of this plugin. Remove it to use the dynamic endpoint.',
				Plugin::DOCUMENT
			)
		);

		echo <<<HTML
<div class="notice notice-warning"><p>{$message}</p></div>
HTML;
	}
}

new Admin();
