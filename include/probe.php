<?php

use Friendica\Network\Probe;

/**
 *
 * PROBE_DIASPORA has a bias towards returning Diaspora information
 * while PROBE_NORMAL has a bias towards dfrn/zot - in the case where
 * an address (such as a Friendica address) supports more than one type
 * of network.
 *
 */
define('PROBE_NORMAL',   0);
define('PROBE_DIASPORA', 1);

/**
 * @brief Probes a network address to discover what kind of protocols we need to communicate with it.
 *
 * Warning: this function is a bit touchy and there are some subtle dependencies within the logic flow.
 * Edit with care.
 *
 * @deprecated Use Friendica\Network\Probe instead
 *
 * @see Friendica\Network\Probe::uri()
 *
 * @param string $url Any URI
 * @param int $mode One of the PROBE_* constants
 * @return array Same data array returned by Friendica\Network\Probe::uri()
 */
function probe_url($url, $mode = PROBE_NORMAL) {

	if ($mode == PROBE_DIASPORA) {
		$network = NETWORK_DIASPORA;
	} else {
		$network = '';
	}

	$data = Probe::uri($url, $network);

	return $data;
}
