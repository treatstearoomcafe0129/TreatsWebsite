<?php
/**
 * The basket.
 *
 * The basket cookie holds product IDs and quantities and nothing else. Every
 * price, every postage figure and every total is recalculated from the
 * database on each request, so editing the cookie can change what you are
 * buying but never what it costs.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cookie the basket lives in.
 *
 * Named so page caches can be told to bypass a request that carries it.
 */
const TREATS_BASKET_COOKIE = 'treats_basket';

/**
 * Most of any one product a customer can put in a basket.
 *
 * Not a business rule so much as a guard against a stuck key or a script
 * ordering four thousand teapots.
 */
const TREATS_BASKET_MAX_QTY = 20;

/* -------------------------------------------------------------------------
 * Basket keys
 *
 * A line is identified by the product, plus the chosen option where there is
 * one: "84" or "84:2". Two amounts of the same gift voucher are two lines,
 * and adding the £20 one twice still adds up to one line of two.
 * ---------------------------------------------------------------------- */

/**
 * Build a basket key.
 *
 * @param int      $product_id Product ID.
 * @param int|null $option     Option position, or null.
 * @return string
 */
function treats_basket_key( $product_id, $option = null ) {
	return null === $option
		? (string) (int) $product_id
		: (int) $product_id . ':' . (int) $option;
}

/**
 * Split a basket key back into a product and an option.
 *
 * @param string $key Basket key.
 * @return array{product_id:int,option:int|null}
 */
function treats_parse_basket_key( $key ) {
	// Strict on purpose. Casting loosely, "12:abc" and "12:0:999" both came
	// out as option zero — no money was at risk, since the price still came
	// from that option, but a key we cannot read should be dropped rather
	// than quietly turned into a different one.
	if ( ! preg_match( '/^(\d+)(?::(\d+))?$/', (string) $key, $matches ) ) {
		return array(
			'product_id' => 0,
			'option'     => null,
		);
	}

	return array(
		'product_id' => (int) $matches[1],
		'option'     => isset( $matches[2] ) ? (int) $matches[2] : null,
	);
}

/* -------------------------------------------------------------------------
 * Reading and writing
 * ---------------------------------------------------------------------- */

/**
 * Current basket as a map of basket key to quantity.
 *
 * Lines pointing at products that have been deleted, unpublished or sold out
 * are dropped silently — the customer sees the basket they can actually buy.
 *
 * @return array<int,int>
 */
function treats_get_basket() {
	$store = &treats_basket_store();

	if ( null !== $store ) {
		return $store;
	}

	$basket = array();

	if ( empty( $_COOKIE[ TREATS_BASKET_COOKIE ] ) ) {
		$store = $basket;

		return $store;
	}

	$raw = json_decode( wp_unslash( $_COOKIE[ TREATS_BASKET_COOKIE ] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Decoded and cast below.

	if ( ! is_array( $raw ) ) {
		$store = $basket;

		return $store;
	}

	foreach ( $raw as $key => $quantity ) {
		$line       = treats_parse_basket_key( $key );
		$product_id = $line['product_id'];

		// Deliberately not absint(): that turns -5 into 5, so a mangled
		// cookie would quietly order five of something. A quantity that is
		// not a positive whole number is not a quantity.
		$quantity = is_scalar( $quantity ) ? (int) $quantity : 0;

		if ( ! $product_id || $quantity < 1 ) {
			continue;
		}

		if ( ! treats_product_purchasable( $product_id ) ) {
			continue;
		}

		// A line must name an option if the product has them, and must not
		// if it does not — otherwise the price it resolves to is a guess.
		$has_options = treats_product_has_options( $product_id );

		if ( $has_options && ( null === $line['option'] || ! treats_product_option( $product_id, $line['option'] ) ) ) {
			continue;
		}

		if ( ! $has_options && null !== $line['option'] ) {
			continue;
		}

		$basket[ treats_basket_key( $product_id, $line['option'] ) ] = treats_clamp_quantity( $product_id, $quantity );
	}

	$store = $basket;

	return $store;
}

/**
 * The single memoised copy of the basket for this request.
 *
 * Returned by reference so both the reader and the writer work on the same
 * array — two independent caches would disagree the moment anything changed.
 *
 * @return array<int,int>|null
 */
function &treats_basket_store() {
	static $basket = null;

	return $basket;
}

/**
 * Hold a quantity to something we can actually supply.
 *
 * @param int $product_id Product ID.
 * @param int $quantity   Requested quantity.
 * @return int
 */
function treats_clamp_quantity( $product_id, $quantity ) {
	$quantity = min( max( 1, (int) $quantity ), TREATS_BASKET_MAX_QTY );
	$stock    = treats_product_stock( $product_id );

	if ( null !== $stock ) {
		$quantity = min( $quantity, max( 0, $stock ) );
	}

	return $quantity;
}

/**
 * Persist the basket.
 *
 * @param array<string,int> $basket Basket key to quantity.
 * @return void
 */
function treats_set_basket( $basket ) {
	$basket = array_filter( array_map( 'absint', (array) $basket ) );

	// Keep the memoised copy in step so anything rendered later in this
	// request sees the basket the customer just changed.
	$store = &treats_basket_store();
	$store = $basket;

	$value = $basket ? wp_json_encode( $basket ) : '';

	if ( headers_sent() ) {
		return;
	}

	setcookie(
		TREATS_BASKET_COOKIE,
		$value,
		array(
			'expires'  => $basket ? time() + ( 14 * DAY_IN_SECONDS ) : time() - 3600,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);

	$_COOKIE[ TREATS_BASKET_COOKIE ] = $value;
}

/**
 * Empty the basket.
 *
 * @return void
 */
function treats_clear_basket() {
	treats_set_basket( array() );
}

/* -------------------------------------------------------------------------
 * Contents and totals
 * ---------------------------------------------------------------------- */

/**
 * The basket expanded into displayable lines.
 *
 * @return array<int,array<string,mixed>>
 */
function treats_basket_lines() {
	$lines = array();

	foreach ( treats_get_basket() as $key => $quantity ) {
		$parsed     = treats_parse_basket_key( $key );
		$product_id = $parsed['product_id'];
		$option     = $parsed['option'];
		$chosen     = null === $option ? null : treats_product_option( $product_id, $option );
		$price      = treats_product_price( $product_id, $option );

		$lines[] = array(
			'key'          => (string) $key,
			'product_id'   => $product_id,
			'option'       => $option,
			'option_label' => $chosen ? $chosen['label'] : '',
			'title'        => get_the_title( $product_id ),
			'url'          => get_permalink( $product_id ),
			'sku'          => (string) get_post_meta( $product_id, '_treats_sku', true ),
			'quantity'     => $quantity,
			'price'        => $price,
			'total'        => $price * $quantity,
			'postage'      => treats_product_postage( $product_id ),
			'collect_only' => treats_product_collect_only( $product_id ),
			'image_id'     => (int) get_post_thumbnail_id( $product_id ),
			'max'          => treats_basket_line_max( $product_id ),
		);
	}

	return $lines;
}

/**
 * The largest quantity this line could be raised to.
 *
 * @param int $product_id Product ID.
 * @return int
 */
function treats_basket_line_max( $product_id ) {
	$stock = treats_product_stock( $product_id );

	if ( null === $stock ) {
		return TREATS_BASKET_MAX_QTY;
	}

	return min( TREATS_BASKET_MAX_QTY, max( 1, $stock ) );
}

/**
 * Number of individual items in the basket.
 *
 * @return int
 */
function treats_basket_count() {
	return (int) array_sum( treats_get_basket() );
}

/**
 * Whether anything in the basket cannot be posted.
 *
 * @return bool
 */
function treats_basket_collect_only() {
	foreach ( array_keys( treats_get_basket() ) as $key ) {
		$parsed = treats_parse_basket_key( $key );

		if ( treats_product_collect_only( $parsed['product_id'] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Whether the customer may choose postage at all.
 *
 * @return bool
 */
function treats_basket_can_post() {
	$settings = treats_postage_settings();

	if ( ! $settings['offers_post'] ) {
		return false;
	}

	return ! treats_basket_collect_only();
}

/**
 * Postage due on the current basket, in pence.
 *
 * An order pays the dearest single item's postage, plus a configurable amount
 * for each item after the first. Charging every item's full postage would
 * punish someone buying two trivets that travel in the same box.
 *
 * @param string $fulfilment Either 'collect' or 'post'.
 * @return int
 */
function treats_basket_postage( $fulfilment ) {
	if ( 'post' !== $fulfilment ) {
		return 0;
	}

	$basket = treats_get_basket();

	if ( ! $basket ) {
		return 0;
	}

	$settings = treats_postage_settings();
	$subtotal = treats_basket_subtotal();

	if ( $settings['free_over'] > 0 && $subtotal >= $settings['free_over'] ) {
		return 0;
	}

	$dearest = 0;
	$units   = 0;

	foreach ( $basket as $key => $quantity ) {
		$parsed  = treats_parse_basket_key( $key );
		$dearest = max( $dearest, treats_product_postage( $parsed['product_id'] ) );
		$units  += $quantity;
	}

	return $dearest + ( $settings['extra_item'] * max( 0, $units - 1 ) );
}

/**
 * Basket subtotal before postage, in pence.
 *
 * @return int
 */
function treats_basket_subtotal() {
	$subtotal = 0;

	foreach ( treats_get_basket() as $key => $quantity ) {
		$parsed    = treats_parse_basket_key( $key );
		$subtotal += treats_product_price( $parsed['product_id'], $parsed['option'] ) * $quantity;
	}

	return $subtotal;
}

/**
 * Every figure the basket and checkout need.
 *
 * @param string $fulfilment Either 'collect' or 'post'.
 * @return array<string,int>
 */
function treats_basket_totals( $fulfilment = 'collect' ) {
	$subtotal = treats_basket_subtotal();
	$postage  = treats_basket_postage( $fulfilment );

	return array(
		'subtotal' => $subtotal,
		'postage'  => $postage,
		'total'    => $subtotal + $postage,
		'count'    => treats_basket_count(),
	);
}

/**
 * How much more the customer must spend to reach free postage.
 *
 * @return int Pence remaining, or 0 when there is no threshold or it is met.
 */
function treats_basket_free_postage_gap() {
	$settings = treats_postage_settings();

	if ( $settings['free_over'] <= 0 ) {
		return 0;
	}

	return max( 0, $settings['free_over'] - treats_basket_subtotal() );
}

/* -------------------------------------------------------------------------
 * Endpoints
 * ---------------------------------------------------------------------- */

/**
 * Register basket endpoints for both AJAX and plain POSTs.
 *
 * @return void
 */
function treats_register_basket_handlers() {
	foreach ( array( 'add', 'update', 'remove', 'empty' ) as $operation ) {
		add_action( 'wp_ajax_treats_basket_' . $operation, 'treats_handle_basket' );
		add_action( 'wp_ajax_nopriv_treats_basket_' . $operation, 'treats_handle_basket' );
	}
}
add_action( 'init', 'treats_register_basket_handlers' );

/**
 * Handle a basket change submitted without JavaScript.
 *
 * @return void
 */
function treats_handle_basket_fallback() {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in the handler.
	if ( empty( $_POST['treats_basket_action'] ) ) {
		return;
	}

	treats_handle_basket();
}
add_action( 'template_redirect', 'treats_handle_basket_fallback', 5 );

/**
 * Apply a basket change.
 *
 * @return void
 */
function treats_handle_basket() {
	$is_ajax = wp_doing_ajax();

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked immediately below.
	$operation = isset( $_POST['treats_basket_action'] ) ? sanitize_key( wp_unslash( $_POST['treats_basket_action'] ) ) : '';

	if ( $is_ajax && '' === $operation ) {
		$operation = str_replace( 'treats_basket_', '', sanitize_key( wp_unslash( $_POST['action'] ?? '' ) ) );
	}

	if ( ! in_array( $operation, array( 'add', 'update', 'remove', 'empty' ), true ) ) {
		treats_basket_respond( false, __( 'Unknown basket request.', 'treats' ), $is_ajax, 400 );
	}

	$nonce = isset( $_POST['treats_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['treats_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'treats_public' ) ) {
		treats_basket_respond( false, __( 'Your session expired. Please refresh the page and try again.', 'treats' ), $is_ajax, 403 );
	}

	if ( ! treats_shop_enabled() ) {
		treats_basket_respond( false, __( 'The shop is closed at the moment.', 'treats' ), $is_ajax, 403 );
	}

	$basket     = treats_get_basket();
	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	// Signed, so that "update" can read zero or below as "take it out" and
	// "add" can reject it, rather than both seeing a positive number.
	$quantity = isset( $_POST['quantity'] ) ? (int) $_POST['quantity'] : 1; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cast to int.

	// Which line is being changed. "add" names a product and an option;
	// "update" and "remove" name an existing line outright.
	$option = isset( $_POST['option'] ) && '' !== $_POST['option'] ? absint( $_POST['option'] ) : null;
	$line   = isset( $_POST['line'] ) ? sanitize_text_field( wp_unslash( $_POST['line'] ) ) : '';
	$key    = '' !== $line ? $line : treats_basket_key( $product_id, $option );

	switch ( $operation ) {
		case 'empty':
			$basket = array();
			$message = __( 'Basket emptied.', 'treats' );
			break;

		case 'remove':
			unset( $basket[ $key ] );
			$message = __( 'Removed from your basket.', 'treats' );
			break;

		case 'update':
			if ( ! isset( $basket[ $key ] ) ) {
				treats_basket_respond( false, __( 'That item is not in your basket.', 'treats' ), $is_ajax, 404 );
			}

			if ( $quantity < 1 ) {
				unset( $basket[ $key ] );
			} else {
				$basket[ $key ] = treats_clamp_quantity( treats_parse_basket_key( $key )['product_id'], $quantity );
			}

			$message = __( 'Basket updated.', 'treats' );
			break;

		default:
			if ( ! treats_product_purchasable( $product_id ) ) {
				treats_basket_respond( false, __( 'Sorry — that item is not available.', 'treats' ), $is_ajax, 409 );
			}

			// A product sold in several amounts cannot be added without
			// saying which, or the price would be whichever we guessed.
			if ( treats_product_has_options( $product_id ) && ! treats_product_option( $product_id, (int) $option ) ) {
				treats_basket_respond( false, __( 'Please choose an option first.', 'treats' ), $is_ajax, 422 );
			}

			if ( ! treats_product_has_options( $product_id ) ) {
				$option = null;
			}

			$key    = treats_basket_key( $product_id, $option );
			$wanted = ( $basket[ $key ] ?? 0 ) + max( 1, $quantity );
			$given  = treats_clamp_quantity( $product_id, $wanted );

			$basket[ $key ] = $given;

			$message = $given < $wanted
				/* translators: %d: quantity available. */
				? sprintf( __( 'We only have %d left, so that is what is in your basket.', 'treats' ), $given )
				: __( 'Added to your basket.', 'treats' );
	}

	treats_set_basket( $basket );

	treats_basket_respond( true, $message, $is_ajax );
}

/**
 * Reply to a basket change.
 *
 * @param bool   $success Whether it worked.
 * @param string $message Message for the customer.
 * @param bool   $is_ajax Whether this was an AJAX request.
 * @param int    $status  HTTP status for AJAX failures.
 * @return void
 */
function treats_basket_respond( $success, $message, $is_ajax, $status = 200 ) {
	if ( $is_ajax ) {
		$fulfilment = treats_basket_can_post() ? 'post' : 'collect';
		$totals     = treats_basket_totals( $fulfilment );

		$payload = array(
			'message'  => $message,
			'count'    => $totals['count'],
			'subtotal' => treats_money( $totals['subtotal'] ),
			'markup'   => treats_basket_markup(),
		);

		if ( $success ) {
			wp_send_json_success( $payload );
		}

		wp_send_json_error( $payload, $status );
	}

	if ( $success ) {
		treats_flash( $message );
	} else {
		treats_flash( $message, 'error' );
	}

	$referer = wp_get_referer();

	wp_safe_redirect( $referer ? $referer : treats_shop_url() );
	exit;
}

/**
 * Capture the basket panel as a string for AJAX replies.
 *
 * @return string
 */
function treats_basket_markup() {
	ob_start();
	get_template_part( 'template-parts/shop/basket-panel' );

	return (string) ob_get_clean();
}

/**
 * Stash a one-shot message for the next page load.
 *
 * Used by the no-JavaScript path, where the only way to say "added to your
 * basket" is to carry it across the redirect.
 *
 * @param string $message Message.
 * @param string $type    Either 'success' or 'error'.
 * @return void
 */
function treats_flash( $message, $type = 'success' ) {
	if ( headers_sent() ) {
		return;
	}

	setcookie(
		'treats_flash',
		wp_json_encode(
			array(
				'message' => $message,
				'type'    => $type,
			)
		),
		array(
			'expires'  => time() + MINUTE_IN_SECONDS,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
}

/**
 * Read and clear the one-shot message.
 *
 * @return array{message:string,type:string}|null
 */
function treats_take_flash() {
	if ( empty( $_COOKIE['treats_flash'] ) ) {
		return null;
	}

	$flash = json_decode( wp_unslash( $_COOKIE['treats_flash'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.

	unset( $_COOKIE['treats_flash'] );

	if ( ! headers_sent() ) {
		setcookie(
			'treats_flash',
			'',
			array(
				'expires'  => time() - 3600,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
	}

	if ( ! is_array( $flash ) || empty( $flash['message'] ) ) {
		return null;
	}

	return array(
		'message' => sanitize_text_field( $flash['message'] ),
		'type'    => 'error' === ( $flash['type'] ?? '' ) ? 'error' : 'success',
	);
}
