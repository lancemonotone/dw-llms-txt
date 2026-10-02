<?php

/**
 * Site catalogs used by settings and document building.
 *
 * @package Llms_Txt
 */

declare(strict_types=1);

namespace Llms_Txt;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Discoverable post types, archives, and assigned menus.
 */
final class Catalog {

	/**
	 * Public post types with an archive URL (no page/attachment).
	 *
	 * @return array<string, string> Slug => label.
	 */
	public function post_types(): array {
		$objects = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		if (! is_array($objects)) {
			return array();
		}

		$out = array();

		foreach ($objects as $slug => $object) {
			if (! is_string($slug) || ! $object instanceof \WP_Post_Type) {
				continue;
			}
			if (in_array($slug, array('attachment', 'page'), true)) {
				continue;
			}
			if ($this->archive_url($slug, $object) === '') {
				continue;
			}

			$label      = isset($object->labels->name) ? (string) $object->labels->name : $slug;
			$out[$slug] = $label;
		}

		return $out;
	}

	/**
	 * Public archive URL for a post type, or empty when none.
	 *
	 * @param string             $post_type Post type slug.
	 * @param \WP_Post_Type|null $object    Optional post type object.
	 * @return string
	 */
	public function archive_url(string $post_type, ?\WP_Post_Type $object = null): string {
		if (! $object instanceof \WP_Post_Type) {
			$object = get_post_type_object($post_type);
		}

		if (! $object instanceof \WP_Post_Type) {
			return '';
		}

		if ($post_type === 'post') {
			$page_for_posts = (int) get_option('page_for_posts');
			if ($page_for_posts <= 0) {
				return '';
			}
			$permalink = get_permalink($page_for_posts);

			return is_string($permalink) ? $permalink : '';
		}

		if (empty($object->has_archive)) {
			return '';
		}

		$link = get_post_type_archive_link($post_type);

		return is_string($link) ? $link : '';
	}

	/**
	 * Theme menu locations that currently have a menu assigned.
	 *
	 * @return array<string, string> Location slug => label.
	 */
	public function assigned_menu_locations(): array {
		$locations  = get_nav_menu_locations();
		$registered = get_registered_nav_menus();

		if (! is_array($locations) || $locations === array()) {
			return array();
		}

		if (! is_array($registered)) {
			$registered = array();
		}

		$out = array();

		foreach ($locations as $slug => $menu_id) {
			if (! is_string($slug) || (int) $menu_id <= 0) {
				continue;
			}
			$label      = isset($registered[$slug]) ? (string) $registered[$slug] : $slug;
			$out[$slug] = $label;
		}

		return $out;
	}
}
