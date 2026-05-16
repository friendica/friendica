<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util\Writer;

use Friendica\Core\Renderer;
use Friendica\Database\Definition\DbaDefinition;
use Friendica\Network\HTTPException\ServiceUnavailableException;

/**
 * Utility class to write content into the '/doc' directory
 */
class DocWriter
{
	/** @var string the relativ path to the database specification */
	const DOC_PATH_PREFIX = '/doc/en/spec/database/';

	/**
	 * Creates all database definitions as Markdown fields and create the mkdoc config file.
	 *
	 * @param DbaDefinition $definition The Database definition class
	 * @param string        $basePath   The basepath of Friendica
	 *
	 * @return void
	 * @throws ServiceUnavailableException in really unexpected cases!
	 */
	public static function writeDbDefinition(DbaDefinition $definition, string $basePath)
	{
		$tables = [];
		foreach ($definition->load(false)->getAll() as $name => $definition) {
			$indexes = [
				[
					'name'   => 'Name',
					'fields' => 'Fields',
				],
				[
					'name'   => '-',
					'fields' => '-',
				]
			];

			$lengths = ['name' => 4, 'fields' => 6];
			foreach ($definition['indexes'] as $key => $value) {
				$fieldlist         = implode(', ', $value);
				$indexes[]         = ['name' => $key, 'fields' => $fieldlist];
				$lengths['name']   = max($lengths['name'], strlen($key));
				$lengths['fields'] = max($lengths['fields'], strlen($fieldlist));
			}

			array_walk_recursive($indexes, function (&$value, $key) use ($lengths) {
				$value = str_pad($value, $lengths[$key], $value === '-' ? '-' : ' ');
			});

			$foreign = [];
			$fields  = [
				[
					'name'    => 'Field',
					'comment' => 'Description',
					'type'    => 'Type',
					'null'    => 'Null',
					'primary' => 'Key',
					'default' => 'Default',
					'extra'   => 'Extra',
				],
				[
					'name'    => '-',
					'comment' => '-',
					'type'    => '-',
					'null'    => '-',
					'primary' => '-',
					'default' => '-',
					'extra'   => '-',
				]
			];
			$lengths = [
				'name'    => 5,
				'comment' => 11,
				'type'    => 4,
				'null'    => 4,
				'primary' => 3,
				'default' => 7,
				'extra'   => 5,
			];
			foreach ($definition['fields'] as $key => $value) {
				$field = [
					'name'    => $key,
					'comment' => $value['comment'] ?? '',
					'type'    => $value['type'],
					'null'    => ($value['not null'] ?? false) ? 'NO' : 'YES',
					'primary' => ($value['primary'] ?? false) ? 'PRI' : '',
					'default' => $value['default'] ?? 'NULL',
					'extra'   => $value['extra']   ?? '',
				];

				foreach ($field as $fieldName => $fieldvalue) {
					$lengths[$fieldName] = max($lengths[$fieldName] ?? 0, strlen($fieldvalue));
				}
				$fields[] = $field;

				if (!empty($value['foreign'])) {
					$foreign[] = [
						'field'       => $key,
						'targettable' => array_keys($value['foreign'])[0],
						'targetfield' => array_values($value['foreign'])[0]
					];
				}
			}

			array_walk_recursive($fields, function (&$value, $key) use ($lengths) {
				$value = str_pad($value, $lengths[$key], $value === '-' ? '-' : ' ');
			});

			$tables[] = ['name' => $name, 'comment' => $definition['comment']];
			$content  = Renderer::replaceMacros(Renderer::getMarkupTemplate('structure.tpl'), [
				'$name'    => $name,
				'$comment' => $definition['comment'],
				'$fields'  => $fields,
				'$indexes' => $indexes,
				'$foreign' => $foreign,
			]);
			$filename = $basePath . static::DOC_PATH_PREFIX . 'db-' . $name . '.md';
			file_put_contents($filename, $content);
		}
		asort($tables);
		$content = Renderer::replaceMacros(Renderer::getMarkupTemplate('tables.tpl'), [
			'$tables' => $tables,
		]);
		$filename = $basePath . static::DOC_PATH_PREFIX . 'index.md';
		file_put_contents($filename, $content);
	}
}
