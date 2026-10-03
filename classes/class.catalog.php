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
 * Discoverable post types, taxonomies, archives, and assigned menus.
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
	 * Public taxonomies.
	 *
	 * @return array<string, string> Slug => label.
	 */
	public function taxonomies(): array {
		$objects = get_taxonomies(
			array(
				'public' => true,
			),
			'objects'
		);

		$out = array();

		foreach ($objects as $slug => $object) {
			if (! is_string($slug) || ! $object instanceof \WP_Taxonomy) {
				continue;
			}

			$label      = isset($object->labels->name) ? (string) $object->labels->name : $slug;
			$out[$slug] = $label;
		}

		return $out;
	}

	/**
	 * Non-empty terms for a taxonomy (name, url, description).
	 *
	 * @return array<int, array{title: string, url: string, description: string}>
	 */
	public function terms(string $taxonomy): array {
		if (! taxonomy_exists($taxonomy)) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			)
		);

		if (! is_array($terms)) {
			return array();
		}

		$out = array();

		foreach ($terms as $term) {
			if (! $term instanceof \WP_Term) {
				continue;
			}

			$link = get_term_link($term);

			if (is_wp_error($link) || ! is_string($link) || $link === '') {
				continue;
			}

			$out[] = array(
				'title'       => $term->name,
				'url'         => $link,
				'description' => trim(wp_strip_all_tags($term->description)),
			);
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

		if ($locations === array()) {
			return array();
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
