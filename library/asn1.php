<?php

// ASN.1 parsing library
// Attribution: http://www.krisbailey.com
// license: unknown
// modified: Mike Macgrivin mike@macgirvin.com 6-oct-2010 to support Salmon auto-discovery
// from openssl public keys


class ASN_BASE {
	public $asnData = null;
	private $cursor = 0;
	private $parent = null;
	
	public static $ASN_MARKERS = array(
		'ASN_UNIVERSAL'		=> 0x00,
		'ASN_APPLICATION'	=> 0x40,
		'ASN_CONTEXT'		=> 0x80,
		'ASN_PRIVATE'		=> 0xC0,

		'ASN_PRIMITIVE'		=> 0x00,
		'ASN_CONSTRUCTOR'	=> 0x20,

		'ASN_LONG_LEN'		=> 0x80,
		'ASN_EXTENSION_ID'	=> 0x1F,
		'ASN_BIT'		=> 0x80,
	);
	
	public static $ASN_TYPES = array(
		1	=> 'ASN_BOOLEAN',
		2	=> 'ASN_INTEGER',
		3	=> 'ASN_BIT_STR',
		4	=> 'ASN_OCTET_STR',
		5	=> 'ASN_NULL',
		6	=> 'ASN_OBJECT_ID',
		9	=> 'ASN_REAL',
		10	=> 'ASN_ENUMERATED',
		13	=> 'ASN_RELATIVE_OID',
		48	=> 'ASN_SEQUENCE',
		49	=> 'ASN_SET',
		19	=> 'ASN_PRINT_STR',
		22	=> 'ASN_IA5_STR',
		23	=> 'ASN_UTC_TIME',
		24	=> 'ASN_GENERAL_TIME',
	);

	function __construct($v = false)
	{
		if (false !== $v) {
			$this->asnData = $v;
			if (is_array($this->asnData)) {
				foreach ($this->asnData as $key => $value) {
					if (is_object($value)) {
						$this->asnData[$key]->setParent($this);
					}
				}
			} else {
				if (is_object($this->asnData)) {
					$this->asnData->setParent($this);
				}
			}
		}
	}
	
	public function setParent($parent)
	{
		if (false !== $parent) {
			$this->parent = $parent;
		}
	}
	
	/**
	 * This function will take the markers and types arrays and
	 * dynamically generate classes that extend this class for each one,
	 * and also define constants for them.
	 */
	public static function generateSubclasses()
	{
		define('ASN_BASE', 0);
		foreach (self::$ASN_MARKERS as $name => $bit)
			self::makeSubclass($name, $bit);
		foreach (self::$ASN_TYPES as $bit => $name)
			self::makeSubclass($name, $bit);
	}
	
	/**
	 * Helper function for generateSubclasses()
	 */
	public static function makeSubclass($name, $bit)
	{
		define($name, $bit);
		eval("class ".$name." extends ASN_BASE {}");
	}
	
	/**
	 * This function reset's the internal cursor used for value iteration.
	 */
	public function reset()
	{
		$this->cursor = 0;
	}
	
	/**
	 * This function catches calls to get the value for the type, typeName, value, values, and data
	 * from the object.  For type calls we just return the class name or the value of the constant that
	 * is named the same as the class.
	 */
	public function __get($name)
	{
		if ('type' == $name) {
			// int flag of the data type
			return constant(get_class($this));
		} elseif ('typeName' == $name) {
			// name of the data type
			return get_class($this);
		} elseif ('value' == $name) {
			// will always return one value and can be iterated over with:
			// while ($v = $obj->value) { ...
			// because $this->asnData["invalid key"] will return false
			return is_array($this->asnData) ? $this->asnData[$this->cursor++] : $this->asnData;
		} elseif ('values' == $name) {
			// will always return an array
			return is_array($this->asnData) ? $this->asnData : array($this->asnData);
		} elseif ('data' == $name) {
			// will always return the raw data
			return $this->asnData;
		}
	}

	/**
	 * Parse an ASN.1 binary string.
	 * 
	 * This function takes a binary ASN.1 string and parses it into it's respective
	 * pieces and returns it.  It can optionally stop at any depth.
	 *
	 * @param	string	$string		The binary ASN.1 String
	 * @param	int	$level		The current parsing depth level
	 * @param	int	$maxLevel	The max parsing depth level
	 * @return	ASN_BASE	The array representation of the ASN.1 data contained in $string
	 */
	public static function parseASNString($string=false, $level=1, $maxLevels=false){
		if (!class_exists('ASN_UNIVERSAL'))
			self::generateSubclasses();
		if ($level>$maxLevels && $maxLevels)
			return array(new ASN_BASE($string));
		$parsed = array();
		$endLength = strlen($string);
		$bigLength = $length = $type = $dtype = $p = 0;
		while ($p<$endLength){
			$type = ord($string[$p++]);
			$dtype = ($type & 192) >> 6;
			if ($type==0){ // if we are type 0, just continue
			} else {
				$length = ord($string[$p++]);
				if (($length & ASN_LONG_LEN)==ASN_LONG_LEN){
					$tempLength = 0;
					for ($x=0; $x<($length & (ASN_LONG_LEN-1)); $x++){
						$tempLength = ord($string[$p++]) + ($tempLength * 256);
					}
					$length = $tempLength;
				}
				$data = substr($string, $p, $length);
				$parsed[] = self::parseASNData($type, $data, $level, $maxLevels);
				$p = $p + $length;
			}
		}
		return $parsed;
	}

	/**
	 * Parse an ASN.1 field value.
	 * 
	 * This function takes a binary ASN.1 value and parses it according to it's specified type
	 *
	 * @param	int	$type		The type of data being provided
	 * @param	string	$data		The raw binary data string
	 * @param	int	$level		The current parsing depth
	 * @param	int	$maxLevels	The max parsing depth
	 * @return	mixed	The data that was parsed from the raw binary data string
	 */
	public static function parseASNData($type, $data, $level, $maxLevels){
		$type = $type%50; // strip out context
		switch ($type){
			default:
				return new ASN_BASE($data);
			case ASN_BOOLEAN:
				return new ASN_BOOLEAN((bool)$data);
			case ASN_INTEGER:
				return new ASN_INTEGER(strtr(base64_encode($data),'+/','-_'));
			case ASN_BIT_STR:
				return new ASN_BIT_STR(self::parseASNString($data, $level+1, $maxLevels));
			case ASN_OCTET_STR:
				return new ASN_OCTET_STR($data);
			case ASN_NULL:
				return new ASN_NULL(null);
			case ASN_REAL:
				return new ASN_REAL($data);
			case ASN_ENUMERATED:
				return new ASN_ENUMERATED(self::parseASNString($data, $level+1, $maxLevels));
			case ASN_RELATIVE_OID: // I don't really know how this works and don't have an example :-)
						// so, lets just return it ...
				return new ASN_RELATIVE_OID($data);
			case ASN_SEQUENCE:
				return new ASN_SEQUENCE(self::parseASNString($data, $level+1, $maxLevels));
			case ASN_SET:
				return new ASN_SET(self::parseASNString($data, $level+1, $maxLevels));
			case ASN_PRINT_STR:
				return new ASN_PRINT_STR($data);
			case ASN_IA5_STR:
				return new ASN_IA5_STR($data);
			case ASN_UTC_TIME:
				return new ASN_UTC_TIME($data);
			case ASN_GENERAL_TIME:
				return new ASN_GENERAL_TIME($data);
			case ASN_OBJECT_ID:
				return new ASN_OBJECT_ID(self::parseOID($data));
		}
	}

	/**
	 * Parse an ASN.1 OID value.
	 * 
	 * This takes the raw binary string that represents an OID value and parses it into its
	 * dot notation form.  example - 1.2.840.113549.1.1.5
	 * look up OID's here: http://www.oid-info.com/
	 * (the multi-byte OID section can be done in a more efficient way, I will fix it later)
	 *
	 * @param	string	$data		The raw binary data string
	 * @return	string	The OID contained in $data
	 */
	public static function parseOID($string){
		$ret = floor(ord($string[0])/40).".";
		$ret .= (ord($string[0]) % 40);
		$build = array();
		$cs = 0;	
		
		for ($i=1; $i<strlen($string); $i++){
			$v = ord($string[$i]);
			if ($v>127){
				$build[] = ord($string[$i])-ASN_BIT;
			} elseif ($build){
				// do the build here for multibyte values
				$build[] = ord($string[$i])-ASN_BIT;
				// you know, it seems there should be a better way to do this...
				$build = array_reverse($build);
				$num = 0;
				for ($x=0; $x<count($build); $x++){
					$mult = $x==0?1:pow(256, $x);
					if ($x+1==count($build)){
						$value = ((($build[$x] & (ASN_BIT-1)) >> $x)) * $mult;
					} else {
						$value = ((($build[$x] & (ASN_BIT-1)) >> $x) ^ ($build[$x+1] << (7 - $x) & 255)) * $mult;
					}
					$num += $value;
				}
				$ret .= ".".$num;
				$build = array(); // start over
			} else {
				$ret .= ".".$v;
				$build = array();
			}
		}
		return $ret;
	}
	
	public static function printASN($x, $indent=''){
		if (is_object($x)) {
			echo $indent.$x->typeName."\n";
			if (ASN_NULL == $x->type) return;
			if (is_array($x->data)) {
				while ($d = $x->value) {
					echo self::printASN($d, $indent.'.  ');
				}
				$x->reset();
			} else {
				echo self::printASN($x->data, $indent.'.  ');
			}
		} elseif (is_array($x)) {
			foreach ($x as $d) {
				echo self::printASN($d, $indent);
			}
		} else {
			if (preg_match('/[^[:print:]]/', $x))	// if we have non-printable characters that would
				$x = base64_encode($x);		// mess up the console, then print the base64 of them...
			echo $indent.$x."\n";
		}
	}

	
}

