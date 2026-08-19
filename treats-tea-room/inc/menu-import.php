<?php
/**
 * One-click import for the printed menu.
 *
 * The theme seeds a small starter menu on activation so the site is never
 * empty, but the real menu is 171 dishes across five pages and nobody should
 * type that into the dashboard by hand. This screen replaces the lot in one
 * go, from `menu-data.php`.
 *
 * It is deliberately explicit rather than automatic: it never runs on its
 * own, it says exactly what it is about to do, and everything it removes goes
 * to the trash rather than being destroyed.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * The version of the printed menu this data represents.
 *
 * Bumping this is what tells the notice it has something new to offer.
 */
const TREATS_MENU_EDITION = 'july-2025';

/**
 * Register the import screen under Tools.
 *
 * @return void
 */
function treats_menu_import_menu() {
	add_management_page(
		__( 'Import Treats Menu', 'treats' ),
		__( 'Import Treats Menu', 'treats' ),
		'manage_options',
		'treats-menu-import',
		'treats_menu_import_screen'
	);
}
add_action( 'admin_menu', 'treats_menu_import_menu' );

/**
 * Count the menu items currently published.
 *
 * @return int
 */
function treats_count_menu_items() {
	$counts = wp_count_posts( 'treats_menu_item' );

	return (int) ( $counts->publish ?? 0 ) + (int) ( $counts->draft ?? 0 );
}

/**
 * Render the import screen.
 *
 * @return void
 */
function treats_menu_import_screen() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to import the menu.', 'treats' ) );
	}

	$result = null;

	if ( isset( $_POST['treats_import_menu'] ) ) {
		check_admin_referer( 'treats_import_menu' );
		$result = treats_import_menu( ! empty( $_POST['treats_replace'] ) );
	}

	$existing = treats_count_menu_items();
	$incoming = count( treats_menu_items() );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Import Treats Menu', 'treats' ); ?></h1>

		<?php if ( $result ) : ?>
			<div class="notice notice-success">
				<p>
					<?php
					printf(
						/* translators: 1: number imported, 2: number trashed. */
						esc_html__( 'Imported %1$d dishes. Moved %2$d old items to the trash.', 'treats' ),
						(int) $result['imported'],
						(int) $result['trashed']
					);
					?>
				</p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=treats_menu_item' ) ); ?>">
						<?php esc_html_e( 'Review the menu', 'treats' ); ?>
					</a>
				</p>
			</div>
		<?php endif; ?>

		<p>
			<?php
			printf(
				/* translators: %d: number of dishes in the import. */
				esc_html__( 'This imports the printed menu — %d dishes — with prices, descriptions, sections and dietary labels already set.', 'treats' ),
				(int) $incoming
			);
			?>
		</p>

		<p>
			<?php
			printf(
				/* translators: %d: number of existing items. */
				esc_html__( 'There are currently %d menu items on the site.', 'treats' ),
				(int) $existing
			);
			?>
		</p>

		<form method="post">
			<?php wp_nonce_field( 'treats_import_menu' ); ?>

			<p>
				<label>
					<input type="checkbox" name="treats_replace" value="1" checked>
					<?php esc_html_e( 'Move the existing menu items to the trash first. Leave this ticked unless you have added dishes of your own that you want to keep — nothing is permanently deleted either way.', 'treats' ); ?>
				</label>
			</p>

			<?php submit_button( __( 'Import the menu', 'treats' ), 'primary', 'treats_import_menu' ); ?>
		</form>

		<h2><?php esc_html_e( 'What you will get', 'treats' ); ?></h2>
		<ul style="list-style: disc; margin-left: 1.4em;">
			<?php foreach ( treats_menu_taxonomy() as $conf ) : ?>
				<li>
					<strong><?php echo esc_html( $conf['name'] ); ?></strong>
					— <?php echo esc_html( implode( ', ', $conf['children'] ) ); ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}

/**
 * Make sure every category and sub-category exists, and return their IDs.
 *
 * @return array<string,int> Slug to term ID.
 */
function treats_ensure_menu_terms() {
	$ids = array();

	foreach ( treats_menu_taxonomy() as $slug => $conf ) {
		$parent_id = treats_ensure_term( $slug, $conf['name'], 0 );

		if ( ! $parent_id ) {
			continue;
		}

		$ids[ $slug ] = $parent_id;

		foreach ( $conf['children'] as $child_slug => $child_name ) {
			$child_id = treats_ensure_term( $child_slug, $child_name, $parent_id );

			if ( $child_id ) {
				$ids[ $child_slug ] = $child_id;
			}
		}
	}

	return $ids;
}

/**
 * Fetch a menu category by slug, creating it if it is missing.
 *
 * @param string $slug   Term slug.
 * @param string $name   Term name.
 * @param int    $parent Parent term ID, 0 for top level.
 * @return int Term ID, or 0 on failure.
 */
function treats_ensure_term( $slug, $name, $parent = 0 ) {
	$existing = get_term_by( 'slug', $slug, 'treats_menu_category' );

	if ( $existing instanceof WP_Term ) {
		// Keep the tree right even if the term was created by an earlier run.
		if ( (int) $existing->parent !== (int) $parent ) {
			wp_update_term( $existing->term_id, 'treats_menu_category', array( 'parent' => $parent ) );
		}

		return (int) $existing->term_id;
	}

	$term = wp_insert_term( $name, 'treats_menu_category', array( 'slug' => $slug, 'parent' => $parent ) );

	return is_wp_error( $term ) ? 0 : (int) $term['term_id'];
}

/**
 * Import the menu.
 *
 * @param bool $replace Whether to trash the existing items first.
 * @return array{imported:int,trashed:int}
 */
function treats_import_menu( $replace = true ) {
	treats_ensure_menu_terms();
	treats_seed_dietary_terms();

	$trashed = 0;

	if ( $replace ) {
		$existing = get_posts(
			array(
				'post_type'      => 'treats_menu_item',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		foreach ( $existing as $post_id ) {
			// Trash, never delete. This is somebody's live menu.
			if ( wp_trash_post( $post_id ) ) {
				$trashed++;
			}
		}
	}

	$imported = 0;

	foreach ( treats_menu_items() as $order => $item ) {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'treats_menu_item',
				'post_status'  => 'publish',
				'post_title'   => $item['title'],
				'post_content' => $item['description'] ?? '',
				'menu_order'   => ( $order + 1 ) * 10,
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}

		wp_set_object_terms( $post_id, $item['categories'], 'treats_menu_category' );

		if ( ! empty( $item['dietary'] ) ) {
			wp_set_object_terms( $post_id, $item['dietary'], 'treats_dietary' );
		}

		$price = $item['price'] ?? '';
		update_post_meta( $post_id, '_treats_price', $price );

		// Only a plain number can be added to a Click & Collect basket, and a
		// few lines here are notes rather than things you can order.
		$collectable = ( $item['collect'] ?? true ) && is_numeric( $price );
		update_post_meta( $post_id, '_treats_collectable', $collectable ? '1' : '' );

		if ( ! empty( $item['note'] ) ) {
			update_post_meta( $post_id, '_treats_price_note', $item['note'] );
		}

		if ( ! empty( $item['badge'] ) ) {
			update_post_meta( $post_id, '_treats_badge', $item['badge'] );
		}

		if ( ! empty( $item['featured'] ) ) {
			update_post_meta( $post_id, '_treats_featured', '1' );
		}

		update_post_meta( $post_id, '_treats_menu_edition', TREATS_MENU_EDITION );

		$imported++;
	}

	treats_tidy_empty_menu_terms();

	update_option( 'treats_menu_edition', TREATS_MENU_EDITION, false );
	update_option( 'treats_menu_seeded', 1, false );

	return array( 'imported' => $imported, 'trashed' => $trashed );
}

/**
 * Remove menu categories left behind by an earlier menu.
 *
 * Only ever touches terms that are both empty and not part of the current
 * menu, so a category somebody added themselves and used is safe.
 *
 * @return void
 */
function treats_tidy_empty_menu_terms() {
	$keep = array();

	foreach ( treats_menu_taxonomy() as $slug => $conf ) {
		$keep[] = $slug;
		$keep    = array_merge( $keep, array_keys( $conf['children'] ) );
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'treats_menu_category',
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $terms ) ) {
		return;
	}

	foreach ( $terms as $term ) {
		if ( in_array( $term->slug, $keep, true ) || $term->count > 0 ) {
			continue;
		}

		wp_delete_term( $term->term_id, 'treats_menu_category' );
	}
}

/**
 * Point the owner at the importer when there is a newer menu than the one
 * they have loaded.
 *
 * @return void
 */
function treats_menu_import_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( $screen && 'tools_page_treats-menu-import' === $screen->id ) {
		return;
	}

	if ( get_option( 'treats_menu_edition' ) === TREATS_MENU_EDITION ) {
		return;
	}
	?>
	<div class="notice notice-info is-dismissible">
		<p>
			<strong><?php esc_html_e( 'Treats Tea Room', 'treats' ); ?></strong>
			—
			<?php esc_html_e( 'the printed menu is ready to import, which will replace the starter dishes.', 'treats' ); ?>
			<a href="<?php echo esc_url( admin_url( 'tools.php?page=treats-menu-import' ) ); ?>">
				<?php esc_html_e( 'Import it now', 'treats' ); ?>
			</a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'treats_menu_import_notice' );
