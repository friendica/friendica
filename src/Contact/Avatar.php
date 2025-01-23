<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Contact;

use Friendica\DI;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Object\Image;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Proxy;

/**
 * functions for handling contact avatar caching
 */
class Avatar
{
	const BASE_PATH = '/avatar/';

	/**
	 * Returns a field array with locally cached avatar pictures
	 *
	 * @param array $contact Contact array
	 * @param string $avatar Link to avatar picture
	 * @param bool   $force  force picture update
	 * @return array
	 */
	public static function fetchAvatarContact(array $contact, string $avatar, bool $force = false): array
	{
		$fields = ['avatar' => $avatar, 'avatar-date' => DateTimeFormat::utcNow(), 'photo' => '', 'thumb' => '', 'micro' => ''];

		if (!DI::config()->get('system', 'avatar_cache')) {
			self::deleteCache($contact);
			return $fields;
		}

		if (DI::baseUrl()->isLocalUrl($avatar) || empty($avatar)) {
			self::deleteCache($contact);
			return $fields;
		}

		if (($avatar != $contact['avatar']) || $force) {
			self::deleteCache($contact);
			DI::logger()->debug('Avatar file name changed', ['new' => $avatar, 'old' => $contact['avatar']]);
		} elseif (self::isCacheFile($contact['photo']) && self::isCacheFile($contact['thumb']) && self::isCacheFile($contact['micro'])) {
			$fields['photo'] = $contact['photo'];
			$fields['thumb'] = $contact['thumb'];
			$fields['micro'] = $contact['micro'];
			DI::logger()->debug('Using existing cache files', ['uri-id' => $contact['uri-id'], 'fields' => $fields]);
			return $fields;
		}

		try {
			$fetchResult = HTTPSignature::fetchRaw($avatar, 0, [HttpClientOptions::ACCEPT_CONTENT => [HttpClientAccept::IMAGE]]);
		} catch (\Exception $exception) {
			DI::logger()->notice('Avatar is invalid', ['avatar' => $avatar, 'exception' => $exception]);
			return $fields;
		}

		if (!$fetchResult->isSuccess()) {
			DI::logger()->debug('Fetching was unsuccessful', ['avatar' => $avatar]);
			return $fields;
		}

		$img_str = $fetchResult->getBodyString();
		if (empty($img_str)) {
			DI::logger()->debug('Avatar is invalid', ['avatar' => $avatar]);
			return $fields;
		}

		$image = new Image($img_str, $fetchResult->getContentType(), $avatar);
		if (!$image->isValid()) {
			DI::logger()->debug('Avatar picture is invalid', ['avatar' => $avatar]);
			return $fields;
		}

		$filename  = self::getFilename($contact['url']);
		$timestamp = time();

		$fields['blurhash'] = $image->getBlurHash($img_str);

		$fields['photo'] = self::storeAvatarCache($image, $filename, Proxy::PIXEL_SMALL, $timestamp);
		$fields['thumb'] = self::storeAvatarCache($image, $filename, Proxy::PIXEL_THUMB, $timestamp);
		$fields['micro'] = self::storeAvatarCache($image, $filename, Proxy::PIXEL_MICRO, $timestamp);

		DI::logger()->debug('Storing new avatar cache', ['uri-id' => $contact['uri-id'], 'fields' => $fields]);

		return $fields;
	}

	public static function storeAvatarByImage(array $contact, Image $image): array
	{
		$fields = ['photo' => '', 'thumb' => '', 'micro' => ''];

		if (!DI::config()->get('system', 'avatar_cache')) {
			self::deleteCache($contact);
			return $fields;
		}

		if (DI::baseUrl()->isLocalUrl($contact['avatar']) || empty($contact['avatar'])) {
			self::deleteCache($contact);
			return $fields;
		}

		$filename  = self::getFilename($contact['url']);
		$timestamp = time();

		$fields['photo'] = self::storeAvatarCache($image, $filename, Proxy::PIXEL_SMALL, $timestamp);
		$fields['thumb'] = self::storeAvatarCache($image, $filename, Proxy::PIXEL_THUMB, $timestamp);
		$fields['micro'] = self::storeAvatarCache($image, $filename, Proxy::PIXEL_MICRO, $timestamp);

		return $fields;
	}

	private static function getFilename(string $url): string
	{
		$guid = hash('ripemd128', $url);
		return substr($guid, 0, 3) . '/' . substr($guid, 4) . '-';
	}

	private static function storeAvatarCache(Image $image, string $filename, int $size, int $timestamp): string
	{
		$image->scaleDown($size);
		if (is_null($image) || !$image->isValid()) {
			return '';
		}

		$path = $filename . $size . $image->getExt();

		$basepath = self::basePath();
		if (empty($basepath)) {
			return '';
		}

		$filepath = $basepath . $path;

		$dirpath = $basepath;

		DI::profiler()->startRecording('file');

		// Fetch the permission and group ownership of the "avatar" path and apply to all files
		$dir_perm  = fileperms($dirpath) & 0777;
		$file_perm = fileperms($dirpath) & 0666;
		$group     = filegroup($dirpath);

		// Check directory permissions of all parts of the path
		foreach (explode('/', dirname($filename)) as $part) {
			$dirpath .= $part . '/';

			if (!file_exists($dirpath)) {
				if (!@mkdir($dirpath, $dir_perm) && !file_exists($dirpath)) {
					DI::logger()->warning('Directory could not be created', ['directory' => $dirpath]);
				}
			} elseif ((($old_perm = fileperms($dirpath) & 0777) != $dir_perm) && !chmod($dirpath, $dir_perm)) {
				DI::logger()->warning('Directory permissions could not be changed', ['directory' => $dirpath, 'old' => $old_perm, 'new' => $dir_perm]);
			}

			if ((($old_group = filegroup($dirpath)) != $group) && !chgrp($dirpath, $group)) {
				DI::logger()->warning('Directory group could not be changed', ['directory' => $dirpath, 'old' => $old_group, 'new' => $group]);
			}
		}

		if (!file_put_contents($filepath, $image->asString())) {
			DI::logger()->warning('File could not be created', ['file' => $filepath]);
		}

		$old_perm  = fileperms($filepath) & 0666;
		$old_group = filegroup($filepath);

		if (($old_perm != $file_perm) && !chmod($filepath, $file_perm)) {
			DI::logger()->warning('File permissions could not be changed', ['file' => $filepath, 'old' => $old_perm, 'new' => $file_perm]);
		}

		if (($old_group != $group) && !chgrp($filepath, $group)) {
			DI::logger()->warning('File group could not be changed', ['file' => $filepath, 'old' => $old_group, 'new' => $group]);
		}

		DI::profiler()->stopRecording();

		if (!file_exists($filepath)) {
			DI::logger()->warning('Avatar cache file could not be stored', ['file' => $filepath]);
			return '';
		}

		return self::baseUrl() . $path . '?ts=' . $timestamp;
	}

	/**
	 * Check if the avatar cache file is locally stored
	 *
	 * @param string $avatar
	 * @return boolean
	 */
	private static function isCacheFile(string $avatar): bool
	{
		return !empty(self::getCacheFile($avatar));
	}

	/**
	 * Fetch the name of locally cached avatar pictures
	 *
	 * @param string $avatar
	 * @return string
	 */
	private static function getCacheFile(string $avatar): string
	{
		$parts = parse_url($avatar);
		if (empty($parts['host']) || ($parts['host'] != parse_url(self::baseUrl(), PHP_URL_HOST))) {
			return '';
		}

		$avatarpath = parse_url(self::baseUrl(), PHP_URL_PATH);
		$pos        = strpos($parts['path'], $avatarpath);
		if ($pos !== 0) {
			return '';
		}

		$filename = self::basePath() . substr($parts['path'], strlen($avatarpath));

		DI::profiler()->startRecording('file');
		$exists = file_exists($filename);
		DI::profiler()->stopRecording();

		if (!$exists) {
			return '';
		}
		return $filename;
	}

	/**
	 * Delete locally cached avatar pictures of a contact
	 */
	public static function deleteCache(array $contact): bool
	{
		$existed = (self::isCacheFile($contact['photo']) || self::isCacheFile($contact['thumb']) || self::isCacheFile($contact['micro']));
		self::deleteCacheFile($contact['photo']);
		self::deleteCacheFile($contact['thumb']);
		self::deleteCacheFile($contact['micro']);

		return $existed;
	}

	/**
	 * Delete a locally cached avatar picture
	 *
	 * @param string $avatar
	 * @return void
	 */
	private static function deleteCacheFile(string $avatar)
	{
		$localFile = self::getCacheFile($avatar);
		if (!empty($localFile)) {
			@unlink($localFile);
			DI::logger()->debug('Unlink avatar', ['avatar' => $avatar, 'local' => $localFile]);
		}
	}

	/**
	 * Fetch the avatar base path
	 *
	 * @return string
	 */
	private static function basePath(): string
	{
		$basepath = DI::config()->get('system', 'avatar_cache_path');
		if (empty($basepath)) {
			$basepath = DI::basePath() . self::BASE_PATH;
		}
		$basepath = rtrim($basepath, '/') . '/';

		if (!file_exists($basepath)) {
			// We only automatically create the folder when it is in the web root
			if (strpos($basepath, DI::basePath()) !== 0) {
				DI::logger()->warning('Base directory does not exist', ['directory' => $basepath]);
				return '';
			}
			if (!mkdir($basepath, 0775)) {
				DI::logger()->warning('Base directory could not be created', ['directory' => $basepath]);
				return '';
			}
		}

		return $basepath;
	}

	/**
	 * Fetch the avatar base url
	 *
	 * @return string
	 */
	public static function baseUrl(): string
	{
		$baseurl = DI::config()->get('system', 'avatar_cache_url');
		if (!empty($baseurl)) {
			return rtrim($baseurl, '/') . '/';
		}

		return DI::baseUrl() . self::BASE_PATH;
	}
}
