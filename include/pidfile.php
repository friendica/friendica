<?php
class pidfile {
	private $_file;
	private $_running;

	public function __construct($dir, $name) {
		$this->_file = "$dir/$name.pid";

		if (file_exists($this->_file)) {
			$pid = trim(@file_get_contents($this->_file));
			if (($pid != "") AND posix_kill($pid, 0)) {
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
			@unlink($this->_file);
		}
	}

	public function is_already_running() {
		return $this->_running;
	}

	public function running_time() {
		return(time() - @filectime($this->_file));
	}

	public function kill() {
		if (file_exists($this->_file))
			return(posix_kill(file_get_contents($this->_file), SIGTERM));
	}
}
?>
