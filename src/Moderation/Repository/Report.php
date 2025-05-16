<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Moderation\Repository;

use Friendica\Database\Database;
use Friendica\Model\Post;
use Friendica\Moderation\Collection\Report\Posts as PostsCollection;
use Friendica\Moderation\Collection\Report\Rules as RulesCollection;
use Friendica\Moderation\Entity\Report as ReportEntity;
use Friendica\Moderation\Factory\Report as ReportFactory;
use Friendica\Moderation\Factory\Report\Post as PostFactory;
use Friendica\Moderation\Factory\Report\Rule as RuleFactory;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

final class Report extends \Friendica\BaseRepository
{
	protected static $table_name = 'report';

	/** @var ReportFactory */
	protected $factory;
	/** @var PostFactory */
	protected $postFactory;
	/** @var RuleFactory */
	protected $ruleFactory;

	public function __construct(Database $database, LoggerInterface $logger, ReportFactory $factory, PostFactory $postFactory, RuleFactory $ruleFactory)
	{
		parent::__construct($database, $logger, $factory);

		$this->factory     = $factory;
		$this->postFactory = $postFactory;
		$this->ruleFactory = $ruleFactory;
	}

	public function selectOneById(int $lastInsertId): ReportEntity
	{
		return $this->_selectOne(['id' => $lastInsertId]);
	}

	public function save(ReportEntity $Report): ReportEntity
	{
		$fields = [
			'reporter-id'     => $Report->reporterCid,
			'uid'             => $Report->reporterUid,
			'cid'             => $Report->cid,
			'gsid'            => $Report->gsid,
			'comment'         => $Report->comment,
			'forward'         => $Report->forward,
			'category-id'     => $Report->category,
			'public-remarks'  => $Report->publicRemarks,
			'private-remarks' => $Report->privateRemarks,
			'last-editor-uid' => $Report->lastEditorUid,
			'assigned-uid'    => $Report->assignedUid,
			'status'          => $Report->status,
			'resolution'      => $Report->resolution,
			'created'         => $Report->created->format(DateTimeFormat::MYSQL),
			'edited'          => $Report->edited ? $Report->edited->format(DateTimeFormat::MYSQL) : null,
		];

		if ($Report->id) {
			$this->db->update(self::$table_name, $fields, ['id' => $Report->id]);
		} else {
			$this->db->insert(self::$table_name, $fields, Database::INSERT_IGNORE);

			$newReportId = $this->db->lastInsertId();

			foreach ($Report->posts as $post) {
				if (Post::exists(['uri-id' => $post->uriId])) {
					$this->db->insert('report-post', ['rid' => $newReportId, 'uri-id' => $post->uriId, 'status' => $post->status]);
				} else {
					$this->logger->notice('Post does not exist', ['uri-id' => $post->uriId, 'report' => $Report]);
				}
			}

			foreach ($Report->rules as $rule) {
				$this->db->insert('report-rule', ['rid' => $newReportId, 'line-id' => $rule->lineId, 'text' => $rule->text]);
			}

			$Report = $this->selectOneById($newReportId);
		}

		return $Report;
	}

	/**
	 * @throws NotFoundException
	 */
	protected function _selectOne(array $condition, array $params = []): ReportEntity
	{
		$fields = $this->db->selectFirst(self::$table_name, [], $condition, $params);
		if (!$this->db->isResult($fields)) {
			throw new NotFoundException();
		}

		$reportPosts = new PostsCollection(array_map([$this->postFactory, 'createFromTableRow'], $this->db->selectToArray('report-post', ['uri-id', 'status'], ['rid' => $condition['id'] ?? 0])));
		$reportRules = new RulesCollection(array_map([$this->ruleFactory, 'createFromTableRow'], $this->db->selectToArray('report-rule', ['line-id', 'text'], ['rid' => $condition['id'] ?? 0])));

		return $this->factory->createFromTableRow($fields, $reportPosts, $reportRules);
	}
}
