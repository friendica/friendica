<?php

/*
 * Name: Dark Bubble
 * Version: 1.0
 * Maintainer: Mike Macgirvin <mike@macgirvin.com>
 */


$a->theme_info = array(
  'extends' => 'testbubble',
);


$a->page['htmlhead'] .= <<< EOT
<script>
$(document).ready(function() {

$('html').click(function() { $("#nav-notifications-menu" ).hide(); });
});
</script>
EOT;
