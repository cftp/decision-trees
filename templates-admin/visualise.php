<div class="wrap">

	<?php screen_icon(); ?>
	<h2><?php _e( 'Decision Trees', 'cftp_dt' ); ?></h2>

	<div class="cftp_dt_container">

		<?php foreach ( $tree as $level => $nodes ) { ?>

			<?php if ( !empty( $level ) ) { ?>

				<div class="cftp_dt_level cftp_dt_level_answer">

					<?php foreach ( $nodes as $node_pos => $node ) { ?>

						<div class="cftp_dt_node cftp_dt_node_answer" data-nodeparent="<?php echo absint( $node->post_parent ); ?>_question" id="cftp_dt_node_<?php echo absint( $node->ID ); ?>_answer" data-nodetype="answer">
							<p><?php echo get_post_meta( $node->ID, '_cftp_dt_answer_value', true ); ?></p>
							<!-- <p class="row-actions"><a href="#">Edit</a> | <a href="#">View</a></p> -->
						</div>

					<?php } ?>

				</div>

			<?php } ?>

			<div class="cftp_dt_level cftp_dt_level_question">

				<?php foreach ( $nodes as $node_pos => $node ) { ?>

					<div class="cftp_dt_node cftp_dt_node_question" data-nodeparent="<?php echo absint( $node->ID ); ?>_answer" id="cftp_dt_node_<?php echo absint( $node->ID ); ?>_question" data-nodetype="question">
						<p><?php echo get_the_title( $node->ID ); ?></p>
						<p class="row-actions"><a href="<?php echo get_edit_post_link( $node->ID ); ?>">Edit</a> | <a href="<?php echo get_permalink( $node->ID ); ?>">View</a></p>
					</div>

				<?php } ?>

			</div>

		<?php } ?>

	</div>

</div>