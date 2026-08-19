<?php
/**
 * The shop, on the home page.
 *
 * Shows real products with their photographs rather than another icon tile —
 * a teapot people can see is worth more than the word "shop". Renders nothing
 * at all when there is nothing published to sell, so the home page never
 * carries an empty promise.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

if ( ! post_type_exists( 'treats_product' ) ) {
	return;
}

if ( ! get_theme_mod( 'treats_home_show_shop', true ) ) {
	return;
}

$treats_shop_pool = get_posts(
	array(
		'post_type'      => 'treats_product',
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'orderby'        => 'menu_order date',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	)
);

if ( ! $treats_shop_pool ) {
	return;
}

/*
 * Photographed products come first. This is a shop window: three cards
 * showing a teapot icon rather than a teapot would be worse than no window at
 * all. Nothing is hidden — the order the café sets still decides which
 * photographed product leads, and unphotographed ones fill any gap.
 */
$treats_with_photo = array();
$treats_without    = array();

foreach ( $treats_shop_pool as $treats_candidate ) {
	if ( treats_product_image_id( $treats_candidate->ID ) ) {
		$treats_with_photo[] = $treats_candidate;
	} else {
		$treats_without[] = $treats_candidate;
	}
}

$treats_shop_items = array_slice( array_merge( $treats_with_photo, $treats_without ), 0, 3 );
?>
<section class="section home-shop">
	<div class="container container--wide">
		<?php
		treats_section_heading(
			array(
				'eyebrow' => __( 'Take a piece of Treats home', 'treats' ),
				'title'   => (string) get_theme_mod( 'treats_home_shop_title', __( 'From our shop', 'treats' ) ),
				'text'    => (string) get_theme_mod(
					'treats_home_shop_text',
					__( 'Cast iron teapots, trivets and gift vouchers — posted out to you, or waiting behind the counter.', 'treats' )
				),
				'center'  => true,
			)
		);
		?>

		<ul class="product-grid home-shop__grid">
			<?php foreach ( $treats_shop_items as $treats_index => $treats_item ) : ?>
				<?php
				get_template_part(
					'template-parts/shop/product-card',
					null,
					array(
						'product_id' => $treats_item->ID,
						'delay'      => $treats_index * 70,
					)
				);
				?>
			<?php endforeach; ?>
		</ul>

		<p class="home-shop__more">
			<a class="btn btn--secondary" href="<?php echo esc_url( treats_shop_url() ); ?>">
				<?php esc_html_e( 'Visit the shop', 'treats' ); ?>
				<?php treats_icon( 'arrow-right', array( 'size' => 15 ) ); ?>
			</a>
		</p>
	</div>
</section>
