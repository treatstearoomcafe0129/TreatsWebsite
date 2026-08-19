<?php
/**
 * Square payments.
 *
 * Two calls make a sale: an order is created so the café sees itemised lines
 * in its Square dashboard next to till takings, then a payment is taken
 * against that order using the single-use card token the browser produced.
 *
 * The card itself never touches this server. Square's Web Payments SDK renders
 * the card fields in an iframe it owns and hands back an opaque token, which
 * is what keeps the site out of scope for the harder parts of PCI compliance.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Square API version this integration is written against.
 *
 * Square dates its releases and holds old behaviour stable, so pinning a
 * version means an upstream change cannot alter what happens mid-sale.
 */
const TREATS_SQUARE_API_VERSION = '2024-10-17';

/* -------------------------------------------------------------------------
 * Credentials
 * ---------------------------------------------------------------------- */

/**
 * Which Square environment to talk to.
 *
 * @return string Either 'sandbox' or 'production'.
 */
function treats_square_env() {
	if ( defined( 'TREATS_SQUARE_ENV' ) ) {
		return 'production' === TREATS_SQUARE_ENV ? 'production' : 'sandbox';
	}

	return 'production' === get_theme_mod( 'treats_square_env', 'sandbox' ) ? 'production' : 'sandbox';
}

/**
 * Whether the shop is pointed at real money.
 *
 * @return bool
 */
function treats_square_is_live() {
	return 'production' === treats_square_env();
}

/**
 * Square access token.
 *
 * A constant in wp-config.php wins, because a secret in the database is
 * readable by every administrator and travels in every backup.
 *
 * @return string
 */
function treats_square_token() {
	if ( defined( 'TREATS_SQUARE_TOKEN' ) && TREATS_SQUARE_TOKEN ) {
		return (string) TREATS_SQUARE_TOKEN;
	}

	return trim( (string) get_theme_mod( 'treats_square_token', '' ) );
}

/**
 * Square application ID, used by the browser SDK.
 *
 * @return string
 */
function treats_square_app_id() {
	if ( defined( 'TREATS_SQUARE_APP_ID' ) && TREATS_SQUARE_APP_ID ) {
		return (string) TREATS_SQUARE_APP_ID;
	}

	return trim( (string) get_theme_mod( 'treats_square_app_id', '' ) );
}

/**
 * Square location ID.
 *
 * @return string
 */
function treats_square_location_id() {
	if ( defined( 'TREATS_SQUARE_LOCATION_ID' ) && TREATS_SQUARE_LOCATION_ID ) {
		return (string) TREATS_SQUARE_LOCATION_ID;
	}

	return trim( (string) get_theme_mod( 'treats_square_location_id', '' ) );
}

/**
 * Whether every credential needed to take a payment is present.
 *
 * @return bool
 */
function treats_square_configured() {
	return '' !== treats_square_token()
		&& '' !== treats_square_app_id()
		&& '' !== treats_square_location_id();
}

/**
 * URL of the Web Payments SDK for the current environment.
 *
 * @return string
 */
function treats_square_sdk_url() {
	return treats_square_is_live()
		? 'https://web.squarecdn.com/v1/square.js'
		: 'https://sandbox.web.squarecdn.com/v1/square.js';
}

/* -------------------------------------------------------------------------
 * Transport
 * ---------------------------------------------------------------------- */

/**
 * Call the Square API.
 *
 * @param string $path   Path after /v2/, e.g. 'orders'.
 * @param array  $body   Request body.
 * @param string $method HTTP method.
 * @return array|WP_Error Decoded response, or an error.
 */
function treats_square_request( $path, $body = array(), $method = 'POST' ) {
	if ( ! treats_square_configured() ) {
		return new WP_Error( 'treats_square_unconfigured', __( 'Card payments are not set up yet.', 'treats' ) );
	}

	$base = treats_square_is_live()
		? 'https://connect.squareup.com/v2/'
		: 'https://connect.squareupsandbox.com/v2/';

	$args = array(
		'method'  => $method,
		'timeout' => 25,
		'headers' => array(
			'Square-Version' => TREATS_SQUARE_API_VERSION,
			'Authorization'  => 'Bearer ' . treats_square_token(),
			'Content-Type'   => 'application/json',
			'Accept'         => 'application/json',
		),
	);

	if ( 'GET' !== $method ) {
		$args['body'] = wp_json_encode( $body );
	}

	$response = wp_remote_request( $base . ltrim( $path, '/' ), $args );

	if ( is_wp_error( $response ) ) {
		treats_square_log( $path, $response->get_error_message() );

		return new WP_Error(
			'treats_square_unreachable',
			__( 'We could not reach the card processor. Please try again in a moment.', 'treats' )
		);
	}

	$status = (int) wp_remote_retrieve_response_code( $response );
	$parsed = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $parsed ) ) {
		treats_square_log( $path, 'Unreadable response, HTTP ' . $status );

		return new WP_Error( 'treats_square_bad_response', __( 'The card processor sent something we could not read.', 'treats' ) );
	}

	if ( $status >= 200 && $status < 300 ) {
		return $parsed;
	}

	$detail = isset( $parsed['errors'][0] ) ? $parsed['errors'][0] : array();

	treats_square_log(
		$path,
		sprintf(
			'HTTP %1$d %2$s %3$s',
			$status,
			$detail['code'] ?? 'UNKNOWN',
			$detail['detail'] ?? ''
		)
	);

	return new WP_Error(
		'treats_square_' . strtolower( $detail['code'] ?? 'error' ),
		treats_square_message( $detail['code'] ?? '' ),
		array( 'status' => $status )
	);
}

/**
 * Turn a Square error code into something a customer can act on.
 *
 * Anything unrecognised gets the generic line rather than Square's own
 * wording, which tends to be aimed at developers.
 *
 * @param string $code Square error code.
 * @return string
 */
function treats_square_message( $code ) {
	$messages = array(
		'CARD_DECLINED'                 => __( 'Your card was declined. Please try another card.', 'treats' ),
		'CARD_DECLINED_CALL_ISSUER'     => __( 'Your bank declined the payment. Give them a ring, or try another card.', 'treats' ),
		'CARD_DECLINED_VERIFICATION_REQUIRED' => __( 'Your bank needs to verify this payment. Please try again.', 'treats' ),
		'CVV_FAILURE'                   => __( 'The security code did not match. Please check the three digits on the back of the card.', 'treats' ),
		'ADDRESS_VERIFICATION_FAILURE'  => __( 'The billing postcode did not match your card. Please check it and try again.', 'treats' ),
		'EXPIRATION_FAILURE'            => __( 'That expiry date was not accepted. Please check it.', 'treats' ),
		'INVALID_EXPIRATION'            => __( 'That expiry date was not accepted. Please check it.', 'treats' ),
		'INSUFFICIENT_FUNDS'            => __( 'There were not enough funds available on that card.', 'treats' ),
		'GENERIC_DECLINE'               => __( 'Your card was declined. Please try another card.', 'treats' ),
		'PAYMENT_LIMIT_EXCEEDED'        => __( 'That payment is above the limit on the card.', 'treats' ),
		'TEMPORARY_ERROR'               => __( 'The card processor is busy. Please try again in a moment.', 'treats' ),
		'UNAUTHORIZED'                  => __( 'Card payments are not set up correctly. Please ring us to order.', 'treats' ),
	);

	if ( isset( $messages[ $code ] ) ) {
		return $messages[ $code ];
	}

	return __( 'We could not take that payment. Please check your card details and try again.', 'treats' );
}

/**
 * Record a Square failure for the site owner.
 *
 * Kept to the last twenty so the option cannot grow without bound, and
 * deliberately free of customer or card detail.
 *
 * @param string $path    Endpoint called.
 * @param string $message What went wrong.
 * @return void
 */
function treats_square_log( $path, $message ) {
	$log = get_option( 'treats_square_log', array() );

	if ( ! is_array( $log ) ) {
		$log = array();
	}

	array_unshift(
		$log,
		array(
			'time'    => current_time( 'mysql' ),
			'path'    => $path,
			'message' => $message,
		)
	);

	update_option( 'treats_square_log', array_slice( $log, 0, 20 ), false );
}

/* -------------------------------------------------------------------------
 * Taking a payment
 * ---------------------------------------------------------------------- */

/**
 * Build the Square order body for a basket.
 *
 * @param array  $lines      Basket lines from treats_basket_lines().
 * @param int    $postage    Postage in pence.
 * @param string $reference  Our own order reference.
 * @param string $fulfilment Either 'collect' or 'post'.
 * @return array
 */
function treats_square_order_body( $lines, $postage, $reference, $fulfilment ) {
	$items = array();

	foreach ( $lines as $line ) {
		$item = array(
			'name'             => $line['title'],
			'quantity'         => (string) $line['quantity'],
			'base_price_money' => array(
				'amount'   => $line['price'],
				'currency' => 'GBP',
			),
		);

		// Only send the product code when there is one — Square rejects a
		// null where it expects a string.
		if ( '' !== $line['sku'] ) {
			$item['note'] = $line['sku'];
		}

		$items[] = $item;
	}

	if ( $postage > 0 ) {
		$items[] = array(
			'name'             => __( 'Postage', 'treats' ),
			'quantity'         => '1',
			'base_price_money' => array(
				'amount'   => $postage,
				'currency' => 'GBP',
			),
		);
	}

	return array(
		'idempotency_key' => 'order-' . $reference,
		'order'           => array(
			'location_id' => treats_square_location_id(),
			'reference_id' => $reference,
			'line_items'  => $items,
			'source'      => array( 'name' => __( 'Website shop', 'treats' ) ),
			'metadata'    => array(
				'fulfilment' => $fulfilment,
				'reference'  => $reference,
			),
		),
	);
}

/**
 * Create an order and take payment for it.
 *
 * @param array $args {
 *     @type array  $lines      Basket lines.
 *     @type int    $postage    Postage in pence.
 *     @type int    $total      Total in pence, calculated server side.
 *     @type string $reference  Our order reference.
 *     @type string $fulfilment 'collect' or 'post'.
 *     @type string $source_id  Card token from the browser.
 *     @type string $email      Customer email.
 *     @type string $note       Note shown in the Square dashboard.
 * }
 * @return array|WP_Error Payment data on success.
 */
function treats_square_take_payment( $args ) {
	$order = treats_square_request(
		'orders',
		treats_square_order_body( $args['lines'], $args['postage'], $args['reference'], $args['fulfilment'] )
	);

	if ( is_wp_error( $order ) ) {
		return $order;
	}

	$order_id = $order['order']['id'] ?? '';
	$charged  = (int) ( $order['order']['total_money']['amount'] ?? 0 );

	// Square has just totalled the same basket independently. If its figure
	// and ours disagree, something is wrong with one of them and the safe
	// move is to take no money at all.
	if ( ! $order_id || $charged !== (int) $args['total'] ) {
		treats_square_log(
			'orders',
			sprintf( 'Total mismatch: expected %1$d, Square said %2$d', (int) $args['total'], $charged )
		);

		return new WP_Error(
			'treats_square_total_mismatch',
			__( 'We could not confirm the total for this order. Nothing has been charged — please try again.', 'treats' )
		);
	}

	$payment = treats_square_request(
		'payments',
		array(
			'idempotency_key'     => 'pay-' . $args['reference'],
			'source_id'           => $args['source_id'],
			'order_id'            => $order_id,
			'location_id'         => treats_square_location_id(),
			'reference_id'        => $args['reference'],
			'note'                => mb_substr( (string) $args['note'], 0, 500 ),
			'buyer_email_address' => $args['email'],
			'amount_money'        => array(
				'amount'   => (int) $args['total'],
				'currency' => 'GBP',
			),
		)
	);

	if ( is_wp_error( $payment ) ) {
		return $payment;
	}

	return array(
		'payment_id' => $payment['payment']['id'] ?? '',
		'order_id'   => $order_id,
		'status'     => $payment['payment']['status'] ?? '',
		'receipt'    => $payment['payment']['receipt_url'] ?? '',
		'card_brand' => $payment['payment']['card_details']['card']['card_brand'] ?? '',
		'last_four'  => $payment['payment']['card_details']['card']['last_4'] ?? '',
	);
}
