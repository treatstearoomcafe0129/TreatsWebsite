<?php
/**
 * Newsletter sign-up.
 *
 * Posts to an external list when one is configured, otherwise stores the
 * subscriber in WordPress via the theme's AJAX handler.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$external = trim( (string) get_theme_mod( 'treats_newsletter_action', '' ) );
$uid      = wp_unique_id( 'newsletter-' );
?>
<div class="newsletter">
	<p class="newsletter__small" style="margin-bottom:.25rem">
		<?php echo esc_html( get_theme_mod( 'treats_newsletter_text', __( 'One short email a month: new bakes, seasonal afternoon teas and the odd offer.', 'treats' ) ) ); ?>
	</p>

	<form
		class="newsletter__form"
		method="post"
		action="<?php echo esc_url( '' !== $external ? $external : admin_url( 'admin-ajax.php' ) ); ?>"
		<?php if ( '' === $external ) : ?>
			data-treats-form="subscribe"
		<?php endif; ?>
		<?php echo '' !== $external ? 'target="_blank" rel="noopener"' : ''; ?>
	>
		<div data-form-response hidden></div>

		<div class="newsletter__row">
			<label class="screen-reader-text" for="<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Email address', 'treats' ); ?></label>
			<input
				class="input"
				type="email"
				id="<?php echo esc_attr( $uid ); ?>"
				name="<?php echo esc_attr( '' !== $external ? 'EMAIL' : 'treats_email' ); ?>"
				placeholder="<?php esc_attr_e( 'your@email.co.uk', 'treats' ); ?>"
				autocomplete="email"
				required
			>
			<button class="btn btn--primary" type="submit"><?php esc_html_e( 'Subscribe', 'treats' ); ?></button>
		</div>

		<?php if ( '' === $external ) : ?>
			<?php treats_form_fields( 'subscribe' ); ?>
		<?php endif; ?>

		<p class="newsletter__small">
			<?php
			$privacy_url = get_privacy_policy_url();

			if ( $privacy_url ) {
				printf(
					/* translators: %s: link to the privacy policy. */
					esc_html__( 'We only email about the tea room. Unsubscribe any time — see our %s.', 'treats' ),
					'<a href="' . esc_url( $privacy_url ) . '">' . esc_html__( 'privacy policy', 'treats' ) . '</a>'
				);
			} else {
				esc_html_e( 'We only email about the tea room. Unsubscribe any time.', 'treats' );
			}
			?>
		</p>
	</form>
</div>
