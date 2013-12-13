jQuery(window).load(function(){
	$prev_answer_length = jQuery('.cftp_dt_prev_answer').length;
	if($prev_answer_length>0) {
		$prev_answer_last = jQuery('.cftp_dt_prev_answer').eq(($prev_answer_length-1));
		$prev_answer_last_offset = jQuery($prev_answer_last).offset();
		jQuery('html, body').animate({ scrollTop: $prev_answer_last_offset.top }, 1200);
	};
});