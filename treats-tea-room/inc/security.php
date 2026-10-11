<?php
/**
 * Security hardening.
 *
 * Theme-level defence only — it complements, and does not replace, server
 * configuration and a maintained WordPress install.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Security headers.
 *
 * Sent from PHP so the theme is portable across hosts. If your server already
 * sets these at the web-server level, disable them with the filter below.
 *
 * @param array<string,string> $headers Existing headers.
 * @return array<string,string>
 */
function treats_security_headers( $headers ) {
	/**
	 * Filter whether the theme sends security headers.
	 *
	 * @param bool $send Default true.
	 */
	if ( ! apply_filters( 'treats_send_security_headers', true ) ) {
		return $headers;
	}

	$headers['X-Content-Type-Options'] = 'nosniff';
	$headers['X-Frame-Options']        = 'SAMEORIGIN';
	$headers['Referrer-Policy']        = 'strict-origin-when-cross-origin';
	$headers['Permissions-Policy']     = 'camera=(), microphone=(), geolocation=(self), interest-cohort=()';
	$headers['Cross-Origin-Resource-Policy'] = 'same-origin';

	if ( is_ssl() ) {
		$headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
	}

	return $headers;
}
add_filter( 'wp_headers', 'treats_security_headers' );

/**
 * Hide login errors that confirm whether a username exists.
 *
 * @return string
 */
function treats_login_error_message() {
	return __( 'Those details were not recognised.', 'treats' );
}
add_filter( 'login_errors', 'treats_login_error_message' );

/**
 * Remove the WordPress version from RSS feeds.
 *
 * @return string
 */
function treats_remove_version() {
	return '';
}
add_filter( 'the_generator', 'treats_remove_version' );

/**
 * Disable XML-RPC, which this site does not use and which is a common
 * brute-force and pingback-amplification target.
 *
 * @return bool
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * Remove XML-RPC and pingback headers.
 *
 * @param array<string,string> $headers Existing headers.
 * @return array<string,string>
 */
function treats_remove_pingback_header( $headers ) {
	unset( $headers['X-Pingback'] );

	return $headers;
}
add_filter( 'wp_headers', 'treats_remove_pingback_header', 20 );

/**
 * Block the REST user endpoint for unauthenticated requests so usernames are
 * not enumerable.
 *
 * @param mixed $result Existing result.
 * @return mixed
 */
function treats_restrict_user_endpoint( $result ) {
	if ( ! empty( $result ) ) {
		return $result;
	}

	if ( is_user_logged_in() ) {
		return $result;
	}

	$route = isset( $GLOBALS['wp']->query_vars['rest_route'] ) ? (string) $GLOBALS['wp']->query_vars['rest_route'] : '';

	if ( false !== strpos( $route, '/wp/v2/users' ) ) {
		return new WP_Error(
			'rest_forbidden',
			__( 'Not available.', 'treats' ),
			array( 'status' => 401 )
		);
	}

	return $result;
}
add_filter( 'rest_authentication_errors', 'treats_restrict_user_endpoint' );

/**
 * Stop ?author=N redirecting to a username-revealing archive.
 *
 * @return void
 */
function treats_block_author_scan() {
	if ( is_admin() || is_user_logged_in() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only guard.
	if ( isset( $_GET['author'] ) && ! empty( $_GET['author'] ) ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'treats_block_author_scan' );

/**
 * Strip potentially dangerous file types from uploads handled by editors.
 *
 * @param array<string,string> $mimes Allowed mime types.
 * @return array<string,string>
 */
function treats_restrict_upload_mimes( $mimes ) {
	unset( $mimes['exe'], $mimes['swf'], $mimes['msi'], $mimes['bat'], $mimes['htm|html'], $mimes['phtml'] );

	return $mimes;
}
add_filter( 'upload_mimes', 'treats_restrict_upload_mimes' );

/**
 * Simple per-IP rate limiter used by the front-end form handlers.
 *
 * Stores counters in transients — good enough to stop casual abuse without a
 * database table. Serious protection belongs at the edge.
 *
 * @param string $action    Action key.
 * @param int    $limit     Allowed attempts.
 * @param int    $window    Window in seconds.
 * @return bool True when the request is within the limit.
 */
function treats_rate_limit( $action, $limit = 5, $window = 600 ) {
	$key   = 'treats_rl_' . md5( $action . '|' . treats_client_ip_hash() );
	$count = (int) get_transient( $key );

	if ( $count >= $limit ) {
		return false;
	}

	set_transient( $key, $count + 1, $window );

	return true;
}

/**
 * A salted, one-way hash of the visitor's IP.
 *
 * The raw address is never stored, which keeps the submission records
 * proportionate under UK GDPR while still allowing abuse to be spotted.
 *
 * @return string
 */
function treats_client_ip_hash() {
	$ip = '';

	// Only trust a proxy header when the site is explicitly configured for one.
	$candidates = apply_filters( 'treats_ip_headers', array( 'REMOTE_ADDR' ) );

	foreach ( $candidates as $header ) {
		if ( ! empty( $_SERVER[ $header ] ) ) {
			$value = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
			$parts = explode( ',', $value );
			$ip    = trim( $parts[0] );
			break;
		}
	}

	if ( '' === $ip || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		$ip = '0.0.0.0';
	}

	return substr( wp_hash( $ip, 'nonce' ), 0, 16 );
}

/**
 * Escape a value for safe use inside an email body.
 *
 * @param string $value Raw value.
 * @return string
 */
function treats_email_safe( $value ) {
	return wp_strip_all_tags( str_replace( array( "\r", "\n" ), ' ', (string) $value ) );
}

/**
 * Disable file editing from the dashboard when the constant is not already set.
 *
 * @return void
 */
function treats_disable_file_edit() {
	if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
		define( 'DISALLOW_FILE_EDIT', true );
	}
}
add_action( 'init', 'treats_disable_file_edit' );
