<?php
/**
 * A single product, offered for sale inside another page.
 *
 * Used where a page is about the thing rather than about the shop — the
 * Afternoon Tea page selling it as a gift, the Gift Vouchers page selling the
 * voucher card. The shop still holds the product; this is a second door into
 * it, not a second copy.
 *
 * @package Treats
 *
 * @var array $args {
 *     @type int    $product_id Product post ID.
 *     @type string $eyebrow    Small label above the title.
 *     @type string $title      Overrides the product title.
 *     @type string $text       Overrides the product summary.
 * }
 */

defined( 'ABSPATH' ) || exit;

$config = wp_parse_args(
	$args ?? array(),
	array(
		'product_id' => 0,
		'eyebrow'    => '',
		'title'      => '',
		'text'       => '',
	)
);

$product_id = (int) $config['product_id'];

if ( ! $product_id || 'publish' !== get_post_status( $product_id ) ) {
	return;
}

$options  = treats_product_options( $product_id );
$price    = treats_product_price( $product_id, $options ? 0 : null );
$stock    = treats_product_stock( $product_id );
$sold_out = null !== $stock && $stock < 1;
$summary  = '' !== $config['text'] ? $config['text'] : treats_product_summary( $product_id );
?>
<section class="section section--sm">
	<div class="container container--wide">
		<div class="buy-panel info-card"<?php treats_reveal(); ?>>

			<?php if ( has_post_thumbnail( $product_id ) ) : ?>
				<div class="buy-panel__media">
					<?php
					echo get_the_post_thumbnail(
						$product_id,
						'medium_large',
						array(
							'loading'  => 'lazy',
							'decoding' => 'async',
							'alt'      => '',
						)
					);
					?>
				</div>
			<?php endif; ?>

			<div class="buy-panel__body">
				<?php if ( '' !== $config['eyebrow'] ) : ?>
					<p class="buy-panel__eyebrow"><?php echo esc_html( $config['eyebrow'] ); ?></p>
				<?php endif; ?>

				<h2 class="glass-title buy-panel__title">
					<?php echo esc_html( '' !== $config['title'] ? $config['title'] : get_the_title( $product_id ) ); ?>
				</h2>

				<span class="gold-rule" aria-hidden="true"></span>

				<?php if ( '' !== $summary ) : ?>
					<p class="buy-panel__text"><?php echo esc_html( $summary ); ?></p>
				<?php endif; ?>

				<p class="buy-panel__price" data-product-price>
					<?php
					echo esc_html(
						$options
							/* translators: %s: the cheapest price. */
							? sprintf( __( 'from %s', 'treats' ), treats_money( $price ) )
							: treats_money( $price )
					);
					?>
				</p>

				<?php if ( $sold_out ) : ?>

					<p class="buy-panel__note"><?php esc_html_e( 'Sold out at the moment — ring us and we will tell you when more are in.', 'treats' ); ?></p>

				<?php elseif ( ! treats_shop_enabled() ) : ?>

					<a class="btn btn--primary btn--lg" href="tel:<?php echo esc_attr( treats_get_phone_link() ); ?>">
						<?php esc_html_e( 'Ring us to order', 'treats' ); ?>
					</a>

				<?php elseif ( $options ) : ?>

					<?php /* An amount has to be chosen, and the shop page is where that happens properly. */ ?>
					<a class="btn btn--primary btn--lg" href="<?php echo esc_url( get_permalink( $product_id ) ); ?>">
						<?php esc_html_e( 'Choose an amount', 'treats' ); ?>
					</a>

				<?php else : ?>

					<form class="buy-panel__form" method="post" action="<?php echo esc_url( treats_form_action() ); ?>" data-treats-basket="add">
						<input type="hidden" name="treats_basket_action" value="add">
						<input type="hidden" name="action" value="treats_basket_add">
						<input type="hidden" name="treats_nonce" value="<?php echo esc_attr( wp_create_nonce( 'treats_public' ) ); ?>">
						<input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>">
						<input type="hidden" name="quantity" value="1">

						<button class="btn btn--primary btn--lg" type="submit">
							<?php treats_icon( 'bag', array( 'size' => 17 ) ); ?>
							<?php esc_html_e( 'Add to basket', 'treats' ); ?>
						</button>
					</form>

				<?php endif; ?>

				<p class="buy-panel__more">
					<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>">
						<?php esc_html_e( 'See the details', 'treats' ); ?>
						<?php treats_icon( 'arrow-right', array( 'size' => 14 ) ); ?>
					</a>
				</p>
			</div>
		</div>
	</div>
</section>
