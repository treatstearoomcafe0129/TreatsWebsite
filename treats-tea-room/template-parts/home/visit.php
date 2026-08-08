<?php
/**
 * Visit us: address, hours and map.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$address = treats_get_address();
$hours   = treats_get_opening_hours();
$today   = (int) current_datetime()->format( 'N' ) - 1;
$note    = (string) get_theme_mod( 'treats_hours_note', '' );
?>
<section class="section section--surface" id="visit">
	<div class="container container--wide">
		<div class="split split--wide-right" style="align-items:start">
			<div<?php treats_reveal(); ?>>
				<p class="eyebrow"><?php esc_html_e( 'Visit us', 'treats' ); ?></p>
				<h2 style="margin-bottom:1.5rem"><?php esc_html_e( 'At the foot of Silver Street', 'treats' ); ?></h2>

				<p class="lede" style="margin-bottom:2.5rem">
					<?php echo esc_html( get_theme_mod( 'treats_travel_note', __( 'A two-minute walk from Durham Market Place, beside Framwellgate Bridge.', 'treats' ) ) ); ?>
				</p>

				<ul class="contact-list" style="margin-bottom:2.5rem">
					<li class="contact-list__item">
						<?php treats_icon( 'pin', array( 'size' => 20 ) ); ?>
						<span>
							<span class="contact-list__label"><?php esc_html_e( 'Address', 'treats' ); ?></span>
							<?php echo esc_html( $address['street'] ); ?>,
							<?php echo esc_html( $address['locality'] ); ?>
							<?php echo esc_html( $address['postcode'] ); ?>
						</span>
					</li>

					<?php if ( treats_get_phone() ) : ?>
						<li class="contact-list__item">
							<?php treats_icon( 'phone', array( 'size' => 20 ) ); ?>
							<span>
								<span class="contact-list__label"><?php esc_html_e( 'Telephone', 'treats' ); ?></span>
								<a href="tel:<?php echo esc_attr( treats_get_phone_link() ); ?>"><?php echo esc_html( treats_get_phone() ); ?></a>
							</span>
						</li>
					<?php endif; ?>

					<?php if ( treats_get_email() ) : ?>
						<li class="contact-list__item">
							<?php treats_icon( 'mail', array( 'size' => 20 ) ); ?>
							<span>
								<span class="contact-list__label"><?php esc_html_e( 'Email', 'treats' ); ?></span>
								<a href="mailto:<?php echo esc_attr( treats_get_email() ); ?>"><?php echo esc_html( treats_get_email() ); ?></a>
							</span>
						</li>
					<?php endif; ?>
				</ul>

				<h3 style="font-size:var(--fs-lg);margin-bottom:1rem"><?php esc_html_e( 'Opening hours', 'treats' ); ?></h3>

				<table class="hours-table">
					<caption class="screen-reader-text"><?php esc_html_e( 'Opening hours by day', 'treats' ); ?></caption>
					<tbody>
						<?php foreach ( $hours as $index => $day ) : ?>
							<tr class="<?php echo (int) $index === $today ? 'is-today' : ''; ?>">
								<th scope="row"><?php echo esc_html( $day['label'] ); ?></th>
								<td>
									<?php echo esc_html( treats_format_hours( $day ) ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( '' !== $note ) : ?>
					<p class="text-mute" style="font-size:var(--fs-xs);margin-top:1rem"><?php echo esc_html( $note ); ?></p>
				<?php endif; ?>
			</div>

			<div<?php treats_reveal( 80, 'scale' ); ?>>
				<?php get_template_part( 'template-parts/components/map', null, array( 'tall' => true ) ); ?>

				<div class="actions" style="margin-top:1.5rem">
					<a class="btn btn--secondary btn--sm" href="<?php echo esc_url( treats_map_directions_url() ); ?>" target="_blank" rel="noopener">
						<?php treats_icon( 'pin', array( 'size' => 16 ) ); ?>
						<?php esc_html_e( 'Get directions', 'treats' ); ?>
					</a>
					<a class="btn btn--primary btn--sm" href="<?php echo esc_url( treats_booking_url() ); ?>">
						<?php esc_html_e( 'Book a table', 'treats' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>
</section>
