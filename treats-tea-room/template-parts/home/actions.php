<?php
/**
 * The action cards: four primary tiles, then two wider ones.
 *
 * Each tile is a frosted panel with a round gold-iconned badge, a small-caps
 * serif title, a gold rule and an outlined link.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

// Both point at the menu itself: the afternoon tea tile used to open the
// itemised afternoon tea page, which is no longer how the menu is published.
$afternoon = treats_menus_url();

/*
 * These are the four things a customer actually comes here to do. What was
 * here before included a "Staff Portal" tile pointing at the WordPress login
 * — a staff door on the shop front — and a "Products … available to buy
 * online" tile that led to the cakes menu, which is not a shop. Both are
 * gone. Evening venue hire took a place instead: it is a real service that
 * nothing on the site linked to.
 */
$cards = array(
	array(
		'icon'  => 'cup',
		'title' => __( 'Afternoon Tea', 'treats' ),
		'text'  => __( 'Our signature experience. Beautifully presented and made to be savoured.', 'treats' ),
		'label' => __( 'See the menu', 'treats' ),
		'url'   => $afternoon,
	),
	array(
		'icon'  => 'calendar',
		'title' => __( 'Book a Table', 'treats' ),
		'text'  => __( 'Planning something special? Reserve your table and let us take care of the rest.', 'treats' ),
		'label' => __( 'Book a table', 'treats' ),
		'url'   => treats_booking_url(),
	),
	array(
		'icon'  => 'gift',
		'title' => __( 'Gift Vouchers', 'treats' ),
		'text'  => __( 'The perfect gift for any occasion. Treat someone to something special.', 'treats' ),
		'label' => __( 'Gift vouchers', 'treats' ),
		'url'   => treats_vouchers_url(),
	),
	array(
		'icon'  => 'users',
		'title' => __( 'Evening Venue Hire', 'treats' ),
		'text'  => __( 'The whole tea room to yourselves, any evening of the week.', 'treats' ),
		'label' => __( 'Venue hire', 'treats' ),
		'url'   => treats_get_template_page_url( 'page-templates/template-events.php' ),
	),
);

$wide = array(
	array(
		'icon'  => 'teapot',
		'title' => __( 'Our Menus', 'treats' ),
		'text'  => __( 'Breakfast and brunch all day, lunch, afternoon tea, cakes and drinks.', 'treats' ),
		'label' => __( 'See all menus', 'treats' ),
		'url'   => treats_menus_url(),
	),
	array(
		'icon'  => 'pin',
		'title' => __( 'Find Us', 'treats' ),
		'text'  => __( 'On Silver Street, a minute from Durham Market Place. Opening hours and directions.', 'treats' ),
		'label' => __( 'Find us', 'treats' ),
		'url'   => treats_get_template_page_url( 'page-templates/template-contact.php' ),
	),
);
?>
<section class="section section--sm">
	<div class="container container--wide">
		<ul class="action-grid">
			<?php foreach ( $cards as $index => $card ) : ?>
				<li class="action-card glass"<?php treats_reveal( min( $index * 70, 280 ) ); ?>>
					<span class="icon-badge"><?php treats_icon( $card['icon'], array( 'size' => 30 ) ); ?></span>
					<h2 class="glass-title action-card__title"><?php echo esc_html( $card['title'] ); ?></h2>
					<span class="gold-rule" aria-hidden="true"></span>
					<p class="action-card__text"><?php echo esc_html( $card['text'] ); ?></p>
					<a class="btn btn--secondary btn--sm action-card__link" href="<?php echo esc_url( $card['url'] ); ?>">
						<?php echo esc_html( $card['label'] ); ?>
						<?php treats_icon( 'arrow-right', array( 'size' => 15 ) ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

		<ul class="action-grid action-grid--wide">
			<?php foreach ( $wide as $index => $card ) : ?>
				<li class="action-card action-card--row glass"<?php treats_reveal( min( $index * 80, 160 ) ); ?>>
					<span class="icon-badge icon-badge--sm"><?php treats_icon( $card['icon'], array( 'size' => 24 ) ); ?></span>
					<div class="action-card__body">
						<h2 class="glass-title action-card__title"><?php echo esc_html( $card['title'] ); ?></h2>
						<p class="action-card__text"><?php echo esc_html( $card['text'] ); ?></p>
						<a class="btn btn--secondary btn--sm action-card__link" href="<?php echo esc_url( $card['url'] ); ?>">
							<?php echo esc_html( $card['label'] ); ?>
							<?php treats_icon( 'arrow-right', array( 'size' => 15 ) ); ?>
						</a>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
