<?php
/**
 * Afternoon tea feature — the set-piece section of the home page.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$term = get_term_by( 'slug', 'afternoon-tea', 'treats_menu_category' );
$url  = treats_menu_page_url( 'afternoon-tea' );

$includes = array(
	__( 'A tier of finger sandwiches, cut fresh to order', 'treats' ),
	__( 'Warm fruit scones, Yorkshire jam and clotted cream', 'treats' ),
	__( 'A tier of cakes from our own counter', 'treats' ),
	__( 'A pot of loose leaf tea, refilled as often as you like', 'treats' ),
);
?>
<section class="section section--deep">
	<div class="container container--wide">
		<div class="split split--wide-left">
			<div<?php treats_reveal(); ?>>
				<p class="eyebrow"><?php esc_html_e( 'The Treats Afternoon Tea', 'treats' ); ?></p>

				<h2 style="margin-bottom:1.5rem"><?php esc_html_e( 'The Durham afternoon, done properly', 'treats' ); ?></h2>

				<p class="lede" style="margin-bottom:2rem">
					<?php esc_html_e( 'Three tiers, a proper pot of tea and no rush. Served every day from midday — booking recommended, especially at weekends.', 'treats' ); ?>
				</p>

				<ul style="list-style:none;padding:0;display:grid;gap:.85rem;margin-bottom:2.5rem">
					<?php foreach ( $includes as $line ) : ?>
						<li style="display:flex;gap:.75rem;align-items:flex-start">
							<span style="color:var(--sage-300);flex-shrink:0;margin-top:.15rem"><?php treats_icon( 'check', array( 'size' => 18 ) ); ?></span>
							<span><?php echo esc_html( $line ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>

				<div class="actions">
					<a class="btn btn--light" href="<?php echo esc_url( treats_booking_url() ); ?>"><?php esc_html_e( 'Book afternoon tea', 'treats' ); ?></a>
					<a class="btn btn--outline-light" href="<?php echo esc_url( $url ); ?>">
						<?php esc_html_e( 'See what is included', 'treats' ); ?>
						<?php treats_icon( 'arrow-right', array( 'size' => 16 ) ); ?>
					</a>
				</div>
			</div>

			<div<?php treats_reveal( 100, 'scale' ); ?>>
				<?php
				$image_id = $term instanceof WP_Term ? (int) get_term_meta( $term->term_id, 'treats_image_id', true ) : 0;

				treats_image(
					$image_id,
					'treats-card-tall',
					array(
						'class' => 'media--round media--soft',
						'ratio' => '4 / 5',
						'sizes' => '(max-width: 899px) 100vw, 45vw',
						'alt'   => __( 'A three-tier afternoon tea at Treats', 'treats' ),
					)
				);
				?>
			</div>
		</div>
	</div>
</section>
