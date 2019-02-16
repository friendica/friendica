<?php

/**
 * @file src/Worker/UpdateGcontact.php
 */

namespace Friendica\Worker;

use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Network\Probe;
use Friendica\Protocol\PortableContact;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

class UpdateGContact extends AbstractWorker
{
	/**
	 * {@inheritdoc}
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function execute(array $parameters = [])
	{
		if (!$this->checkParameters($parameters, 1)) {
			return;
		}

		$contact_id = $parameters[0];
		$this->logger->info('update_gcontact: start');

		if (empty($contact_id)) {
			$this->logger->info('update_gcontact: no contact');
			return;
		}

		$r = q("SELECT * FROM `gcontact` WHERE `id` = %d", intval($contact_id));

		if (!DBA::isResult($r)) {
			return;
		}

		if (!in_array($r[0]["network"], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS])) {
			return;
		}

		$data = Probe::uri($r[0]["url"]);

		if (!in_array($data["network"], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS])) {
			if ($r[0]["server_url"] != "") {
				PortableContact::checkServer($r[0]["server_url"], $r[0]["network"]);
			}

			q("UPDATE `gcontact` SET `last_failure` = '%s' WHERE `id` = %d",
				DBA::escape(DateTimeFormat::utcNow()), intval($contact_id));
			return;
		}

		if (($data["name"] == "") && ($r[0]['name'] != "")) {
			$data["name"] = $r[0]['name'];
		}

		if (($data["nick"] == "") && ($r[0]['nick'] != "")) {
			$data["nick"] = $r[0]['nick'];
		}

		if (($data["addr"] == "") && ($r[0]['addr'] != "")) {
			$data["addr"] = $r[0]['addr'];
		}

		if (($data["photo"] == "") && ($r[0]['photo'] != "")) {
			$data["photo"] = $r[0]['photo'];
		}


		q("UPDATE `gcontact` SET `name` = '%s', `nick` = '%s', `addr` = '%s', `photo` = '%s'
					WHERE `id` = %d",
					DBA::escape($data["name"]),
					DBA::escape($data["nick"]),
					DBA::escape($data["addr"]),
					DBA::escape($data["photo"]),
			intval($contact_id)
		);

		q("UPDATE `contact` SET `name` = '%s', `nick` = '%s', `addr` = '%s', `photo` = '%s'
					WHERE `uid` = 0 AND `addr` = '' AND `nurl` = '%s'",
					DBA::escape($data["name"]),
					DBA::escape($data["nick"]),
					DBA::escape($data["addr"]),
					DBA::escape($data["photo"]),
					DBA::escape(Strings::normaliseLink($data["url"]))
		);

		q("UPDATE `contact` SET `addr` = '%s'
					WHERE `uid` != 0 AND `addr` = '' AND `nurl` = '%s'",
					DBA::escape($data["addr"]),
					DBA::escape(Strings::normaliseLink($data["url"]))
		);
	}
}
