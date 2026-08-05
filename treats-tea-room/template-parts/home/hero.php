<?php
/**
 * Home page hero.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$hero_image = (string) get_theme_mod( 'treats_hero_image', '' );
$cta_url    = (string) get_theme_mod( 'treats_hero_cta_url', '' );
$alt_url    = (string) get_theme_mod( 'treats_hero_alt_url', '' );
$is_open    = treats_is_open_now();
$hours      = treats_get_opening_hours();
$today      = (int) current_datetime()->format( 'N' ) - 1;

if ( '' === $cta_url ) {
	$cta_url = treats_booking_url();
}

if ( '' === $alt_url ) {
	$alt_url = treats_get_template_page_url( 'page-templates/template-menu.php' );
}
?>
<section class="hero">
	<div class="hero__bg">
		<?php if ( '' !== $hero_image ) : ?>
			<?php
			$hero_id = attachment_url_to_postid( $hero_image );

			if ( $hero_id ) {
				echo wp_get_attachment_image(
					$hero_id,
					'treats-hero',
					false,
					array(
						'alt'           => '',
						'fetchpriority' => 'high',
						'loading'       => 'eager',
						'decoding'      => 'async',
						'sizes'         => '100vw',
					)
				);
			} else {
				printf(
					'<img src="%s" alt="" fetchpriority="high" decoding="async">',
					esc_url( $hero_image )
				);
			}
			?>
		<?php endif; ?>
	</div>
	<div class="hero__scrim"></div>

	<div class="container">
		<div class="hero__inner">
			<?php $eyebrow = get_theme_mod( 'treats_hero_eyebrow', __( 'Durham · Since 1991', 'treats' ) ); ?>

			<?php if ( $eyebrow ) : ?>
				<p class="eyebrow hero__eyebrow reveal reveal--fade is-visible"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>

			<h1 class="hero__title">
				<?php echo esc_html( get_theme_mod( 'treats_hero_title', __( 'A proper tea room in the heart of Durham', 'treats' ) ) ); ?>
			</h1>

			<p class="hero__intro">
				<?php echo esc_html( get_theme_mod( 'treats_hero_intro', __( 'Freshly baked cakes, generous breakfasts and traditional afternoon tea, served on Silver Street since 1991.', 'treats' ) ) ); ?>
			</p>

			<div class="hero__actions">
				<a class="btn btn--light btn--lg" href="<?php echo esc_url( $cta_url ); ?>">
					<?php echo esc_html( get_theme_mod( 'treats_hero_cta_label', __( 'Book a table', 'treats' ) ) ); ?>
				</a>

				<a class="btn btn--outline-light btn--lg" href="<?php echo esc_url( $alt_url ); ?>">
					<?php echo esc_html( get_theme_mod( 'treats_hero_alt_label', __( 'View our menus', 'treats' ) ) ); ?>
					<?php treats_icon( 'arrow-right', array( 'size' => 17 ) ); ?>
				</a>
			</div>

			<p class="hero__status<?php echo $is_open ? '' : ' hero__status--closed'; ?>">
				<span class="hero__status-dot" aria-hidden="true"></span>
				<?php if ( $is_open && isset( $hours[ $today ] ) ) : ?>
					<?php
					printf(
						/* translators: %s: closing time. */
						esc_html__( 'Open now — until %s today', 'treats' ),
						esc_html( $hours[ $today ]['close'] )
					);
					?>
				<?php elseif ( isset( $hours[ $today ] ) && ! $hours[ $today ]['closed'] ) : ?>
					<?php
					printf(
						/* translators: 1: opening time, 2: closing time. */
						esc_html__( 'Closed now — today we are open %1$s to %2$s', 'treats' ),
						esc_html( $hours[ $today ]['open'] ),
						esc_html( $hours[ $today ]['close'] )
					);
					?>
				<?php else : ?>
					<?php esc_html_e( 'Closed today — see our opening hours', 'treats' ); ?>
				<?php endif; ?>
			</p>
		</div>
	</div>

	<a class="hero__scroll" href="#welcome">
		<span><?php esc_html_e( 'Scroll', 'treats' ); ?></span>
		<span class="hero__scroll-line" aria-hidden="true"></span>
	</a>
</section>
