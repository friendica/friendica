<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Calendar\Event;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Content\Widget\CalendarExport;
use Friendica\Core\ACL;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Event as EventModel;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Util\ACLFormatter;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Friendica\Util\Temporal;
use Psr\Log\LoggerInterface;

/**
 * The editor-view of an event
 */
class Form extends BaseModule
{
	public const MODE_NEW  = 'new';
	public const MODE_EDIT = 'edit';
	public const MODE_COPY = 'copy';

	public const ALLOWED_MODES = [
		self::MODE_NEW,
		self::MODE_EDIT,
		self::MODE_COPY,
	];

	/** @var IHandleUserSessions */
	protected $session;
	/** @var SystemMessages */
	protected $sysMessages;
	/** @var ACLFormatter */
	protected $aclFormatter;
	/** @var App\Page */
	protected $page;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $session, SystemMessages $sysMessages, ACLFormatter $aclFormatter, App\Page $page, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session      = $session;
		$this->sysMessages  = $sysMessages;
		$this->aclFormatter = $aclFormatter;
		$this->page         = $page;
	}

	protected function content(array $request = []): string
	{
		if (empty($this->parameters['mode']) || !in_array($this->parameters['mode'], self::ALLOWED_MODES)) {
			throw new BadRequestException($this->t('Invalid Request'));
		}

		Nav::setSelected('calendar');

		if (!$this->session->getLocalUserId()) {
			$this->sysMessages->addNotice($this->t('Permission denied.'));
			return Login::form();
		}

		$mode = $this->parameters['mode'];

		if (($mode === self::MODE_EDIT || $mode === self::MODE_COPY)) {
			if (empty($this->parameters['id'])) {
				throw new BadRequestException('Invalid Request');
			}
			$orig_event = EventModel::getByIdAndUid($this->session->getLocalUserId(), $this->parameters['id']);
			if (empty($orig_event)) {
				throw new BadRequestException('Invalid Request');
			}
		}

		if ($mode === self::MODE_NEW) {
			$this->session->set('return_path', $this->args->getCommand());
		}

		// get the translation strings for the calendar
		$i18n = EventModel::getStrings();

		$this->page->registerStylesheet('view/asset/fullcalendar/dist/fullcalendar.min.css');
		$this->page->registerStylesheet('view/asset/fullcalendar/dist/fullcalendar.print.min.css', 'print');
		$this->page->registerFooterScript('view/asset/moment/min/moment-with-locales.min.js');
		$this->page->registerFooterScript('view/asset/fullcalendar/dist/fullcalendar.min.js');

		$htpl = Renderer::getMarkupTemplate('calendar/calendar_head.tpl');

		$this->page['htmlhead'] .= Renderer::replaceMacros($htpl, [
			'$calendar_api' => $this->baseUrl . '/calendar/api/get',
			'$event_api'    => $this->baseUrl . '/calendar/event/show',
			'$modparams'    => 2,
			'$i18n'         => $i18n,
		]);

		$share_checked  = '';
		$share_disabled = '';

		$submit = $this->t("Create event");
		if (empty($orig_event)) {
			$orig_event = User::getById(
				$this->session->getLocalUserId(),
				['allow_cid', 'allow_gid', 'deny_cid', 'deny_gid'],
			);
		} elseif ($orig_event['allow_cid'] !== '<' . $this->session->getLocalUserId() . '>'
				   || $orig_event['allow_gid']
				   || $orig_event['deny_cid']
				   || $orig_event['deny_gid']) {
			$share_checked = ' checked="checked" ';
			$submit        = $this->t("Save changes");
		}

		// In case of an error the browser is redirected back here, with these parameters filled in with the previous values
		if (!empty($request['nofinish'])) {
			$orig_event['nofinish'] = $request['nofinish'];
		}
		if (!empty($request['summary'])) {
			$orig_event['summary'] = $request['summary'];
		}
		if (!empty($request['desc'])) {
			$orig_event['desc'] = $request['desc'];
		}
		if (!empty($request['location'])) {
			$orig_event['location'] = $request['location'];
		}
		if (!empty($request['start'])) {
			$orig_event['start'] = $request['start'];
		}
		if (!empty($request['finish'])) {
			$orig_event['finish'] = $request['finish'];
		}

		$n_checked = (!empty($orig_event['nofinish']) ? ' checked="checked" ' : '');

		$t_orig = $orig_event['summary']  ?? '';
		$d_orig = $orig_event['desc']     ?? '';
		$l_orig = $orig_event['location'] ?? '';
		$eid    = $orig_event['id']       ?? 0;
		$cid    = $orig_event['cid']      ?? 0;
		$uri    = $orig_event['uri']      ?? '';

		if ($cid || $mode === 'edit') {
			$share_disabled = 'disabled="disabled"';
		}

		$sdt = $orig_event['start']  ?? 'now';
		$fdt = $orig_event['finish'] ?? 'now';

		$syear  = DateTimeFormat::local($sdt, 'Y');
		$smonth = DateTimeFormat::local($sdt, 'm');
		$sday   = DateTimeFormat::local($sdt, 'd');

		$shour   = !empty($orig_event) ? DateTimeFormat::local($sdt, 'H') : '00';
		$sminute = !empty($orig_event) ? DateTimeFormat::local($sdt, 'i') : '00';

		$fyear  = DateTimeFormat::local($fdt, 'Y');
		$fmonth = DateTimeFormat::local($fdt, 'm');
		$fday   = DateTimeFormat::local($fdt, 'd');

		$fhour   = !empty($orig_event) ? DateTimeFormat::local($fdt, 'H') : '00';
		$fminute = !empty($orig_event) ? DateTimeFormat::local($fdt, 'i') : '00';

		if (!$cid && in_array($mode, [self::MODE_NEW, self::MODE_COPY])) {
			$acl = ACL::getFullSelectorHTML($this->page, $this->session->getLocalUserId(), false, ACL::getDefaultUserPermissions($orig_event));
		} else {
			$acl = '';
		}

		// If we copy an old event, we need to remove the ID and URI
		// from the original event.
		if ($mode === self::MODE_COPY) {
			$eid = 0;
			$uri = '';
		}

		$this->page['aside'] .= CalendarExport::getHTML($this->session->getLocalUserId());

		$tpl           = Renderer::getMarkupTemplate('calendar/event_form.tpl');
		$no_formatting = $this->t("Formatting is not supported for this field");

		if (str_contains((string) $_SERVER['REQUEST_URI'], "/calendar/event/edit")) {
			$title = $this->t('Edit event');
		} else {
			$title = $this->t('New event'); // Used when creating or duplicating an event
		}

		$ends_text = $this->t('Ends');

		return Renderer::replaceMacros($tpl, [
			'$post'      => 'calendar/api/create',
			'$eid'       => $eid,
			'$cid'       => $cid,
			'$uri'       => $uri,
			'$back_text' => $this->t('Back to the calendar'),

			'$title'  => $title,
			'$desc'   => $this->t('Starting date and Title are required.'),
			'$s_text' => $this->t('Starts') . ' <span class="required" title="' . $this->t('Required') . '">*</span>',
			'$s_dsel' => Temporal::getDateTimeField(
				new \DateTime(),
				\DateTime::createFromFormat('Y', intval($syear) + 5),
				\DateTime::createFromFormat('Y-m-d H:i', "$syear-$smonth-$sday $shour:$sminute"),
				$this->t('Starts'),
				'start_text',
				true,
				true,
				'',
				'',
				true,
				false,
			),

			'$section_time'       => $this->t('When does it happen?'),
			'$section_additional' => $this->t('Further details'),
			'$timezone_help'      => Temporal::timeZoneHelper(),
			'$n_text'             => $this->t("Don't specify ending"),
			'$n_checked'          => $n_checked,
			'$f_text'             => $ends_text,
			'$f_dsel'             => Temporal::getDateTimeField(
				new \DateTime(),
				\DateTime::createFromFormat('Y', intval($fyear) + 5),
				\DateTime::createFromFormat('Y-m-d H:i', "$fyear-$fmonth-$fday $fhour:$fminute"),
				$ends_text,
				'finish_text',
				true,
				true,
				'start_text',
				'',
				false,
				false,
			),

			'$no_formatting' => $no_formatting,
			'$t_text'        => $this->t('Title') . ' <span class="required" title="' . $this->t('Required') . '">*</span>',
			'$t_orig'        => $t_orig,
			'$l_text'        => $this->t('Location'),
			'$l_placeholder' => $this->t('Where does it happen?'),
			'$l_orig'        => $l_orig,
			'$d_text'        => $this->t('Description'),
			'$d_placeholder' => $this->t('What are the details?'),
			'$d_orig'        => $d_orig,
			'$summary'       => ['summary', $this->t('Event title'), $t_orig, $no_formatting, '*'],
			'$sh_text'       => $this->t('Share this event'),
			'$share'         => ['share', $this->t('Share this event'), $share_checked, '', $share_disabled],
			'$sh_checked'    => $share_checked,
			'$nofinish'      => ['nofinish', $this->t("Don't specify ending"), $n_checked],
			'$preview'       => $this->t('Preview'),
			'$acl'           => $acl,
			'$submit'        => $submit,
			'$basic'         => $this->t('Basic'),
			'$advanced'      => $this->t('Advanced'),
			'$permissions'   => $this->t('Permissions'),
		]);
	}
}
