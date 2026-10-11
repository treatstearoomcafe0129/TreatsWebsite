<?php
/**
 * Template Name: Contact
 * Template Post Type: page
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

$address  = treats_get_address();
$hours    = treats_get_opening_hours();
$today    = (int) current_datetime()->format( 'N' ) - 1;
$subjects = array(
	__( 'General enquiry', 'treats' ),
	__( 'Booking a large group', 'treats' ),
	__( 'Celebration cakes', 'treats' ),
	__( 'Allergies and dietary needs', 'treats' ),
	__( 'Gift vouchers', 'treats' ),
	__( 'Feedback', 'treats' ),
	__( 'Something else', 'treats' ),
);

while ( have_posts() ) :
	the_post();

	treats_page_hero(
		array(
			'eyebrow'  => (string) treats_meta( 'eyebrow', 0, __( 'Find us', 'treats' ) ),
			'title'    => get_the_title(),
			'intro'    => (string) treats_meta( 'intro' ),
			'image_id' => (int) get_post_thumbnail_id(),
		)
	);
	?>

	<section class="section">
		<div class="container container--wide">
			<div class="split split--wide-left" style="align-items:start">
				<div<?php treats_reveal(); ?>>
					<div class="form-card">
						<header class="form-card__header">
							<h2 class="form-card__title"><?php esc_html_e( 'Send us a message', 'treats' ); ?></h2>
							<p class="text-mute" style="font-size:var(--fs-sm)">
								<?php esc_html_e( 'We read everything and usually reply within a day. For a table today, please call — it is quicker.', 'treats' ); ?>
							</p>
						</header>

						<?php treats_form_notice(); ?>

						<form class="form" method="post" action="<?php echo esc_url( treats_form_action() ); ?>" data-treats-form="enquiry">
							<div data-form-response hidden></div>

							<div class="form__grid form__grid--2">
								<div class="field">
									<label class="field__label" for="contact-name"><?php esc_html_e( 'Your name', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
									<input class="input" type="text" id="contact-name" name="treats_name" autocomplete="name" required>
									<span class="field__error"></span>
								</div>

								<div class="field">
									<label class="field__label" for="contact-email"><?php esc_html_e( 'Email', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
									<input class="input" type="email" id="contact-email" name="treats_email" autocomplete="email" required>
									<span class="field__error"></span>
								</div>

								<div class="field">
									<label class="field__label" for="contact-phone"><?php esc_html_e( 'Phone', 'treats' ); ?></label>
									<input class="input" type="tel" id="contact-phone" name="treats_phone" autocomplete="tel">
									<span class="field__error"></span>
								</div>

								<div class="field">
									<label class="field__label" for="contact-subject"><?php esc_html_e( 'What is it about?', 'treats' ); ?></label>
									<select class="input" id="contact-subject" name="treats_subject">
										<?php foreach ( $subjects as $subject ) : ?>
											<option value="<?php echo esc_attr( $subject ); ?>"><?php echo esc_html( $subject ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>

							<div class="field">
								<label class="field__label" for="contact-message"><?php esc_html_e( 'Message', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
								<textarea class="input" id="contact-message" name="treats_message" rows="6" required></textarea>
								<span class="field__error"></span>
							</div>

							<label class="check">
								<input type="checkbox" name="treats_marketing" value="1">
								<span><?php esc_html_e( 'Email me occasionally about seasonal menus and events.', 'treats' ); ?></span>
							</label>

							<?php treats_form_fields( 'enquiry' ); ?>

							<div class="form__footer">
								<button class="btn btn--primary btn--lg" type="submit"><?php esc_html_e( 'Send message', 'treats' ); ?></button>
								<?php if ( get_privacy_policy_url() ) : ?>
									<p class="form__note">
										<?php
										printf(
											/* translators: %s: privacy policy link. */
											esc_html__( 'We use your details only to reply. See our %s.', 'treats' ),
											'<a href="' . esc_url( get_privacy_policy_url() ) . '">' . esc_html__( 'privacy policy', 'treats' ) . '</a>'
										);
										?>
									</p>
								<?php endif; ?>
							</div>
						</form>
					</div>
				</div>

				<aside class="booking-aside"<?php treats_reveal( 100 ); ?>>
					<div class="info-card">
						<h2 class="info-card__title"><?php esc_html_e( 'The tea room', 'treats' ); ?></h2>
						<ul class="contact-list">
							<li class="contact-list__item">
								<?php treats_icon( 'pin', array( 'size' => 20 ) ); ?>
								<span>
									<span class="contact-list__label"><?php esc_html_e( 'Address', 'treats' ); ?></span>
									<?php echo esc_html( $address['street'] ); ?><br>
									<?php echo esc_html( $address['locality'] ); ?> <?php echo esc_html( $address['postcode'] ); ?>
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
					</div>

					<div class="info-card">
						<h2 class="info-card__title"><?php esc_html_e( 'Opening hours', 'treats' ); ?></h2>
						<table class="hours-table">
							<tbody>
								<?php foreach ( $hours as $index => $day ) : ?>
									<tr class="<?php echo (int) $index === $today ? 'is-today' : ''; ?>">
										<th scope="row"><?php echo esc_html( $day['label'] ); ?></th>
										<td>
											<?php
											echo esc_html( treats_format_hours( $day ) );
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<?php if ( treats_get_social_links() ) : ?>
						<div class="info-card">
							<h2 class="info-card__title"><?php esc_html_e( 'Follow us', 'treats' ); ?></h2>
							<ul class="social-links" style="--c-line:var(--c-line-strong)">
								<?php foreach ( treats_get_social_links() as $key => $link ) : ?>
									<li>
										<a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener me" aria-label="<?php echo esc_attr( $link['label'] ); ?>" style="border-color:var(--c-line-strong);color:var(--c-ink)">
											<?php treats_icon( $key, array( 'size' => 18 ) ); ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				</aside>
			</div>

			<?php
			$treats_content = trim( get_the_content() );

			if ( '' !== $treats_content ) {
				?>
				<div class="entry-content" style="max-width:var(--w-content);margin:4rem auto 0">
					<?php the_content(); ?>
				</div>
				<?php
			}
			?>
		</div>
	</section>

	<section class="section section--sm" style="padding-top:0">
		<div class="container container--wide">
			<div<?php treats_reveal( 0, 'scale' ); ?>>
				<?php get_template_part( 'template-parts/components/map', null, array( 'wide' => true ) ); ?>
			</div>

			<div class="actions actions--center" style="margin-top:1.5rem">
				<a class="btn btn--secondary btn--sm" href="<?php echo esc_url( treats_map_directions_url() ); ?>" target="_blank" rel="noopener">
					<?php treats_icon( 'pin', array( 'size' => 16 ) ); ?>
					<?php esc_html_e( 'Get directions', 'treats' ); ?>
				</a>
			</div>

			<?php if ( get_theme_mod( 'treats_travel_note' ) ) : ?>
				<p class="text-center text-mute" style="max-width:52ch;margin:1.5rem auto 0;font-size:var(--fs-sm)">
					<?php echo esc_html( get_theme_mod( 'treats_travel_note' ) ); ?>
				</p>
			<?php endif; ?>
		</div>
	</section>

	<?php
endwhile;

get_footer();
