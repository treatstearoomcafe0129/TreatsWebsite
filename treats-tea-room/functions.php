<?php
/**
 * Treats Tea Room — theme bootstrap.
 *
 * Loads the theme's modules. Every module is a small, single-responsibility
 * file inside /inc so this bootstrap stays readable.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

define( 'TREATS_VERSION', '1.2.1' );
define( 'TREATS_DIR', trailingslashit( get_template_directory() ) );
define( 'TREATS_URI', trailingslashit( get_template_directory_uri() ) );

/**
 * Load a theme module from /inc.
 *
 * @param string $module Filename without extension.
 * @return void
 */
function treats_load_module( $module ) {
	$path = TREATS_DIR . 'inc/' . $module . '.php';

	if ( is_readable( $path ) ) {
		require_once $path;
	}
}

$treats_modules = array(
	'setup',          // Theme supports, menus, image sizes.
	'enqueue',        // Scripts, styles, fonts, preloading.
	'template-tags',  // Reusable presentation helpers.
	'post-types',     // Menu items, reviews, FAQs, bookings, vouchers.
	'meta-boxes',     // Editorial controls for the CPTs and pages.
	'customizer',     // Brand, contact, hours, social, integrations.
	'nav-walker',     // Accessible menu walker with mega-menu support.
	'seo',            // Titles, meta, Open Graph, canonical.
	'schema',         // JSON-LD structured data.
	'performance',    // Bloat removal, lazy loading, resource hints.
	'security',       // Hardening and security headers.
	'forms',          // AJAX handlers: booking, contact, newsletter, orders.
	'activation',     // First-run page/menu scaffolding.
	'compat-enfold',  // Strips leftover Avia shortcodes from old content.
	'menu-data',      // The printed menu, as data.
	'menu-import',    // Tools screen that loads it into WordPress.
);

foreach ( $treats_modules as $treats_module ) {
	treats_load_module( $treats_module );
}

unset( $treats_modules, $treats_module );
