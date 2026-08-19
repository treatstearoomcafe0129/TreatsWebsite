<?php
/**
 * Performance.
 *
 * Removes the parts of core output this site does not use, and adds the
 * loading hints that move the needle on Core Web Vitals.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Strip unused head output.
 *
 * @return void
 */
function treats_clean_head() {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
	remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
	remove_action( 'template_redirect', 'rest_output_link_header', 11 );

	// Emoji support costs ~15KB and a DNS lookup for a feature this site never uses.
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'treats_clean_head' );

/**
 * Drop the emoji TinyMCE plugin.
 *
 * @param array<int,string> $plugins Plugin list.
 * @return array<int,string>
 */
function treats_disable_emoji_tinymce( $plugins ) {
	return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
}
add_filter( 'tiny_mce_plugins', 'treats_disable_emoji_tinymce' );

/**
 * Remove the s.w.org prefetch hint left behind by emoji support.
 *
 * @param array<int,string> $urls          Hint URLs.
 * @param string            $relation_type Hint type.
 * @return array<int,string>
 */
function treats_remove_emoji_dns_prefetch( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$urls = array_filter(
			$urls,
			static function ( $url ) {
				return false === strpos( (string) $url, 's.w.org' );
			}
		);
	}

	return $urls;
}
add_filter( 'wp_resource_hints', 'treats_remove_emoji_dns_prefetch', 10, 2 );

/**
 * Dequeue the core block library CSS on the front end.
 *
 * The theme styles blocks itself; loading both ships ~90KB of dead CSS.
 * Sites that rely on core block styling can disable this with the filter.
 *
 * @return void
 */
function treats_dequeue_block_library() {
	if ( is_admin() ) {
		return;
	}

	/**
	 * Filter whether core block CSS is removed.
	 *
	 * @param bool $remove Default true.
	 */
	if ( ! apply_filters( 'treats_remove_core_block_css', true ) ) {
		return;
	}

	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'global-styles' );
}
add_action( 'wp_enqueue_scripts', 'treats_dequeue_block_library', 100 );

/**
 * Remove the jQuery Migrate shim.
 *
 * @param WP_Scripts $scripts Scripts registry.
 * @return void
 */
function treats_remove_jquery_migrate( $scripts ) {
	if ( is_admin() || ! isset( $scripts->registered['jquery'] ) ) {
		return;
	}

	$scripts->registered['jquery']->deps = array_diff(
		$scripts->registered['jquery']->deps,
		array( 'jquery-migrate' )
	);
}
add_action( 'wp_default_scripts', 'treats_remove_jquery_migrate' );

/**
 * Give the first in-content image priority and stop lazy-loading it.
 *
 * WordPress lazy-loads everything by default, which can delay LCP. This
 * promotes the first image on singular views instead.
 *
 * @param array<string,string> $attr       Image attributes.
 * @param WP_Post              $attachment Attachment.
 * @param string|array         $size       Requested size.
 * @return array<string,string>
 */
function treats_priority_first_image( $attr, $attachment, $size ) {
	static $seen = false;

	if ( is_admin() || $seen || ! is_singular() ) {
		return $attr;
	}

	$seen = true;

	$attr['loading']       = 'eager';
	$attr['fetchpriority'] = 'high';
	$attr['decoding']      = 'async';

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'treats_priority_first_image', 10, 3 );

/**
 * Preconnect only to hosts the site genuinely uses.
 *
 * @param array<int,mixed> $urls          Hint URLs.
 * @param string           $relation_type Hint type.
 * @return array<int,mixed>
 */
function treats_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' !== $relation_type ) {
		return $urls;
	}

	// The Google Maps embed is deferred until interaction, but preconnecting
	// shaves the handshake off that first click.
	$urls[] = array(
		'href'        => 'https://www.google.com',
		'crossorigin' => '',
	);

	if ( get_theme_mod( 'treats_instagram_token' ) ) {
		$urls[] = array(
			'href'        => 'https://scontent.cdninstagram.com',
			'crossorigin' => '',
		);
	}

	return $urls;
}
add_filter( 'wp_resource_hints', 'treats_resource_hints', 10, 2 );

/**
 * Serve a leaner oEmbed footprint.
 *
 * @return void
 */
function treats_disable_embeds() {
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	add_filter( 'embed_oembed_discover', '__return_false' );
}
add_action( 'init', 'treats_disable_embeds', 9999 );

/**
 * Cache the Instagram feed response for an hour.
 *
 * @return array<int,array<string,string>>
 */
function treats_get_instagram_feed() {
	$token = trim( (string) get_theme_mod( 'treats_instagram_token', '' ) );
	$count = (int) get_theme_mod( 'treats_instagram_count', 6 );

	if ( '' === $token ) {
		return array();
	}

	$cache_key = 'treats_instagram_' . md5( $token . '|' . $count );
	$cached    = get_transient( $cache_key );

	if ( false !== $cached ) {
		return (array) $cached;
	}

	$response = wp_remote_get(
		add_query_arg(
			array(
				'fields'       => 'id,caption,media_type,media_url,permalink,thumbnail_url',
				'limit'        => $count,
				'access_token' => $token,
			),
			'https://graph.instagram.com/me/media'
		),
		array( 'timeout' => 8 )
	);

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		// Cache the failure briefly so a broken token cannot slow every request.
		set_transient( $cache_key, array(), 10 * MINUTE_IN_SECONDS );

		return array();
	}

	$body  = json_decode( wp_remote_retrieve_body( $response ), true );
	$posts = array();

	if ( ! empty( $body['data'] ) && is_array( $body['data'] ) ) {
		foreach ( $body['data'] as $post ) {
			$image = ( isset( $post['media_type'] ) && 'VIDEO' === $post['media_type'] && ! empty( $post['thumbnail_url'] ) )
				? $post['thumbnail_url']
				: ( $post['media_url'] ?? '' );

			if ( '' === $image ) {
				continue;
			}

			$posts[] = array(
				'image'   => esc_url_raw( $image ),
				'link'    => esc_url_raw( $post['permalink'] ?? '' ),
				'caption' => sanitize_text_field( wp_html_excerpt( $post['caption'] ?? '', 120, '…' ) ),
			);
		}
	}

	set_transient( $cache_key, $posts, HOUR_IN_SECONDS );

	return $posts;
}

/**
 * Bust the template URL cache when pages change.
 *
 * @return void
 */
function treats_flush_template_cache() {
	wp_cache_flush_group( 'treats' );
}
add_action( 'save_post_page', 'treats_flush_template_cache' );

/**
 * Fallback for object caches without group flushing.
 *
 * @return void
 */
if ( ! function_exists( 'wp_cache_flush_group' ) ) {
	/**
	 * Flush a cache group.
	 *
	 * @param string $group Group name.
	 * @return bool
	 */
	function wp_cache_flush_group( $group ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		return false;
	}
}
