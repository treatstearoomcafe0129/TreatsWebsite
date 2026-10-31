<?php
/**
 * Template Name: Loyalty Card
 * Template Post Type: page
 *
 * Shows the visitor's card if this phone has one (from the email link or
 * remembered from last time), otherwise the join form.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$treats_card = treats_loyalty_visitor_card();

get_header();

while ( have_posts() ) :
	the_post();

	if ( $treats_card ) :
		$state = treats_loyalty_card_state( $treats_card['member'] );
		?>
		<section class="section loyalty-section">
			<div class="container container--narrow">
				<div class="loyalty-card" data-loyalty-card data-token="<?php echo esc_attr( $treats_card['token'] ); ?>" data-version="<?php echo esc_attr( $state['version'] ); ?>">
					<div class="loyalty-card__bar" aria-hidden="true"></div>

					<header class="loyalty-card__head">
						<?php treats_the_logo( 'loyalty-card__logo' ); ?>
						<p class="loyalty-card__eyebrow"><?php esc_html_e( 'Loyalty card', 'treats' ); ?></p>
						<h1 class="loyalty-card__name"><?php echo esc_html( $state['name'] ); ?></h1>
					</header>

					<div class="loyalty-rewards" data-loyalty-rewards<?php echo $state['rewards'] ? '' : ' hidden'; ?>>
						<p class="loyalty-rewards__title"><?php esc_html_e( 'Ready to enjoy', 'treats' ); ?></p>
						<ul class="loyalty-rewards__list" data-loyalty-reward-list>
							<?php foreach ( $state['rewards'] as $reward ) : ?>
								<li class="loyalty-rewards__item loyalty-rewards__item--<?php echo esc_attr( $reward['kind'] ); ?>"><?php echo esc_html( $reward['label'] ); ?></li>
							<?php endforeach; ?>
						</ul>
						<p class="loyalty-rewards__hint"><?php esc_html_e( 'Show this card at the counter to claim it.', 'treats' ); ?></p>
					</div>

					<ol class="loyalty-grid" data-loyalty-grid aria-label="<?php esc_attr_e( 'Stamps on your card', 'treats' ); ?>">
						<?php foreach ( $state['boxes'] as $box ) : ?>
							<li class="loyalty-box loyalty-box--<?php echo esc_attr( $box['type'] ); ?><?php echo $box['filled'] ? ' is-filled' : ''; ?><?php echo $box['kind'] ? ' loyalty-box--' . esc_attr( $box['kind'] ) : ''; ?>">
								<span class="screen-reader-text">
									<?php
									echo esc_html(
										'reward' === $box['type']
											? treats_loyalty_reward_label( $box['kind'] ) . ( $box['filled'] ? ', ' . __( 'earned', 'treats' ) : '' )
											/* translators: %d: box number. */
											: sprintf( __( 'Stamp %d', 'treats' ), $box['box'] ) . ( $box['filled'] ? ', ' . __( 'stamped', 'treats' ) : '' )
									);
									?>
								</span>
							</li>
						<?php endforeach; ?>
					</ol>

					<p class="loyalty-card__next" data-loyalty-next aria-live="polite">
						<?php
						if ( $state['next'] ) {
							echo esc_html(
								sprintf(
									/* translators: 1: number of stamps, 2: reward name. */
									_n( '%1$d more hot drink until your %2$s', '%1$d more hot drinks until your %2$s', $state['next']['to_go'], 'treats' ),
									$state['next']['to_go'],
									strtolower( $state['next']['label'] )
								)
							);
						}
						?>
					</p>

					<div class="loyalty-card__divider" aria-hidden="true"></div>

					<div class="loyalty-qr">
						<div class="loyalty-qr__code" data-loyalty-qr data-code="<?php echo esc_attr( 'TREATS:' . $state['code'] ); ?>" role="img" aria-label="<?php esc_attr_e( 'QR code for staff to scan', 'treats' ); ?>"></div>
						<p class="loyalty-qr__label"><?php esc_html_e( 'Show this at the counter with every hot drink', 'treats' ); ?></p>
						<p class="loyalty-qr__text"><?php echo esc_html( $state['code'] ); ?></p>
					</div>
				</div>

				<div class="loyalty-help">
					<details class="loyalty-help__item">
						<summary><?php esc_html_e( 'Keep your card one tap away', 'treats' ); ?></summary>
						<p><strong><?php esc_html_e( 'iPhone:', 'treats' ); ?></strong> <?php esc_html_e( 'tap the Share button in Safari, then “Add to Home Screen”.', 'treats' ); ?></p>
						<p><strong><?php esc_html_e( 'Android:', 'treats' ); ?></strong> <?php esc_html_e( 'tap the ⋮ menu in Chrome, then “Add to Home screen”.', 'treats' ); ?></p>
						<p><?php esc_html_e( 'Lost it? Your welcome email has a button that always brings it back.', 'treats' ); ?></p>
					</details>
					<details class="loyalty-help__item">
						<summary><?php esc_html_e( 'How it works', 'treats' ); ?></summary>
						<p><?php esc_html_e( 'Every hot drink you buy earns a stamp. After 5 stamps your next hot drink is free, after 10 you get a free scone and after 15 a free cake. Then your card starts again.', 'treats' ); ?></p>
						<p><?php esc_html_e( 'Rewards stay on your card until you use them. One card per person; stamps cannot be exchanged for cash.', 'treats' ); ?></p>
					</details>
					<p class="loyalty-help__forget">
						<a href="<?php echo esc_url( add_query_arg( 'forget', '1', treats_loyalty_card_url() ) ); ?>"><?php esc_html_e( 'Not your card? Remove it from this phone', 'treats' ); ?></a>
					</p>
				</div>
			</div>
		</section>
		<?php
	else :
		treats_page_hero(
			array(
				'eyebrow'  => (string) treats_meta( 'eyebrow', 0, __( 'Loyalty card', 'treats' ) ),
				'title'    => get_the_title(),
				'intro'    => (string) treats_meta( 'intro', 0, __( 'Your 6th hot drink is on us, then a free scone, then a free cake.', 'treats' ) ),
				'image_id' => (int) get_post_thumbnail_id(),
			)
		);

		$privacy = get_privacy_policy_url();
		?>
		<section class="section">
			<div class="container container--wide">
				<div class="split split--wide-left" style="align-items:start">
					<div<?php treats_reveal(); ?>>
						<div class="form-card">
							<header class="form-card__header">
								<h2 class="form-card__title"><?php esc_html_e( 'Get your card', 'treats' ); ?></h2>
								<p class="text-mute" style="font-size:var(--fs-sm)"><?php esc_html_e( 'Free, and it lives on your phone. It takes about twenty seconds.', 'treats' ); ?></p>
							</header>

							<form class="form" method="post" data-loyalty-form="join" novalidate>
								<div data-form-response hidden></div>

								<div class="field">
									<label class="field__label" for="loyalty-name"><?php esc_html_e( 'Your name', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
									<input class="input" type="text" id="loyalty-name" name="name" autocomplete="name" maxlength="100" required>
									<span class="field__error"></span>
								</div>

								<div class="field">
									<label class="field__label" for="loyalty-email"><?php esc_html_e( 'Your email', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
									<input class="input" type="email" id="loyalty-email" name="email" autocomplete="email" required>
									<span class="field__hint"><?php esc_html_e( 'We email you a link to your card, so you can always get it back.', 'treats' ); ?></span>
									<span class="field__error"></span>
								</div>

								<div class="field">
									<label class="loyalty-check">
										<input type="checkbox" name="terms" value="1" required>
										<span>
											<?php
											if ( $privacy ) {
												printf(
													/* translators: %s: privacy policy link. */
													esc_html__( 'I am happy for Treats to keep my name, email and stamps to run my loyalty card, as set out in the %s.', 'treats' ),
													'<a href="' . esc_url( $privacy ) . '">' . esc_html__( 'privacy policy', 'treats' ) . '</a>'
												);
											} else {
												esc_html_e( 'I am happy for Treats to keep my name, email and stamps to run my loyalty card.', 'treats' );
											}
											?>
										</span>
									</label>
									<span class="field__error"></span>
								</div>

								<div class="field">
									<label class="loyalty-check">
										<input type="checkbox" name="marketing" value="1">
										<span><?php esc_html_e( 'Send me the occasional email about seasonal menus and offers. (Optional — you can unsubscribe at any time.)', 'treats' ); ?></span>
									</label>
								</div>

								<input type="hidden" name="treats_nonce" value="<?php echo esc_attr( wp_create_nonce( 'treats_public' ) ); ?>">
								<input type="hidden" name="treats_ts" value="<?php echo esc_attr( (string) time() ); ?>">
								<div class="form-trap" aria-hidden="true">
									<label for="loyalty-website"><?php esc_html_e( 'Leave this field empty', 'treats' ); ?></label>
									<input type="text" id="loyalty-website" name="treats_website" tabindex="-1" autocomplete="off">
								</div>

								<button class="btn btn--primary btn--lg" type="submit"><?php esc_html_e( 'Create my card', 'treats' ); ?></button>
								<noscript><p class="field__hint"><?php esc_html_e( 'Please turn on JavaScript to create your card, or ask us at the counter.', 'treats' ); ?></p></noscript>
							</form>
						</div>
					</div>

					<aside class="booking-aside"<?php treats_reveal( 100 ); ?>>
						<div class="info-card loyalty-ladder">
							<h3 class="info-card__title"><?php esc_html_e( 'How it works', 'treats' ); ?></h3>
							<ol class="loyalty-ladder__list">
								<li><strong><?php esc_html_e( 'Show your card', 'treats' ); ?></strong> <?php esc_html_e( 'whenever you buy a hot drink, and we stamp it.', 'treats' ); ?></li>
								<li><strong><?php esc_html_e( '5 stamps', 'treats' ); ?></strong> <?php esc_html_e( 'your next hot drink is free.', 'treats' ); ?></li>
								<li><strong><?php esc_html_e( '10 stamps', 'treats' ); ?></strong> <?php esc_html_e( 'a free scone.', 'treats' ); ?></li>
								<li><strong><?php esc_html_e( '15 stamps', 'treats' ); ?></strong> <?php esc_html_e( 'a free cake — then your card starts again.', 'treats' ); ?></li>
							</ol>
						</div>

						<div class="info-card">
							<h3 class="info-card__title"><?php esc_html_e( 'Already have a card?', 'treats' ); ?></h3>
							<p class="text-mute" style="font-size:var(--fs-sm)"><?php esc_html_e( 'New phone, or lost the link? We will email it to you.', 'treats' ); ?></p>
							<form class="form" method="post" data-loyalty-form="resend" novalidate>
								<div data-form-response hidden></div>
								<div class="field">
									<label class="field__label" for="loyalty-resend-email"><?php esc_html_e( 'Your email', 'treats' ); ?></label>
									<input class="input" type="email" id="loyalty-resend-email" name="email" autocomplete="email" required>
									<span class="field__error"></span>
								</div>
								<input type="hidden" name="treats_nonce" value="<?php echo esc_attr( wp_create_nonce( 'treats_public' ) ); ?>">
								<input type="hidden" name="treats_ts" value="<?php echo esc_attr( (string) time() ); ?>">
								<div class="form-trap" aria-hidden="true">
									<label for="loyalty-resend-website"><?php esc_html_e( 'Leave this field empty', 'treats' ); ?></label>
									<input type="text" id="loyalty-resend-website" name="treats_website" tabindex="-1" autocomplete="off">
								</div>
								<button class="btn btn--secondary" type="submit"><?php esc_html_e( 'Email me my card', 'treats' ); ?></button>
							</form>
						</div>
					</aside>
				</div>
			</div>
		</section>
		<?php
	endif;
endwhile;

get_footer();
