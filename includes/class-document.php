<?php
/**
 * Builds the llms.txt markdown from WordPress content.
 *
 * @package DW\LlmsTxt
 */

namespace DW\LlmsTxt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Document {

	private const UPCOMING_LIMIT = 15;

	private const UPCOMING_WINDOW_DAYS = 90;

	public function render(): string {
		$sections = array(
			$this->intro(),
			$this->section( 'Pages', $this->pages() ),
			$this->section( 'Content types', $this->content_types() ),
			$this->section( 'Venue categories', $this->top_terms( 'venue_category' ) ),
			$this->section( 'Event categories', $this->top_terms( 'tribe_events_cat' ) ),
			$this->section( 'Featured venues', $this->featured_venues() ),
			$this->section( 'Upcoming events', $this->upcoming_events() ),
			$this->section( 'Trip Ideas', $this->trip_ideas() ),
			$this->section( 'Optional', $this->optional() ),
		);

		$sections = array_values( array_filter( $sections, static function ( string $section ): bool {
			return $section !== '';
		} ) );

		return implode( "\n\n", $sections ) . "\n";
	}

	private function intro(): string {
		$title = $this->escape_md( get_bloginfo( 'name' ) );
		$lines = array( '# ' . $title );
		$description = $this->site_description();

		if ( $description !== '' ) {
			$lines[] = '';
			$lines[] = '> ' . $this->escape_md( $description );
		}

		return implode( "\n", $lines );
	}

	private function site_description(): string {
		$titles = get_option( 'wpseo_titles' );

		if ( is_array( $titles ) && isset( $titles['metadesc-home-wpseo'] ) ) {
			$meta = trim( wp_strip_all_tags( (string) $titles['metadesc-home-wpseo'] ) );

			if ( $meta !== '' ) {
				return $meta;
			}
		}

		return trim( wp_strip_all_tags( (string) get_bloginfo( 'description' ) ) );
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
		$locations = get_nav_menu_locations();

		if ( empty( $locations['main-nav'] ) ) {
			return array();
		}

		$items = wp_get_nav_menu_items( (int) $locations['main-nav'] );

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
	 * @return array<int, string>
	 */
	private function content_types(): array {
		$lines = array();

		foreach ( array( 'tribe_venue', 'tribe_events', 'tribe_event_series', 'tribe_organizer' ) as $post_type ) {
			$line = $this->archive_line( $post_type );

			if ( $line !== null ) {
				$lines[] = $line;
			}
		}

		return $lines;
	}

	private function archive_line( string $post_type ): ?string {
		if ( ! post_type_exists( $post_type ) ) {
			return null;
		}

		$url = get_post_type_archive_link( $post_type );

		if ( ( ! is_string( $url ) || $url === '' ) && $post_type === 'tribe_events' && function_exists( 'tribe_get_events_link' ) ) {
			$url = tribe_get_events_link();
		}

		if ( ! is_string( $url ) || $url === '' ) {
			return null;
		}

		$object = get_post_type_object( $post_type );
		$label  = ( $object && isset( $object->labels->name ) ) ? (string) $object->labels->name : $post_type;

		return $this->link_line( $label, $url );
	}

	/**
	 * @return array<int, string>
	 */
	private function top_terms( string $taxonomy ): array {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'parent'     => 0,
				'hide_empty' => true,
			)
		);

		if ( ! is_array( $terms ) ) {
			return array();
		}

		$lines = array();

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$link = get_term_link( $term );

			if ( is_wp_error( $link ) || ! is_string( $link ) ) {
				continue;
			}

			$description = trim( wp_strip_all_tags( $term->description ) );
			$lines[]     = $this->link_line( $term->name, $link, $description );
		}

		return $lines;
	}

	/**
	 * @return array<int, string>
	 */
	private function featured_venues(): array {
		if ( ! function_exists( 'carbon_get_post_meta' ) || ! post_type_exists( 'tribe_venue' ) ) {
			return array();
		}

		$ids = get_posts(
			array(
				'post_type'              => 'tribe_venue',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( ! is_array( $ids ) ) {
			return array();
		}

		$lines = array();

		foreach ( $ids as $id ) {
			$id = (int) $id;

			if ( carbon_get_post_meta( $id, 'feature_venue' ) !== true ) {
				continue;
			}

			$url = get_permalink( $id );

			if ( ! is_string( $url ) || $url === '' ) {
				continue;
			}

			$lines[] = $this->link_line( get_the_title( $id ), $url, $this->excerpt( $id ) );
		}

		return $lines;
	}

	/**
	 * @return array<int, string>
	 */
	private function upcoming_events(): array {
		if ( ! function_exists( 'tribe_events' ) ) {
			return array();
		}

		$until  = wp_date( 'Y-m-d H:i:s', time() + ( self::UPCOMING_WINDOW_DAYS * DAY_IN_SECONDS ) );
		$events = tribe_events()
			->where( 'ends_after', 'now' )
			->where( 'starts_before', $until )
			->order_by( 'event_date', 'ASC' )
			->per_page( 80 )
			->all();

		if ( ! is_array( $events ) ) {
			return array();
		}

		$lines = array();
		$seen  = array();

		foreach ( $events as $event ) {
			if ( ! $event instanceof \WP_Post ) {
				continue;
			}

			$row = $this->upcoming_row( $event );

			if ( $row === null || isset( $seen[ $row['key'] ] ) ) {
				continue;
			}

			$seen[ $row['key'] ] = true;
			$lines[]             = $row['line'];

			if ( count( $lines ) >= self::UPCOMING_LIMIT ) {
				break;
			}
		}

		return $lines;
	}

	/**
	 * @return array{key: string, line: string}|null
	 */
	private function upcoming_row( \WP_Post $event ): ?array {
		$event_id = $this->event_post_id( $event );
		$series   = ( $event_id > 0 && function_exists( 'tec_event_series' ) ) ? tec_event_series( $event_id ) : null;
		$source   = $series instanceof \WP_Post ? $series : get_post( $event_id );

		if ( ! $source instanceof \WP_Post ) {
			$source = $event;
		}

		$url = get_permalink( $source );

		if ( ! is_string( $url ) || $url === '' ) {
			return null;
		}

		$key  = $series instanceof \WP_Post ? 'series-' . $series->ID : 'event-' . (int) $source->ID;
		$bits = array();
		$date = function_exists( 'tribe_get_start_date' ) ? tribe_get_start_date( $event, false, 'F j, Y' ) : '';

		if ( is_string( $date ) && $date !== '' ) {
			$bits[] = 'Next: ' . $this->escape_md( $date );
		}

		$venue_bit = $this->venue_bit( $event );

		if ( $venue_bit !== '' ) {
			$bits[] = $venue_bit;
		}

		$excerpt = $this->excerpt( (int) $source->ID );

		if ( $excerpt !== '' ) {
			$bits[] = $this->escape_md( $excerpt );
		}

		$line = '- [' . $this->escape_md( $source->post_title ) . '](' . $url . ')';

		if ( $bits !== array() ) {
			$line .= ': ' . implode( '. ', $bits );
		}

		return array(
			'key'  => $key,
			'line' => $line,
		);
	}

	private function event_post_id( \WP_Post $event ): int {
		$post_id = (int) $event->ID;

		if ( ! empty( $event->post_parent ) ) {
			$post_id = (int) $event->post_parent;
		}

		if ( has_filter( 'tec_events_custom_tables_v1_normalize_occurrence_id' ) ) {
			$post_id = (int) apply_filters( 'tec_events_custom_tables_v1_normalize_occurrence_id', $post_id );
		}

		return $post_id > 0 ? $post_id : (int) $event->ID;
	}

	private function venue_bit( \WP_Post $event ): string {
		if ( ! function_exists( 'tribe_get_venue_id' ) ) {
			return '';
		}

		$venue_id = (int) tribe_get_venue_id( $event->ID );

		if ( $venue_id <= 0 ) {
			$venue_id = (int) tribe_get_venue_id( $this->event_post_id( $event ) );
		}

		if ( $venue_id <= 0 ) {
			return '';
		}

		$venue_url = get_permalink( $venue_id );
		$name      = get_the_title( $venue_id );

		if ( ! is_string( $venue_url ) || $venue_url === '' || $name === '' ) {
			return '';
		}

		return 'Venue: [' . $this->escape_md( $name ) . '](' . $venue_url . ')';
	}

	/**
	 * @return array<int, string>
	 */
	private function trip_ideas(): array {
		$hub = get_page_by_path( 'trip-ideas', OBJECT, 'page' );

		if ( ! $hub instanceof \WP_Post || $hub->post_status !== 'publish' ) {
			return array();
		}

		$hub_url = get_permalink( $hub );

		if ( ! is_string( $hub_url ) || $hub_url === '' ) {
			return array();
		}

		$lines = array(
			$this->link_line( $hub->post_title, $hub_url, $this->excerpt( $hub->ID ) ),
		);
		$seen  = array( (int) $hub->ID => true );

		if ( ! preg_match_all( '/href\s*=\s*([\'"])([^\'"]+)\1/i', $hub->post_content, $matches ) ) {
			return $lines;
		}

		foreach ( $matches[2] as $href ) {
			$page = $this->page_from_href( (string) $href );

			if ( ! $page instanceof \WP_Post || isset( $seen[ $page->ID ] ) ) {
				continue;
			}

			$url = get_permalink( $page );

			if ( ! is_string( $url ) || $url === '' ) {
				continue;
			}

			$seen[ $page->ID ] = true;
			$lines[]           = $this->link_line( $page->post_title, $url, $this->excerpt( $page->ID ) );
		}

		return $lines;
	}

	private function page_from_href( string $href ): ?\WP_Post {
		$parts = wp_parse_url( $href );

		if ( ! is_array( $parts ) ) {
			return null;
		}

		$path = isset( $parts['path'] ) ? trim( (string) $parts['path'], '/' ) : '';

		if ( $path === '' ) {
			return null;
		}

		$page = get_page_by_path( $path, OBJECT, 'page' );

		if ( ! $page instanceof \WP_Post || $page->post_status !== 'publish' ) {
			return null;
		}

		return $page;
	}

	/**
	 * @return array<int, string>
	 */
	private function optional(): array {
		return array(
			$this->link_line( 'Sitemap index', home_url( '/sitemap_index.xml' ) ),
		);
	}
}
