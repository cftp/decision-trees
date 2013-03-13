<div class="wrap">

	<?php screen_icon(); ?>
	<h2><?php _e( 'Decision Trees', 'cftp_dt' ); ?></h2>

	<div class="cftp_dt_container">

		<?php foreach ( $tree as $level => $nodes ) { ?>

			<div class="cftp_dt_level" data-level="<?php echo absint( $level ); ?>">

				<?php foreach ( $nodes as $node_pos => $node ) { ?>

					<div class="cftp_dt_node" data-nodeparent="<?php echo absint( $node->post_parent ); ?>" id="cftp_dt_node_<?php echo absint( $node->ID ); ?>" data-label="<?php echo esc_attr( get_post_meta( $node->ID, '_cftp_dt_answer_value', true ) ); ?>">
						<?php echo get_the_title( $node->ID ); ?>
					</div>

				<?php } ?>

			</div>

		<?php } ?>

	</div>

</div>