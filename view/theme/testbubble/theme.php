<?php

/*
 * Name: Test Bubble
 * Version: 1.1
 * Author: Anne Walk
 * Author: Devlon Duthied
 * Maintainer: Mike Macgirvin <mike@macgirvin.com>
 */


function testbubble_init(&$a) {
set_template_engine($a, 'smarty3');

$a->page['htmlhead'] .= <<< EOT
<script>
$(document).ready(function() {

$('html').click(function() { $("#nav-notifications-menu" ).hide(); });
});
</script>
EOT;
}
