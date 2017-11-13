<?php
/**
 * @file include/socgraph.php
 *
 * @todo Move GNU Social URL schemata (http://server.tld/user/number) to http://server.tld/username
 * @todo Fetch profile data from profile page for Redmatrix users
 * @todo Detect if it is a forum
 */

use Friendica\App;
use Friendica\Core\System;
use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Network\Probe;

require_once 'include/datetime.php';
require_once 'include/network.php';
require_once 'include/html2bbcode.php';
require_once 'include/Contact.php';
require_once 'include/Photo.php';


