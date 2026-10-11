<?php
/**
 * Template Name: Evening Venue Hire
 * Template Post Type: page
 *
 * Private evening bookings: the room to yourself, a price, two food packages
 * and a way to ask. Every figure comes from the Customizer so prices can move
 * without anyone touching a template.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

get_header();

/**
 * Turn a comma separated Customizer field into a list.
 *
 * @param string $value Raw setting.
 * @return string[]
 */
function treats_event_list( $value ) {
	return array_values( array_filter( array_map( 'trim', explode( ',', (string) $value ) ) ) );
}

$hire_price   = (string) get_theme_mod( 'treats_event_price', '250' );
$hire_note    = (string) get_theme_mod( 'treats_event_price_note', __( 'Includes 2 staff', 'treats' ) );
$hours        = (string) get_theme_mod( 'treats_event_hours', __( 'Available every day, 6.30pm – 10pm', 'treats' ) );
$event_image  = (string) get_theme_mod( 'treats_event_image', '' );

$packages = array(
	array(
		'title' => __( 'Afternoon Tea Package', 'treats' ),
		'price' => (string) get_theme_mod( 'treats_event_tea_price', '16.50' ),
		'items' => treats_event_list( get_theme_mod( 'treats_event_tea_items', 'Sandwich selection, Mini pies & quiche, Scone with preserve & clotted cream, Cake, Any hot drink' ) ),
		'icon'  => 'teapot',
	),
	array(
		'title' => __( 'Finger Food Package', 'treats' ),
		'price' => (string) get_theme_mod( 'treats_event_finger_price', '12.50' ),
		'items' => treats_event_list( get_theme_mod( 'treats_event_finger_items', 'Selection of sandwiches, Mini pies & quiche, Crisps & dips' ) ),
		'icon'  => 'cup',
	),
);

while ( have_posts() ) :
	the_post();

	treats_page_hero(
		array(
			'eyebrow'  => (string) treats_meta( 'eyebrow', 0, __( 'Private bookings', 'treats' ) ),
			'title'    => get_the_title(),
			'intro'    => (string) treats_meta( 'intro', 0, get_theme_mod( 'treats_event_intro', '' ) ),
			'image_id' => (int) get_post_thumbnail_id(),
		)
	);
	?>

	<?php
	// With no photograph and nothing written on the page there is no left
	// column to speak of, and the hire card should not sit next to a hole.
	$has_body = $event_image || '' !== trim( (string) get_the_content() );
	?>
	<section class="section">
		<div class="container container--wide">
			<div class="<?php echo $has_body ? 'split split--wide-left' : 'event-hire-solo'; ?>" style="align-items:start">

				<?php if ( $has_body ) : ?>
					<div<?php treats_reveal(); ?>>
						<?php if ( $event_image ) : ?>
							<img class="event-image" src="<?php echo esc_url( $event_image ); ?>" alt="" loading="lazy" decoding="async">
						<?php endif; ?>

						<div class="entry-content">
							<?php the_content(); ?>
						</div>
					</div>
				<?php endif; ?>

				<aside<?php treats_reveal( 100 ); ?>>
					<div class="info-card event-hire">
						<p class="event-hire__hours"><?php echo esc_html( $hours ); ?></p>

						<h2 class="glass-title"><?php esc_html_e( 'Full venue hire', 'treats' ); ?></h2>

						<p class="event-hire__price"><?php echo esc_html( treats_format_price( $hire_price ) ); ?></p>

						<?php if ( '' !== $hire_note ) : ?>
							<p class="event-hire__note"><?php echo esc_html( $hire_note ); ?></p>
						<?php endif; ?>

						<p class="event-hire__text">
							<?php esc_html_e( 'Exclusive use of the whole tea room for your evening.', 'treats' ); ?>
						</p>

						<a class="btn btn--primary btn--lg" href="#event-enquiry">
							<?php esc_html_e( 'Enquire about a date', 'treats' ); ?>
						</a>
					</div>
				</aside>
			</div>
		</div>
	</section>

	<section class="section section--sm">
		<div class="container container--wide">
			<?php
			treats_section_heading(
				array(
					'eyebrow' => __( 'Food packages', 'treats' ),
					'title'   => __( 'Something to eat, if you would like it', 'treats' ),
					'text'    => __( 'Add catering to your evening, priced per guest. Bespoke menus are available too — tell us what you have in mind.', 'treats' ),
					'center'  => true,
				)
			);
			?>

			<div class="package-grid">
				<?php foreach ( $packages as $index => $package ) : ?>
					<article class="card package"<?php treats_reveal( $index * 80 ); ?>>
						<span class="icon-badge" aria-hidden="true">
							<?php treats_icon( $package['icon'], array( 'size' => 30 ) ); ?>
						</span>

						<h3 class="package__title"><?php echo esc_html( $package['title'] ); ?></h3>

						<p class="package__price">
							<?php echo esc_html( treats_format_price( $package['price'] ) ); ?>
							<span class="package__per"><?php esc_html_e( 'per person', 'treats' ); ?></span>
						</p>

						<ul class="package__list">
							<?php foreach ( $package['items'] as $line ) : ?>
								<li><?php echo esc_html( $line ); ?></li>
							<?php endforeach; ?>
						</ul>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section section--sm" id="event-enquiry">
		<div class="container container--content">
			<div class="form-card"<?php treats_reveal(); ?>>
				<header class="form-card__header">
					<h2 class="form-card__title"><?php esc_html_e( 'Enquire about an evening', 'treats' ); ?></h2>
					<p class="text-mute" style="font-size:var(--fs-sm)">
						<?php esc_html_e( 'Tell us the date and roughly how many of you there will be, and we will come back with availability and a quote.', 'treats' ); ?>
					</p>
				</header>

				<?php treats_form_notice(); ?>

				<form class="form" method="post" action="<?php echo esc_url( treats_form_action() ); ?>" data-treats-form="event">
					<div data-form-response hidden></div>

					<div class="form__grid form__grid--2">
						<div class="field">
							<label class="field__label" for="event-name"><?php esc_html_e( 'Your name', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
							<input class="input" type="text" id="event-name" name="treats_name" autocomplete="name" required>
							<span class="field__error"></span>
						</div>

						<div class="field">
							<label class="field__label" for="event-email"><?php esc_html_e( 'Email', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
							<input class="input" type="email" id="event-email" name="treats_email" autocomplete="email" required>
							<span class="field__error"></span>
						</div>

						<div class="field">
							<label class="field__label" for="event-phone"><?php esc_html_e( 'Phone', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
							<input class="input" type="tel" id="event-phone" name="treats_phone" autocomplete="tel" required>
							<span class="field__error"></span>
						</div>

						<div class="field">
							<label class="field__label" for="event-date"><?php esc_html_e( 'Preferred date', 'treats' ); ?></label>
							<input class="input" type="date" id="event-date" name="treats_date" min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
							<span class="field__error"></span>
						</div>

						<div class="field">
							<label class="field__label" for="event-party"><?php esc_html_e( 'Approximate guests', 'treats' ); ?></label>
							<input class="input" type="number" id="event-party" name="treats_party" min="1" max="200" inputmode="numeric">
							<span class="field__error"></span>
						</div>

						<div class="field">
							<label class="field__label" for="event-occasion"><?php esc_html_e( 'Occasion', 'treats' ); ?></label>
							<input class="input" type="text" id="event-occasion" name="treats_occasion" placeholder="<?php esc_attr_e( 'Birthday, celebration, meeting…', 'treats' ); ?>">
							<span class="field__error"></span>
						</div>
					</div>

					<div class="field">
						<label class="field__label" for="event-package"><?php esc_html_e( 'Food package', 'treats' ); ?></label>
						<select class="input" id="event-package" name="treats_package">
							<option value="<?php esc_attr_e( 'Not sure yet', 'treats' ); ?>"><?php esc_html_e( 'Not sure yet', 'treats' ); ?></option>
							<?php foreach ( $packages as $package ) : ?>
								<option value="<?php echo esc_attr( $package['title'] ); ?>"><?php echo esc_html( $package['title'] ); ?></option>
							<?php endforeach; ?>
							<option value="<?php esc_attr_e( 'Bespoke menu', 'treats' ); ?>"><?php esc_html_e( 'Bespoke menu', 'treats' ); ?></option>
							<option value="<?php esc_attr_e( 'Venue only, no food', 'treats' ); ?>"><?php esc_html_e( 'Venue only, no food', 'treats' ); ?></option>
						</select>
					</div>

					<div class="field">
						<label class="field__label" for="event-message"><?php esc_html_e( 'Anything else we should know', 'treats' ); ?><span class="required" aria-hidden="true">*</span></label>
						<textarea class="input" id="event-message" name="treats_message" rows="5" required></textarea>
						<span class="field__error"></span>
					</div>

					<?php treats_form_fields( 'event' ); ?>

					<div class="form__footer">
						<button class="btn btn--primary btn--lg" type="submit"><?php esc_html_e( 'Send enquiry', 'treats' ); ?></button>
						<p class="form__note">
							<?php
							printf(
								/* translators: %s: email address. */
								esc_html__( 'Prefer email? Write to us at %s, or speak to a member of staff next time you are in.', 'treats' ),
								esc_html( treats_get_email() )
							);
							?>
						</p>
					</div>
				</form>
			</div>
		</div>
	</section>

	<?php
endwhile;

get_footer();
