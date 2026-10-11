<?php
/**
 * Full-width call to action.
 *
 * @package Treats
 *
 * @var array $args {
 *     @type string $eyebrow
 *     @type string $title
 *     @type string $text
 *     @type array  $actions Each: label, url, style.
 * }
 */

defined( 'ABSPATH' ) || exit;

$cta = wp_parse_args(
	$args ?? array(),
	array(
		'eyebrow' => __( 'Gift vouchers', 'treats' ),
		'title'   => __( 'Send someone a proper afternoon in Durham', 'treats' ),
		'text'    => __( 'Any amount, or one of our afternoon tea experiences. Posted to you or emailed the same day.', 'treats' ),
		'actions' => array(
			array(
				'label' => __( 'Buy a gift voucher', 'treats' ),
				'url'   => treats_vouchers_url(),
				'style' => 'btn--light',
			),
			array(
				'label' => __( 'Book a table', 'treats' ),
				'url'   => treats_booking_url(),
				'style' => 'btn--outline-light',
			),
		),
	)
);
?>
<section class="section">
	<div class="container container--wide">
		<div class="cta-banner"<?php treats_reveal( 0, 'scale' ); ?>>
			<?php if ( '' !== $cta['eyebrow'] ) : ?>
				<p class="eyebrow"><?php echo esc_html( $cta['eyebrow'] ); ?></p>
			<?php endif; ?>

			<h2><?php echo esc_html( $cta['title'] ); ?></h2>

			<?php if ( '' !== $cta['text'] ) : ?>
				<p><?php echo esc_html( $cta['text'] ); ?></p>
			<?php endif; ?>

			<div class="actions actions--center">
				<?php foreach ( $cta['actions'] as $action ) : ?>
					<a class="btn <?php echo esc_attr( $action['style'] ?? 'btn--light' ); ?> btn--lg" href="<?php echo esc_url( $action['url'] ); ?>">
						<?php echo esc_html( $action['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
