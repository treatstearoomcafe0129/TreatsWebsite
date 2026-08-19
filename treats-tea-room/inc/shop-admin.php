<?php
/**
 * Shop administration.
 *
 * Orders arrive paid, so the only decision left is whether they have been
 * packed and handed over. That is the whole workflow, and this screen is
 * built around it rather than around a generic list of meta fields.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * The states an order moves through.
 *
 * @return array<string,string>
 */
function treats_order_statuses() {
	return array(
		'paid'       => __( 'Paid — needs packing', 'treats' ),
		'ready'      => __( 'Ready to collect', 'treats' ),
		'dispatched' => __( 'Posted', 'treats' ),
		'completed'  => __( 'Collected / done', 'treats' ),
		'refunded'   => __( 'Refunded', 'treats' ),
	);
}

/* -------------------------------------------------------------------------
 * Order list
 * ---------------------------------------------------------------------- */

/**
 * Columns on the orders list.
 *
 * @param array $columns Default columns.
 * @return array
 */
function treats_shop_order_columns( $columns ) {
	return array(
		'cb'                => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'             => __( 'Reference', 'treats' ),
		'treats_customer'   => __( 'Customer', 'treats' ),
		'treats_fulfilment' => __( 'How', 'treats' ),
		'treats_total'      => __( 'Total', 'treats' ),
		'treats_status'     => __( 'Status', 'treats' ),
		'date'              => __( 'Placed', 'treats' ),
	);
}
add_filter( 'manage_treats_shop_order_posts_columns', 'treats_shop_order_columns' );

/**
 * Fill the order columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Order ID.
 * @return void
 */
function treats_shop_order_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'treats_customer':
			$email = (string) get_post_meta( $post_id, '_treats_email', true );

			echo esc_html( (string) get_post_meta( $post_id, '_treats_name', true ) ) . '<br>';

			if ( $email ) {
				printf( '<a href="mailto:%1$s">%1$s</a>', esc_attr( $email ) );
			}
			break;

		case 'treats_fulfilment':
			echo 'post' === get_post_meta( $post_id, '_treats_fulfilment', true )
				? esc_html__( 'Post', 'treats' )
				: esc_html__( 'Collect', 'treats' );
			break;

		case 'treats_total':
			echo esc_html( treats_money( (int) get_post_meta( $post_id, '_treats_total', true ) ) );
			break;

		case 'treats_status':
			$statuses = treats_order_statuses();
			$status   = (string) get_post_meta( $post_id, '_treats_status', true );

			printf(
				'<span class="treats-status treats-status--%1$s">%2$s</span>',
				esc_attr( $status ),
				esc_html( $statuses[ $status ] ?? $status )
			);
			break;
	}
}
add_action( 'manage_treats_shop_order_posts_custom_column', 'treats_shop_order_column_content', 10, 2 );

/**
 * Newest orders first, and let the reference column stand alone.
 *
 * @param WP_Query $query Current query.
 * @return void
 */
function treats_shop_order_admin_order( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( 'treats_shop_order' !== $query->get( 'post_type' ) ) {
		return;
	}

	$query->set( 'orderby', 'date' );
	$query->set( 'order', 'DESC' );
}
add_action( 'pre_get_posts', 'treats_shop_order_admin_order' );

/* -------------------------------------------------------------------------
 * Order detail
 * ---------------------------------------------------------------------- */

/**
 * Add the order screen's boxes.
 *
 * @return void
 */
function treats_shop_order_meta_boxes() {
	add_meta_box(
		'treats-order-detail',
		__( 'Order', 'treats' ),
		'treats_render_order_detail',
		'treats_shop_order',
		'normal',
		'high'
	);

	add_meta_box(
		'treats-order-status',
		__( 'Progress', 'treats' ),
		'treats_render_order_status',
		'treats_shop_order',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'treats_shop_order_meta_boxes' );

/**
 * The order itself.
 *
 * @param WP_Post $post Order.
 * @return void
 */
function treats_render_order_detail( $post ) {
	$items      = treats_order_items( $post->ID );
	$fulfilment = (string) get_post_meta( $post->ID, '_treats_fulfilment', true );
	$postage    = (int) get_post_meta( $post->ID, '_treats_postage', true );
	$receipt    = (string) get_post_meta( $post->ID, '_treats_receipt', true );
	$notes      = trim( (string) get_post_meta( $post->ID, '_treats_notes', true ) );

	echo '<table class="widefat striped" style="margin-bottom:20px"><thead><tr>';
	printf( '<th>%s</th>', esc_html__( 'Item', 'treats' ) );
	printf( '<th style="width:80px">%s</th>', esc_html__( 'Qty', 'treats' ) );
	printf( '<th style="width:110px">%s</th>', esc_html__( 'Total', 'treats' ) );
	echo '</tr></thead><tbody>';

	foreach ( $items as $item ) {
		printf(
			'<tr><td><strong>%1$s</strong>%2$s</td><td>%3$d</td><td>%4$s</td></tr>',
			esc_html( $item['title'] ),
			empty( $item['sku'] ) ? '' : '<br><span class="description">' . esc_html( $item['sku'] ) . '</span>',
			(int) $item['quantity'],
			esc_html( treats_money( (int) $item['total'] ) )
		);
	}

	if ( $postage > 0 ) {
		printf(
			'<tr><td colspan="2">%1$s</td><td>%2$s</td></tr>',
			esc_html__( 'Postage', 'treats' ),
			esc_html( treats_money( $postage ) )
		);
	}

	printf(
		'<tr><td colspan="2"><strong>%1$s</strong></td><td><strong>%2$s</strong></td></tr>',
		esc_html__( 'Paid', 'treats' ),
		esc_html( treats_money( (int) get_post_meta( $post->ID, '_treats_total', true ) ) )
	);

	echo '</tbody></table>';

	$rows = array(
		__( 'Name', 'treats' )  => (string) get_post_meta( $post->ID, '_treats_name', true ),
		__( 'Email', 'treats' ) => (string) get_post_meta( $post->ID, '_treats_email', true ),
		__( 'Phone', 'treats' ) => (string) get_post_meta( $post->ID, '_treats_phone', true ),
	);

	if ( 'post' === $fulfilment ) {
		$rows[ __( 'Post to', 'treats' ) ] = implode(
			"\n",
			array_filter(
				array(
					(string) get_post_meta( $post->ID, '_treats_address1', true ),
					(string) get_post_meta( $post->ID, '_treats_address2', true ),
					(string) get_post_meta( $post->ID, '_treats_city', true ),
					(string) get_post_meta( $post->ID, '_treats_postcode', true ),
				)
			)
		);
	} else {
		$rows[ __( 'Collecting on', 'treats' ) ] = (string) get_post_meta( $post->ID, '_treats_collect_date', true );
	}

	if ( '' !== $notes ) {
		$rows[ __( 'Customer notes', 'treats' ) ] = $notes;
	}

	$rows[ __( 'Card', 'treats' ) ] = (string) get_post_meta( $post->ID, '_treats_card', true );

	echo '<table class="widefat striped"><tbody>';

	foreach ( $rows as $label => $value ) {
		if ( '' === trim( (string) $value ) ) {
			continue;
		}

		printf(
			'<tr><th scope="row" style="width:180px">%s</th><td>%s</td></tr>',
			esc_html( $label ),
			nl2br( esc_html( $value ) )
		);
	}

	if ( '' !== $receipt ) {
		printf(
			'<tr><th scope="row">%1$s</th><td><a href="%2$s" target="_blank" rel="noopener">%3$s</a></td></tr>',
			esc_html__( 'Square receipt', 'treats' ),
			esc_url( $receipt ),
			esc_html__( 'Open in Square', 'treats' )
		);
	}

	echo '</tbody></table>';
}

/**
 * The one control this screen needs.
 *
 * @param WP_Post $post Order.
 * @return void
 */
function treats_render_order_status( $post ) {
	$current = (string) get_post_meta( $post->ID, '_treats_status', true );

	wp_nonce_field( 'treats_order_status', 'treats_order_status_nonce' );

	echo '<select name="treats_order_status" class="widefat">';

	foreach ( treats_order_statuses() as $value => $label ) {
		printf(
			'<option value="%1$s" %2$s>%3$s</option>',
			esc_attr( $value ),
			selected( $current, $value, false ),
			esc_html( $label )
		);
	}

	echo '</select>';

	printf(
		'<p class="description" style="margin-top:8px">%s</p>',
		esc_html__( 'Changing this is for your own records. Refunds are issued in Square, not here.', 'treats' )
	);
}

/**
 * Save the status.
 *
 * @param int $post_id Order ID.
 * @return void
 */
function treats_save_order_status( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( 'treats_shop_order' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['treats_order_status_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['treats_order_status_nonce'] ) ), 'treats_order_status' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$status = isset( $_POST['treats_order_status'] ) ? sanitize_key( wp_unslash( $_POST['treats_order_status'] ) ) : '';

	if ( array_key_exists( $status, treats_order_statuses() ) ) {
		update_post_meta( $post_id, '_treats_status', $status );
	}
}
add_action( 'save_post', 'treats_save_order_status' );

/* -------------------------------------------------------------------------
 * Product list
 * ---------------------------------------------------------------------- */

/**
 * Columns on the products list.
 *
 * @param array $columns Default columns.
 * @return array
 */
function treats_product_columns( $columns ) {
	$new = array();

	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;

		if ( 'title' === $key ) {
			$new['treats_price'] = __( 'Price', 'treats' );
			$new['treats_stock'] = __( 'Stock', 'treats' );
		}
	}

	return $new;
}
add_filter( 'manage_treats_product_posts_columns', 'treats_product_columns' );

/**
 * Fill the product columns.
 *
 * @param string $column     Column key.
 * @param int    $product_id Product ID.
 * @return void
 */
function treats_product_column_content( $column, $product_id ) {
	if ( 'treats_price' === $column ) {
		$price = treats_product_price( $product_id );

		if ( $price < 1 ) {
			printf( '<span style="color:#b32d2e">%s</span>', esc_html__( 'No price — cannot be bought', 'treats' ) );

			return;
		}

		echo esc_html( treats_money( $price ) );

		if ( treats_product_on_sale( $product_id ) ) {
			printf(
				' <span class="description">%s</span>',
				esc_html__( '(reduced)', 'treats' )
			);
		}

		return;
	}

	if ( 'treats_stock' !== $column ) {
		return;
	}

	$stock = treats_product_stock( $product_id );

	if ( null === $stock ) {
		printf( '<span class="description">%s</span>', esc_html__( 'Not counted', 'treats' ) );

		return;
	}

	if ( $stock < 1 ) {
		printf( '<span style="color:#b32d2e"><strong>%s</strong></span>', esc_html__( 'Sold out', 'treats' ) );

		return;
	}

	printf(
		'%1$d%2$s',
		(int) $stock,
		$stock <= 3 ? ' <span style="color:#b26200">' . esc_html__( '(low)', 'treats' ) . '</span>' : ''
	);
}
add_action( 'manage_treats_product_posts_custom_column', 'treats_product_column_content', 10, 2 );

/* -------------------------------------------------------------------------
 * Nudges
 * ---------------------------------------------------------------------- */

/**
 * Say plainly when the shop cannot take money, and why.
 *
 * @return void
 */
function treats_shop_admin_notice() {
	$screen = get_current_screen();

	if ( ! $screen || ! in_array( $screen->id, array( 'edit-treats_product', 'treats_product', 'edit-treats_shop_order' ), true ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( treats_shop_enabled() ) {
		if ( ! treats_square_is_live() ) {
			printf(
				'<div class="notice notice-warning"><p><strong>%1$s</strong> %2$s</p></div>',
				esc_html__( 'The shop is in test mode.', 'treats' ),
				esc_html__( 'Orders placed now take no real money. Switch to live in Appearance → Customize → Shop & Payments when you are ready.', 'treats' )
			);
		}

		return;
	}

	$reason = get_theme_mod( 'treats_shop_enabled', false )
		? __( 'Square is not set up yet — the application ID, location ID and access token all need filling in.', 'treats' )
		: __( 'The shop has not been opened yet.', 'treats' );

	printf(
		'<div class="notice notice-warning"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
		esc_html__( 'Products are visible but cannot be bought.', 'treats' ),
		esc_html( $reason ),
		esc_url( admin_url( 'customize.php?autofocus[section]=treats_shop' ) ),
		esc_html__( 'Open the shop settings', 'treats' )
	);
}
add_action( 'admin_notices', 'treats_shop_admin_notice' );

/**
 * Small styling for the status pill.
 *
 * @return void
 */
function treats_shop_admin_style() {
	$screen = get_current_screen();

	if ( ! $screen || 'edit-treats_shop_order' !== $screen->id ) {
		return;
	}

	wp_add_inline_style(
		'common',
		'.treats-status{display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;background:#e5e5e5}
		.treats-status--paid{background:#fcf0d4;color:#7a5a00}
		.treats-status--ready{background:#d8e9f7;color:#1f4f73}
		.treats-status--dispatched,.treats-status--completed{background:#d9efd9;color:#1e5b1e}
		.treats-status--refunded{background:#f5dada;color:#7c2b2b}'
	);
}
add_action( 'admin_enqueue_scripts', 'treats_shop_admin_style' );
