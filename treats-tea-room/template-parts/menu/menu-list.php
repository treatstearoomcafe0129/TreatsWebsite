<?php
/**
 * A full menu: filter controls plus grouped sections.
 *
 * @package Treats
 *
 * @var array $args {
 *     @type string $category   Parent menu category slug. Empty for everything.
 *     @type bool   $show_search
 *     @type bool   $show_filters
 *     @type bool   $show_images
 * }
 */

defined( 'ABSPATH' ) || exit;

$config = wp_parse_args(
	$args ?? array(),
	array(
		'category'     => '',
		'show_search'  => true,
		'show_filters' => true,
		'show_images'  => true,
	)
);

$items = treats_get_menu_items( $config['category'] );

if ( ! $items ) {
	?>
	<p class="lede text-center"><?php esc_html_e( 'Our menu is being updated. Please call us and we will happily talk you through what is on today.', 'treats' ); ?></p>
	<?php
	return;
}

$parent_id = treats_menu_category_term_id( $config['category'] );
$groups    = treats_group_menu_items( $items, $parent_id );
$collect   = (bool) get_theme_mod( 'treats_collect_enabled', true );

$dietary_terms = get_terms(
	array(
		'taxonomy'   => 'treats_dietary',
		'hide_empty' => true,
	)
);

if ( is_wp_error( $dietary_terms ) ) {
	$dietary_terms = array();
}
?>
<div data-menu-root>
	<?php if ( $config['show_filters'] || $config['show_search'] ) : ?>
		<div class="menu-controls">
			<div class="container menu-controls__inner">
				<?php if ( $config['show_filters'] ) : ?>
					<div class="menu-filters" role="group" aria-label="<?php esc_attr_e( 'Filter the menu', 'treats' ); ?>">
						<?php
						/*
						 * There is no "Everything" button. The menu starts
						 * unfiltered, so a control whose only job is to restore
						 * the state you are already in is noise — and on a phone
						 * it was the first thing in the row, pushing the real
						 * categories off screen. Pressing an active filter again
						 * clears it; see js/menu.js.
						 */
						?>
						<?php foreach ( $groups as $group ) : ?>
							<?php if ( ! $group['term'] ) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<button class="tab" type="button" aria-pressed="false" data-menu-filter="<?php echo esc_attr( $group['term']->slug ); ?>">
								<?php echo esc_html( $group['term']->name ); ?>
							</button>
						<?php endforeach; ?>

						<?php foreach ( $dietary_terms as $term ) : ?>
							<?php if ( ! in_array( $term->slug, array( 've', 'gf' ), true ) ) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<button class="tab" type="button" aria-pressed="false" data-menu-filter="<?php echo esc_attr( $term->slug ); ?>">
								<?php echo esc_html( $term->name ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( $config['show_search'] ) : ?>
					<div class="menu-search">
						<span class="menu-search__icon"><?php treats_icon( 'search', array( 'size' => 17 ) ); ?></span>
						<label class="screen-reader-text" for="menu-search-field"><?php esc_html_e( 'Search the menu', 'treats' ); ?></label>
						<input type="search" id="menu-search-field" placeholder="<?php esc_attr_e( 'Search the menu…', 'treats' ); ?>" data-menu-search autocomplete="off">
						<button class="menu-search__clear" type="button" data-menu-search-clear aria-label="<?php esc_attr_e( 'Clear search', 'treats' ); ?>">
							<?php treats_icon( 'close', array( 'size' => 15 ) ); ?>
						</button>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="container container--wide">
		<?php foreach ( $groups as $index => $group ) : ?>
			<section class="menu-section"<?php treats_reveal( min( $index * 60, 240 ) ); ?>>
				<?php if ( $group['term'] ) : ?>
					<header class="menu-section__header">
						<h2 class="menu-section__title"><?php echo esc_html( $group['term']->name ); ?></h2>
						<span class="menu-section__count" data-section-count="<?php echo esc_attr( (string) count( $group['items'] ) ); ?>">
							<?php
							printf(
								/* translators: %s: number of dishes. */
								esc_html( _n( '%s dish', '%s dishes', count( $group['items'] ), 'treats' ) ),
								esc_html( number_format_i18n( count( $group['items'] ) ) )
							);
							?>
						</span>
					</header>

					<?php if ( $group['term']->description ) : ?>
						<p class="lede" style="margin-top:-1.5rem;margin-bottom:2rem;max-width:52ch"><?php echo esc_html( $group['term']->description ); ?></p>
					<?php endif; ?>
				<?php endif; ?>

				<ul class="menu-items">
					<?php foreach ( $group['items'] as $item ) : ?>
						<?php
						get_template_part(
							'template-parts/menu/menu-item',
							null,
							array(
								'item'       => $item,
								'show_image' => $config['show_images'],
								'collect'    => $collect,
							)
						);
						?>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endforeach; ?>

		<div class="menu-empty" data-menu-empty hidden>
			<p class="lede"><?php esc_html_e( 'Nothing matches that just yet.', 'treats' ); ?></p>
			<p><?php esc_html_e( 'Try a different word, or clear the filters to see the whole menu.', 'treats' ); ?></p>
		</div>

		<?php if ( $dietary_terms ) : ?>
			<div class="diet-key">
				<strong><?php esc_html_e( 'Key:', 'treats' ); ?></strong>
				<?php foreach ( $dietary_terms as $term ) : ?>
					<span class="diet-key__item">
						<span class="diet-tag diet-tag--<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( strtoupper( $term->slug ) ); ?></span>
						<?php echo esc_html( $term->name ); ?>
					</span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<p class="menu-note">
			<span data-menu-count>
				<?php
				printf(
					/* translators: %s: number of dishes. */
					esc_html( _n( '%s dish shown.', '%s dishes shown.', count( $items ), 'treats' ) ),
					esc_html( number_format_i18n( count( $items ) ) )
				);
				?>
			</span>
			<?php esc_html_e( 'Allergen information is available for every dish — please ask a member of the team.', 'treats' ); ?>
		</p>
	</div>
</div>
