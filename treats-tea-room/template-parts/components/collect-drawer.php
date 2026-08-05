<?php
/**
 * Click & Collect basket and order form.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$slots = treats_booking_slots();
?>
<button class="collect-fab" type="button" aria-haspopup="dialog">
	<?php treats_icon( 'bag', array( 'size' => 18 ) ); ?>
	<span><?php esc_html_e( 'Your order', 'treats' ); ?></span>
	<span class="collect-fab__count" data-collect-count>0</span>
</button>

<div class="collect-drawer" role="dialog" aria-modal="true" aria-labelledby="collect-title">
	<div class="collect-drawer__scrim"></div>

	<div class="collect-drawer__panel">
		<header class="collect-drawer__header">
			<h2 class="collect-drawer__title" id="collect-title"><?php esc_html_e( 'Click & Collect', 'treats' ); ?></h2>
			<button class="collect-drawer__close" type="button" aria-label="<?php esc_attr_e( 'Close', 'treats' ); ?>">
				<?php treats_icon( 'close', array( 'size' => 20 ) ); ?>
			</button>
		</header>

		<div class="collect-drawer__body">
			<div class="collect-empty" data-collect-empty>
				<?php treats_icon( 'bag', array( 'size' => 44 ) ); ?>
				<p><?php esc_html_e( 'Your collection order is empty. Add anything you like from our menus.', 'treats' ); ?></p>
			</div>

			<ul class="collect-list" data-collect-list></ul>

			<form class="form" style="margin-top:1.5rem" method="post" action="<?php echo esc_url( treats_form_action() ); ?>" data-treats-form="order">
				<div data-form-response hidden></div>
				<?php treats_form_notice(); ?>

				<div class="form__grid form__grid--2">
					<div class="field">
						<label class="field__label" for="collect-name"><?php esc_html_e( 'Name', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
						<input class="input" type="text" id="collect-name" name="treats_name" autocomplete="name" required>
						<span class="field__error"></span>
					</div>

					<div class="field">
						<label class="field__label" for="collect-phone"><?php esc_html_e( 'Phone', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
						<input class="input" type="tel" id="collect-phone" name="treats_phone" autocomplete="tel" required>
						<span class="field__error"></span>
					</div>

					<div class="field field--full">
						<label class="field__label" for="collect-email"><?php esc_html_e( 'Email', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
						<input class="input" type="email" id="collect-email" name="treats_email" autocomplete="email" required>
						<span class="field__error"></span>
					</div>

					<div class="field">
						<label class="field__label" for="collect-date"><?php esc_html_e( 'Collection date', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
						<input class="input" type="date" id="collect-date" name="treats_date" required>
						<span class="field__error"></span>
					</div>

					<div class="field">
						<label class="field__label" for="collect-time"><?php esc_html_e( 'Collection time', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
						<select class="input" id="collect-time" name="treats_time" required>
							<option value=""><?php esc_html_e( 'Choose a time', 'treats' ); ?></option>
							<?php foreach ( $slots as $slot ) : ?>
								<option value="<?php echo esc_attr( $slot ); ?>"><?php echo esc_html( $slot ); ?></option>
							<?php endforeach; ?>
						</select>
						<span class="field__error"></span>
					</div>

					<div class="field field--full">
						<label class="field__label" for="collect-notes"><?php esc_html_e( 'Anything we should know?', 'treats' ); ?></label>
						<textarea class="input" id="collect-notes" name="treats_notes" rows="2" style="min-height:70px"></textarea>
					</div>
				</div>

				<input type="hidden" name="treats_items" data-collect-items value="">
				<input type="hidden" name="treats_total" data-collect-total-field value="">

				<?php treats_form_fields( 'order' ); ?>

				<div class="collect-total">
					<span><?php esc_html_e( 'Estimated total', 'treats' ); ?></span>
					<span class="collect-total__value" data-collect-total><?php echo esc_html( treats_currency_symbol() . '0.00' ); ?></span>
				</div>

				<p class="collect-note">
					<?php echo esc_html( get_theme_mod( 'treats_collect_notice', __( 'Collection orders need 2 hours notice. We will call to confirm your time and take payment on collection.', 'treats' ) ) ); ?>
				</p>

				<button class="btn btn--primary btn--block" type="submit"><?php esc_html_e( 'Send collection request', 'treats' ); ?></button>

				<button class="btn btn--ghost btn--sm" type="button" data-collect-clear><?php esc_html_e( 'Clear order', 'treats' ); ?></button>
			</form>
		</div>
	</div>
</div>
