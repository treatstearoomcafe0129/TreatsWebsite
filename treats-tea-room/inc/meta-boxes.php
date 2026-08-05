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
		add_meta_box(
			'treats-details-' . $screen,
			__( 'Treats details', 'treats' ),
			'treats_render_meta_box',
			$screen,
			'side',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'treats_add_meta_boxes' );

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

		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}

		$raw = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per type below.

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

			default:
				$value = sanitize_text_field( $raw );
		}

		if ( '' === $value ) {
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
