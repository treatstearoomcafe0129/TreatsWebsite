<?php
/**
 * Template Name: Checkout
 * Template Post Type: page
 *
 * One page, one column of decisions: who you are, how you want it, and the
 * card. The order summary sits alongside and updates as the fulfilment choice
 * changes, so the total is never a surprise at the last step.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

$treats_lines    = treats_basket_lines();
$treats_can_post = treats_basket_can_post();
$treats_default  = $treats_can_post ? 'post' : 'collect';
$treats_totals   = treats_basket_totals( $treats_default );
$treats_lead     = treats_collection_lead_hours();
$treats_earliest = gmdate( 'Y-m-d', time() + ( $treats_lead * HOUR_IN_SECONDS ) );
$treats_dispatch = (string) get_theme_mod( 'treats_shop_dispatch_note', '' );

treats_page_hero(
	array(
		'eyebrow' => __( 'Almost there', 'treats' ),
		'title'   => __( 'Checkout', 'treats' ),
		'compact' => true,
	)
);
?>

<section class="section">
	<div class="container container--wide">

		<?php if ( ! $treats_lines ) : ?>

			<div class="shop-empty">
				<?php treats_icon( 'bag', array( 'size' => 44 ) ); ?>
				<p><?php esc_html_e( 'There is nothing in your basket yet.', 'treats' ); ?></p>
				<a class="btn btn--primary" href="<?php echo esc_url( treats_shop_url() ); ?>">
					<?php esc_html_e( 'Go to the shop', 'treats' ); ?>
				</a>
			</div>

		<?php elseif ( ! treats_shop_enabled() ) : ?>

			<div class="shop-empty">
				<?php treats_icon( 'phone', array( 'size' => 44 ) ); ?>
				<p><?php esc_html_e( 'Online payment is not switched on yet. Ring us and we will take the order over the phone.', 'treats' ); ?></p>
				<a class="btn btn--primary" href="tel:<?php echo esc_attr( treats_get_phone_link() ); ?>">
					<?php echo esc_html( treats_get_phone() ); ?>
				</a>
			</div>

		<?php else : ?>

			<div class="checkout">

				<form class="checkout__form form" method="post" data-treats-checkout novalidate>
					<div class="checkout__alert" data-checkout-error role="alert" hidden></div>

					<noscript>
						<p class="checkout__alert checkout__alert--static">
							<?php esc_html_e( 'Card payment needs JavaScript switched on. Please ring us and we will take your order over the phone.', 'treats' ); ?>
						</p>
					</noscript>

					<input type="hidden" name="action" value="treats_checkout">
					<input type="hidden" name="treats_nonce" value="<?php echo esc_attr( wp_create_nonce( 'treats_public' ) ); ?>">
					<input type="hidden" name="treats_ts" value="<?php echo esc_attr( (string) time() ); ?>">
					<input type="hidden" name="treats_source_id" value="">

					<div class="form-trap" aria-hidden="true">
						<label for="checkout-website"><?php esc_html_e( 'Leave this field empty', 'treats' ); ?></label>
						<input type="text" id="checkout-website" name="treats_website" tabindex="-1" autocomplete="off">
					</div>

					<fieldset class="checkout__step">
						<legend class="checkout__legend">
							<span class="checkout__number" aria-hidden="true">1</span>
							<?php esc_html_e( 'Your details', 'treats' ); ?>
						</legend>

						<div class="form__grid form__grid--2">
							<div class="field">
								<label class="field__label" for="checkout-name"><?php esc_html_e( 'Name', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
								<input class="input" type="text" id="checkout-name" name="treats_name" autocomplete="name" required>
								<span class="field__error"></span>
							</div>

							<div class="field">
								<label class="field__label" for="checkout-phone"><?php esc_html_e( 'Telephone', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
								<input class="input" type="tel" id="checkout-phone" name="treats_phone" autocomplete="tel" required>
								<span class="field__error"></span>
							</div>

							<div class="field field--full">
								<label class="field__label" for="checkout-email"><?php esc_html_e( 'Email', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
								<input class="input" type="email" id="checkout-email" name="treats_email" autocomplete="email" required>
								<span class="field__hint"><?php esc_html_e( 'Your receipt and confirmation go here.', 'treats' ); ?></span>
								<span class="field__error"></span>
							</div>
						</div>
					</fieldset>

					<fieldset class="checkout__step">
						<legend class="checkout__legend">
							<span class="checkout__number" aria-hidden="true">2</span>
							<?php esc_html_e( 'How would you like it?', 'treats' ); ?>
						</legend>

						<div class="fulfilment">
							<label class="fulfilment__option">
								<input type="radio" name="treats_fulfilment" value="collect" data-fulfilment
									<?php checked( 'collect', $treats_default ); ?>>
								<span class="fulfilment__body">
									<span class="fulfilment__title"><?php esc_html_e( 'Collect from the café', 'treats' ); ?></span>
									<span class="fulfilment__note">
										<?php echo esc_html( treats_get_address_line() ); ?>
									</span>
									<span class="fulfilment__price"><?php esc_html_e( 'Free', 'treats' ); ?></span>
								</span>
							</label>

							<?php if ( $treats_can_post ) : ?>
								<label class="fulfilment__option">
									<input type="radio" name="treats_fulfilment" value="post" data-fulfilment
										<?php checked( 'post', $treats_default ); ?>>
									<span class="fulfilment__body">
										<span class="fulfilment__title"><?php esc_html_e( 'Post it to me', 'treats' ); ?></span>
										<span class="fulfilment__note">
											<?php
											echo esc_html(
												'' !== $treats_dispatch
													? $treats_dispatch
													: __( 'Sent by tracked delivery.', 'treats' )
											);
											?>
										</span>
										<?php $treats_post_cost = treats_basket_postage( 'post' ); ?>
										<span class="fulfilment__price" data-postage-price>
											<?php
											echo esc_html(
												$treats_post_cost > 0
													? treats_money( $treats_post_cost )
													: __( 'Free', 'treats' )
											);
											?>
										</span>
									</span>
								</label>
							<?php else : ?>
								<p class="checkout__hint">
									<?php esc_html_e( 'Something in your basket can only be collected, so postage is not available for this order.', 'treats' ); ?>
								</p>
							<?php endif; ?>
						</div>

						<div class="checkout__conditional" data-when="collect"<?php echo 'collect' === $treats_default ? '' : ' hidden'; ?>>
							<div class="field">
								<label class="field__label" for="checkout-collect-date"><?php esc_html_e( 'When will you collect?', 'treats' ); ?></label>
								<input class="input" type="date" id="checkout-collect-date" name="treats_collect_date"
									min="<?php echo esc_attr( $treats_earliest ); ?>">
								<span class="field__hint">
									<?php
									if ( $treats_lead > 0 ) {
										printf(
											/* translators: %d: hours of notice. */
											esc_html__( 'We need %d hours to get things ready. Leave it blank and we will ring you.', 'treats' ),
											(int) $treats_lead
										);
									} else {
										esc_html_e( 'Leave it blank and we will ring you when it is ready.', 'treats' );
									}
									?>
								</span>
							</div>
						</div>

						<div class="checkout__conditional" data-when="post"<?php echo 'post' === $treats_default ? '' : ' hidden'; ?>>
							<div class="form__grid form__grid--2">
								<div class="field field--full">
									<label class="field__label" for="checkout-address1"><?php esc_html_e( 'Address', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
									<input class="input" type="text" id="checkout-address1" name="treats_address1" autocomplete="address-line1">
									<span class="field__error"></span>
								</div>

								<div class="field field--full">
									<label class="field__label" for="checkout-address2"><?php esc_html_e( 'Address line 2', 'treats' ); ?></label>
									<input class="input" type="text" id="checkout-address2" name="treats_address2" autocomplete="address-line2">
								</div>

								<div class="field">
									<label class="field__label" for="checkout-city"><?php esc_html_e( 'Town or city', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
									<input class="input" type="text" id="checkout-city" name="treats_city" autocomplete="address-level2">
									<span class="field__error"></span>
								</div>

								<div class="field">
									<label class="field__label" for="checkout-postcode"><?php esc_html_e( 'Postcode', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
									<input class="input" type="text" id="checkout-postcode" name="treats_postcode" autocomplete="postal-code">
									<span class="field__error"></span>
								</div>
							</div>
						</div>

						<div class="field">
							<label class="field__label" for="checkout-notes"><?php esc_html_e( 'Anything we should know?', 'treats' ); ?></label>
							<textarea class="input" id="checkout-notes" name="treats_notes" rows="3"></textarea>
						</div>
					</fieldset>

					<fieldset class="checkout__step">
						<legend class="checkout__legend">
							<span class="checkout__number" aria-hidden="true">3</span>
							<?php esc_html_e( 'Payment', 'treats' ); ?>
						</legend>

						<div class="checkout__wallets" data-square-wallets hidden>
							<div id="square-apple-pay" class="checkout__wallet" hidden></div>
							<div id="square-google-pay" class="checkout__wallet" hidden></div>
							<p class="checkout__divider"><span><?php esc_html_e( 'or pay by card', 'treats' ); ?></span></p>
						</div>

						<div class="checkout__card">
							<div id="square-card" data-square-card></div>
							<p class="checkout__loading" data-square-loading>
								<?php esc_html_e( 'Loading the secure card form…', 'treats' ); ?>
							</p>
						</div>

						<button class="btn btn--primary btn--lg checkout__pay" type="submit" data-checkout-submit disabled>
							<?php
							printf(
								/* translators: %s: order total. */
								esc_html__( 'Pay %s', 'treats' ),
								'<span data-checkout-total>' . esc_html( treats_money( $treats_totals['total'] ) ) . '</span>'
							);
							?>
						</button>

						<p class="checkout__secure">
							<?php treats_icon( 'check', array( 'size' => 15 ) ); ?>
							<?php esc_html_e( 'Payments are handled by Square. Your card details go straight to them and are never stored on this website.', 'treats' ); ?>
						</p>
					</fieldset>
				</form>

				<aside class="checkout__summary" aria-label="<?php esc_attr_e( 'Order summary', 'treats' ); ?>">
					<div class="info-card checkout__card-summary">
						<h2 class="glass-title checkout__summary-title"><?php esc_html_e( 'Your order', 'treats' ); ?></h2>

						<ul class="checkout__items">
							<?php foreach ( $treats_lines as $treats_line ) : ?>
								<li>
									<span class="checkout__item-name">
										<?php echo esc_html( $treats_line['title'] ); ?>
										<span class="checkout__item-qty">&times;<?php echo esc_html( (string) $treats_line['quantity'] ); ?></span>
									</span>
									<span class="checkout__item-price"><?php echo esc_html( treats_money( $treats_line['total'] ) ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>

						<dl class="checkout__totals">
							<div>
								<dt><?php esc_html_e( 'Subtotal', 'treats' ); ?></dt>
								<dd><?php echo esc_html( treats_money( $treats_totals['subtotal'] ) ); ?></dd>
							</div>
							<div>
								<dt><?php esc_html_e( 'Postage', 'treats' ); ?></dt>
								<dd data-summary-postage><?php echo esc_html( treats_money( $treats_totals['postage'] ) ); ?></dd>
							</div>
							<div class="checkout__grand">
								<dt><?php esc_html_e( 'Total', 'treats' ); ?></dt>
								<dd data-summary-total><?php echo esc_html( treats_money( $treats_totals['total'] ) ); ?></dd>
							</div>
						</dl>

						<a class="checkout__edit" href="<?php echo esc_url( treats_shop_url() ); ?>">
							<?php esc_html_e( 'Change your basket', 'treats' ); ?>
						</a>
					</div>
				</aside>
			</div>

		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
