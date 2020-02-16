#!/usr/bin/env php
<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU APGL version 3 or any later version
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
 * Used to check/generate entities for the Friendica codebase
 *
 */

require dirname(__DIR__) . '/../vendor/autoload.php';

/**
 * Custom file printer with tab indention and one line between methods
 */
class FriendicaPhpPrinter extends \Nette\PhpGenerator\Printer
{
	protected $linesBetweenMethods = 1;
}

// replaces digits with their names
function digitToText(string $name) {
	$name = str_replace('0', 'zero_', $name);
	$name = str_replace('1', 'one_', $name);
	$name = str_replace('2', 'two_', $name);
	$name = str_replace('3', 'three_', $name);
	$name = str_replace('4', 'four_', $name);
	$name = str_replace('5', 'five_', $name);
	$name = str_replace('6', 'six_', $name);
	$name = str_replace('7', 'seven_', $name);
	$name = str_replace('8', 'eight_', $name);
	$name = str_replace('9', 'nine_', $name);
	return $name;
}

// Replaces underlines ("_") with camelCase notation (for variables)
function camelCase($str) {
	$i = array("-","_");
	$str = digitToText($str);
	$str = preg_replace('/([a-z])([A-Z])/', "\\1 \\2", $str);
	$str = preg_replace('@[^a-zA-Z0-9\-_ ]+@', '', $str);
	$str = str_replace($i, ' ', $str);
	$str = str_replace(' ', '', ucwords(strtolower($str)));
	$str = strtolower(substr($str,0,1)).substr($str,1);
	return $str;
}

// Like camelcase, but with Uppercasing the first letter (for classes)
function toClassName($str) {
	$str = camelCase($str);
	return ucfirst($str);
}

// Custom mapping of db-types to PHP types
function getDbType(string $type) {
	switch ($type) {
		case 'int unsigned':
		case 'longblob':
		case 'mediumint unsigned':
		case 'int':
			return Nette\PhpGenerator\Type::INT;
		case 'datetime':
			// @todo Replace with "real" datetime
			return Nette\PhpGenerator\Type::STRING;
		case 'boolean':
			return Nette\PhpGenerator\Type::BOOL;
		default:
			return Nette\PhpGenerator\Type::STRING;
	}
}

// returns the class name based on a given table name
function getClassName(string $str) {
	$names = preg_split('/[-]+/', $str);
	return toClassName($names[count($names) - 1]);
}

// returns a directory sequence based on a given table name
function getDirs(string $str, string $del = '/') {
	$names = preg_split('/[-]+/', $str);
	$dirs = '';
	for ($i = 0; $i < count($names) - 1; $i++) {
		$dirs .= toClassName($names[$i]) . $del;
	}
	return substr($dirs, 0, (strlen($dirs) - strlen($del)));
}

$dbstructure = include __DIR__ . '/../../static/dbstructure.config.php';

foreach ($dbstructure as $name => $table) {
	$className = getClassName($name);
	$dirPath = getDirs($name, '/');
	$nsPath = getDirs($name, '\\');
	$generator = new Nette\PhpGenerator\ClassType($className);
	$generator->setExtends(\Friendica\BaseEntity::class);
	$generator->addComment(sprintf('Entity class for table %s', $name))
	          ->addComment('');

	$returnArray = $generator->addMethod('toArray')
	                         ->setPublic()
	                         ->addComment('{@inheritDoc}')
	                         ->addBody('return [');

	$file = new Nette\PhpGenerator\PhpFile();
	$file->addComment(<<<LIC
@copyright Copyright (C) 2020, Friendica

@license GNU APGL version 3 or any later version

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.

Used to check/generate entities for the Friendica codebase
LIC
	);
	$file->setStrictTypes();

	$namespace = $file->addNamespace('Friendica\Domain\Entity' . ($nsPath ? '\\' . $nsPath : ''));
	$namespace->addUse(\Friendica\BaseEntity::class);

	foreach ($table as $key => $value) {
		switch ($key) {
			case 'comment':
				$generator->addComment($value);
				break;
			case 'fields':
				foreach ($value as $field => $attributes) {
					$property    = $generator->addProperty(camelCase($field))->setPrivate();
					$getter      = $generator->addMethod(camelCase('get_' . $field))
					                         ->setPublic()
					                         ->addBody(sprintf('return $this->%s;', $property->getName()));
					$setter      = $generator->addMethod(camelCase('set_' . $field))
					                         ->setPublic()
					                         ->addBody(sprintf('$this->%s = $%s;', $property->getName(), $property->getName()));
					$setterParam = $setter->addParameter($property->getName());
					$returnArray->addBody(sprintf("\t'%s' => \$this->%s,", $field, $property->getName()));
					foreach ($attributes as $name => $attribute) {
						switch ($name) {
							case 'type':
								$property->addComment(sprintf('@var %s', getDbType($attribute)));
								$getter->addComment(sprintf('@return %s', getDbType($attribute)));
								$setter->addComment(sprintf('@param %s $%s', getDbType($attribute), $property->getName()));
								$setterParam->setType(getDbType($attribute));
								break;
							case 'comment':
								$property->addComment($attribute);
								$getter->addComment('Get ' . $attribute);
								$setter->addComment('Set ' . $attribute);
								break;
							case 'primary':
								if ($attribute) {
									$generator->removeMethod($setter->getName());
								}
								break;
							case 'relation':
								foreach ($attribute as $relTable => $relField) {
									$nsRel = getDirs($relTable, '\\');
									$generator->addMethod(camelCase('get_' . $relTable))
									          ->addComment(sprintf('Get %s', ($nsRel ? '\\' . $nsRel : '') . getClassName($relTable)))
									          ->addComment('')
									          ->addComment(sprintf('@return %s', ($nsRel ? '\\' . $nsRel : '') . getClassName($relTable)))
									          ->addBody('//@todo use closure')
									          ->addBody(sprintf('throw new NotImplementedException(\'lazy loading for %s is not implemented yet\');', camelCase($relField)));
									$namespace->addUse(Friendica\Network\HTTPException\NotImplementedException::class);
									if ($nsRel) {
										$namespace->addUse('Friendica\Domain\Entity\\' . $nsRel );
									}
								}
								break;
							case 'default':
								$property->setValue($attribute);
						}
					}
				}
				break;
		}
	}

	$returnArray->addBody('];');

	$class = $namespace->add($generator);

	$dir = __DIR__ . '/../../src/Domain/Entity/' . $dirPath . '/';
	if (!file_exists($dir)) {
		mkdir($dir, 0777, true);
	}

	file_put_contents($dir . $generator->getName() . '.php', (new FriendicaPhpPrinter())->printFile($file), FILE_USE_INCLUDE_PATH);
}
