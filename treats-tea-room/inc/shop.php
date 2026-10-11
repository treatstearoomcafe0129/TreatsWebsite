<?php
/**
 * The shop: products, pricing and stock.
 *
 * Products are editorial content managed in WordPress, not mirrored from the
 * Square catalogue — that catalogue is the café's till menu, and mixing a
 * teapot in among 400 breakfast items would make both harder to look after.
 * Square's job here is payment, not inventory.
 *
 * Money is handled in two forms and the distinction matters: meta stores a
 * human-friendly decimal string ("24.95") because that is what a shopkeeper
 * types, while every calculation and everything sent to Square uses integer
 * pence, because floating point cannot be trusted with money.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Registration
 * ---------------------------------------------------------------------- */

/**
 * Register the product post type and its category taxonomy.
 *
 * @return void
 */
function treats_register_shop_types() {
	register_post_type(
		'treats_product',
		array(
			'labels'       => array(
				'name'          => __( 'Products', 'treats' ),
				'singular_name' => __( 'Product', 'treats' ),
				'add_new_item'  => __( 'Add New Product', 'treats' ),
				'edit_item'     => __( 'Edit Product', 'treats' ),
				'search_items'  => __( 'Search Products', 'treats' ),
				'not_found'     => __( 'No products yet.', 'treats' ),
				'all_items'     => __( 'All Products', 'treats' ),
				'menu_name'     => __( 'Shop', 'treats' ),
			),
			'public'       => true,
			'has_archive'  => 'shop',
			'rewrite'      => array(
				'slug'       => 'shop',
				'with_front' => false,
			),
			'menu_icon'    => 'dashicons-store',
			'menu_position' => 29,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions' ),
			'show_in_rest' => true,
			'taxonomies'   => array( 'treats_product_cat' ),
		)
	);

	register_taxonomy(
		'treats_product_cat',
		array( 'treats_product' ),
		array(
			'labels'            => array(
				'name'          => __( 'Product Categories', 'treats' ),
				'singular_name' => __( 'Product Category', 'treats' ),
				'add_new_item'  => __( 'Add New Product Category', 'treats' ),
				'menu_name'     => __( 'Categories', 'treats' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'shop/category',
				'with_front' => false,
			),
		)
	);

	// Paid orders. Written by the checkout, never created by hand.
	register_post_type(
		'treats_shop_order',
		array(
			'labels'              => array(
				'name'          => __( 'Shop Orders', 'treats' ),
				'singular_name' => __( 'Shop Order', 'treats' ),
				'menu_name'     => __( 'Shop Orders', 'treats' ),
				'edit_item'     => __( 'View Shop Order', 'treats' ),
				'not_found'     => __( 'No orders yet.', 'treats' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_rest'        => false,
			'menu_icon'           => 'dashicons-cart',
			'menu_position'       => 30,
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
add_action( 'init', 'treats_register_shop_types' );

/* -------------------------------------------------------------------------
 * Money
 * ---------------------------------------------------------------------- */

/**
 * Convert a decimal price string to integer pence.
 *
 * Rounds rather than truncates so "19.99" cannot become 1998 through the
 * usual binary representation problem.
 *
 * @param mixed $amount Decimal amount, e.g. "24.95".
 * @return int Pence.
 */
function treats_to_pence( $amount ) {
	$clean = preg_replace( '/[^0-9.\-]/', '', (string) $amount );

	if ( '' === $clean || ! is_numeric( $clean ) ) {
		return 0;
	}

	return (int) round( (float) $clean * 100 );
}

/**
 * Format integer pence for display.
 *
 * @param int $pence Amount in pence.
 * @return string
 */
function treats_money( $pence ) {
	return treats_currency_symbol() . number_format_i18n( ( (int) $pence ) / 100, 2 );
}

/* -------------------------------------------------------------------------
 * Product accessors
 * ---------------------------------------------------------------------- */

/**
 * The choices a product is sold in, if any.
 *
 * A gift voucher is one thing on the shelf sold at six amounts, not six
 * things. Options carry their own price and nothing else — stock stays at
 * product level, which is right for vouchers and honest about the fact that
 * this is not a full inventory system.
 *
 * @param int $product_id Product ID.
 * @return array<int,array{label:string,price:int}>
 */
function treats_product_options( $product_id ) {
	$rows = get_post_meta( $product_id, '_treats_options', true );

	if ( ! is_array( $rows ) ) {
		return array();
	}

	$options = array();

	foreach ( $rows as $row ) {
		$label = trim( (string) ( $row['label'] ?? '' ) );
		$price = treats_to_pence( $row['price'] ?? '' );

		if ( '' === $label || $price < 1 ) {
			continue;
		}

		$options[] = array(
			'label' => $label,
			'price' => $price,
		);
	}

	return $options;
}

/**
 * One option, by position.
 *
 * @param int $product_id Product ID.
 * @param int $index      Zero-based position.
 * @return array{label:string,price:int}|null
 */
function treats_product_option( $product_id, $index ) {
	$options = treats_product_options( $product_id );

	return $options[ (int) $index ] ?? null;
}

/**
 * The price a customer actually pays, in pence.
 *
 * @param int      $product_id Product ID.
 * @param int|null $option     Option position, when the product has options.
 * @return int
 */
function treats_product_price( $product_id, $option = null ) {
	if ( null !== $option ) {
		$chosen = treats_product_option( $product_id, $option );

		if ( $chosen ) {
			return $chosen['price'];
		}
	}

	$sale = treats_to_pence( get_post_meta( $product_id, '_treats_sale_price', true ) );

	if ( $sale > 0 ) {
		return $sale;
	}

	$price = treats_to_pence( get_post_meta( $product_id, '_treats_price', true ) );

	// A product priced only through its options still needs a figure to show
	// on the shop listing: the cheapest way in.
	if ( $price < 1 ) {
		$options = treats_product_options( $product_id );

		if ( $options ) {
			return min( wp_list_pluck( $options, 'price' ) );
		}
	}

	return $price;
}

/**
 * The price before any reduction, in pence.
 *
 * @param int $product_id Product ID.
 * @return int
 */
function treats_product_regular_price( $product_id ) {
	return treats_to_pence( get_post_meta( $product_id, '_treats_price', true ) );
}

/**
 * Whether the product is reduced.
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function treats_product_on_sale( $product_id ) {
	$sale = treats_to_pence( get_post_meta( $product_id, '_treats_sale_price', true ) );

	return $sale > 0 && $sale < treats_product_regular_price( $product_id );
}

/**
 * Stock level, or null when the product is not stock tracked.
 *
 * An untracked product is always available — right for made-to-order things
 * and for a shelf the owner tops up without telling the website.
 *
 * @param int $product_id Product ID.
 * @return int|null
 */
function treats_product_stock( $product_id ) {
	if ( '1' !== (string) get_post_meta( $product_id, '_treats_track_stock', true ) ) {
		return null;
	}

	return (int) get_post_meta( $product_id, '_treats_stock', true );
}

/**
 * Whether the requested quantity can be sold.
 *
 * @param int $product_id Product ID.
 * @param int $quantity   Quantity wanted.
 * @return bool
 */
function treats_product_in_stock( $product_id, $quantity = 1 ) {
	$stock = treats_product_stock( $product_id );

	if ( null === $stock ) {
		return true;
	}

	return $stock >= max( 1, (int) $quantity );
}

/**
 * Whether a product can be added to a basket at all.
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function treats_product_purchasable( $product_id ) {
	if ( 'treats_product' !== get_post_type( $product_id ) ) {
		return false;
	}

	if ( 'publish' !== get_post_status( $product_id ) ) {
		return false;
	}

	return treats_product_price( $product_id ) > 0 && treats_product_in_stock( $product_id );
}

/**
 * Whether the customer has to pick something before they can buy.
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function treats_product_has_options( $product_id ) {
	return (bool) treats_product_options( $product_id );
}

/**
 * Postage cost for one product, in pence.
 *
 * @param int $product_id Product ID.
 * @return int
 */
function treats_product_postage( $product_id ) {
	return treats_to_pence( get_post_meta( $product_id, '_treats_postage', true ) );
}

/**
 * Whether the product must be collected rather than posted.
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function treats_product_collect_only( $product_id ) {
	return '1' === (string) get_post_meta( $product_id, '_treats_collect_only', true );
}

/**
 * Whether the product must be posted rather than collected.
 *
 * The mirror of collect-only. A product marked both ways is treated as
 * collection only, because that is the one the café can always honour.
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function treats_product_post_only( $product_id ) {
	if ( treats_product_collect_only( $product_id ) ) {
		return false;
	}

	return '1' === (string) get_post_meta( $product_id, '_treats_post_only', true );
}

/**
 * Whether the shop offers collection from the café at all.
 *
 * @return bool
 */
function treats_collection_offered() {
	return (bool) get_theme_mod( 'treats_shop_collect_enabled', true );
}

/**
 * Gallery image IDs for a product.
 *
 * @param int $product_id Product ID.
 * @return int[]
 */
function treats_product_gallery( $product_id ) {
	$raw = (string) get_post_meta( $product_id, '_treats_gallery', true );

	if ( '' === trim( $raw ) ) {
		return array();
	}

	$ids = array_map( 'absint', preg_split( '/[\s,]+/', $raw ) );

	return array_values( array_filter( $ids ) );
}

/**
 * The picture to show for a product.
 *
 * The featured image if there is one, otherwise the first of the extra
 * photographs, otherwise the first image in the description. A product with a
 * photograph on its own page but a blank square on the shop listing is the
 * commonest way for this to look broken, and it is always because the picture
 * went somewhere other than the featured image box.
 *
 * @param int $product_id Product ID.
 * @return int Attachment ID, or 0.
 */
function treats_product_image_id( $product_id ) {
	$featured = (int) get_post_thumbnail_id( $product_id );

	if ( $featured ) {
		return $featured;
	}

	$gallery = treats_product_gallery( $product_id );

	if ( $gallery ) {
		return (int) $gallery[0];
	}

	// Last resort: an image dropped straight into the description.
	$content = get_post_field( 'post_content', $product_id );

	if ( $content && preg_match( '/wp-image-(\d+)/', $content, $found ) ) {
		return (int) $found[1];
	}

	return 0;
}

/**
 * Short one-line description used on product cards.
 *
 * @param int $product_id Product ID.
 * @return string
 */
function treats_product_summary( $product_id ) {
	$summary = trim( (string) get_post_meta( $product_id, '_treats_summary', true ) );

	if ( '' !== $summary ) {
		return $summary;
	}

	return wp_strip_all_tags( get_the_excerpt( $product_id ) );
}

/* -------------------------------------------------------------------------
 * Shop settings
 * ---------------------------------------------------------------------- */

/**
 * Whether the shop is switched on and configured well enough to take money.
 *
 * @return bool
 */
function treats_shop_enabled() {
	if ( ! get_theme_mod( 'treats_shop_enabled', false ) ) {
		return false;
	}

	return treats_square_configured();
}

/**
 * URL of the shop.
 *
 * @return string
 */
function treats_shop_url() {
	return get_post_type_archive_link( 'treats_product' ) ?: home_url( '/shop/' );
}

/**
 * URL of the checkout page.
 *
 * @return string
 */
function treats_checkout_url() {
	$url = treats_get_template_page_url( 'page-templates/template-checkout.php' );

	return $url ?: home_url( '/checkout/' );
}

/**
 * Postage settings, all in pence except the two toggles.
 *
 * @return array<string,int|bool>
 */
function treats_postage_settings() {
	$threshold = trim( (string) get_theme_mod( 'treats_postage_free_over', '' ) );

	return array(
		'extra_item' => treats_to_pence( get_theme_mod( 'treats_postage_extra_item', '0' ) ),
		'free_over'  => '' === $threshold ? 0 : treats_to_pence( $threshold ),
		'offers_post' => (bool) get_theme_mod( 'treats_postage_enabled', true ),
	);
}

/**
 * How long before collection an order needs to be placed, in hours.
 *
 * @return int
 */
function treats_collection_lead_hours() {
	return max( 0, (int) get_theme_mod( 'treats_collect_lead_hours', 24 ) );
}
