<?php
/**
 * Treats loyalty card.
 *
 * The printed card, on a phone. Every hot drink a customer pays for earns a
 * stamp. Five stamps earn a free hot drink, ten a free scone and fifteen a free
 * cake, after which the card starts again — the same ladder as the 18-box
 * paper card, where boxes 6, 12 and 18 are the rewards.
 *
 * Customers can only look at their card. Stamps are added and rewards redeemed
 * from the Loyalty Till page, which staff unlock with their own code, so every
 * stamp in the log has a name against it.
 *
 * Data lives in three small tables rather than post meta: stamping has to be
 * a locked read-modify-write, and the log grows by a row per drink.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/** Schema version. Bump to run the table migration again. */
const TREATS_LOYALTY_DB_VERSION = '1';

/** Paid stamps in one full card. */
const TREATS_LOYALTY_CYCLE = 15;

/** Most stamps that can be added in one go, so a slip of the thumb cannot fill a card. */
const TREATS_LOYALTY_MAX_PER_ORDER = 6;

/** How long an unlocked till stays unlocked. */
const TREATS_LOYALTY_SESSION = 12 * HOUR_IN_SECONDS;

/** How long after a stamp it can still be undone. */
const TREATS_LOYALTY_UNDO_WINDOW = 15 * MINUTE_IN_SECONDS;

/** Cookie holding an unlocked till session. */
const TREATS_LOYALTY_TILL_COOKIE = 'treats_till';

/** Cookie that remembers a customer's card on their phone. */
const TREATS_LOYALTY_CARD_COOKIE = 'treats_card';

/* -------------------------------------------------------------------------
 * The reward ladder
 * ---------------------------------------------------------------------- */

/**
 * Rewards, keyed by the paid stamp that earns them.
 *
 * @return array<int,array{kind:string,label:string,box:int}>
 */
function treats_loyalty_ladder() {
	return array(
		5  => array(
			'kind'  => 'drink',
			'label' => __( 'Free hot drink', 'treats' ),
			'box'   => 6,
		),
		10 => array(
			'kind'  => 'scone',
			'label' => __( 'Free scone', 'treats' ),
			'box'   => 12,
		),
		15 => array(
			'kind'  => 'cake',
			'label' => __( 'Free cake', 'treats' ),
			'box'   => 18,
		),
	);
}

/**
 * Human label for a reward kind.
 *
 * @param string $kind Reward kind.
 * @return string
 */
function treats_loyalty_reward_label( $kind ) {
	foreach ( treats_loyalty_ladder() as $step ) {
		if ( $step['kind'] === $kind ) {
			return $step['label'];
		}
	}

	return __( 'Reward', 'treats' );
}

/**
 * The 18 boxes of the card, for a given number of paid stamps this card.
 *
 * Paid stamps fill boxes 1–5, 7–11 and 13–17; boxes 6, 12 and 18 light up as
 * their reward is earned.
 *
 * @param int $stamps Paid stamps on the current card.
 * @return array<int,array{box:int,type:string,filled:bool,kind:string}>
 */
function treats_loyalty_boxes( $stamps ) {
	$ladder = treats_loyalty_ladder();
	$boxes  = array();
	$paid   = 0;

	for ( $box = 1; $box <= 18; $box++ ) {
		$reward = null;

		foreach ( $ladder as $needed => $step ) {
			if ( $step['box'] === $box ) {
				$reward = array( $needed, $step );
			}
		}

		if ( $reward ) {
			$boxes[] = array(
				'box'    => $box,
				'type'   => 'reward',
				'filled' => $stamps >= $reward[0],
				'kind'   => $reward[1]['kind'],
			);
			continue;
		}

		$paid++;
		$boxes[] = array(
			'box'    => $box,
			'type'   => 'stamp',
			'filled' => $paid <= $stamps,
			'kind'   => '',
		);
	}

	return $boxes;
}

/* -------------------------------------------------------------------------
 * Tables
 * ---------------------------------------------------------------------- */

/**
 * Table names.
 *
 * @param string $which members|rewards|log.
 * @return string
 */
function treats_loyalty_table( $which ) {
	global $wpdb;

	return $wpdb->prefix . 'treats_loyalty_' . $which;
}

/**
 * Create or update the tables.
 *
 * @return void
 */
function treats_loyalty_install() {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset = $wpdb->get_charset_collate();
	$members = treats_loyalty_table( 'members' );
	$rewards = treats_loyalty_table( 'rewards' );
	$log     = treats_loyalty_table( 'log' );

	dbDelta(
		"CREATE TABLE {$members} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			card_code varchar(12) NOT NULL,
			token_hash char(64) NOT NULL,
			name varchar(100) NOT NULL,
			email varchar(190) NOT NULL,
			marketing tinyint(1) NOT NULL DEFAULT 0,
			stamps smallint(5) unsigned NOT NULL DEFAULT 0,
			lifetime int(10) unsigned NOT NULL DEFAULT 0,
			cards_completed int(10) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			last_stamp_at datetime NULL DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY card_code (card_code),
			UNIQUE KEY token_hash (token_hash),
			UNIQUE KEY email (email)
		) {$charset};"
	);

	dbDelta(
		"CREATE TABLE {$rewards} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			member_id bigint(20) unsigned NOT NULL,
			kind varchar(20) NOT NULL,
			earned_at datetime NOT NULL,
			redeemed_at datetime NULL DEFAULT NULL,
			redeemed_by varchar(100) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY member_id (member_id)
		) {$charset};"
	);

	dbDelta(
		"CREATE TABLE {$log} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			member_id bigint(20) unsigned NOT NULL,
			type varchar(20) NOT NULL,
			qty smallint(5) NOT NULL DEFAULT 0,
			staff varchar(100) NOT NULL DEFAULT '',
			detail text NULL,
			undone tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY member_id (member_id),
			KEY created_at (created_at)
		) {$charset};"
	);

	update_option( 'treats_loyalty_db_version', TREATS_LOYALTY_DB_VERSION, false );
}

/**
 * Install on first use and after a schema bump.
 *
 * Runs on `init` rather than only in the dashboard, because the first person
 * to touch the loyalty card after an update may well be a customer.
 *
 * @return void
 */
function treats_loyalty_maybe_install() {
	if ( get_option( 'treats_loyalty_db_version' ) !== TREATS_LOYALTY_DB_VERSION ) {
		treats_loyalty_install();
	}
}
add_action( 'init', 'treats_loyalty_maybe_install', 5 );

/* -------------------------------------------------------------------------
 * Members
 * ---------------------------------------------------------------------- */

/**
 * A short code for the QR and for reading out loud.
 *
 * No 0/O, 1/I/L or 5/S, so it survives being read across a counter.
 *
 * @return string e.g. "K7PM-4QXA".
 */
function treats_loyalty_new_code() {
	$alphabet = 'ACDEFGHJKMNPQRTUVWXY2346789';
	$code     = '';

	for ( $i = 0; $i < 8; $i++ ) {
		$code .= $alphabet[ random_int( 0, strlen( $alphabet ) - 1 ) ];
	}

	return substr( $code, 0, 4 ) . '-' . substr( $code, 4 );
}

/**
 * Normalise whatever staff typed or scanned into a card code.
 *
 * Accepts "k7pm4qxa", "K7PM-4QXA" and the full "TREATS:K7PM-4QXA" QR payload.
 *
 * @param string $raw Raw input.
 * @return string Empty when it cannot be a card code.
 */
function treats_loyalty_normalise_code( $raw ) {
	$raw = strtoupper( (string) $raw );
	$raw = preg_replace( '/^TREATS:/', '', $raw );
	$raw = preg_replace( '/[^A-Z0-9]/', '', $raw );

	if ( 8 !== strlen( $raw ) ) {
		return '';
	}

	return substr( $raw, 0, 4 ) . '-' . substr( $raw, 4 );
}

/**
 * Hash a card token for storage.
 *
 * The token in the card link works like a password; only its hash is kept, so
 * a copy of the database cannot be used to open anybody's card.
 *
 * @param string $token Raw token.
 * @return string
 */
function treats_loyalty_hash_token( $token ) {
	return hash_hmac( 'sha256', (string) $token, wp_salt( 'auth' ) );
}

/**
 * Fetch a member.
 *
 * @param string $field id|card_code|token|email.
 * @param mixed  $value Value.
 * @return object|null
 */
function treats_loyalty_get_member( $field, $value ) {
	global $wpdb;

	$table = treats_loyalty_table( 'members' );

	switch ( $field ) {
		case 'id':
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $value ) );
		case 'card_code':
			$code = treats_loyalty_normalise_code( $value );

			if ( '' === $code ) {
				return null;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE card_code = %s", $code ) );
		case 'token':
			if ( ! preg_match( '/^[a-f0-9]{40}$/', (string) $value ) ) {
				return null;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE token_hash = %s", treats_loyalty_hash_token( $value ) ) );
		case 'email':
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE email = %s", strtolower( trim( (string) $value ) ) ) );
	}

	return null;
}

/**
 * Create a member.
 *
 * @param string $name      Name.
 * @param string $email     Email.
 * @param bool   $marketing Whether they opted in to news.
 * @return array{member:object,token:string}|WP_Error
 */
function treats_loyalty_create_member( $name, $email, $marketing ) {
	global $wpdb;

	$table = treats_loyalty_table( 'members' );

	for ( $attempt = 0; $attempt < 5; $attempt++ ) {
		$token = bin2hex( random_bytes( 20 ) );
		$code  = treats_loyalty_new_code();

		$inserted = $wpdb->insert(
			$table,
			array(
				'card_code'  => $code,
				'token_hash' => treats_loyalty_hash_token( $token ),
				'name'       => $name,
				'email'      => strtolower( $email ),
				'marketing'  => $marketing ? 1 : 0,
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		if ( $inserted ) {
			$member = treats_loyalty_get_member( 'id', $wpdb->insert_id );
			treats_loyalty_log( (int) $member->id, 'join', 0, '' );

			return array(
				'member' => $member,
				'token'  => $token,
			);
		}

		// Somebody joined with the same email a moment ago.
		if ( treats_loyalty_get_member( 'email', $email ) ) {
			return new WP_Error( 'exists', 'exists' );
		}
		// Otherwise it was a code collision; go round again.
	}

	return new WP_Error( 'insert_failed', __( 'We could not create your card just now. Please try again.', 'treats' ) );
}

/**
 * Give a member a fresh card link, retiring the old one.
 *
 * Used when somebody asks for their link again: the email can only carry a
 * token we know, and we only keep hashes.
 *
 * @param object $member Member row.
 * @return string New token.
 */
function treats_loyalty_rotate_token( $member ) {
	global $wpdb;

	$token = bin2hex( random_bytes( 20 ) );

	$wpdb->update(
		treats_loyalty_table( 'members' ),
		array( 'token_hash' => treats_loyalty_hash_token( $token ) ),
		array( 'id' => (int) $member->id ),
		array( '%s' ),
		array( '%d' )
	);

	return $token;
}

/**
 * Rewards a member has earned and not used.
 *
 * @param int $member_id Member ID.
 * @return array<int,object>
 */
function treats_loyalty_available_rewards( $member_id ) {
	global $wpdb;

	$table = treats_loyalty_table( 'rewards' );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
	return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE member_id = %d AND redeemed_at IS NULL ORDER BY earned_at ASC, id ASC", $member_id ) );
}

/**
 * Write to the activity log.
 *
 * @param int    $member_id Member.
 * @param string $type      join|stamp|redeem|undo|adjust.
 * @param int    $qty       Stamps involved.
 * @param string $staff     Staff name.
 * @param array  $detail    Anything needed to undo it.
 * @return int Log ID.
 */
function treats_loyalty_log( $member_id, $type, $qty, $staff, $detail = array() ) {
	global $wpdb;

	$wpdb->insert(
		treats_loyalty_table( 'log' ),
		array(
			'member_id'  => $member_id,
			'type'       => $type,
			'qty'        => $qty,
			'staff'      => $staff,
			'detail'     => $detail ? wp_json_encode( $detail ) : null,
			'created_at' => current_time( 'mysql', true ),
		),
		array( '%d', '%s', '%d', '%s', '%s', '%s' )
	);

	return (int) $wpdb->insert_id;
}

/**
 * Everything the card and the till show about a member.
 *
 * @param object $member Member row.
 * @return array<string,mixed>
 */
function treats_loyalty_card_state( $member ) {
	$stamps  = (int) $member->stamps;
	$rewards = array();

	foreach ( treats_loyalty_available_rewards( (int) $member->id ) as $reward ) {
		$rewards[] = array(
			'id'    => (int) $reward->id,
			'kind'  => $reward->kind,
			'label' => treats_loyalty_reward_label( $reward->kind ),
		);
	}

	$next = null;

	foreach ( treats_loyalty_ladder() as $needed => $step ) {
		if ( $stamps < $needed ) {
			$next = array(
				'label' => $step['label'],
				'to_go' => $needed - $stamps,
			);
			break;
		}
	}

	return array(
		'code'     => $member->card_code,
		'name'     => $member->name,
		'first'    => strtok( (string) $member->name, ' ' ),
		'stamps'   => $stamps,
		'lifetime' => (int) $member->lifetime,
		'cards'    => (int) $member->cards_completed,
		'boxes'    => treats_loyalty_boxes( $stamps ),
		'rewards'  => $rewards,
		'next'     => $next,
		'version'  => md5( $stamps . '|' . $member->lifetime . '|' . wp_json_encode( $rewards ) ),
	);
}

/* -------------------------------------------------------------------------
 * Stamping and redeeming
 * ---------------------------------------------------------------------- */

/**
 * Add paid stamps to a card.
 *
 * Locked so two tills stamping the same card at once cannot lose a stamp or
 * hand out a reward twice.
 *
 * @param int    $member_id Member.
 * @param int    $qty       Stamps to add.
 * @param string $staff     Staff name.
 * @return array{member:object,earned:array<int,string>,log_id:int}|WP_Error
 */
function treats_loyalty_add_stamps( $member_id, $qty, $staff ) {
	global $wpdb;

	$qty = max( 1, min( TREATS_LOYALTY_MAX_PER_ORDER, (int) $qty ) );

	$members = treats_loyalty_table( 'members' );
	$rewards = treats_loyalty_table( 'rewards' );
	$ladder  = treats_loyalty_ladder();

	$wpdb->query( 'START TRANSACTION' );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
	$member = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$members} WHERE id = %d FOR UPDATE", $member_id ) );

	if ( ! $member ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'not_found', __( 'That card no longer exists.', 'treats' ) );
	}

	$before = array(
		'stamps'          => (int) $member->stamps,
		'lifetime'        => (int) $member->lifetime,
		'cards_completed' => (int) $member->cards_completed,
		'last_stamp_at'   => $member->last_stamp_at,
	);

	$stamps     = (int) $member->stamps;
	$cards      = (int) $member->cards_completed;
	$earned     = array();
	$reward_ids = array();
	$now        = current_time( 'mysql', true );

	for ( $i = 0; $i < $qty; $i++ ) {
		$stamps++;

		if ( isset( $ladder[ $stamps ] ) ) {
			$wpdb->insert(
				$rewards,
				array(
					'member_id' => $member_id,
					'kind'      => $ladder[ $stamps ]['kind'],
					'earned_at' => $now,
				),
				array( '%d', '%s', '%s' )
			);

			$reward_ids[] = (int) $wpdb->insert_id;
			$earned[]     = $ladder[ $stamps ]['label'];
		}

		if ( $stamps >= TREATS_LOYALTY_CYCLE ) {
			$stamps = 0;
			$cards++;
		}
	}

	$wpdb->update(
		$members,
		array(
			'stamps'          => $stamps,
			'lifetime'        => (int) $member->lifetime + $qty,
			'cards_completed' => $cards,
			'last_stamp_at'   => $now,
		),
		array( 'id' => $member_id ),
		array( '%d', '%d', '%d', '%s' ),
		array( '%d' )
	);

	$log_id = treats_loyalty_log(
		$member_id,
		'stamp',
		$qty,
		$staff,
		array(
			'before'  => $before,
			'rewards' => $reward_ids,
		)
	);

	$wpdb->query( 'COMMIT' );

	return array(
		'member' => treats_loyalty_get_member( 'id', $member_id ),
		'earned' => $earned,
		'log_id' => $log_id,
	);
}

/**
 * Undo a stamp entry made in the last few minutes.
 *
 * Refuses if a reward it earned has already been handed over: at that point
 * the drink has been made, and the honest record is to leave it.
 *
 * @param int    $log_id Log entry.
 * @param string $staff  Staff name doing the undo.
 * @return object|WP_Error Updated member.
 */
function treats_loyalty_undo( $log_id, $staff ) {
	global $wpdb;

	$log_table = treats_loyalty_table( 'log' );
	$members   = treats_loyalty_table( 'members' );
	$rewards   = treats_loyalty_table( 'rewards' );

	$wpdb->query( 'START TRANSACTION' );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
	$entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$log_table} WHERE id = %d FOR UPDATE", $log_id ) );

	if ( ! $entry || 'stamp' !== $entry->type || (int) $entry->undone ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'cannot_undo', __( 'There is nothing to undo.', 'treats' ) );
	}

	if ( time() - strtotime( $entry->created_at . ' UTC' ) > TREATS_LOYALTY_UNDO_WINDOW ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'too_late', __( 'That was too long ago to undo here. A manager can correct it in the dashboard.', 'treats' ) );
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
	$later = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$log_table} WHERE member_id = %d AND id > %d AND undone = 0 AND type IN ('stamp','adjust')", $entry->member_id, $entry->id ) );

	if ( $later ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'not_latest', __( 'This card has been stamped again since, so it cannot be undone here.', 'treats' ) );
	}

	$detail = json_decode( (string) $entry->detail, true );
	$ids    = array_map( 'intval', (array) ( $detail['rewards'] ?? array() ) );

	if ( $ids ) {
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Built above.
		$used = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$rewards} WHERE id IN ({$placeholders}) AND redeemed_at IS NOT NULL", $ids ) );

		if ( $used ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'reward_used', __( 'The reward from those stamps has already been used, so they cannot be undone.', 'treats' ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Built above.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$rewards} WHERE id IN ({$placeholders})", $ids ) );
	}

	$before = (array) ( $detail['before'] ?? array() );

	$wpdb->update(
		$members,
		array(
			'stamps'          => (int) ( $before['stamps'] ?? 0 ),
			'lifetime'        => (int) ( $before['lifetime'] ?? 0 ),
			'cards_completed' => (int) ( $before['cards_completed'] ?? 0 ),
			'last_stamp_at'   => $before['last_stamp_at'] ?? null,
		),
		array( 'id' => (int) $entry->member_id ),
		array( '%d', '%d', '%d', '%s' ),
		array( '%d' )
	);

	$wpdb->update( $log_table, array( 'undone' => 1 ), array( 'id' => (int) $entry->id ), array( '%d' ), array( '%d' ) );

	treats_loyalty_log( (int) $entry->member_id, 'undo', -1 * (int) $entry->qty, $staff, array( 'log_id' => (int) $entry->id ) );

	$wpdb->query( 'COMMIT' );

	return treats_loyalty_get_member( 'id', (int) $entry->member_id );
}

/**
 * Hand over a reward.
 *
 * @param int    $member_id Member.
 * @param int    $reward_id Reward.
 * @param string $staff     Staff name.
 * @return string|WP_Error Reward label.
 */
function treats_loyalty_redeem( $member_id, $reward_id, $staff ) {
	global $wpdb;

	$table = treats_loyalty_table( 'rewards' );

	// The IS NULL guard makes this the lock: of two simultaneous taps, only
	// one update can match.
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
	$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET redeemed_at = %s, redeemed_by = %s WHERE id = %d AND member_id = %d AND redeemed_at IS NULL", current_time( 'mysql', true ), $staff, $reward_id, $member_id ) );

	if ( ! $updated ) {
		return new WP_Error( 'not_available', __( 'That reward has already been used.', 'treats' ) );
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
	$kind  = (string) $wpdb->get_var( $wpdb->prepare( "SELECT kind FROM {$table} WHERE id = %d", $reward_id ) );
	$label = treats_loyalty_reward_label( $kind );

	treats_loyalty_log( $member_id, 'redeem', 0, $staff, array( 'reward_id' => $reward_id, 'kind' => $kind ) );

	return $label;
}

/* -------------------------------------------------------------------------
 * Staff and the till session
 * ---------------------------------------------------------------------- */

/**
 * Staff who can use the till.
 *
 * @return array<string,array{name:string,hash:string}> Keyed by staff ID.
 */
function treats_loyalty_staff() {
	$staff = get_option( 'treats_loyalty_staff', array() );

	return is_array( $staff ) ? $staff : array();
}

/**
 * Which staff member a code belongs to.
 *
 * @param string $code Code typed at the till.
 * @return array{id:string,name:string}|null
 */
function treats_loyalty_staff_by_code( $code ) {
	foreach ( treats_loyalty_staff() as $id => $person ) {
		if ( wp_check_password( $code, $person['hash'] ) ) {
			return array(
				'id'   => (string) $id,
				'name' => $person['name'],
			);
		}
	}

	return null;
}

/**
 * Sign a till session value.
 *
 * @param string $payload Payload.
 * @return string
 */
function treats_loyalty_sign( $payload ) {
	return hash_hmac( 'sha256', $payload, wp_salt( 'logged_in' ) . '|treats_till' );
}

/**
 * Unlock the till for a staff member.
 *
 * @param string $staff_id Staff ID.
 * @return void
 */
function treats_loyalty_start_session( $staff_id ) {
	$expires = time() + TREATS_LOYALTY_SESSION;
	$payload = $staff_id . '|' . $expires;
	$value   = $payload . '|' . treats_loyalty_sign( $payload );

	setcookie(
		TREATS_LOYALTY_TILL_COOKIE,
		$value,
		array(
			'expires'  => $expires,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Strict',
		)
	);
}

/**
 * Lock the till.
 *
 * @return void
 */
function treats_loyalty_end_session() {
	setcookie(
		TREATS_LOYALTY_TILL_COOKIE,
		'',
		array(
			'expires'  => time() - DAY_IN_SECONDS,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Strict',
		)
	);
}

/**
 * The staff member the till is unlocked for, if any.
 *
 * Removing somebody in the dashboard locks them out straight away, because
 * the session is checked against the staff list on every request.
 *
 * @return array{id:string,name:string}|null
 */
function treats_loyalty_current_staff() {
	if ( empty( $_COOKIE[ TREATS_LOYALTY_TILL_COOKIE ] ) ) {
		return null;
	}

	$parts = explode( '|', sanitize_text_field( wp_unslash( $_COOKIE[ TREATS_LOYALTY_TILL_COOKIE ] ) ) );

	if ( 3 !== count( $parts ) ) {
		return null;
	}

	list( $staff_id, $expires, $signature ) = $parts;

	if ( ! hash_equals( treats_loyalty_sign( $staff_id . '|' . $expires ), $signature ) || (int) $expires < time() ) {
		return null;
	}

	$staff = treats_loyalty_staff();

	if ( empty( $staff[ $staff_id ] ) ) {
		return null;
	}

	return array(
		'id'   => $staff_id,
		'name' => $staff[ $staff_id ]['name'],
	);
}

/* -------------------------------------------------------------------------
 * Pages
 * ---------------------------------------------------------------------- */

/**
 * Whether the current request is the customer card page.
 *
 * @return bool
 */
function treats_is_loyalty_page() {
	return is_page_template( 'page-templates/template-loyalty.php' );
}

/**
 * Whether the current request is the till page.
 *
 * @return bool
 */
function treats_is_loyalty_till() {
	return is_page_template( 'page-templates/template-loyalty-till.php' );
}

/**
 * URL of the customer card page, optionally opened on a card.
 *
 * @param string $token Card token.
 * @return string
 */
function treats_loyalty_card_url( $token = '' ) {
	$url = treats_get_template_page_url( 'page-templates/template-loyalty.php' );

	if ( ! $url ) {
		$url = home_url( '/loyalty-card/' );
	}

	return $token ? add_query_arg( 'card', $token, $url ) : $url;
}

/**
 * The card a visitor to the loyalty page should see.
 *
 * A link from the email wins; otherwise the card this phone opened last.
 * Opening a link remembers it, so the home-screen icon always opens the card.
 *
 * @return array{member:object,token:string}|null
 */
function treats_loyalty_visitor_card() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The token is the credential.
	$token = isset( $_GET['card'] ) ? sanitize_key( wp_unslash( $_GET['card'] ) ) : '';

	if ( '' === $token && ! empty( $_COOKIE[ TREATS_LOYALTY_CARD_COOKIE ] ) ) {
		$token = sanitize_key( wp_unslash( $_COOKIE[ TREATS_LOYALTY_CARD_COOKIE ] ) );
	}

	if ( '' === $token ) {
		return null;
	}

	$member = treats_loyalty_get_member( 'token', $token );

	if ( ! $member ) {
		return null;
	}

	return array(
		'member' => $member,
		'token'  => $token,
	);
}

/**
 * Page-level housekeeping for the two loyalty pages.
 *
 * - Never cache them: they are personal.
 * - Remember the card on this phone when it is opened from a link.
 * - Let the till use the camera, which the site otherwise switches off.
 *
 * @return void
 */
function treats_loyalty_template_redirect() {
	if ( treats_is_loyalty_page() ) {
		nocache_headers();

		$card = treats_loyalty_visitor_card();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only checking presence.
		if ( $card && isset( $_GET['card'] ) ) {
			setcookie(
				TREATS_LOYALTY_CARD_COOKIE,
				$card['token'],
				array(
					'expires'  => time() + 2 * YEAR_IN_SECONDS,
					'path'     => COOKIEPATH ? COOKIEPATH : '/',
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Signing out of a card on this phone only.
		if ( isset( $_GET['forget'] ) ) {
			setcookie(
				TREATS_LOYALTY_CARD_COOKIE,
				'',
				array(
					'expires' => time() - DAY_IN_SECONDS,
					'path'    => COOKIEPATH ? COOKIEPATH : '/',
				)
			);
			wp_safe_redirect( treats_loyalty_card_url() );
			exit;
		}
	}

	if ( treats_is_loyalty_till() ) {
		nocache_headers();
		header( 'Permissions-Policy: camera=(self), microphone=(), geolocation=(self), interest-cohort=()' );
		header( 'X-Robots-Tag: noindex, nofollow' );
	}
}
add_action( 'template_redirect', 'treats_loyalty_template_redirect' );

/**
 * Keep both loyalty pages out of search results.
 *
 * @param array<string,bool> $robots Robots directives.
 * @return array<string,bool>
 */
function treats_loyalty_robots( $robots ) {
	if ( treats_is_loyalty_till() || ( treats_is_loyalty_page() && treats_loyalty_visitor_card() ) ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}

	return $robots;
}
add_filter( 'wp_robots', 'treats_loyalty_robots' );

/**
 * Leave the till out of the XML sitemap.
 *
 * @param array<string,mixed> $args Query args.
 * @return array<string,mixed>
 */
function treats_loyalty_sitemap_exclude( $args ) {
	$till = treats_get_template_page_id( 'page-templates/template-loyalty-till.php' );

	if ( $till ) {
		$args['post__not_in']   = isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array();
		$args['post__not_in'][] = $till;
	}

	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'treats_loyalty_sitemap_exclude' );

/**
 * Assets for the loyalty pages.
 *
 * @return void
 */
function treats_loyalty_enqueue() {
	$is_card = treats_is_loyalty_page();
	$is_till = treats_is_loyalty_till();

	if ( ! $is_card && ! $is_till ) {
		return;
	}

	wp_enqueue_style( 'treats-forms', TREATS_URI . 'css/forms.css', array( 'treats-main' ), treats_asset_version( 'css/forms.css' ) );
	wp_enqueue_style( 'treats-loyalty', TREATS_URI . 'css/loyalty.css', array( 'treats-main' ), treats_asset_version( 'css/loyalty.css' ) );

	wp_enqueue_script( 'treats-qrcode', TREATS_URI . 'js/vendor/qrcode-generator.js', array(), treats_asset_version( 'js/vendor/qrcode-generator.js' ), true );
	wp_enqueue_script( 'treats-loyalty', TREATS_URI . 'js/loyalty.js', array( 'treats-qrcode' ), treats_asset_version( 'js/loyalty.js' ), true );

	wp_add_inline_script(
		'treats-loyalty',
		'window.treatsLoyalty = ' . wp_json_encode(
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'treats_public' ),
				'tillNonce'  => wp_create_nonce( 'treats_till' ),
				'jsqrUrl'    => TREATS_URI . 'js/vendor/jsqr.js?ver=' . rawurlencode( treats_asset_version( 'js/vendor/jsqr.js' ) ),
				'maxPerOrder' => TREATS_LOYALTY_MAX_PER_ORDER,
				'phone'      => treats_get_phone(),
				'i18n'       => array(
					'generic'     => __( 'Something went wrong. Please try again.', 'treats' ),
					'offline'     => __( 'No connection. Check the Wi-Fi and try again.', 'treats' ),
					'sending'     => __( 'One moment…', 'treats' ),
					'cameraNo'    => __( 'The camera could not be opened. Type the card code instead.', 'treats' ),
					'stamped'     => __( 'Stamped', 'treats' ),
					'earned'      => __( 'Earned', 'treats' ),
					'redeemed'    => __( 'Given', 'treats' ),
					'undone'      => __( 'Undone', 'treats' ),
					/* translators: 1: number of stamps, 2: reward name. */
					'toGo'        => __( '%1$d more hot drinks until your %2$s', 'treats' ),
					/* translators: %2$s: reward name. */
					'toGoOne'     => __( '1 more hot drink until your %2$s', 'treats' ),
					'newStamp'    => __( 'New stamp!', 'treats' ),
					'rewardReady' => __( 'Reward ready — show this card at the counter', 'treats' ),
				),
			)
		) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'treats_loyalty_enqueue', 20 );

/* -------------------------------------------------------------------------
 * Customer endpoints
 * ---------------------------------------------------------------------- */

/**
 * Register AJAX endpoints.
 *
 * @return void
 */
function treats_loyalty_register_ajax() {
	$public = array( 'join', 'resend', 'card' );
	$till   = array( 'login', 'logout', 'lookup', 'stamp', 'redeem', 'undo' );

	foreach ( $public as $action ) {
		add_action( 'wp_ajax_treats_loyalty_' . $action, 'treats_loyalty_ajax_' . $action );
		add_action( 'wp_ajax_nopriv_treats_loyalty_' . $action, 'treats_loyalty_ajax_' . $action );
	}

	foreach ( $till as $action ) {
		add_action( 'wp_ajax_treats_till_' . $action, 'treats_loyalty_ajax_till_' . $action );
		add_action( 'wp_ajax_nopriv_treats_till_' . $action, 'treats_loyalty_ajax_till_' . $action );
	}
}
add_action( 'init', 'treats_loyalty_register_ajax' );

/**
 * Common checks for the customer forms: nonce, honeypot, time trap, rate limit.
 *
 * @param string $action Rate-limit bucket.
 * @param int    $limit  Attempts per hour.
 * @return void Sends an error and exits on failure.
 */
function treats_loyalty_guard_public( $action, $limit ) {
	$nonce = isset( $_POST['treats_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['treats_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'treats_public' ) ) {
		wp_send_json_error( array( 'message' => __( 'Your session expired. Please refresh the page and try again.', 'treats' ) ), 403 );
	}

	if ( ! empty( $_POST['treats_website'] ) ) {
		wp_send_json_success( array( 'message' => __( 'Thank you.', 'treats' ) ) );
	}

	$rendered = isset( $_POST['treats_ts'] ) ? (int) $_POST['treats_ts'] : 0;

	if ( $rendered && ( time() - $rendered ) < 2 ) {
		wp_send_json_error( array( 'message' => __( 'That was a little too quick — please try again.', 'treats' ) ), 429 );
	}

	if ( ! treats_rate_limit( 'loyalty_' . $action, $limit, HOUR_IN_SECONDS ) ) {
		wp_send_json_error( array( 'message' => __( 'Too many attempts from this connection. Please ask us at the counter.', 'treats' ) ), 429 );
	}
}

/**
 * Join: create a card and open it.
 *
 * Joining with an email that already has a card does not reveal that it does;
 * it sends that address a fresh link and says to check the inbox.
 *
 * @return void
 */
function treats_loyalty_ajax_join() {
	treats_loyalty_guard_public( 'join', 10 );

	$name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$marketing = ! empty( $_POST['marketing'] );
	$errors    = array();

	if ( '' === $name || mb_strlen( $name ) > 100 ) {
		$errors['name'] = __( 'Please tell us your name.', 'treats' );
	}

	if ( ! is_email( $email ) ) {
		$errors['email'] = __( 'Please enter a valid email address.', 'treats' );
	}

	if ( empty( $_POST['terms'] ) ) {
		$errors['terms'] = __( 'Please confirm you are happy with how we use your details.', 'treats' );
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

	$existing = treats_loyalty_get_member( 'email', $email );

	if ( $existing ) {
		treats_loyalty_send_link( $existing, treats_loyalty_rotate_token( $existing ), false );

		wp_send_json_success(
			array(
				'message' => __( 'You already have a Treats card. We have emailed you a link to open it — check your inbox.', 'treats' ),
			)
		);
	}

	$created = treats_loyalty_create_member( $name, $email, $marketing );

	if ( is_wp_error( $created ) ) {
		if ( 'exists' === $created->get_error_code() ) {
			wp_send_json_success( array( 'message' => __( 'You already have a Treats card. Check your inbox for the link.', 'treats' ) ) );
		}

		wp_send_json_error( array( 'message' => $created->get_error_message() ), 500 );
	}

	treats_loyalty_send_link( $created['member'], $created['token'], true );

	wp_send_json_success(
		array(
			'message'  => __( 'Your card is ready.', 'treats' ),
			'redirect' => treats_loyalty_card_url( $created['token'] ),
		)
	);
}

/**
 * Lost card: email a fresh link.
 *
 * Always answers the same way, so the form cannot be used to find out who
 * has a card.
 *
 * @return void
 */
function treats_loyalty_ajax_resend() {
	treats_loyalty_guard_public( 'resend', 5 );

	$email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$member = is_email( $email ) ? treats_loyalty_get_member( 'email', $email ) : null;

	if ( $member ) {
		treats_loyalty_send_link( $member, treats_loyalty_rotate_token( $member ), false );
	}

	wp_send_json_success(
		array(
			'message' => __( 'If that address has a Treats card, a link to it is on its way. It can take a few minutes — check your junk folder too.', 'treats' ),
		)
	);
}

/**
 * The card's current state, polled by the card page.
 *
 * @return void
 */
function treats_loyalty_ajax_card() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only; the token is the credential.
	$token = isset( $_REQUEST['token'] ) ? sanitize_key( wp_unslash( $_REQUEST['token'] ) ) : '';

	if ( '' === $token && ! empty( $_COOKIE[ TREATS_LOYALTY_CARD_COOKIE ] ) ) {
		$token = sanitize_key( wp_unslash( $_COOKIE[ TREATS_LOYALTY_CARD_COOKIE ] ) );
	}

	if ( ! treats_rate_limit( 'loyalty_card', 600, HOUR_IN_SECONDS ) ) {
		wp_send_json_error( array( 'message' => 'slow down' ), 429 );
	}

	$member = treats_loyalty_get_member( 'token', $token );

	if ( ! $member ) {
		wp_send_json_error( array( 'message' => __( 'Card not found.', 'treats' ) ), 404 );
	}

	nocache_headers();
	wp_send_json_success( treats_loyalty_card_state( $member ) );
}

/* -------------------------------------------------------------------------
 * Till endpoints
 * ---------------------------------------------------------------------- */

/**
 * Checks for every till request after login.
 *
 * @return array{id:string,name:string} The staff member.
 */
function treats_loyalty_guard_till() {
	$nonce = isset( $_POST['treats_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['treats_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'treats_till' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'This page has been open a while. Refresh it and try again.', 'treats' ),
				'refresh' => true,
			),
			403
		);
	}

	$staff = treats_loyalty_current_staff();

	if ( ! $staff ) {
		wp_send_json_error(
			array(
				'message' => __( 'The till has locked. Enter your code again.', 'treats' ),
				'locked'  => true,
			),
			401
		);
	}

	return $staff;
}

/**
 * Unlock the till with a staff code.
 *
 * Per-connection and site-wide limits together make guessing a code a slow
 * business: five tries per connection every fifteen minutes, and thirty
 * failures across the whole site in an hour locks the till for everyone.
 *
 * @return void
 */
function treats_loyalty_ajax_till_login() {
	$nonce = isset( $_POST['treats_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['treats_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'treats_till' ) ) {
		wp_send_json_error( array( 'message' => __( 'Refresh the page and try again.', 'treats' ) ), 403 );
	}

	$failures = (int) get_transient( 'treats_till_failures' );

	if ( $failures >= 30 ) {
		wp_send_json_error( array( 'message' => __( 'Too many wrong codes. The till is locked for an hour — a manager can check the staff codes in the dashboard.', 'treats' ) ), 429 );
	}

	if ( ! treats_rate_limit( 'till_login', 5, 15 * MINUTE_IN_SECONDS ) ) {
		wp_send_json_error( array( 'message' => __( 'Too many tries. Wait fifteen minutes and try again.', 'treats' ) ), 429 );
	}

	$code  = isset( $_POST['code'] ) ? preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['code'] ) ) ) : '';
	$staff = ( strlen( $code ) >= 4 ) ? treats_loyalty_staff_by_code( $code ) : null;

	if ( ! $staff ) {
		set_transient( 'treats_till_failures', $failures + 1, HOUR_IN_SECONDS );
		wp_send_json_error( array( 'message' => __( 'That code was not recognised.', 'treats' ) ), 401 );
	}

	treats_loyalty_start_session( $staff['id'] );

	wp_send_json_success( array( 'name' => $staff['name'] ) );
}

/**
 * Lock the till.
 *
 * @return void
 */
function treats_loyalty_ajax_till_logout() {
	treats_loyalty_end_session();
	wp_send_json_success();
}

/**
 * Find a card by code, QR payload or email.
 *
 * @return void
 */
function treats_loyalty_ajax_till_lookup() {
	treats_loyalty_guard_till();

	$query  = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
	$member = null;

	if ( false !== strpos( $query, '@' ) ) {
		$member = treats_loyalty_get_member( 'email', $query );
	} else {
		$member = treats_loyalty_get_member( 'card_code', $query );
	}

	if ( ! $member ) {
		wp_send_json_error( array( 'message' => __( 'No card found. Check the code, or try their email address.', 'treats' ) ), 404 );
	}

	wp_send_json_success( array( 'card' => treats_loyalty_card_state( $member ) ) );
}

/**
 * Stamp a card.
 *
 * @return void
 */
function treats_loyalty_ajax_till_stamp() {
	$staff  = treats_loyalty_guard_till();
	$code   = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
	$qty    = isset( $_POST['qty'] ) ? (int) $_POST['qty'] : 1;
	$member = treats_loyalty_get_member( 'card_code', $code );

	if ( ! $member ) {
		wp_send_json_error( array( 'message' => __( 'Card not found.', 'treats' ) ), 404 );
	}

	if ( $qty < 1 || $qty > TREATS_LOYALTY_MAX_PER_ORDER ) {
		/* translators: %d: maximum stamps. */
		wp_send_json_error( array( 'message' => sprintf( __( 'Between 1 and %d stamps at a time.', 'treats' ), TREATS_LOYALTY_MAX_PER_ORDER ) ), 422 );
	}

	$result = treats_loyalty_add_stamps( (int) $member->id, $qty, $staff['name'] );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), 409 );
	}

	wp_send_json_success(
		array(
			'card'   => treats_loyalty_card_state( $result['member'] ),
			'earned' => $result['earned'],
			'logId'  => $result['log_id'],
		)
	);
}

/**
 * Redeem a reward.
 *
 * @return void
 */
function treats_loyalty_ajax_till_redeem() {
	$staff     = treats_loyalty_guard_till();
	$code      = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
	$reward_id = isset( $_POST['reward'] ) ? (int) $_POST['reward'] : 0;
	$member    = treats_loyalty_get_member( 'card_code', $code );

	if ( ! $member ) {
		wp_send_json_error( array( 'message' => __( 'Card not found.', 'treats' ) ), 404 );
	}

	$label = treats_loyalty_redeem( (int) $member->id, $reward_id, $staff['name'] );

	if ( is_wp_error( $label ) ) {
		wp_send_json_error(
			array(
				'message' => $label->get_error_message(),
				'card'    => treats_loyalty_card_state( $member ),
			),
			409
		);
	}

	wp_send_json_success(
		array(
			'card'  => treats_loyalty_card_state( treats_loyalty_get_member( 'id', (int) $member->id ) ),
			'label' => $label,
		)
	);
}

/**
 * Undo the last stamp.
 *
 * @return void
 */
function treats_loyalty_ajax_till_undo() {
	$staff  = treats_loyalty_guard_till();
	$log_id = isset( $_POST['log'] ) ? (int) $_POST['log'] : 0;
	$member = treats_loyalty_undo( $log_id, $staff['name'] );

	if ( is_wp_error( $member ) ) {
		wp_send_json_error( array( 'message' => $member->get_error_message() ), 409 );
	}

	wp_send_json_success( array( 'card' => treats_loyalty_card_state( $member ) ) );
}

/* -------------------------------------------------------------------------
 * Email
 * ---------------------------------------------------------------------- */

/**
 * Send someone the link to their card.
 *
 * @param object $member Member row.
 * @param string $token  Token for the link.
 * @param bool   $is_new Whether they have just joined.
 * @return bool
 */
function treats_loyalty_send_link( $member, $token, $is_new ) {
	$url   = treats_loyalty_card_url( $token );
	$first = strtok( (string) $member->name, ' ' );

	$paragraphs = $is_new
		? array(
			__( 'Welcome to the Treats loyalty card. Your card is ready.', 'treats' ),
			__( 'Show the QR code on your card at the counter whenever you buy a hot drink and we will stamp it. Your 6th hot drink is on us, then a free scone, then a free cake.', 'treats' ),
			__( 'Keep this email: the button below always opens your card. On your phone, add the card to your home screen so it is one tap away.', 'treats' ),
		)
		: array(
			__( 'Here is the link to your Treats loyalty card. Any older links to your card have stopped working.', 'treats' ),
			__( 'If you did not ask for this, you can ignore this email. Your stamps are safe.', 'treats' ),
		);

	$subject = $is_new
		? __( 'Your Treats loyalty card', 'treats' )
		: __( 'Your Treats loyalty card link', 'treats' );

	return treats_loyalty_mail( $member->email, $subject, $first, $paragraphs, __( 'Open my card', 'treats' ), $url );
}

/**
 * Send a branded email with a plain-text alternative.
 *
 * Light green page, gold border, gold top bar and divider, the gold logo, a
 * gold "Treats" sign-off and the address footer — the café's email style.
 *
 * @param string   $to         Recipient.
 * @param string   $subject    Subject.
 * @param string   $first_name Greeting name.
 * @param string[] $paragraphs Body paragraphs.
 * @param string   $button     Button label.
 * @param string   $url        Button URL.
 * @return bool
 */
function treats_loyalty_mail( $to, $subject, $first_name, $paragraphs, $button, $url ) {
	$logo    = TREATS_URI . 'images/logo.png';
	$address = treats_get_address_line();
	$phone   = treats_get_phone();
	$site    = wp_parse_url( home_url(), PHP_URL_HOST );
	$footer  = trim( implode( ' · ', array_filter( array( $address, $phone, $site ) ) ) );
	$gold    = '#b48a3c';

	$body = '';

	foreach ( $paragraphs as $paragraph ) {
		$body .= '<p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#2f3a2b;">' . esc_html( $paragraph ) . '</p>';
	}

	$html = '<!DOCTYPE html><html lang="en-GB"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html( $subject ) . '</title></head>'
		. '<body style="margin:0;padding:0;background:#eaf2e3;">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eaf2e3;"><tr><td align="center" style="padding:24px 12px;">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#eaf2e3;border:2px solid ' . $gold . ';border-radius:12px;overflow:hidden;">'
		. '<tr><td style="height:6px;background:' . $gold . ';font-size:0;line-height:0;">&nbsp;</td></tr>'
		. '<tr><td align="center" style="padding:28px 28px 8px;"><img src="' . esc_url( $logo ) . '" width="220" alt="Treats — Tea Rooms | Cafe, Est. 1984" style="display:block;width:220px;max-width:70%;height:auto;border:0;"></td></tr>'
		. '<tr><td style="padding:8px 28px 0;"><div style="height:1px;background:' . $gold . ';font-size:0;line-height:0;">&nbsp;</div></td></tr>'
		. '<tr><td style="padding:24px 28px 8px;font-family:Georgia,\'Times New Roman\',serif;">'
		. '<p style="margin:0 0 16px;font-size:15px;color:#2f3a2b;">' . esc_html( sprintf( /* translators: %s: first name. */ __( 'Dear %s,', 'treats' ), $first_name ) ) . '</p>'
		. $body
		. '<p style="margin:24px 0;text-align:center;"><a href="' . esc_url( $url ) . '" style="display:inline-block;background:' . $gold . ';color:#ffffff;text-decoration:none;font-family:Arial,sans-serif;font-size:16px;font-weight:bold;padding:14px 28px;border-radius:8px;">' . esc_html( $button ) . '</a></p>'
		. '<p style="margin:0 0 4px;font-size:16px;color:#2f3a2b;">' . esc_html__( 'With warm wishes,', 'treats' ) . '</p>'
		. '<p style="margin:0 0 8px;font-family:Georgia,serif;font-style:italic;font-size:30px;color:' . $gold . ';">Treats</p>'
		. '</td></tr>'
		. '<tr><td style="padding:0 28px;"><div style="height:1px;background:' . $gold . ';font-size:0;line-height:0;">&nbsp;</div></td></tr>'
		. '<tr><td align="center" style="padding:16px 28px 24px;font-family:Arial,sans-serif;font-size:12px;color:#5d6656;">' . esc_html( $footer ) . '</td></tr>'
		. '</table></td></tr></table></body></html>';

	$text = sprintf( /* translators: %s: first name. */ __( 'Dear %s,', 'treats' ), $first_name ) . "\n\n"
		. implode( "\n\n", $paragraphs ) . "\n\n"
		. $button . ': ' . $url . "\n\n"
		. __( 'With warm wishes,', 'treats' ) . "\nTreats\n\n"
		. $footer . "\n";

	$set_alt = static function ( $mailer ) use ( $text ) {
		$mailer->AltBody = $text; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer API.
	};

	add_action( 'phpmailer_init', $set_alt );

	$sent = wp_mail( $to, $subject, $html, array( 'Content-Type: text/html; charset=UTF-8' ) );

	remove_action( 'phpmailer_init', $set_alt );

	return (bool) $sent;
}

/* -------------------------------------------------------------------------
 * Dashboard
 * ---------------------------------------------------------------------- */

/**
 * Capability needed to manage the loyalty scheme.
 *
 * @return string
 */
function treats_loyalty_capability() {
	return (string) apply_filters( 'treats_loyalty_capability', 'edit_pages' );
}

/**
 * Add the Loyalty screen.
 *
 * @return void
 */
function treats_loyalty_admin_menu() {
	add_menu_page(
		__( 'Loyalty card', 'treats' ),
		__( 'Loyalty', 'treats' ),
		treats_loyalty_capability(),
		'treats-loyalty',
		'treats_loyalty_admin_page',
		'dashicons-awards',
		26
	);
}
add_action( 'admin_menu', 'treats_loyalty_admin_menu' );

/**
 * Handle dashboard actions: staff codes, adjustments, deletion, export.
 *
 * @return void
 */
function treats_loyalty_admin_actions() {
	if ( empty( $_POST['treats_loyalty_admin'] ) && empty( $_GET['treats_loyalty_export'] ) ) {
		return;
	}

	if ( ! current_user_can( treats_loyalty_capability() ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', 'treats' ) );
	}

	global $wpdb;

	$back = admin_url( 'admin.php?page=treats-loyalty' );

	if ( ! empty( $_GET['treats_loyalty_export'] ) ) {
		check_admin_referer( 'treats_loyalty_export' );

		$table = treats_loyalty_table( 'members' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
		$rows = $wpdb->get_results( "SELECT name, email, created_at FROM {$table} WHERE marketing = 1 ORDER BY created_at ASC" );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=treats-loyalty-newsletter-' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streaming a download.
		fputcsv( $out, array( 'Name', 'Email', 'Joined' ) );

		foreach ( $rows as $row ) {
			// Stop a name starting with = from running as a formula in Excel.
			$name = preg_match( '/^[=+\-@]/', $row->name ) ? "'" . $row->name : $row->name;
			fputcsv( $out, array( $name, $row->email, $row->created_at ) );
		}

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Streaming a download.
		exit;
	}

	check_admin_referer( 'treats_loyalty_admin' );

	$do      = sanitize_key( wp_unslash( $_POST['treats_loyalty_admin'] ) );
	$user    = wp_get_current_user();
	$manager = $user->display_name ? $user->display_name : $user->user_login;
	$notice  = '';

	switch ( $do ) {
		case 'add_staff':
			$name = isset( $_POST['staff_name'] ) ? sanitize_text_field( wp_unslash( $_POST['staff_name'] ) ) : '';
			$code = isset( $_POST['staff_code'] ) ? preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['staff_code'] ) ) ) : '';

			if ( '' === $name || strlen( $code ) < 4 || strlen( $code ) > 8 ) {
				$notice = 'staff_invalid';
				break;
			}

			if ( treats_loyalty_staff_by_code( $code ) ) {
				$notice = 'staff_duplicate';
				break;
			}

			$staff                           = treats_loyalty_staff();
			$staff[ wp_generate_uuid4() ] = array(
				'name' => $name,
				'hash' => wp_hash_password( $code ),
			);
			update_option( 'treats_loyalty_staff', $staff, false );
			$notice = 'staff_added';
			break;

		case 'remove_staff':
			$id    = isset( $_POST['staff_id'] ) ? sanitize_text_field( wp_unslash( $_POST['staff_id'] ) ) : '';
			$staff = treats_loyalty_staff();
			unset( $staff[ $id ] );
			update_option( 'treats_loyalty_staff', $staff, false );
			$notice = 'staff_removed';
			break;

		case 'adjust':
			$member_id = isset( $_POST['member_id'] ) ? (int) $_POST['member_id'] : 0;
			$stamps    = isset( $_POST['stamps'] ) ? (int) $_POST['stamps'] : -1;
			$member    = treats_loyalty_get_member( 'id', $member_id );

			if ( ! $member || $stamps < 0 || $stamps >= TREATS_LOYALTY_CYCLE ) {
				$notice = 'adjust_invalid';
				break;
			}

			$wpdb->update( treats_loyalty_table( 'members' ), array( 'stamps' => $stamps ), array( 'id' => $member_id ), array( '%d' ), array( '%d' ) );
			treats_loyalty_log( $member_id, 'adjust', $stamps - (int) $member->stamps, $manager, array( 'from' => (int) $member->stamps, 'to' => $stamps ) );
			$notice = 'adjusted';
			break;

		case 'delete_member':
			$member_id = isset( $_POST['member_id'] ) ? (int) $_POST['member_id'] : 0;

			$wpdb->delete( treats_loyalty_table( 'rewards' ), array( 'member_id' => $member_id ), array( '%d' ) );
			$wpdb->delete( treats_loyalty_table( 'log' ), array( 'member_id' => $member_id ), array( '%d' ) );
			$wpdb->delete( treats_loyalty_table( 'members' ), array( 'id' => $member_id ), array( '%d' ) );
			$notice = 'deleted';
			break;
	}

	wp_safe_redirect( add_query_arg( 'notice', $notice, $back ) );
	exit;
}
add_action( 'admin_init', 'treats_loyalty_admin_actions' );

/**
 * Render the Loyalty screen.
 *
 * @return void
 */
function treats_loyalty_admin_page() {
	global $wpdb;

	$members_table = treats_loyalty_table( 'members' );
	$rewards_table = treats_loyalty_table( 'rewards' );
	$log_table     = treats_loyalty_table( 'log' );

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display filters only.
	$tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'members';
	$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$notice = isset( $_GET['notice'] ) ? sanitize_key( wp_unslash( $_GET['notice'] ) ) : '';
	// phpcs:enable

	$notices = array(
		'staff_added'     => array( 'success', __( 'Staff member added. They can unlock the till with their code now.', 'treats' ) ),
		'staff_removed'   => array( 'success', __( 'Staff member removed. Their code stopped working straight away.', 'treats' ) ),
		'staff_invalid'   => array( 'error', __( 'Enter a name and a code of 4 to 8 digits.', 'treats' ) ),
		'staff_duplicate' => array( 'error', __( 'Somebody already uses that code. Choose another.', 'treats' ) ),
		'adjusted'        => array( 'success', __( 'Stamps updated and the change logged.', 'treats' ) ),
		'adjust_invalid'  => array( 'error', __( 'Stamps must be between 0 and 14.', 'treats' ) ),
		'deleted'         => array( 'success', __( 'Card and its history deleted.', 'treats' ) ),
	);

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are internal.
	$total_members = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$members_table}" );
	$total_stamps  = (int) $wpdb->get_var( "SELECT COALESCE(SUM(lifetime),0) FROM {$members_table}" );
	$total_given   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$rewards_table} WHERE redeemed_at IS NOT NULL" );
	$this_week     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(qty),0) FROM {$log_table} WHERE type = 'stamp' AND undone = 0 AND created_at >= %s", gmdate( 'Y-m-d H:i:s', time() - WEEK_IN_SECONDS ) ) );
	// phpcs:enable

	$base = admin_url( 'admin.php?page=treats-loyalty' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Loyalty card', 'treats' ); ?></h1>

		<?php if ( isset( $notices[ $notice ] ) ) : ?>
			<div class="notice notice-<?php echo esc_attr( $notices[ $notice ][0] ); ?> is-dismissible"><p><?php echo esc_html( $notices[ $notice ][1] ); ?></p></div>
		<?php endif; ?>

		<p>
			<?php
			printf(
				/* translators: 1: card page link, 2: till page link. */
				esc_html__( 'Customers join at %1$s. Staff stamp cards at %2$s.', 'treats' ),
				'<a href="' . esc_url( treats_loyalty_card_url() ) . '" target="_blank" rel="noopener">' . esc_html( treats_loyalty_card_url() ) . '</a>',
				'<a href="' . esc_url( (string) treats_get_template_page_url( 'page-templates/template-loyalty-till.php' ) ) . '" target="_blank" rel="noopener">' . esc_html( (string) treats_get_template_page_url( 'page-templates/template-loyalty-till.php' ) ) . '</a>'
			);
			?>
		</p>

		<ul class="subsubsub" style="float:none;margin-bottom:12px">
			<li><strong><?php echo esc_html( number_format_i18n( $total_members ) ); ?></strong> <?php esc_html_e( 'members', 'treats' ); ?> |</li>
			<li><strong><?php echo esc_html( number_format_i18n( $this_week ) ); ?></strong> <?php esc_html_e( 'stamps in the last 7 days', 'treats' ); ?> |</li>
			<li><strong><?php echo esc_html( number_format_i18n( $total_stamps ) ); ?></strong> <?php esc_html_e( 'stamps ever', 'treats' ); ?> |</li>
			<li><strong><?php echo esc_html( number_format_i18n( $total_given ) ); ?></strong> <?php esc_html_e( 'rewards given', 'treats' ); ?></li>
		</ul>

		<nav class="nav-tab-wrapper">
			<a class="nav-tab <?php echo 'members' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $base ); ?>"><?php esc_html_e( 'Members', 'treats' ); ?></a>
			<a class="nav-tab <?php echo 'staff' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', 'staff', $base ) ); ?>"><?php esc_html_e( 'Staff codes', 'treats' ); ?></a>
			<a class="nav-tab <?php echo 'activity' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', 'activity', $base ) ); ?>"><?php esc_html_e( 'Activity', 'treats' ); ?></a>
		</nav>

		<?php
		if ( 'staff' === $tab ) {
			treats_loyalty_admin_staff_tab();
		} elseif ( 'activity' === $tab ) {
			treats_loyalty_admin_activity_tab();
		} else {
			treats_loyalty_admin_members_tab( $search );
		}
		?>
	</div>
	<?php
}

/**
 * Members tab.
 *
 * @param string $search Search term.
 * @return void
 */
function treats_loyalty_admin_members_tab( $search ) {
	global $wpdb;

	$members_table = treats_loyalty_table( 'members' );
	$rewards_table = treats_loyalty_table( 'rewards' );

	if ( '' !== $search ) {
		$like = '%' . $wpdb->esc_like( $search ) . '%';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT m.*, (SELECT COUNT(*) FROM {$rewards_table} r WHERE r.member_id = m.id AND r.redeemed_at IS NULL) AS waiting FROM {$members_table} m WHERE m.name LIKE %s OR m.email LIKE %s OR m.card_code LIKE %s ORDER BY m.created_at DESC LIMIT 200", $like, $like, $like ) );
	} else {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
		$rows = $wpdb->get_results( "SELECT m.*, (SELECT COUNT(*) FROM {$rewards_table} r WHERE r.member_id = m.id AND r.redeemed_at IS NULL) AS waiting FROM {$members_table} m ORDER BY COALESCE(m.last_stamp_at, m.created_at) DESC LIMIT 200" );
	}
	?>
	<form method="get" style="margin:16px 0;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
		<input type="hidden" name="page" value="treats-loyalty">
		<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Name, email or card code', 'treats' ); ?>">
		<button class="button"><?php esc_html_e( 'Search', 'treats' ); ?></button>
		<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=treats-loyalty&treats_loyalty_export=1' ), 'treats_loyalty_export' ) ); ?>"><?php esc_html_e( 'Download newsletter sign-ups (CSV)', 'treats' ); ?></a>
	</form>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'treats' ); ?></th>
				<th><?php esc_html_e( 'Email', 'treats' ); ?></th>
				<th><?php esc_html_e( 'Card', 'treats' ); ?></th>
				<th><?php esc_html_e( 'This card', 'treats' ); ?></th>
				<th><?php esc_html_e( 'Rewards waiting', 'treats' ); ?></th>
				<th><?php esc_html_e( 'All-time stamps', 'treats' ); ?></th>
				<th><?php esc_html_e( 'News', 'treats' ); ?></th>
				<th><?php esc_html_e( 'Last stamp', 'treats' ); ?></th>
				<th><?php esc_html_e( 'Correct / delete', 'treats' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $rows ) : ?>
				<tr><td colspan="9"><?php esc_html_e( 'No members yet.', 'treats' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( $rows as $row ) : ?>
				<tr>
					<td><?php echo esc_html( $row->name ); ?></td>
					<td><?php echo esc_html( $row->email ); ?></td>
					<td><code><?php echo esc_html( $row->card_code ); ?></code></td>
					<td><?php echo esc_html( $row->stamps . ' / ' . TREATS_LOYALTY_CYCLE ); ?></td>
					<td><?php echo esc_html( (string) $row->waiting ); ?></td>
					<td><?php echo esc_html( (string) $row->lifetime ); ?></td>
					<td><?php echo $row->marketing ? esc_html__( 'Yes', 'treats' ) : '—'; ?></td>
					<td><?php echo $row->last_stamp_at ? esc_html( get_date_from_gmt( $row->last_stamp_at, 'j M Y, H:i' ) ) : '—'; ?></td>
					<td>
						<form method="post" style="display:inline-flex;gap:4px;align-items:center">
							<?php wp_nonce_field( 'treats_loyalty_admin' ); ?>
							<input type="hidden" name="treats_loyalty_admin" value="adjust">
							<input type="hidden" name="member_id" value="<?php echo esc_attr( (string) $row->id ); ?>">
							<input type="number" name="stamps" min="0" max="<?php echo esc_attr( (string) ( TREATS_LOYALTY_CYCLE - 1 ) ); ?>" value="<?php echo esc_attr( (string) $row->stamps ); ?>" style="width:60px" aria-label="<?php esc_attr_e( 'Stamps on this card', 'treats' ); ?>">
							<button class="button button-small"><?php esc_html_e( 'Set', 'treats' ); ?></button>
						</form>
						<form method="post" style="display:inline" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this card and all its history? This cannot be undone.', 'treats' ) ); ?>');">
							<?php wp_nonce_field( 'treats_loyalty_admin' ); ?>
							<input type="hidden" name="treats_loyalty_admin" value="delete_member">
							<input type="hidden" name="member_id" value="<?php echo esc_attr( (string) $row->id ); ?>">
							<button class="button button-small button-link-delete"><?php esc_html_e( 'Delete', 'treats' ); ?></button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Staff codes tab.
 *
 * @return void
 */
function treats_loyalty_admin_staff_tab() {
	$staff = treats_loyalty_staff();
	?>
	<p style="max-width:640px"><?php esc_html_e( 'Each person gets their own code, so every stamp and reward in the activity log has a name against it. Codes are stored scrambled and cannot be read back — if someone forgets theirs, remove them and add them again with a new one.', 'treats' ); ?></p>

	<table class="widefat striped" style="max-width:640px">
		<thead><tr><th><?php esc_html_e( 'Name', 'treats' ); ?></th><th></th></tr></thead>
		<tbody>
			<?php if ( ! $staff ) : ?>
				<tr><td colspan="2"><?php esc_html_e( 'No staff yet. Add someone below so the till can be used.', 'treats' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( $staff as $id => $person ) : ?>
				<tr>
					<td><?php echo esc_html( $person['name'] ); ?></td>
					<td style="text-align:right">
						<form method="post">
							<?php wp_nonce_field( 'treats_loyalty_admin' ); ?>
							<input type="hidden" name="treats_loyalty_admin" value="remove_staff">
							<input type="hidden" name="staff_id" value="<?php echo esc_attr( (string) $id ); ?>">
							<button class="button button-small button-link-delete"><?php esc_html_e( 'Remove', 'treats' ); ?></button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<h2><?php esc_html_e( 'Add a staff member', 'treats' ); ?></h2>
	<form method="post" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
		<?php wp_nonce_field( 'treats_loyalty_admin' ); ?>
		<input type="hidden" name="treats_loyalty_admin" value="add_staff">
		<input type="text" name="staff_name" placeholder="<?php esc_attr_e( 'Name', 'treats' ); ?>" required>
		<input type="password" name="staff_code" placeholder="<?php esc_attr_e( 'Code (4–8 digits)', 'treats' ); ?>" inputmode="numeric" pattern="\d{4,8}" autocomplete="new-password" required>
		<button class="button button-primary"><?php esc_html_e( 'Add', 'treats' ); ?></button>
	</form>
	<?php
}

/**
 * Activity tab.
 *
 * @return void
 */
function treats_loyalty_admin_activity_tab() {
	global $wpdb;

	$log_table     = treats_loyalty_table( 'log' );
	$members_table = treats_loyalty_table( 'members' );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are internal.
	$rows = $wpdb->get_results( "SELECT l.*, m.name, m.card_code FROM {$log_table} l LEFT JOIN {$members_table} m ON m.id = l.member_id ORDER BY l.id DESC LIMIT 300" );

	$types = array(
		'join'   => __( 'Joined', 'treats' ),
		'stamp'  => __( 'Stamped', 'treats' ),
		'redeem' => __( 'Reward given', 'treats' ),
		'undo'   => __( 'Stamp undone', 'treats' ),
		'adjust' => __( 'Corrected by manager', 'treats' ),
	);
	?>
	<table class="widefat striped" style="margin-top:16px">
		<thead>
			<tr>
				<th><?php esc_html_e( 'When', 'treats' ); ?></th>
				<th><?php esc_html_e( 'Customer', 'treats' ); ?></th>
				<th><?php esc_html_e( 'What', 'treats' ); ?></th>
				<th><?php esc_html_e( 'Staff', 'treats' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $rows ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'Nothing yet.', 'treats' ); ?></td></tr>
			<?php endif; ?>
			<?php
			foreach ( $rows as $row ) :
				$detail = json_decode( (string) $row->detail, true );
				$what   = $types[ $row->type ] ?? $row->type;

				if ( 'stamp' === $row->type ) {
					/* translators: %d: stamps. */
					$what = sprintf( _n( '%d stamp', '%d stamps', (int) $row->qty, 'treats' ), (int) $row->qty ) . ( $row->undone ? ' ' . __( '(undone)', 'treats' ) : '' );
				} elseif ( 'redeem' === $row->type ) {
					$what = treats_loyalty_reward_label( (string) ( $detail['kind'] ?? '' ) ) . ' ' . __( 'given', 'treats' );
				} elseif ( 'adjust' === $row->type ) {
					/* translators: 1: old count, 2: new count. */
					$what = sprintf( __( 'Stamps corrected from %1$d to %2$d', 'treats' ), (int) ( $detail['from'] ?? 0 ), (int) ( $detail['to'] ?? 0 ) );
				}
				?>
				<tr>
					<td><?php echo esc_html( get_date_from_gmt( $row->created_at, 'D j M Y, H:i' ) ); ?></td>
					<td><?php echo esc_html( $row->name ? $row->name . ' (' . $row->card_code . ')' : __( 'Deleted card', 'treats' ) ); ?></td>
					<td><?php echo esc_html( $what ); ?></td>
					<td><?php echo esc_html( $row->staff ? $row->staff : '—' ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}
