<?php
/**
 * Menu category cards.
 *
 * Cards link to the page that uses each menu category, so they always land on
 * a real, editable page rather than a bare taxonomy archive.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

$categories = array(
	'breakfast-brunch' => __( 'Cooked to order from the moment we open, all day long.', 'treats' ),
	'afternoon-tea'    => __( 'Sandwiches, warm scones and a tier of our own cakes.', 'treats' ),
	'lunch'            => __( 'Homemade pies, quiches, soups and proper sandwiches.', 'treats' ),
	'cakes-desserts'   => __( 'The counter Durham knows us for.', 'treats' ),
	'drinks'           => __( 'Loose leaf tea, speciality coffee and real hot chocolate.', 'treats' ),
);

$terms = array();

foreach ( $categories as $slug => $description ) {
	$term = get_term_by( 'slug', $slug, 'treats_menu_category' );

	if ( $term instanceof WP_Term ) {
		$terms[] = array(
			'term' => $term,
			'text' => $description,
			'url'  => treats_menu_page_url( $slug ),
		);
	}
}

if ( ! $terms ) {
	return;
}
?>
<section class="section section--tint" id="menus">
	<div class="container container--wide">
		<?php
		treats_section_heading(
			array(
				'eyebrow' => __( 'Our menus', 'treats' ),
				'title'   => __( 'Everything is made here, every day', 'treats' ),
				'intro'   => __( 'Five menus, one kitchen. Whatever time you arrive, there is something worth stopping for.', 'treats' ),
			)
		);
		?>

		<div class="menu-grid grid grid--3">
			<?php foreach ( $terms as $index => $entry ) : ?>
				<?php
				$thumb_id = (int) get_term_meta( $entry['term']->term_id, 'treats_image_id', true );
				$tall     = $index < 2;
				?>
				<a class="menu-card<?php echo $tall ? ' menu-card--tall' : ''; ?>" href="<?php echo esc_url( $entry['url'] ); ?>"<?php treats_reveal( min( $index * 70, 280 ), 'scale' ); ?>>
					<?php
					treats_image(
						$thumb_id,
						$tall ? 'treats-card-tall' : 'treats-card',
						array(
							'class' => 'menu-card__media',
							'ratio' => 'auto',
							'sizes' => '(max-width: 899px) 100vw, 33vw',
							'alt'   => '',
						)
					);
					?>

					<span class="menu-card__body">
						<span class="menu-card__title"><?php echo esc_html( $entry['term']->name ); ?></span>
						<span class="menu-card__text"><?php echo esc_html( $entry['text'] ); ?></span>
						<span class="menu-card__link">
							<?php esc_html_e( 'See the menu', 'treats' ); ?>
							<?php treats_icon( 'arrow-right', array( 'size' => 16 ) ); ?>
						</span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
