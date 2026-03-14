<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Photo;
use Friendica\Util\DateTimeFormat;

/**
 * Class MediaExif
 *
 * This Model class handles exif media interactions.
 */
class MediaExif
{
	/**
	 * Insert a post-media-exif record
	 *
	 * @param int   $media_id Id of the related media entry
	 * @param int   $uri_id   Uri-Id of the related post
	 * @param array $data     Array with Exif data
	 * @return bool
	 */
	public static function insert(int $media_id, int $uri_id, array $data): bool
	{
		$exif = self::translate($data);

		$row = [
			'media-id'          => $media_id,
			'uri-id'            => $uri_id,
			'raw-data'          => json_encode($data),
			'FocalLength'       => self::getFocalLength($exif),
			'ExposureTime'      => self::getExposureTime($exif),
			'LensSpecification' => self::getLensSpecification($exif),
			'ApertureFNumber'   => $data['COMPUTED']['ApertureFNumber'] ?? null,
			'FocusDistance'     => $data['COMPUTED']['FocusDistance']   ?? null,
			'CCDWidth'          => $data['COMPUTED']['CCDWidth']        ?? null,
			'ISOSpeedRatings'   => $exif['ISOSpeedRatings']             ?? null,
			'DateTime'          => self::getDateTime($exif),
			'DateTimeOriginal'  => self::getDateTimeOriginal($exif),
			'DateTimeDigitized' => self::getDateTimeDigitized($exif),
			'BodySerialNumber'  => $exif['BodySerialNumber']      ?? null,
			'Orientation'       => $exif['Orientation']           ?? null,
			'Artist'            => $exif['Artist']                ?? null,
			'Copyright'         => $data['COMPUTED']['Copyright'] ?? null,
			'ExpandFilm'        => $exif['ExpandFilm']            ?? null,
			'ExpandLens'        => $exif['ExpandLens']            ?? null,
			'HostComputer'      => $exif['HostComputer']          ?? null,
			'ImageDescription'  => $exif['ImageDescription']      ?? null,
			"ImageUniqueID"     => $exif['ImageUniqueID']         ?? null,
			"LensMake"          => $exif['LensMake']              ?? null,
			"LensModel"         => $exif['LensModel']             ?? null,
			'Make'              => $exif['Make']                  ?? null,
			'MakerNote'         => $exif['MakerNote']             ?? null,
			'Model'             => $exif['Model']                 ?? null,
			'OwnerName'         => $exif['OwnerName']             ?? null,
			'Software'          => $exif['Software']              ?? null,
			"UserComment"       => $exif['UserComment']           ?? null,
		];

		if (isset($exif['GPSLatitude']) && isset($exif['GPSLatitudeRef']) && isset($exif['GPSLongitude']) && isset($exif['GPSLongitudeRef'])) {
			$row['coord'] = Photo::getGps($exif['GPSLatitude'], $exif['GPSLatitudeRef']) . ' ' . Photo::getGps($exif['GPSLongitude'], $exif['GPSLongitudeRef']);
		}

		$row = DI::dbaDefinition()->truncateFieldsForTable('post-media-exif', $row);

		$result = DBA::insert('post-media-exif', $row, Database::INSERT_UPDATE);
		DI::logger()->info('Updated media exif', ['result' => $result, 'row' => $row]);
		return $result;
	}

	/**
	 * Get the lens specification from exif data
	 *
	 * @param array $exif
	 * @return string|null
	 */
	private static function getLensSpecification(array $exif): ?string
	{
		if (!isset($exif['LensSpecification']) || !is_array($exif['LensSpecification']) || count($exif['LensSpecification']) != 4) {
			return null;
		}

		$vals = [];
		foreach ($exif['LensSpecification'] as $val) {
			$parts = explode('/', $val);
			if (count($parts) == 2 && is_numeric($parts[0]) && is_numeric($parts[1]) && $parts[1] != 0) {
				$vals[] = (float) $parts[0] / (float) $parts[1];
			} else {
				$vals[] = null;
			}
		}

		$LensSpecification = null;
		if (count($vals) == 4 && !in_array(null, $vals)) {
			if (isset($vals[0]) && $vals[0] == $vals[1]) {
				$LensSpecification = round($vals[0]) . 'mm';
			} elseif (isset($vals[0]) && isset($vals[1])) {
				$LensSpecification = round($vals[0]) . '-' . round($vals[1]) . 'mm';
			}
			if (isset($vals[2]) && $vals[2] == $vals[3]) {
				$LensSpecification .= ' f/' . round($vals[2], 1);
			} elseif (isset($vals[2]) && isset($vals[3])) {
				$LensSpecification .= ' f/' . round($vals[2], 1) . '-' . round($vals[3], 1);
			}
		}

		return $LensSpecification;
	}

	/**
	 * Get the focal length from exif data
	 *
	 * @param array $exif
	 * @return string|null
	 */
	private static function getFocalLength(array $exif): ?string
	{
		if (!isset($exif['FocalLength'])) {
			return null;
		}

		$parts = explode('/', $exif['FocalLength']);
		if (count($parts) == 2 && is_numeric($parts[0]) && is_numeric($parts[1]) && $parts[1] != 0) {
			return round((float) $parts[0] / (float) $parts[1], 1) . ' mm';
		}

		return null;
	}

	/**
	 * Get the exposure time from exif data
	 *
	 * @param array $exif
	 * @return string|null
	 */
	private static function getExposureTime(array $exif): ?string
	{
		if (!isset($exif['ExposureTime'])) {
			return null;
		}

		$parts = explode('/', $exif['ExposureTime']);
		if (count($parts) == 2 && is_numeric($parts[0]) && is_numeric($parts[1]) && $parts[1] != 0) {
			$value = (float) $parts[0] / (float) $parts[1];
			if ($value >= 1) {
				return (string) round($value, 0);
			} elseif ($value > 0) {
				return '1/' . round(1 / $value);
			}
		}

		return null;
	}

	/**
	 * Get the DateTime from exif data
	 *
	 * @param array $exif
	 * @return string|null
	 */
	private static function getDateTime(array $exif): ?string
	{
		$dateTime = $exif['DateTime'] ?? null;
		if ($dateTime && isset($exif['OffsetTime'])) {
			$dateTime .= ' ' . $exif['OffsetTime'];
		}
		return $dateTime ? DateTimeFormat::utc($dateTime) : null;
	}

	/**
	 * Get the DateTimeOriginal from exif data
	 *
	 * @param array $exif
	 * @return string|null
	 */
	private static function getDateTimeOriginal(array $exif): ?string
	{
		$dateTime = $exif['DateTimeOriginal'] ?? null;
		if ($dateTime && isset($exif['OffsetTimeOriginal'])) {
			$dateTime .= ' ' . $exif['OffsetTimeOriginal'];
		}
		return $dateTime ? DateTimeFormat::utc($dateTime) : null;
	}

	/**
	 * Get the DateTimeDigitized from exif data
	 *
	 * @param array $exif
	 * @return string|null
	 */
	private static function getDateTimeDigitized(array $exif): ?string
	{
		$dateTime = $exif['DateTimeDigitized'] ?? null;
		if ($dateTime && isset($exif['OffsetTimeDigitized'])) {
			$dateTime .= ' ' . $exif['OffsetTimeDigitized'];
		}
		return $dateTime ? DateTimeFormat::utc($dateTime) : null;
	}

	/**
	 * Translation for all tags that aren't known by "exif_read_data"
	 * @see https://exiv2.org/tags.html
	 * @param array $data
	 * @return array
	 */
	public static function translate(array $data): array
	{
		$translation = [
			'UndefinedTag:0x4746' => 'Rating',
			'UndefinedTag:0x8830' => 'SensitivityType',
			'UndefinedTag:0x8831' => 'StandardOutputSensitivity',
			'UndefinedTag:0x8832' => 'RecommendedExposureIndex',
			'UndefinedTag:0x9010' => 'OffsetTime',
			'UndefinedTag:0x9011' => 'OffsetTimeOriginal',
			'UndefinedTag:0x9012' => 'OffsetTimeDigitized',
			'UndefinedTag:0x9402' => 'Pressure',
			'UndefinedTag:0xA431' => 'BodySerialNumber',
			'UndefinedTag:0xA432' => 'LensSpecification',
			'UndefinedTag:0xA433' => 'LensMake',
			'UndefinedTag:0xA434' => 'LensModel',
			'UndefinedTag:0xA460' => 'CompositeImage',
			'UndefinedTag:0xAFC1' => 'ExpandLens',
			'UndefinedTag:0xAFC2' => 'ExpandFilm',
			'UndefinedTag:0xC4A5' => 'PrintImageMatching',
		];
		$exif = [];
		foreach ($data as $key => $value) {
			if (isset($translation[$key])) {
				$exif[$translation[$key]] = $value;
			} else {
				$exif[$key] = $value;
			}
		}

		$translation = [
			'InternalSerialNumber' => 'BodySerialNumber',
			'Author'               => 'Artist',
		];

		foreach ($translation as $key => $value) {
			if (!isset($exif[$value]) && isset($exif[$key])) {
				$exif[$value] = $exif[$key];
			}
			unset($exif[$key]);
		}

		if (isset($data['COMPUTED']['UserComment'])) {
			$exif['UserComment'] = $data['COMPUTED']['UserComment'];
		}

		if (!isset($exif['UserComment']) && isset($data['COMMENT']) && is_array($data['COMMENT'])) {
			$exif['UserComment'] = implode("\n", $data['COMMENT']);
			unset($exif['COMMENT']);
		}
		return $exif;
	}
}
