<?php
require_once('include/Probe.php');

/**
 *
 * Probe a network address to discover what kind of protocols we need to communicate with it.
 *
 * Warning: this function is a bit touchy and there are some subtle dependencies within the logic flow.
 * Edit with care.
 *
 */

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

function probe_url($url, $mode = PROBE_NORMAL, $level = 1)
{
    if ($mode == PROBE_DIASPORA) {
        $network = NETWORK_DIASPORA;
    } else {
        $network = "";
    }

    $data = Probe::uri($url, $network);

    return $data;
}
