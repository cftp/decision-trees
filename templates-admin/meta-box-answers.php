<?php defined( 'ABSPATH' ) or die(); ?>

<input type="hidden" name="cftp_dt_post_<?php echo absint( $post->ID ); ?>_parent" value="<?php echo absint( $post->post_parent ); ?>" />

<p class="description">
	<?php _e( 'Tip: phrase the answer link text as a statement, e.g. "It has six legs."', 'nao' ); ?>
</p>

<div id="cftp_dt_add_answer">

	<p><input type="button" class="button-secondary" value="<?php _e( 'Add An Answer', 'cftp_dt' ); ?>" id="cftp_add_answer" /></p>

	<?php foreach ( $this->answer_providers[ $post->ID ] as $provider_name => $provider ) : ?>

		<div class="add_answer" data-answer-type="<?php echo esc_attr( $provider_name ); ?>">
			<div class="answer_field">
				<?php echo $provider->get_add_form(); ?>
			</div>
			<div class="answer_title">
				<input type="text" class="regular-text" name="cftp_dt_new[<?php echo esc_attr( $provider_name ); ?>][page_title]" value="" placeholder="<?php esc_attr_e( 'Answer page title', 'cftp_dt' ); ?>" />
			</div>
		</div>

	<?php endforeach; ?>

</div>

<div id="cftp_dt_edit_answers">

	<?php foreach ( $answers as $answer_id => $answer ) : ?>

		<?php
		if ( !( $provider = $this->get_answer_provider_for_post( $answer->get_answer_type(), $post->ID ) ) )
			continue;
		?>

		<div class="edit_answer">
			<div class="answer_field">
				<input type="hidden" name="cftp_dt_edit[<?php echo esc_attr( $answer_id ); ?>][<?php echo esc_attr( $answer->get_answer_type() ); ?>][page_id]" value="<?php echo absint( $answer->get_post()->ID ); ?>" />
				<?php echo $provider->get_edit_form( $answer_id, $answer ); ?>
			</div>
			<div class="answer_title">
				<?php echo esc_html( $answer->get_page_title() ); ?>
				<a href="<?php echo get_edit_post_link( $answer->get_post()->ID ); ?>">edit</a>
			</div>
		</div>

	<?php endforeach; ?>

</div>
