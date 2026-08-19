<?php
/**
 * The contents of the basket.
 *
 * Rendered inside the drawer and also returned on its own after every basket
 * change, so the markup here is the single description of what a basket looks
 * like — there is no second copy of it in JavaScript to fall out of step.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$lines      = treats_basket_lines();
$can_post   = treats_basket_can_post();
$totals     = treats_basket_totals( $can_post ? 'post' : 'collect' );
$gap        = treats_basket_free_postage_gap();
$nonce      = wp_create_nonce( 'treats_public' );
?>
<div class="basket-panel" data-basket-panel>
	<?php if ( ! $lines ) : ?>
		<div class="basket-empty">
			<?php treats_icon( 'bag', array( 'size' => 44 ) ); ?>
			<p><?php esc_html_e( 'Your basket is empty.', 'treats' ); ?></p>
			<a class="btn btn--secondary btn--sm" href="<?php echo esc_url( treats_shop_url() ); ?>">
				<?php esc_html_e( 'Have a look in the shop', 'treats' ); ?>
			</a>
		</div>
	<?php else : ?>
		<ul class="basket-list">
			<?php foreach ( $lines as $line ) : ?>
				<li class="basket-line">
					<div class="basket-line__media">
						<?php if ( $line['image_id'] ) : ?>
							<?php echo wp_get_attachment_image( $line['image_id'], 'thumbnail', false, array( 'alt' => '' ) ); ?>
						<?php else : ?>
							<span class="basket-line__placeholder"><?php treats_icon( 'teapot', array( 'size' => 22 ) ); ?></span>
						<?php endif; ?>
					</div>

					<div class="basket-line__body">
						<a class="basket-line__title" href="<?php echo esc_url( $line['url'] ); ?>">
							<?php echo esc_html( $line['title'] ); ?>
						</a>

						<p class="basket-line__price">
							<?php
							printf(
								/* translators: 1: unit price, 2: line total. */
								esc_html__( '%1$s each — %2$s', 'treats' ),
								esc_html( treats_money( $line['price'] ) ),
								esc_html( treats_money( $line['total'] ) )
							);
							?>
						</p>

						<?php if ( $line['collect_only'] ) : ?>
							<p class="basket-line__note"><?php esc_html_e( 'Collection only', 'treats' ); ?></p>
						<?php endif; ?>

						<form class="basket-line__qty" method="post" action="<?php echo esc_url( treats_form_action() ); ?>" data-treats-basket="update">
							<input type="hidden" name="treats_basket_action" value="update">
							<input type="hidden" name="action" value="treats_basket_update">
							<input type="hidden" name="treats_nonce" value="<?php echo esc_attr( $nonce ); ?>">
							<input type="hidden" name="product_id" value="<?php echo esc_attr( $line['product_id'] ); ?>">

							<label class="screen-reader-text" for="basket-qty-<?php echo esc_attr( $line['product_id'] ); ?>">
								<?php
								printf(
									/* translators: %s: product name. */
									esc_html__( 'Quantity of %s', 'treats' ),
									esc_html( $line['title'] )
								);
								?>
							</label>

							<input class="input basket-line__input" type="number"
								id="basket-qty-<?php echo esc_attr( $line['product_id'] ); ?>"
								name="quantity"
								value="<?php echo esc_attr( $line['quantity'] ); ?>"
								min="1" max="<?php echo esc_attr( $line['max'] ); ?>" step="1" inputmode="numeric">

							<button class="btn btn--secondary btn--sm basket-line__apply" type="submit">
								<?php esc_html_e( 'Update', 'treats' ); ?>
							</button>
						</form>
					</div>

					<form class="basket-line__remove" method="post" action="<?php echo esc_url( treats_form_action() ); ?>" data-treats-basket="remove">
						<input type="hidden" name="treats_basket_action" value="remove">
						<input type="hidden" name="action" value="treats_basket_remove">
						<input type="hidden" name="treats_nonce" value="<?php echo esc_attr( $nonce ); ?>">
						<input type="hidden" name="product_id" value="<?php echo esc_attr( $line['product_id'] ); ?>">

						<?php
						$remove_label = sprintf(
							/* translators: %s: product name. */
							__( 'Remove %s from your basket', 'treats' ),
							$line['title']
						);
						?>
						<button type="submit" aria-label="<?php echo esc_attr( $remove_label ); ?>">
							<?php treats_icon( 'close', array( 'size' => 16 ) ); ?>
						</button>
					</form>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php if ( $can_post && $gap > 0 ) : ?>
			<p class="basket-nudge">
				<?php
				printf(
					/* translators: %s: amount remaining. */
					esc_html__( 'Spend %s more for free postage.', 'treats' ),
					esc_html( treats_money( $gap ) )
				);
				?>
			</p>
		<?php endif; ?>

		<dl class="basket-totals">
			<div>
				<dt><?php esc_html_e( 'Subtotal', 'treats' ); ?></dt>
				<dd><?php echo esc_html( treats_money( $totals['subtotal'] ) ); ?></dd>
			</div>
			<div>
				<dt><?php esc_html_e( 'Postage', 'treats' ); ?></dt>
				<dd>
					<?php if ( ! $can_post ) : ?>
						<?php esc_html_e( 'Collection only', 'treats' ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Chosen at checkout', 'treats' ); ?>
					<?php endif; ?>
				</dd>
			</div>
		</dl>

		<a class="btn btn--primary btn--lg basket-checkout" href="<?php echo esc_url( treats_checkout_url() ); ?>">
			<?php esc_html_e( 'Checkout', 'treats' ); ?>
			<?php treats_icon( 'arrow-right', array( 'size' => 16 ) ); ?>
		</a>

		<p class="basket-reassure">
			<?php treats_icon( 'check', array( 'size' => 15 ) ); ?>
			<?php esc_html_e( 'Card payment handled by Square. We never see your card number.', 'treats' ); ?>
		</p>
	<?php endif; ?>
</div>
