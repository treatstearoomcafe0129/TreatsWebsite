<?php
/**
 * Document head and site header.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$treats_default_scheme = treats_color_scheme();
$treats_over_hero      = is_front_page() && ! is_paged();

/*
 * "Always light" and "Always dark" mean exactly that. The scheme is stamped
 * on the html element here, on the server, which settles it before a single
 * byte of CSS is parsed: the device preference cannot win, a visitor's stored
 * choice from an earlier visit cannot win, and it holds with JavaScript
 * turned off. Only "follow the visitor's device" leaves the decision to the
 * little script below.
 */
$treats_locked_scheme = in_array( $treats_default_scheme, array( 'light', 'dark' ), true )
	? $treats_default_scheme
	: '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js"<?php echo $treats_locked_scheme ? ' data-theme="' . esc_attr( $treats_locked_scheme ) . '"' : ''; ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php if ( ! $treats_locked_scheme ) : ?>
	<script>
		/* Applies the visitor's stored choice before first paint, so there is
		   no flash of the wrong theme. Only runs when the site is set to
		   follow the device — otherwise the attribute is already on <html>.
		   Deliberately inline and tiny. */
		(function () {
			var stored = null;

			try {
				stored = window.localStorage.getItem('treats-theme');
			} catch (e) {}

			if (stored === 'dark' || stored === 'light') {
				document.documentElement.setAttribute('data-theme', stored);
			}
		})();
	</script>
	<?php endif; ?>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'treats' ); ?></a>

<header class="site-header<?php echo $treats_over_hero ? ' site-header--over' : ''; ?>" id="site-header">
	<div class="container site-header__inner">
		<?php treats_the_logo(); ?>

		<nav class="primary-nav" aria-label="<?php esc_attr_e( 'Primary', 'treats' ); ?>">
			<?php treats_nav_menu( 'primary' ); ?>
		</nav>

		<div class="header-actions">
			<a class="btn btn--primary btn--sm header-action--cta" href="<?php echo esc_url( treats_booking_url() ); ?>">
				<?php treats_icon( 'calendar', array( 'size' => 17 ) ); ?>
				<?php esc_html_e( 'Book a table', 'treats' ); ?>
			</a>

			<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="mobile-nav" aria-label="<?php esc_attr_e( 'Open menu', 'treats' ); ?>">
				<span class="nav-toggle__bars" aria-hidden="true">
					<span class="nav-toggle__bar"></span>
					<span class="nav-toggle__bar"></span>
					<span class="nav-toggle__bar"></span>
				</span>
			</button>
		</div>
	</div>
</header>

<div class="mobile-nav" id="mobile-nav">
	<nav aria-label="<?php esc_attr_e( 'Mobile', 'treats' ); ?>">
		<?php treats_nav_menu( 'primary' ); ?>
	</nav>

	<div class="mobile-nav__meta">
		<?php if ( treats_get_phone() ) : ?>
			<a href="tel:<?php echo esc_attr( treats_get_phone_link() ); ?>">
				<?php treats_icon( 'phone', array( 'size' => 18 ) ); ?>
				<?php echo esc_html( treats_get_phone() ); ?>
			</a>
		<?php endif; ?>

		<a href="<?php echo esc_url( treats_map_directions_url() ); ?>" target="_blank" rel="noopener">
			<?php treats_icon( 'pin', array( 'size' => 18 ) ); ?>
			<?php echo esc_html( treats_get_address()['street'] . ', ' . treats_get_address()['locality'] ); ?>
		</a>

		<span>
			<?php treats_icon( 'clock', array( 'size' => 18 ) ); ?>
			<?php echo esc_html( treats_is_open_now() ? __( 'Open now', 'treats' ) : __( 'Closed right now', 'treats' ) ); ?>
		</span>
	</div>
</div>

<main id="main" class="site-main">
