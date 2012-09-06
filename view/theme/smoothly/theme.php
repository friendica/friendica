<?php

/*
 * Name: Smoothly
 * Version: Version 0.3
 * Author: Alex <alex@friendica.pixelbits.de>
 * Maintainer: Alex alex@friendica.pixelbits.de>
 * Description: Theme optimized for Tablets (iPad etc.)
 * Screenshot: <a href="screenshot.png">Screenshot</a>
 */

$a->page['htmlhead'] .= <<< EOT
<script>
$(document).ready(function() {

$('html').click(function() { $("#nav-notifications-menu" ).hide(); });
});
</script>
EOT;
