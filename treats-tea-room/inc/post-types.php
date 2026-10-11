<?php
/**
 * Custom post types and taxonomies.
 *
 * Menu items, reviews and FAQs are editorial content. Bookings, enquiries and
 * voucher orders are private records created by the front-end forms; they are
 * readable in the admin but cannot be created there.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register post types.
 *
 * @return void
 */
function treats_register_post_types() {
	register_post_type(
		'treats_menu_item',
		array(
			'labels'              => array(
				'name'               => __( 'Menu Items', 'treats' ),
				'singular_name'      => __( 'Menu Item', 'treats' ),
				'add_new_item'       => __( 'Add New Menu Item', 'treats' ),
				'edit_item'          => __( 'Edit Menu Item', 'treats' ),
				'search_items'       => __( 'Search Menu Items', 'treats' ),
				'not_found'          => __( 'No menu items yet.', 'treats' ),
				'all_items'          => __( 'All Menu Items', 'treats' ),
				'menu_name'          => __( 'Menu', 'treats' ),
			),
			'public'              => true,
			'has_archive'         => true,
			'rewrite'             => array(
				'slug'       => 'menu',
				'with_front' => false,
			),
			'menu_icon'           => 'dashicons-food',
			'menu_position'       => 21,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions' ),
			'show_in_rest'        => true,
			'hierarchical'        => false,
			'exclude_from_search' => false,
			'taxonomies'          => array( 'treats_menu_category', 'treats_dietary' ),
		)
	);

	register_post_type(
		'treats_review',
		array(
			'labels'        => array(
				'name'          => __( 'Reviews', 'treats' ),
				'singular_name' => __( 'Review', 'treats' ),
				'add_new_item'  => __( 'Add New Review', 'treats' ),
				'edit_item'     => __( 'Edit Review', 'treats' ),
				'menu_name'     => __( 'Reviews', 'treats' ),
			),
			'public'        => false,
			'show_ui'       => true,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-star-filled',
			'menu_position' => 22,
			'supports'      => array( 'title', 'editor', 'page-attributes' ),
		)
	);

	register_post_type(
		'treats_faq',
		array(
			'labels'        => array(
				'name'          => __( 'FAQs', 'treats' ),
				'singular_name' => __( 'FAQ', 'treats' ),
				'add_new_item'  => __( 'Add New FAQ', 'treats' ),
				'edit_item'     => __( 'Edit FAQ', 'treats' ),
				'menu_name'     => __( 'FAQs', 'treats' ),
			),
			'public'        => false,
			'show_ui'       => true,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-editor-help',
			'menu_position' => 23,
			'supports'      => array( 'title', 'editor', 'page-attributes' ),
			'taxonomies'    => array( 'treats_faq_topic' ),
		)
	);

	// Private records written by the front-end forms.
	$record_types = array(
		'treats_booking'  => array( __( 'Bookings', 'treats' ), __( 'Booking', 'treats' ), 'dashicons-calendar-alt', 24 ),
		'treats_enquiry'  => array( __( 'Enquiries', 'treats' ), __( 'Enquiry', 'treats' ), 'dashicons-email-alt', 25 ),
		'treats_event'    => array( __( 'Event Enquiries', 'treats' ), __( 'Event Enquiry', 'treats' ), 'dashicons-tickets-alt', 25 ),
		'treats_order'    => array( __( 'Collection Orders', 'treats' ), __( 'Collection Order', 'treats' ), 'dashicons-cart', 26 ),
		'treats_voucher'  => array( __( 'Voucher Orders', 'treats' ), __( 'Voucher Order', 'treats' ), 'dashicons-tickets-alt', 27 ),
		'treats_subscriber' => array( __( 'Subscribers', 'treats' ), __( 'Subscriber', 'treats' ), 'dashicons-megaphone', 28 ),
	);

	foreach ( $record_types as $slug => $config ) {
		list( $plural, $singular, $icon, $position ) = $config;

		register_post_type(
			$slug,
			array(
				'labels'              => array(
					'name'          => $plural,
					'singular_name' => $singular,
					'menu_name'     => $plural,
					'edit_item'     => sprintf( /* translators: %s: record label. */ __( 'View %s', 'treats' ), $singular ),
					'not_found'     => __( 'Nothing here yet.', 'treats' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_rest'        => false,
				'menu_icon'           => $icon,
				'menu_position'       => $position,
				'supports'            => array( 'title' ),
				'capabilities'        => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap'        => true,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'has_archive'         => false,
				'rewrite'             => false,
			)
		);
	}
}
add_action( 'init', 'treats_register_post_types' );

/**
 * Register taxonomies.
 *
 * @return void
 */
function treats_register_taxonomies() {
	register_taxonomy(
		'treats_menu_category',
		array( 'treats_menu_item' ),
		array(
			'labels'            => array(
				'name'          => __( 'Menu Categories', 'treats' ),
				'singular_name' => __( 'Menu Category', 'treats' ),
				'add_new_item'  => __( 'Add New Menu Category', 'treats' ),
				'menu_name'     => __( 'Categories', 'treats' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'menu',
				'with_front' => false,
			),
		)
	);

	register_taxonomy(
		'treats_dietary',
		array( 'treats_menu_item' ),
		array(
			'labels'            => array(
				'name'          => __( 'Dietary Labels', 'treats' ),
				'singular_name' => __( 'Dietary Label', 'treats' ),
				'add_new_item'  => __( 'Add New Dietary Label', 'treats' ),
				'menu_name'     => __( 'Dietary', 'treats' ),
			),
			'public'            => true,
			'hierarchical'      => false,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'dietary' ),
		)
	);

	register_taxonomy(
		'treats_faq_topic',
		array( 'treats_faq' ),
		array(
			'labels'            => array(
				'name'          => __( 'FAQ Topics', 'treats' ),
				'singular_name' => __( 'FAQ Topic', 'treats' ),
				'menu_name'     => __( 'Topics', 'treats' ),
			),
			'public'            => false,
			'show_ui'           => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
		)
	);
}
add_action( 'init', 'treats_register_taxonomies' );

/**
 * Seed the dietary labels the café actually uses, once.
 *
 * @return void
 */
function treats_seed_dietary_terms() {
	if ( get_option( 'treats_dietary_seeded' ) ) {
		return;
	}

	$terms = array(
		'v'   => __( 'Vegetarian', 'treats' ),
		've'  => __( 'Vegan', 'treats' ),
		'gf'  => __( 'Gluten Free', 'treats' ),
		'gfa' => __( 'Gluten Free Available', 'treats' ),
		'df'  => __( 'Dairy Free', 'treats' ),
		'n'   => __( 'Contains Nuts', 'treats' ),
	);

	foreach ( $terms as $slug => $name ) {
		if ( ! term_exists( $slug, 'treats_dietary' ) ) {
			wp_insert_term( $name, 'treats_dietary', array( 'slug' => $slug ) );
		}
	}

	update_option( 'treats_dietary_seeded', 1, false );
}
add_action( 'init', 'treats_seed_dietary_terms', 20 );

/**
 * Order menu items by their menu_order, then title.
 *
 * @param WP_Query $query Query instance.
 * @return void
 */
function treats_menu_query_order( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_post_type_archive( 'treats_menu_item' ) || $query->is_tax( 'treats_menu_category' ) ) {
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
		$query->set( 'posts_per_page', 100 );
	}
}
add_action( 'pre_get_posts', 'treats_menu_query_order' );

/**
 * Fetch menu items for a category.
 *
 * @param string $category Term slug or ID. Empty for all.
 * @param int    $limit    Max items.
 * @return WP_Post[]
 */
function treats_get_menu_items( $category = '', $limit = 200 ) {
	$args = array(
		'post_type'      => 'treats_menu_item',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $limit,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
		'no_found_rows'  => true,
	);

	if ( '' !== $category ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy'         => 'treats_menu_category',
				'field'            => is_numeric( $category ) ? 'term_id' : 'slug',
				'terms'            => $category,
				'include_children' => true,
			),
		);
	}

	return get_posts( $args );
}

/**
 * Group menu items by their deepest menu category, preserving term order.
 *
 * @param WP_Post[] $items      Menu items.
 * @param int       $parent_term Parent term ID to group beneath.
 * @return array<int,array<string,mixed>>
 */
function treats_group_menu_items( $items, $parent_term = 0 ) {
	$groups   = array();
	$ungrouped = array();

	$children = get_terms(
		array(
			'taxonomy'   => 'treats_menu_category',
			'parent'     => (int) $parent_term,
			'hide_empty' => true,
		)
	);

	if ( is_wp_error( $children ) ) {
		$children = array();
	}

	foreach ( $children as $child ) {
		$groups[ $child->term_id ] = array(
			'term'  => $child,
			'items' => array(),
		);
	}

	foreach ( $items as $item ) {
		$placed = false;

		foreach ( $groups as $term_id => $group ) {
			if ( has_term( $term_id, 'treats_menu_category', $item ) ) {
				$groups[ $term_id ]['items'][] = $item;
				$placed                        = true;
				break;
			}
		}

		if ( ! $placed ) {
			$ungrouped[] = $item;
		}
	}

	// Drop empty groups so the layout never renders a bare heading.
	$groups = array_values(
		array_filter(
			$groups,
			static function ( $group ) {
				return ! empty( $group['items'] );
			}
		)
	);

	if ( $ungrouped ) {
		$groups[] = array(
			'term'  => null,
			'items' => $ungrouped,
		);
	}

	return $groups;
}

/**
 * Reviews for the testimonial slider.
 *
 * @param int $limit Max reviews.
 * @return WP_Post[]
 */
function treats_get_reviews( $limit = 9 ) {
	return get_posts(
		array(
			'post_type'      => 'treats_review',
			'post_status'    => 'publish',
			'posts_per_page' => (int) $limit,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'no_found_rows'  => true,
		)
	);
}

/**
 * FAQs, optionally filtered by topic.
 *
 * @param string $topic Topic slug.
 * @param int    $limit Max FAQs.
 * @return WP_Post[]
 */
function treats_get_faqs( $topic = '', $limit = 100 ) {
	$args = array(
		'post_type'      => 'treats_faq',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $limit,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
		'no_found_rows'  => true,
	);

	if ( '' !== $topic ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => 'treats_faq_topic',
				'field'    => 'slug',
				'terms'    => $topic,
			),
		);
	}

	return get_posts( $args );
}

/**
 * Admin columns for booking records.
 *
 * @param array<string,string> $columns Existing columns.
 * @return array<string,string>
 */
function treats_booking_columns( $columns ) {
	return array(
		'cb'             => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'          => __( 'Reference', 'treats' ),
		'treats_when'    => __( 'Date & time', 'treats' ),
		'treats_party'   => __( 'Party', 'treats' ),
		'treats_contact' => __( 'Contact', 'treats' ),
		'date'           => __( 'Received', 'treats' ),
	);
}
add_filter( 'manage_treats_booking_posts_columns', 'treats_booking_columns' );

/**
 * Render booking admin columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 * @return void
 */
function treats_booking_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'treats_when':
			$date = get_post_meta( $post_id, '_treats_date', true );
			$time = get_post_meta( $post_id, '_treats_time', true );
			echo esc_html( trim( $date . ' ' . $time ) );
			break;

		case 'treats_party':
			echo esc_html( (string) get_post_meta( $post_id, '_treats_party', true ) );
			break;

		case 'treats_contact':
			$email = get_post_meta( $post_id, '_treats_email', true );
			$phone = get_post_meta( $post_id, '_treats_phone', true );

			if ( $email ) {
				printf( '<a href="mailto:%1$s">%1$s</a><br>', esc_attr( $email ) );
			}

			echo esc_html( $phone );
			break;
	}
}
add_action( 'manage_treats_booking_posts_custom_column', 'treats_booking_column_content', 10, 2 );

/**
 * Show the stored submission details on record screens.
 *
 * @return void
 */
function treats_record_details_meta_box() {
	$types = array( 'treats_booking', 'treats_enquiry', 'treats_event', 'treats_order', 'treats_voucher', 'treats_subscriber' );

	foreach ( $types as $type ) {
		add_meta_box(
			'treats-record-details',
			__( 'Submission details', 'treats' ),
			'treats_render_record_details',
			$type,
			'normal',
			'high'
		);
	}
}
add_action( 'add_meta_boxes', 'treats_record_details_meta_box' );

/**
 * Render the read-only submission details table.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function treats_render_record_details( $post ) {
	$meta   = get_post_meta( $post->ID );
	$labels = array(
		'_treats_name'     => __( 'Name', 'treats' ),
		'_treats_email'    => __( 'Email', 'treats' ),
		'_treats_phone'    => __( 'Phone', 'treats' ),
		'_treats_date'     => __( 'Date', 'treats' ),
		'_treats_time'     => __( 'Time', 'treats' ),
		'_treats_party'    => __( 'Party size', 'treats' ),
		'_treats_occasion' => __( 'Occasion', 'treats' ),
		'_treats_notes'    => __( 'Notes', 'treats' ),
		'_treats_message'  => __( 'Message', 'treats' ),
		'_treats_subject'  => __( 'Subject', 'treats' ),
		'_treats_items'    => __( 'Items', 'treats' ),
		'_treats_total'    => __( 'Total', 'treats' ),
		'_treats_amount'   => __( 'Amount', 'treats' ),
		'_treats_recipient' => __( 'Recipient', 'treats' ),
		'_treats_delivery' => __( 'Delivery', 'treats' ),
		'_treats_marketing' => __( 'Marketing opt-in', 'treats' ),
		'_treats_source'   => __( 'Source page', 'treats' ),
		'_treats_ip_hash'  => __( 'IP (hashed)', 'treats' ),
	);

	echo '<table class="widefat striped"><tbody>';

	foreach ( $labels as $key => $label ) {
		if ( empty( $meta[ $key ][0] ) ) {
			continue;
		}

		printf(
			'<tr><th scope="row" style="width:180px">%s</th><td>%s</td></tr>',
			esc_html( $label ),
			nl2br( esc_html( $meta[ $key ][0] ) )
		);
	}

	echo '</tbody></table>';
}
