<?php
/**
 * Generate the serialized unicode_blocks.dat file shipped with the package
 */
$unicode_blocks = include __DIR__ . '/unicode_blocks.php';
file_put_contents(__DIR__ . '/unicode_blocks.dat', serialize($unicode_blocks));
?>