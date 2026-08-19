<?php
/**
 * A single product.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$treats_id       = get_the_ID();
	$treats_price    = treats_product_price( $treats_id );
	$treats_stock    = treats_product_stock( $treats_id );
	$treats_sold_out = null !== $treats_stock && $treats_stock < 1;
	$treats_sale     = treats_product_on_sale( $treats_id );
	$treats_gallery  = treats_product_gallery( $treats_id );
	$treats_sku      = (string) get_post_meta( $treats_id, '_treats_sku', true );
	$treats_collect  = treats_product_collect_only( $treats_id );
	$treats_postage  = treats_product_postage( $treats_id );
	$treats_max      = treats_basket_line_max( $treats_id );
	$treats_terms    = get_the_terms( $treats_id, 'treats_product_cat' );
	?>

	<article class="section product">
		<div class="container container--wide">
			<nav class="breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'treats' ); ?>">
				<a href="<?php echo esc_url( treats_shop_url() ); ?>"><?php esc_html_e( 'Shop', 'treats' ); ?></a>
				<?php if ( ! is_wp_error( $treats_terms ) && $treats_terms ) : ?>
					<span aria-hidden="true">/</span>
					<a href="<?php echo esc_url( get_term_link( $treats_terms[0] ) ); ?>"><?php echo esc_html( $treats_terms[0]->name ); ?></a>
				<?php endif; ?>
				<span aria-hidden="true">/</span>
				<span aria-current="page"><?php the_title(); ?></span>
			</nav>

			<div class="product__layout">

				<div class="product__media"<?php treats_reveal(); ?>>
					<figure class="product__figure">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'large', array( 'id' => 'product-main-image' ) ); ?>
						<?php else : ?>
							<span class="product__placeholder">
								<?php treats_icon( 'teapot', array( 'size' => 64 ) ); ?>
							</span>
						<?php endif; ?>
					</figure>

					<?php if ( $treats_gallery && has_post_thumbnail() ) : ?>
						<ul class="product__thumbs" data-product-gallery>
							<li>
								<button class="product__thumb product__thumb--active" type="button"
									data-full="<?php echo esc_url( (string) wp_get_attachment_image_url( get_post_thumbnail_id(), 'large' ) ); ?>"
									aria-label="<?php esc_attr_e( 'Show the main photograph', 'treats' ); ?>">
									<?php the_post_thumbnail( 'thumbnail', array( 'alt' => '' ) ); ?>
								</button>
							</li>

							<?php foreach ( $treats_gallery as $treats_image_id ) : ?>
								<li>
									<button class="product__thumb" type="button"
										data-full="<?php echo esc_url( (string) wp_get_attachment_image_url( $treats_image_id, 'large' ) ); ?>"
										aria-label="<?php esc_attr_e( 'Show this photograph', 'treats' ); ?>">
										<?php echo wp_get_attachment_image( $treats_image_id, 'thumbnail', false, array( 'alt' => '' ) ); ?>
									</button>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>

				<div class="product__detail"<?php treats_reveal( 100 ); ?>>
					<h1 class="product__title"><?php the_title(); ?></h1>
					<span class="gold-rule" aria-hidden="true"></span>

					<p class="product__price">
						<?php if ( $treats_sale && ! treats_product_has_options( $treats_id ) ) : ?>
							<span class="product__was"><?php echo esc_html( treats_money( treats_product_regular_price( $treats_id ) ) ); ?></span>
						<?php endif; ?>
						<span class="product__now" data-product-price>
							<?php
							// With options the figure follows whichever is
							// selected, starting at the first.
							echo esc_html(
								treats_money(
									treats_product_has_options( $treats_id )
										? treats_product_price( $treats_id, 0 )
										: $treats_price
								)
							);
							?>
						</span>
					</p>

					<div class="product__content entry-content">
						<?php the_content(); ?>
					</div>

					<?php if ( $treats_sold_out ) : ?>
						<p class="product__notice product__notice--out">
							<?php esc_html_e( 'Sold out for the moment. Ring us and we will tell you when more are in.', 'treats' ); ?>
						</p>
					<?php elseif ( ! treats_shop_enabled() ) : ?>
						<p class="product__notice">
							<?php esc_html_e( 'Online ordering is not open yet. Please ring us and we will put one aside.', 'treats' ); ?>
						</p>
					<?php else : ?>
						<form class="product__buy" method="post" action="<?php echo esc_url( treats_form_action() ); ?>" data-treats-basket="add">
							<input type="hidden" name="treats_basket_action" value="add">
							<input type="hidden" name="action" value="treats_basket_add">
							<input type="hidden" name="treats_nonce" value="<?php echo esc_attr( wp_create_nonce( 'treats_public' ) ); ?>">
							<input type="hidden" name="product_id" value="<?php echo esc_attr( $treats_id ); ?>">

							<?php $treats_options = treats_product_options( $treats_id ); ?>
							<?php if ( $treats_options ) : ?>
								<fieldset class="product__options">
									<legend class="field__label"><?php esc_html_e( 'Choose an amount', 'treats' ); ?></legend>

									<div class="product__option-row">
										<?php foreach ( $treats_options as $treats_index => $treats_option ) : ?>
											<label class="product__option">
												<input type="radio" name="option"
													value="<?php echo esc_attr( (string) $treats_index ); ?>"
													data-price="<?php echo esc_attr( (string) $treats_option['price'] ); ?>"
													<?php checked( 0, $treats_index ); ?>>
												<span><?php echo esc_html( $treats_option['label'] ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
								</fieldset>
							<?php endif; ?>

							<div class="field product__qty">
								<label class="field__label" for="product-qty"><?php esc_html_e( 'Quantity', 'treats' ); ?></label>
								<input class="input" type="number" id="product-qty" name="quantity" value="1" min="1" max="<?php echo esc_attr( $treats_max ); ?>" step="1" inputmode="numeric">
							</div>

							<button class="btn btn--primary btn--lg" type="submit">
								<?php treats_icon( 'bag', array( 'size' => 17 ) ); ?>
								<?php esc_html_e( 'Add to basket', 'treats' ); ?>
							</button>
						</form>
					<?php endif; ?>

					<ul class="product__facts">
						<?php if ( null !== $treats_stock && $treats_stock > 0 && $treats_stock <= 5 ) : ?>
							<li>
								<?php treats_icon( 'tag', array( 'size' => 17 ) ); ?>
								<?php
								printf(
									/* translators: %d: number remaining. */
									esc_html( _n( 'Only %d left in stock', 'Only %d left in stock', (int) $treats_stock, 'treats' ) ),
									(int) $treats_stock
								);
								?>
							</li>
						<?php endif; ?>

						<li>
							<?php treats_icon( 'bag', array( 'size' => 17 ) ); ?>
							<?php if ( $treats_collect || ! treats_postage_settings()['offers_post'] ) : ?>
								<?php esc_html_e( 'Collection from the café only', 'treats' ); ?>
							<?php elseif ( $treats_postage > 0 ) : ?>
								<?php
								printf(
									/* translators: %s: postage price. */
									esc_html__( 'Free collection, or posted from %s', 'treats' ),
									esc_html( treats_money( $treats_postage ) )
								);
								?>
							<?php else : ?>
								<?php esc_html_e( 'Free collection, or posted out to you', 'treats' ); ?>
							<?php endif; ?>
						</li>

						<?php if ( '' !== $treats_sku ) : ?>
							<li>
								<?php treats_icon( 'check', array( 'size' => 17 ) ); ?>
								<?php
								printf(
									/* translators: %s: product code. */
									esc_html__( 'Product code %s', 'treats' ),
									esc_html( $treats_sku )
								);
								?>
							</li>
						<?php endif; ?>

						<li>
							<?php treats_icon( 'phone', array( 'size' => 17 ) ); ?>
							<?php esc_html_e( 'Questions? Ring us on', 'treats' ); ?>
							<a href="tel:<?php echo esc_attr( treats_get_phone_link() ); ?>"><?php echo esc_html( treats_get_phone() ); ?></a>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</article>

	<?php
	$treats_related = get_posts(
		array(
			'post_type'      => 'treats_product',
			'posts_per_page' => 3,
			'post__not_in'   => array( $treats_id ),
			'orderby'        => 'rand',
			'no_found_rows'  => true,
		)
	);

	if ( $treats_related ) :
		?>
		<section class="section section--sm">
			<div class="container container--wide">
				<?php
				treats_section_heading(
					array(
						'eyebrow' => __( 'You might also like', 'treats' ),
						'title'   => __( 'More from the shop', 'treats' ),
						'center'  => true,
					)
				);
				?>

				<ul class="product-grid">
					<?php foreach ( $treats_related as $treats_index => $treats_other ) : ?>
						<?php
						get_template_part(
							'template-parts/shop/product-card',
							null,
							array(
								'product_id' => $treats_other->ID,
								'delay'      => $treats_index * 70,
							)
						);
						?>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<?php
	endif;

endwhile;

get_footer();
