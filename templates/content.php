<?php defined( 'ABSPATH' ) or die(); ?>

<div id="cftp_dt_answers">

	<?php foreach ( $answers as $answer_id => $answer ) : ?>

		<?php
		if ( !( $provider = $this->get_answer_provider( $answer->get_answer_type() ) ) ) {
			# @TODO this should probably raise a warning or something
			continue;
		}
		?>

		<div class="cftp_dt_answer">
			<?php echo $provider->get_answer( $answer ); ?>
		</div>

	<?php endforeach; ?>

</div>
