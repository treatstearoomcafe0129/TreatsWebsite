<?php
/**
 * The shop.
 *
 * Also serves the product category archives, which is why the heading and
 * intro come from the queried object rather than being hard coded.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

$term      = is_tax( 'treats_product_cat' ) ? get_queried_object() : null;
$title     = $term ? $term->name : __( 'The shop', 'treats' );
$intro     = $term && $term->description
	? $term->description
	: __( 'A few of the things people ask us for by name — cast iron teapots, trivets, and gift vouchers for someone who deserves an afternoon off.', 'treats' );
$categories = get_terms(
	array(
		'taxonomy'   => 'treats_product_cat',
		'hide_empty' => true,
	)
);

if ( is_wp_error( $categories ) ) {
	$categories = array();
}

treats_page_hero(
	array(
		'eyebrow' => __( 'Take a piece of Treats home', 'treats' ),
		'title'   => $title,
		'intro'   => $intro,
	)
);
?>

<section class="section">
	<div class="container container--wide">

		<?php if ( count( $categories ) > 1 ) : ?>
			<nav class="shop-filters" aria-label="<?php esc_attr_e( 'Product categories', 'treats' ); ?>">
				<a class="tab<?php echo $term ? '' : ' is-active'; ?>" href="<?php echo esc_url( treats_shop_url() ); ?>"<?php echo $term ? '' : ' aria-current="page"'; ?>>
					<?php esc_html_e( 'Everything', 'treats' ); ?>
				</a>

				<?php foreach ( $categories as $category ) : ?>
					<?php $active = $term && $term->term_id === $category->term_id; ?>
					<a class="tab<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $category ) ); ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>>
						<?php echo esc_html( $category->name ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<ul class="product-grid">
				<?php
				$treats_index = 0;

				while ( have_posts() ) :
					the_post();

					get_template_part(
						'template-parts/shop/product-card',
						null,
						array(
							'product_id' => get_the_ID(),
							'delay'      => min( $treats_index * 60, 280 ),
						)
					);

					$treats_index++;
				endwhile;
				?>
			</ul>

			<?php
			the_posts_pagination(
				array(
					'mid_size'  => 1,
					'prev_text' => __( 'Previous', 'treats' ),
					'next_text' => __( 'Next', 'treats' ),
				)
			);
			?>
		<?php else : ?>
			<div class="shop-empty">
				<?php treats_icon( 'bag', array( 'size' => 44 ) ); ?>
				<p><?php esc_html_e( 'Nothing in the shop just yet. Do come back — or ask us in the café, we usually have something on the shelf.', 'treats' ); ?></p>
				<a class="btn btn--secondary" href="<?php echo esc_url( treats_get_template_page_url( 'page-templates/template-contact.php' ) ); ?>">
					<?php esc_html_e( 'Get in touch', 'treats' ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php
get_template_part(
	'template-parts/components/cta-banner',
	null,
	array(
		'eyebrow' => __( 'Buying a present?', 'treats' ),
		'title'   => __( 'A gift voucher never comes back the wrong size', 'treats' ),
		'text'    => __( 'Afternoon tea for two, a long lunch, or simply an amount you choose. We will post it or you can collect it.', 'treats' ),
		'actions' => array(
			array(
				'label' => __( 'Gift vouchers', 'treats' ),
				'url'   => treats_get_template_page_url( 'page-templates/template-vouchers.php' ),
				'style' => 'btn--light',
			),
			array(
				'label' => __( 'Find us', 'treats' ),
				'url'   => treats_get_template_page_url( 'page-templates/template-contact.php' ),
				'style' => 'btn--outline-light',
			),
		),
	)
);

get_footer();
