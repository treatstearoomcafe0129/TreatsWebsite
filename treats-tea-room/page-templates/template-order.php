<?php
/**
 * Template Name: Order Confirmation
 * Template Post Type: page
 *
 * Reached only with a reference and its key, so one customer cannot read
 * another's order by changing the number in the address bar.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

$treats_order = treats_order_from_request();
?>

<section class="section order-confirm-page">
	<div class="container container--content">

		<?php if ( ! $treats_order ) : ?>

			<div class="order-confirm order-confirm--missing">
				<h1 class="order-confirm__title"><?php esc_html_e( 'We could not find that order', 'treats' ); ?></h1>
				<p><?php esc_html_e( 'The link may have been shortened or copied incompletely. Your confirmation email has a working one — and if anything looks wrong, ring us and we will sort it out.', 'treats' ); ?></p>

				<div class="order-confirm__actions">
					<a class="btn btn--primary" href="tel:<?php echo esc_attr( treats_get_phone_link() ); ?>">
						<?php echo esc_html( treats_get_phone() ); ?>
					</a>
					<a class="btn btn--secondary" href="<?php echo esc_url( treats_shop_url() ); ?>">
						<?php esc_html_e( 'Back to the shop', 'treats' ); ?>
					</a>
				</div>
			</div>

		<?php else : ?>

			<?php
			$treats_reference  = (string) get_post_meta( $treats_order, '_treats_reference', true );
			$treats_fulfilment = (string) get_post_meta( $treats_order, '_treats_fulfilment', true );
			$treats_items      = treats_order_items( $treats_order );
			$treats_postage    = (int) get_post_meta( $treats_order, '_treats_postage', true );
			$treats_receipt    = (string) get_post_meta( $treats_order, '_treats_receipt', true );
			$treats_collect_on = (string) get_post_meta( $treats_order, '_treats_collect_date', true );
			$treats_email      = (string) get_post_meta( $treats_order, '_treats_email', true );
			?>

			<div class="order-confirm">
				<span class="order-confirm__tick" aria-hidden="true">
					<?php treats_icon( 'check', array( 'size' => 30 ) ); ?>
				</span>

				<h1 class="order-confirm__title"><?php esc_html_e( 'Thank you — that is all paid for', 'treats' ); ?></h1>

				<p class="order-confirm__lead">
					<?php
					printf(
						/* translators: %s: email address. */
						esc_html__( 'We have sent a confirmation to %s. Your reference is below — quote it if you need to ring us about this order.', 'treats' ),
						'<strong>' . esc_html( $treats_email ) . '</strong>'
					);
					?>
				</p>

				<p class="order-confirm__reference">
					<span><?php esc_html_e( 'Reference', 'treats' ); ?></span>
					<strong><?php echo esc_html( $treats_reference ); ?></strong>
				</p>

				<div class="info-card order-confirm__card">
					<h2 class="glass-title order-confirm__heading">
						<?php
						echo 'post' === $treats_fulfilment
							? esc_html__( 'We are posting it', 'treats' )
							: esc_html__( 'Collecting from us', 'treats' );
						?>
					</h2>

					<?php if ( 'post' === $treats_fulfilment ) : ?>
						<address class="order-confirm__address">
							<?php
							$treats_address = array_filter(
								array(
									(string) get_post_meta( $treats_order, '_treats_address1', true ),
									(string) get_post_meta( $treats_order, '_treats_address2', true ),
									(string) get_post_meta( $treats_order, '_treats_city', true ),
									(string) get_post_meta( $treats_order, '_treats_postcode', true ),
								)
							);

							echo esc_html( implode( ', ', $treats_address ) );
							?>
						</address>

						<p><?php echo esc_html( (string) get_theme_mod( 'treats_shop_dispatch_note', __( 'Posted orders are sent within three working days.', 'treats' ) ) ); ?></p>
					<?php else : ?>
						<address class="order-confirm__address"><?php echo esc_html( treats_get_address_line() ); ?></address>

						<?php if ( '' !== $treats_collect_on ) : ?>
							<p>
								<?php
								printf(
									/* translators: %s: collection date. */
									esc_html__( 'Ready for you on %s.', 'treats' ),
									esc_html( date_i18n( get_option( 'date_format' ), strtotime( $treats_collect_on ) ) )
								);
								?>
							</p>
						<?php else : ?>
							<p><?php esc_html_e( 'We will ring you as soon as it is ready.', 'treats' ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
				</div>

				<ul class="order-confirm__items">
					<?php foreach ( $treats_items as $treats_item ) : ?>
						<li>
							<span>
								<?php echo esc_html( $treats_item['title'] ); ?>
								<?php if ( ! empty( $treats_item['option_label'] ) ) : ?>
									<span class="order-confirm__option"><?php echo esc_html( $treats_item['option_label'] ); ?></span>
								<?php endif; ?>
								<span class="order-confirm__qty">&times;<?php echo esc_html( (string) $treats_item['quantity'] ); ?></span>
							</span>
							<span><?php echo esc_html( treats_money( (int) $treats_item['total'] ) ); ?></span>
						</li>
					<?php endforeach; ?>

					<?php if ( $treats_postage > 0 ) : ?>
						<li>
							<span><?php esc_html_e( 'Postage', 'treats' ); ?></span>
							<span><?php echo esc_html( treats_money( $treats_postage ) ); ?></span>
						</li>
					<?php endif; ?>

					<li class="order-confirm__paid">
						<span><?php esc_html_e( 'Paid', 'treats' ); ?></span>
						<span><?php echo esc_html( treats_money( (int) get_post_meta( $treats_order, '_treats_total', true ) ) ); ?></span>
					</li>
				</ul>

				<div class="order-confirm__actions">
					<?php if ( '' !== $treats_receipt ) : ?>
						<a class="btn btn--secondary" href="<?php echo esc_url( $treats_receipt ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( 'Your Square receipt', 'treats' ); ?>
						</a>
					<?php endif; ?>

					<a class="btn btn--primary" href="<?php echo esc_url( treats_shop_url() ); ?>">
						<?php esc_html_e( 'Back to the shop', 'treats' ); ?>
					</a>
				</div>
			</div>

		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
