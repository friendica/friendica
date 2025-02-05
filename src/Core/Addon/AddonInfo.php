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
	 * @internal Never create this object by yourself, use `Friendica\Core\Addon\AddonHelper::getAddonInfo()` instead.
	 *
	 * @see Friendica\Core\Addon\AddonHelper::getAddonInfo()
	 */
	public static function fromArray(array $info): self
	{
		$id          = array_key_exists('id', $info) ? (string) $info['id'] : '';
		$name        = array_key_exists('name', $info) ? (string) $info['name'] : '';
		$description = array_key_exists('description', $info) ? (string) $info['description'] : '';
		$authors     = array_key_exists('authors', $info) ? self::parseContributors($info['authors']) : [];
		$maintainers = array_key_exists('maintainers', $info) ? self::parseContributors($info['maintainers']) : [];
		$version     = array_key_exists('version', $info) ? (string) $info['version'] : '';
		$status      = array_key_exists('status', $info) ? (string) $info['status'] : '';

		return new self(
			$id,
			$name,
			$description,
			$authors,
			$maintainers,
			$version,
			$status
		);
	}

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

	private function __construct(
		string $id,
		string $name,
		string $description,
		array $authors,
		array $maintainers,
		string $version,
		string $status
	) {
		$this->id          = $id;
		$this->name        = $name;
		$this->description = $description;
		$this->authors     = $authors;
		$this->maintainers = $maintainers;
		$this->version     = $version;
		$this->status      = $status;
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
