<?php
/**
 * Front-end form handling.
 *
 * Every submission goes through the same pipeline: nonce → honeypot →
 * time trap → rate limit → validation → store → notify. Handlers respond to
 * both AJAX and standard POSTs so the forms still work without JavaScript.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the AJAX endpoints.
 *
 * @return void
 */
function treats_register_form_handlers() {
	$actions = array( 'booking', 'enquiry', 'event', 'order', 'voucher', 'subscribe' );

	foreach ( $actions as $action ) {
		add_action( 'wp_ajax_treats_' . $action, 'treats_handle_form' );
		add_action( 'wp_ajax_nopriv_treats_' . $action, 'treats_handle_form' );
	}
}
add_action( 'init', 'treats_register_form_handlers' );

/**
 * Handle non-AJAX submissions so the forms degrade gracefully.
 *
 * @return void
 */
function treats_handle_post_fallback() {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified inside the handler.
	if ( empty( $_POST['treats_action'] ) ) {
		return;
	}

	treats_handle_form();
}
add_action( 'template_redirect', 'treats_handle_post_fallback' );

/**
 * Shared submission handler.
 *
 * @return void
 */
function treats_handle_form() {
	$is_ajax = wp_doing_ajax();

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked below.
	$raw_action = isset( $_POST['treats_action'] ) ? sanitize_key( wp_unslash( $_POST['treats_action'] ) ) : '';

	if ( $is_ajax && '' === $raw_action ) {
		$raw_action = str_replace( 'treats_', '', sanitize_key( wp_unslash( $_POST['action'] ?? '' ) ) );
	}

	$config = treats_form_config( $raw_action );

	if ( ! $config ) {
		treats_form_respond( false, __( 'Unknown request.', 'treats' ), $is_ajax );
	}

	// 1. Nonce.
	$nonce = isset( $_POST['treats_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['treats_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'treats_public' ) ) {
		treats_form_respond( false, __( 'Your session expired. Please refresh the page and try again.', 'treats' ), $is_ajax, 403 );
	}

	// 2. Honeypot — a field only a bot will fill in.
	if ( ! empty( $_POST['treats_website'] ) ) {
		// Report success so bots do not learn they were caught.
		treats_form_respond( true, $config['success'], $is_ajax );
	}

	// 3. Time trap — humans take more than three seconds to fill a form in.
	$rendered = isset( $_POST['treats_ts'] ) ? (int) $_POST['treats_ts'] : 0;

	if ( $rendered && ( time() - $rendered ) < 3 ) {
		treats_form_respond( false, __( 'That was a little too quick — please try again.', 'treats' ), $is_ajax, 429 );
	}

	// 4. Rate limit.
	if ( ! treats_rate_limit( 'form_' . $raw_action, $config['limit'], HOUR_IN_SECONDS ) ) {
		treats_form_respond( false, __( 'Too many submissions from this connection. Please call us instead.', 'treats' ), $is_ajax, 429 );
	}

	// 5. Validate and collect.
	$data   = array();
	$errors = array();

	foreach ( $config['fields'] as $field => $rules ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per type.
		$raw   = isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '';
		$value = treats_sanitize_field( $raw, $rules['type'] );

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
		treats_form_respond( false, implode( ' ', $errors ), $is_ajax, 422, array( 'errors' => $errors ) );
	}

	// Choosing "Other" on the voucher form moves the real figure into the
	// free-text box, so fold it back into the amount before storing.
	if ( 'voucher' === $raw_action && 'other' === ( $data['treats_amount'] ?? '' ) ) {
		$custom = trim( (string) ( $data['treats_amount_custom'] ?? '' ) );

		if ( '' === $custom ) {
			treats_form_respond(
				false,
				__( 'Please tell us the voucher amount you would like.', 'treats' ),
				$is_ajax,
				422,
				array( 'errors' => array( 'treats_amount_custom' => __( 'Please enter an amount.', 'treats' ) ) )
			);
		}

		$data['treats_amount'] = is_numeric( $custom ) ? treats_format_price( $custom ) : $custom;
	}

	unset( $data['treats_amount_custom'] );

	// 6. Store.
	$post_id = treats_store_submission( $raw_action, $config, $data );

	if ( is_wp_error( $post_id ) ) {
		treats_form_respond( false, __( 'We could not save your request. Please call us.', 'treats' ), $is_ajax, 500 );
	}

	// 7. Notify.
	treats_notify_submission( $raw_action, $config, $data, $post_id );

	/**
	 * Fires after a front-end submission has been stored and emailed.
	 *
	 * @param string $action  Form action key.
	 * @param array  $data    Sanitized data.
	 * @param int    $post_id Stored record ID.
	 */
	do_action( 'treats_form_submitted', $raw_action, $data, $post_id );

	$extra = array();

	if ( 'voucher' === $raw_action ) {
		$checkout = trim( (string) get_theme_mod( 'treats_voucher_checkout_url', '' ) );

		if ( '' !== $checkout ) {
			$extra['redirect'] = $checkout;
		}
	}

	treats_form_respond( true, $config['success'], $is_ajax, 200, $extra );
}

/**
 * Configuration for each form.
 *
 * @param string $action Action key.
 * @return array<string,mixed>|null
 */
function treats_form_config( $action ) {
	$name  = array(
		'label'    => __( 'Name', 'treats' ),
		'type'     => 'text',
		'required' => true,
		'max'      => 80,
	);
	$email = array(
		'label'    => __( 'Email', 'treats' ),
		'type'     => 'email',
		'required' => true,
	);
	$phone = array(
		'label' => __( 'Phone', 'treats' ),
		'type'  => 'text',
		'max'   => 30,
	);

	$configs = array(
		'booking'   => array(
			'post_type' => 'treats_booking',
			'limit'     => 6,
			'subject'   => __( 'New table booking request', 'treats' ),
			'success'   => __( 'Thank you — your request is with us. We will confirm by email or phone shortly.', 'treats' ),
			'fields'    => array(
				'treats_name'      => $name,
				'treats_email'     => $email,
				'treats_phone'     => array_merge( $phone, array( 'required' => true ) ),
				'treats_date'      => array(
					'label'    => __( 'Date', 'treats' ),
					'type'     => 'date',
					'required' => true,
				),
				'treats_time'      => array(
					'label'    => __( 'Time', 'treats' ),
					'type'     => 'time',
					'required' => true,
				),
				'treats_party'     => array(
					'label'    => __( 'Party size', 'treats' ),
					'type'     => 'int',
					'required' => true,
					'min'      => 1,
					'max'      => 60,
				),
				'treats_occasion'  => array(
					'label' => __( 'Occasion', 'treats' ),
					'type'  => 'text',
					'max'   => 60,
				),
				'treats_notes'     => array(
					'label' => __( 'Notes', 'treats' ),
					'type'  => 'textarea',
					'max'   => 1000,
				),
				'treats_marketing' => array(
					'label' => __( 'Marketing opt-in', 'treats' ),
					'type'  => 'bool',
				),
			),
		),
		'enquiry'   => array(
			'post_type' => 'treats_enquiry',
			'limit'     => 6,
			'subject'   => __( 'New website enquiry', 'treats' ),
			'success'   => __( 'Thank you for getting in touch — we will reply as soon as we can.', 'treats' ),
			'fields'    => array(
				'treats_name'    => $name,
				'treats_email'   => $email,
				'treats_phone'   => $phone,
				'treats_subject' => array(
					'label' => __( 'Subject', 'treats' ),
					'type'  => 'text',
					'max'   => 120,
				),
				'treats_message' => array(
					'label'    => __( 'Message', 'treats' ),
					'type'     => 'textarea',
					'required' => true,
					'max'      => 2000,
				),
				'treats_marketing' => array(
					'label' => __( 'Marketing opt-in', 'treats' ),
					'type'  => 'bool',
				),
			),
		),
		'event'     => array(
			'post_type' => 'treats_event',
			'limit'     => 6,
			'subject'   => __( 'New evening venue hire enquiry', 'treats' ),
			'success'   => __( 'Thank you — your enquiry is with us. We will come back to you with availability and a quote.', 'treats' ),
			'fields'    => array(
				'treats_name'    => $name,
				'treats_email'   => $email,
				'treats_phone'   => array_merge( $phone, array( 'required' => true ) ),
				'treats_date'    => array(
					'label' => __( 'Preferred date', 'treats' ),
					'type'  => 'date',
				),
				'treats_party'   => array(
					'label' => __( 'Approximate guests', 'treats' ),
					'type'  => 'int',
					'min'   => 1,
					'max'   => 200,
				),
				'treats_package' => array(
					'label' => __( 'Food package', 'treats' ),
					'type'  => 'text',
					'max'   => 80,
				),
				'treats_occasion' => array(
					'label' => __( 'Occasion', 'treats' ),
					'type'  => 'text',
					'max'   => 120,
				),
				'treats_message' => array(
					'label'    => __( 'Anything else we should know', 'treats' ),
					'type'     => 'textarea',
					'required' => true,
					'max'      => 2000,
				),
			),
		),
		'order'     => array(
			'post_type' => 'treats_order',
			'limit'     => 8,
			'subject'   => __( 'New Click & Collect order', 'treats' ),
			'success'   => __( 'Order received. We will call to confirm your collection time.', 'treats' ),
			'fields'    => array(
				'treats_name'  => $name,
				'treats_email' => $email,
				'treats_phone' => array_merge( $phone, array( 'required' => true ) ),
				'treats_date'  => array(
					'label'    => __( 'Collection date', 'treats' ),
					'type'     => 'date',
					'required' => true,
				),
				'treats_time'  => array(
					'label'    => __( 'Collection time', 'treats' ),
					'type'     => 'time',
					'required' => true,
				),
				'treats_items' => array(
					'label'    => __( 'Items', 'treats' ),
					'type'     => 'textarea',
					'required' => true,
					'max'      => 3000,
				),
				'treats_total' => array(
					'label' => __( 'Total', 'treats' ),
					'type'  => 'text',
					'max'   => 20,
				),
				'treats_notes' => array(
					'label' => __( 'Notes', 'treats' ),
					'type'  => 'textarea',
					'max'   => 1000,
				),
			),
		),
		'voucher'   => array(
			'post_type' => 'treats_voucher',
			'limit'     => 8,
			'subject'   => __( 'New gift voucher order', 'treats' ),
			'success'   => __( 'Thank you — we will email you to arrange payment and delivery of the voucher.', 'treats' ),
			'fields'    => array(
				'treats_name'      => $name,
				'treats_email'     => $email,
				'treats_phone'     => $phone,
				'treats_amount'    => array(
					'label'    => __( 'Amount', 'treats' ),
					'type'     => 'text',
					'required' => true,
					'max'      => 20,
				),
				'treats_amount_custom' => array(
					'label' => __( 'Custom amount', 'treats' ),
					'type'  => 'text',
					'max'   => 20,
				),
				'treats_recipient' => array(
					'label' => __( 'Recipient name', 'treats' ),
					'type'  => 'text',
					'max'   => 80,
				),
				'treats_delivery'  => array(
					'label' => __( 'Delivery', 'treats' ),
					'type'  => 'text',
					'max'   => 40,
				),
				'treats_message'   => array(
					'label' => __( 'Gift message', 'treats' ),
					'type'  => 'textarea',
					'max'   => 500,
				),
			),
		),
		'subscribe' => array(
			'post_type' => 'treats_subscriber',
			'limit'     => 5,
			'subject'   => __( 'New newsletter subscriber', 'treats' ),
			'success'   => __( 'You are on the list — thank you.', 'treats' ),
			'fields'    => array(
				'treats_email' => $email,
				'treats_name'  => array(
					'label' => __( 'Name', 'treats' ),
					'type'  => 'text',
					'max'   => 80,
				),
			),
		),
	);

	return isset( $configs[ $action ] ) ? $configs[ $action ] : null;
}

/**
 * Sanitize a submitted value by type.
 *
 * @param mixed  $value Raw value.
 * @param string $type  Field type.
 * @return string
 */
function treats_sanitize_field( $value, $type ) {
	if ( is_array( $value ) ) {
		$value = implode( ', ', array_map( 'sanitize_text_field', $value ) );
	}

	$value = (string) $value;

	switch ( $type ) {
		case 'email':
			return sanitize_email( trim( $value ) );

		case 'textarea':
			return sanitize_textarea_field( $value );

		case 'int':
			return '' === trim( $value ) ? '' : (string) absint( $value );

		case 'bool':
			return empty( $value ) ? '' : '1';

		case 'date':
		case 'time':
		default:
			return sanitize_text_field( trim( $value ) );
	}
}

/**
 * Validate a sanitized value against its rules.
 *
 * @param string $value Sanitized value.
 * @param array  $rules Field rules.
 * @return bool
 */
function treats_validate_field( $value, $rules ) {
	if ( ! empty( $rules['max'] ) && mb_strlen( $value ) > (int) $rules['max'] && ! in_array( $rules['type'], array( 'int' ), true ) ) {
		return false;
	}

	switch ( $rules['type'] ) {
		case 'email':
			return (bool) is_email( $value );

		case 'date':
			return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) && checkdate(
				(int) substr( $value, 5, 2 ),
				(int) substr( $value, 8, 2 ),
				(int) substr( $value, 0, 4 )
			);

		case 'time':
			return (bool) preg_match( '/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $value );

		case 'int':
			$number = (int) $value;

			if ( isset( $rules['min'] ) && $number < (int) $rules['min'] ) {
				return false;
			}

			if ( isset( $rules['max'] ) && $number > (int) $rules['max'] ) {
				return false;
			}

			return true;
	}

	// Reject anything that looks like an injected header or a URL-stuffed spam
	// payload in short text fields.
	if ( 'text' === $rules['type'] && preg_match( '/(https?:\/\/|\bbcc:|\bcc:|content-type:)/i', $value ) ) {
		return false;
	}

	return true;
}

/**
 * Store a submission as a private record.
 *
 * @param string $action Action key.
 * @param array  $config Form config.
 * @param array  $data   Sanitized data.
 * @return int|WP_Error
 */
function treats_store_submission( $action, $config, $data ) {
	$reference = strtoupper( substr( $action, 0, 3 ) ) . '-' . gmdate( 'ymd' ) . '-' . wp_generate_password( 4, false, false );

	$title_parts = array_filter(
		array(
			$reference,
			$data['treats_name'] ?? ( $data['treats_email'] ?? '' ),
		)
	);

	$post_id = wp_insert_post(
		array(
			'post_type'   => $config['post_type'],
			'post_status' => 'publish',
			'post_title'  => implode( ' · ', $title_parts ),
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	// Field names arrive as `treats_name`; store them as protected meta
	// (`_treats_name`) so personal data stays out of the custom fields UI and
	// the REST API, and matches what the admin screens read back.
	foreach ( $data as $key => $value ) {
		if ( '' !== $value ) {
			update_post_meta( $post_id, '_' . $key, $value );
		}
	}

	update_post_meta( $post_id, '_treats_reference', $reference );
	update_post_meta( $post_id, '_treats_ip_hash', treats_client_ip_hash() );

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce already verified.
	$source = isset( $_POST['treats_source'] ) ? esc_url_raw( wp_unslash( $_POST['treats_source'] ) ) : '';

	if ( $source ) {
		update_post_meta( $post_id, '_treats_source', $source );
	}

	return (int) $post_id;
}

/**
 * Email the café, and acknowledge the customer.
 *
 * @param string $action  Action key.
 * @param array  $config  Form config.
 * @param array  $data    Sanitized data.
 * @param int    $post_id Record ID.
 * @return void
 */
function treats_notify_submission( $action, $config, $data, $post_id ) {
	$to = get_theme_mod( 'treats_notification_email', '' );

	if ( ! is_email( $to ) ) {
		$to = treats_get_email();
	}

	if ( ! is_email( $to ) ) {
		$to = get_option( 'admin_email' );
	}

	$site      = treats_get_business_name();
	$reference = (string) get_post_meta( $post_id, '_treats_reference', true );

	$lines = array(
		$config['subject'],
		str_repeat( '-', 40 ),
		sprintf( /* translators: %s: reference code. */ __( 'Reference: %s', 'treats' ), $reference ),
		'',
	);

	foreach ( $config['fields'] as $field => $rules ) {
		if ( empty( $data[ $field ] ) ) {
			continue;
		}

		$value = ( 'bool' === $rules['type'] ) ? __( 'Yes', 'treats' ) : $data[ $field ];

		$lines[] = $rules['label'] . ': ' . treats_email_safe( $value );
	}

	$lines[] = '';
	$lines[] = sprintf(
		/* translators: %s: admin URL. */
		__( 'Manage this submission: %s', 'treats' ),
		admin_url( 'post.php?post=' . $post_id . '&action=edit' )
	);

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

	if ( ! empty( $data['treats_email'] ) && is_email( $data['treats_email'] ) ) {
		$headers[] = 'Reply-To: ' . treats_email_safe( $data['treats_name'] ?? '' ) . ' <' . $data['treats_email'] . '>';
	}

	wp_mail(
		$to,
		sprintf( '[%1$s] %2$s — %3$s', $site, $config['subject'], $reference ),
		implode( "\n", $lines ),
		$headers
	);

	// Customer acknowledgement.
	if ( empty( $data['treats_email'] ) || ! is_email( $data['treats_email'] ) || 'subscribe' === $action ) {
		return;
	}

	$customer_lines = array(
		sprintf( /* translators: %s: customer first name. */ __( 'Hello %s,', 'treats' ), treats_email_safe( strtok( (string) ( $data['treats_name'] ?? '' ), ' ' ) ) ),
		'',
		$config['success'],
		'',
		sprintf( /* translators: %s: reference code. */ __( 'Your reference is %s.', 'treats' ), $reference ),
		'',
		$site,
		treats_get_address_line(),
		treats_get_phone(),
		home_url( '/' ),
	);

	wp_mail(
		$data['treats_email'],
		sprintf( /* translators: 1: business name, 2: reference. */ __( '%1$s — we have your request (%2$s)', 'treats' ), $site, $reference ),
		implode( "\n", $customer_lines ),
		array( 'Content-Type: text/plain; charset=UTF-8' )
	);
}

/**
 * Send the response, as JSON for AJAX or as a redirect otherwise.
 *
 * @param bool   $success Whether the submission succeeded.
 * @param string $message Message for the visitor.
 * @param bool   $is_ajax Whether this is an AJAX request.
 * @param int    $status  HTTP status for AJAX responses.
 * @param array  $extra   Extra response data.
 * @return void
 */
function treats_form_respond( $success, $message, $is_ajax, $status = 200, $extra = array() ) {
	if ( $is_ajax ) {
		$payload = array_merge( array( 'message' => $message ), $extra );

		if ( $success ) {
			wp_send_json_success( $payload, $status );
		}

		wp_send_json_error( $payload, $status );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified upstream; used only to build the redirect.
	$redirect = isset( $_POST['treats_source'] ) ? esc_url_raw( wp_unslash( $_POST['treats_source'] ) ) : home_url( '/' );

	if ( ! empty( $extra['redirect'] ) ) {
		wp_redirect( $extra['redirect'] ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Deliberate external payment link.
		exit;
	}

	$redirect = add_query_arg(
		array(
			'treats_status'  => $success ? 'success' : 'error',
			'treats_message' => rawurlencode( $message ),
		),
		$redirect
	);

	wp_safe_redirect( $redirect . '#form-response' );
	exit;
}

/**
 * Where a form posts when JavaScript is unavailable.
 *
 * Forms post back to the page they live on so the `template_redirect` handler
 * can process them and redirect with a readable message. The JavaScript layer
 * ignores this and posts to admin-ajax.php instead.
 *
 * @return string
 */
function treats_form_action() {
	return treats_get_canonical();
}

/**
 * Render the shared hidden fields every form needs.
 *
 * @param string $action Action key.
 * @return void
 */
function treats_form_fields( $action ) {
	?>
	<input type="hidden" name="action" value="treats_<?php echo esc_attr( $action ); ?>">
	<input type="hidden" name="treats_action" value="<?php echo esc_attr( $action ); ?>">
	<input type="hidden" name="treats_nonce" value="<?php echo esc_attr( wp_create_nonce( 'treats_public' ) ); ?>">
	<input type="hidden" name="treats_ts" value="<?php echo esc_attr( (string) time() ); ?>">
	<input type="hidden" name="treats_source" value="<?php echo esc_url( treats_get_canonical() ); ?>">
	<div class="form-trap" aria-hidden="true">
		<label for="treats-website-<?php echo esc_attr( $action ); ?>"><?php esc_html_e( 'Leave this field empty', 'treats' ); ?></label>
		<input type="text" id="treats-website-<?php echo esc_attr( $action ); ?>" name="treats_website" tabindex="-1" autocomplete="off">
	</div>
	<?php
}

/**
 * Show the result of a no-JavaScript submission.
 *
 * Renders at most once per request, so pages carrying several forms do not
 * repeat the same message.
 *
 * @return void
 */
function treats_form_notice() {
	static $rendered = false;

	if ( $rendered ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
	$status = isset( $_GET['treats_status'] ) ? sanitize_key( wp_unslash( $_GET['treats_status'] ) ) : '';

	if ( ! in_array( $status, array( 'success', 'error' ), true ) ) {
		return;
	}

	$rendered = true;

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
	$message = isset( $_GET['treats_message'] ) ? sanitize_text_field( wp_unslash( rawurldecode( $_GET['treats_message'] ) ) ) : '';

	printf(
		'<div class="form-response form-response--%1$s" id="form-response" role="status" tabindex="-1">%2$s</div>',
		esc_attr( $status ),
		esc_html( $message )
	);
}
