
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

	if ( window.jsPlumb ) {

		jsPlumb.ready(function(){

			var arrowCommon = {
				foldback    : 0.7,
				width       : 14,
				length      : 14,
				paintStyle  : {
					fillStyle : '#ccc'
				}
			};
			var epCommon = {
				endpoint : 'Blank',
				anchor   : [ 'RightMiddle', 'LeftMiddle' ]
			};

			$('[data-nodeparent]').each(function(k,v){

				node_type   = $(this).attr('data-nodetype');
				node_parent = $(this).attr('data-nodeparent');
				source_id   = $(this).attr('id');
				target_id   = 'cftp_dt_node_' + node_parent;

				if ( $('#'+target_id).length ) {

					ep_source = jsPlumb.addEndpoint( source_id, {}, epCommon );
					ep_target = jsPlumb.addEndpoint( target_id, {}, epCommon );

					jsPlumb.connect({
						source     : ep_source,
						target     : ep_target,
						connector  : 'Straight',
						paintStyle : {
							lineWidth : 2,
							strokeStyle : '#ccc'
						},
						overlays   : [
							[ 'Arrow', {
								location  : 0.5,
								direction : -1,
							}, arrowCommon ]
						]
					});

				}

			});

		});

	}

});
