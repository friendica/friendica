<?php

/**
 * SPDX-FileCopyrightText: 2010 - 2024 the Friendica project
 *
 * SPDX-License-Identifier: CC0-1.0
 */

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
	->in(__DIR__)
	->exclude([
		'addon',
		'bin/dev',
		'config',
		'doc',
		'images',
		'mods',
		'spec',
		'vendor',
		'view/asset',
		'lang',
		'view/smarty3/compiled',
	])
	->append([
		'.php-cs-fixer.dist.php',
	])
;

$config = new PhpCsFixer\Config();
return $config
	->setRules([
		'@PER-CS3x0'                  => true,
		'@PER-CS3x0:risky'            => true,
		'@PHPUnit10x0Migration:risky' => true,
		'align_multiline_comment'     => true,
		'binary_operator_spaces'      => [
			'default'   => 'single_space',
			'operators' => [
				'=>' => 'align_single_space_minimal',
				'='  => 'align_single_space_minimal',
				'??' => 'align_single_space_minimal',
			],
		],
		'braces_position' => [
			'anonymous_classes_opening_brace'  => 'same_line',
			'control_structures_opening_brace' => 'same_line',
			'functions_opening_brace'          => 'next_line_unless_newline_at_signature_end',
		],
		'function_declaration' => [
			'closure_function_spacing' => 'one',
		],
		'list_syntax' => [
			'syntax' => 'short',
		],
		'new_with_parentheses'        => true,
		'no_unused_imports'           => true,
		'single_import_per_statement' => true,
		'ternary_operator_spaces'     => false,
	])
	->setRiskyAllowed(true)
	->setFinder($finder)
	->setIndent("\t");
