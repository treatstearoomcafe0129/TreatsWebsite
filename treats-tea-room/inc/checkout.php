<?php
/**
 * Checkout.
 *
 * The browser turns card details into a single-use token; everything from
 * there happens here. Totals are recalculated from the database rather than
 * trusted from the form, stock is checked twice, and the order record is only
 * written once Square confirms the money has actually moved.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the checkout endpoint.
 *
 * @return void
 */
function treats_register_checkout_handler() {
	add_action( 'wp_ajax_treats_checkout', 'treats_handle_checkout' );
	add_action( 'wp_ajax_nopriv_treats_checkout', 'treats_handle_checkout' );
}
add_action( 'init', 'treats_register_checkout_handler' );

/**
 * Fields the checkout collects, and how each is validated.
 *
 * @return array<string,array<string,mixed>>
 */
function treats_checkout_fields() {
	return array(
		'treats_name'         => array(
			'label'    => __( 'Name', 'treats' ),
			'type'     => 'text',
			'required' => true,
			'max'      => 80,
		),
		'treats_email'        => array(
			'label'    => __( 'Email', 'treats' ),
			'type'     => 'email',
			'required' => true,
			'max'      => 120,
		),
		'treats_phone'        => array(
			'label'    => __( 'Telephone', 'treats' ),
			'type'     => 'tel',
			'required' => true,
			'max'      => 30,
		),
		'treats_fulfilment'   => array(
			'label'    => __( 'Collection or postage', 'treats' ),
			'type'     => 'text',
			'required' => true,
			'max'      => 10,
		),
		'treats_address1'     => array(
			'label' => __( 'Address', 'treats' ),
			'type'  => 'text',
			'max'   => 120,
		),
		'treats_address2'     => array(
			'label' => __( 'Address line 2', 'treats' ),
			'type'  => 'text',
			'max'   => 120,
		),
		'treats_city'         => array(
			'label' => __( 'Town or city', 'treats' ),
			'type'  => 'text',
			'max'   => 60,
		),
		'treats_postcode'     => array(
			'label' => __( 'Postcode', 'treats' ),
			'type'  => 'text',
			'max'   => 12,
		),
		'treats_collect_date' => array(
			'label' => __( 'Collection date', 'treats' ),
			'type'  => 'date',
		),
		'treats_notes'        => array(
			'label' => __( 'Notes', 'treats' ),
			'type'  => 'textarea',
			'max'   => 1000,
		),
	);
}

/**
 * Take an order.
 *
 * @return void
 */
function treats_handle_checkout() {
	$fail = static function ( $message, $status = 422 ) {
		wp_send_json_error( array( 'message' => $message ), $status );
	};

	// 1. Same front door as every other form: nonce, honeypot, time trap,
	// rate limit. A checkout is a more attractive target, not a less one.
	$nonce = isset( $_POST['treats_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['treats_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'treats_public' ) ) {
		$fail( __( 'Your session expired. Please refresh the page and try again.', 'treats' ), 403 );
	}

	if ( ! empty( $_POST['treats_website'] ) ) {
		$fail( __( 'We could not process that order.', 'treats' ), 400 );
	}

	$rendered = isset( $_POST['treats_ts'] ) ? (int) $_POST['treats_ts'] : 0;

	if ( $rendered && ( time() - $rendered ) < 3 ) {
		$fail( __( 'That was a little too quick — please try again.', 'treats' ), 429 );
	}

	if ( ! treats_rate_limit( 'checkout', 8, HOUR_IN_SECONDS ) ) {
		$fail( __( 'Too many attempts from this connection. Please ring us to order.', 'treats' ), 429 );
	}

	if ( ! treats_shop_enabled() ) {
		$fail( __( 'The shop is closed at the moment.', 'treats' ), 403 );
	}

	// 2. Validate the customer's details.
	$data   = array();
	$errors = array();

	foreach ( treats_checkout_fields() as $field => $rules ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per type.
		$raw   = isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '';
		$value = treats_sanitize_field( $raw, $rules['type'] );

		if ( '' === $value && treats_field_was_filled( $raw ) ) {
			$errors[ $field ] = $rules['label'] . ': ' . __( 'please check this value.', 'treats' );
			continue;
		}

		if ( ! empty( $rules['required'] ) && '' === $value ) {
			$errors[ $field ] = $rules['label'] . ': ' . __( 'this field is required.', 'treats' );
			continue;
		}

		if ( '' !== $value && ! treats_validate_field( $value, $rules ) ) {
			$errors[ $field ] = $rules['label'] . ': ' . __( 'please check this value.', 'treats' );
			continue;
		}

		$data[ $field ] = $value;
	}

	if ( $errors ) {
		wp_send_json_error(
			array(
				'message' => implode( ' ', $errors ),
				'errors'  => $errors,
			),
			422
		);
	}

	$fulfilment = 'post' === ( $data['treats_fulfilment'] ?? '' ) ? 'post' : 'collect';

	if ( 'post' === $fulfilment && ! treats_basket_can_post() ) {
		$fail( __( 'Something in your basket can only be collected. Please choose collection.', 'treats' ) );
	}

	if ( 'post' === $fulfilment ) {
		foreach ( array( 'treats_address1', 'treats_city', 'treats_postcode' ) as $required ) {
			if ( '' === ( $data[ $required ] ?? '' ) ) {
				$fail( __( 'Please give us the full address to post to.', 'treats' ) );
			}
		}
	}

	// 3. Rebuild the basket from the database. Nothing about price or
	// availability is taken from the request.
	$lines = treats_basket_lines();

	if ( ! $lines ) {
		$fail( __( 'Your basket is empty.', 'treats' ), 409 );
	}

	foreach ( $lines as $line ) {
		if ( ! treats_product_in_stock( $line['product_id'], $line['quantity'] ) ) {
			$fail(
				sprintf(
					/* translators: %s: product name. */
					__( 'Sorry — %s sold out while you were ordering. Please adjust your basket.', 'treats' ),
					$line['title']
				),
				409
			);
		}
	}

	$totals = treats_basket_totals( $fulfilment );

	if ( $totals['total'] < 1 ) {
		$fail( __( 'That basket does not come to anything payable.', 'treats' ), 409 );
	}

	$source_id = isset( $_POST['treats_source_id'] ) ? sanitize_text_field( wp_unslash( $_POST['treats_source_id'] ) ) : '';

	if ( '' === $source_id ) {
		$fail( __( 'We did not receive your card details. Please try again.', 'treats' ) );
	}

	// 4. Reference first, so the idempotency keys sent to Square are stable
	// if the customer's connection drops and the request is retried.
	$reference = 'TTR-' . gmdate( 'ymd' ) . '-' . strtoupper( wp_generate_password( 4, false, false ) );

	$payment = treats_square_take_payment(
		array(
			'lines'      => $lines,
			'postage'    => $totals['postage'],
			'total'      => $totals['total'],
			'reference'  => $reference,
			'fulfilment' => $fulfilment,
			'source_id'  => $source_id,
			'email'      => $data['treats_email'],
			'note'       => treats_checkout_square_note( $data, $fulfilment ),
		)
	);

	if ( is_wp_error( $payment ) ) {
		wp_send_json_error( array( 'message' => $payment->get_error_message() ), 402 );
	}

	// 5. Money has moved. From here nothing may fail in a way that loses the
	// order, so the record is written before anything optional is attempted.
	$order_id = treats_create_shop_order( $reference, $data, $fulfilment, $lines, $totals, $payment );

	treats_reduce_stock( $lines );
	treats_clear_basket();

	treats_send_order_emails( $order_id );

	wp_send_json_success(
		array(
			'redirect' => treats_order_confirmation_url( $order_id ),
		)
	);
}

/**
 * A one-line summary for the Square dashboard.
 *
 * @param array  $data       Validated fields.
 * @param string $fulfilment 'collect' or 'post'.
 * @return string
 */
function treats_checkout_square_note( $data, $fulfilment ) {
	if ( 'post' === $fulfilment ) {
		return sprintf(
			/* translators: 1: customer name, 2: postcode. */
			__( 'Website order — post to %1$s, %2$s', 'treats' ),
			$data['treats_name'],
			$data['treats_postcode'] ?? ''
		);
	}

	return sprintf(
		/* translators: %s: customer name. */
		__( 'Website order — collection by %s', 'treats' ),
		$data['treats_name']
	);
}

/**
 * Write the order record.
 *
 * @param string $reference  Order reference.
 * @param array  $data       Validated fields.
 * @param string $fulfilment 'collect' or 'post'.
 * @param array  $lines      Basket lines.
 * @param array  $totals     Totals.
 * @param array  $payment    Square payment result.
 * @return int Order post ID.
 */
function treats_create_shop_order( $reference, $data, $fulfilment, $lines, $totals, $payment ) {
	$order_id = wp_insert_post(
		array(
			'post_type'   => 'treats_shop_order',
			'post_title'  => $reference,
			'post_status' => 'publish',
		),
		true
	);

	if ( is_wp_error( $order_id ) ) {
		// The customer has paid. Losing the record here would be far worse
		// than a noisy log, so make sure it is recoverable from Square.
		treats_square_log( 'order-record', 'Could not store paid order ' . $reference );

		return 0;
	}

	$meta = array(
		'_treats_reference'   => $reference,
		'_treats_status'      => 'paid',
		'_treats_fulfilment'  => $fulfilment,
		// Stored as an array, not JSON. update_post_meta() unslashes what it
		// is given, which strips the backslash out of every "—" that
		// wp_json_encode() produces — an em dash in a product name came back
		// as the literal text "u2014" on the order and in the email.
		'_treats_items'       => $lines,
		'_treats_subtotal'    => $totals['subtotal'],
		'_treats_postage'     => $totals['postage'],
		'_treats_total'       => $totals['total'],
		'_treats_payment_id'  => $payment['payment_id'],
		'_treats_square_order' => $payment['order_id'],
		'_treats_receipt'     => $payment['receipt'],
		'_treats_card'        => trim( $payment['card_brand'] . ' ' . $payment['last_four'] ),
		'_treats_key'         => wp_generate_password( 20, false, false ),
	);

	foreach ( treats_checkout_fields() as $field => $rules ) {
		$meta[ '_' . $field ] = $data[ $field ] ?? '';
	}

	foreach ( $meta as $key => $value ) {
		update_post_meta( $order_id, $key, $value );
	}

	return (int) $order_id;
}

/**
 * Take sold items off the shelf.
 *
 * @param array $lines Basket lines.
 * @return void
 */
function treats_reduce_stock( $lines ) {
	foreach ( $lines as $line ) {
		$stock = treats_product_stock( $line['product_id'] );

		if ( null === $stock ) {
			continue;
		}

		update_post_meta( $line['product_id'], '_treats_stock', max( 0, $stock - $line['quantity'] ) );
	}
}

/**
 * Signed URL of an order's confirmation page.
 *
 * The key stops one customer reading another's order by guessing references.
 *
 * @param int $order_id Order post ID.
 * @return string
 */
function treats_order_confirmation_url( $order_id ) {
	$page = treats_get_template_page_url( 'page-templates/template-order.php' );
	$page = $page ? $page : home_url( '/order/' );

	if ( ! $order_id ) {
		return $page;
	}

	return add_query_arg(
		array(
			'ref' => rawurlencode( (string) get_post_meta( $order_id, '_treats_reference', true ) ),
			'key' => rawurlencode( (string) get_post_meta( $order_id, '_treats_key', true ) ),
		),
		$page
	);
}

/**
 * Find an order from the reference and key in the URL.
 *
 * @return int Order post ID, or 0.
 */
function treats_order_from_request() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only lookup guarded by the key below.
	$reference = isset( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : '';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only lookup guarded by the key below.
	$key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';

	if ( '' === $reference || '' === $key ) {
		return 0;
	}

	$found = get_posts(
		array(
			'post_type'      => 'treats_shop_order',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'title'          => $reference,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	if ( ! $found ) {
		return 0;
	}

	$order_id = (int) $found[0];
	$stored   = (string) get_post_meta( $order_id, '_treats_key', true );

	// Constant-time so the key cannot be recovered a character at a time.
	if ( '' === $stored || ! hash_equals( $stored, $key ) ) {
		return 0;
	}

	return $order_id;
}

/**
 * Order lines, decoded.
 *
 * @param int $order_id Order post ID.
 * @return array<int,array<string,mixed>>
 */
function treats_order_items( $order_id ) {
	$items = get_post_meta( $order_id, '_treats_items', true );

	if ( is_array( $items ) ) {
		return $items;
	}

	// Orders written before the storage changed hold a JSON string.
	$decoded = json_decode( (string) $items, true );

	return is_array( $decoded ) ? $decoded : array();
}

/* -------------------------------------------------------------------------
 * Emails
 * ---------------------------------------------------------------------- */

/**
 * Tell the café and the customer about a new order.
 *
 * @param int $order_id Order post ID.
 * @return void
 */
function treats_send_order_emails( $order_id ) {
	if ( ! $order_id ) {
		return;
	}

	$reference  = (string) get_post_meta( $order_id, '_treats_reference', true );
	$fulfilment = (string) get_post_meta( $order_id, '_treats_fulfilment', true );
	$site       = treats_get_business_name();
	$items      = treats_order_items( $order_id );
	$headers    = array( 'Content-Type: text/plain; charset=UTF-8' );

	$summary = array();

	foreach ( $items as $item ) {
		$summary[] = sprintf(
			'%1$d × %2$s — %3$s',
			(int) $item['quantity'],
			$item['title'],
			treats_money( (int) $item['total'] )
		);
	}

	$subtotal = (int) get_post_meta( $order_id, '_treats_subtotal', true );
	$postage  = (int) get_post_meta( $order_id, '_treats_postage', true );
	$total    = (int) get_post_meta( $order_id, '_treats_total', true );

	$money = array(
		'',
		sprintf( __( 'Subtotal: %s', 'treats' ), treats_money( $subtotal ) ),
	);

	if ( $postage > 0 ) {
		$money[] = sprintf( __( 'Postage: %s', 'treats' ), treats_money( $postage ) );
	}

	$money[] = sprintf( __( 'Paid: %s', 'treats' ), treats_money( $total ) );

	$name  = (string) get_post_meta( $order_id, '_treats_name', true );
	$email = (string) get_post_meta( $order_id, '_treats_email', true );

	// The café's copy.
	$to = strtolower( trim( (string) get_theme_mod( 'treats_notification_email', '' ) ) );

	if ( ! is_email( $to ) ) {
		$to = treats_get_email();
	}

	$delivery = 'post' === $fulfilment
		? implode(
			"\n",
			array_filter(
				array(
					(string) get_post_meta( $order_id, '_treats_address1', true ),
					(string) get_post_meta( $order_id, '_treats_address2', true ),
					(string) get_post_meta( $order_id, '_treats_city', true ),
					(string) get_post_meta( $order_id, '_treats_postcode', true ),
				)
			)
		)
		: sprintf(
			/* translators: %s: collection date. */
			__( 'Collection on %s', 'treats' ),
			(string) get_post_meta( $order_id, '_treats_collect_date', true ) ?: __( 'a date to be arranged', 'treats' )
		);

	$owner_lines = array_merge(
		array(
			sprintf( __( 'New shop order: %s', 'treats' ), $reference ),
			str_repeat( '-', 40 ),
			'',
			$name,
			$email,
			(string) get_post_meta( $order_id, '_treats_phone', true ),
			'',
			'post' === $fulfilment ? __( 'POST TO:', 'treats' ) : __( 'COLLECTION:', 'treats' ),
			$delivery,
			'',
		),
		$summary,
		$money,
		array(
			'',
			sprintf( __( 'Card: %s', 'treats' ), (string) get_post_meta( $order_id, '_treats_card', true ) ),
			sprintf( __( 'Square receipt: %s', 'treats' ), (string) get_post_meta( $order_id, '_treats_receipt', true ) ),
			'',
			sprintf( __( 'Manage this order: %s', 'treats' ), admin_url( 'post.php?post=' . $order_id . '&action=edit' ) ),
		)
	);

	$notes = trim( (string) get_post_meta( $order_id, '_treats_notes', true ) );

	if ( '' !== $notes ) {
		$owner_lines[] = '';
		$owner_lines[] = __( 'Customer notes:', 'treats' );
		$owner_lines[] = $notes;
	}

	wp_mail(
		$to,
		sprintf( '[%1$s] %2$s — %3$s', $site, __( 'Shop order', 'treats' ), $reference ),
		implode( "\n", $owner_lines ),
		$headers
	);

	// The customer's copy.
	if ( ! is_email( $email ) ) {
		return;
	}

	$closing = 'post' === $fulfilment
		? (string) get_theme_mod( 'treats_shop_dispatch_note', __( 'Posted orders are sent within three working days by tracked delivery.', 'treats' ) )
		: sprintf(
			/* translators: %s: address of the café. */
			__( 'We will have this ready for you at %s. We will be in touch if we need to check anything.', 'treats' ),
			treats_get_address_line()
		);

	$customer_lines = array_merge(
		array(
			sprintf( __( 'Hello %s,', 'treats' ), strtok( $name, ' ' ) ),
			'',
			__( 'Thank you — your order is paid for and we have it.', 'treats' ),
			'',
			sprintf( __( 'Your reference is %s.', 'treats' ), $reference ),
			'',
		),
		$summary,
		$money,
		array(
			'',
			$closing,
			'',
			$site,
			treats_get_address_line(),
			treats_get_phone(),
			home_url( '/' ),
		)
	);

	wp_mail(
		$email,
		sprintf(
			/* translators: 1: business name, 2: order reference. */
			__( '%1$s — your order is confirmed (%2$s)', 'treats' ),
			$site,
			$reference
		),
		implode( "\n", $customer_lines ),
		$headers
	);
}
