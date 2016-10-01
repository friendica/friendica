<?php
/**
 * @file view/theme/frio/php/modes/none.php
 * @brief The site template for pure content (e.g. (modals)
 *
 * This themplate is used e.g for bs modals. So outputs
 * only the pure content
 */

if (x($page, 'content')) {
    echo $page['content'];
}
