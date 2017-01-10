<?php
    function opensearch_content(App &$a) {
    	
		$tpl = get_markup_template('opensearch.tpl');
	
		header("Content-type: application/opensearchdescription+xml");
	
		$o = replace_macros($tpl, array(
			'$baseurl' => App::get_baseurl(),
			'$nodename' => $a->get_hostname(),
		));
		
		echo $o;
		
		killme();
		
	}
?>