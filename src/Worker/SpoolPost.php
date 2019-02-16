<?php
/**
 * @file src/Worker/SpoolPost.php
 * @brief Posts items that wer spooled because they couldn't be posted.
 */
namespace Friendica\Worker;

use Friendica\Model\Item;

class SpoolPost extends AbstractWorker
{
	/**
	 * {@inheritdoc}
	 * 
	 * @throws \Exception
	 */
	public function execute(array $parameters = []) {
		$path = get_spoolpath();

		if (($path != '') && is_writable($path)){
			if ($dh = opendir($path)) {
				while (($file = readdir($dh)) !== false) {

					// It is not named like a spool file, so we don't care.
					if (substr($file, 0, 5) != "item-") {
						continue;
					}

					$fullfile = $path."/".$file;

					// We don't care about directories either
					if (filetype($fullfile) != "file") {
						continue;
					}

					// We can't read or write the file? So we don't care about it.
					if (!is_writable($fullfile) || !is_readable($fullfile)) {
						continue;
					}

					$arr = json_decode(file_get_contents($fullfile), true);

					// If it isn't an array then it is no spool file
					if (!is_array($arr)) {
						continue;
					}

					// Skip if it doesn't seem to be an item array
					if (!isset($arr['uid']) && !isset($arr['uri']) && !isset($arr['network'])) {
						continue;
					}

					$result = Item::insert($arr);

					$this->logger->info("Spool file ".$file." stored: ".$result);
					unlink($fullfile);
				}
				closedir($dh);
			}
		}
	}
}
