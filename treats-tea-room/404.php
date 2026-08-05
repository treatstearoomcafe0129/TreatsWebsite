<?php
/**
 * 404 — page not found.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

$treats_links = array(
	array(
		'icon'  => 'cup',
		'label' => __( 'Our menus', 'treats' ),
		'text'  => __( 'Breakfast, lunch, afternoon tea and the cake counter.', 'treats' ),
		'url'   => treats_get_template_page_url( 'page-templates/template-menu.php' ),
	),
	array(
		'icon'  => 'calendar',
		'label' => __( 'Book a table', 'treats' ),
		'text'  => __( 'Tell us when you would like to come and we will confirm.', 'treats' ),
		'url'   => treats_booking_url(),
	),
	array(
		'icon'  => 'pin',
		'label' => __( 'Find us', 'treats' ),
		'text'  => __( 'Silver Street, Durham — a minute from the Market Place.', 'treats' ),
		'url'   => treats_get_template_page_url( 'page-templates/template-contact.php' ),
	),
);
?>

<section class="page-hero page-hero--center">
	<div class="container">
		<div class="page-hero__inner">
			<p class="eyebrow page-hero__eyebrow"><?php esc_html_e( 'Error 404', 'treats' ); ?></p>
			<h1 class="page-hero__title"><?php esc_html_e( 'We could not find that page', 'treats' ); ?></h1>
			<p class="page-hero__intro">
				<?php esc_html_e( 'It may have moved, or the link may have a typo in it. Here is where most people are heading.', 'treats' ); ?>
			</p>

			<div class="page-hero__actions" style="justify-content:center">
				<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to the home page', 'treats' ); ?></a>
			</div>
		</div>
	</div>
</section>

<section class="section">
	<div class="container container--wide">
		<div class="grid grid--3">
			<?php foreach ( $treats_links as $index => $link ) : ?>
				<a class="card" href="<?php echo esc_url( $link['url'] ); ?>" style="text-decoration:none"<?php treats_reveal( $index * 80 ); ?>>
					<div class="card__body">
						<span style="color:var(--c-accent)"><?php treats_icon( $link['icon'], array( 'size' => 26 ) ); ?></span>
						<h2 class="card__title"><?php echo esc_html( $link['label'] ); ?></h2>
						<p class="card__text"><?php echo esc_html( $link['text'] ); ?></p>
					</div>
				</a>
			<?php endforeach; ?>
		</div>

		<div style="max-width:28rem;margin:4rem auto 0">
			<?php get_search_form(); ?>
		</div>
	</div>
</section>

<?php
get_footer();
