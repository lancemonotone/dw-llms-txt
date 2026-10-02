<?php

/**
 * Builds the llms.txt markdown from WordPress content.
 *
 * @package Llms_Txt
 */

declare(strict_types=1);

namespace Llms_Txt;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Assembles the llms.txt document body.
 */
final class Document {

	private Config $config;

	private Catalog $catalog;

	public function __construct() {
		$this->config  = new Config();
		$this->catalog = new Catalog();
	}

	/**
	 * Full markdown document.
	 *
	 * @return string
	 */
	public function render(): string {
		$sections = array(
			$this->intro(),
			$this->section('Navigation', $this->navigation()),
			$this->section('Content types', $this->content_types()),
			$this->section('Optional', $this->optional()),
		);

		/**
		 * Filter document sections before they are joined.
		 *
		 * @param string[] $sections Markdown sections (empty strings are dropped).
		 */
		$sections = apply_filters('llms_txt_document_sections', $sections);

		if (! is_array($sections)) {
			$sections = array();
		}

		$sections = array_values(
			array_filter(
				$sections,
				static function ($section): bool {
					return is_string($section) && $section !== '';
				}
			)
		);

		return implode("\n\n", $sections) . "\n";
	}

	/**
	 * Site title and tagline.
	 *
	 * @return string
	 */
	private function intro(): string {
		$title       = $this->escape_md(get_bloginfo('name'));
		$lines       = array('# ' . $title);
		$description = trim(wp_strip_all_tags((string) get_bloginfo('description')));

		if ($description !== '') {
			$lines[] = '';
			$lines[] = '> ' . $this->escape_md($description);
		}

		return implode("\n", $lines);
	}

	/**
	 * Markdown ## section, or empty when there are no lines.
	 *
	 * @param string   $heading Section heading.
	 * @param string[] $lines   Bullet lines.
	 * @return string
	 */
	private function section(string $heading, array $lines): string {
		if ($lines === array()) {
			return '';
		}

		return '## ' . $heading . "\n" . implode("\n", $lines);
	}

	/**
	 * One markdown link bullet.
	 *
	 * @return string
	 */
	private function link_line(string $title, string $url, string $description = ''): string {
		$line = '- [' . $this->escape_md($title) . '](' . $url . ')';

		if ($description !== '') {
			$line .= ': ' . $this->escape_md($description);
		}

		return $line;
	}

	/**
	 * Escape text for markdown link labels.
	 *
	 * @return string
	 */
	private function escape_md(string $text): string {
		$text = wp_strip_all_tags($text);
		$text = preg_replace('/\s+/', ' ', $text);
		$text = is_string($text) ? $text : '';

		return trim(str_replace(array('\\', '[', ']'), array('\\\\', '\[', '\]'), $text));
	}

	/**
	 * Plain-text excerpt for a post, if any.
	 *
	 * @return string
	 */
	private function excerpt(int $post_id): string {
		$post = get_post($post_id);

		if (! $post instanceof \WP_Post) {
			return '';
		}

		return trim(wp_strip_all_tags($post->post_excerpt));
	}

	/**
	 * Navigation bullets from the configured menu.
	 *
	 * @return string[]
	 */
	private function navigation(): array {
		$menu_id = $this->primary_menu_id();

		if ($menu_id <= 0) {
			return array();
		}

		$items = wp_get_nav_menu_items($menu_id);

		if (! is_array($items)) {
			return array();
		}

		$lines = array();
		$seen  = array();

		foreach ($items as $item) {
			$row = $this->menu_item($item);

			if ($row === null || isset($seen[$row['url']])) {
				continue;
			}

			$seen[$row['url']] = true;
			$lines[]           = $this->link_line($row['title'], $row['url'], $row['description']);
		}

		return $lines;
	}

	/**
	 * Menu ID from settings, or first preferred theme location.
	 *
	 * @return int 0 when none.
	 */
	private function primary_menu_id(): int {
		$locations = get_nav_menu_locations();

		if (! is_array($locations) || $locations === array()) {
			return 0;
		}

		$chosen = $this->config->menu_location();

		if ($chosen !== '' && ! empty($locations[$chosen])) {
			return (int) $locations[$chosen];
		}

		/**
		 * Preferred theme_location slugs when menu location is Auto.
		 *
		 * @param string[] $slugs
		 */
		$preferred = apply_filters(
			'llms_txt_menu_locations',
			array('primary', 'main', 'main-nav', 'header', 'menu-1')
		);

		if (! is_array($preferred)) {
			$preferred = array();
		}

		foreach ($preferred as $slug) {
			if (! is_string($slug) || $slug === '') {
				continue;
			}
			if (! empty($locations[$slug])) {
				return (int) $locations[$slug];
			}
		}

		$first = reset($locations);

		return $first ? (int) $first : 0;
	}

	/**
	 * One nav item as a link row, or null when skipped.
	 *
	 * @param object $item Nav menu item.
	 * @return array{title: string, url: string, description: string}|null
	 */
	private function menu_item(object $item): ?array {
		$type  = isset($item->type) ? (string) $item->type : '';
		$title = isset($item->title) ? trim((string) $item->title) : '';
		$url   = isset($item->url) ? (string) $item->url : '';

		if ($type === 'post_type') {
			return $this->menu_post((int) $item->object_id, $title);
		}

		if ($type === 'post_type_archive') {
			$post_type = isset($item->object) ? (string) $item->object : '';
			$link      = $post_type !== '' ? get_post_type_archive_link($post_type) : false;

			if (! is_string($link) || $link === '') {
				return null;
			}

			$object = get_post_type_object($post_type);
			$label  = ($object && isset($object->labels->name)) ? (string) $object->labels->name : $post_type;

			return array(
				'title'       => $title !== '' ? $title : $label,
				'url'         => $link,
				'description' => '',
			);
		}

		if ($type === 'taxonomy') {
			$taxonomy = isset($item->object) ? (string) $item->object : '';
			$term     = get_term((int) $item->object_id, $taxonomy);

			if (! $term instanceof \WP_Term) {
				return null;
			}

			$link = get_term_link($term);

			if (is_wp_error($link) || ! is_string($link) || $link === '') {
				return null;
			}

			return array(
				'title'       => $title !== '' ? $title : $term->name,
				'url'         => $link,
				'description' => trim(wp_strip_all_tags($term->description)),
			);
		}

		if ($url === '') {
			return null;
		}

		if ($this->is_front_url($url)) {
			return array(
				'title'       => $title !== '' ? $title : get_bloginfo('name'),
				'url'         => home_url('/'),
				'description' => '',
			);
		}

		if (! $this->is_same_host($url)) {
			return null;
		}

		$resolved = url_to_postid($url);

		if ($resolved > 0) {
			$row = $this->menu_post($resolved, $title);

			if ($row !== null) {
				return $row;
			}
		}

		return array(
			'title'       => $title !== '' ? $title : $url,
			'url'         => $url,
			'description' => '',
		);
	}

	/**
	 * Published public post as a link row.
	 *
	 * @return array{title: string, url: string, description: string}|null
	 */
	private function menu_post(int $post_id, string $menu_title): ?array {
		$post = get_post($post_id);

		if (! $post instanceof \WP_Post || $post->post_status !== 'publish') {
			return null;
		}

		$object = get_post_type_object($post->post_type);

		if (! $object instanceof \WP_Post_Type || ! $object->public) {
			return null;
		}

		$permalink = get_permalink($post);

		if (! is_string($permalink) || $permalink === '') {
			return null;
		}

		return array(
			'title'       => $menu_title !== '' ? $menu_title : $post->post_title,
			'url'         => $permalink,
			'description' => $this->excerpt($post->ID),
		);
	}

	/**
	 * Whether the URL is the site front page.
	 *
	 * @return bool
	 */
	private function is_front_url(string $url): bool {
		return untrailingslashit($url) === untrailingslashit(home_url('/'));
	}

	/**
	 * Same host as the site, or a relative URL.
	 *
	 * @return bool
	 */
	private function is_same_host(string $url): bool {
		$parsed = wp_parse_url($url);

		if (! is_array($parsed) || empty($parsed['host'])) {
			return true;
		}

		$home = wp_parse_url(home_url('/'));

		if (! is_array($home) || empty($home['host'])) {
			return false;
		}

		return strtolower((string) $home['host']) === strtolower((string) $parsed['host']);
	}

	/**
	 * Content-type archive bullets for selected (or all eligible) types.
	 *
	 * @return string[]
	 */
	private function content_types(): array {
		$lines = array();

		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		if (! is_array($post_types)) {
			return array();
		}

		$allowed = $this->config->post_types();

		foreach ($post_types as $post_type => $object) {
			if (! is_string($post_type) || ! $object instanceof \WP_Post_Type) {
				continue;
			}

			if (in_array($post_type, array('attachment', 'page'), true)) {
				continue;
			}

			if (is_array($allowed) && ! in_array($post_type, $allowed, true)) {
				continue;
			}

			$line = $this->archive_line($post_type, $object);

			if ($line !== null) {
				$lines[] = $line;
			}
		}

		return $lines;
	}

	/**
	 * One archive link line, or null when there is no archive URL.
	 *
	 * @return string|null
	 */
	private function archive_line(string $post_type, \WP_Post_Type $object): ?string {
		$url = $this->catalog->archive_url($post_type, $object);

		if ($url === '') {
			return null;
		}

		$label = isset($object->labels->name) ? (string) $object->labels->name : $post_type;

		return $this->link_line($label, $url);
	}

	/**
	 * Optional section lines (sitemap when enabled).
	 *
	 * @return string[]
	 */
	private function optional(): array {
		if (! $this->config->include_sitemap()) {
			return array();
		}

		return array(
			$this->link_line('Sitemap index', home_url('/wp-sitemap.xml')),
		);
	}
}
