<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Addon;

/**
 * Information about an addon
 */
final class AddonInfo
{
	/**
	 * Parse addon comment in search of addon infos.
	 *
	 * like
	 * \code
	 *   * Name: addon
	 *   * Description: An addon which plugs in
	 * . * Version: 1.2.3
	 *   * Author: John <profile url>
	 *   * Author: Jane <email>
	 *   * Maintainer: Jess without link
	 *   * Maintainer: Robin <email>
	 *   * Status: in development
	 * \endcode
	 *
	 * @internal Never create this object by yourself, use `Friendica\Core\Addon\AddonHelper::getAddonInfo()` instead.
	 * @see Friendica\Core\Addon\AddonHelper::getAddonInfo()
	 *
	 * @param string $addonId the name of the addon
	 * @param string $raw The raw file content
	 */
	public static function fromString(string $addonId, string $raw): self
	{
		$data = [
			'id' => $addonId,
		];

		$ll = explode("\n", $raw);

		foreach ($ll as $l) {
			$l = trim($l, "\t\n\r */");
			if ($l !== '') {
				$addon_info = array_map('trim', explode(":", $l, 2));
				if (count($addon_info) < 2) {
					continue;
				}

				[$type, $v] = $addon_info;
				$type       = strtolower($type);

				if ($type === 'author' || $type === 'maintainer') {
					$r = preg_match("|([^<]+)<([^>]+)>|", $v, $m);
					if ($r === false || $r === 0) {
						$data[$type][] = ['name' => trim($v)];
					} else {
						$data[$type][] = ['name' => trim($m[1]), 'link' => $m[2]];
					}
				} else {
					$data[$type] = $v;
				}
			}
		}

		// rename author to authors
		if (array_key_exists('author', $data)) {
			$data['authors'] = $data['author'];
			unset($data['author']);
		}

		// rename maintainer to maintainers
		if (array_key_exists('maintainer', $data)) {
			$data['maintainers'] = $data['maintainer'];
			unset($data['maintainer']);
		}

		return self::fromArray($data);
	}

	/**
	 * @internal Never create this object by yourself, use `Friendica\Core\Addon\AddonHelper::getAddonInfo()` instead.
	 *
	 * @see Friendica\Core\Addon\AddonHelper::getAddonInfo()
	 */
	public static function fromArray(array $info): self
	{
		$addonInfo              = new self();
		$addonInfo->id          = array_key_exists('id', $info) ? (string) $info['id'] : '';
		$addonInfo->name        = array_key_exists('name', $info) ? (string) $info['name'] : '';
		$addonInfo->description = array_key_exists('description', $info) ? (string) $info['description'] : '';
		$addonInfo->authors     = array_key_exists('authors', $info) ? self::parseContributors($info['authors']) : [];
		$addonInfo->maintainers = array_key_exists('maintainers', $info) ? self::parseContributors($info['maintainers']) : [];
		$addonInfo->version     = array_key_exists('version', $info) ? (string) $info['version'] : '';
		$addonInfo->status      = array_key_exists('status', $info) ? (string) $info['status'] : '';

		return $addonInfo;
	}

	/**
	 * @param mixed $entries
	 */
	private static function parseContributors($entries): array
	{
		if (!is_array($entries)) {
			return [];
		}

		$contributors = [];

		foreach ($entries as $entry) {
			if (!is_array($entry)) {
				continue;
			}

			if (!array_key_exists('name', $entry)) {
				continue;
			}

			$contributor = [
				'name' => (string) $entry['name'],
			];

			if (array_key_exists('link', $entry)) {
				$contributor['link'] = (string) $entry['link'];
			}

			$contributors[] = $contributor;
		}

		return $contributors;
	}

	private string $id = '';

	private string $name = '';

	private string $description = '';

	private array $authors = [];

	private array $maintainers = [];

	private string $version = '';

	private string $status = '';

	private function __construct()
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function getAuthors(): array
	{
		return $this->authors;
	}

	public function getMaintainers(): array
	{
		return $this->maintainers;
	}

	public function getVersion(): string
	{
		return $this->version;
	}

	public function getStatus(): string
	{
		return $this->status;
	}
}
