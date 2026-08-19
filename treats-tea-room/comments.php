<?php
/**
 * Comments.
 *
 * @package Treats
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="comments-area">
	<?php if ( have_comments() ) : ?>
		<h2 style="font-size:var(--fs-lg)">
			<?php
			$treats_count = (int) get_comments_number();

			printf(
				/* translators: %s: number of comments. */
				esc_html( _n( '%s comment', '%s comments', $treats_count, 'treats' ) ),
				esc_html( number_format_i18n( $treats_count ) )
			);
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'avatar_size' => 48,
					'short_ping'  => true,
				)
			);
			?>
		</ol>

		<?php
		the_comments_pagination(
			array(
				'class'     => 'pagination',
				'prev_text' => __( 'Previous', 'treats' ),
				'next_text' => __( 'Next', 'treats' ),
			)
		);
		?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="text-mute"><?php esc_html_e( 'Comments are closed.', 'treats' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'title_reply'        => __( 'Leave a comment', 'treats' ),
			'title_reply_before' => '<h2 class="comment-reply-title" style="font-size:var(--fs-lg);margin-bottom:1rem">',
			'title_reply_after'  => '</h2>',
			'class_form'         => 'form comment-form',
			'class_submit'       => 'btn btn--primary',
			'comment_field'      => sprintf(
				'<p class="field"><label class="field__label" for="comment">%1$s</label><textarea class="input" id="comment" name="comment" rows="5" required></textarea></p>',
				esc_html__( 'Comment', 'treats' )
			),
		)
	);
	?>
</div>
