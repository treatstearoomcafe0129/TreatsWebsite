<?php
/**
 * Theme Customizer.
 *
 * Every piece of business-specific copy is editable here so the site can be
 * maintained without touching code.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register a control with sensible defaults.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 * @param string               $id           Setting ID.
 * @param array                $args         Control args.
 * @return void
 */
function treats_add_control( $wp_customize, $id, $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'label'       => '',
			'description' => '',
			'section'     => 'treats_brand',
			'type'        => 'text',
			'default'     => '',
			'choices'     => array(),
			'sanitize'    => 'sanitize_text_field',
			'transport'   => 'refresh',
			'input_attrs' => array(),
		)
	);

	$wp_customize->add_setting(
		$id,
		array(
			'default'           => $args['default'],
			'sanitize_callback' => $args['sanitize'],
			'transport'         => $args['transport'],
		)
	);

	$control_args = array(
		'label'       => $args['label'],
		'description' => $args['description'],
		'section'     => $args['section'],
		'type'        => $args['type'],
		'input_attrs' => $args['input_attrs'],
	);

	if ( $args['choices'] ) {
		$control_args['choices'] = $args['choices'];
	}

	if ( 'image' === $args['type'] ) {
		$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $id, $control_args ) );

		return;
	}

	if ( 'color' === $args['type'] ) {
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, $control_args ) );

		return;
	}

	$wp_customize->add_control( $id, $control_args );
}

/**
 * Sanitize a checkbox.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function treats_sanitize_checkbox( $value ) {
	return (bool) $value;
}

/**
 * Sanitize a select against its registered choices.
 *
 * @param string               $value   Raw value.
 * @param WP_Customize_Setting $setting Setting instance.
 * @return string
 */
function treats_sanitize_select( $value, $setting ) {
	$control = $setting->manager->get_control( $setting->id );
	$choices = $control ? $control->choices : array();

	return array_key_exists( $value, $choices ) ? $value : $setting->default;
}

/**
 * Products, as Customizer dropdown choices.
 *
 * @return array<string,string>
 */
function treats_product_choices() {
	$choices = array( '' => __( '— None —', 'treats' ) );

	$products = get_posts(
		array(
			'post_type'      => 'treats_product',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);

	foreach ( $products as $product ) {
		$choices[ (string) $product->ID ] = $product->post_title;
	}

	return $choices;
}

/**
 * Only ever store one of the two Square environments.
 *
 * Anything unrecognised falls back to the sandbox, so a mangled value can
 * never quietly start taking real money.
 *
 * @param string $value Raw value.
 * @return string
 */
function treats_sanitize_square_env( $value ) {
	return 'production' === $value ? 'production' : 'sandbox';
}

/**
 * Validate an HH:MM time string.
 *
 * @param string $value Raw value.
 * @return string
 */
function treats_sanitize_time( $value ) {
	$value = trim( (string) $value );

	return preg_match( '/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $value ) ? $value : '';
}

/**
 * Build the Customizer panels.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 * @return void
 */
function treats_customize_register( $wp_customize ) {
	$wp_customize->get_setting( 'blogname' )->transport        = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';

	$wp_customize->add_panel(
		'treats_panel',
		array(
			'title'    => __( 'Treats Tea Room', 'treats' ),
			'priority' => 10,
		)
	);

	$sections = array(
		'treats_brand'      => __( 'Brand & Appearance', 'treats' ),
		'treats_contact'    => __( 'Contact & Location', 'treats' ),
		'treats_hours'      => __( 'Opening Hours', 'treats' ),
		'treats_social'     => __( 'Social Profiles', 'treats' ),
		'treats_home'       => __( 'Home Page', 'treats' ),
		'treats_tea'        => __( 'Afternoon Tea', 'treats' ),
		'treats_events'     => __( 'Evening Venue Hire', 'treats' ),
		'treats_booking'    => __( 'Table Booking', 'treats' ),
		'treats_collect'    => __( 'Click & Collect', 'treats' ),
		'treats_vouchers'   => __( 'Gift Vouchers', 'treats' ),
		'treats_shop'       => __( 'Shop & Payments', 'treats' ),
		'treats_instagram'  => __( 'Instagram Feed', 'treats' ),
		'treats_newsletter' => __( 'Newsletter', 'treats' ),
		'treats_seo'        => __( 'SEO & Sharing', 'treats' ),
	);

	$priority = 10;

	foreach ( $sections as $id => $title ) {
		$wp_customize->add_section(
			$id,
			array(
				'title'    => $title,
				'panel'    => 'treats_panel',
				'priority' => $priority,
			)
		);

		$priority += 10;
	}

	/* --------------------------------------------------------------- Brand */

	treats_add_control(
		$wp_customize,
		'treats_business_name',
		array(
			'label'     => __( 'Business name', 'treats' ),
			'default'   => 'Treats Tea Room Café',
			'transport' => 'postMessage',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_logo_script',
		array(
			'label'       => __( 'Wordmark script word', 'treats' ),
			'description' => __( 'Set in the script face. Keep it short — one word reads best.', 'treats' ),
			'default'     => 'Treats',
			'transport'   => 'postMessage',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_logo_tagline',
		array(
			'label'     => __( 'Wordmark caps line', 'treats' ),
			'default'   => 'Tea Room',
			'transport' => 'postMessage',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_color_scheme_default',
		array(
			'label'       => __( 'Default colour scheme', 'treats' ),
			'description' => __( 'Choose “Always light” to switch dark mode off completely — the site then ignores the visitor’s device setting and the toggle is hidden. Only “Follow the visitor’s device” offers a choice.', 'treats' ),
			'type'        => 'select',
			'default'     => 'light',
			'choices'     => array(
				'system' => __( 'Follow the visitor’s device', 'treats' ),
				'light'  => __( 'Always light', 'treats' ),
				'dark'   => __( 'Always dark', 'treats' ),
			),
			'sanitize'    => 'treats_sanitize_select',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_enable_dark_toggle',
		array(
			'label'       => __( 'Show the dark mode toggle', 'treats' ),
			'description' => __( 'Only applies when the scheme follows the visitor’s device.', 'treats' ),
			'type'     => 'checkbox',
			'default'  => true,
			'sanitize' => 'treats_sanitize_checkbox',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_nav_group_menus',
		array(
			'label'       => __( 'Group the menu pages under “Menu”', 'treats' ),
			'description' => __( 'Breakfast, Lunch, Cakes and Drinks appear as a dropdown under Menu rather than as separate items in the bar. Turn this off to list them all across the top.', 'treats' ),
			'section'     => 'treats_brand',
			'type'        => 'checkbox',
			'default'     => true,
			'sanitize'    => 'treats_sanitize_checkbox',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_accent_color',
		array(
			'label'     => __( 'Accent colour', 'treats' ),
			'type'      => 'color',
			'default'   => '#8a6d2c',
			'sanitize'  => 'sanitize_hex_color',
			'transport' => 'postMessage',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_enable_animations',
		array(
			'label'       => __( 'Enable scroll animations', 'treats' ),
			'description' => __( 'Always disabled automatically for visitors who prefer reduced motion.', 'treats' ),
			'type'        => 'checkbox',
			'default'     => true,
			'sanitize'    => 'treats_sanitize_checkbox',
		)
	);

	/* ------------------------------------------------------------- Contact */

	$contact_fields = array(
		'treats_address_street'   => array( __( 'Street address', 'treats' ), '10/11 Silver Street' ),
		'treats_address_locality' => array( __( 'Town / city', 'treats' ), 'Durham' ),
		'treats_address_region'   => array( __( 'County', 'treats' ), 'County Durham' ),
		'treats_address_postcode' => array( __( 'Postcode', 'treats' ), 'DH1 3RD' ),
		'treats_address_country'  => array( __( 'Country', 'treats' ), 'United Kingdom' ),
		'treats_phone'            => array( __( 'Telephone', 'treats' ), '0191 386 0925' ),
	);

	foreach ( $contact_fields as $id => $config ) {
		treats_add_control(
			$wp_customize,
			$id,
			array(
				'label'   => $config[0],
				'default' => $config[1],
				'section' => 'treats_contact',
			)
		);
	}

	treats_add_control(
		$wp_customize,
		'treats_email',
		array(
			'label'    => __( 'Public email address', 'treats' ),
			'section'  => 'treats_contact',
			'type'     => 'email',
			'default'  => 'info@treatstearoom.co.uk',
			'sanitize' => 'sanitize_email',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_notification_email',
		array(
			'label'       => __( 'Send form notifications to', 'treats' ),
			'description' => __( 'Where bookings, enquiries and orders are emailed. Defaults to the public address.', 'treats' ),
			'section'     => 'treats_contact',
			'type'        => 'email',
			'sanitize'    => 'sanitize_email',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_map_embed_url',
		array(
			'label'       => __( 'Google Maps embed URL', 'treats' ),
			'description' => __( 'Optional. Leave blank to generate one from the address above.', 'treats' ),
			'section'     => 'treats_contact',
			'type'        => 'url',
			'sanitize'    => 'esc_url_raw',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_travel_note',
		array(
			'label'    => __( 'Getting here note', 'treats' ),
			'section'  => 'treats_contact',
			'type'     => 'textarea',
			'default'  => 'A two-minute walk from Durham Market Place, at the foot of Silver Street beside Framwellgate Bridge. The nearest car parks are Prince Bishops and Walkergate.',
			'sanitize' => 'sanitize_textarea_field',
		)
	);

	/* --------------------------------------------------------------- Hours */

	$hour_defaults = array(
		0 => array( '08:30', '17:00' ),
		1 => array( '08:30', '17:00' ),
		2 => array( '08:30', '17:00' ),
		3 => array( '08:30', '17:00' ),
		4 => array( '08:30', '17:00' ),
		5 => array( '08:30', '17:00' ),
		6 => array( '08:30', '17:00' ),
	);

	foreach ( $hour_defaults as $index => $defaults ) {
		treats_add_control(
			$wp_customize,
			'treats_hours_' . $index . '_closed',
			array(
				/* translators: %s: weekday name. */
				'label'    => sprintf( __( '%s — closed all day', 'treats' ), treats_weekday_label( $index ) ),
				'section'  => 'treats_hours',
				'type'     => 'checkbox',
				'default'  => false,
				'sanitize' => 'treats_sanitize_checkbox',
			)
		);

		treats_add_control(
			$wp_customize,
			'treats_hours_' . $index . '_open',
			array(
				/* translators: %s: weekday name. */
				'label'    => sprintf( __( '%s — opens', 'treats' ), treats_weekday_label( $index ) ),
				'section'  => 'treats_hours',
				'type'     => 'time',
				'default'  => $defaults[0],
				'sanitize' => 'treats_sanitize_time',
			)
		);

		treats_add_control(
			$wp_customize,
			'treats_hours_' . $index . '_close',
			array(
				/* translators: %s: weekday name. */
				'label'    => sprintf( __( '%s — closes', 'treats' ), treats_weekday_label( $index ) ),
				'section'  => 'treats_hours',
				'type'     => 'time',
				'default'  => $defaults[1],
				'sanitize' => 'treats_sanitize_time',
			)
		);
	}

	treats_add_control(
		$wp_customize,
		'treats_hours_note',
		array(
			'label'    => __( 'Opening hours note', 'treats' ),
			'section'  => 'treats_hours',
			'type'     => 'textarea',
			'default'  => 'Last orders 30 minutes before closing. Bank holiday hours may vary.',
			'sanitize' => 'sanitize_textarea_field',
		)
	);

	/* -------------------------------------------------------------- Social */

	$socials = array(
		'treats_social_facebook'    => __( 'Facebook URL', 'treats' ),
		'treats_social_instagram'   => __( 'Instagram URL', 'treats' ),
		'treats_social_tripadvisor' => __( 'Tripadvisor URL', 'treats' ),
		'treats_social_x'           => __( 'X (Twitter) URL', 'treats' ),
	);

	foreach ( $socials as $id => $label ) {
		treats_add_control(
			$wp_customize,
			$id,
			array(
				'label'    => $label,
				'section'  => 'treats_social',
				'type'     => 'url',
				'sanitize' => 'esc_url_raw',
			)
		);
	}

	/* ---------------------------------------------------------------- Home */

	$home_fields = array(
		'treats_hero_eyebrow'   => array( __( 'Hero eyebrow', 'treats' ), 'Durham · Since 1984', 'text' ),
		'treats_hero_title'     => array( __( 'Hero heading', 'treats' ), 'A proper tea room in the heart of Durham', 'textarea' ),
		'treats_hero_intro'     => array( __( 'Hero paragraph', 'treats' ), 'Freshly baked cakes, generous breakfasts and traditional afternoon tea, served on Silver Street since 1984.', 'textarea' ),
		'treats_hero_cta_label' => array( __( 'Hero button label', 'treats' ), 'Book a table', 'text' ),
		'treats_hero_cta_url'   => array( __( 'Hero button URL', 'treats' ), '', 'url' ),
		'treats_hero_alt_label' => array( __( 'Hero secondary button label', 'treats' ), 'View our menus', 'text' ),
		'treats_hero_alt_url'   => array( __( 'Hero secondary button URL', 'treats' ), '', 'url' ),
	);

	foreach ( $home_fields as $id => $config ) {
		treats_add_control(
			$wp_customize,
			$id,
			array(
				'label'    => $config[0],
				'default'  => $config[1],
				'section'  => 'treats_home',
				'type'     => $config[2],
				'sanitize' => 'url' === $config[2] ? 'esc_url_raw' : ( 'textarea' === $config[2] ? 'sanitize_textarea_field' : 'sanitize_text_field' ),
			)
		);
	}

	treats_add_control(
		$wp_customize,
		'treats_home_show_content',
		array(
			'label'       => __( 'Show the Home page editor content', 'treats' ),
			'description' => __( 'Adds whatever is typed into the Home page in the editor below the designed sections. Off by default — a page carried over from a previous theme usually has old copy sitting in there.', 'treats' ),
			'section'     => 'treats_home',
			'type'        => 'checkbox',
			'default'     => false,
			'sanitize'    => 'treats_sanitize_checkbox',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_hero_image',
		array(
			'label'       => __( 'Hero image', 'treats' ),
			'description' => __( 'Landscape, at least 2400px wide. A sage gradient is used when empty.', 'treats' ),
			'section'     => 'treats_home',
			'type'        => 'image',
			'sanitize'    => 'esc_url_raw',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_story_title',
		array(
			'label'    => __( 'Story section heading', 'treats' ),
			'default'  => 'Thirty years of good food at good prices',
			'section'  => 'treats_home',
			'type'     => 'textarea',
			'sanitize' => 'sanitize_textarea_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_story_text',
		array(
			'label'    => __( 'Story section text', 'treats' ),
			'default'  => 'Treats has been part of Durham life for three decades. We bake every morning, cook everything to order and keep prices honest — the same welcome for students, families and visitors alike.',
			'section'  => 'treats_home',
			'type'     => 'textarea',
			'sanitize' => 'sanitize_textarea_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_story_image',
		array(
			'label'    => __( 'Story image', 'treats' ),
			'section'  => 'treats_home',
			'type'     => 'image',
			'sanitize' => 'esc_url_raw',
		)
	);

	/* ------------------------------------------------------- Afternoon tea */

	$tea_fields = array(
		'treats_tea_price'      => array( __( 'Price', 'treats' ), '44', 'text' ),
		'treats_tea_serves'     => array( __( 'What the price covers', 'treats' ), 'for two', 'text' ),
		'treats_tea_includes'   => array( __( 'What arrives on the stand', 'treats' ), '2 afternoon tea dainty sandwiches on white or brown, 2 mini corned beef pies, 2 mini quiches, 2 scones with preserve and clotted cream, 2 cakes from the display, Any 2 hot drinks', 'textarea' ),
		'treats_tea_sandwiches' => array( __( 'Sandwich choices', 'treats' ), 'Cheese savoury, Smoked salmon cream cheese, Hummus cucumber tomato, Ham, Tuna cucumber, Prawn Marie Rose, Cheese and pickle', 'textarea' ),
		'treats_tea_upgrade'    => array( __( 'Upgrade line', 'treats' ), 'Upgrade to an alcoholic beverage for £8 extra.', 'text' ),
		'treats_tea_note'       => array( __( 'Booking note', 'treats' ), 'Booking is recommended, especially at weekends. Vegan and gluten free afternoon teas are available with 24 hours’ notice.', 'textarea' ),
	);

	foreach ( $tea_fields as $id => $config ) {
		treats_add_control(
			$wp_customize,
			$id,
			array(
				'label'       => $config[0],
				'description' => 'textarea' === $config[2] ? __( 'Separate each item with a comma.', 'treats' ) : '',
				'default'     => $config[1],
				'section'     => 'treats_tea',
				'type'        => $config[2],
				'sanitize'    => 'textarea' === $config[2] ? 'sanitize_textarea_field' : 'sanitize_text_field',
			)
		);
	}

	treats_add_control(
		$wp_customize,
		'treats_tea_gift_product',
		array(
			'label'       => __( 'Sell as a gift', 'treats' ),
			'description' => __( 'Pick a shop product and the Afternoon Tea page will offer it for sale — an afternoon tea bought as a present rather than booked for yourself.', 'treats' ),
			'section'     => 'treats_tea',
			'type'        => 'select',
			'choices'     => treats_product_choices(),
			'sanitize'    => 'absint',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_tea_image',
		array(
			'label'       => __( 'Photograph', 'treats' ),
			'description' => __( 'The stand, ideally. Landscape, at least 1600px wide.', 'treats' ),
			'section'     => 'treats_tea',
			'type'        => 'image',
			'sanitize'    => 'esc_url_raw',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_menu_pdf',
		array(
			'label'       => __( 'Printed menu PDF', 'treats' ),
			'description' => __( 'Offered as a download on the Menus page. Leave empty to use the copy that ships with the theme.', 'treats' ),
			'section'     => 'treats_tea',
			'type'        => 'url',
			'sanitize'    => 'esc_url_raw',
		)
	);

	/* -------------------------------------------------- Evening venue hire */

	$event_fields = array(
		'treats_event_hours'       => array( __( 'Availability', 'treats' ), 'Available every day, 6.30pm – 10pm', 'text' ),
		'treats_event_price'       => array( __( 'Full venue hire price', 'treats' ), '250', 'text' ),
		'treats_event_price_note'  => array( __( 'What the hire includes', 'treats' ), 'Includes 2 staff', 'text' ),
		'treats_event_intro'       => array( __( 'Intro', 'treats' ), 'Host your special occasion in our beautiful tea room setting. Exclusive use of the entire venue — perfect for birthdays, celebrations, meetings and special occasions.', 'textarea' ),
		'treats_event_tea_price'   => array( __( 'Afternoon tea package, per person', 'treats' ), '16.50', 'text' ),
		'treats_event_tea_items'   => array( __( 'Afternoon tea package includes', 'treats' ), 'Sandwich selection, Mini pies & quiche, Scone with preserve & clotted cream, Cake, Any hot drink', 'textarea' ),
		'treats_event_finger_price' => array( __( 'Finger food package, per person', 'treats' ), '12.50', 'text' ),
		'treats_event_finger_items' => array( __( 'Finger food package includes', 'treats' ), 'Selection of sandwiches, Mini pies & quiche, Crisps & dips', 'textarea' ),
	);

	foreach ( $event_fields as $id => $config ) {
		treats_add_control(
			$wp_customize,
			$id,
			array(
				'label'    => $config[0],
				'default'  => $config[1],
				'section'  => 'treats_events',
				'type'     => $config[2],
				'sanitize' => 'textarea' === $config[2] ? 'sanitize_textarea_field' : 'sanitize_text_field',
			)
		);
	}

	treats_add_control(
		$wp_customize,
		'treats_event_image',
		array(
			'label'       => __( 'Photograph', 'treats' ),
			'description' => __( 'The room set up for an event. Landscape, at least 1600px wide.', 'treats' ),
			'section'     => 'treats_events',
			'type'        => 'image',
			'sanitize'    => 'esc_url_raw',
		)
	);

	/* ------------------------------------------------------------- Booking */

	treats_add_control(
		$wp_customize,
		'treats_booking_external_url',
		array(
			'label'       => __( 'External booking system URL', 'treats' ),
			'description' => __( 'Optional. If set, booking buttons point here instead of the built-in form.', 'treats' ),
			'section'     => 'treats_booking',
			'type'        => 'url',
			'sanitize'    => 'esc_url_raw',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_booking_embed',
		array(
			'label'       => __( 'Booking widget embed code', 'treats' ),
			'description' => __( 'Optional. Paste an iframe from OpenTable, ResDiary or similar to replace the built-in form.', 'treats' ),
			'section'     => 'treats_booking',
			'type'        => 'textarea',
			'sanitize'    => 'treats_sanitize_embed',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_booking_max_party',
		array(
			'label'    => __( 'Largest party bookable online', 'treats' ),
			'section'  => 'treats_booking',
			'type'     => 'number',
			'default'  => 12,
			'sanitize' => 'absint',
			'input_attrs' => array(
				'min' => 1,
				'max' => 60,
			),
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_booking_lead_hours',
		array(
			'label'       => __( 'Minimum notice (hours)', 'treats' ),
			'section'     => 'treats_booking',
			'type'        => 'number',
			'default'     => 2,
			'sanitize'    => 'absint',
			'input_attrs' => array( 'min' => 0 ),
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_booking_max_days',
		array(
			'label'       => __( 'How far ahead bookings are accepted (days)', 'treats' ),
			'section'     => 'treats_booking',
			'type'        => 'number',
			'default'     => 90,
			'sanitize'    => 'absint',
			'input_attrs' => array( 'min' => 1 ),
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_booking_slots',
		array(
			'label'       => __( 'Bookable times', 'treats' ),
			'description' => __( 'One per line, 24-hour format. Leave blank to generate them from opening hours.', 'treats' ),
			'section'     => 'treats_booking',
			'type'        => 'textarea',
			'sanitize'    => 'sanitize_textarea_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_booking_note',
		array(
			'label'    => __( 'Booking form note', 'treats' ),
			'section'  => 'treats_booking',
			'type'     => 'textarea',
			'default'  => 'Requests are confirmed by email or phone, usually within a couple of hours during opening times. For parties over 12, please call us.',
			'sanitize' => 'sanitize_textarea_field',
		)
	);

	/* ------------------------------------------------------------- Collect */

	treats_add_control(
		$wp_customize,
		'treats_collect_enabled',
		array(
			'label'       => __( 'Let people order food for collection', 'treats' ),
			'description' => __( 'Turn this off to make the menus read-only: dishes and prices still show, but nothing on them can be added to an order. The shop is separate and is not affected.', 'treats' ),
			'section'     => 'treats_collect',
			'type'        => 'checkbox',
			'default'     => false,
			'sanitize'    => 'treats_sanitize_checkbox',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_collect_notice',
		array(
			'label'    => __( 'Collection notice', 'treats' ),
			'section'  => 'treats_collect',
			'type'     => 'textarea',
			'default'  => 'Collection orders need 2 hours notice. We will call to confirm your time and take payment on collection.',
			'sanitize' => 'sanitize_textarea_field',
		)
	);

	/* ------------------------------------------------------------ Vouchers */

	treats_add_control(
		$wp_customize,
		'treats_voucher_product',
		array(
			'label'       => __( 'Voucher people can buy online', 'treats' ),
			'description' => __( 'Pick the shop product for your printed voucher card. The page will take payment for it instead of only taking enquiries.', 'treats' ),
			'section'     => 'treats_vouchers',
			'type'        => 'select',
			'choices'     => treats_product_choices(),
			'sanitize'    => 'absint',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_voucher_intro',
		array(
			'label'    => __( 'Voucher page intro', 'treats' ),
			'section'  => 'treats_vouchers',
			'type'     => 'textarea',
			'default'  => 'A Treats gift voucher is the easiest way to send someone a proper afternoon in Durham. Choose an amount or one of our afternoon tea experiences.',
			'sanitize' => 'sanitize_textarea_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_voucher_amounts',
		array(
			'label'       => __( 'Voucher amounts', 'treats' ),
			'description' => __( 'Comma separated, numbers only.', 'treats' ),
			'section'     => 'treats_vouchers',
			'default'     => '10, 20, 25, 50, 75, 100',
			'sanitize'    => 'sanitize_text_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_voucher_terms',
		array(
			'label'    => __( 'Voucher terms', 'treats' ),
			'section'  => 'treats_vouchers',
			'type'     => 'textarea',
			'default'  => 'Vouchers are valid for 12 months from the date of purchase, can be used against anything on our menus, and are non-refundable and non-transferable for cash.',
			'sanitize' => 'sanitize_textarea_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_voucher_checkout_url',
		array(
			'label'       => __( 'Payment link', 'treats' ),
			'description' => __( 'Optional. A Stripe, SumUp or Square payment link. When set, buyers are sent here after submitting their details.', 'treats' ),
			'section'     => 'treats_vouchers',
			'type'        => 'url',
			'sanitize'    => 'esc_url_raw',
		)
	);

	/* ---------------------------------------------------------------- Shop */

	treats_add_control(
		$wp_customize,
		'treats_home_show_shop',
		array(
			'label'       => __( 'Show the shop on the home page', 'treats' ),
			'description' => __( 'A strip of three products with their photographs. Hides itself when there is nothing published to sell.', 'treats' ),
			'section'     => 'treats_shop',
			'type'        => 'checkbox',
			'default'     => true,
			'sanitize'    => 'treats_sanitize_checkbox',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_home_shop_title',
		array(
			'label'    => __( 'Home page shop heading', 'treats' ),
			'section'  => 'treats_shop',
			'default'  => __( 'From our shop', 'treats' ),
			'sanitize' => 'sanitize_text_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_home_shop_text',
		array(
			'label'    => __( 'Home page shop intro', 'treats' ),
			'section'  => 'treats_shop',
			'type'     => 'textarea',
			'default'  => __( 'Cast iron teapots, trivets and gift vouchers — posted out to you, or waiting behind the counter.', 'treats' ),
			'sanitize' => 'sanitize_textarea_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_shop_enabled',
		array(
			'label'       => __( 'Open the shop', 'treats' ),
			'description' => __( 'Until this is on — and Square is filled in below — products are visible but cannot be bought.', 'treats' ),
			'section'     => 'treats_shop',
			'type'        => 'checkbox',
			'default'     => false,
			'sanitize'    => 'treats_sanitize_checkbox',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_square_env',
		array(
			'label'       => __( 'Square mode', 'treats' ),
			'description' => __( 'Test mode uses Square’s sandbox and takes no real money. Switch to live only once a test order has gone through.', 'treats' ),
			'section'     => 'treats_shop',
			'type'        => 'select',
			'default'     => 'sandbox',
			'choices'     => array(
				'sandbox'    => __( 'Test mode (sandbox)', 'treats' ),
				'production' => __( 'Live — take real payments', 'treats' ),
			),
			'sanitize'    => 'treats_sanitize_square_env',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_square_app_id',
		array(
			'label'       => __( 'Square application ID', 'treats' ),
			'description' => __( 'From the Square Developer dashboard. Starts sq0idp- when live, sandbox-sq0idb- in test mode.', 'treats' ),
			'section'     => 'treats_shop',
			'sanitize'    => 'sanitize_text_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_square_location_id',
		array(
			'label'       => __( 'Square location ID', 'treats' ),
			'description' => __( 'The shop the money is taken against.', 'treats' ),
			'section'     => 'treats_shop',
			'sanitize'    => 'sanitize_text_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_square_token',
		array(
			'label'       => __( 'Square access token', 'treats' ),
			'description' => __( 'Safer in wp-config.php as TREATS_SQUARE_TOKEN. A token stored here is readable by anyone who can edit the site.', 'treats' ),
			'section'     => 'treats_shop',
			'sanitize'    => 'sanitize_text_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_shop_collect_enabled',
		array(
			'label'       => __( 'Offer collection from the café', 'treats' ),
			'description' => __( 'Turn this off if shop orders are always posted. Individual products can still be marked collection only.', 'treats' ),
			'section'     => 'treats_shop',
			'type'        => 'checkbox',
			'default'     => true,
			'sanitize'    => 'treats_sanitize_checkbox',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_postage_enabled',
		array(
			'label'       => __( 'Offer postage as well as collection', 'treats' ),
			'description' => __( 'Turn off to make everything collection only.', 'treats' ),
			'section'     => 'treats_shop',
			'type'        => 'checkbox',
			'default'     => true,
			'sanitize'    => 'treats_sanitize_checkbox',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_postage_extra_item',
		array(
			'label'       => __( 'Extra charge per additional item', 'treats' ),
			'description' => __( 'An order pays the highest postage among its items, plus this for each item after the first. Leave at 0 if everything goes in one box.', 'treats' ),
			'section'     => 'treats_shop',
			'default'     => '0',
			'sanitize'    => 'sanitize_text_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_postage_free_over',
		array(
			'label'       => __( 'Free postage over', 'treats' ),
			'description' => __( 'Optional. Leave empty for no free postage threshold.', 'treats' ),
			'section'     => 'treats_shop',
			'sanitize'    => 'sanitize_text_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_collect_lead_hours',
		array(
			'label'       => __( 'Notice needed before collection (hours)', 'treats' ),
			'section'     => 'treats_shop',
			'type'        => 'number',
			'default'     => 24,
			'sanitize'    => 'absint',
			'input_attrs' => array(
				'min'  => 0,
				'max'  => 336,
				'step' => 1,
			),
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_shop_dispatch_note',
		array(
			'label'       => __( 'Dispatch note', 'treats' ),
			'description' => __( 'Shown at checkout and repeated in the confirmation email.', 'treats' ),
			'section'     => 'treats_shop',
			'type'        => 'textarea',
			'default'     => __( 'Posted orders are sent within three working days by tracked delivery.', 'treats' ),
			'sanitize'    => 'sanitize_textarea_field',
		)
	);

	/* ----------------------------------------------------------- Instagram */

	treats_add_control(
		$wp_customize,
		'treats_instagram_handle',
		array(
			'label'    => __( 'Instagram handle', 'treats' ),
			'section'  => 'treats_instagram',
			'default'  => 'treatstearoomdurham',
			'sanitize' => 'sanitize_text_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_instagram_token',
		array(
			'label'       => __( 'Instagram Basic Display access token', 'treats' ),
			'description' => __( 'Optional. With a token the feed pulls live posts; without one, the curated gallery below is shown instead.', 'treats' ),
			'section'     => 'treats_instagram',
			'sanitize'    => 'sanitize_text_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_instagram_count',
		array(
			'label'       => __( 'Number of posts', 'treats' ),
			'section'     => 'treats_instagram',
			'type'        => 'number',
			'default'     => 6,
			'sanitize'    => 'absint',
			'input_attrs' => array(
				'min' => 3,
				'max' => 12,
			),
		)
	);

	/* ---------------------------------------------------------- Newsletter */

	treats_add_control(
		$wp_customize,
		'treats_newsletter_title',
		array(
			'label'    => __( 'Newsletter heading', 'treats' ),
			'section'  => 'treats_newsletter',
			'default'  => 'Seasonal menus, straight to your inbox',
			'sanitize' => 'sanitize_text_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_newsletter_text',
		array(
			'label'    => __( 'Newsletter text', 'treats' ),
			'section'  => 'treats_newsletter',
			'type'     => 'textarea',
			'default'  => 'One short email a month: new bakes, seasonal afternoon teas and the odd offer. No spam, unsubscribe any time.',
			'sanitize' => 'sanitize_textarea_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_newsletter_action',
		array(
			'label'       => __( 'External list URL', 'treats' ),
			'description' => __( 'Optional Mailchimp/Brevo form action. Without one, subscribers are stored in WordPress.', 'treats' ),
			'section'     => 'treats_newsletter',
			'type'        => 'url',
			'sanitize'    => 'esc_url_raw',
		)
	);

	/* ----------------------------------------------------------------- SEO */

	treats_add_control(
		$wp_customize,
		'treats_seo_description',
		array(
			'label'       => __( 'Default meta description', 'treats' ),
			'description' => __( 'Used on the home page and anywhere without its own excerpt. Aim for 150–160 characters.', 'treats' ),
			'section'     => 'treats_seo',
			'type'        => 'textarea',
			'default'     => 'Treats Tea Room Café on Silver Street, Durham. Breakfast and brunch, lunch, traditional afternoon tea and award-winning cakes, baked fresh every morning.',
			'sanitize'    => 'sanitize_textarea_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_seo_share_image',
		array(
			'label'    => __( 'Default sharing image', 'treats' ),
			'section'  => 'treats_seo',
			'type'     => 'image',
			'sanitize' => 'esc_url_raw',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_seo_price_range',
		array(
			'label'    => __( 'Price range', 'treats' ),
			'section'  => 'treats_seo',
			'default'  => '££',
			'sanitize' => 'sanitize_text_field',
		)
	);

	treats_add_control(
		$wp_customize,
		'treats_seo_founding_year',
		array(
			'label'    => __( 'Year established', 'treats' ),
			'section'  => 'treats_seo',
			'default'  => '1984',
			'sanitize' => 'sanitize_text_field',
		)
	);

	/* ------------------------------------------- Selective refresh partials */

	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->selective_refresh->add_partial(
			'blogname',
			array(
				'selector'        => '.site-logo__name',
				'render_callback' => 'treats_get_business_name',
			)
		);
	}
}
add_action( 'customize_register', 'treats_customize_register' );

/**
 * Sanitize a third-party embed, allowing only iframes and scripts from https.
 *
 * @param string $value Raw markup.
 * @return string
 */
function treats_sanitize_embed( $value ) {
	if ( ! current_user_can( 'unfiltered_html' ) ) {
		return wp_kses_post( $value );
	}

	$allowed = array(
		'iframe' => array(
			'src'             => true,
			'width'           => true,
			'height'          => true,
			'title'           => true,
			'style'           => true,
			'frameborder'     => true,
			'allow'           => true,
			'allowfullscreen' => true,
			'loading'         => true,
			'referrerpolicy'  => true,
			'id'              => true,
			'class'           => true,
		),
		'div'    => array(
			'id'    => true,
			'class' => true,
			'style' => true,
			'data-*' => true,
		),
		'script' => array(
			'src'   => true,
			'async' => true,
			'defer' => true,
			'type'  => true,
			'id'    => true,
		),
	);

	return wp_kses( $value, $allowed );
}

/**
 * Live-preview script for the Customizer.
 *
 * @return void
 */
function treats_customize_preview_js() {
	wp_enqueue_script(
		'treats-customizer',
		TREATS_URI . 'js/customizer.js',
		array( 'customize-preview' ),
		treats_asset_version( 'js/customizer.js' ),
		true
	);
}
add_action( 'customize_preview_init', 'treats_customize_preview_js' );

/**
 * Emit the accent colour override chosen in the Customizer.
 *
 * @return void
 */
function treats_customizer_css() {
	$accent = get_theme_mod( 'treats_accent_color', '#8a6d2c' );

	if ( '#8a6d2c' === strtolower( (string) $accent ) || ! $accent ) {
		return;
	}

	printf(
		'<style id="treats-accent">:root{--c-accent:%1$s;--c-accent-strong:%1$s}</style>' . "\n",
		esc_attr( $accent )
	);
}
add_action( 'wp_head', 'treats_customizer_css', 20 );
