<div class="wrap">

	<?php screen_icon(); ?>
	<h2><?php _e( 'Decision Tree Nodes', 'cftp_dt' ); ?></h2>

	<div class="cftp_dt_container">

		<?php foreach ( $tree as $level => $nodes ) { ?>

			<div class="cftp_dt_level">

				<?php foreach ( $nodes as $node_pos => $node ) { ?>

					<div class="cftp_dt_node cftp_dt_node_<?php echo esc_attr( $level ); ?>" data-nodeparent="<?php echo absint( $node->post_parent ); ?>" id="cftp_dt_node_<?php echo absint( $node->ID ); ?>">
						<?php if ( empty( $level ) ) { ?>
							<h3><?php echo get_the_title( $node->ID ); ?></h3>
						<?php } else { ?>
							<h3><?php echo get_post_meta( $node->ID, '_cftp_dt_answer_value', true ); ?></h3>
							<p><?php echo get_the_title( $node->ID ); ?></p>
						<?php } ?>
						<p class="row-actions"><a href="<?php echo get_edit_post_link( $node->ID ); ?>">Edit</a> | <a href="<?php echo get_permalink( $node->ID ); ?>">View</a></p>
					</div>

				<?php } ?>

			</div>

		<?php } ?>

	</div>

</div>