<?php
/**
 * Template Name: FAQ
 * Template Post Type: page
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

$faqs   = treats_get_faqs();
$topics = get_terms(
	array(
		'taxonomy'   => 'treats_faq_topic',
		'hide_empty' => true,
	)
);

if ( is_wp_error( $topics ) ) {
	$topics = array();
}

while ( have_posts() ) :
	the_post();

	treats_page_hero(
		array(
			'eyebrow'  => (string) treats_meta( 'eyebrow', 0, __( 'Good to know', 'treats' ) ),
			'title'    => get_the_title(),
			'intro'    => (string) treats_meta( 'intro' ),
			'image_id' => (int) get_post_thumbnail_id(),
		)
	);
	?>

	<section class="section">
		<div class="container container--content">
			<?php
			$treats_content = trim( get_the_content() );

			if ( '' !== $treats_content ) {
				?>
				<div class="entry-content" style="margin-bottom:3rem">
					<?php the_content(); ?>
				</div>
				<?php
			}
			?>

			<?php if ( $faqs ) : ?>
				<?php if ( count( $topics ) > 1 ) : ?>
					<div class="tabs" role="group" aria-label="<?php esc_attr_e( 'Filter questions by topic', 'treats' ); ?>" data-filter-group data-filter-target=".accordion__item"<?php treats_reveal(); ?>>
						<button class="tab is-active" type="button" data-filter="" aria-pressed="true"><?php esc_html_e( 'All questions', 'treats' ); ?></button>
						<?php foreach ( $topics as $topic ) : ?>
							<button class="tab" type="button" data-filter="<?php echo esc_attr( $topic->slug ); ?>" aria-pressed="false"><?php echo esc_html( $topic->name ); ?></button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="accordion" data-single="false"<?php treats_reveal( 60 ); ?>>
					<?php foreach ( $faqs as $index => $faq ) : ?>
						<?php
						$faq_topics = wp_get_object_terms( $faq->ID, 'treats_faq_topic', array( 'fields' => 'slugs' ) );

						if ( is_wp_error( $faq_topics ) ) {
							$faq_topics = array();
						}

						$panel_id = 'faq-panel-' . $faq->ID;
						?>
						<div class="accordion__item" data-topics="<?php echo esc_attr( implode( ' ', $faq_topics ) ); ?>">
							<h2 style="margin:0">
								<button class="accordion__trigger" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $panel_id ); ?>">
									<span><?php echo esc_html( get_the_title( $faq ) ); ?></span>
									<span class="accordion__icon" aria-hidden="true"></span>
								</button>
							</h2>

							<div class="accordion__panel" id="<?php echo esc_attr( $panel_id ); ?>">
								<div>
									<div class="accordion__content">
										<?php echo wp_kses_post( apply_filters( 'the_content', $faq->post_content ) ); ?>
									</div>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="lede"><?php esc_html_e( 'We are writing these up. In the meantime, please call or email us — we are happy to help.', 'treats' ); ?></p>
			<?php endif; ?>

			<div class="info-card" style="margin-top:3rem"<?php treats_reveal(); ?>>
				<h2 class="info-card__title"><?php esc_html_e( 'Still not answered?', 'treats' ); ?></h2>
				<p style="font-size:var(--fs-sm);color:var(--c-ink-soft);margin-bottom:1.25rem">
					<?php esc_html_e( 'Give us a ring or send a message — a real person will get back to you.', 'treats' ); ?>
				</p>
				<div class="actions">
					<?php if ( treats_get_phone() ) : ?>
						<a class="btn btn--primary btn--sm" href="tel:<?php echo esc_attr( treats_get_phone_link() ); ?>">
							<?php treats_icon( 'phone', array( 'size' => 16 ) ); ?>
							<?php echo esc_html( treats_get_phone() ); ?>
						</a>
					<?php endif; ?>
					<a class="btn btn--secondary btn--sm" href="<?php echo esc_url( treats_get_template_page_url( 'page-templates/template-contact.php' ) ); ?>">
						<?php esc_html_e( 'Contact us', 'treats' ); ?>
					</a>
				</div>
			</div>
		</div>
	</section>

	<?php
endwhile;

get_footer();
