<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

class Conversation
{
	/*
	 * These constants represent the parcel format used to transport a conversation independently of the message protocol.
	 * It currently is stored in the "protocol" field for legacy reasons.
	 */
	public const PARCEL_ACTIVITYPUB        = 0;
	public const PARCEL_DFRN               = 1; // @deprecated
	public const PARCEL_DIASPORA           = 2;
	public const PARCEL_SALMON             = 3; // @deprecated 2024.09 since version 2024.09
	public const PARCEL_FEED               = 4; // @deprecated
	public const PARCEL_SPLIT_CONVERSATION = 6; // @deprecated 2022.09 since version 2022.09
	public const PARCEL_LEGACY_DFRN        = 7; // @deprecated 2021.09 since version 2021.09
	public const PARCEL_DIASPORA_DFRN      = 8;
	public const PARCEL_LOCAL_DFRN         = 9;
	public const PARCEL_DIRECT             = 10;
	public const PARCEL_IMAP               = 11;
	public const PARCEL_RDF                = 12;
	public const PARCEL_RSS                = 13;
	public const PARCEL_ATOM               = 14;
	public const PARCEL_ATOM03             = 15;
	public const PARCEL_OPML               = 16;
	public const PARCEL_JETSTREAM          = 17; // @see https://github.com/bluesky-social/jetstream
	public const PARCEL_TWITTER            = 67;
	public const PARCEL_CONNECTOR          = 68;
	public const PARCEL_UNKNOWN            = 255;

	/**
	 * Unknown message direction
	 */
	public const UNKNOWN = 0;
	/**
	 * The message had been pushed to this system
	 */
	public const PUSH = 1;
	/**
	 * The message had been fetched by our system
	 */
	public const PULL = 2;
	/**
	 * The message had been pushed to this system via a relay server
	 */
	public const RELAY = 3;

}
