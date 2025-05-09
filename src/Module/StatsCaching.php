<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Capability\ICanCacheInMemory;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Core\Lock\Type\CacheLock;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;
use Friendica\Network\HTTPException;

/**
 * Returns statistics of Cache / Lock instances
 *
 * @todo Currently not possible to get distributed cache statistics in case the distributed cache (for sessions) is different to the normal cache (not possible to get the distributed cache instance yet)
 */
class StatsCaching extends BaseModule
{
	private IManageConfigValues $config;
	private ICanCache $cache;
	private ICanLock $lock;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, IManageConfigValues $config, ICanCache $cache, ICanLock $lock, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config = $config;
		$this->cache  = $cache;
		$this->lock   = $lock;
	}

	private function isAllowed(array $request): bool
	{
		return !empty($request['key']) && $request['key'] == $this->config->get('system', 'stats_key');
	}

	/**
	 * @throws NotFoundException In case the rquest isn't allowed
	 */
	protected function content(array $request = []): string
	{
		if (!$this->isAllowed($request)) {
			throw new HTTPException\NotFoundException($this->l10n->t('Page not found.'));
		}
		return '';
	}

	protected function rawContent(array $request = [])
	{
		if (!$this->isAllowed($request)) {
			return;
		}

		$data = [];

		// OPcache
		if (function_exists('opcache_get_status')) {
			$status          = opcache_get_status(false);
			$data['opcache'] = [
				'enabled'            => $status['opcache_enabled']                          ?? false,
				'hit_rate'           => $status['opcache_statistics']['opcache_hit_rate']   ?? null,
				'used_memory'        => $status['memory_usage']['used_memory']              ?? null,
				'free_memory'        => $status['memory_usage']['free_memory']              ?? null,
				'num_cached_scripts' => $status['opcache_statistics']['num_cached_scripts'] ?? null,
			];
		} else {
			$data['opcache'] = [
				'enabled' => false,
			];
		}

		if ($this->cache instanceof ICanCacheInMemory) {
			$data['cache'] = [
				'type'  => $this->cache->getName(),
				'stats' => $this->cache->getStats(),
			];
		} else {
			$data['cache'] = [
				'type' => $this->cache->getName(),
			];
		}

		if ($this->lock instanceof CacheLock) {
			$data['lock'] = [
				'type'  => $this->lock->getName(),
				'stats' => $this->lock->getCacheStats(),
			];
		} else {
			$data['lock'] = [
				'type' => $this->lock->getName(),
			];
		}

		$this->response->setType('json', 'application/json; charset=utf-8');
		$this->response->addContent(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	}
}
