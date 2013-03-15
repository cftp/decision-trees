<div class="wrap">

	<?php screen_icon(); ?>
	<h2><?php _e( 'Decision Tree Nodes', 'cftp_dt' ); ?></h2>

	<div id="cftp_dt_visualiser">

		<?php foreach ( $tree as $level => $nodes ) { ?>

			<div class="cftp_dt_level">

				<?php foreach ( $nodes as $node_pos => $node ) { ?>

					<div
						class="cftp_dt_node cftp_dt_node_<?php echo esc_attr( $level ); ?>"
						data-nodeparent="<?php echo absint( $node->post_parent ); ?>"
						data-nodeid="<?php echo absint( $node->ID ); ?>"
						id="cftp_dt_node_<?php echo absint( $node->ID ); ?>"
					>
						<?php if ( 0 == $level ) { ?>
							<h3><?php echo get_the_title( $node->ID ); ?></h3>
						<?php } else { ?>
							<?php /* @TODO replace this with the get_answer_value() method on an answer object */ ?>
							<h3><?php echo get_post_meta( $node->ID, '_cftp_dt_answer_value', true ); ?></h3>
							<p><?php echo get_the_title( $node->ID ); ?></p>
						<?php } ?>
						<?php if ( 'publish' != $node->post_status ) { ?>
							<p class="description"><?php echo get_post_status_object( $node->post_status )->label; ?></p>
						<?php } ?>
						<p class="row-actions">
							<?php /* @TODO i18n */ ?>
							<a href="<?php echo get_edit_post_link( $node->ID ); ?>">Edit</a>
							| <a href="<?php echo get_permalink( $node->ID ); ?>">View</a>
							<span class="hide-if-no-js">| <a href="#TB_inline?height=200&width=300&inlineId=cftp_dt_visualiser_add_answer" class="thickbox cftp_dt_add_answer" title="Add Answer">Add Answer</a></span>
						</p>
					</div>

				<?php } ?>

			</div>

		<?php } ?>

	</div>

	<div id="cftp_dt_visualiser_add_answer">
		<form action="" method="post" id="cftp_dt_add_answer_form">
			<input type="hidden" name="action" value="cftp_dt_add_answer" />
			<input type="hidden" name="post_id" value="" id="cftp_dt_add_answer_id" />
			<?php wp_nonce_field( 'cftp_dt_add_answer' ); ?>

			<?php foreach ( $this->get_answer_providers() as $provider_name => $provider ) : ?>

				<div class="add_answer" data-answer-type="<?php echo esc_attr( $provider_name ); ?>">
					<div class="answer_field">
						<?php echo $provider->get_add_form(); ?>
					</div>
					<div class="answer_title">
						<input type="text" class="regular-text" name="cftp_dt_new[<?php echo esc_attr( $provider_name ); ?>][page_title]" value="" placeholder="<?php esc_attr_e( 'Answer page title', 'cftp_dt' ); ?>" />
					</div>
				</div>

			<?php endforeach; ?>

			<?php submit_button( __( 'Add &raquo;', 'cftp_dt' ) ); ?>

		</form>
	</div>

</div>
