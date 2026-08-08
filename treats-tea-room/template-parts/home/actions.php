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

$afternoon = treats_menu_page_url( 'afternoon-tea' );

$cards = array(
	array(
		'icon'  => 'cup',
		'title' => __( 'Afternoon Tea', 'treats' ),
		'text'  => __( 'Our signature experience. Beautifully presented and made to be savoured.', 'treats' ),
		'label' => __( 'Afternoon tea', 'treats' ),
		'url'   => $afternoon,
	),
	array(
		'icon'  => 'calendar',
		'title' => __( 'Book a Table', 'treats' ),
		'text'  => __( 'Planning something special? Reserve your table today and let us take care of the rest.', 'treats' ),
		'label' => __( 'Book now', 'treats' ),
		'url'   => treats_booking_url(),
	),
	array(
		'icon'  => 'gift',
		'title' => __( 'Gift Vouchers', 'treats' ),
		'text'  => __( 'The perfect gift for any occasion. Treat someone to something special.', 'treats' ),
		'label' => __( 'Buy a voucher', 'treats' ),
		'url'   => treats_vouchers_url(),
	),
	array(
		'icon'  => 'user',
		'title' => __( 'Staff Portal', 'treats' ),
		'text'  => __( 'Access important updates, schedules, documents and resources.', 'treats' ),
		'label' => __( 'Staff login', 'treats' ),
		'url'   => wp_login_url(),
	),
);

$wide = array(
	array(
		'icon'  => 'bag',
		'title' => __( 'Products', 'treats' ),
		'text'  => __( 'Discover our handpicked products and treats, available to buy online.', 'treats' ),
		'label' => __( 'View products', 'treats' ),
		'url'   => treats_menu_page_url( 'cakes-desserts' ),
	),
	array(
		'icon'  => 'tag',
		'title' => __( 'News & Offers', 'treats' ),
		'text'  => __( 'Stay up to date with our latest news, offers and seasonal highlights.', 'treats' ),
		'label' => __( 'View offers', 'treats' ),
		'url'   => get_permalink( (int) get_option( 'page_for_posts' ) ) ?: home_url( '/' ),
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
