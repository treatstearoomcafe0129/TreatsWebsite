<?php
/**
 * Home page opening: a leaf ornament and the primary booking call to action,
 * sitting on the sage ground above the glass panels.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$cta_url = (string) get_theme_mod( 'treats_hero_cta_url', '' );

if ( '' === $cta_url ) {
	$cta_url = treats_booking_url();
}

$title = (string) get_theme_mod( 'treats_hero_title', '' );
$intro = (string) get_theme_mod( 'treats_hero_intro', '' );
?>
<section class="hero-glass">
	<div class="container container--wide">
		<p class="ornament" aria-hidden="true"><?php treats_icon( 'leaf', array( 'size' => 34 ) ); ?></p>

		<?php if ( '' !== $title ) : ?>
			<h1 class="hero-glass__title"><?php echo esc_html( $title ); ?></h1>
		<?php else : ?>
			<h1 class="screen-reader-text"><?php echo esc_html( treats_get_business_name() ); ?></h1>
		<?php endif; ?>

		<?php if ( '' !== $intro ) : ?>
			<p class="hero-glass__intro"><?php echo esc_html( $intro ); ?></p>
		<?php endif; ?>

		<p class="hero-glass__cta">
			<a class="btn btn--primary btn--feature" href="<?php echo esc_url( $cta_url ); ?>">
				<?php echo esc_html( get_theme_mod( 'treats_hero_cta_label', __( 'Book your table', 'treats' ) ) ); ?>
				<?php treats_icon( 'calendar', array( 'size' => 20 ) ); ?>
			</a>
		</p>
	</div>
</section>
