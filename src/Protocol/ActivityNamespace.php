<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol;

/**
 * Activity namespaces constants
 */
final class ActivityNamespace
{
	/**
	 * Zot is a WebMTA which provides a decentralised identity and communications protocol using HTTPS/JSON.
	 *
	 * @var string
	 * @see https://zotlabs.org/page/zotlabs/specs+zot6+home
	 */
	public const ZOT = 'http://purl.org/zot';
	/**
	 * Friendica is using ActivityStreams in version 1.0 for its activities and object types.
	 * Additional types are used for non standard activities.
	 *
	 * @var string
	 * @see https://github.com/friendica/friendica/wiki/ActivityStreams
	 */
	public const DFRN = 'http://purl.org/macgirvin/dfrn/1.0';
	/**
	 * This namespace defines an extension for expressing threaded
	 * discussions within the Atom Syndication Format [RFC4287]
	 *
	 * @see https://tools.ietf.org/rfc/rfc4685.txt
	 * @var string
	 */
	public const THREAD = 'http://purl.org/syndication/thread/1.0';
	/**
	 * This namespace adds mechanisms to the Atom Syndication Format
	 * that publishers of Atom Feed and Entry documents can use to
	 * explicitly identify Atom entries that have been removed.
	 *
	 * @see https://tools.ietf.org/html/rfc6721
	 * @var string
	 */
	public const TOMB = 'http://purl.org/atompub/tombstones/1.0';
	/**
	 * This specification details a model for representing potential and completed activities
	 * using the JSON format.
	 *
	 * @see https://www.w3.org/ns/activitystreams
	 * @var string
	 */
	public const ACTIVITY2 = 'https://www.w3.org/ns/activitystreams#';
	/**
	 * Atom Activities 1.0
	 *
	 * This namespace presents an XML format that allows activities on social objects
	 * to be expressed within the Atom Syndication Format.
	 *
	 * @see http://activitystrea.ms/spec/1.0
	 * @var string
	 */
	public const ACTIVITY = 'http://activitystrea.ms/spec/1.0/';
	/**
	 * This namespace presents a base set of Object types and Verbs for use with Activity Streams.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html
	 * @var string
	 */
	public const ACTIVITY_SCHEMA = 'http://activitystrea.ms/schema/1.0/';
	/**
	 * Atom Media Extensions
	 *
	 * @var string
	 */
	public const MEDIA = 'http://purl.org/syndication/atommedia';
	/**
	 * The Salmon Protocol is an open, simple, standards-based solution that lets
	 * aggregators and sources unify the conversations.
	 *
	 * @see http://www.salmon-protocol.org/salmon-protocol-summary
	 * @var string
	 */
	public const SALMON_ME = 'http://salmon-protocol.org/ns/magic-env';
	/**
	 * OStatus is a minimal specification for distributed status updates or microblogging.
	 *
	 * @see https://ostatus.github.io/spec/OStatus%201.0%20Draft%202.html
	 * @var string
	 */
	public const OSTATUSSUB = 'http://ostatus.org/schema/1.0/subscribe';
	/**
	 * Webfinger avatar
	 *
	 * @see https://webfinger.net/rel/#avatar
	 * @var string
	 */
	public const WEBFINGERAVATAR = 'http://webfinger.net/rel/avatar';
	/**
	 * Webfinger profile
	 *
	 * @see https://webfinger.net/rel/#profile-page
	 * @var string
	 */
	public const WEBFINGERPROFILE = 'http://webfinger.net/rel/profile-page';
	/**
	 * HCard
	 *
	 * @see http://microformats.org/wiki/hcard
	 * @var string
	 */
	public const HCARD = 'http://microformats.org/profile/hcard';
	/**
	 * Base url of the Diaspora installation
	 *
	 * @var string
	 */
	public const DIASPORA_SEED = 'http://joindiaspora.com/seed_location';
	/**
	 * Diaspora Guid
	 *
	 * @var string
	 */
	public const DIASPORA_GUID = 'http://joindiaspora.com/guid';
	/**
	 * GeoRSS was designed as a lightweight, community driven way to extend existing feeds with geographic information.
	 *
	 * @see http://www.georss.org/
	 * @var string
	 */
	public const GEORSS = 'http://www.georss.org/georss';
	/**
	 * The Portable Contacts specification is designed to make it easier for developers
	 * to give their users a secure way to access the address books and friends lists
	 * they have built up all over the web.
	 *
	 * @see http://portablecontacts.net/draft-spec/
	 * @var string
	 */
	public const POCO = 'http://portablecontacts.net/spec/1.0';
	/**
	 * OpenWebAuth is used by Friendica and Hubzilla to authenticate at remote systems
	 *
	 * @var string
	 */
	public const OPENWEBAUTH = 'http://purl.org/openwebauth/v1';
	/**
	 * @var string
	 */
	public const FEED = 'http://schemas.google.com/g/2010#updates-from';
	/**
	 * OStatus is a minimal specification for distributed status updates or microblogging.
	 *
	 * @see https://ostatus.github.io/spec/OStatus%201.0%20Draft%202.html
	 * @var string
	 */
	public const OSTATUS = 'http://ostatus.org/schema/1.0';
	/**
	 * @var string
	 */
	public const STATUSNET = 'http://status.net/schema/api/1/';
	/**
	 * This namespace describes the Atom Activity Streams in RDF Vocabulary (AAIR),
	 * defined as a dictionary of named properties and classes using W3C's RDF technology,
	 * and specifically a mapping of the Atom Activity Streams work to RDF.
	 *
	 * @see http://xmlns.notu.be/aair/#RFC4287
	 * @var string
	 */
	public const ATOM1 = 'http://www.w3.org/2005/Atom';

	/**
	 * This namespace is used for the (deprecated) Atom 0.3 specification
	 * @var string
	 */
	public const ATOM03 = 'http://purl.org/atom/ns#';

	/**
	 * @var string
	 */
	public const MASTODON = 'http://mastodon.social/schema/1.0';

	/**
	 * @var string
	 */
	public const LITEPUB = 'http://litepub.social';

	/**
	 * @var string
	 */
	public const PEERTUBE = 'https://joinpeertube.org';

	/**
	 * FEP-3b86: Activity Intents base namespace.
	 * Activity Intents enable remote servers to link users back to their home server
	 * to perform activities (Follow, Create, etc.) on remote content.
	 *
	 * @see https://w3id.org/fep/3b86
	 * @var string
	 */
	public const FEP3B86 = 'https://w3id.org/fep/3b86';

	/**
	 * FEP-3b86: Follow Intent – endpoint to follow a remote actor
	 *
	 * @see https://w3id.org/fep/3b86
	 * @var string
	 */
	public const FEP3B86_FOLLOW = 'https://w3id.org/fep/3b86/Follow';

	/**
	 * FEP-3b86: Create Intent – endpoint to create a new post (share / compose)
	 *
	 * @see https://w3id.org/fep/3b86
	 * @var string
	 */
	public const FEP3B86_CREATE = 'https://w3id.org/fep/3b86/Create';
}
