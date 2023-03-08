<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

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
	const PARCEL_SALMON             = 3;
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
	const PARCEL_TWITTER            = 67;
	const PARCEL_UNKNOWN            = 255;

	/**
	 * Unknown message direction
	 */
	const UNKNOWN = 0;
	/**
	 * The message had been pushed to this sytem
	 */
	const PUSH    = 1;
	/**
	 * The message had been fetched by our system
	 */
	const PULL    = 2;
	/**
	 * The message had been pushed to this system via a relay server
	 */
	const RELAY   = 3;

}
