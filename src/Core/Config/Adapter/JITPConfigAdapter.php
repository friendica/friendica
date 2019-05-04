<?php
namespace Friendica\Core\Config\Adapter;

/**
 * JustInTime User Configuration Adapter
 *
 * Default PConfig Adapter. Provides the best performance for pages loading few configuration variables.
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class JITPConfigAdapter extends AbstractDbaConfigAdapter implements IPConfigAdapter
{
	private $in_db;

	/**
	 * {@inheritdoc}
	 */
	public function load($uid, $cat)
	{
		$return = [];

		if (!$this->isConnected()) {
			return $return;
		}

		$database = $this->database;

		$pconfigs = $database->select('pconfig', ['v', 'k'], ['cat' => $cat, 'uid' => $uid]);
		if ($database->isResult($pconfigs)) {
			while ($pconfig = $database->fetch($pconfigs)) {
				$key = $pconfig['k'];
				$value = $this->toConfigValue($pconfig['v']);

				// The value was in the db, so don't check it again (unless you have to)
				$this->in_db[$uid][$cat][$key] = true;

				if (isset($value)) {
					$return[$key] = $value;
				}
			}
		} else if ($cat != 'config') {
			// Negative caching
			$return = null;
		}
		$database->close($pconfigs);

		return [$cat => $return];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param bool $mark if true, mark the selection of the current cat/key pair
	 */
	public function get($uid, $cat, $key, $mark = true)
	{
		if (!$this->isConnected()) {
			return null;
		}

		// The value was in the db, so don't check it again (unless you have to)
		if ($mark) {
			$this->in_db[$uid][$cat][$key] = true;
		}

		$database = $this->database;

		$pconfig = $database->selectFirst('pconfig', ['v'], ['uid' => $uid, 'cat' => $cat, 'k' => $key]);
		if ($database->isResult($pconfig)) {
			$value = $this->toConfigValue($pconfig['v']);

			if (isset($value)) {
				return $value;
			}
		}

		$this->in_db[$uid][$cat][$key] = false;
		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function set($uid, $cat, $key, $value)
	{
		if (!$this->isConnected()) {
			return false;
		}

		// We store our setting values in a string variable.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$compare_value = (!is_array($value) ? (string)$value : $value);
		$stored_value = $this->get($uid, $cat, $key, false);

		if (!isset($this->in_db[$uid])) {
			$this->in_db[$uid] = [];
		}
		if (!isset($this->in_db[$uid][$cat])) {
			$this->in_db[$uid][$cat] = [];
		}
		if (!isset($this->in_db[$uid][$cat][$key])) {
			$this->in_db[$uid][$cat][$key] = false;
		}

		if (isset($stored_value) && ($stored_value === $compare_value) && $this->in_db[$uid][$cat][$key]) {
			return true;
		}

		// manage array value
		$dbvalue = (is_array($value) ? serialize($value) : $value);

		$result = $this->database->update('pconfig', ['v' => $dbvalue], ['uid' => $uid, 'cat' => $cat, 'k' => $key], true);

		$this->in_db[$uid][$cat][$key] = $result;

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete($uid, $cat, $key)
	{
		if (!$this->isConnected()) {
			return false;
		}

		if (isset($this->in_db[$uid][$cat][$key])) {
			unset($this->in_db[$uid][$cat][$key]);
		}

		return $this->database->delete('pconfig', ['uid' => $uid, 'cat' => $cat, 'k' => $key]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function isLoaded($uid, $cat, $key)
	{
		if (!$this->isConnected()) {
			return false;
		}

		return (isset($this->in_db[$uid][$cat][$key])) && $this->in_db[$uid][$cat][$key];
	}
}
