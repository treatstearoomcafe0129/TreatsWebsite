<?php
/**
 * Asset loading.
 *
 * Styles and scripts are split so that a first-time visitor on the home page
 * downloads only what that page needs. Fonts are self-hosted and preloaded.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cache-busting version for a theme asset, based on file modification time.
 *
 * @param string $relative_path Path relative to the theme root.
 * @return string
 */
function treats_asset_version( $relative_path ) {
	$file = TREATS_DIR . ltrim( $relative_path, '/' );

	if ( file_exists( $file ) ) {
		return (string) filemtime( $file );
	}

	return TREATS_VERSION;
}

/**
 * Does the current view need the interactive menu bundle?
 *
 * @return bool
 */
function treats_needs_menu_assets() {
	return is_page_template( 'page-templates/template-menu.php' )
		|| is_post_type_archive( 'treats_menu_item' )
		|| is_tax( 'treats_menu_category' )
		|| is_singular( 'treats_menu_item' )
		|| is_front_page();
}

/**
 * Does the current view need the form bundle?
 *
 * @return bool
 */
function treats_needs_form_assets() {
	$templates = array(
		'page-templates/template-booking.php',
		'page-templates/template-contact.php',
		'page-templates/template-vouchers.php',
	);

	foreach ( $templates as $template ) {
		if ( is_page_template( $template ) ) {
			return true;
		}
	}

	// The newsletter block sits in the footer on every page.
	return true;
}

/**
 * Enqueue front-end assets.
 *
 * @return void
 */
function treats_enqueue_assets() {
	// Theme stylesheet declaration (kept tiny; real styles are in /css).
	wp_enqueue_style( 'treats-style', get_stylesheet_uri(), array(), treats_asset_version( 'style.css' ) );

	wp_enqueue_style( 'treats-main', TREATS_URI . 'css/main.css', array( 'treats-style' ), treats_asset_version( 'css/main.css' ) );

	if ( treats_needs_menu_assets() ) {
		wp_enqueue_style( 'treats-menu', TREATS_URI . 'css/menu.css', array( 'treats-main' ), treats_asset_version( 'css/menu.css' ) );
	}

	if ( treats_needs_form_assets() ) {
		wp_enqueue_style( 'treats-forms', TREATS_URI . 'css/forms.css', array( 'treats-main' ), treats_asset_version( 'css/forms.css' ) );
	}

	wp_enqueue_style( 'treats-print', TREATS_URI . 'css/print.css', array( 'treats-main' ), treats_asset_version( 'css/print.css' ), 'print' );

	// Core behaviour: navigation, theme toggle, scroll reveal, accordions.
	wp_enqueue_script( 'treats-main', TREATS_URI . 'js/main.js', array(), treats_asset_version( 'js/main.js' ), true );
	wp_script_add_data( 'treats-main', 'defer', true );

	wp_localize_script(
		'treats-main',
		'treatsData',
		array(
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'treats_public' ),
			'homeUrl'      => home_url( '/' ),
			'reducedMotion' => (bool) apply_filters( 'treats_force_reduced_motion', false ),
			'i18n'         => array(
				'menuOpen'      => __( 'Open menu', 'treats' ),
				'menuClose'     => __( 'Close menu', 'treats' ),
				'themeLight'    => __( 'Switch to light mode', 'treats' ),
				'themeDark'     => __( 'Switch to dark mode', 'treats' ),
				'added'         => __( 'Added to your order', 'treats' ),
				'emptyBasket'   => __( 'Your collection order is empty.', 'treats' ),
				'sending'       => __( 'Sending…', 'treats' ),
				'genericError'  => __( 'Something went wrong. Please try again or call us on %s.', 'treats' ),
				'requiredField' => __( 'Please complete this field.', 'treats' ),
				'invalidEmail'  => __( 'Please enter a valid email address.', 'treats' ),
			),
		)
	);

	if ( treats_needs_menu_assets() ) {
		wp_enqueue_script( 'treats-menu', TREATS_URI . 'js/menu.js', array( 'treats-main' ), treats_asset_version( 'js/menu.js' ), true );
		wp_script_add_data( 'treats-menu', 'defer', true );

		wp_localize_script(
			'treats-menu',
			'treatsMenuData',
			array(
				'collectEnabled' => (bool) get_theme_mod( 'treats_collect_enabled', true ),
				'collectNotice'  => get_theme_mod( 'treats_collect_notice', __( 'Collection orders need 2 hours notice. We will call to confirm.', 'treats' ) ),
				'currency'       => treats_currency_symbol(),
				'i18n'           => array(
					/* translators: %s: number of dishes. */
					'dishOne'       => __( '%s dish', 'treats' ),
					/* translators: %s: number of dishes. */
					'dishMany'      => __( '%s dishes', 'treats' ),
					/* translators: %s: number of dishes. */
					'dishShownOne'  => __( '%s dish shown.', 'treats' ),
					/* translators: %s: number of dishes. */
					'dishShownMany' => __( '%s dishes shown.', 'treats' ),
				),
			)
		);
	}

	if ( treats_needs_form_assets() ) {
		wp_enqueue_script( 'treats-forms', TREATS_URI . 'js/forms.js', array( 'treats-main' ), treats_asset_version( 'js/forms.js' ), true );
		wp_script_add_data( 'treats-forms', 'defer', true );

		wp_localize_script(
			'treats-forms',
			'treatsFormData',
			array(
				'maxPartySize'  => (int) get_theme_mod( 'treats_booking_max_party', 12 ),
				'leadTimeHours' => (int) get_theme_mod( 'treats_booking_lead_hours', 2 ),
				'maxDaysAhead'  => (int) get_theme_mod( 'treats_booking_max_days', 90 ),
				'closedDays'    => treats_closed_weekdays(),
				'slots'         => treats_booking_slots(),
				'phone'         => treats_get_phone(),
			)
		);
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'treats_enqueue_assets' );

/**
 * Self-hosted variable fonts, declared inline to avoid an extra request.
 *
 * @return void
 */
function treats_font_face_css() {
	$fonts = TREATS_URI . 'fonts/';
	$latin = 'U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD';
	$ext   = 'U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF';

	$faces = array(
		array( 'Cormorant Garamond', 'cormorant-garamond-latin.woff2', '300 700', $latin ),
		array( 'Cormorant Garamond', 'cormorant-garamond-latin-ext.woff2', '300 700', $ext ),
		array( 'Inter', 'inter-latin.woff2', '100 900', $latin ),
		array( 'Inter', 'inter-latin-ext.woff2', '100 900', $ext ),
	);

	$css = '';

	foreach ( $faces as $face ) {
		list( $family, $file, $weight, $range ) = $face;

		$css .= sprintf(
			'@font-face{font-family:"%1$s";font-style:normal;font-weight:%2$s;font-display:swap;src:url("%3$s") format("woff2");unicode-range:%4$s;}',
			$family,
			$weight,
			esc_url( $fonts . $file ),
			$range
		);
	}

	wp_register_style( 'treats-fonts', false, array(), TREATS_VERSION );
	wp_enqueue_style( 'treats-fonts' );
	wp_add_inline_style( 'treats-fonts', $css );
}
add_action( 'wp_enqueue_scripts', 'treats_font_face_css', 5 );

/**
 * Preload the two fonts used above the fold, plus the hero image.
 *
 * @return void
 */
function treats_resource_hints_head() {
	$preloads = array(
		TREATS_URI . 'fonts/inter-latin.woff2',
		TREATS_URI . 'fonts/cormorant-garamond-latin.woff2',
	);

	foreach ( $preloads as $href ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( $href )
		);
	}
}
add_action( 'wp_head', 'treats_resource_hints_head', 1 );

/**
 * Apply the `defer` attribute registered via wp_script_add_data().
 *
 * @param string $tag    Script tag.
 * @param string $handle Script handle.
 * @return string
 */
function treats_defer_scripts( $tag, $handle ) {
	if ( is_admin() ) {
		return $tag;
	}

	if ( wp_scripts()->get_data( $handle, 'defer' ) && false === strpos( $tag, ' defer' ) ) {
		$tag = str_replace( ' src=', ' defer src=', $tag );
	}

	return $tag;
}
add_filter( 'script_loader_tag', 'treats_defer_scripts', 10, 2 );

/**
 * Critical CSS for the first paint. Kept deliberately small.
 *
 * Prevents a flash of unstyled content and locks in the colour scheme before
 * main.css arrives, which matters most on slow mobile connections.
 *
 * @return void
 */
function treats_critical_css() {
	?>
<style id="treats-critical">
:root{--t-bg:#fdfcfa;--t-ink:#1a1d1a;--t-sage:#6f9375;--t-header-h:76px}
html{-webkit-text-size-adjust:100%;scroll-behavior:smooth}
@media (prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
body{margin:0;background:var(--t-bg);color:var(--t-ink);font-family:"Inter",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;font-size:17px;line-height:1.65;-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility}
img{max-width:100%;height:auto;display:block}
.site-header{position:fixed;inset:0 0 auto;z-index:100;height:var(--t-header-h)}
.skip-link{position:absolute;left:-9999px;top:0}
.no-js [data-reveal]{opacity:1!important;transform:none!important}
</style>
		<?php
}
add_action( 'wp_head', 'treats_critical_css', 2 );

/**
 * Editor assets so the back end previews the front-end typography.
 *
 * @return void
 */
function treats_block_editor_assets() {
	wp_enqueue_style( 'treats-editor', TREATS_URI . 'css/editor.css', array(), treats_asset_version( 'css/editor.css' ) );
}
add_action( 'enqueue_block_editor_assets', 'treats_block_editor_assets' );
