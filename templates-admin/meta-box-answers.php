<?php defined( 'ABSPATH' ) or die(); ?>

<?php if ( $answer_providers ) : ?>

	<?php submit_button(); ?>

	<?php foreach ( $answer_providers as $answer_provider ) : ?>

		<?php $answer_provider->form(); ?>

	<?php endforeach; ?>

<?php else : ?>

	<p><?php _e( 'There are no answer providers configured, this is a pretty serious problem.', 'cftp_dt' ); ?></p>

<?php endif; ?>
