<?php defined( 'ABSPATH' ) or die(); ?>

<?php 
	$previous_answers = array_reverse( get_post_ancestors( $post->ID ) );
	$previous_answers[] = get_the_ID();
	$start = array_shift( $previous_answers );
	array_shift( $previous_answers );
	$start = get_post( $start );
?>

<div class="cftp-dt-restart">
	<a href="<?php // echo get_permalink( $start->ID ); ?>">restart</a>
</div>

<ol id="cftp-dt-answers">
		<?php 
			foreach ( $previous_answers as $previous_answer ) :
				$previous_answer = get_post( $previous_answer );
				if ( ! $previous_answer->post_parent )
					continue;
				$previous_answer_parent = get_post( $previous_answer->post_parent );
				$answer = new CFTP_DT_Answer( $previous_answer->ID );
				$provider = $this->get_answer_provider_for_post( $answer->get_answer_type(), $previous_answer->ID );
			?>
				<li class="cftp_dt_prev_answer">
					<h3 class="cftp-dt-node-title"><?php echo $previous_answer_parent->post_title; ?></h3>
					<p class="cftp-dt-answer-value"><?php echo $answer->get_answer_value(); ?></p>
					<a href="<?php echo $provider->get_edit_answer_url( $answer ); ?>">change this answer</a>
				</li>
		<?php endforeach; ?>

	<li class="cftp-dt-current">

		<?php 
			$answer = new CFTP_DT_Answer( get_the_ID() );
			$provider = $this->get_answer_provider_for_post( $answer->get_answer_type(), get_the_ID() );
		?>

		<h2 class="cftp-dt-current"><?php echo $title; ?></h2>

		<div class="cftp-dt-content"><?php echo $content; ?></div>

		<?php if ( $answers ) : ?>

			<ul id="cftp-dt-next">

				<?php foreach ( $answers as $answer_id => $answer ) : ?>

					<?php
					if ( !( $provider = $this->get_answer_provider_for_post( $answer->get_answer_type(), get_the_ID() ) ) ) {
						# @TODO this should probably raise a warning or something
						continue;
					}
					?>

					<li class="cftp-dt-next-answer">
						<?php echo $provider->get_answer( $answer ); ?>
					</li>

				<?php endforeach; ?>

			</ul>

		<?php endif; ?>

	</li>

</ol>
