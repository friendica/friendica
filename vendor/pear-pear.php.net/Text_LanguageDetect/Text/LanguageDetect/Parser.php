<?php
/**
 * Part of Text_LanguageDetect
 *
 * PHP version 5
 *
 * @category  Text
 * @package   Text_LanguageDetect
 * @author    Nicholas Pisarro <infinityminusnine+pear@gmail.com>
 * @copyright 2006 Nicholas Pisarro
 * @license   BSD http://www.opensource.org/licenses/bsd-license.php
 * @link      http://pear.php.net/package/Text_LanguageDetect/
 */

/**
 * This class represents a text sample to be parsed.
 *
 * This separates the analysis of a text sample from the primary LanguageDetect
 * class. After a new profile has been built, the data can be retrieved using
 * the accessor functions.
 *
 * This class is intended to be used by the Text_LanguageDetect class, not
 * end-users.
 *
 * @category  Text
 * @package   Text_LanguageDetect
 * @author    Nicholas Pisarro <infinityminusnine+pear@gmail.com>
 * @copyright 2006 Nicholas Pisarro
 * @license   BSD http://www.opensource.org/licenses/bsd-license.php
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/Text_LanguageDetect/
 */
class Text_LanguageDetect_Parser extends Text_LanguageDetect
{
    /**
     * The piece of text being parsed
     *
     * @var string
     */
    protected $_string;

    /**
     * Stores the trigram frequencies of the sample
     *
     * @var string
     */
    protected $_trigrams = array();

    /**
     * Stores the trigram ranks of the sample
     *
     * @var array
     */
    protected $_trigram_ranks = array();

    /**
     * Stores the unicode blocks of the sample
     *
     * @var array
     */
    protected $_unicode_blocks = array();

    /**
     * Whether the parser should compile the unicode ranges
     *
     * @var bool
     */
    protected $_compile_unicode = false;

    /**
     * Whether the parser should compile trigrams
     *
     * @var bool
     */
    protected $_compile_trigram = false;

    /**
     * Whether the trigram parser should pad the beginning of the string
     *
     * @var bool
     */
    protected $_trigram_pad_start = false;

    /**
     * Whether the unicode parser should skip non-alphabetical ascii chars
     *
     * @var bool
     */
    protected $_unicode_skip_symbols = true;

    /**
     * Constructor
     *
     * @param string $string string to be parsed
     */
    public function __construct($string)
    {
        $this->_string = $string;
    }

    /**
     * PHP 4 constructor for backwards compatibility.
     *
     * @param string $string string to be parsed
     *
     * @return void
     */
    public function Text_LanguageDetect_Parser($string)
    {
        self::__construct($string);
    }

    /**
     * Returns true if a string is suitable for parsing
     *
     * @param string $str input string to test
     *
     * @return bool true if acceptable, false if not
     */
    public static function validateString($str)
    {
        if (!empty($str) && strlen($str) > 3 && preg_match('/\S/', $str)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Turn on/off trigram counting
     *
     * @param bool $bool true for on, false for off
     *
     * @return void
     */
    public function prepareTrigram($bool = true)
    {
        $this->_compile_trigram = $bool;
    }

    /**
     * Turn on/off unicode block counting
     *
     * @param bool $bool true for on, false for off
     *
     * @return void
     */
    public function prepareUnicode($bool = true)
    {
        $this->_compile_unicode = $bool;
    }

    /**
     * Turn on/off padding the beginning of the sample string
     *
     * @param bool $bool true for on, false for off
     *
     * @return void
     */
    public function setPadStart($bool = true)
    {
        $this->_trigram_pad_start = $bool;
    }

    /**
     * Should the unicode block counter skip non-alphabetical ascii chars?
     *
     * @param bool $bool true for on, false for off
     *
     * @return void
     */
    public function setUnicodeSkipSymbols($bool = true)
    {
        $this->_unicode_skip_symbols = $bool;
    }

    /**
     * Returns the trigram ranks for the text sample
     *
     * @return array Trigram ranks in the text sample
     */
    public function getTrigramRanks()
    {
        return $this->_trigram_ranks;
    }

    /**
     * Return the trigram freqency table
     *
     * Only used in testing to make sure the parser is working
     *
     * @return array Trigram freqencies in the text sample
     */
    public function getTrigramFreqs()
    {
        return $this->_trigram;
    }

    /**
     * Returns the array of unicode blocks
     *
     * @return array Unicode blocks in the text sample
     */
    public function getUnicodeBlocks()
    {
        return $this->_unicode_blocks;
    }

    /**
     * Executes the parsing operation
     *
     * Be sure to call the set*() functions to set options and the
     * prepare*() functions first to tell it what kind of data to compute
     *
     * Afterwards the get*() functions can be used to access the compiled
     * information.
     *
     * @return void
     */
    public function analyze()
    {
        $len = strlen($this->_string);
        $byte_counter = 0;


        // unicode startup
        if ($this->_compile_unicode) {
            $blocks = $this->_read_unicode_block_db();
            $block_count = count($blocks);

            $skipped_count = 0;
            $unicode_chars = array();
        }

        // trigram startup
        if ($this->_compile_trigram) {
            // initialize them as blank so the parser will skip the first two
            // (since it skips trigrams with more than  2 contiguous spaces)
            $a = ' ';
            $b = ' ';

            // kludge
            // if it finds a valid trigram to start and the start pad option is
            // off, then set a variable that will be used to reduce this
            // trigram after parsing has finished
            if (!$this->_trigram_pad_start) {
                $a = $this->_next_char($this->_string, $byte_counter, true);

                if ($a != ' ') {
                    $b = $this->_next_char($this->_string, $byte_counter, true);
                    $dropone = " $a$b";
                }

                $byte_counter = 0;
                $a = ' ';
                $b = ' ';
            }
        }

        while ($byte_counter < $len) {
            $char = $this->_next_char($this->_string, $byte_counter, true);


            // language trigram detection
            if ($this->_compile_trigram) {
                if (!($b == ' ' && ($a == ' ' || $char == ' '))) {
                    if (!isset($this->_trigram[$a . $b . $char])) {
                        $this->_trigram[$a . $b . $char] = 1;
                    } else {
                        $this->_trigram[$a . $b . $char]++;
                    }
                }

                $a = $b;
                $b = $char;
            }

            // unicode block detection
            if ($this->_compile_unicode) {
                if ($this->_unicode_skip_symbols
                    && strlen($char) == 1
                    && ($char < 'A' || $char > 'z'
                    || ($char > 'Z' && $char < 'a'))
                    && $char != "'"
                ) {  // does not skip the apostrophe
                                            // since it's included in the language
                                            // models

                    $skipped_count++;
                    continue;
                }

                // build an array of all the characters
                if (isset($unicode_chars[$char])) {
                    $unicode_chars[$char]++;
                } else {
                    $unicode_chars[$char] = 1;
                }
            }

            // todo: add byte detection here
        }

        // unicode cleanup
        if ($this->_compile_unicode) {
            foreach ($unicode_chars as $utf8_char => $count) {
                $search_result = $this->_unicode_block_name(
                    $this->_utf8char2unicode($utf8_char), $blocks, $block_count
                );

                if ($search_result != -1) {
                    $block_name = $search_result[2];
                } else {
                    $block_name = '[Malformatted]';
                }

                if (isset($this->_unicode_blocks[$block_name])) {
                    $this->_unicode_blocks[$block_name] += $count;
                } else {
                    $this->_unicode_blocks[$block_name] = $count;
                }
            }
        }


        // trigram cleanup
        if ($this->_compile_trigram) {
            // pad the end
            if ($b != ' ') {
                if (!isset($this->_trigram["$a$b "])) {
                    $this->_trigram["$a$b "] = 1;
                } else {
                    $this->_trigram["$a$b "]++;
                }
            }

            // perl compatibility; Language::Guess does not pad the beginning
            // kludge
            if (isset($dropone)) {
                if ($this->_trigram[$dropone] == 1) {
                    unset($this->_trigram[$dropone]);
                } else {
                    $this->_trigram[$dropone]--;
                }
            }

            if (!empty($this->_trigram)) {
                $this->_trigram_ranks = $this->_arr_rank($this->_trigram);
            } else {
                $this->_trigram_ranks = array();
            }
        }
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

?>
