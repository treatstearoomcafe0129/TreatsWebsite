<?php
/**
 * Editorial meta boxes.
 *
 * Deliberately dependency-free: no ACF required, so the theme can be dropped
 * onto any install and work immediately.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field definitions per screen.
 *
 * @return array<string,array<string,array<string,mixed>>>
 */
function treats_meta_fields() {
	return array(
		'treats_menu_item' => array(
			'_treats_price'       => array(
				'label' => __( 'Price', 'treats' ),
				'type'  => 'text',
				'desc'  => __( 'Numbers only for automatic currency formatting (e.g. 8.95), or free text such as “from 12.00”.', 'treats' ),
			),
			'_treats_price_note'  => array(
				'label' => __( 'Price note', 'treats' ),
				'type'  => 'text',
				'desc'  => __( 'Optional, e.g. “per person” or “serves two”.', 'treats' ),
			),
			'_treats_badge'       => array(
				'label' => __( 'Badge', 'treats' ),
				'type'  => 'text',
				'desc'  => __( 'Optional highlight, e.g. “Signature” or “New”.', 'treats' ),
			),
			'_treats_allergens'   => array(
				'label' => __( 'Allergen note', 'treats' ),
				'type'  => 'text',
			),
			'_treats_featured'    => array(
				'label' => __( 'Feature on the home page', 'treats' ),
				'type'  => 'checkbox',
			),
			'_treats_collectable' => array(
				'label' => __( 'Available for Click & Collect', 'treats' ),
				'type'  => 'checkbox',
			),
		),
		'treats_review'    => array(
			'_treats_author'   => array(
				'label' => __( 'Reviewer name', 'treats' ),
				'type'  => 'text',
			),
			'_treats_rating'   => array(
				'label'   => __( 'Rating', 'treats' ),
				'type'    => 'number',
				'default' => '5',
				'attrs'   => array(
					'min'  => '1',
					'max'  => '5',
					'step' => '0.5',
				),
			),
			'_treats_source'   => array(
				'label' => __( 'Source', 'treats' ),
				'type'  => 'text',
				'desc'  => __( 'e.g. Tripadvisor, Google.', 'treats' ),
			),
			'_treats_source_url' => array(
				'label' => __( 'Source URL', 'treats' ),
				'type'  => 'url',
			),
			'_treats_review_date' => array(
				'label' => __( 'Review date', 'treats' ),
				'type'  => 'date',
			),
		),
		'treats_product'   => array(
			'_treats_summary'      => array(
				'label' => __( 'Short description', 'treats' ),
				'type'  => 'textarea',
				'desc'  => __( 'One line, shown on the shop listing. Falls back to the excerpt.', 'treats' ),
			),
			'_treats_price'        => array(
				'label' => __( 'Price', 'treats' ),
				'type'  => 'text',
				'desc'  => __( 'Numbers only, e.g. 24.95. Products without a price cannot be bought.', 'treats' ),
			),
			'_treats_sale_price'   => array(
				'label' => __( 'Sale price', 'treats' ),
				'type'  => 'text',
				'desc'  => __( 'Optional. Leave empty unless the item is reduced.', 'treats' ),
			),
			'_treats_options'      => array(
				'label' => __( 'Options', 'treats' ),
				'type'  => 'options',
				'desc'  => __( 'For anything sold in several amounts or sizes — a gift voucher at £10, £20 and so on. Each option carries its own price, and the customer picks one before adding it to their basket. Leave empty for an ordinary product.', 'treats' ),
			),
			'_treats_sku'          => array(
				'label' => __( 'Product code', 'treats' ),
				'type'  => 'text',
				'desc'  => __( 'Optional. Appears on orders so you can find the item on the shelf.', 'treats' ),
			),
			'_treats_track_stock'  => array(
				'label' => __( 'Keep count of stock', 'treats' ),
				'type'  => 'checkbox',
				'desc'  => __( 'Leave off for anything you can always make or reorder.', 'treats' ),
			),
			'_treats_stock'        => array(
				'label' => __( 'Number in stock', 'treats' ),
				'type'  => 'number',
				'attrs' => array(
					'min'  => '0',
					'step' => '1',
				),
				'desc'  => __( 'Only used when the count is switched on. Drops automatically as orders come in.', 'treats' ),
			),
			'_treats_postage'      => array(
				'label' => __( 'Postage for this item', 'treats' ),
				'type'  => 'text',
				'desc'  => __( 'What it costs to send this one item, e.g. 4.95. An order is charged the highest of these, plus the extra-item charge for each other item.', 'treats' ),
			),
			'_treats_collect_only' => array(
				'label' => __( 'Collection only — cannot be posted', 'treats' ),
				'type'  => 'checkbox',
			),
			'_treats_gallery'      => array(
				'label' => __( 'More photographs', 'treats' ),
				'type'  => 'gallery',
				'desc'  => __( 'Shown alongside the main image on the product page.', 'treats' ),
			),
		),
		'page'             => array(
			'_treats_eyebrow'      => array(
				'label' => __( 'Hero eyebrow', 'treats' ),
				'type'  => 'text',
				'desc'  => __( 'Small label above the page title.', 'treats' ),
			),
			'_treats_intro'        => array(
				'label' => __( 'Hero intro', 'treats' ),
				'type'  => 'textarea',
				'desc'  => __( 'One or two sentences below the page title.', 'treats' ),
			),
			'_treats_menu_category' => array(
				'label'   => __( 'Menu category', 'treats' ),
				'type'    => 'taxonomy',
				'tax'     => 'treats_menu_category',
				'desc'    => __( 'Used by the Menu page template to decide which items to show.', 'treats' ),
			),
			'_treats_menu_pdf'     => array(
				'label' => __( 'Downloadable menu (PDF URL)', 'treats' ),
				'type'  => 'url',
			),
			'_treats_hide_hero'    => array(
				'label' => __( 'Hide the page hero', 'treats' ),
				'type'  => 'checkbox',
			),
		),
	);
}

/**
 * Register the meta boxes.
 *
 * @return void
 */
function treats_add_meta_boxes() {
	foreach ( treats_meta_fields() as $screen => $fields ) {
		// A product carries prices, stock and postage together; a cramped
		// sidebar column is the wrong shape for it.
		$product = 'treats_product' === $screen;

		add_meta_box(
			'treats-details-' . $screen,
			$product ? __( 'Product details', 'treats' ) : __( 'Treats details', 'treats' ),
			'treats_render_meta_box',
			$screen,
			$product ? 'normal' : 'side',
			$product ? 'high' : 'default'
		);
	}
}
add_action( 'add_meta_boxes', 'treats_add_meta_boxes' );

/**
 * Load the media library on screens that have a gallery field.
 *
 * @param string $hook Current admin page.
 * @return void
 */
function treats_meta_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || 'treats_product' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();
	wp_add_inline_style(
		'common',
		'.treats-gallery__list{display:flex;flex-wrap:wrap;gap:10px;margin:0 0 10px;padding:0;list-style:none}
		.treats-gallery__list:empty{margin:0}
		.treats-gallery__list li{position:relative;line-height:0}
		.treats-gallery__list img{width:80px;height:80px;object-fit:cover;border-radius:4px}
		.treats-gallery__remove{position:absolute;top:-6px;right:-6px;width:20px;height:20px;border-radius:50%;background:#b32d2e;color:#fff;text-align:center;line-height:18px;text-decoration:none}
		.treats-gallery__remove:hover,.treats-gallery__remove:focus{background:#8a2223;color:#fff}
		.treats-meta{display:grid;gap:0 24px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}
		.treats-meta > p:first-child,.treats-meta > p:last-child{grid-column:1/-1}
		.treats-options__list{margin:0 0 10px;padding:0;list-style:none;display:grid;gap:8px}
		.treats-options__list:empty{margin:0}
		.treats-options__row{display:flex;gap:8px;align-items:center}
		.treats-options__label{flex:1 1 60%}
		.treats-options__price{flex:0 0 7em}
		.treats-options__remove{flex:0 0 auto;width:24px;height:24px;border-radius:50%;background:#b32d2e;color:#fff;text-align:center;line-height:22px;text-decoration:none}
		.treats-options__remove:hover,.treats-options__remove:focus{background:#8a2223;color:#fff}'
	);
	wp_enqueue_script( 'treats-admin-product', TREATS_URI . 'js/admin-product.js', array( 'jquery' ), TREATS_VERSION, true );
	wp_localize_script(
		'treats-admin-product',
		'treatsAdminProduct',
		array(
			'title'       => __( 'Choose product photographs', 'treats' ),
			'button'      => __( 'Use these photographs', 'treats' ),
			'remove'      => __( 'Remove', 'treats' ),
			'optionLabel' => __( 'Name, e.g. £20', 'treats' ),
			'optionPrice' => __( 'Price', 'treats' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'treats_meta_admin_assets' );

/**
 * Render a meta box.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function treats_render_meta_box( $post ) {
	$all    = treats_meta_fields();
	$fields = isset( $all[ $post->post_type ] ) ? $all[ $post->post_type ] : array();

	wp_nonce_field( 'treats_save_meta', 'treats_meta_nonce' );

	echo '<div class="treats-meta">';

	foreach ( $fields as $key => $field ) {
		$value = get_post_meta( $post->ID, $key, true );

		if ( '' === $value && isset( $field['default'] ) ) {
			$value = $field['default'];
		}

		$id = esc_attr( $key );

		echo '<p style="margin:0 0 16px">';

		if ( 'checkbox' === $field['type'] ) {
			printf(
				'<label for="%1$s"><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s> %3$s</label>',
				$id,
				checked( $value, '1', false ),
				esc_html( $field['label'] )
			);
		} else {
			printf( '<label for="%1$s" style="display:block;font-weight:600;margin-bottom:4px">%2$s</label>', $id, esc_html( $field['label'] ) );

			switch ( $field['type'] ) {
				case 'textarea':
					printf(
						'<textarea id="%1$s" name="%1$s" rows="3" class="widefat">%2$s</textarea>',
						$id,
						esc_textarea( $value )
					);
					break;

				case 'options':
					$rows = is_array( $value ) ? $value : array();

					echo '<div class="treats-options" data-treats-options>';
					echo '<ul class="treats-options__list" data-treats-options-list>';

					foreach ( $rows as $row ) {
						printf(
							'<li class="treats-options__row">
								<input type="text" name="%1$s[label][]" value="%2$s" placeholder="%3$s" class="treats-options__label">
								<input type="text" name="%1$s[price][]" value="%4$s" placeholder="%5$s" class="treats-options__price" inputmode="decimal">
								<button type="button" class="button-link treats-options__remove" data-treats-options-remove aria-label="%6$s">&times;</button>
							</li>',
							$id,
							esc_attr( (string) ( $row['label'] ?? '' ) ),
							esc_attr__( 'Name, e.g. £20', 'treats' ),
							esc_attr( (string) ( $row['price'] ?? '' ) ),
							esc_attr__( 'Price', 'treats' ),
							esc_attr__( 'Remove this option', 'treats' )
						);
					}

					echo '</ul>';

					printf(
						'<button type="button" class="button" data-treats-options-add data-name="%1$s">%2$s</button>',
						esc_attr( $id ),
						esc_html__( 'Add an option', 'treats' )
					);

					echo '</div>';
					break;

				case 'gallery':
					$ids = array_filter( array_map( 'absint', preg_split( '/[\s,]+/', (string) $value ) ) );

					printf(
						'<div class="treats-gallery" data-treats-gallery><input type="hidden" id="%1$s" name="%1$s" value="%2$s"><ul class="treats-gallery__list" data-treats-gallery-list>',
						$id,
						esc_attr( implode( ',', $ids ) )
					);

					foreach ( $ids as $attachment_id ) {
						$thumb = wp_get_attachment_image( $attachment_id, 'thumbnail', false, array( 'alt' => '' ) );

						if ( ! $thumb ) {
							continue;
						}

						printf(
							'<li data-id="%1$d">%2$s<button type="button" class="button-link treats-gallery__remove" data-treats-gallery-remove aria-label="%3$s">&times;</button></li>',
							(int) $attachment_id,
							$thumb, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by core.
							esc_attr__( 'Remove this photograph', 'treats' )
						);
					}

					printf(
						'</ul><button type="button" class="button" data-treats-gallery-add>%s</button></div>',
						esc_html__( 'Add photographs', 'treats' )
					);
					break;

				case 'taxonomy':
					$terms = get_terms(
						array(
							'taxonomy'   => $field['tax'],
							'hide_empty' => false,
						)
					);

					printf( '<select id="%1$s" name="%1$s" class="widefat">', $id );
					printf( '<option value="">%s</option>', esc_html__( '— Select —', 'treats' ) );

					if ( ! is_wp_error( $terms ) ) {
						foreach ( $terms as $term ) {
							printf(
								'<option value="%1$s" %2$s>%3$s</option>',
								esc_attr( $term->slug ),
								selected( $value, $term->slug, false ),
								esc_html( $term->name )
							);
						}
					}

					echo '</select>';
					break;

				default:
					$attrs = '';

					if ( ! empty( $field['attrs'] ) ) {
						foreach ( $field['attrs'] as $attr => $attr_value ) {
							$attrs .= sprintf( ' %s="%s"', esc_attr( $attr ), esc_attr( $attr_value ) );
						}
					}

					printf(
						'<input type="%1$s" id="%2$s" name="%2$s" value="%3$s" class="widefat"%4$s>',
						esc_attr( $field['type'] ),
						$id,
						esc_attr( $value ),
						$attrs // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
					);
			}
		}

		if ( ! empty( $field['desc'] ) ) {
			printf( '<span class="description" style="display:block;margin-top:4px">%s</span>', esc_html( $field['desc'] ) );
		}

		echo '</p>';
	}

	echo '</div>';
}

/**
 * Persist meta box values.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function treats_save_meta( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! isset( $_POST['treats_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['treats_meta_nonce'] ) ), 'treats_save_meta' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$post_type = get_post_type( $post_id );
	$all       = treats_meta_fields();

	if ( ! isset( $all[ $post_type ] ) ) {
		return;
	}

	foreach ( $all[ $post_type ] as $key => $field ) {
		if ( 'checkbox' === $field['type'] ) {
			if ( empty( $_POST[ $key ] ) ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, '1' );
			}

			continue;
		}

		// Removing the last option row leaves nothing in the POST at all, so
		// a missing repeatable field has to mean "empty", not "unchanged".
		if ( ! isset( $_POST[ $key ] ) && 'options' !== $field['type'] ) {
			continue;
		}

		$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per type below.

		switch ( $field['type'] ) {
			case 'textarea':
				$value = sanitize_textarea_field( $raw );
				break;

			case 'url':
				$value = esc_url_raw( $raw );
				break;

			case 'number':
				$value = '' === $raw ? '' : (string) floatval( $raw );
				break;

			case 'taxonomy':
				$value = sanitize_key( $raw );
				break;

			case 'gallery':
				$ids   = array_filter( array_map( 'absint', preg_split( '/[\s,]+/', (string) $raw ) ) );
				$value = implode( ',', array_unique( $ids ) );
				break;

			case 'options':
				$labels = isset( $raw['label'] ) ? (array) $raw['label'] : array();
				$prices = isset( $raw['price'] ) ? (array) $raw['price'] : array();
				$rows   = array();

				foreach ( $labels as $index => $label ) {
					$label = sanitize_text_field( $label );
					$price = sanitize_text_field( $prices[ $index ] ?? '' );

					// A row needs both halves to mean anything; a name with no
					// price would be an option nobody could be charged for.
					if ( '' === trim( $label ) || '' === trim( $price ) ) {
						continue;
					}

					$rows[] = array(
						'label' => $label,
						'price' => $price,
					);
				}

				$value = $rows;
				break;

			default:
				$value = sanitize_text_field( $raw );
		}

		if ( '' === $value || array() === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
}
add_action( 'save_post', 'treats_save_meta' );

/**
 * Convenience accessor for theme meta with a fallback.
 *
 * @param string $key      Meta key without the leading underscore prefix.
 * @param int    $post_id  Post ID. Defaults to the current post.
 * @param mixed  $fallback Value returned when the meta is empty.
 * @return mixed
 */
function treats_meta( $key, $post_id = 0, $fallback = '' ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();

	if ( ! $post_id ) {
		return $fallback;
	}

	$value = get_post_meta( $post_id, '_treats_' . $key, true );

	return ( '' === $value || null === $value ) ? $fallback : $value;
}
