<?php

/*
 * Name: Test Bubble
 * Version: 1.1
 * Author: Anne Walk
 * Author: Devlon Duthied
 * Maintainer: Mike Macgirvin <mike@macgirvin.com>
 */

$a->theme['template_engine'] = 'smarty3';

$a->page['htmlhead'] .= <<< EOT
<script>
$(document).ready(function() {

$('html').click(function() { $("#nav-notifications-menu" ).hide(); });
});
</script>
EOT;
