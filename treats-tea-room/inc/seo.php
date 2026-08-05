<?php
/**
 * On-page SEO: descriptions, canonicals, Open Graph, Twitter cards.
 *
 * All output is skipped when a dedicated SEO plugin (Yoast, Rank Math, SEO
 * Framework, AIOSEO) is active, so nothing is ever duplicated.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is a third-party SEO plugin handling meta output?
 *
 * @return bool
 */
function treats_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' )
		|| defined( 'RANK_MATH_VERSION' )
		|| defined( 'THE_SEO_FRAMEWORK_VERSION' )
		|| defined( 'AIOSEO_VERSION' )
		|| class_exists( 'SEOPress' );
}

/**
 * Description for the current view.
 *
 * @return string
 */
function treats_get_meta_description() {
	$description = '';

	if ( is_singular() ) {
		$post = get_queried_object();

		if ( $post instanceof WP_Post ) {
			$description = has_excerpt( $post ) ? get_the_excerpt( $post ) : '';

			if ( '' === $description ) {
				$description = wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) );
			}
		}
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			$description = $term->description;
		}
	} elseif ( is_post_type_archive() ) {
		$description = get_the_archive_description();
	}

	if ( '' === trim( (string) $description ) ) {
		$description = (string) get_theme_mod( 'treats_seo_description', get_bloginfo( 'description' ) );
	}

	$description = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $description ) ) );

	if ( mb_strlen( $description ) > 160 ) {
		$description = rtrim( mb_substr( $description, 0, 157 ), " ,.;:-" ) . '…';
	}

	return $description;
}

/**
 * Sharing image URL for the current view.
 *
 * @return string
 */
function treats_get_share_image() {
	if ( is_singular() && has_post_thumbnail() ) {
		$url = get_the_post_thumbnail_url( get_the_ID(), 'treats-hero' );

		if ( $url ) {
			return $url;
		}
	}

	$fallback = (string) get_theme_mod( 'treats_seo_share_image', '' );

	if ( '' !== $fallback ) {
		return $fallback;
	}

	$hero = (string) get_theme_mod( 'treats_hero_image', '' );

	return '' !== $hero ? $hero : TREATS_URI . 'images/social-default.png';
}

/**
 * Canonical URL for the current view.
 *
 * @return string
 */
function treats_get_canonical() {
	if ( is_front_page() ) {
		return home_url( '/' );
	}

	if ( is_singular() ) {
		return (string) get_permalink();
	}

	if ( is_tax() || is_category() || is_tag() ) {
		$link = get_term_link( get_queried_object() );

		return is_wp_error( $link ) ? home_url( '/' ) : $link;
	}

	if ( is_post_type_archive() ) {
		return (string) get_post_type_archive_link( get_post_type() );
	}

	if ( is_home() ) {
		$blog_id = (int) get_option( 'page_for_posts' );

		return $blog_id ? (string) get_permalink( $blog_id ) : home_url( '/' );
	}

	if ( is_search() ) {
		return get_search_link();
	}

	return home_url( add_query_arg( array() ) );
}

/**
 * Print SEO meta tags.
 *
 * @return void
 */
function treats_seo_meta() {
	if ( treats_seo_plugin_active() ) {
		return;
	}

	$description = treats_get_meta_description();
	$canonical   = treats_get_canonical();
	$image       = treats_get_share_image();
	$title       = wp_get_document_title();
	$type        = is_singular( 'post' ) ? 'article' : 'website';

	printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
	printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canonical ) );

	if ( is_search() || is_404() || is_paged() && is_search() ) {
		echo '<meta name="robots" content="noindex, follow">' . "\n";
	} else {
		echo '<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">' . "\n";
	}

	printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( $type ) );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $canonical ) );
	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( treats_get_business_name() ) );
	printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( str_replace( '-', '_', get_bloginfo( 'language' ) ) ) );

	if ( $image ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
		printf( '<meta property="og:image:alt" content="%s">' . "\n", esc_attr( $title ) );
	}

	if ( 'article' === $type ) {
		printf( '<meta property="article:published_time" content="%s">' . "\n", esc_attr( get_the_date( DATE_W3C ) ) );
		printf( '<meta property="article:modified_time" content="%s">' . "\n", esc_attr( get_the_modified_date( DATE_W3C ) ) );
	}

	printf( '<meta name="twitter:card" content="%s">' . "\n", $image ? 'summary_large_image' : 'summary' );
	printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
	printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $description ) );

	if ( $image ) {
		printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image ) );
	}

	printf( '<meta name="theme-color" content="%s" media="(prefers-color-scheme: light)">' . "\n", '#fdfcfa' );
	printf( '<meta name="theme-color" content="%s" media="(prefers-color-scheme: dark)">' . "\n", '#101310' );
	echo '<meta name="format-detection" content="telephone=no">' . "\n";
	printf( '<meta name="geo.placename" content="%s">' . "\n", esc_attr( treats_get_address()['locality'] ) );
	echo '<meta name="geo.region" content="GB-DUR">' . "\n";
}
add_action( 'wp_head', 'treats_seo_meta', 3 );

/**
 * Refine the document title separator and tagline handling.
 *
 * @param array<string,string> $parts Title parts.
 * @return array<string,string>
 */
function treats_document_title_parts( $parts ) {
	if ( treats_seo_plugin_active() ) {
		return $parts;
	}

	if ( is_front_page() ) {
		$parts['title'] = treats_get_business_name();

		if ( empty( $parts['tagline'] ) ) {
			$parts['tagline'] = __( 'Tea Room & Café in Durham', 'treats' );
		}
	}

	$parts['site'] = treats_get_business_name();

	return $parts;
}
add_filter( 'document_title_parts', 'treats_document_title_parts' );

/**
 * Use an en dash as the title separator.
 *
 * @return string
 */
function treats_document_title_separator() {
	return '·';
}
add_filter( 'document_title_separator', 'treats_document_title_separator' );

/**
 * Favicons and app icons when none are set in the Customizer.
 *
 * @return void
 */
function treats_favicons() {
	if ( has_site_icon() ) {
		return;
	}

	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( TREATS_URI . 'images/favicon.svg' ) );
	printf( '<link rel="icon" href="%s" sizes="192x192">' . "\n", esc_url( TREATS_URI . 'images/icon-192.png' ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( TREATS_URI . 'images/apple-touch-icon.png' ) );
	printf( '<link rel="mask-icon" href="%s" color="#6f9375">' . "\n", esc_url( TREATS_URI . 'images/favicon.svg' ) );
}
add_action( 'wp_head', 'treats_favicons', 4 );
