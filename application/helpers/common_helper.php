<?php
/*=================================================================================
							Check if ajax request or not	
==================================================================================*/
function is_ajax()
{
	$C = & get_instance();
	
	if(!$C->input->is_ajax_request()) 
		exit('No direct script access allowed');
}

?>