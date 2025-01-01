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
	const PARCEL_ACTIVITYPUB        = 0;
	const PARCEL_DFRN               = 1; // Deprecated
	const PARCEL_DIASPORA           = 2;
	const PARCEL_SALMON             = 3; // @deprecated since version 2024.09
	const PARCEL_FEED               = 4; // Deprecated
	const PARCEL_SPLIT_CONVERSATION = 6; // @deprecated since version 2022.09
	const PARCEL_LEGACY_DFRN        = 7; // @deprecated since version 2021.09
	const PARCEL_DIASPORA_DFRN      = 8;
	const PARCEL_LOCAL_DFRN         = 9;
	const PARCEL_DIRECT             = 10;
	const PARCEL_IMAP               = 11;
	const PARCEL_RDF                = 12;
	const PARCEL_RSS                = 13;
	const PARCEL_ATOM               = 14;
	const PARCEL_ATOM03             = 15;
	const PARCEL_OPML               = 16;
	const PARCEL_JETSTREAM          = 17; // @see https://github.com/bluesky-social/jetstream
	const PARCEL_TWITTER            = 67;
	const PARCEL_CONNECTOR          = 68;
	const PARCEL_UNKNOWN            = 255;

	/**
	 * Unknown message direction
	 */
	const UNKNOWN = 0;
	/**
	 * The message had been pushed to this system
	 */
	const PUSH = 1;
	/**
	 * The message had been fetched by our system
	 */
	const PULL = 2;
	/**
	 * The message had been pushed to this system via a relay server
	 */
	const RELAY = 3;

}
