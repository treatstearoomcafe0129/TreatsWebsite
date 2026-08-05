<?php
/**
 * Theme setup: supports, menus, image sizes, editor styles.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register theme features.
 *
 * @return void
 */
function treats_setup() {
	load_theme_textdomain( 'treats', TREATS_DIR . 'languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'appearance-tools' );

	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);

	add_theme_support(
		'custom-logo',
		array(
			'height'               => 96,
			'width'                => 320,
			'flex-height'          => true,
			'flex-width'           => true,
			'unlink-homepage-logo' => true,
		)
	);

	add_theme_support(
		'custom-background',
		array( 'default-color' => 'fdfcfa' )
	);

	// The palette is driven by CSS custom properties; mirror it for the editor.
	add_theme_support( 'editor-color-palette', treats_editor_palette() );
	add_theme_support( 'editor-font-sizes', treats_editor_font_sizes() );
	add_theme_support( 'disable-custom-gradients' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary Navigation', 'treats' ),
			'menus'   => __( 'Menus Dropdown', 'treats' ),
			'footer'  => __( 'Footer Navigation', 'treats' ),
			'legal'   => __( 'Legal Links', 'treats' ),
			'social'  => __( 'Social Links', 'treats' ),
		)
	);

	// Hero and card crops. Sized for 2x delivery on common breakpoints.
	add_image_size( 'treats-hero', 2400, 1350, true );
	add_image_size( 'treats-hero-mobile', 1080, 1440, true );
	add_image_size( 'treats-card', 900, 675, true );
	add_image_size( 'treats-card-tall', 900, 1200, true );
	add_image_size( 'treats-square', 800, 800, true );
	add_image_size( 'treats-thumb', 320, 240, true );

	add_editor_style( 'css/editor.css' );

	set_post_thumbnail_size( 900, 675, true );

	// Content width used by oEmbed and wide images.
	if ( ! isset( $GLOBALS['content_width'] ) ) {
		$GLOBALS['content_width'] = 760;
	}
}
add_action( 'after_setup_theme', 'treats_setup' );

/**
 * Brand palette exposed to the block editor.
 *
 * @return array<int,array<string,string>>
 */
function treats_editor_palette() {
	return array(
		array(
			'name'  => __( 'Sage', 'treats' ),
			'slug'  => 'sage',
			'color' => '#6f9375',
		),
		array(
			'name'  => __( 'Sage Deep', 'treats' ),
			'slug'  => 'sage-deep',
			'color' => '#35493a',
		),
		array(
			'name'  => __( 'Sage Mist', 'treats' ),
			'slug'  => 'sage-mist',
			'color' => '#e8efe8',
		),
		array(
			'name'  => __( 'Cream', 'treats' ),
			'slug'  => 'cream',
			'color' => '#f7f4ef',
		),
		array(
			'name'  => __( 'Brass', 'treats' ),
			'slug'  => 'brass',
			'color' => '#b08d57',
		),
		array(
			'name'  => __( 'Ink', 'treats' ),
			'slug'  => 'ink',
			'color' => '#1a1d1a',
		),
		array(
			'name'  => __( 'White', 'treats' ),
			'slug'  => 'white',
			'color' => '#ffffff',
		),
	);
}

/**
 * Font sizes exposed to the block editor.
 *
 * @return array<int,array<string,mixed>>
 */
function treats_editor_font_sizes() {
	return array(
		array(
			'name' => __( 'Small', 'treats' ),
			'slug' => 'small',
			'size' => 14,
		),
		array(
			'name' => __( 'Normal', 'treats' ),
			'slug' => 'normal',
			'size' => 17,
		),
		array(
			'name' => __( 'Medium', 'treats' ),
			'slug' => 'medium',
			'size' => 21,
		),
		array(
			'name' => __( 'Large', 'treats' ),
			'slug' => 'large',
			'size' => 30,
		),
		array(
			'name' => __( 'Display', 'treats' ),
			'slug' => 'display',
			'size' => 48,
		),
	);
}

/**
 * Register widget areas.
 *
 * @return void
 */
function treats_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Sidebar', 'treats' ),
			'id'            => 'sidebar-1',
			'description'   => __( 'Shown beside blog posts and archives.', 'treats' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget__title">',
			'after_title'   => '</h2>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'Footer Extras', 'treats' ),
			'id'            => 'footer-1',
			'description'   => __( 'Optional widgets in the footer, above the legal bar.', 'treats' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget__title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'treats_widgets_init' );

/**
 * Body classes that drive layout and theming.
 *
 * @param array<int,string> $classes Existing classes.
 * @return array<int,string>
 */
function treats_body_classes( $classes ) {
	$classes[] = 'treats';

	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	if ( is_front_page() ) {
		$classes[] = 'is-front';
	}

	if ( is_page_template( 'page-templates/template-menu.php' ) ) {
		$classes[] = 'is-menu-page';
	}

	if ( ! has_nav_menu( 'primary' ) ) {
		$classes[] = 'no-primary-menu';
	}

	return $classes;
}
add_filter( 'body_class', 'treats_body_classes' );

/**
 * Add a pingback header on singular views.
 *
 * @return void
 */
function treats_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">' . "\n", esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'treats_pingback_header' );

/**
 * Trim the excerpt to a length that suits the card design.
 *
 * @param int $length Default length.
 * @return int
 */
function treats_excerpt_length( $length ) {
	return is_admin() ? $length : 28;
}
add_filter( 'excerpt_length', 'treats_excerpt_length' );

/**
 * Replace the excerpt ellipsis.
 *
 * @param string $more Default string.
 * @return string
 */
function treats_excerpt_more( $more ) {
	return is_admin() ? $more : '&hellip;';
}
add_filter( 'excerpt_more', 'treats_excerpt_more' );
