<?php
/**
 * Template Name: Book a Table
 * Template Post Type: page
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

$embed      = (string) get_theme_mod( 'treats_booking_embed', '' );
$slots      = treats_booking_slots();
$max_party  = (int) get_theme_mod( 'treats_booking_max_party', 12 );
$hours      = treats_get_opening_hours();
$today      = (int) current_datetime()->format( 'N' ) - 1;
$occasions  = array(
	__( 'No special occasion', 'treats' ),
	__( 'Birthday', 'treats' ),
	__( 'Anniversary', 'treats' ),
	__( 'Afternoon tea', 'treats' ),
	__( 'Graduation', 'treats' ),
	__( 'Business', 'treats' ),
);

while ( have_posts() ) :
	the_post();

	treats_page_hero(
		array(
			'eyebrow'  => (string) treats_meta( 'eyebrow', 0, __( 'Reservations', 'treats' ) ),
			'title'    => get_the_title(),
			'intro'    => (string) treats_meta( 'intro', 0, __( 'Tell us when you would like to come and we will confirm your table by email or phone.', 'treats' ) ),
			'image_id' => (int) get_post_thumbnail_id(),
		)
	);
	?>

	<section class="section">
		<div class="container container--wide">
			<div class="split split--wide-left" style="align-items:start">
				<div<?php treats_reveal(); ?>>
					<?php if ( '' !== trim( $embed ) ) : ?>
						<div class="form-card">
							<?php echo treats_sanitize_embed( $embed ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized on save and again here. ?>
						</div>
					<?php else : ?>
						<div class="form-card">
							<header class="form-card__header">
								<h2 class="form-card__title"><?php esc_html_e( 'Request a table', 'treats' ); ?></h2>
								<p class="text-mute" style="font-size:var(--fs-sm)">
									<?php esc_html_e( 'Every field marked with an asterisk is needed so we can confirm your booking.', 'treats' ); ?>
								</p>
							</header>

							<?php treats_form_notice(); ?>

							<form class="form" method="post" action="<?php echo esc_url( treats_form_action() ); ?>" data-treats-form="booking">
								<div data-form-response hidden></div>

								<div class="form__grid form__grid--2">
									<div class="field">
										<label class="field__label" for="booking-name"><?php esc_html_e( 'Your name', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
										<input class="input" type="text" id="booking-name" name="treats_name" autocomplete="name" required>
										<span class="field__error"></span>
									</div>

									<div class="field">
										<label class="field__label" for="booking-phone"><?php esc_html_e( 'Phone', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
										<input class="input" type="tel" id="booking-phone" name="treats_phone" autocomplete="tel" required>
										<span class="field__error"></span>
									</div>

									<div class="field field--full">
										<label class="field__label" for="booking-email"><?php esc_html_e( 'Email', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
										<input class="input" type="email" id="booking-email" name="treats_email" autocomplete="email" required>
										<span class="field__error"></span>
									</div>
								</div>

								<div class="field">
									<span class="field__label"><?php esc_html_e( 'How many of you?', 'treats' ); ?><span class="required" aria-hidden="true">*</span></span>
									<div class="choice-grid" data-required-group>
										<?php for ( $treats_i = 1; $treats_i <= $max_party; $treats_i++ ) : ?>
											<span class="choice">
												<input type="radio" id="party-<?php echo esc_attr( (string) $treats_i ); ?>" name="treats_party" value="<?php echo esc_attr( (string) $treats_i ); ?>" <?php checked( 2, $treats_i ); ?>>
												<label class="choice__label" for="party-<?php echo esc_attr( (string) $treats_i ); ?>"><?php echo esc_html( (string) $treats_i ); ?></label>
											</span>
										<?php endfor; ?>
									</div>
									<span class="field__hint">
										<?php
										printf(
											/* translators: %s: phone number. */
											esc_html__( 'For parties larger than %1$d, please call us on %2$s.', 'treats' ),
											esc_html( (string) $max_party ),
											esc_html( treats_get_phone() )
										);
										?>
									</span>
								</div>

								<div class="field">
									<label class="field__label" for="booking-date"><?php esc_html_e( 'Date', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
									<input class="input" type="date" id="booking-date" name="treats_date" data-booking-date required>
									<span class="field__error"></span>
									<span class="field__hint" data-booking-notice hidden></span>
								</div>

								<div class="field">
									<span class="field__label"><?php esc_html_e( 'Time', 'treats' ); ?><span class="required" aria-hidden="true">*</span></span>
									<div class="choice-grid" data-booking-times data-required-group>
										<?php foreach ( $slots as $treats_slot ) : ?>
											<span class="choice">
												<input type="radio" id="slot-<?php echo esc_attr( str_replace( ':', '', $treats_slot ) ); ?>" name="treats_time" value="<?php echo esc_attr( $treats_slot ); ?>">
												<label class="choice__label" for="slot-<?php echo esc_attr( str_replace( ':', '', $treats_slot ) ); ?>"><?php echo esc_html( $treats_slot ); ?></label>
											</span>
										<?php endforeach; ?>
									</div>
								</div>

								<div class="form__grid form__grid--2">
									<div class="field">
										<label class="field__label" for="booking-occasion"><?php esc_html_e( 'Occasion', 'treats' ); ?></label>
										<select class="input" id="booking-occasion" name="treats_occasion">
											<?php foreach ( $occasions as $treats_occasion ) : ?>
												<option value="<?php echo esc_attr( $treats_occasion ); ?>"><?php echo esc_html( $treats_occasion ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>

								<div class="field">
									<label class="field__label" for="booking-notes"><?php esc_html_e( 'Anything we should know?', 'treats' ); ?></label>
									<textarea class="input" id="booking-notes" name="treats_notes" rows="3" placeholder="<?php esc_attr_e( 'Allergies, a pushchair, a table by the window…', 'treats' ); ?>"></textarea>
								</div>

								<label class="check">
									<input type="checkbox" name="treats_marketing" value="1">
									<span><?php esc_html_e( 'Email me occasionally about seasonal menus and events. No spam, unsubscribe any time.', 'treats' ); ?></span>
								</label>

								<?php treats_form_fields( 'booking' ); ?>

								<div class="form__footer">
									<button class="btn btn--primary btn--lg" type="submit"><?php esc_html_e( 'Request this table', 'treats' ); ?></button>
									<p class="form__note"><?php esc_html_e( 'This is a request, not an instant confirmation — we will come back to you to confirm.', 'treats' ); ?></p>
								</div>
							</form>
						</div>
					<?php endif; ?>

					<?php
					$treats_content = trim( get_the_content() );

					if ( '' !== $treats_content ) {
						?>
						<div class="entry-content" style="margin-top:3rem">
							<?php the_content(); ?>
						</div>
						<?php
					}
					?>
				</div>

				<aside class="booking-aside"<?php treats_reveal( 100 ); ?>>
					<div class="info-card">
						<h2 class="info-card__title"><?php esc_html_e( 'Good to know', 'treats' ); ?></h2>
						<p style="font-size:var(--fs-sm);color:var(--c-ink-soft)">
							<?php echo esc_html( get_theme_mod( 'treats_booking_note', __( 'Requests are confirmed by email or phone, usually within a couple of hours during opening times.', 'treats' ) ) ); ?>
						</p>
					</div>

					<div class="info-card">
						<h2 class="info-card__title"><?php esc_html_e( 'Opening hours', 'treats' ); ?></h2>
						<table class="hours-table">
							<tbody>
								<?php foreach ( $hours as $treats_index => $treats_day ) : ?>
									<tr class="<?php echo (int) $treats_index === $today ? 'is-today' : ''; ?>">
										<th scope="row"><?php echo esc_html( $treats_day['label'] ); ?></th>
										<td>
											<?php
											echo esc_html( treats_format_hours( $treats_day ) );
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<div class="info-card">
						<h2 class="info-card__title"><?php esc_html_e( 'Prefer to call?', 'treats' ); ?></h2>
						<ul class="contact-list">
							<?php if ( treats_get_phone() ) : ?>
								<li class="contact-list__item">
									<?php treats_icon( 'phone', array( 'size' => 18 ) ); ?>
									<a href="tel:<?php echo esc_attr( treats_get_phone_link() ); ?>"><?php echo esc_html( treats_get_phone() ); ?></a>
								</li>
							<?php endif; ?>
							<li class="contact-list__item">
								<?php treats_icon( 'pin', array( 'size' => 18 ) ); ?>
								<span><?php echo esc_html( treats_get_address_line() ); ?></span>
							</li>
						</ul>
					</div>
				</aside>
			</div>
		</div>
	</section>

	<?php
endwhile;

get_footer();
