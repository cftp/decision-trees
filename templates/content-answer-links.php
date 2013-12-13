<?php defined( 'ABSPATH' ) or die(); ?>

<ul id="cftp-dt-next">
	<?php 
		$answer = new CFTP_DT_Answer( get_the_ID() );
		$provider = $this->get_answer_provider( $answer->get_answer_type() );
	?>

	<?php foreach ( $answers as $answer_id => $answer ) : ?>

		<?php
		if ( !( $provider = $this->get_answer_provider( $answer->get_answer_type() ) ) ) {
			# @TODO this should probably raise a warning or something
			continue;
		}
		?>

		<li class="cftp-dt-next-answer">
			<?php echo $provider->get_answer( $answer ); ?>
		</li>

	<?php endforeach; ?>

</ul>
