<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Util\Writer;

use Friendica\Core\Renderer;
use Friendica\Database\Definition\DbaDefinition;
use Friendica\Network\HTTPException\ServiceUnavailableException;

/**
 * Utility class to write content into the '/doc' directory
 */
class DocWriter
{
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
		foreach ($definition->getAll() as $name => $definition) {
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
				$field            = [];
				$field['name']    = $key;
				$field['comment'] = $value['comment'] ?? '';
				$field['type']    = $value['type'];
				$field['null']    = ($value['not null'] ?? false) ? 'NO' : 'YES';
				$field['primary'] = ($value['primary'] ?? false) ? 'PRI' : '';
				$field['default'] = $value['default'] ?? 'NULL';
				$field['extra']   = $value['extra']   ?? '';

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
			$filename = $basePath . '/doc/database/db_' . $name . '.md';
			file_put_contents($filename, $content);
		}
		asort($tables);
		$content = Renderer::replaceMacros(Renderer::getMarkupTemplate('tables.tpl'), [
			'$tables' => $tables,
		]);
		$filename = $basePath . '/doc/database.md';
		file_put_contents($filename, $content);
	}
}
