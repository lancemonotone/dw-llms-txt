<?php
/**
 * Builds the llms.txt markdown from WordPress content.
 *
 * @package Llms_Txt
 */

declare(strict_types=1);

namespace Llms_Txt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content → markdown body.
 */
final class Document {

	public function render(): string {
		$sections = array(
			$this->intro(),
			$this->section( 'Navigation', $this->navigation() ),
			$this->section( 'Content types', $this->content_types() ),
			$this->section( 'Optional', $this->optional() ),
		);

		/**
		 * Filter document sections before join.
		 *
		 * @param list<string> $sections Markdown sections (may be empty strings).
		 */
		$sections = apply_filters( 'llms_txt_document_sections', $sections );

		if ( ! is_array( $sections ) ) {
			$sections = array();
		}

		$sections = array_values(
			array_filter(
				$sections,
				static function ( $section ): bool {
					return is_string( $section ) && $section !== '';
				}
			)
		);

		return implode( "\n\n", $sections ) . "\n";
	}

	private function intro(): string {
		$title       = $this->escape_md( get_bloginfo( 'name' ) );
		$lines       = array( '# ' . $title );
		$description = trim( wp_strip_all_tags( (string) get_bloginfo( 'description' ) ) );

		if ( $description !== '' ) {
			$lines[] = '';
			$lines[] = '> ' . $this->escape_md( $description );
		}

		return implode( "\n", $lines );
	}

	/**
	 * @param array<int, string> $lines
	 */
	private function section( string $heading, array $lines ): string {
		if ( $lines === array() ) {
			return '';
		}

		return '## ' . $heading . "\n" . implode( "\n", $lines );
	}

	private function link_line( string $title, string $url, string $description = '' ): string {
		$line = '- [' . $this->escape_md( $title ) . '](' . $url . ')';

		if ( $description !== '' ) {
			$line .= ': ' . $this->escape_md( $description );
		}

		return $line;
	}

	private function escape_md( string $text ): string {
		$text = wp_strip_all_tags( $text );
		$text = preg_replace( '/\s+/', ' ', $text );
		$text = is_string( $text ) ? $text : '';

		return trim( str_replace( array( '\\', '[', ']' ), array( '\\\\', '\[', '\]' ), $text ) );
	}

	private function excerpt( int $post_id ): string {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return '';
		}

		return trim( wp_strip_all_tags( $post->post_excerpt ) );
	}

	/**
	 * Primary menu destinations: pages, public CPTs, archives, terms, same-host custom links.
	 *
	 * @return array<int, string>
	 */
	private function navigation(): array {
		$menu_id = $this->primary_menu_id();

		if ( $menu_id <= 0 ) {
			return array();
		}

		$items = wp_get_nav_menu_items( $menu_id );

		if ( ! is_array( $items ) ) {
			return array();
		}

		$lines = array();
		$seen  = array();

		foreach ( $items as $item ) {
			$row = $this->menu_item( $item );

			if ( $row === null || isset( $seen[ $row['url'] ] ) ) {
				continue;
			}

			$seen[ $row['url'] ] = true;
			$lines[]             = $this->link_line( $row['title'], $row['url'], $row['description'] );
		}

		return $lines;
	}

	private function primary_menu_id(): int {
		$locations = get_nav_menu_locations();

		if ( ! is_array( $locations ) || $locations === array() ) {
			return 0;
		}

		/**
		 * Preferred theme_location slugs for the Navigation section.
		 *
		 * @param list<string> $slugs
		 */
		$preferred = apply_filters(
			'llms_txt_menu_locations',
			array( 'primary', 'main', 'main-nav', 'header', 'menu-1' )
		);

		if ( ! is_array( $preferred ) ) {
			$preferred = array();
		}

		foreach ( $preferred as $slug ) {
			if ( ! is_string( $slug ) || $slug === '' ) {
				continue;
			}
			if ( ! empty( $locations[ $slug ] ) ) {
				return (int) $locations[ $slug ];
			}
		}

		$first = reset( $locations );

		return $first ? (int) $first : 0;
	}

	/**
	 * @param object $item Nav menu item.
	 * @return array{title: string, url: string, description: string}|null
	 */
	private function menu_item( object $item ): ?array {
		$type  = isset( $item->type ) ? (string) $item->type : '';
		$title = isset( $item->title ) ? trim( (string) $item->title ) : '';
		$url   = isset( $item->url ) ? (string) $item->url : '';

		if ( $type === 'post_type' ) {
			return $this->menu_post( (int) $item->object_id, $title );
		}

		if ( $type === 'post_type_archive' ) {
			$post_type = isset( $item->object ) ? (string) $item->object : '';
			$link      = $post_type !== '' ? get_post_type_archive_link( $post_type ) : false;

			if ( ! is_string( $link ) || $link === '' ) {
				return null;
			}

			$object = get_post_type_object( $post_type );
			$label  = ( $object && isset( $object->labels->name ) ) ? (string) $object->labels->name : $post_type;

			return array(
				'title'       => $title !== '' ? $title : $label,
				'url'         => $link,
				'description' => '',
			);
		}

		if ( $type === 'taxonomy' ) {
			$taxonomy = isset( $item->object ) ? (string) $item->object : '';
			$term     = get_term( (int) $item->object_id, $taxonomy );

			if ( ! $term instanceof \WP_Term ) {
				return null;
			}

			$link = get_term_link( $term );

			if ( is_wp_error( $link ) || ! is_string( $link ) || $link === '' ) {
				return null;
			}

			return array(
				'title'       => $title !== '' ? $title : $term->name,
				'url'         => $link,
				'description' => trim( wp_strip_all_tags( $term->description ) ),
			);
		}

		if ( $url === '' ) {
			return null;
		}

		if ( $this->is_front_url( $url ) ) {
			return array(
				'title'       => $title !== '' ? $title : get_bloginfo( 'name' ),
				'url'         => home_url( '/' ),
				'description' => '',
			);
		}

		if ( ! $this->is_same_host( $url ) ) {
			return null;
		}

		$resolved = url_to_postid( $url );

		if ( $resolved > 0 ) {
			$row = $this->menu_post( $resolved, $title );

			if ( $row !== null ) {
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
	 * @return array{title: string, url: string, description: string}|null
	 */
	private function menu_post( int $post_id, string $menu_title ): ?array {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post || $post->post_status !== 'publish' ) {
			return null;
		}

		$object = get_post_type_object( $post->post_type );

		if ( ! $object instanceof \WP_Post_Type || ! $object->public ) {
			return null;
		}

		$permalink = get_permalink( $post );

		if ( ! is_string( $permalink ) || $permalink === '' ) {
			return null;
		}

		return array(
			'title'       => $menu_title !== '' ? $menu_title : $post->post_title,
			'url'         => $permalink,
			'description' => $this->excerpt( $post->ID ),
		);
	}

	private function is_front_url( string $url ): bool {
		return untrailingslashit( $url ) === untrailingslashit( home_url( '/' ) );
	}

	private function is_same_host( string $url ): bool {
		$parsed = wp_parse_url( $url );

		if ( ! is_array( $parsed ) || empty( $parsed['host'] ) ) {
			return true;
		}

		$home = wp_parse_url( home_url( '/' ) );

		if ( ! is_array( $home ) || empty( $home['host'] ) ) {
			return false;
		}

		return strtolower( (string) $home['host'] ) === strtolower( (string) $parsed['host'] );
	}

	/**
	 * Public post types with archives (plus posts when a Posts page is set).
	 *
	 * @return array<int, string>
	 */
	private function content_types(): array {
		$lines = array();

		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		if ( ! is_array( $post_types ) ) {
			return array();
		}

		foreach ( $post_types as $post_type => $object ) {
			if ( ! is_string( $post_type ) || ! $object instanceof \WP_Post_Type ) {
				continue;
			}

			if ( in_array( $post_type, array( 'attachment', 'page' ), true ) ) {
				continue;
			}

			$line = $this->archive_line( $post_type, $object );

			if ( $line !== null ) {
				$lines[] = $line;
			}
		}

		return $lines;
	}

	private function archive_line( string $post_type, \WP_Post_Type $object ): ?string {
		$url = '';

		if ( $post_type === 'post' ) {
			$page_for_posts = (int) get_option( 'page_for_posts' );
			if ( $page_for_posts > 0 ) {
				$permalink = get_permalink( $page_for_posts );
				$url       = is_string( $permalink ) ? $permalink : '';
			}
		} elseif ( ! empty( $object->has_archive ) ) {
			$link = get_post_type_archive_link( $post_type );
			$url  = is_string( $link ) ? $link : '';
		}

		if ( $url === '' ) {
			return null;
		}

		$label = isset( $object->labels->name ) ? (string) $object->labels->name : $post_type;

		return $this->link_line( $label, $url );
	}

	/**
	 * @return array<int, string>
	 */
	private function optional(): array {
		return array(
			$this->link_line( 'Sitemap index', home_url( '/wp-sitemap.xml' ) ),
		);
	}
}
