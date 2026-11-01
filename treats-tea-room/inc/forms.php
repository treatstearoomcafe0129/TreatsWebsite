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

	// Switching Click & Collect off hides the ordering controls, but the
	// endpoint would still accept a posted order and file it. Off should
	// mean off, or the café ends up with orders it never offered to take.
	if ( 'order' === $raw_action && ! treats_collect_enabled() ) {
		treats_form_respond( false, __( 'We are not taking collection orders through the website at the moment. Please ring us.', 'treats' ), $is_ajax, 403 );
	}

	// 2. Honeypot — a field only a bot will fill in.
	if ( ! empty( $_POST['treats_website'] ) ) {
		// Report success so bots do not learn they were caught.
		treats_form_respond( true, $config['success'], $is_ajax );
	}

	// 3. Time trap — humans take more than three seconds to fill a form in.
	// The timestamp is signed when the form is drawn, so a bot posting
	// straight at the endpoint cannot leave it out or make one up.
	$raw_ts   = isset( $_POST['treats_ts'] ) ? sanitize_text_field( wp_unslash( $_POST['treats_ts'] ) ) : '';
	$rendered = treats_verify_form_ts( $raw_ts );

	if ( $rendered && ( time() - $rendered ) < 3 ) {
		treats_form_respond( false, __( 'That was a little too quick — please try again.', 'treats' ), $is_ajax, 429 );
	}

	// 3b. Bot check. A real visitor's browser fills in `treats_human` the
	// moment they type, tap or click inside the form. Scripts that post the
	// form without a person behind it never do. Suspect submissions are kept
	// (marked as suspected spam) so nothing genuine is lost, but nobody is
	// emailed and no follow-up hooks run.
	$suspect = treats_form_suspect_reason( $raw_ts, $rendered );

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

		// Sanitising an invalid value can empty it — "not-an-email" comes back
		// as "". Telling somebody who typed something that the field is
		// required sends them looking for a field they already filled in.
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
	$post_id = treats_store_submission( $raw_action, $config, $data, $suspect );

	if ( is_wp_error( $post_id ) ) {
		treats_form_respond( false, __( 'We could not save your request. Please call us.', 'treats' ), $is_ajax, 500 );
	}

	if ( '' !== $suspect ) {
		// Report success so bots do not learn they were caught.
		treats_form_respond( true, $config['success'], $is_ajax );
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
 * Did the visitor actually put something in this field?
 *
 * Asked of the raw submission, before sanitising, so an invalid value can be
 * told apart from a blank one.
 *
 * @param mixed $raw Raw submitted value.
 * @return bool
 */
function treats_field_was_filled( $raw ) {
	if ( is_array( $raw ) ) {
		$raw = implode( '', $raw );
	}

	return '' !== trim( (string) $raw );
}

/**
 * Sanitize a submitted value by its declared type.
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
 * @param array  $data    Sanitized data.
 * @param string $suspect Why the submission looks like spam, or '' if it does not.
 * @return int|WP_Error
 */
function treats_store_submission( $action, $config, $data, $suspect = '' ) {
	$reference = strtoupper( substr( $action, 0, 3 ) ) . '-' . gmdate( 'ymd' ) . '-' . wp_generate_password( 4, false, false );

	$title_parts = array_filter(
		array(
			'' !== $suspect ? __( 'Suspected spam', 'treats' ) : '',
			$reference,
			$data['treats_name'] ?? ( $data['treats_email'] ?? '' ),
		)
	);

	$post_id = wp_insert_post(
		array(
			'post_type'   => $config['post_type'],
			// Suspected spam is filed as Pending so it sits apart from real
			// submissions in the admin list and can be checked or binned.
			'post_status' => '' !== $suspect ? 'pending' : 'publish',
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

	if ( '' !== $suspect ) {
		update_post_meta( $post_id, '_treats_spam', $suspect );
	}

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
	$to = strtolower( trim( (string) get_theme_mod( 'treats_notification_email', '' ) ) );

	if ( ! is_email( $to ) ) {
		$to = treats_get_email();
	}

	if ( ! is_email( $to ) ) {
		$to = strtolower( (string) get_option( 'admin_email' ) );
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

	// Site-wide cap on acknowledgements. A spam wave arriving from many
	// connections would otherwise turn the café's mail into a way of sending
	// strangers unwanted email, which damages the sending reputation.
	if ( ! treats_rate_limit_global( 'form_ack', 15, HOUR_IN_SECONDS ) ) {
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
	$ts = treats_sign_form_ts( time() );
	?>
	<input type="hidden" name="action" value="treats_<?php echo esc_attr( $action ); ?>">
	<input type="hidden" name="treats_action" value="<?php echo esc_attr( $action ); ?>">
	<input type="hidden" name="treats_nonce" value="<?php echo esc_attr( wp_create_nonce( 'treats_public' ) ); ?>">
	<input type="hidden" name="treats_ts" value="<?php echo esc_attr( $ts ); ?>">
	<input type="hidden" name="treats_human" value="" data-treats-human="<?php echo esc_attr( treats_form_human_token( $ts ) ); ?>">
	<noscript>
		<p class="form-note">
			<?php
			printf(
				/* translators: %s: café phone number. */
				esc_html__( 'This form needs JavaScript switched on to reach us. If you cannot turn it on, please ring us on %s.', 'treats' ),
				esc_html( treats_get_phone() )
			);
			?>
		</p>
	</noscript>
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

/**
 * Sign a form timestamp so it cannot be forged or left out.
 *
 * @param int $time Unix time the form was drawn.
 * @return string `time.signature`
 */
function treats_sign_form_ts( $time ) {
	$time = (int) $time;

	return $time . '.' . substr( wp_hash( 'treats_ts|' . $time, 'nonce' ), 0, 16 );
}

/**
 * Check a signed form timestamp.
 *
 * @param string $raw Posted `treats_ts` value.
 * @return int The time the form was drawn, or 0 if the value is missing or forged.
 */
function treats_verify_form_ts( $raw ) {
	$parts = explode( '.', (string) $raw );

	if ( 2 !== count( $parts ) || ! ctype_digit( $parts[0] ) ) {
		return 0;
	}

	$time = (int) $parts[0];

	if ( ! hash_equals( treats_sign_form_ts( $time ), (string) $raw ) ) {
		return 0;
	}

	return $time;
}

/**
 * The value the browser copies into `treats_human` once a person interacts.
 *
 * @param string $signed_ts Signed timestamp the form was drawn with.
 * @return string
 */
function treats_form_human_token( $signed_ts ) {
	return substr( wp_hash( 'treats_human|' . $signed_ts, 'nonce' ), 0, 20 );
}

/**
 * Decide whether a submission looks automated.
 *
 * @param string $raw_ts   Posted `treats_ts` value.
 * @param int    $rendered Verified render time, or 0.
 * @return string Short reason, or '' if the submission looks genuine.
 */
function treats_form_suspect_reason( $raw_ts, $rendered ) {
	if ( ! $rendered ) {
		return 'bad-timestamp';
	}

	// Pages can be cached, so allow a generous age, but not forever.
	if ( ( time() - $rendered ) > 2 * DAY_IN_SECONDS ) {
		return 'stale-form';
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified upstream.
	$human = isset( $_POST['treats_human'] ) ? sanitize_text_field( wp_unslash( $_POST['treats_human'] ) ) : '';

	if ( ! hash_equals( treats_form_human_token( $raw_ts ), $human ) ) {
		return 'no-interaction';
	}

	return '';
}
