
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

	$('.cftp_dt_node').hover(function(){

		/* This code is a nice big mess. Clean it up! */

		np = $(this).attr('data-nodeparent');
		hl = new Array();

		while ( np != '0' ) {
			hl.push( np );
			parent_node = $('#cftp_dt_node_'+np);
			if ( !parent_node.length )
				break;
			np = parent_node.attr('data-nodeparent');
		}

		$(this).addClass('cftp_dt_node_highlighted');
		conn = jsPlumb.getConnections({
			source : $(this).attr('id')
		});
		$.each(conn,function(k,v){
			v.setPaintStyle({
				strokeStyle : '#555'
			});
		});
		$.each(hl,function(k,v){
			$('#cftp_dt_node_'+v).addClass('cftp_dt_node_highlighted');
			conn = jsPlumb.getConnections({
				source : 'cftp_dt_node_'+v
			});
			$.each(conn,function(k,v){
				v.setPaintStyle({
					strokeStyle : '#555'
				});
			});
		});

	},function(){

		$('.cftp_dt_node').removeClass('cftp_dt_node_highlighted');

		conn = jsPlumb.getConnections();
		$.each(conn,function(k,v){
			v.setPaintStyle({
				strokeStyle : '#ccc'
			});
		});

	});

	if ( window.jsPlumb ) {

		jsPlumb.ready(function(){

			var arrowCommon = {
				foldback    : 0.8,
				width       : 11,
				length      : 11,
				paintStyle  : {
					fillStyle : '#ccc'
				}
			};
			var epCommon = {
				endpoint : 'Blank',
				anchor   : [ 'RightMiddle', 'LeftMiddle' ]
			};

			$('[data-nodeparent]').each(function(k,v){

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
							lineWidth : 1,
							strokeStyle : '#ccc'
						}/*,
						overlays   : [
							[ 'Arrow', {
								location  : 0.5,
								direction : -1,
							}, arrowCommon ]
						]*/
					});

				}

			});

		});

	}

});
