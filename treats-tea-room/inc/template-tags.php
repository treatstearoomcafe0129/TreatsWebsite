<?php
/**
 * Template tags and presentation helpers.
 *
 * Everything here is safe to call from templates and always returns escaped,
 * ready-to-print markup (functions prefixed `treats_the_`) or raw data
 * (functions prefixed `treats_get_`).
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Business details
 * ---------------------------------------------------------------------- */

/**
 * Business name.
 *
 * @return string
 */
function treats_get_business_name() {
	$name = trim( (string) get_theme_mod( 'treats_business_name', '' ) );

	if ( '' === $name ) {
		$name = trim( (string) get_bloginfo( 'name' ) );
	}

	// Emails put the name at the front of the subject line, so an empty
	// setting used to send "— we have your request".
	return '' !== $name ? $name : 'Treats Tea Room Café';
}

/**
 * Whether people can order food for collection through the site.
 *
 * Off by default: the menus exist to be read. A café that wants to take
 * collection orders turns it on deliberately, rather than discovering it has
 * been accepting them all along.
 *
 * The default lives here alone — it was repeated across five files, and the
 * one that disagreed would have been the one still taking orders.
 *
 * @return bool
 */
function treats_collect_enabled() {
	return (bool) get_theme_mod( 'treats_collect_enabled', false );
}

/**
 * Street address parts.
 *
 * @return array<string,string>
 */
function treats_get_address() {
	return array(
		'street'   => (string) get_theme_mod( 'treats_address_street', '10/11 Silver Street' ),
		'locality' => (string) get_theme_mod( 'treats_address_locality', 'Durham' ),
		'region'   => (string) get_theme_mod( 'treats_address_region', 'County Durham' ),
		'postcode' => (string) get_theme_mod( 'treats_address_postcode', 'DH1 3RD' ),
		'country'  => (string) get_theme_mod( 'treats_address_country', 'United Kingdom' ),
	);
}

/**
 * Address as a single line.
 *
 * @return string
 */
function treats_get_address_line() {
	$address = treats_get_address();

	return implode( ', ', array_filter( $address ) );
}

/**
 * Public telephone number, as entered.
 *
 * @return string
 */
function treats_get_phone() {
	return (string) get_theme_mod( 'treats_phone', '0191 386 0925' );
}

/**
 * Telephone number normalised for a tel: link.
 *
 * @return string
 */
function treats_get_phone_link() {
	$raw = preg_replace( '/[^0-9+]/', '', treats_get_phone() );

	if ( '' === $raw ) {
		return '';
	}

	// Convert a UK national number to E.164 for reliable mobile dialling.
	if ( 0 === strpos( $raw, '0' ) ) {
		$raw = '+44' . substr( $raw, 1 );
	}

	return $raw;
}

/**
 * Public email address.
 *
 * @return string
 */
function treats_get_email() {
	// Lower cased because transactional providers match the sender address
	// exactly against the one you verified with them, and a stray capital in
	// the Customizer is enough to have every notification rejected.
	return strtolower( trim( (string) get_theme_mod( 'treats_email', 'info@treatstearoom.co.uk' ) ) );
}

/**
 * Currency symbol used across menus and vouchers.
 *
 * @return string
 */
function treats_currency_symbol() {
	return (string) get_theme_mod( 'treats_currency_symbol', '£' );
}

/**
 * The colour scheme the site is set to.
 *
 * Defaults to light. This is a tea room in Durham, not a code editor — the
 * sage and gold is the brand, and a visitor whose phone happens to be in dark
 * mode should still see it. Set it back to "Follow the visitor's device" in
 * the Customizer if you would rather offer the choice.
 *
 * @return string 'light', 'dark' or 'system'.
 */
function treats_color_scheme() {
	$scheme = get_theme_mod( 'treats_color_scheme_default', 'light' );

	return in_array( $scheme, array( 'light', 'dark', 'system' ), true ) ? $scheme : 'light';
}

/**
 * URL of the printed menu as a PDF, if there is one.
 *
 * Offered as a download beside the browsable menus — some people would rather
 * have the designed thing on their phone than scroll a web page.
 *
 * @return string
 */
function treats_menu_pdf_url() {
	$custom = (string) get_theme_mod( 'treats_menu_pdf', '' );

	if ( '' !== $custom ) {
		return $custom;
	}

	return file_exists( get_theme_file_path( 'images/menu/treats-menu.pdf' ) )
		? get_theme_file_uri( 'images/menu/treats-menu.pdf' )
		: '';
}

/**
 * ID of the first page using a template.
 *
 * @param string $template Template file, relative to the theme.
 * @return int
 */
function treats_get_template_page_id( $template ) {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $template, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);

	return $pages ? (int) $pages[0] : 0;
}

/**
 * The five menu pages, in the order they are meant to be read.
 *
 * Ordered by `menu_order` — the order the activation scaffold created them in
 * — rather than by date. `treats_get_template_page_url()` returns whichever
 * page carrying a template is newest, which is fine when only one page uses a
 * template and wrong when five do: "See our menus" was landing on Drinks
 * purely because Drinks was created last.
 *
 * @return array<int,array{title:string,url:string,note:string,icon:string,count:int}>
 */
function treats_menu_pages() {
	$cached = wp_cache_get( 'treats_menu_pages', 'treats' );

	if ( false !== $cached ) {
		return (array) $cached;
	}

	$notes = array(
		'breakfast-brunch' => array( __( 'Served all day, plus the morning menu until 10.30am.', 'treats' ), 'leaf' ),
		'lunch'            => array( __( 'Sandwiches, burgers, jackets, salads and house specialities.', 'treats' ), 'bag' ),
		'afternoon-tea'    => array( __( 'Our signature three tiers, for two.', 'treats' ), 'cup' ),
		'cakes-desserts'   => array( __( 'Pancakes, and the cake counter.', 'treats' ), 'cake' ),
		'drinks'           => array( __( 'Tea, coffee, smoothies, milkshakes and something stronger.', 'treats' ), 'teapot' ),
	);

	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
			'no_found_rows'  => true,
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => 'page-templates/template-menu.php', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);

	$menus = array();

	foreach ( $pages as $page ) {
		$slug = (string) get_post_meta( $page->ID, '_treats_menu_category', true );
		$term = $slug ? get_term_by( 'slug', $slug, 'treats_menu_category' ) : null;

		$menus[] = array(
			'title' => get_the_title( $page ),
			'url'   => (string) get_permalink( $page ),
			'note'  => isset( $notes[ $slug ] ) ? $notes[ $slug ][0] : '',
			'icon'  => isset( $notes[ $slug ] ) ? $notes[ $slug ][1] : 'cup',
			'count' => ( $term instanceof WP_Term ) ? (int) $term->count : 0,
		);
	}

	wp_cache_set( 'treats_menu_pages', $menus, 'treats', HOUR_IN_SECONDS );

	return $menus;
}

/**
 * URL of the menus overview page, falling back to the first menu.
 *
 * @return string
 */
function treats_menus_url() {
	$url = treats_get_template_page_url( 'page-templates/template-menu-index.php' );

	if ( $url && home_url( '/' ) !== $url ) {
		return $url;
	}

	$menus = treats_menu_pages();

	return $menus ? $menus[0]['url'] : home_url( '/' );
}

/**
 * Format a price for display.
 *
 * @param string|float $price Raw price value.
 * @return string
 */
function treats_format_price( $price ) {
	$price = trim( (string) $price );

	if ( '' === $price ) {
		return '';
	}

	// Allow editors to enter things like "5.95", "£5.95" or "from 12.00".
	if ( is_numeric( $price ) ) {
		return treats_currency_symbol() . number_format_i18n( (float) $price, 2 );
	}

	return $price;
}

/**
 * Opening hours, keyed by weekday index (0 = Monday).
 *
 * Each entry is an array with `open`, `close` and `closed` keys.
 *
 * @return array<int,array<string,mixed>>
 */
function treats_get_opening_hours() {
	$defaults = array(
		0 => array( '08:30', '17:00', false ),
		1 => array( '08:30', '17:00', false ),
		2 => array( '08:30', '17:00', false ),
		3 => array( '08:30', '17:00', false ),
		4 => array( '08:30', '17:00', false ),
		5 => array( '08:30', '17:00', false ),
		6 => array( '08:30', '17:00', false ),
	);

	$hours = array();

	foreach ( $defaults as $index => $default ) {
		$hours[ $index ] = array(
			'label'  => treats_weekday_label( $index ),
			'open'   => (string) get_theme_mod( 'treats_hours_' . $index . '_open', $default[0] ),
			'close'  => (string) get_theme_mod( 'treats_hours_' . $index . '_close', $default[1] ),
			'closed' => (bool) get_theme_mod( 'treats_hours_' . $index . '_closed', $default[2] ),
		);
	}

	return $hours;
}

/**
 * A stored 24-hour time rendered the way the design shows it: 8:30am, 5:00pm.
 *
 * Values are stored as HH:MM so they stay sortable and comparable; only the
 * display is converted.
 *
 * @param string $time HH:MM.
 * @return string
 */
function treats_format_time( $time ) {
	$time = trim( (string) $time );

	if ( ! preg_match( '/^([01][0-9]|2[0-3]):([0-5][0-9])$/', $time, $parts ) ) {
		return $time;
	}

	$hour   = (int) $parts[1];
	$minute = $parts[2];
	$suffix = $hour < 12 ? __( 'am', 'treats' ) : __( 'pm', 'treats' );
	$hour12 = $hour % 12;

	if ( 0 === $hour12 ) {
		$hour12 = 12;
	}

	return $hour12 . ':' . $minute . $suffix;
}

/**
 * An opening-to-closing span, formatted for display.
 *
 * @param array $day A row from treats_get_opening_hours().
 * @return string
 */
function treats_format_hours( $day ) {
	if ( ! empty( $day['closed'] ) ) {
		return __( 'Closed', 'treats' );
	}

	return treats_format_time( $day['open'] ) . ' – ' . treats_format_time( $day['close'] );
}

/**
 * Weekday label for an index where 0 = Monday.
 *
 * @param int $index Weekday index.
 * @return string
 */
function treats_weekday_label( $index ) {
	$labels = array(
		__( 'Monday', 'treats' ),
		__( 'Tuesday', 'treats' ),
		__( 'Wednesday', 'treats' ),
		__( 'Thursday', 'treats' ),
		__( 'Friday', 'treats' ),
		__( 'Saturday', 'treats' ),
		__( 'Sunday', 'treats' ),
	);

	return isset( $labels[ $index ] ) ? $labels[ $index ] : '';
}

/**
 * Schema.org day names for an index where 0 = Monday.
 *
 * @param int $index Weekday index.
 * @return string
 */
function treats_weekday_schema( $index ) {
	$days = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );

	return isset( $days[ $index ] ) ? $days[ $index ] : '';
}

/**
 * Weekday indexes on which the café is closed.
 *
 * Returned using JavaScript's `Date.getDay()` convention (0 = Sunday) so the
 * booking form can use the values directly.
 *
 * @return array<int,int>
 */
function treats_closed_weekdays() {
	$closed = array();

	foreach ( treats_get_opening_hours() as $index => $day ) {
		if ( ! empty( $day['closed'] ) ) {
			$closed[] = ( 6 === (int) $index ) ? 0 : (int) $index + 1;
		}
	}

	return $closed;
}

/**
 * Is the café open right now?
 *
 * @return bool
 */
function treats_is_open_now() {
	$now   = current_datetime();
	$index = (int) $now->format( 'N' ) - 1; // 1 (Mon) … 7 (Sun) → 0 … 6.
	$hours = treats_get_opening_hours();

	if ( ! isset( $hours[ $index ] ) || $hours[ $index ]['closed'] ) {
		return false;
	}

	$current = (int) $now->format( 'Hi' );
	$open    = (int) str_replace( ':', '', $hours[ $index ]['open'] );
	$close   = (int) str_replace( ':', '', $hours[ $index ]['close'] );

	return $current >= $open && $current < $close;
}

/**
 * Bookable time slots, derived from opening hours.
 *
 * @return array<int,string>
 */
function treats_booking_slots() {
	$custom = trim( (string) get_theme_mod( 'treats_booking_slots', '' ) );

	if ( '' !== $custom ) {
		$slots = array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', $custom ) ) );

		return array_values( $slots );
	}

	// Default: every 30 minutes from opening until 90 minutes before close.
	$slots   = array();
	$hours   = treats_get_opening_hours();
	$sample  = isset( $hours[0] ) ? $hours[0] : array( 'open' => '09:00', 'close' => '17:00' );
	$start   = (int) substr( $sample['open'], 0, 2 ) * 60 + (int) substr( $sample['open'], 3, 2 );
	$end     = (int) substr( $sample['close'], 0, 2 ) * 60 + (int) substr( $sample['close'], 3, 2 ) - 90;

	for ( $minutes = $start; $minutes <= $end; $minutes += 30 ) {
		$slots[] = sprintf( '%02d:%02d', intdiv( $minutes, 60 ), $minutes % 60 );
	}

	return $slots;
}

/**
 * Social profiles that have been filled in.
 *
 * @return array<string,array<string,string>>
 */
function treats_get_social_links() {
	$networks = array(
		'facebook'  => __( 'Facebook', 'treats' ),
		'instagram' => __( 'Instagram', 'treats' ),
		'tripadvisor' => __( 'Tripadvisor', 'treats' ),
		'x'         => __( 'X', 'treats' ),
	);

	$links = array();

	foreach ( $networks as $key => $label ) {
		$url = (string) get_theme_mod( 'treats_social_' . $key, '' );

		if ( '' !== $url ) {
			$links[ $key ] = array(
				'label' => $label,
				'url'   => $url,
			);
		}
	}

	return $links;
}

/* -------------------------------------------------------------------------
 * Markup helpers
 * ---------------------------------------------------------------------- */

/**
 * Inline SVG icon.
 *
 * Icons are inlined rather than sprited so they inherit `currentColor` and
 * cost no extra request.
 *
 * @param string $name  Icon name.
 * @param array  $args  Optional. `size`, `class`, `label`.
 * @return string
 */
function treats_get_icon( $name, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'size'  => 24,
			'class' => '',
			'label' => '',
		)
	);

	$paths = treats_icon_paths();

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	$hidden = '' === $args['label'] ? ' aria-hidden="true" focusable="false"' : ' role="img"';
	$title  = '' === $args['label'] ? '' : '<title>' . esc_html( $args['label'] ) . '</title>';

	return sprintf(
		'<svg class="icon icon--%1$s %2$s" width="%3$d" height="%3$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"%4$s>%5$s%6$s</svg>',
		esc_attr( $name ),
		esc_attr( $args['class'] ),
		(int) $args['size'],
		$hidden,
		$title,
		$paths[ $name ]
	);
}

/**
 * Print an inline SVG icon.
 *
 * @param string $name Icon name.
 * @param array  $args Optional args.
 * @return void
 */
function treats_icon( $name, $args = array() ) {
	echo treats_get_icon( $name, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup generated internally.
}

/**
 * Icon path definitions.
 *
 * @return array<string,string>
 */
function treats_icon_paths() {
	return array(
		'menu'        => '<path d="M3 6h18M3 12h18M3 18h18"/>',
		'close'       => '<path d="M18 6 6 18M6 6l12 12"/>',
		'chevron'     => '<path d="m6 9 6 6 6-6"/>',
		'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
		'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'arrow-up'    => '<path d="M12 19V5M6 11l6-6 6 6"/>',
		'phone'       => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/>',
		'mail'        => '<path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/><path d="m22 7-10 6L2 7"/>',
		'pin'         => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
		'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'calendar'    => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
		'users'       => '<path d="M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 20v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/>',
		'search'      => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'star'        => '<path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6-5.4-2.8-5.4 2.8 1-6L3.2 9.4l6.1-.9Z"/>',
		'quote'       => '<path d="M9 11H5a1 1 0 0 1-1-1V7a3 3 0 0 1 3-3"/><path d="M20 11h-4a1 1 0 0 1-1-1V7a3 3 0 0 1 3-3"/><path d="M9 11v3a6 6 0 0 1-5 6"/><path d="M20 11v3a6 6 0 0 1-5 6"/>',
		'gift'        => '<rect x="3" y="9" width="18" height="12" rx="2"/><path d="M3 13h18M12 9v12"/><path d="M12 9S10.5 4 8 4a2.5 2.5 0 0 0 0 5Z"/><path d="M12 9s1.5-5 4-5a2.5 2.5 0 0 1 0 5Z"/>',
		'bag'         => '<path d="M6 7h12l1 13H5Z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/>',
		'plus'        => '<path d="M12 5v14M5 12h14"/>',
		'minus'       => '<path d="M5 12h14"/>',
		'check'       => '<path d="m4 12 5 5L20 6"/>',
		'sun'         => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
		'moon'        => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/>',
		'leaf'        => '<path d="M11 20A7 7 0 0 1 4 13c0-6 8-9 16-9 0 8-3 16-9 16Z"/><path d="M4 20c3-3 6-5 9-6"/>',
		'cup'         => '<path d="M5 10h11v4.5a4.5 4.5 0 0 1-4.5 4.5h-2A4.5 4.5 0 0 1 5 14.5Z"/><path d="M16 11.5h1.4a2.3 2.3 0 0 1 0 4.6H16"/><path d="M4 21h13"/><path d="M8 4.5c0 1-1 1.3-1 2.3M12 4.5c0 1-1 1.3-1 2.3"/>',
		'teapot'      => '<path d="M6.5 9.5h9a3.5 3.5 0 0 1 3.5 3.5v1a5 5 0 0 1-5 5h-6a5 5 0 0 1-5-5v-1a3.5 3.5 0 0 1 3.5-3.5Z"/><path d="M19 12.5c1.6.4 2.6 1.4 2.6 2.6M6.5 9.5C7 7.4 8.9 6 11 6s4 1.4 4.5 3.5"/><path d="M11 6V4.2M9 4.2h4"/>',
		'heart'       => '<path d="M12 20s-7-4.4-7-9.3A4.2 4.2 0 0 1 12 8a4.2 4.2 0 0 1 7 2.7C19 15.6 12 20 12 20Z"/>',
		'tag'         => '<path d="M4 11.5V5a1 1 0 0 1 1-1h6.5a1 1 0 0 1 .7.3l7.5 7.5a1 1 0 0 1 0 1.4l-6.5 6.5a1 1 0 0 1-1.4 0L4.3 12.2a1 1 0 0 1-.3-.7Z"/><circle cx="8.5" cy="8.5" r="1.2"/>',
		'user'        => '<circle cx="12" cy="8.5" r="3.6"/><path d="M5.5 20a6.5 6.5 0 0 1 13 0"/>',
		'cake'        => '<path d="M4 15h16v6H4Z"/><path d="M6 15v-3a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v3"/><path d="M12 7V5"/>',
		'facebook'    => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v9h4v-9h3l1-4h-4V9a1 1 0 0 1 1-1Z"/>',
		'instagram'   => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".6" fill="currentColor"/>',
		'tripadvisor' => '<circle cx="7" cy="12" r="4"/><circle cx="17" cy="12" r="4"/><path d="M11 12h2M4 9c2.5-3 13.5-3 16 0"/>',
		'x'           => '<path d="m4 4 16 16M20 4 4 20"/>',
		'accessible'  => '<circle cx="12" cy="4.5" r="1.8"/><path d="M8 8h8M12 8v5m0 0 4 6m-4-6-4 6"/>',
		'wifi'        => '<path d="M5 12.5a10 10 0 0 1 14 0M8.5 16a5 5 0 0 1 7 0"/><circle cx="12" cy="19.5" r="1" fill="currentColor"/>',
		'dog'         => '<path d="M10 5 6 4v4M14 5l4-1v4"/><path d="M6 8a6 6 0 0 0 12 0v4a6 6 0 0 1-12 0Z"/><circle cx="10" cy="12" r=".8" fill="currentColor"/><circle cx="14" cy="12" r=".8" fill="currentColor"/>',
	);
}

/**
 * Reveal-on-scroll attributes.
 *
 * @param int    $delay     Delay in milliseconds.
 * @param string $animation Animation name: up, fade, scale.
 * @return string
 */
function treats_get_reveal( $delay = 0, $animation = 'up' ) {
	// Data attributes rather than class/style: these are printed onto elements
	// that usually already carry both, and a duplicate attribute is silently
	// discarded by the browser.
	$attr = ' data-reveal="' . esc_attr( $animation ) . '"';

	if ( $delay > 0 ) {
		$attr .= ' data-reveal-delay="' . (int) $delay . '"';
	}

	return $attr;
}

/**
 * Print reveal attributes.
 *
 * @param int    $delay     Delay in milliseconds.
 * @param string $animation Animation name.
 * @return void
 */
function treats_reveal( $delay = 0, $animation = 'up' ) {
	echo treats_get_reveal( $delay, $animation ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
}

/**
 * Section eyebrow + heading + intro.
 *
 * @param array $args Heading arguments.
 * @return void
 */
function treats_section_heading( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'eyebrow' => '',
			'title'   => '',
			'intro'   => '',
			'align'   => 'center',
			'level'   => 'h2',
			'class'   => '',
		)
	);

	if ( '' === $args['title'] && '' === $args['eyebrow'] ) {
		return;
	}

	$level = in_array( $args['level'], array( 'h1', 'h2', 'h3' ), true ) ? $args['level'] : 'h2';
	?>
	<header class="section-heading section-heading--<?php echo esc_attr( $args['align'] ); ?> <?php echo esc_attr( $args['class'] ); ?>"<?php treats_reveal(); ?>>
		<?php if ( '' !== $args['eyebrow'] ) : ?>
			<p class="eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p>
		<?php endif; ?>

		<?php if ( '' !== $args['title'] ) : ?>
			<<?php echo esc_attr( $level ); ?> class="section-heading__title"><?php echo wp_kses_post( $args['title'] ); ?></<?php echo esc_attr( $level ); ?>>
		<?php endif; ?>

		<?php if ( '' !== $args['intro'] ) : ?>
			<p class="section-heading__intro"><?php echo wp_kses_post( $args['intro'] ); ?></p>
		<?php endif; ?>
	</header>
	<?php
}

/**
 * A decorative sage divider.
 *
 * @return void
 */
function treats_divider() {
	?>
	<div class="divider" aria-hidden="true"><span class="divider__line"></span><?php treats_icon( 'leaf', array( 'size' => 18 ) ); ?><span class="divider__line"></span></div>
	<?php
}

/**
 * Intrinsic dimensions of an SVG file.
 *
 * Only the opening tag is read — enough for `width`/`height`, or a `viewBox`
 * to fall back on. Nothing is rendered or executed.
 *
 * @param string $path Absolute path to the file.
 * @return array|false array( width, height ), or false if neither is declared.
 */
function treats_svg_dimensions( $path ) {
	$head = file_get_contents( $path, false, null, 0, 2048 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local theme file, not a remote request.

	if ( false === $head ) {
		return false;
	}

	if ( preg_match( '/<svg[^>]*\swidth="([\d.]+)(?:px)?"[^>]*\sheight="([\d.]+)(?:px)?"/i', $head, $m ) ) {
		return array( (int) round( (float) $m[1] ), (int) round( (float) $m[2] ) );
	}

	if ( preg_match( '/<svg[^>]*\sviewBox="[\d.\-]+[,\s]+[\d.\-]+[,\s]+([\d.]+)[,\s]+([\d.]+)"/i', $head, $m ) ) {
		return array( (int) round( (float) $m[1] ), (int) round( (float) $m[2] ) );
	}

	return false;
}

/**
 * A logo shipped with the theme rather than uploaded to the media library.
 *
 * Drop the artwork into the theme's `images/` folder as `logo.svg` (or
 * `logo.png` / `logo.webp`) and it is used automatically — useful when the
 * theme is deployed from a repository and nobody wants to touch the
 * dashboard. A logo set in the Customizer always wins over this.
 *
 * @return array|false array( url, width, height ), or false if there is none.
 */
function treats_bundled_logo() {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$cache = false;

	/**
	 * Filters the file names looked for, in order of preference.
	 *
	 * @param string[] $names File names, relative to the theme's images folder.
	 */
	$names = apply_filters( 'treats_bundled_logo_files', array( 'logo.svg', 'logo.png', 'logo.webp' ) );

	foreach ( $names as $name ) {
		$path = get_theme_file_path( 'images/' . $name );

		if ( ! file_exists( $path ) ) {
			continue;
		}

		if ( '.svg' === substr( $name, -4 ) ) {
			$size = treats_svg_dimensions( $path );
		} else {
			$raw  = getimagesize( $path );
			$size = $raw ? array( (int) $raw[0], (int) $raw[1] ) : false;
		}

		$cache = array(
			'url'    => get_theme_file_uri( 'images/' . $name ),
			'width'  => $size ? $size[0] : 0,
			'height' => $size ? $size[1] : 0,
		);

		break;
	}

	return $cache;
}

/**
 * The site logo: custom logo if set, then a logo bundled with the theme,
 * otherwise the bespoke wordmark.
 *
 * @param string $class Extra class names.
 * @return void
 */
function treats_the_logo( $class = '' ) {
	$name = treats_get_business_name();

	if ( has_custom_logo() ) {
		$logo_id  = (int) get_theme_mod( 'custom_logo' );
		$logo_img = wp_get_attachment_image(
			$logo_id,
			'full',
			false,
			array(
				'class'         => 'site-logo__img',
				'alt'           => $name,
				'fetchpriority' => 'high',
			)
		);

		printf(
			'<a class="site-logo %1$s" href="%2$s" rel="home" aria-label="%3$s">%4$s</a>',
			esc_attr( $class ),
			esc_url( home_url( '/' ) ),
			esc_attr( sprintf( /* translators: %s: business name. */ __( '%s — home', 'treats' ), $name ) ),
			$logo_img // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core-generated markup.
		);

		return;
	}

	$bundled = treats_bundled_logo();

	if ( $bundled ) {
		$dimensions = ( $bundled['width'] && $bundled['height'] )
			? sprintf( ' width="%d" height="%d"', $bundled['width'], $bundled['height'] )
			: '';

		printf(
			'<a class="site-logo %1$s" href="%2$s" rel="home" aria-label="%3$s"><img class="site-logo__img" src="%4$s" alt=""%5$s fetchpriority="high" decoding="async"></a>',
			esc_attr( $class ),
			esc_url( home_url( '/' ) ),
			esc_attr( sprintf( /* translators: %s: business name. */ __( '%s — home', 'treats' ), $name ) ),
			esc_url( $bundled['url'] ),
			$dimensions // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from integers above.
		);

		return;
	}
	?>
	<a class="site-logo site-logo--wordmark <?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
		<span class="site-logo__text">
			<span class="site-logo__lockup">
				<span class="site-logo__name"><?php echo esc_html( get_theme_mod( 'treats_logo_script', 'Treats' ) ); ?></span>

				<?php
				$founded = trim( (string) get_theme_mod( 'treats_seo_founding_year', '1984' ) );

				if ( '' !== $founded ) {
					printf(
						'<span class="site-logo__est" aria-hidden="true">%s</span>',
						esc_html( sprintf( /* translators: %s: year established. */ __( 'Est %s', 'treats' ), $founded ) )
					);
				}
				?>

				<svg class="site-logo__swash" viewBox="0 0 200 26" fill="none" aria-hidden="true" focusable="false" preserveAspectRatio="none">
					<path d="M2 9c18 12 46 15 78 12 30-3 60-9 84-17-20 14-52 22-86 24C46 30 18 22 2 9Z" fill="currentColor"/>
				</svg>
			</span>

			<span class="site-logo__tag"><?php echo esc_html( get_theme_mod( 'treats_logo_tagline', __( 'Tea Room', 'treats' ) ) ); ?></span>
			<span class="screen-reader-text"><?php echo esc_html( $name ); ?></span>
		</span>
	</a>
	<?php
}

/**
 * Breadcrumb trail with structured data handled separately.
 *
 * @return void
 */
function treats_breadcrumbs() {
	if ( is_front_page() ) {
		return;
	}

	$items = treats_get_breadcrumb_items();

	if ( count( $items ) < 2 ) {
		return;
	}

	$last = count( $items ) - 1;
	?>
	<nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'treats' ); ?>">
		<ol class="breadcrumbs__list">
			<?php foreach ( $items as $index => $item ) : ?>
				<li class="breadcrumbs__item">
					<?php if ( $index === $last ) : ?>
						<span aria-current="page"><?php echo esc_html( $item['label'] ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
						<span class="breadcrumbs__sep" aria-hidden="true"><?php treats_icon( 'chevron-right', array( 'size' => 14 ) ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
}

/**
 * Breadcrumb data, shared by the visual trail and the JSON-LD graph.
 *
 * @return array<int,array<string,string>>
 */
function treats_get_breadcrumb_items() {
	$items = array(
		array(
			'label' => __( 'Home', 'treats' ),
			'url'   => home_url( '/' ),
		),
	);

	// The home page is the trail; anything more would repeat itself.
	if ( is_front_page() ) {
		return $items;
	}

	if ( is_singular() ) {
		$post = get_queried_object();

		if ( $post instanceof WP_Post ) {
			foreach ( array_reverse( (array) get_post_ancestors( $post ) ) as $ancestor ) {
				$items[] = array(
					'label' => get_the_title( $ancestor ),
					'url'   => get_permalink( $ancestor ),
				);
			}

			if ( 'post' === $post->post_type ) {
				$blog_id = (int) get_option( 'page_for_posts' );

				if ( $blog_id ) {
					$items[] = array(
						'label' => get_the_title( $blog_id ),
						'url'   => get_permalink( $blog_id ),
					);
				}
			}

			$items[] = array(
				'label' => get_the_title( $post ),
				'url'   => get_permalink( $post ),
			);
		}
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			$items[] = array(
				'label' => $term->name,
				'url'   => get_term_link( $term ),
			);
		}
	} elseif ( is_post_type_archive() ) {
		$items[] = array(
			'label' => post_type_archive_title( '', false ),
			'url'   => get_post_type_archive_link( get_post_type() ),
		);
	} elseif ( is_search() ) {
		$items[] = array(
			'label' => sprintf( /* translators: %s: search term. */ __( 'Search: %s', 'treats' ), get_search_query() ),
			'url'   => get_search_link(),
		);
	} elseif ( is_404() ) {
		$items[] = array(
			'label' => __( 'Page not found', 'treats' ),
			'url'   => home_url( '/' ),
		);
	} elseif ( is_home() ) {
		$items[] = array(
			'label' => single_post_title( '', false ),
			'url'   => get_permalink( (int) get_option( 'page_for_posts' ) ),
		);
	}

	// Guard against WP_Error term links.
	foreach ( $items as $index => $item ) {
		if ( is_wp_error( $item['url'] ) ) {
			$items[ $index ]['url'] = home_url( '/' );
		}
	}

	return $items;
}

/**
 * The page hero used on interior pages.
 *
 * @param array $args Hero arguments.
 * @return void
 */
function treats_page_hero( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'eyebrow'  => '',
			'title'    => '',
			'intro'    => '',
			'image_id' => 0,
			'actions'  => array(),
			'compact'  => false,
		)
	);

	get_template_part(
		'template-parts/components/page-hero',
		null,
		$args
	);
}

/**
 * Responsive image with a graceful placeholder fallback.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $size          Image size.
 * @param array  $args          `class`, `alt`, `sizes`, `priority`, `ratio`.
 * @return void
 */
function treats_image( $attachment_id, $size = 'treats-card', $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'class'    => '',
			'alt'      => '',
			'sizes'    => '(max-width: 767px) 100vw, (max-width: 1199px) 50vw, 33vw',
			'priority' => false,
			'ratio'    => '4 / 3',
		)
	);

	$wrapper_style = 'aspect-ratio:' . esc_attr( $args['ratio'] );

	if ( ! $attachment_id ) {
		printf(
			'<div class="media media--placeholder %1$s" style="%2$s" aria-hidden="true">%3$s</div>',
			esc_attr( $args['class'] ),
			esc_attr( $wrapper_style ),
			treats_get_icon( 'cup', array( 'size' => 40 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Internal markup.
		);

		return;
	}

	$attrs = array(
		'class'   => 'media__img',
		'sizes'   => $args['sizes'],
		'decoding' => 'async',
	);

	if ( $args['priority'] ) {
		$attrs['fetchpriority'] = 'high';
		$attrs['loading']       = 'eager';
	} else {
		$attrs['loading'] = 'lazy';
	}

	if ( '' !== $args['alt'] ) {
		$attrs['alt'] = $args['alt'];
	}

	printf(
		'<div class="media %1$s" style="%2$s">%3$s</div>',
		esc_attr( $args['class'] ),
		esc_attr( $wrapper_style ),
		wp_get_attachment_image( $attachment_id, $size, false, $attrs ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core markup.
	);
}

/**
 * Star rating markup.
 *
 * @param float $rating Rating out of 5.
 * @return void
 */
function treats_stars( $rating ) {
	$rating = max( 0, min( 5, (float) $rating ) );
	$label  = sprintf( /* translators: %s: rating out of five. */ __( 'Rated %s out of 5', 'treats' ), number_format_i18n( $rating, 1 ) );
	?>
	<div class="stars" role="img" aria-label="<?php echo esc_attr( $label ); ?>">
		<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
			<span class="stars__star <?php echo $i <= round( $rating ) ? 'is-filled' : ''; ?>"><?php treats_icon( 'star', array( 'size' => 16 ) ); ?></span>
		<?php endfor; ?>
	</div>
	<?php
}

/**
 * Look up a page by its template, so CTAs always link somewhere real.
 *
 * @param string $template Template filename relative to the theme.
 * @return string
 */
function treats_get_template_page_url( $template ) {
	$cache_key = 'treats_tpl_url_' . md5( $template );
	$cached    = wp_cache_get( $cache_key, 'treats' );

	if ( false !== $cached ) {
		return (string) $cached;
	}

	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $template, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	$url = $pages ? (string) get_permalink( $pages[0] ) : home_url( '/' );

	wp_cache_set( $cache_key, $url, 'treats', HOUR_IN_SECONDS );

	return $url;
}

/**
 * URL of the booking page.
 *
 * @return string
 */
function treats_booking_url() {
	$external = trim( (string) get_theme_mod( 'treats_booking_external_url', '' ) );

	if ( '' !== $external ) {
		return $external;
	}

	return treats_get_template_page_url( 'page-templates/template-booking.php' );
}

/**
 * URL of the gift voucher page.
 *
 * @return string
 */
function treats_vouchers_url() {
	return treats_get_template_page_url( 'page-templates/template-vouchers.php' );
}

/**
 * Google Maps embed URL built from the address.
 *
 * @return string
 */
function treats_map_embed_url() {
	$custom = trim( (string) get_theme_mod( 'treats_map_embed_url', '' ) );

	if ( '' !== $custom ) {
		return $custom;
	}

	return 'https://www.google.com/maps?q=' . rawurlencode( treats_get_business_name() . ', ' . treats_get_address_line() ) . '&output=embed';
}

/**
 * Google Maps directions link.
 *
 * @return string
 */
function treats_map_directions_url() {
	return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( treats_get_business_name() . ', ' . treats_get_address_line() );
}

/**
 * Post meta line for the journal.
 *
 * @return void
 */
function treats_post_meta() {
	?>
	<div class="entry-meta">
		<time class="entry-meta__date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
		<?php
		$categories = get_the_category_list( ', ' );

		if ( $categories ) :
			?>
			<span class="entry-meta__sep" aria-hidden="true">·</span>
			<span class="entry-meta__cats"><?php echo wp_kses_post( $categories ); ?></span>
		<?php endif; ?>
		<span class="entry-meta__sep" aria-hidden="true">·</span>
		<span class="entry-meta__time"><?php echo esc_html( treats_reading_time() ); ?></span>
	</div>
	<?php
}

/**
 * Estimated reading time for the current post.
 *
 * @return string
 */
function treats_reading_time() {
	$words   = str_word_count( wp_strip_all_tags( get_the_content() ) );
	$minutes = max( 1, (int) ceil( $words / 200 ) );

	/* translators: %s: number of minutes. */
	return sprintf( _n( '%s min read', '%s min read', $minutes, 'treats' ), number_format_i18n( $minutes ) );
}

/**
 * Images for the gallery and the Instagram fallback.
 *
 * Prefers media attached to the Gallery page; falls back to menu item
 * photography so the section is never empty on a fresh install.
 *
 * @param int $limit Maximum images.
 * @return int[] Attachment IDs.
 */
function treats_get_gallery_images( $limit = 12 ) {
	$cache_key = 'treats_gallery_' . (int) $limit;
	$cached    = wp_cache_get( $cache_key, 'treats' );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$ids  = array();
	$page = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => 'page-templates/template-gallery.php', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);

	if ( $page ) {
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'post_status'    => 'inherit',
				'post_parent'    => $page[0],
				'posts_per_page' => (int) $limit,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
	}

	if ( count( $ids ) < 4 ) {
		$items = get_posts(
			array(
				'post_type'      => 'treats_menu_item',
				'post_status'    => 'publish',
				'posts_per_page' => (int) $limit,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);

		foreach ( $items as $item_id ) {
			$thumb = (int) get_post_thumbnail_id( $item_id );

			if ( $thumb && ! in_array( $thumb, $ids, true ) ) {
				$ids[] = $thumb;
			}
		}
	}

	$ids = array_slice( array_map( 'intval', $ids ), 0, (int) $limit );

	wp_cache_set( $cache_key, $ids, 'treats', 10 * MINUTE_IN_SECONDS );

	return $ids;
}

/**
 * URL of the page that presents a given menu category.
 *
 * Falls back to the taxonomy archive, then the home page, so links from the
 * home page never dead-end while the site is being set up.
 *
 * @param string $slug Menu category slug.
 * @return string
 */
function treats_menu_page_url( $slug ) {
	$cache_key = 'treats_menu_page_' . $slug;
	$cached    = wp_cache_get( $cache_key, 'treats' );

	if ( false !== $cached ) {
		return (string) $cached;
	}

	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
					'key'   => '_wp_page_template',
					'value' => 'page-templates/template-menu.php',
				),
				array(
					'key'   => '_treats_menu_category',
					'value' => $slug,
				),
			),
		)
	);

	$url = '';

	if ( $pages ) {
		$url = (string) get_permalink( $pages[0] );
	} else {
		$term = get_term_by( 'slug', $slug, 'treats_menu_category' );

		if ( $term instanceof WP_Term ) {
			$link = get_term_link( $term );

			if ( ! is_wp_error( $link ) ) {
				$url = (string) $link;
			}
		}
	}

	if ( '' === $url ) {
		$url = home_url( '/' );
	}

	wp_cache_set( $cache_key, $url, 'treats', HOUR_IN_SECONDS );

	return $url;
}
