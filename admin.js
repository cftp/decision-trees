
jQuery(function($){

	$('#cftp_add_answer').click(function(e){

		/**
		 * At the moment we only have one answer provider so this code only handles that currently.
		 * Once we've written some more answer handlers we'll need to modify this to display a drop-down
		 * to selection the answer type and show/hide the correct answer section accordingly.
		 */

		counter = $('.edit_answer').length;

		source = $('.add_answer[data-answer-type="simple"]');
		target = source.clone();
		target
			.appendTo('#cftp_dt_edit_answers')
			.removeClass('add_answer')
			.addClass('edit_answer')
			.slideDown()
		;

		target.find(':input').each(function(i,v){
			n = $(this).attr('name').replace('cftp_dt_new','cftp_dt_add[' + counter + ']');
			$(this).attr('name',n);
		}).first().focus();

		e.preventDefault();

	});

});
