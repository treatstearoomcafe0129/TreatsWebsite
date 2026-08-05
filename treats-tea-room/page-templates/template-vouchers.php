<?php
/**
 * Template Name: Gift Vouchers
 * Template Post Type: page
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

$amounts_raw = (string) get_theme_mod( 'treats_voucher_amounts', '10, 20, 25, 50, 75, 100' );
$amounts     = array_values(
	array_filter(
		array_map( 'trim', explode( ',', $amounts_raw ) ),
		static function ( $value ) {
			return is_numeric( $value );
		}
	)
);

$experiences = array(
	array(
		'title'    => __( 'Afternoon Tea for Two', 'treats' ),
		'amount'   => '49.90',
		'text'     => __( 'Our full three-tier afternoon tea for two, with a pot of loose leaf tea each.', 'treats' ),
		'featured' => true,
	),
	array(
		'title'    => __( 'Sparkling Afternoon Tea for Two', 'treats' ),
		'amount'   => '65.90',
		'text'     => __( 'The same, with a glass of English sparkling wine to start.', 'treats' ),
		'featured' => false,
	),
	array(
		'title'    => __( 'Breakfast for Two', 'treats' ),
		'amount'   => '30.00',
		'text'     => __( 'Two full breakfasts and two hot drinks, any morning of the week.', 'treats' ),
		'featured' => false,
	),
);

while ( have_posts() ) :
	the_post();

	treats_page_hero(
		array(
			'eyebrow'  => (string) treats_meta( 'eyebrow', 0, __( 'Gifts', 'treats' ) ),
			'title'    => get_the_title(),
			'intro'    => (string) treats_meta( 'intro', 0, get_theme_mod( 'treats_voucher_intro', '' ) ),
			'image_id' => (int) get_post_thumbnail_id(),
		)
	);
	?>

	<section class="section">
		<div class="container container--wide">
			<?php
			treats_section_heading(
				array(
					'eyebrow' => __( 'Experiences', 'treats' ),
					'title'   => __( 'Give an afternoon, not just an amount', 'treats' ),
					'intro'   => __( 'Each voucher arrives as a printed card in a sage envelope, or as a printable PDF if you are in a hurry.', 'treats' ),
				)
			);
			?>

			<div class="voucher-grid">
				<?php foreach ( $experiences as $index => $experience ) : ?>
					<article class="voucher-card<?php echo $experience['featured'] ? ' voucher-card--featured' : ''; ?>"<?php treats_reveal( $index * 80 ); ?>>
						<?php if ( $experience['featured'] ) : ?>
							<span class="pill pill--brass voucher-card__ribbon"><?php esc_html_e( 'Most gifted', 'treats' ); ?></span>
						<?php endif; ?>

						<div class="voucher-card__amount"><?php echo esc_html( treats_format_price( $experience['amount'] ) ); ?></div>
						<h3 class="voucher-card__title"><?php echo esc_html( $experience['title'] ); ?></h3>
						<p class="voucher-card__text"><?php echo esc_html( $experience['text'] ); ?></p>

						<a class="btn btn--secondary btn--sm" href="#voucher-form" data-voucher-preset="<?php echo esc_attr( $experience['amount'] ); ?>">
							<?php esc_html_e( 'Choose this', 'treats' ); ?>
						</a>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section section--tint" id="voucher-form">
		<div class="container container--wide">
			<div class="split split--wide-left" style="align-items:start">
				<div<?php treats_reveal(); ?>>
					<div class="form-card">
						<header class="form-card__header">
							<h2 class="form-card__title"><?php esc_html_e( 'Order a gift voucher', 'treats' ); ?></h2>
							<p class="text-mute" style="font-size:var(--fs-sm)">
								<?php esc_html_e( 'Tell us what you would like and we will email you to arrange payment and delivery — usually the same day.', 'treats' ); ?>
							</p>
						</header>

						<?php treats_form_notice(); ?>

						<form class="form" method="post" action="<?php echo esc_url( treats_form_action() ); ?>" data-treats-form="voucher">
							<div data-form-response hidden></div>

							<div class="field">
								<span class="field__label"><?php esc_html_e( 'Voucher amount', 'treats' ); ?><span class="required" aria-hidden="true">*</span></span>
								<div class="choice-grid choice-grid--wide" data-required-group>
									<?php foreach ( $amounts as $index => $amount ) : ?>
										<span class="choice">
											<input type="radio" id="amount-<?php echo esc_attr( (string) $index ); ?>" name="treats_amount" value="<?php echo esc_attr( treats_format_price( $amount ) ); ?>" data-voucher-amount <?php checked( 1, $index ); ?>>
											<label class="choice__label" for="amount-<?php echo esc_attr( (string) $index ); ?>"><?php echo esc_html( treats_format_price( $amount ) ); ?></label>
										</span>
									<?php endforeach; ?>

									<span class="choice">
										<input type="radio" id="amount-other" name="treats_amount" value="other" data-voucher-amount>
										<label class="choice__label" for="amount-other"><?php esc_html_e( 'Other', 'treats' ); ?></label>
									</span>
								</div>
							</div>

							<div class="field" data-voucher-custom hidden>
								<label class="field__label" for="voucher-custom"><?php esc_html_e( 'Amount you would like', 'treats' ); ?></label>
								<input class="input" type="text" id="voucher-custom" name="treats_amount_custom" inputmode="decimal" placeholder="<?php echo esc_attr( treats_currency_symbol() . '35.00' ); ?>">
							</div>

							<div class="form__grid form__grid--2">
								<div class="field">
									<label class="field__label" for="voucher-name"><?php esc_html_e( 'Your name', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
									<input class="input" type="text" id="voucher-name" name="treats_name" autocomplete="name" required>
									<span class="field__error"></span>
								</div>

								<div class="field">
									<label class="field__label" for="voucher-email"><?php esc_html_e( 'Your email', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
									<input class="input" type="email" id="voucher-email" name="treats_email" autocomplete="email" required>
									<span class="field__error"></span>
								</div>

								<div class="field">
									<label class="field__label" for="voucher-phone"><?php esc_html_e( 'Phone', 'treats' ); ?></label>
									<input class="input" type="tel" id="voucher-phone" name="treats_phone" autocomplete="tel">
								</div>

								<div class="field">
									<label class="field__label" for="voucher-recipient"><?php esc_html_e( 'Who is it for?', 'treats' ); ?></label>
									<input class="input" type="text" id="voucher-recipient" name="treats_recipient">
								</div>
							</div>

							<div class="field">
								<span class="field__label"><?php esc_html_e( 'How should it reach you?', 'treats' ); ?></span>
								<div class="choice-grid choice-grid--wide">
									<span class="choice">
										<input type="radio" id="delivery-post" name="treats_delivery" value="<?php esc_attr_e( 'Posted card', 'treats' ); ?>" checked>
										<label class="choice__label" for="delivery-post"><?php esc_html_e( 'Posted card', 'treats' ); ?></label>
									</span>
									<span class="choice">
										<input type="radio" id="delivery-email" name="treats_delivery" value="<?php esc_attr_e( 'Emailed PDF', 'treats' ); ?>">
										<label class="choice__label" for="delivery-email"><?php esc_html_e( 'Emailed PDF', 'treats' ); ?></label>
									</span>
									<span class="choice">
										<input type="radio" id="delivery-collect" name="treats_delivery" value="<?php esc_attr_e( 'Collect in person', 'treats' ); ?>">
										<label class="choice__label" for="delivery-collect"><?php esc_html_e( 'Collect in person', 'treats' ); ?></label>
									</span>
								</div>
							</div>

							<div class="field">
								<label class="field__label" for="voucher-message"><?php esc_html_e( 'Message on the card', 'treats' ); ?></label>
								<textarea class="input" id="voucher-message" name="treats_message" rows="3" placeholder="<?php esc_attr_e( 'Happy birthday, Mam — enjoy a proper afternoon.', 'treats' ); ?>"></textarea>
							</div>

							<?php treats_form_fields( 'voucher' ); ?>

							<button class="btn btn--primary btn--lg" type="submit"><?php esc_html_e( 'Order this voucher', 'treats' ); ?></button>
						</form>
					</div>
				</div>

				<aside class="booking-aside"<?php treats_reveal( 100 ); ?>>
					<div class="info-card">
						<h2 class="info-card__title"><?php esc_html_e( 'How it works', 'treats' ); ?></h2>
						<ol style="margin:0;padding-left:1.2rem;display:grid;gap:.75rem;font-size:var(--fs-sm);color:var(--c-ink-soft)">
							<li><?php esc_html_e( 'Tell us the amount and who it is for.', 'treats' ); ?></li>
							<li><?php esc_html_e( 'We email you a payment link, or take payment over the phone.', 'treats' ); ?></li>
							<li><?php esc_html_e( 'Your voucher is posted or emailed, usually the same day.', 'treats' ); ?></li>
						</ol>
					</div>

					<div class="info-card">
						<h2 class="info-card__title"><?php esc_html_e( 'Terms', 'treats' ); ?></h2>
						<p style="font-size:var(--fs-sm);color:var(--c-ink-soft)">
							<?php echo esc_html( get_theme_mod( 'treats_voucher_terms', __( 'Vouchers are valid for 12 months from the date of purchase and can be used against anything on our menus.', 'treats' ) ) ); ?>
						</p>
					</div>

					<div class="info-card">
						<h2 class="info-card__title"><?php esc_html_e( 'Questions?', 'treats' ); ?></h2>
						<ul class="contact-list">
							<?php if ( treats_get_phone() ) : ?>
								<li class="contact-list__item">
									<?php treats_icon( 'phone', array( 'size' => 18 ) ); ?>
									<a href="tel:<?php echo esc_attr( treats_get_phone_link() ); ?>"><?php echo esc_html( treats_get_phone() ); ?></a>
								</li>
							<?php endif; ?>
							<?php if ( treats_get_email() ) : ?>
								<li class="contact-list__item">
									<?php treats_icon( 'mail', array( 'size' => 18 ) ); ?>
									<a href="mailto:<?php echo esc_attr( treats_get_email() ); ?>"><?php echo esc_html( treats_get_email() ); ?></a>
								</li>
							<?php endif; ?>
						</ul>
					</div>
				</aside>
			</div>

			<?php
			$treats_content = trim( get_the_content() );

			if ( '' !== $treats_content ) {
				?>
				<div class="container container--content entry-content" style="margin-top:4rem">
					<?php the_content(); ?>
				</div>
				<?php
			}
			?>
		</div>
	</section>

	<?php
endwhile;

get_footer();
