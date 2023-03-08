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

namespace Friendica\Test\src\Model;

use Friendica\Model\FileTag;
use PHPUnit\Framework\TestCase;

class FileTagTest extends TestCase
{
    public function dataArrayToFile()
    {
        return [
            'list-category' => [
                'array' => ['1', '2', '3', 'a', 'b', 'c'],
                'type' => 'category',
                'file' => '<1><2><3><a><b><c>',
            ],
            'list-file' => [
                'array' => ['1', '2', '3', 'a', 'b', 'c'],
                'type' => 'file',
                'file' => '[1][2][3][a][b][c]',
            ],
            'chevron-category' => [
                'array' => ['Left < Center > Right'],
                'type' => 'category',
                'file' => '<Left %3c Center %3e Right>',
            ],
            'bracket-file' => [
                'array' => ['Glass [half-full]'],
                'type' => 'file',
                'file' => '[Glass %5bhalf-full%5d]',
            ],
            /** @see https://github.com/friendica/friendica/issues/7171 */
            'bug-7171-category' => [
                'array' => ['Science, Health, Medicine'],
                'type' => 'category',
                'file' => '<Science, Health, Medicine>',
            ],
            'bug-7171-file' => [
                'array' => ['Science, Health, Medicine'],
                'type' => 'file',
                'file' => '[Science, Health, Medicine]',
            ],
        ];
    }

    /**
     * Test convert saved folders arrays to a file/category field
     * @dataProvider dataArrayToFile
     *
     * @param array  $array
     * @param string $type
     * @param string $file
     */
    public function testArrayToFile(array $array, string $type, string $file)
    {
        self::assertEquals($file, FileTag::arrayToFile($array, $type));
    }

    public function dataFileToArray()
    {
        return [
            'list-category' => [
                'file' => '<1><2><3><a><b><c>',
                'type' => 'category',
                'array' => ['1', '2', '3', 'a', 'b', 'c'],
            ],
            'list-file' => [
                'file' => '[1][2][3][a][b][c]',
                'type' => 'file',
                'array' => ['1', '2', '3', 'a', 'b', 'c'],
            ],
            'combinedlist-category' => [
                'file' => '[1][2][3]<a><b><c>',
                'type' => 'category',
                'array' => ['a', 'b', 'c'],
            ],
            'combinedlist-file' => [
                'file' => '[1][2][3]<a><b><c>',
                'type' => 'file',
                'array' => ['1', '2', '3'],
            ],
            'chevron-category' => [
                'file' => '<Left %3c Center %3e Right>',
                'type' => 'category',
                'array' => ['Left < Center > Right'],
            ],
            'bracket-file' => [
                'file' => '[Glass %5bhalf-full%5d]',
                'type' => 'file',
                'array' => ['Glass [half-full]'],
            ],
            /** @see https://github.com/friendica/friendica/issues/7171 */
            'bug-7171-category' => [
                'file' => '<Science, Health, Medicine>',
                'type' => 'category',
                'array' => ['Science, Health, Medicine'],
            ],
            'bug-7171-file' => [
                'file' => '[Science, Health, Medicine]',
                'type' => 'file',
                'array' => ['Science, Health, Medicine'],
            ],
        ];
    }

    /**
     * Test convert different saved folders to a file/category field
     * @dataProvider dataFileToArray
     *
     * @param string $file
     * @param string $type
     * @param array  $array
     */
    public function testFileToArray(string $file, string $type, array $array)
    {
        self::assertEquals($array, FileTag::fileToArray($file, $type));
    }
}
