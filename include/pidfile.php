<?php
class pidfile {
	private $_file;
	private $_running;

	public function __construct($dir, $name) {
		$this->_file = "$dir/$name.pid";

		if (file_exists($this->_file)) {
			$pid = trim(file_get_contents($this->_file));
			if (posix_kill($pid, 0)) {
				$this->_running = true;
			}
		}

		if (! $this->_running) {
			$pid = getmypid();
			file_put_contents($this->_file, $pid);
		}
	}

	public function __destruct() {
		if ((! $this->_running) && file_exists($this->_file)) {
			unlink($this->_file);
		}
	}

	public function is_already_running() {
		return $this->_running;
	}
}
?>
