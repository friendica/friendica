<?php

/*
 * Name: Dark Bubble
 * Version: 1.0
 * Maintainer: Mike Macgirvin <mike@macgirvin.com>
 */


function darkbubble_init(&$a) {
$a->theme_info = array(
  'extends' => 'testbubble',
);
set_template_engine($a, 'smarty3');


$a->page['htmlhead'] .= <<< EOT
<script>
$(document).ready(function() {

$('html').click(function() { $("#nav-notifications-menu" ).hide(); });
});
</script>
EOT;
}
