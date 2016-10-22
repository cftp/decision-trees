<?php defined( 'ABSPATH' ) or die(); ?>

<?php if ( $start ) : ?>
	<div class="cftp-dt-restart">
		<a href="<?php echo get_permalink( $start->ID ); ?>"><?php esc_html_e( 'restart', 'cftp_dt' ); ?></a>
	</div>
<?php endif; ?>

<ol id="cftp-dt-answers">

	<?php 
		foreach ( $previous_answers as $previous_answer ) :
			$previous_answer = get_post( $previous_answer );
			if ( ! $previous_answer->post_parent )
				continue;
			$previous_answer_parent = get_post( $previous_answer->post_parent );
			$answer = new CFTP_DT_Answer( $previous_answer->ID );
			$provider = $this->get_answer_provider( $answer->get_answer_type() );
		?>
			<li class="cftp_dt_prev_answer">
				<h3 class="cftp-dt-node-title"><?php echo $previous_answer_parent->post_title; ?></h3>
				<p class="cftp-dt-answer-value"><?php echo $answer->get_answer_value(); ?></p>
				<a href="<?php echo $provider->get_edit_answer_url( $answer ); ?>"><?php esc_html_e( 'change this answer', 'cftp_dt' ); ?></a>
			</li>
	<?php endforeach; ?>

	<li class="cftp-dt-current">

		<?php 
			$answer = new CFTP_DT_Answer( get_the_ID() );
			$provider = $this->get_answer_provider( $answer->get_answer_type() );
		?>

		<h2 class="cftp-dt-current"><?php echo $title; ?></h2>

		<div class="cftp-dt-content"><?php echo $content; ?></div>

		<?php if ( $answer_links ) : echo $answer_links; endif; ?>

	</li>

</ol>
