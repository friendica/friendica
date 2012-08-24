<?php
/*
 * Get all css at once to minimize number of requests
 */
header('Content-type: text/css; charset=utf-8');
 
$topdir = '../../../../';
chdir($topdir);

echo file_get_contents('./library/fancybox/jquery.fancybox-1.3.4.css');
echo file_get_contents('./library/tiptip/tipTip.css');
echo file_get_contents('./library/jgrowl/jquery.jgrowl.css');
?>
