<?php
/**
 * Cleaning up after a page builder.
 *
 * Enfold stores page content as Avia shortcodes — `[av_section]`,
 * `[av_slideshow_full …]` and so on — and those only mean anything while
 * Enfold is the active theme. Switch away and WordPress has no handler for
 * them, so it prints them verbatim: screenfuls of `[av_slide_full
 * slide_type='image' …]` where the page used to be.
 *
 * Enfold also escapes angle brackets inside its attributes as `###lt###` and
 * `###gt###`, which leak out as literal text in the same way.
 *
 * This strips both, on output only. The post content in the database is never
 * touched, so re-activating Enfold brings every page back exactly as it was —
 * which matters, because that is the rollback path.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Strip leftover page-builder markup from a string.
 *
 * Deliberately narrow: it matches only the `av_` / `avia_` prefixes Enfold
 * uses. Stripping every unregistered shortcode would eat shortcodes belonging
 * to plugins that register late, which is a much worse failure.
 *
 * @param string $content Post content.
 * @return string Content with the builder's markup removed.
 */
function treats_strip_builder_markup( $content ) {
	if ( ! is_string( $content ) || '' === $content ) {
		return $content;
	}

	// Cheap bail-out: most sites have none of this.
	if ( false === strpos( $content, '[av' ) && false === strpos( $content, '###lt###' ) ) {
		return $content;
	}

	// Opening and closing Avia shortcodes, with or without attributes.
	$content = preg_replace( '/\[\/?av(?:ia)?[a-z0-9_-]*\b[^\]]*\]/i', '', $content );

	// Enfold's escaped angle brackets, e.g. ###lt###h6###gt###.
	$content = preg_replace( '/###lt###\/?[a-z0-9]+[^#]*###gt###/i', '', $content );
	$content = str_replace( array( '###lt###', '###gt###' ), '', $content );

	// Collapse the runs of blank lines the stripping leaves behind.
	$content = preg_replace( "/(?:[ \t]*\R){3,}/", "\n\n", $content );

	return trim( $content );
}

/**
 * Filter post content on the way out.
 *
 * Runs at priority 8, ahead of `do_shortcode` at 11, so the markup is gone
 * before WordPress tries to make sense of it.
 *
 * @param string $content Post content.
 * @return string
 */
function treats_filter_builder_markup( $content ) {
	/**
	 * Filters whether leftover page-builder markup is stripped on output.
	 *
	 * Turn this off once the old content has been rewritten and the filter is
	 * no longer earning its place.
	 *
	 * @param bool $enabled Whether to strip.
	 */
	if ( ! apply_filters( 'treats_strip_builder_markup', true ) ) {
		return $content;
	}

	return treats_strip_builder_markup( $content );
}
add_filter( 'the_content', 'treats_filter_builder_markup', 8 );
add_filter( 'the_excerpt', 'treats_filter_builder_markup', 8 );
add_filter( 'get_the_excerpt', 'treats_filter_builder_markup', 8 );

/**
 * Keep stripped-empty content out of the automatic excerpt.
 *
 * A page that was nothing but builder markup has no words left once it is
 * cleaned, and an empty excerpt is better than an ellipsis on its own.
 *
 * @param string $excerpt Generated excerpt.
 * @return string
 */
function treats_clean_empty_excerpt( $excerpt ) {
	return ( '' === trim( wp_strip_all_tags( $excerpt ) ) ) ? '' : $excerpt;
}
add_filter( 'wp_trim_excerpt', 'treats_clean_empty_excerpt', 20 );
