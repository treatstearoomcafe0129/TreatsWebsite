<?php
/**
 * A product on the shop listing.
 *
 * @package Treats
 *
 * @var array $args {
 *     @type int $product_id Product post ID.
 *     @type int $delay      Reveal delay in milliseconds.
 * }
 */

defined( 'ABSPATH' ) || exit;

$config = wp_parse_args(
	$args ?? array(),
	array(
		'product_id' => 0,
		'delay'      => 0,
	)
);

$product_id = (int) $config['product_id'];

if ( ! $product_id ) {
	return;
}

$price      = treats_product_price( $product_id );
$stock      = treats_product_stock( $product_id );
$sold_out   = null !== $stock && $stock < 1;
$on_sale    = treats_product_on_sale( $product_id );
$summary    = treats_product_summary( $product_id );
$collect    = treats_product_collect_only( $product_id );
$low_stock  = null !== $stock && $stock > 0 && $stock <= 3;
$has_options = treats_product_has_options( $product_id );
?>
<li class="card product-card<?php echo $sold_out ? ' product-card--sold-out' : ''; ?>"<?php treats_reveal( (int) $config['delay'] ); ?>>
	<a class="product-card__media" href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" tabindex="-1" aria-hidden="true">
		<?php $image_id = treats_product_image_id( $product_id ); ?>
		<?php if ( $image_id ) : ?>
			<?php
			echo wp_get_attachment_image(
				$image_id,
				'medium_large',
				false,
				array(
					'loading'  => 'lazy',
					'decoding' => 'async',
					'alt'      => '',
				)
			);
			?>
		<?php else : ?>
			<span class="product-card__placeholder">
				<?php treats_icon( 'teapot', array( 'size' => 40 ) ); ?>
			</span>
		<?php endif; ?>

		<?php if ( $sold_out ) : ?>
			<span class="product-card__flag product-card__flag--out"><?php esc_html_e( 'Sold out', 'treats' ); ?></span>
		<?php elseif ( $on_sale ) : ?>
			<span class="product-card__flag"><?php esc_html_e( 'Reduced', 'treats' ); ?></span>
		<?php endif; ?>
	</a>

	<div class="product-card__body">
		<h2 class="product-card__title">
			<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>"><?php echo esc_html( get_the_title( $product_id ) ); ?></a>
		</h2>

		<?php if ( '' !== $summary ) : ?>
			<p class="product-card__summary"><?php echo esc_html( wp_trim_words( $summary, 18 ) ); ?></p>
		<?php endif; ?>

		<p class="product-card__price">
			<?php if ( $on_sale && ! $has_options ) : ?>
				<span class="product-card__was"><?php echo esc_html( treats_money( treats_product_regular_price( $product_id ) ) ); ?></span>
			<?php endif; ?>
			<span class="product-card__now">
				<?php
				if ( $has_options ) {
					printf(
						/* translators: %s: the cheapest price. */
						esc_html__( 'from %s', 'treats' ),
						esc_html( treats_money( $price ) )
					);
				} else {
					echo esc_html( treats_money( $price ) );
				}
				?>
			</span>
		</p>

		<?php if ( $low_stock ) : ?>
			<p class="product-card__stock">
				<?php
				printf(
					/* translators: %d: number remaining. */
					esc_html( _n( 'Only %d left', 'Only %d left', (int) $stock, 'treats' ) ),
					(int) $stock
				);
				?>
			</p>
		<?php elseif ( $collect ) : ?>
			<p class="product-card__stock"><?php esc_html_e( 'Collection only', 'treats' ); ?></p>
		<?php endif; ?>

		<?php if ( $sold_out ) : ?>
			<span class="btn btn--secondary btn--sm product-card__cta" aria-disabled="true"><?php esc_html_e( 'Sold out', 'treats' ); ?></span>
		<?php elseif ( $has_options ) : ?>
			<?php /* An amount has to be picked before there is a price to charge. */ ?>
			<a class="btn btn--primary btn--sm product-card__cta" href="<?php echo esc_url( get_permalink( $product_id ) ); ?>">
				<?php esc_html_e( 'Choose an amount', 'treats' ); ?>
			</a>
		<?php elseif ( treats_shop_enabled() ) : ?>
			<form class="product-card__form" method="post" action="<?php echo esc_url( treats_form_action() ); ?>" data-treats-basket="add">
				<input type="hidden" name="treats_basket_action" value="add">
				<input type="hidden" name="action" value="treats_basket_add">
				<input type="hidden" name="treats_nonce" value="<?php echo esc_attr( wp_create_nonce( 'treats_public' ) ); ?>">
				<input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>">
				<input type="hidden" name="quantity" value="1">

				<button class="btn btn--primary btn--sm product-card__cta" type="submit">
					<?php treats_icon( 'bag', array( 'size' => 15 ) ); ?>
					<?php esc_html_e( 'Add to basket', 'treats' ); ?>
				</button>
			</form>
		<?php else : ?>
			<a class="btn btn--secondary btn--sm product-card__cta" href="<?php echo esc_url( get_permalink( $product_id ) ); ?>">
				<?php esc_html_e( 'View', 'treats' ); ?>
			</a>
		<?php endif; ?>
	</div>
</li>
