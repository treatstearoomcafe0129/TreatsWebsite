<?php
/**
 * Template Name: Loyalty Till
 * Template Post Type: page
 *
 * The staff screen for stamping cards. A page of its own, without the site's
 * navigation, built for a phone or tablet behind the counter: unlock with a
 * staff code, scan the customer's QR, tap to stamp.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$treats_staff = treats_loyalty_current_staff();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> data-theme="light">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="theme-color" content="#eaf2e3">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-title" content="<?php esc_attr_e( 'Treats Till', 'treats' ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'till-body' ); ?>>
<?php wp_body_open(); ?>

<div class="till" data-till data-staff="<?php echo esc_attr( $treats_staff ? $treats_staff['name'] : '' ); ?>">
	<header class="till__top">
		<span class="till__brand">Treats <span><?php esc_html_e( 'Loyalty till', 'treats' ); ?></span></span>
		<span class="till__who" data-till-who<?php echo $treats_staff ? '' : ' hidden'; ?>>
			<span data-till-name><?php echo esc_html( $treats_staff ? $treats_staff['name'] : '' ); ?></span>
			<button type="button" class="till__lock" data-till-lock><?php esc_html_e( 'Lock', 'treats' ); ?></button>
		</span>
	</header>

	<!-- Locked: staff code -->
	<section class="till-pin" data-till-view="locked"<?php echo $treats_staff ? ' hidden' : ''; ?>>
		<h1 class="till-pin__title"><?php esc_html_e( 'Enter your staff code', 'treats' ); ?></h1>
		<div class="till-pin__dots" data-pin-dots aria-hidden="true"></div>
		<p class="till-msg till-msg--error" data-pin-msg role="alert" hidden></p>
		<div class="till-pin__pad" role="group" aria-label="<?php esc_attr_e( 'Number pad', 'treats' ); ?>">
			<?php foreach ( array( '1', '2', '3', '4', '5', '6', '7', '8', '9' ) as $digit ) : ?>
				<button type="button" data-pin-key="<?php echo esc_attr( $digit ); ?>"><?php echo esc_html( $digit ); ?></button>
			<?php endforeach; ?>
			<button type="button" data-pin-clear aria-label="<?php esc_attr_e( 'Delete', 'treats' ); ?>">⌫</button>
			<button type="button" data-pin-key="0">0</button>
			<button type="button" data-pin-go class="till-pin__go"><?php esc_html_e( 'Go', 'treats' ); ?></button>
		</div>
	</section>

	<!-- Unlocked: find a card -->
	<section class="till-find" data-till-view="find"<?php echo $treats_staff ? '' : ' hidden'; ?>>
		<div class="till-scan" data-scan-box>
			<video class="till-scan__video" data-scan-video playsinline muted hidden></video>
			<div class="till-scan__frame" aria-hidden="true"></div>
			<button type="button" class="till-btn till-btn--gold till-btn--xl" data-scan-start><?php esc_html_e( 'Scan card', 'treats' ); ?></button>
			<button type="button" class="till-btn till-btn--ghost" data-scan-stop hidden><?php esc_html_e( 'Stop camera', 'treats' ); ?></button>
		</div>

		<form class="till-search" data-till-search>
			<label for="till-query"><?php esc_html_e( 'Or type the card code or their email', 'treats' ); ?></label>
			<div class="till-search__row">
				<input id="till-query" name="query" type="text" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="K7PM-4QXA">
				<button type="submit" class="till-btn"><?php esc_html_e( 'Find', 'treats' ); ?></button>
			</div>
		</form>
		<p class="till-msg till-msg--error" data-find-msg role="alert" hidden></p>
	</section>

	<!-- A card is open -->
	<section class="till-card" data-till-view="card" hidden>
		<div class="till-card__head">
			<div>
				<h2 class="till-card__name" data-card-name></h2>
				<p class="till-card__code" data-card-code></p>
			</div>
			<p class="till-card__count"><span data-card-stamps>0</span><small>/<?php echo esc_html( (string) TREATS_LOYALTY_CYCLE ); ?></small></p>
		</div>

		<ol class="loyalty-grid loyalty-grid--till" data-card-grid aria-hidden="true"></ol>
		<p class="till-card__next" data-card-next></p>

		<div class="till-toast" data-toast role="status" aria-live="assertive" hidden>
			<span data-toast-text></span>
			<button type="button" class="till-toast__undo" data-undo hidden><?php esc_html_e( 'Undo', 'treats' ); ?></button>
		</div>

		<div class="till-rewards" data-card-rewards hidden>
			<h3><?php esc_html_e( 'Rewards to give', 'treats' ); ?></h3>
			<div class="till-rewards__list" data-card-reward-list></div>
		</div>

		<div class="till-stamp">
			<p class="till-stamp__label"><?php esc_html_e( 'Hot drinks paid for in this order', 'treats' ); ?></p>
			<div class="till-stamp__qty">
				<button type="button" class="till-round" data-qty-minus aria-label="<?php esc_attr_e( 'One fewer', 'treats' ); ?>">−</button>
				<output data-qty>1</output>
				<button type="button" class="till-round" data-qty-plus aria-label="<?php esc_attr_e( 'One more', 'treats' ); ?>">+</button>
			</div>
			<button type="button" class="till-btn till-btn--gold till-btn--xl" data-stamp><?php esc_html_e( 'Add stamp', 'treats' ); ?></button>
		</div>

		<button type="button" class="till-btn till-btn--ghost till-btn--block" data-next><?php esc_html_e( 'Next customer', 'treats' ); ?></button>
	</section>
</div>

<?php wp_footer(); ?>
</body>
</html>
