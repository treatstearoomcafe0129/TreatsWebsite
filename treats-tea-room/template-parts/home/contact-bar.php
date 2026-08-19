<?php
/**
 * The three-up contact bar that closes the home page.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$address = treats_get_address();
$hours   = treats_get_opening_hours();

// Collapse the week into a single line when every open day is identical.
$open_days = array_filter(
	$hours,
	static function ( $day ) {
		return empty( $day['closed'] );
	}
);

$spans   = array_unique( array_map( static function ( $day ) {
	return $day['open'] . ' – ' . $day['close'];
}, $open_days ) );

$uniform = 7 === count( $open_days ) && 1 === count( $spans );
?>
<section class="section section--sm">
	<div class="container container--wide">
		<ul class="info-bar glass"<?php treats_reveal(); ?>>
			<li class="info-bar__item">
				<span class="icon-badge icon-badge--sm"><?php treats_icon( 'pin', array( 'size' => 22 ) ); ?></span>
				<div>
					<h2 class="info-bar__title"><?php esc_html_e( 'Find Us', 'treats' ); ?></h2>
					<p class="info-bar__text">
						<?php echo esc_html( $address['street'] ); ?><br>
						<?php echo esc_html( $address['locality'] ); ?>, <?php echo esc_html( $address['postcode'] ); ?>
					</p>
				</div>
			</li>

			<li class="info-bar__item">
				<span class="icon-badge icon-badge--sm"><?php treats_icon( 'clock', array( 'size' => 22 ) ); ?></span>
				<div>
					<h2 class="info-bar__title"><?php esc_html_e( 'Opening Hours', 'treats' ); ?></h2>
					<p class="info-bar__text">
						<?php if ( $uniform ) : ?>
							<?php
							printf(
								/* translators: 1: opening time, 2: closing time. */
								esc_html__( 'Mon – Sun  %1$s – %2$s', 'treats' ),
								esc_html( treats_format_time( reset( $open_days )['open'] ) ),
								esc_html( treats_format_time( reset( $open_days )['close'] ) )
							);
							?>
							<br><?php esc_html_e( 'Open 7 days a week', 'treats' ); ?>
						<?php else : ?>
							<?php foreach ( $hours as $day ) : ?>
								<?php echo esc_html( $day['label'] ); ?>
								<?php echo esc_html( treats_format_hours( $day ) ); ?><br>
							<?php endforeach; ?>
						<?php endif; ?>
					</p>
				</div>
			</li>

			<li class="info-bar__item">
				<span class="icon-badge icon-badge--sm"><?php treats_icon( 'phone', array( 'size' => 22 ) ); ?></span>
				<div>
					<h2 class="info-bar__title"><?php esc_html_e( 'Get In Touch', 'treats' ); ?></h2>
					<p class="info-bar__text">
						<?php if ( treats_get_phone() ) : ?>
							<a href="tel:<?php echo esc_attr( treats_get_phone_link() ); ?>"><?php echo esc_html( treats_get_phone() ); ?></a><br>
						<?php endif; ?>
						<?php if ( treats_get_email() ) : ?>
							<a href="mailto:<?php echo esc_attr( treats_get_email() ); ?>"><?php echo esc_html( treats_get_email() ); ?></a>
						<?php endif; ?>
					</p>
				</div>
			</li>
		</ul>
	</div>
</section>
