<?php
require_once("include/threads.php");

function threadupdate_run(&$argv, &$argc){
	update_threads();
	update_threads_mention();
}
