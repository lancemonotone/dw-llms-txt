<?php
/**
 * Builds the llms.txt markdown from WordPress content.
 *
 * @package LlmsTxt
 */

namespace LlmsTxt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Document {

	public function render(): string {
		$sections = array(
			$this->intro(),
			$this->section( 'Pages', $this->pages() ),
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
	 * @return array<int, string>
	 */
	private function pages(): array {
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
			$row = $this->menu_page( $item );

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
		 * Preferred theme_location slugs for the Pages section.
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
	private function menu_page( object $item ): ?array {
		$url = isset( $item->url ) ? (string) $item->url : '';

		if ( $url === '' ) {
			return null;
		}

		$page_id = 0;

		if ( ( $item->type ?? '' ) === 'post_type' && ( $item->object ?? '' ) === 'page' ) {
			$page_id = (int) $item->object_id;
		} else {
			$resolved = url_to_postid( $url );

			if ( $resolved && get_post_type( $resolved ) === 'page' ) {
				$page_id = $resolved;
			}
		}

		if ( $page_id > 0 ) {
			$page = get_post( $page_id );

			if ( ! $page instanceof \WP_Post || $page->post_status !== 'publish' ) {
				return null;
			}

			$permalink = get_permalink( $page );

			if ( ! is_string( $permalink ) || $permalink === '' ) {
				return null;
			}

			$title = isset( $item->title ) ? trim( (string) $item->title ) : '';

			return array(
				'title'       => $title !== '' ? $title : $page->post_title,
				'url'         => $permalink,
				'description' => $this->excerpt( $page->ID ),
			);
		}

		if ( ! $this->is_front_url( $url ) ) {
			return null;
		}

		$title = isset( $item->title ) ? trim( (string) $item->title ) : '';

		return array(
			'title'       => $title !== '' ? $title : get_bloginfo( 'name' ),
			'url'         => home_url( '/' ),
			'description' => '',
		);
	}

	private function is_front_url( string $url ): bool {
		return untrailingslashit( $url ) === untrailingslashit( home_url( '/' ) );
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
