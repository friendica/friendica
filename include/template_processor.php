<?php
/*
 * This is the old template engine, now deprecated.
 * Friendica's default template engine is Smarty3 (see include/friendica_smarty.php)
 * 
 */
require_once 'object/TemplateEngine.php';

define("KEY_NOT_EXISTS", '^R_key_not_Exists^');

class Template implements ITemplateEngine {
	static $name ="internal";
	
	var $r;
	var $search;
	var $replace;
	var $stack = array();
	var $nodes = array();
	var $done = false;
	var $d = false;
	var $lang = null;
	var $debug = false;

	private function _preg_error() {

		switch (preg_last_error()) {
			case PREG_INTERNAL_ERROR: echo('PREG_INTERNAL_ERROR');
				break;
			case PREG_BACKTRACK_LIMIT_ERROR: echo('PREG_BACKTRACK_LIMIT_ERROR');
				break;
			case PREG_RECURSION_LIMIT_ERROR: echo('PREG_RECURSION_LIMIT_ERROR');
				break;
			case PREG_BAD_UTF8_ERROR: echo('PREG_BAD_UTF8_ERROR');
				break;
//			This is only valid for php > 5.3, not certain how to code around it for unit tests
//			case PREG_BAD_UTF8_OFFSET_ERROR: echo('PREG_BAD_UTF8_OFFSET_ERROR'); break;
			default:
				//die("Unknown preg error.");
				return;
		}
		echo "<hr><pre>";
		debug_print_backtrace();
		die();
	}

	private function _push_stack() {
		$this->stack[] = array($this->r, $this->nodes);
	}

	private function _pop_stack() {
		list($this->r, $this->nodes) = array_pop($this->stack);
	}

	private function _get_var($name, $retNoKey = false) {
		$keys = array_map('trim', explode(".", $name));
		if ($retNoKey && !array_key_exists($keys[0], $this->r))
			return KEY_NOT_EXISTS;
		$val = $this->r;
		foreach ($keys as $k) {
			$val = (isset($val[$k]) ? $val[$k] : null);
		}
		return $val;
	}

	/**
	 * IF node
	 * 
	 * {{ if <$var> }}...[{{ else }} ...] {{ endif }}
	 * {{ if <$var>==<val|$var> }}...[{{ else }} ...]{{ endif }}
	 * {{ if <$var>!=<val|$var> }}...[{{ else }} ...]{{ endif }}
	 */
	private function _replcb_if($args) {
		if (strpos($args[2], "==") > 0) {
			list($a, $b) = array_map("trim", explode("==", $args[2]));
			$a = $this->_get_var($a);
			if ($b[0] == "$")
				$b = $this->_get_var($b);
			$val = ($a == $b);
		} else if (strpos($args[2], "!=") > 0) {
			list($a, $b) = array_map("trim", explode("!=", $args[2]));
			$a = $this->_get_var($a);
			if ($b[0] == "$")
				$b = $this->_get_var($b);
			$val = ($a != $b);
		} else {
			$val = $this->_get_var($args[2]);
		}
		$x = preg_split("|{{ *else *}}|", $args[3]);
		return ( $val ? $x[0] : (isset($x[1]) ? $x[1] : ""));
	}

	/**
	 * FOR node
	 * 
	 * {{ for <$var> as $name }}...{{ endfor }}
	 * {{ for <$var> as $key=>$name }}...{{ endfor }}
	 */
	private function _replcb_for($args) {
		$m = array_map('trim', explode(" as ", $args[2]));
		$x = explode("=>", $m[1]);
		if (count($x) == 1) {
			$varname = $x[0];
			$keyname = "";
		} else {
			list($keyname, $varname) = $x;
		}
		if ($m[0] == "" || $varname == "" || is_null($varname))
			die("template error: 'for " . $m[0] . " as " . $varname . "'");
		//$vals = $this->r[$m[0]];
		$vals = $this->_get_var($m[0]);
		$ret = "";
		if (!is_array($vals))
			return $ret;
		foreach ($vals as $k => $v) {
			$this->_push_stack();
			$r = $this->r;
			$r[$varname] = $v;
			if ($keyname != '')
				$r[$keyname] = (($k === 0) ? '0' : $k);
			$ret .= $this->replace($args[3], $r);
			$this->_pop_stack();
		}
		return $ret;
	}

	/**
	 * INC node
	 * 
	 * {{ inc <templatefile> [with $var1=$var2] }}{{ endinc }}
	 */
	private function _replcb_inc($args) {
		if (strpos($args[2], "with")) {
			list($tplfile, $newctx) = array_map('trim', explode("with", $args[2]));
		} else {
			$tplfile = trim($args[2]);
			$newctx = null;
		}

		if ($tplfile[0] == "$")
			$tplfile = $this->_get_var($tplfile);

		$this->_push_stack();
		$r = $this->r;
		if (!is_null($newctx)) {
			list($a, $b) = array_map('trim', explode("=", $newctx));
			$r[$a] = $this->_get_var($b);
		}
		$this->nodes = Array();
		$tpl = get_markup_template($tplfile);
		$ret = $this->replace($tpl, $r);
		$this->_pop_stack();
		return $ret;
	}

	/**
	 * DEBUG node
	 * 
	 * {{ debug $var [$var [$var [...]]] }}{{ enddebug }}
	 * 
	 * replace node with <pre>var_dump($var, $var, ...);</pre>
	 */
	private function _replcb_debug($args) {
		$vars = array_map('trim', explode(" ", $args[2]));
		$vars[] = $args[1];

		$ret = "<pre>";
		foreach ($vars as $var) {
			$ret .= htmlspecialchars(var_export($this->_get_var($var), true));
			$ret .= "\n";
		}
		$ret .= "</pre>";
		return $ret;
	}

	private function _replcb_node($m) {
		$node = $this->nodes[$m[1]];
		if (method_exists($this, "_replcb_" . $node[1])) {
			$s = call_user_func(array($this, "_replcb_" . $node[1]), $node);
		} else {
			$s = "";
		}
		$s = preg_replace_callback('/\|\|([0-9]+)\|\|/', array($this, "_replcb_node"), $s);
		return $s;
	}

	private function _replcb($m) {
		//var_dump(array_map('htmlspecialchars', $m));
		$this->done = false;
		$this->nodes[] = (array) $m;
		return "||" . (count($this->nodes) - 1) . "||";
	}

	private function _build_nodes($s) {
		$this->done = false;
		while (!$this->done) {
			$this->done = true;
			$s = preg_replace_callback('|{{ *([a-z]*) *([^}]*)}}([^{]*({{ *else *}}[^{]*)?){{ *end\1 *}}|', array($this, "_replcb"), $s);
			if ($s == Null)
				$this->_preg_error();
		}
		//({{ *else *}}[^{]*)?
		krsort($this->nodes);
		return $s;
	}

	private function var_replace($s) {
		$m = array();
		/** regexp:
		 * \$ 						literal $
		 * (\[)?					optional open square bracket
		 * ([a-zA-Z0-9-_]+\.?)+		var name, followed by optional
		 * 							dot, repeated at least 1 time
		 * (|[a-zA-Z0-9-_:]+)*		pipe followed by filter name and args, zero or many
		 * (?(1)\])					if there was opened square bracket
		 * 							(subgrup 1), match close bracket
		 */
		if (preg_match_all('/\$(\[)?([a-zA-Z0-9-_]+\.?)+(\|[a-zA-Z0-9-_:]+)*(?(1)\])/', $s, $m)) {
			foreach ($m[0] as $var) {

				$exp = str_replace(array("[", "]"), array("", ""), $var);
				$exptks = explode("|", $exp);

				$varn = $exptks[0];
				unset($exptks[0]);
				$val = $this->_get_var($varn, true);
				if ($val != KEY_NOT_EXISTS) {
					/* run filters */
					/*
					 * Filter are in form of:
					 * filtername:arg:arg:arg
					 * 
					 * "filtername" is function name
					 * "arg"s are optional, var value is appended to the end
					 *			if one "arg"==='x' , is replaced with var value
					 * 
					 * examples:
					 * $item.body|htmlspecialchars				// escape html chars
					 * $item.body|htmlspecialchars|strtoupper   // escape html and uppercase result
					 * $item.created|date:%Y %M %j				// format date (created is a timestamp)
					 * $item.body|str_replace:cat:dog			// replace all "cat" with "dog"
					 * $item.body|str_replace:cat:dog:x:1		// replace one "cat" with "dog"
					 
					 */
					foreach ($exptks as $filterstr) {
						$filter = explode(":", $filterstr);
						$filtername = $filter[0];
						unset($filter[0]);
						$valkey = array_search("x", $filter);
						if ($valkey === false) {
							$filter[] = $val;
						} else {
							$filter[$valkey] = $val;
						}
						if (function_exists($filtername)) {
							$val = call_user_func_array($filtername, $filter);
						}
					}
					$s = str_replace($var, $val, $s);
				}
			}
		}

		return $s;
	}

	// TemplateEngine interface
	public function replace_macros($s, $r) {
		$this->r = $r;

		// remove comments block
		$s = preg_replace('/{#(.*?\s*?)*?#}/', "", $s);

		$s = $this->_build_nodes($s);

		$s = preg_replace_callback('/\|\|([0-9]+)\|\|/', array($this, "_replcb_node"), $s);
		if ($s == Null)
			$this->_preg_error();

		// replace strings recursively (limit to 10 loops)
		$os = "";
		$count = 0;
		while ($os != $s && $count < 10) {
			$os = $s;
			$count++;
			$s = $this->var_replace($s);
		}
		return template_unescape($s);
	}
	
	public function get_template_file($file, $root='') {
		$a = get_app();
		$template_file = get_template_file($a, $file, $root);
		$content = file_get_contents($template_file);
		return $content;		
	}
	
}


function template_escape($s) {

	return str_replace(array('$', '{{'), array('!_Doll^Ars1Az_!', '!_DoubLe^BraceS4Rw_!'), $s);
}

function template_unescape($s) {

	return str_replace(array('!_Doll^Ars1Az_!', '!_DoubLe^BraceS4Rw_!'), array('$', '{{'), $s);
}

