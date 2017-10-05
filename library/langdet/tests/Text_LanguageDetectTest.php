<?php

/**
 * @package Text_LanguageDetect
 * @version CVS: $Id$
 */
set_include_path(
    __DIR__ . '/../' . PATH_SEPARATOR . get_include_path()
);
error_reporting(E_ALL|E_STRICT);

require_once 'Text/LanguageDetect.php';
require_once __DIR__ . '/PrivProxy.php';

class Text_LanguageDetectTest extends PHPUnit_Framework_TestCase {

    function setup ()
    {
        ini_set('magic_quotes_runtime', 0);
        $this->x = new Text_LanguageDetect();
        $this->xproxy = new PrivProxy($this->x);
    }

    function tearDown ()
    {
        unset($this->x);
    }

    function test_get_data_locAbsolute()
    {
        $this->assertEquals(
            '/path/to/file',
            $this->xproxy->_get_data_loc('/path/to/file')
        );
    }

    function test_get_data_locPearPath()
    {
        $this->xproxy->_data_dir = '/path/to/pear/data';
        $this->assertEquals(
            '/path/to/pear/data/Text_LanguageDetect/file',
            $this->xproxy->_get_data_loc('file')
        );
    }

    /**
     * @expectedException Text_LanguageDetect_Exception
     * @expectedExceptionMessage Language database does not exist:
     */
    function test_readdbNonexistingFile()
    {
        $this->xproxy->_readdb('thisfiledoesnotexist');
    }

    /**
     * @expectedException Text_LanguageDetect_Exception
     * @expectedExceptionMessage Language database is not readable:
     */
    function test_readdbUnreadableFile()
    {
        $name = tempnam(sys_get_temp_dir(), 'unittest-Text_LanguageDetect-');
        chmod($name, 0000);
        $this->xproxy->_readdb($name);
    }

    /**
     * @expectedException Text_LanguageDetect_Exception
     * @expectedExceptionMessage Language database has no elements.
     */
    function test_checkTrigramEmpty()
    {
        $this->xproxy->_checkTrigram(array());
    }

    /**
     * @expectedException Text_LanguageDetect_Exception
     * @expectedExceptionMessage Language database is not an array
     */
    function test_checkTrigramNoArray()
    {
        $this->xproxy->_checkTrigram('foo');
    }

    /**
     * @expectedException Text_LanguageDetect_Exception
     * @expectedExceptionMessage Error loading database. Try turning magic_quotes_runtime off
     */
    function test_checkTrigramNoArrayMagicQuotes()
    {
        if (version_compare(PHP_VERSION, '5.4.0-dev') >= 0) {
            $this->markTestSkipped('5.4.0 has no magic quotes anymore');
        }
        ini_set('magic_quotes_runtime', 1);
        $this->xproxy->_checkTrigram('foo');
    }

    function test_splitter ()
    {
        $str = 'hello';

        $result = $this->xproxy->_trigram($str);

        $this->assertEquals(array(' he' => 1, 'hel' => 1, 'ell' => 1, 'llo' => 1, 'lo ' => 1), $result);

        $str = 'aa aa whatever';

        $result = $this->xproxy->_trigram($str);
        $this->assertEquals(2, $result[' aa']);
        $this->assertEquals(2, $result['aa ']);
        $this->assertEquals(1, $result['a a']);

        $str = 'aa  aa';
        $result = $this->xproxy->_trigram($str);
        $this->assertArrayNotHasKey('  a', $result, '  a');
        $this->assertArrayNotHasKey('a  ', $result, 'a  ');
    }

    function test_splitter2 ()
    {
        $str = 'resumé';
 
        $result = $this->xproxy->_trigram($str);
 
        $this->assertTrue(isset($result['mé ']), 'mé ');
        $this->assertTrue(isset($result['umé']), 'umé');
        $this->assertTrue(!isset($result['é  ']), 'é');

        // tests lower-casing accented characters
        $str = 'resumÉ';
        
        $result = $this->xproxy->_trigram($str);
 
        $this->assertTrue(isset($result['mé ']),'mé ');
        $this->assertTrue(isset($result['umé']),'umé');
        $this->assertTrue(!isset($result['é  ']),'é');
    }

    function test_sort ()
    {
        $arr = array('a' => 1, 'b' => 2, 'c' => 2);
        $this->xproxy->__call('_bub_sort',[&$arr]);

        $final_arr = array('b' => 2, 'c' => 2, 'a' => 1);

        $this->assertEquals($final_arr, $arr);
    }

    function test_error ()
    {
        // this test passes the object a series of bad strings to see how it handles them

        $result = $this->x->detectSimple("");

        $this->assertTrue(!$result);

        $result = $this->x->detectSimple("\n");

        $this->assertTrue(!$result);

        // should fail on extremely short strings
        $result = $this->x->detectSimple("a");

        $this->assertTrue(!$result);

        $result = $this->x->detectSimple("aa");

        $this->assertTrue(!$result);

        $result = $this->x->detectSimple('xxxxxxxxxxxxxxxxxxx');

        $this->assertEquals(null, $result);
    }

    function testOmitLanguages()
    {
        $str = 'This function may return Boolean FALSE, but may also return a non-Boolean value which evaluates to FALSE, such as 0 or "". Please read the section on Booleans for more information. Use the === operator for testing the return value of this function.';

        $myobj = new Text_LanguageDetect;
        $myobjproxy = new PrivProxy($myobj);

        $myobjproxy->_use_unicode_narrowing = false;

        $count = $myobj->getLanguageCount();
        $returnval = $myobj->omitLanguages('english');
        $newcount = $myobj->getLanguageCount();

        $this->assertEquals(1, $returnval);
        $this->assertEquals(1, $count - $newcount);

        $result = strtolower($myobj->detectSimple($str));

        $this->assertTrue($result != 'english', $result);

        $myobj = new Text_LanguageDetect;

        $count = $myobj->getLanguageCount();
        $returnval = $myobj->omitLanguages(array('danish', 'italian'), true);
        $newcount = $myobj->getLanguageCount();

        $this->assertEquals($count - $newcount, $returnval);
        $this->assertEquals($count - $returnval, $newcount);

        $result = strtolower($myobj->detectSimple($str));

        $this->assertTrue($result == 'danish' || $result == 'italian', $result);

        $result = $myobj->detect($str);

        $this->assertEquals(2, count($result));
        $this->assertTrue(isset($result['danish']));
        $this->assertTrue(isset($result['italian']));

        unset($myobj);
    }

    function testOmitLanguagesNameMode2()
    {
        $this->x->setNameMode(2);
        $this->assertEquals(1, $this->x->omitLanguages('en'));
    }

    function testOmitLanguagesIncludeString()
    {
        $this->assertGreaterThan(1, $this->x->omitLanguages('english', true));
        $langs = $this->x->getLanguages();
        $this->assertEquals(1, count($langs));
        $this->assertContains('english', $langs);
    }

    function testOmitLanguagesClearsClusterCache()
    {
        $this->x->omitLanguages(array('english', 'german'), true);
        $this->assertNull($this->xproxy->_clusters);
        $this->x->clusterLanguages();
        $this->assertNotNull($this->xproxy->_clusters);
        $this->x->omitLanguages('german');
        $this->assertNull($this->xproxy->_clusters, 'cluster cache be empty now');
    }

    function test_perl_compatibility()
    {
        // if this test fails, then many of the others will

        $this->x->setPerlCompatible(true);

        $testtext = "hello";

        $result = $this->xproxy->_trigram($testtext);

        $this->assertTrue(!isset($result[' he']));
    }

    function test_french_db ()
    {

        $safe_model = array(
            "es " => 0,     " de" => 1,     "de " => 2,     " le" => 3,     "ent" => 4,     
            "le " => 5,     "nt " => 6,     "la " => 7,     "s d" => 8,     " la" => 9,     
            "ion" => 10,     "on " => 11,     "re " => 12,     " pa" => 13,     "e l" => 14,     
            "e d" => 15,     " l'" => 16,     "e p" => 17,     " co" => 18,     " pr" => 19,     
            "tio" => 20,     "ns " => 21,     " en" => 22,     "ne " => 23,     "que" => 24,     
            "r l" => 25,     "les" => 26,     "ur " => 27,     "en " => 28,     "ati" => 29,     
            "ue " => 30,     " po" => 31,     " d'" => 32,     "par" => 33,     " a " => 34,     
            "et " => 35,     "it " => 36,     " qu" => 37,     "men" => 38,     "ons" => 39,     
            "te " => 40,     " et" => 41,     "t d" => 42,     " re" => 43,     "des" => 44,     
            " un" => 45,     "ie " => 46,     "s l" => 47,     " su" => 48,     "pou" => 49,     
            " au" => 50,     " à " => 51,     "con" => 52,     "er " => 53,     " no" => 54,     
            "ait" => 55,     "e c" => 56,     "se " => 57,     "té " => 58,     "du " => 59,     
            " du" => 60,     " dé" => 61,     "ce " => 62,     "e e" => 63,     "is " => 64,     
            "n d" => 65,     "s a" => 66,     " so" => 67,     "e r" => 68,     "e s" => 69,     
            "our" => 70,     "res" => 71,     "ssi" => 72,     "eur" => 73,     " se" => 74,     
            "eme" => 75,     "est" => 76,     "us " => 77,     "sur" => 78,     "ant" => 79,     
            "iqu" => 80,     "s p" => 81,     "une" => 82,     "uss" => 83,     "l'a" => 84,     
            "pro" => 85,     "ter" => 86,     "tre" => 87,     "end" => 88,     "rs " => 89,     
            " ce" => 90,     "e a" => 91,     "t p" => 92,     "un " => 93,     " ma" => 94,     
            " ru" => 95,     " ré" => 96,     "ous" => 97,     "ris" => 98,     "rus" => 99,     
            "sse" => 100,     "ans" => 101,     "ar " => 102,     "com" => 103,     "e m" => 104,     
            "ire" => 105,     "nce" => 106,     "nte" => 107,     "t l" => 108,     " av" => 109,     
            " mo" => 110,     " te" => 111,     "il " => 112,     "me " => 113,     "ont" => 114,     
            "ten" => 115,     "a p" => 116,     "dan" => 117,     "pas" => 118,     "qui" => 119,     
            "s e" => 120,     "s s" => 121,     " in" => 122,     "ist" => 123,     "lle" => 124,     
            "nou" => 125,     "pré" => 126,     "'un" => 127,     "air" => 128,     "d'a" => 129,     
            "ir " => 130,     "n e" => 131,     "rop" => 132,     "ts " => 133,     " da" => 134,     
            "a s" => 135,     "as " => 136,     "au " => 137,     "den" => 138,     "mai" => 139,     
            "mis" => 140,     "ori" => 141,     "out" => 142,     "rme" => 143,     "sio" => 144,     
            "tte" => 145,     "ux " => 146,     "a d" => 147,     "ien" => 148,     "n a" => 149,     
            "ntr" => 150,     "omm" => 151,     "ort" => 152,     "ouv" => 153,     "s c" => 154,     
            "son" => 155,     "tes" => 156,     "ver" => 157,     "ère" => 158,     " il" => 159,     
            " m " => 160,     " sa" => 161,     " ve" => 162,     "a r" => 163,     "ais" => 164,     
            "ava" => 165,     "di " => 166,     "n p" => 167,     "sti" => 168,     "ven" => 169,     
            " mi" => 170,     "ain" => 171,     "enc" => 172,     "for" => 173,     "ité" => 174,     
            "lar" => 175,     "oir" => 176,     "rem" => 177,     "ren" => 178,     "rro" => 179,     
            "rés" => 180,     "sie" => 181,     "t a" => 182,     "tur" => 183,     " pe" => 184,     
            " to" => 185,     "d'u" => 186,     "ell" => 187,     "err" => 188,     "ers" => 189,     
            "ide" => 190,     "ine" => 191,     "iss" => 192,     "mes" => 193,     "por" => 194,     
            "ran" => 195,     "sit" => 196,     "st " => 197,     "t r" => 198,     "uti" => 199,     
            "vai" => 200,     "é l" => 201,     "ési" => 202,     " di" => 203,     " n'" => 204,     
            " ét" => 205,     "a c" => 206,     "ass" => 207,     "e t" => 208,     "in " => 209,     
            "nde" => 210,     "pre" => 211,     "rat" => 212,     "s m" => 213,     "ste" => 214,     
            "tai" => 215,     "tch" => 216,     "ui " => 217,     "uro" => 218,     "ès " => 219,     
            " es" => 220,     " fo" => 221,     " tr" => 222,     "'ad" => 223,     "app" => 224,     
            "aux" => 225,     "e à" => 226,     "ett" => 227,     "iti" => 228,     "lit" => 229,     
            "nal" => 230,     "opé" => 231,     "r d" => 232,     "ra " => 233,     "rai" => 234,     
            "ror" => 235,     "s r" => 236,     "tat" => 237,     "uté" => 238,     "à l" => 239,     
            " af" => 240,     "anc" => 241,     "ara" => 242,     "art" => 243,     "bre" => 244,     
            "ché" => 245,     "dre" => 246,     "e f" => 247,     "ens" => 248,     "lem" => 249,     
            "n r" => 250,     "n t" => 251,     "ndr" => 252,     "nne" => 253,     "onn" => 254,     
            "pos" => 255,     "s t" => 256,     "tiq" => 257,     "ure" => 258,     " tu" => 259,     
            "ale" => 260,     "and" => 261,     "ave" => 262,     "cla" => 263,     "cou" => 264,     
            "e n" => 265,     "emb" => 266,     "ins" => 267,     "jou" => 268,     "mme" => 269,     
            "rie" => 270,     "rès" => 271,     "sem" => 272,     "str" => 273,     "t i" => 274,     
            "ues" => 275,     "uni" => 276,     "uve" => 277,     "é d" => 278,     "ée " => 279,     
            " ch" => 280,     " do" => 281,     " eu" => 282,     " fa" => 283,     " lo" => 284,     
            " ne" => 285,     " ra" => 286,     "arl" => 287,     "att" => 288,     "ec " => 289,     
            "ica" => 290,     "l a" => 291,     "l'o" => 292,     "l'é" => 293,     "mmi" => 294,     
            "nta" => 295,     "orm" => 296,     "ou " => 297,     "r u" => 298,     "rle" => 299
        );


        $my_arr = $this->xproxy->_lang_db['french'];

        foreach ($safe_model as $key => $value) {
            $this->assertTrue(isset($my_arr[$key]),$key);
            if (isset($my_arr[$key])) {
                $this->assertEquals($value, $my_arr[$key], $key);
            }
        }
    }

    function test_english_db ()
    {

        $realdb = array(
            " th" => 0,     "the" => 1,     "he " => 2,     "ed " => 3,     " to" => 4,     
            " in" => 5,     "er " => 6,     "ing" => 7,     "ng " => 8,     " an" => 9,     
            "nd " => 10,     " of" => 11,     "and" => 12,     "to " => 13,     "of " => 14,     
            " co" => 15,     "at " => 16,     "on " => 17,     "in " => 18,     " a " => 19,     
            "d t" => 20,     " he" => 21,     "e t" => 22,     "ion" => 23,     "es " => 24,     
            " re" => 25,     "re " => 26,     "hat" => 27,     " sa" => 28,     " st" => 29,     
            " ha" => 30,     "her" => 31,     "tha" => 32,     "tio" => 33,     "or " => 34,     
            " ''" => 35,     "en " => 36,     " wh" => 37,     "e s" => 38,     "ent" => 39,     
            "n t" => 40,     "s a" => 41,     "as " => 42,     "for" => 43,     "is " => 44,     
            "t t" => 45,     " be" => 46,     "ld " => 47,     "e a" => 48,     "rs " => 49,     
            " wa" => 50,     "ut " => 51,     "ve " => 52,     "ll " => 53,     "al " => 54,     
            " ma" => 55,     "e i" => 56,     " fo" => 57,     "'s " => 58,     "an " => 59,     
            "est" => 60,     " hi" => 61,     " mo" => 62,     " se" => 63,     " pr" => 64,     
            "s t" => 65,     "ate" => 66,     "st " => 67,     "ter" => 68,     "ere" => 69,     
            "ted" => 70,     "nt " => 71,     "ver" => 72,     "d a" => 73,     " wi" => 74,     
            "se " => 75,     "e c" => 76,     "ect" => 77,     "ns " => 78,     " on" => 79,     
            "ly " => 80,     "tol" => 81,     "ey " => 82,     "r t" => 83,     " ca" => 84,     
            "ati" => 85,     "ts " => 86,     "all" => 87,     " no" => 88,     "his" => 89,     
            "s o" => 90,     "ers" => 91,     "con" => 92,     "e o" => 93,     "ear" => 94,     
            "f t" => 95,     "e w" => 96,     "was" => 97,     "ons" => 98,     "sta" => 99,     
            "'' " => 100,     "sti" => 101,     "n a" => 102,     "sto" => 103,     "t h" => 104,     
            " we" => 105,     "id " => 106,     "th " => 107,     " it" => 108,     "ce " => 109,     
            " di" => 110,     "ave" => 111,     "d h" => 112,     "cou" => 113,     "pro" => 114,     
            "ad " => 115,     "oll" => 116,     "ry " => 117,     "d s" => 118,     "e m" => 119,     
            " so" => 120,     "ill" => 121,     "cti" => 122,     "te " => 123,     "tor" => 124,     
            "eve" => 125,     "g t" => 126,     "it " => 127,     " ch" => 128,     " de" => 129,     
            "hav" => 130,     "oul" => 131,     "ty " => 132,     "uld" => 133,     "use" => 134,     
            " al" => 135,     "are" => 136,     "ch " => 137,     "me " => 138,     "out" => 139,     
            "ove" => 140,     "wit" => 141,     "ys " => 142,     "chi" => 143,     "t a" => 144,     
            "ith" => 145,     "oth" => 146,     " ab" => 147,     " te" => 148,     " wo" => 149,     
            "s s" => 150,     "res" => 151,     "t w" => 152,     "tin" => 153,     "e b" => 154,     
            "e h" => 155,     "nce" => 156,     "t s" => 157,     "y t" => 158,     "e p" => 159,     
            "ele" => 160,     "hin" => 161,     "s i" => 162,     "nte" => 163,     " li" => 164,     
            "le " => 165,     " do" => 166,     "aid" => 167,     "hey" => 168,     "ne " => 169,     
            "s w" => 170,     " as" => 171,     " fr" => 172,     " tr" => 173,     "end" => 174,     
            "sai" => 175,     " el" => 176,     " ne" => 177,     " su" => 178,     "'t " => 179,     
            "ay " => 180,     "hou" => 181,     "ive" => 182,     "lec" => 183,     "n't" => 184,     
            " ye" => 185,     "but" => 186,     "d o" => 187,     "o t" => 188,     "y o" => 189,     
            " ho" => 190,     " me" => 191,     "be " => 192,     "cal" => 193,     "e e" => 194,     
            "had" => 195,     "ple" => 196,     " at" => 197,     " bu" => 198,     " la" => 199,     
            "d b" => 200,     "s h" => 201,     "say" => 202,     "t i" => 203,     " ar" => 204,     
            "e f" => 205,     "ght" => 206,     "hil" => 207,     "igh" => 208,     "int" => 209,     
            "not" => 210,     "ren" => 211,     " is" => 212,     " pa" => 213,     " sh" => 214,     
            "ays" => 215,     "com" => 216,     "n s" => 217,     "r a" => 218,     "rin" => 219,     
            "y a" => 220,     " un" => 221,     "n c" => 222,     "om " => 223,     "thi" => 224,     
            " mi" => 225,     "by " => 226,     "d i" => 227,     "e d" => 228,     "e n" => 229,     
            "t o" => 230,     " by" => 231,     "e r" => 232,     "eri" => 233,     "old" => 234,     
            "ome" => 235,     "whe" => 236,     "yea" => 237,     " gr" => 238,     "ar " => 239,     
            "ity" => 240,     "mpl" => 241,     "oun" => 242,     "one" => 243,     "ow " => 244,     
            "r s" => 245,     "s f" => 246,     "tat" => 247,     " ba" => 248,     " vo" => 249,     
            "bou" => 250,     "sam" => 251,     "tim" => 252,     "vot" => 253,     "abo" => 254,     
            "ant" => 255,     "ds " => 256,     "ial" => 257,     "ine" => 258,     "man" => 259,     
            "men" => 260,     " or" => 261,     " po" => 262,     "amp" => 263,     "can" => 264,     
            "der" => 265,     "e l" => 266,     "les" => 267,     "ny " => 268,     "ot " => 269,     
            "rec" => 270,     "tes" => 271,     "tho" => 272,     "ica" => 273,     "ild" => 274,     
            "ir " => 275,     "nde" => 276,     "ose" => 277,     "ous" => 278,     "pre" => 279,     
            "ste" => 280,     "era" => 281,     "per" => 282,     "r o" => 283,     "red" => 284,     
            "rie" => 285,     " bo" => 286,     " le" => 287,     "ali" => 288,     "ars" => 289,     
            "ore" => 290,     "ric" => 291,     "s m" => 292,     "str" => 293,     " fa" => 294,     
            "ess" => 295,     "ie " => 296,     "ist" => 297,     "lat" => 298,     "uri" => 299,
        );

        $mod = $this->xproxy->_lang_db['english'];

        foreach ($realdb as $key => $value) {
            $this->assertTrue(isset($mod[$key]), $key);
            if (isset($mod[$key])) {
                $this->assertEquals($value, $mod[$key], $key);
            }
        }

        foreach ($mod as $key => $value) {
            $this->assertTrue(isset($realdb[$key]));
            if (isset($realdb[$key])) {
                $this->assertEquals($value, $realdb[$key], $key);
            }
        }
    }

    function test_confidence ()
    {
        $str = 'The next thing to notice is the Content-length header. The Content-length header notifies the server of the size of the data that you intend to send. This prevents unexpected end-of-data errors from the server when dealing with binary data, because the server will read the specified number of bytes from the data stream regardless of any spurious end-of-data characters.';

        $result = $this->x->detectConfidence($str);

        $this->assertEquals(3, count($result));
        $this->assertTrue(isset($result['language']), 'language');
        $this->assertTrue(isset($result['similarity']), 'similarity');
        $this->assertTrue(isset($result['confidence']), 'confidence');
        $this->assertEquals('english', $result['language']);
        $this->assertTrue($result['similarity'] <= 300 && $result['similarity'] >= 0, $result['similarity']);
        $this->assertTrue($result['confidence'] <= 1 && $result['confidence'] >= 0, $result['confidence']);

        // todo: tests for Danish and Norwegian should have lower confidence
    }

    function test_long_example ()
    {
    // an example that is more than 300 trigrams long
        $str = 'The Italian Renaissance began the opening phase of the Renaissance, a period of great cultural change and achievement from the 14th to the 16th century. The word renaissance means "rebirth," and the era is best known for the renewed interest in the culture of classical antiquity. The Italian Renaissance began in northern Italy, centering in Florence. It then spread south, having an especially significant impact on Rome, which was largely rebuilt by the Renaissance popes. The Italian Renaissance is best known for its cultural achievements. This includes works of literature by such figures as Petrarch, Castiglione, and Machiavelli; artists such as Michaelangelo and Leonardo da Vinci, and great works of architecture such as The Duomo in Florence and St. Peter\'s Basilica in Rome. At the same time, present-day historians also see the era as one of economic regression and of little progress in science. Furthermore, some historians argue that the lot of the peasants and urban poor, the majority of the population, worsened during this period.';
    
        $this->x->setPerlCompatible();
        $tri = $this->xproxy->_trigram($str);
        
        $exp_tri = array(
            ' th',
            'the',
            'he ',
            ' an',
            ' re',
            ' of',
            'ce ',
            'nce',
            'of ',
            'ren',
            ' in',
            'and',
            'nd ',
            'an ',
            'san',
            ' it',
            'ais',
            'anc',
            'ena',
            'in ',
            'iss',
            'nai',
            'ssa',
            'tur',
            ' pe',
            'as ',
            'ch ',
            'ent',
            'ian',
            'me ',
            'n r',
            'res',
            ' as',
            ' be',
            ' wo',
            'at ',
            'chi',
            'e i',
            'e o',
            'e p',
            'gre',
            'his',
            'ing',
            'is ',
            'ita',
            'n f',
            'ng ',
            're ',
            's a',
            'st ',
            'tal',
            'ter',
            'th ',
            'ts ',
            'ure',
            'wor',
            ' ar',
            ' cu',
            ' po',
            ' su',
            'ach',
            'al ',
            'ali',
            'ans',
            'ant',
            'cul',
            'e b',
            'e r',
            'e t',
            'enc',
            'era',
            'eri',
            'es ',
            'est',
            'f t',
            'ica',
            'ion',
            'ist',
            'lia',
            'ltu',
            'ly ',
            'ns ',
            'nt ',
            'ome',
            'on ',
            'or ',
            'ore',
            'ori',
            'rea',
            'rom',
            'rth',
            's b',
            's o',
            'suc',
            't t',
            'uch',
            'ult',
            ' ac',
            ' by',
            ' ce',
            ' da',
            ' du',
            ' er',
            ' fl',
            ' fo',
            ' gr',
            ' hi',
            ' is',
            ' kn',
            ' li',
            ' ma',
            ' on',
            ' pr',
            ' ro',
            ' so',
            'a i',
            'ang',
            'arc',
            'arg',
            'beg',
            'bes',
            'by ',
            'cen',
            'cha',
            'd o',
            'd s',
            'e a',
            'e e',
            'e m',
            'e s',
            'eat',
            'ed ',
            'ega',
            'eme',
            'ene',
            'ess',
            'eve',
            'f l',
            'flo',
            'for',
            'gan',
            'gel',
            'h a',
            'her',
            'hie',
            'ich',
            'iev',
            'inc',
            'iod',
            'ite',
            'ity',
            'kno',
            'ks ',
            'l a',
            'lit',
            'lor',
            'men',
            'mic',
            'n i',
            'n s',
            'n t',
            'ne ',
            'nge',
            'now',
            'nte',
            'nts',
            'od ',
            'one',
            'ope',
            'ork',
            'own',
            'per',
            'pet',
            'pop',
            'pre',
            'ra ',
            'ral',
            'rch',
            'reb',
            'ria',
            'rin',
            'rio',
            'rks',
            's i',
            's p',
            'sen',
            'ssi',
            'sto',
            't i',
            't k',
            't o',
            'thi',
            'tor',
            'ty ',
            'ura',
            'vem',
            'vin',
            'wn ',
            'y s',
            ' a ',
            ' al',
            ' at',
            ' ba',
            ' ca',
            ' ch',
            ' cl',
            ' ec',
            ' es',
            ' fi',
            ' fr',
            ' fu',
            ' ha',
            ' im',
            ' la',
            ' le',
            ' lo',
            ' me',
            ' mi',
            ' no',
            ' op',
            ' ph',
            ' sa',
            ' sc',
            ' se',
            ' si',
            ' sp',
            ' st',
            ' ti',
            ' to',
            ' ur',
            ' vi',
            ' wa',
            ' wh',
            '\'s ',
            'a a',
            'a p',
            'a v',
            'act',
            'ad ',
            'ael',
            'ajo',
            'all',
            'als',
            'aly',
            'ame',
            'ard',
            'art',
            'asa',
            'ase',
            'asi',
            'ass',
            'ast',
            'ati',
            'atu',
            'ave',
            'avi',
            'ay ',
            'ban',
            'bas',
            'bir',
            'bui',
            'c r',
            'ca ',
            'cal',
            'can',
            'cas',
            'ci ',
            'cia',
            'cie',
            'cla',
            'clu',
            'con',
            'ct ',
            'ctu',
            'd a',
            'd d',
            'd g',
            'd i',
            'd l',
            'd m',
            'd r',
            'd t',
            'd u',
            'da ',
            'day',
            'des',
            'do ',
            'duo',
            'dur',
            'e c',
            'e d',
            'e h',
            'e l',
            'e w',
            'ead',
            'ean',
            'eas',
            'ebi',
            'ebu',
            'eci',
            'eco',
            'ect',
            'ee ',
            'egr',
            'ela',
            'ell',
            'elo',
            'ely',
            'en ',
            'eni',
            'eon',
            'er\'',
            'ere',
            'erm',
            'ern',
            'ese',
            'esp',
            'ete',
            'etr',
            'ewe',
            'f a',
            'f c',
            'f e',
            'f g',
            'fic',
            'fig',
            'fro',
            'fur',
            'g a',
            'g i',
            'g p',
            'g t',
            'ge ',
            'gli',
            'gni',
            'gue',
            'gur',
            'h c',
            'h f',
            'h t',
            'h w',
            'hae',
            'han',
            'has',
            'hat',
            'hav',
            'hen',
            'hia',
            'hic',
            'hit',
            'ial',
            'iav',
            'ic ',
            'ien',
            'ifi',
            'igl',
            'ign',
            'igu',
            'ili',
            'ilt',
            'ime',
            'imp',
            'int',
            'iqu',
            'irt',
            'it ',
            'its',
            'itt',
            'jor',
            'l c',
            'lan',
            'lar',
            'las',
            'lat',
            'le ',
            'leo',
            'li ',
            'lic',
            'lio',
            'lli',
            'lly',
            'lo ',
            'lot',
            'lso',
            'lt ',
            'lud',
            'm t',
            'mac',
            'maj',
            'mea',
            'mo ',
            'mor',
            'mpa',
            'n a',
            'n e',
            'n n',
            'n p',
            'nar',
            'nci',
            'ncl',
            'ned',
            'new',
            'nif',
            'nin',
            'nom',
            'nor',
            'nti',
            'ntu',
            'o a',
            'o d',
            'o i',
            'o s',
            'o t',
            'ogr',
            'om ',
            'omi',
            'omo',
            'ona',
            'ono',
            'oor',
            'opu',
            'ord',
            'ors',
            'ort',
            'ot ',
            'out',
            'pac',
            'pea',
            'pec',
            'pen',
            'pes',
            'pha',
            'poo',
            'pro',
            'pul',
            'qui',
            'r i',
            'r t',
            'r\'s',
            'rar',
            'rat',
            'rba',
            'rd ',
            'rdo',
            'reg',
            'rge',
            'rgu',
            'rit',
            'rmo',
            'rn ',
            'rog',
            'rse',
            'rti',
            'ry ',
            's c',
            's l',
            's m',
            's s',
            's t',
            's w',
            'sam',
            'sci',
            'se ',
            'see',
            'sic',
            'sig',
            'sil',
            'sio',
            'so ',
            'som',
            'sou',
            'spe',
            'spr',
            'ss ',
            'sti',
            'sts',
            't b',
            't c',
            't d',
            't f',
            't w',
            'tec',
            'tha',
            'tig',
            'tim',
            'tio',
            'tiq',
            'tis',
            'tle',
            'to ',
            'tra',
            'ttl',
            'ude',
            'ue ',
            'uil',
            'uit',
            'ula',
            'uom',
            'urb',
            'uri',
            'urt',
            'ury',
            'uth',
            'vel',
            'was',
            'wed',
            'whi',
            'y h',
            'y o',
            'y r',
            'y t'
        );

        $differences = array_diff(array_keys($tri), $exp_tri);
        $this->assertEquals(0, count($differences));
        $this->assertEquals(0, count(array_diff($exp_tri, array_keys($tri))));
        $this->assertEquals(count($exp_tri), count($tri));
        //print_r(array_diff($exp_tri, array_keys($tri)));
        //print_r(array_diff(array_keys($tri), $exp_tri));

        // tests the bubble sort mechanism
        $this->xproxy->__call('_bub_sort', [&$tri]);
        $this->assertEquals($exp_tri, array_keys($tri));

        $true_differences = array(
            "cas" => array('change' => 300, 'baserank' => 265, 'refrank' => null),    "s i" => array('change' => 21, 'baserank' => 183, 'refrank' => 162),
            "e b" => array('change' => 88, 'baserank' => 66, 'refrank' => 154),       "ent" => array('change' => 12, 'baserank' => 27, 'refrank' => 39),
            "ome" => array('change' => 152, 'baserank' => 83, 'refrank' => 235),      "ral" => array('change' => 300, 'baserank' => 176, 'refrank' => null),
            "ita" => array('change' => 300, 'baserank' => 44, 'refrank' => null),     "bas" => array('change' => 300, 'baserank' => 258, 'refrank' => null),
            " ar" => array('change' => 148, 'baserank' => 56, 'refrank' => 204),      " in" => array('change' => 5, 'baserank' => 10, 'refrank' => 5),
            " ti" => array('change' => 300, 'baserank' => 227, 'refrank' => null),    "ty " => array('change' => 61, 'baserank' => 193, 'refrank' => 132),
            "tur" => array('change' => 300, 'baserank' => 23, 'refrank' => null),     "iss" => array('change' => 300, 'baserank' => 20, 'refrank' => null),
            "ria" => array('change' => 300, 'baserank' => 179, 'refrank' => null),    " me" => array('change' => 25, 'baserank' => 216, 'refrank' => 191),
            "t k" => array('change' => 300, 'baserank' => 189, 'refrank' => null),    " es" => array('change' => 300, 'baserank' => 207, 'refrank' => null),
            "ren" => array('change' => 202, 'baserank' => 9, 'refrank' => 211),       "in " => array('change' => 1, 'baserank' => 19, 'refrank' => 18),
            "ly " => array('change' => 0, 'baserank' => 80, 'refrank' => 80), "st " => array('change' => 18, 'baserank' => 49, 'refrank' => 67),
            "ne " => array('change' => 8, 'baserank' => 161, 'refrank' => 169),       "all" => array('change' => 154, 'baserank' => 241, 'refrank' => 87),
            "vin" => array('change' => 300, 'baserank' => 196, 'refrank' => null),    " op" => array('change' => 300, 'baserank' => 219, 'refrank' => null),
            "chi" => array('change' => 107, 'baserank' => 36, 'refrank' => 143),      "e w" => array('change' => 197, 'baserank' => 293, 'refrank' => 96),
            " ro" => array('change' => 300, 'baserank' => 113, 'refrank' => null),    "act" => array('change' => 300, 'baserank' => 237, 'refrank' => null),
            "d r" => array('change' => 300, 'baserank' => 280, 'refrank' => null),    "nt " => array('change' => 11, 'baserank' => 82, 'refrank' => 71),
            "can" => array('change' => 0, 'baserank' => 264, 'refrank' => 264),       "rea" => array('change' => 300, 'baserank' => 88, 'refrank' => null),
            "ssa" => array('change' => 300, 'baserank' => 22, 'refrank' => null),     " fo" => array('change' => 47, 'baserank' => 104, 'refrank' => 57),
            "eas" => array('change' => 300, 'baserank' => 296, 'refrank' => null),    "mic" => array('change' => 300, 'baserank' => 157, 'refrank' => null),
            "cul" => array('change' => 300, 'baserank' => 65, 'refrank' => null),     " an" => array('change' => 6, 'baserank' => 3, 'refrank' => 9),
            "n t" => array('change' => 120, 'baserank' => 160, 'refrank' => 40),      "arg" => array('change' => 300, 'baserank' => 118, 'refrank' => null),
            " it" => array('change' => 93, 'baserank' => 15, 'refrank' => 108),       "ebi" => array('change' => 300, 'baserank' => 297, 'refrank' => null),
            " re" => array('change' => 21, 'baserank' => 4, 'refrank' => 25), "res" => array('change' => 120, 'baserank' => 31, 'refrank' => 151),
            " be" => array('change' => 13, 'baserank' => 33, 'refrank' => 46),        "rom" => array('change' => 300, 'baserank' => 89, 'refrank' => null),
            "'s " => array('change' => 175, 'baserank' => 233, 'refrank' => 58),      "arc" => array('change' => 300, 'baserank' => 117, 'refrank' => null),
            " su" => array('change' => 119, 'baserank' => 59, 'refrank' => 178),      "s p" => array('change' => 300, 'baserank' => 184, 'refrank' => null),
            "ich" => array('change' => 300, 'baserank' => 145, 'refrank' => null),    "d d" => array('change' => 300, 'baserank' => 275, 'refrank' => null),
            "cal" => array('change' => 70, 'baserank' => 263, 'refrank' => 193),      "ci " => array('change' => 300, 'baserank' => 266, 'refrank' => null),
            "ssi" => array('change' => 300, 'baserank' => 186, 'refrank' => null),    "bes" => array('change' => 300, 'baserank' => 120, 'refrank' => null),
            "des" => array('change' => 300, 'baserank' => 285, 'refrank' => null),    "e s" => array('change' => 91, 'baserank' => 129, 'refrank' => 38),
            "ch " => array('change' => 111, 'baserank' => 26, 'refrank' => 137),      "san" => array('change' => 300, 'baserank' => 14, 'refrank' => null),
            "asi" => array('change' => 300, 'baserank' => 249, 'refrank' => null),    "ajo" => array('change' => 300, 'baserank' => 240, 'refrank' => null),
            "ase" => array('change' => 300, 'baserank' => 248, 'refrank' => null),    " wa" => array('change' => 181, 'baserank' => 231, 'refrank' => 50),
            "vem" => array('change' => 300, 'baserank' => 195, 'refrank' => null),    "ed " => array('change' => 128, 'baserank' => 131, 'refrank' => 3),
            "ant" => array('change' => 191, 'baserank' => 64, 'refrank' => 255),      "a p" => array('change' => 300, 'baserank' => 235, 'refrank' => null),
            "lor" => array('change' => 300, 'baserank' => 155, 'refrank' => null),    "kno" => array('change' => 300, 'baserank' => 151, 'refrank' => null),
            "ais" => array('change' => 300, 'baserank' => 16, 'refrank' => null),     " pe" => array('change' => 300, 'baserank' => 24, 'refrank' => null),
            "or " => array('change' => 51, 'baserank' => 85, 'refrank' => 34),        "e i" => array('change' => 19, 'baserank' => 37, 'refrank' => 56),
            " sp" => array('change' => 300, 'baserank' => 225, 'refrank' => null),    "ad " => array('change' => 123, 'baserank' => 238, 'refrank' => 115),
            " kn" => array('change' => 300, 'baserank' => 108, 'refrank' => null),    "ega" => array('change' => 300, 'baserank' => 132, 'refrank' => null),
            " ba" => array('change' => 46, 'baserank' => 202, 'refrank' => 248),      "d t" => array('change' => 261, 'baserank' => 281, 'refrank' => 20),
            "ork" => array('change' => 300, 'baserank' => 169, 'refrank' => null),    "lia" => array('change' => 300, 'baserank' => 78, 'refrank' => null),
            "ard" => array('change' => 300, 'baserank' => 245, 'refrank' => null),    "iev" => array('change' => 300, 'baserank' => 146, 'refrank' => null),
            "of " => array('change' => 6, 'baserank' => 8, 'refrank' => 14),  " cu" => array('change' => 300, 'baserank' => 57, 'refrank' => null),
            "day" => array('change' => 300, 'baserank' => 284, 'refrank' => null),    "cen" => array('change' => 300, 'baserank' => 122, 'refrank' => null),
            "re " => array('change' => 21, 'baserank' => 47, 'refrank' => 26),        "ist" => array('change' => 220, 'baserank' => 77, 'refrank' => 297),
            " fl" => array('change' => 300, 'baserank' => 103, 'refrank' => null),    "anc" => array('change' => 300, 'baserank' => 17, 'refrank' => null),
            "at " => array('change' => 19, 'baserank' => 35, 'refrank' => 16),        "rch" => array('change' => 300, 'baserank' => 177, 'refrank' => null),
            "ang" => array('change' => 300, 'baserank' => 116, 'refrank' => null),    " mi" => array('change' => 8, 'baserank' => 217, 'refrank' => 225),
            "y s" => array('change' => 300, 'baserank' => 198, 'refrank' => null),    "ca " => array('change' => 300, 'baserank' => 262, 'refrank' => null),
            " ma" => array('change' => 55, 'baserank' => 110, 'refrank' => 55),       " lo" => array('change' => 300, 'baserank' => 215, 'refrank' => null),
            "rin" => array('change' => 39, 'baserank' => 180, 'refrank' => 219),      " im" => array('change' => 300, 'baserank' => 212, 'refrank' => null),
            " er" => array('change' => 300, 'baserank' => 102, 'refrank' => null),    "ce " => array('change' => 103, 'baserank' => 6, 'refrank' => 109),
            "bui" => array('change' => 300, 'baserank' => 260, 'refrank' => null),    "lit" => array('change' => 300, 'baserank' => 154, 'refrank' => null),
            "iod" => array('change' => 300, 'baserank' => 148, 'refrank' => null),    "ame" => array('change' => 300, 'baserank' => 244, 'refrank' => null),
            "ter" => array('change' => 17, 'baserank' => 51, 'refrank' => 68),        "e a" => array('change' => 78, 'baserank' => 126, 'refrank' => 48),
            "f l" => array('change' => 300, 'baserank' => 137, 'refrank' => null),    "eri" => array('change' => 162, 'baserank' => 71, 'refrank' => 233),
            "ra " => array('change' => 300, 'baserank' => 175, 'refrank' => null),    "ng " => array('change' => 38, 'baserank' => 46, 'refrank' => 8),
            "d i" => array('change' => 50, 'baserank' => 277, 'refrank' => 227),      "asa" => array('change' => 300, 'baserank' => 247, 'refrank' => null),
            "wn " => array('change' => 300, 'baserank' => 197, 'refrank' => null),    " at" => array('change' => 4, 'baserank' => 201, 'refrank' => 197),
            "now" => array('change' => 300, 'baserank' => 163, 'refrank' => null),    " by" => array('change' => 133, 'baserank' => 98, 'refrank' => 231),
            "n s" => array('change' => 58, 'baserank' => 159, 'refrank' => 217),      " li" => array('change' => 55, 'baserank' => 109, 'refrank' => 164),
            "l a" => array('change' => 300, 'baserank' => 153, 'refrank' => null),    "da " => array('change' => 300, 'baserank' => 283, 'refrank' => null),
            "ean" => array('change' => 300, 'baserank' => 295, 'refrank' => null),    "tal" => array('change' => 300, 'baserank' => 50, 'refrank' => null),
            "d a" => array('change' => 201, 'baserank' => 274, 'refrank' => 73),      "ct " => array('change' => 300, 'baserank' => 272, 'refrank' => null),
            "ali" => array('change' => 226, 'baserank' => 62, 'refrank' => 288),      "ian" => array('change' => 300, 'baserank' => 28, 'refrank' => null),
            " sa" => array('change' => 193, 'baserank' => 221, 'refrank' => 28),      "do " => array('change' => 300, 'baserank' => 286, 'refrank' => null),
            "t o" => array('change' => 40, 'baserank' => 190, 'refrank' => 230),      "ure" => array('change' => 300, 'baserank' => 54, 'refrank' => null),
            "e c" => array('change' => 213, 'baserank' => 289, 'refrank' => 76),      "ing" => array('change' => 35, 'baserank' => 42, 'refrank' => 7),
            "d o" => array('change' => 63, 'baserank' => 124, 'refrank' => 187),      " ha" => array('change' => 181, 'baserank' => 211, 'refrank' => 30),
            "ts " => array('change' => 33, 'baserank' => 53, 'refrank' => 86),        "rth" => array('change' => 300, 'baserank' => 90, 'refrank' => null),
            "cla" => array('change' => 300, 'baserank' => 269, 'refrank' => null),    " ac" => array('change' => 300, 'baserank' => 97, 'refrank' => null),
            "th " => array('change' => 55, 'baserank' => 52, 'refrank' => 107),       "rio" => array('change' => 300, 'baserank' => 181, 'refrank' => null),
            "al " => array('change' => 7, 'baserank' => 61, 'refrank' => 54), "sto" => array('change' => 84, 'baserank' => 187, 'refrank' => 103),
            "e o" => array('change' => 55, 'baserank' => 38, 'refrank' => 93),        "bir" => array('change' => 300, 'baserank' => 259, 'refrank' => null),
            " pr" => array('change' => 48, 'baserank' => 112, 'refrank' => 64),       " le" => array('change' => 73, 'baserank' => 214, 'refrank' => 287),
            "nai" => array('change' => 300, 'baserank' => 21, 'refrank' => null),     "t i" => array('change' => 15, 'baserank' => 188, 'refrank' => 203),
            " po" => array('change' => 204, 'baserank' => 58, 'refrank' => 262),      "f t" => array('change' => 21, 'baserank' => 74, 'refrank' => 95),
            "ban" => array('change' => 300, 'baserank' => 257, 'refrank' => null),    "an " => array('change' => 46, 'baserank' => 13, 'refrank' => 59),
            "wor" => array('change' => 300, 'baserank' => 55, 'refrank' => null),     "pet" => array('change' => 300, 'baserank' => 172, 'refrank' => null),
            "ael" => array('change' => 300, 'baserank' => 239, 'refrank' => null),    "ura" => array('change' => 300, 'baserank' => 194, 'refrank' => null),
            "eve" => array('change' => 11, 'baserank' => 136, 'refrank' => 125),      "ion" => array('change' => 53, 'baserank' => 76, 'refrank' => 23),
            "nge" => array('change' => 300, 'baserank' => 162, 'refrank' => null),    "cha" => array('change' => 300, 'baserank' => 123, 'refrank' => null),
            "ity" => array('change' => 90, 'baserank' => 150, 'refrank' => 240),      " se" => array('change' => 160, 'baserank' => 223, 'refrank' => 63),
            " on" => array('change' => 32, 'baserank' => 111, 'refrank' => 79),       "s b" => array('change' => 300, 'baserank' => 91, 'refrank' => null),
            "ans" => array('change' => 300, 'baserank' => 63, 'refrank' => null),     "own" => array('change' => 300, 'baserank' => 170, 'refrank' => null),
            " si" => array('change' => 300, 'baserank' => 224, 'refrank' => null),    "e r" => array('change' => 165, 'baserank' => 67, 'refrank' => 232),
            "est" => array('change' => 13, 'baserank' => 73, 'refrank' => 60),        "hie" => array('change' => 300, 'baserank' => 144, 'refrank' => null),
            "aly" => array('change' => 300, 'baserank' => 243, 'refrank' => null),    "and" => array('change' => 1, 'baserank' => 11, 'refrank' => 12),
            "beg" => array('change' => 300, 'baserank' => 119, 'refrank' => null),    "dur" => array('change' => 300, 'baserank' => 288, 'refrank' => null),
            "reb" => array('change' => 300, 'baserank' => 178, 'refrank' => null),    "e e" => array('change' => 67, 'baserank' => 127, 'refrank' => 194),
            "men" => array('change' => 104, 'baserank' => 156, 'refrank' => 260),     " la" => array('change' => 14, 'baserank' => 213, 'refrank' => 199),
            "con" => array('change' => 179, 'baserank' => 271, 'refrank' => 92),      " fu" => array('change' => 300, 'baserank' => 210, 'refrank' => null),
            "e l" => array('change' => 26, 'baserank' => 292, 'refrank' => 266),      "s a" => array('change' => 7, 'baserank' => 48, 'refrank' => 41),
            "art" => array('change' => 300, 'baserank' => 246, 'refrank' => null),    "ltu" => array('change' => 300, 'baserank' => 79, 'refrank' => null),
            "a i" => array('change' => 300, 'baserank' => 115, 'refrank' => null),    "ctu" => array('change' => 300, 'baserank' => 273, 'refrank' => null),
            "tor" => array('change' => 68, 'baserank' => 192, 'refrank' => 124),      "ach" => array('change' => 300, 'baserank' => 60, 'refrank' => null),
            "d g" => array('change' => 300, 'baserank' => 276, 'refrank' => null),    "od " => array('change' => 300, 'baserank' => 166, 'refrank' => null),
            "nte" => array('change' => 1, 'baserank' => 164, 'refrank' => 163),       "ena" => array('change' => 300, 'baserank' => 18, 'refrank' => null),
            "d l" => array('change' => 300, 'baserank' => 278, 'refrank' => null),    "ene" => array('change' => 300, 'baserank' => 134, 'refrank' => null),
            "e h" => array('change' => 136, 'baserank' => 291, 'refrank' => 155),     "era" => array('change' => 211, 'baserank' => 70, 'refrank' => 281),
            "on " => array('change' => 67, 'baserank' => 84, 'refrank' => 17),        " ce" => array('change' => 300, 'baserank' => 99, 'refrank' => null),
            "ay " => array('change' => 76, 'baserank' => 256, 'refrank' => 180),      " da" => array('change' => 300, 'baserank' => 100, 'refrank' => null),
            "ori" => array('change' => 300, 'baserank' => 87, 'refrank' => null),     "atu" => array('change' => 300, 'baserank' => 253, 'refrank' => null),
            "ave" => array('change' => 143, 'baserank' => 254, 'refrank' => 111),     "rks" => array('change' => 300, 'baserank' => 182, 'refrank' => null),
            "e d" => array('change' => 62, 'baserank' => 290, 'refrank' => 228),      "ns " => array('change' => 3, 'baserank' => 81, 'refrank' => 78),
            " ca" => array('change' => 119, 'baserank' => 203, 'refrank' => 84),      "d s" => array('change' => 7, 'baserank' => 125, 'refrank' => 118),
            "uch" => array('change' => 300, 'baserank' => 95, 'refrank' => null),     "a v" => array('change' => 300, 'baserank' => 236, 'refrank' => null),
            "nce" => array('change' => 149, 'baserank' => 7, 'refrank' => 156),       "his" => array('change' => 48, 'baserank' => 41, 'refrank' => 89),
            "flo" => array('change' => 300, 'baserank' => 138, 'refrank' => null),    "ead" => array('change' => 300, 'baserank' => 294, 'refrank' => null),
            " vi" => array('change' => 300, 'baserank' => 230, 'refrank' => null),    "me " => array('change' => 109, 'baserank' => 29, 'refrank' => 138),
            "suc" => array('change' => 300, 'baserank' => 93, 'refrank' => null),     "e p" => array('change' => 120, 'baserank' => 39, 'refrank' => 159),
            "eci" => array('change' => 300, 'baserank' => 299, 'refrank' => null),    "eme" => array('change' => 300, 'baserank' => 133, 'refrank' => null),
            "sen" => array('change' => 300, 'baserank' => 185, 'refrank' => null),    "ks " => array('change' => 300, 'baserank' => 152, 'refrank' => null),
            " to" => array('change' => 224, 'baserank' => 228, 'refrank' => 4),       " gr" => array('change' => 133, 'baserank' => 105, 'refrank' => 238),
            " ch" => array('change' => 76, 'baserank' => 204, 'refrank' => 128),      "ati" => array('change' => 167, 'baserank' => 252, 'refrank' => 85),
            " th" => array('change' => 0, 'baserank' => 0, 'refrank' => 0),   " ec" => array('change' => 300, 'baserank' => 206, 'refrank' => null),
            " wo" => array('change' => 115, 'baserank' => 34, 'refrank' => 149),      "ope" => array('change' => 300, 'baserank' => 168, 'refrank' => null),
            " a " => array('change' => 180, 'baserank' => 199, 'refrank' => 19),      "one" => array('change' => 76, 'baserank' => 167, 'refrank' => 243),
            "n f" => array('change' => 300, 'baserank' => 45, 'refrank' => null),     "eat" => array('change' => 300, 'baserank' => 130, 'refrank' => null),
            "ica" => array('change' => 198, 'baserank' => 75, 'refrank' => 273),      "inc" => array('change' => 300, 'baserank' => 147, 'refrank' => null),
            "enc" => array('change' => 300, 'baserank' => 69, 'refrank' => null),     "ore" => array('change' => 204, 'baserank' => 86, 'refrank' => 290),
            "is " => array('change' => 1, 'baserank' => 43, 'refrank' => 44), " as" => array('change' => 139, 'baserank' => 32, 'refrank' => 171),
            "nts" => array('change' => 300, 'baserank' => 165, 'refrank' => null),    "d m" => array('change' => 300, 'baserank' => 279, 'refrank' => null),
            "her" => array('change' => 112, 'baserank' => 143, 'refrank' => 31),      " al" => array('change' => 65, 'baserank' => 200, 'refrank' => 135),
            " is" => array('change' => 105, 'baserank' => 107, 'refrank' => 212),     "e t" => array('change' => 46, 'baserank' => 68, 'refrank' => 22),
            "c r" => array('change' => 300, 'baserank' => 261, 'refrank' => null),    " hi" => array('change' => 45, 'baserank' => 106, 'refrank' => 61),
            "cia" => array('change' => 300, 'baserank' => 267, 'refrank' => null),    " fr" => array('change' => 37, 'baserank' => 209, 'refrank' => 172),
            "ult" => array('change' => 300, 'baserank' => 96, 'refrank' => null),     "e m" => array('change' => 9, 'baserank' => 128, 'refrank' => 119),
            "ass" => array('change' => 300, 'baserank' => 250, 'refrank' => null),    "s o" => array('change' => 2, 'baserank' => 92, 'refrank' => 90),
            "pop" => array('change' => 300, 'baserank' => 173, 'refrank' => null),    "nd " => array('change' => 2, 'baserank' => 12, 'refrank' => 10),
            "the" => array('change' => 0, 'baserank' => 1, 'refrank' => 1),   " st" => array('change' => 197, 'baserank' => 226, 'refrank' => 29),
            " no" => array('change' => 130, 'baserank' => 218, 'refrank' => 88),      "ast" => array('change' => 300, 'baserank' => 251, 'refrank' => null),
            " fi" => array('change' => 300, 'baserank' => 208, 'refrank' => null),    "ess" => array('change' => 160, 'baserank' => 135, 'refrank' => 295),
            "gre" => array('change' => 300, 'baserank' => 40, 'refrank' => null),     "h a" => array('change' => 300, 'baserank' => 142, 'refrank' => null),
            "duo" => array('change' => 300, 'baserank' => 287, 'refrank' => null),    " so" => array('change' => 6, 'baserank' => 114, 'refrank' => 120),
            "es " => array('change' => 48, 'baserank' => 72, 'refrank' => 24),        "for" => array('change' => 96, 'baserank' => 139, 'refrank' => 43),
            "gan" => array('change' => 300, 'baserank' => 140, 'refrank' => null),    "per" => array('change' => 111, 'baserank' => 171, 'refrank' => 282),
            "thi" => array('change' => 33, 'baserank' => 191, 'refrank' => 224),      " of" => array('change' => 6, 'baserank' => 5, 'refrank' => 11),
            " cl" => array('change' => 300, 'baserank' => 205, 'refrank' => null),    " sc" => array('change' => 300, 'baserank' => 222, 'refrank' => null),
            "t t" => array('change' => 49, 'baserank' => 94, 'refrank' => 45),        "als" => array('change' => 300, 'baserank' => 242, 'refrank' => null),
            "avi" => array('change' => 300, 'baserank' => 255, 'refrank' => null),    "cie" => array('change' => 300, 'baserank' => 268, 'refrank' => null),
            " du" => array('change' => 300, 'baserank' => 101, 'refrank' => null),    "pre" => array('change' => 105, 'baserank' => 174, 'refrank' => 279),
            "as " => array('change' => 17, 'baserank' => 25, 'refrank' => 42),        "a a" => array('change' => 300, 'baserank' => 234, 'refrank' => null),
            "gel" => array('change' => 300, 'baserank' => 141, 'refrank' => null),    "ite" => array('change' => 300, 'baserank' => 149, 'refrank' => null),
            "n r" => array('change' => 300, 'baserank' => 30, 'refrank' => null),     "by " => array('change' => 105, 'baserank' => 121, 'refrank' => 226),
            "d u" => array('change' => 300, 'baserank' => 282, 'refrank' => null),    "clu" => array('change' => 300, 'baserank' => 270, 'refrank' => null),
            " ur" => array('change' => 300, 'baserank' => 229, 'refrank' => null),    "ebu" => array('change' => 300, 'baserank' => 298, 'refrank' => null),
            "n i" => array('change' => 300, 'baserank' => 158, 'refrank' => null),    "he " => array('change' => 0, 'baserank' => 2, 'refrank' => 2),
            " wh" => array('change' => 195, 'baserank' => 232, 'refrank' => 37),      " ph" => array('change' => 300, 'baserank' => 220, 'refrank' => null),
        );
        
        $ranked = $this->xproxy->_arr_rank($this->xproxy->_trigram($str));
        $results = $this->x->detect($str);

        $count = count($ranked);
        $sum = 0;

        //foreach ($this->xproxy->_lang_db['english'] as $key => $value) {
        foreach ($ranked as $key => $value) {
            if (isset($ranked[$key]) && isset($this->xproxy->_lang_db['english'][$key])) {
                $difference = abs($this->xproxy->_lang_db['english'][$key] - $ranked[$key]);
            } else {
                $difference = 300;
            }

            $this->assertTrue(isset($true_differences[$key]), "'$key'");
            if (isset($true_differences[$key])) {
                $this->assertEquals($true_differences[$key]['change'], $difference, "'$key'");
            }
            $sum += $difference;
        }

        $this->assertEquals(300, $count);
        $this->assertEquals(59490, $sum);

        $this->assertEquals('english', key($results));
        $this->assertEquals(198, floor(current($results)));
        next($results);
        $this->assertEquals('italian', key($results));
        $this->assertEquals(228, floor(current($results)));
    }

    function test_french ()
    {
        $this->x->setPerlCompatible();
        $str = "Verifions que le détecteur de langues marche";

        $trigrams = $this->xproxy->_trigram($str);
        $this->assertEquals(42, count($trigrams));
        // verified in Language::Guess

        $ranked = $this->xproxy->_arr_rank($trigrams);
        $this->assertEquals(0, $ranked['e l']);

        $correct_ranks = array(
            ' de' => 1,
            "éte" => 41,
            "dét" => 12,
            'fio' => 18,
            'de ' => 11,
            'ons' => 28,
            'ect' => 14,
            'le ' => 24,
            'arc' => 8,
            'lan' => 23,
            'es ' => 16,
            'mar' => 25,
            " dé" => 2,
            'ifi' => 21,
            'gue' => 19,
            'ur ' => 39,
            'rch' => 31,
            'ang' => 7,
            'que' => 29,
            'ngu' => 26,
            'e d' => 13,
            'rif' => 32,
            ' ma' => 5,
            'tec' => 35,
            'ns ' => 27,
            ' la' => 3,
            ' le' => 4,
            'r d' => 30,
            'e l' => 0,
            'che' => 9,
            's m' => 33,
            'ue ' => 37,
            'ver' => 40,
            'teu' => 36,
            'eri' => 15,
            'cte' => 10,
            'ues' => 38,
            's q' => 34,
            'eur' => 17,
            ' qu' => 6,
            'he ' => 20,
            'ion' => 22
        );


        $this->assertEquals(count($correct_ranks), count($ranked), "different number of trigrams found");

        $distances = array(
            ' de' => array('change' => 0, 'baserank' => 1, 'refrank' => 1),
            'éte' => array('change' => 300, 'baserank' => 41, 'refrank' => null),
            'dét' => array('change' => 300, 'baserank' => 12, 'refrank' => null),
            'fio' => array('change' => 300, 'baserank' => 18, 'refrank' => null),
            'de ' => array('change' => 9, 'baserank' => 11, 'refrank' => 2),
            'ons' => array('change' => 11, 'baserank' => 28, 'refrank' => 39),
            'ect' => array('change' => 300, 'baserank' => 14, 'refrank' => null),
            'le ' => array('change' => 19, 'baserank' => 24, 'refrank' => 5),
            'arc' => array('change' => 300, 'baserank' => 8, 'refrank' => null),
            'lan' => array('change' => 300, 'baserank' => 23, 'refrank' => null),
            'es ' => array('change' => 16, 'baserank' => 16, 'refrank' => 0),
            'mar' => array('change' => 300, 'baserank' => 25, 'refrank' => null),
            ' dé' => array('change' => 59, 'baserank' => 2, 'refrank' => 61),
            'ifi' => array('change' => 300, 'baserank' => 21, 'refrank' => null),
            'gue' => array('change' => 300, 'baserank' => 19, 'refrank' => null),
            'ur ' => array('change' => 12, 'baserank' => 39, 'refrank' => 27),
            'rch' => array('change' => 300, 'baserank' => 31, 'refrank' => null),
            'ang' => array('change' => 300, 'baserank' => 7, 'refrank' => null),
            'que' => array('change' => 5, 'baserank' => 29, 'refrank' => 24),
            'ngu' => array('change' => 300, 'baserank' => 26, 'refrank' => null),
            'e d' => array('change' => 2, 'baserank' => 13, 'refrank' => 15),
            'rif' => array('change' => 300, 'baserank' => 32, 'refrank' => null),
            ' ma' => array('change' => 89, 'baserank' => 5, 'refrank' => 94),
            'tec' => array('change' => 300, 'baserank' => 35, 'refrank' => null),
            'ns ' => array('change' => 6, 'baserank' => 27, 'refrank' => 21),
            ' la' => array('change' => 6, 'baserank' => 3, 'refrank' => 9),
            ' le' => array('change' => 1, 'baserank' => 4, 'refrank' => 3),
            'r d' => array('change' => 202, 'baserank' => 30, 'refrank' => 232),
            'e l' => array('change' => 14, 'baserank' => 0, 'refrank' => 14),
            'che' => array('change' => 300, 'baserank' => 9, 'refrank' => null),
            's m' => array('change' => 180, 'baserank' => 33, 'refrank' => 213),
            'ue ' => array('change' => 7, 'baserank' => 37, 'refrank' => 30),
            'ver' => array('change' => 117, 'baserank' => 40, 'refrank' => 157),
            'teu' => array('change' => 300, 'baserank' => 36, 'refrank' => null),
            'eri' => array('change' => 300, 'baserank' => 15, 'refrank' => null),
            'cte' => array('change' => 300, 'baserank' => 10, 'refrank' => null),
            'ues' => array('change' => 237, 'baserank' => 38, 'refrank' => 275),
            's q' => array('change' => 300, 'baserank' => 34, 'refrank' => null),
            'eur' => array('change' => 56, 'baserank' => 17, 'refrank' => 73),
            ' qu' => array('change' => 31, 'baserank' => 6, 'refrank' => 37),
            'he ' => array('change' => 300, 'baserank' => 20, 'refrank' => null),
            'ion' => array('change' => 12, 'baserank' => 22, 'refrank' => 10),
        );



        $french_ranks = $this->xproxy->_lang_db['french'];

        $sumchange = 0;
        foreach ($ranked as $key => $value) {
            if (isset($french_ranks[$key])) {
                $difference = abs($french_ranks[$key] - $ranked[$key]);
            } else {
                $difference = 300;
            }
            $this->assertTrue(isset($distances[$key]), $key);
            if (isset($distances[$key])) {
                $this->assertEquals($distances[$key]['baserank'], $ranked[$key], "baserank for $key");
                if ($distances[$key]['refrank'] === null) {
                    $this->assertArrayNotHasKey($key, $french_ranks);
                } else {
                    $this->assertEquals($distances[$key]['refrank'], $french_ranks[$key], "refrank for $key");
                }
                $this->assertEquals($distances[$key]['change'], $difference, "difference for $key");
            }

            $sumchange += $difference;
        }

        $actual_result = $this->xproxy->_distance($french_ranks, $ranked);
        $this->assertEquals($sumchange, $actual_result);
        $this->assertEquals(7091, $actual_result);
        $this->assertEquals(168, floor($sumchange/count($trigrams)));

        $final_result = $this->x->detect($str);
        $this->assertEquals(168, floor($final_result['french']));
        $this->assertEquals(211, $final_result['spanish']);
    }

    function test_russian ()
    {
        $str = 'авай проверить  узнает ли наш угадатель русски язык';

        $this->x->setPerlCompatible();
        $trigrams = $this->xproxy->_trigram($str);
        $ranked = $this->xproxy->_arr_rank($trigrams);

        $correct_ranks = array(
            ' ру' => array('change' => 300, 'baserank' => 3, 'refrank' => null),
            'ай ' => array('change' => 300, 'baserank' => 10, 'refrank' => null),
            'ада' => array('change' => 300, 'baserank' => 8, 'refrank' => null),
            ' пр' => array('change' => 1, 'baserank' => 2, 'refrank' => 1),
            ' яз' => array('change' => 300, 'baserank' => 6, 'refrank' => null),
            'ить' => array('change' => 300, 'baserank' => 24, 'refrank' => null),
            ' на' => array('change' => 1, 'baserank' => 1, 'refrank' => 0),
            'зна' => array('change' => 153, 'baserank' => 20, 'refrank' => 173),
            'вай' => array('change' => 300, 'baserank' => 13, 'refrank' => null),
            'ш у' => array('change' => 300, 'baserank' => 44, 'refrank' => null),
            'ль ' => array('change' => 300, 'baserank' => 28, 'refrank' => null),
            ' ли' => array('change' => 300, 'baserank' => 0, 'refrank' => null),
            'сск' => array('change' => 300, 'baserank' => 37, 'refrank' => null),
            'ть ' => array('change' => 31, 'baserank' => 40, 'refrank' => 9),
            'ава' => array('change' => 300, 'baserank' => 7, 'refrank' => null),
            'про' => array('change' => 18, 'baserank' => 32, 'refrank' => 14),
            'гад' => array('change' => 300, 'baserank' => 15, 'refrank' => null),
            'усс' => array('change' => 300, 'baserank' => 43, 'refrank' => null),
            'ык ' => array('change' => 300, 'baserank' => 45, 'refrank' => null),
            'ель' => array('change' => 64, 'baserank' => 17, 'refrank' => 81),
            'язы' => array('change' => 300, 'baserank' => 47, 'refrank' => null),
            ' уг' => array('change' => 300, 'baserank' => 4, 'refrank' => null),
            'ате' => array('change' => 152, 'baserank' => 11, 'refrank' => 163),
            'и н' => array('change' => 63, 'baserank' => 22, 'refrank' => 85),
            'и я' => array('change' => 300, 'baserank' => 23, 'refrank' => null),
            'ает' => array('change' => 152, 'baserank' => 9, 'refrank' => 161),
            'узн' => array('change' => 300, 'baserank' => 42, 'refrank' => null),
            'ери' => array('change' => 300, 'baserank' => 18, 'refrank' => null),
            'ли ' => array('change' => 23, 'baserank' => 27, 'refrank' => 4),
            'т л' => array('change' => 300, 'baserank' => 38, 'refrank' => null),
            ' уз' => array('change' => 300, 'baserank' => 5, 'refrank' => null),
            'дат' => array('change' => 203, 'baserank' => 16, 'refrank' => 219),
            'зык' => array('change' => 300, 'baserank' => 21, 'refrank' => null),
            'ров' => array('change' => 59, 'baserank' => 34, 'refrank' => 93),
            'рит' => array('change' => 300, 'baserank' => 33, 'refrank' => null),
            'ь р' => array('change' => 300, 'baserank' => 46, 'refrank' => null),
            'ет ' => array('change' => 19, 'baserank' => 19, 'refrank' => 38),
            'ки ' => array('change' => 116, 'baserank' => 26, 'refrank' => 142),
            'рус' => array('change' => 300, 'baserank' => 35, 'refrank' => null),
            'тел' => array('change' => 16, 'baserank' => 39, 'refrank' => 23),
            'нае' => array('change' => 300, 'baserank' => 29, 'refrank' => null),
            'й п' => array('change' => 300, 'baserank' => 25, 'refrank' => null),
            'наш' => array('change' => 300, 'baserank' => 30, 'refrank' => null),
            'уга' => array('change' => 300, 'baserank' => 41, 'refrank' => null),
            'ове' => array('change' => 214, 'baserank' => 31, 'refrank' => 245),
            'ски' => array('change' => 112, 'baserank' => 36, 'refrank' => 148),
            'вер' => array('change' => 31, 'baserank' => 14, 'refrank' => 45),
            'аш ' => array('change' => 300, 'baserank' => 12, 'refrank' => null),
            );

        $this->assertEquals(48, count($ranked));


        $russian = $this->xproxy->_lang_db['russian'];

        $sumchange = 0;
        foreach ($ranked as $key => $value) {
            if (isset($russian[$key])) {
                $difference = abs($russian[$key] - $ranked[$key]);
            } else {
                $difference = 300;
            }
            $this->assertTrue(isset($correct_ranks[$key], $key));
            if (isset($correct_ranks[$key])) {
                $this->assertEquals($correct_ranks[$key]['baserank'], $ranked[$key], "baserank for $key");
                if ($correct_ranks[$key]['refrank'] === null) {
                    $this->assertArrayNotHasKey($key, $russian);
                } else {
                    $this->assertEquals($correct_ranks[$key]['refrank'], $russian[$key], "refrank for $key");
                }
                $this->assertEquals($correct_ranks[$key]['change'], $difference, "difference for $key");
            }

            $sumchange += $difference;
        }

        $actual_result = $this->xproxy->_distance($russian, $ranked);
        $this->assertEquals($sumchange, $actual_result);
        $this->assertEquals(10428, $actual_result);
        $this->assertEquals(217, floor($sumchange/count($trigrams)));

        $final_result = $this->x->detect($str);
        $this->assertEquals(217,floor($final_result['russian']));
    }

    function test_ranker ()
    {
        $str = 'is it s i';

        $result = $this->xproxy->_arr_rank($this->xproxy->_trigram($str));

        $this->assertEquals(0, $result['s i']);
    }


    function test_count ()
    {
        $langs = $this->x->getLanguages();
        
        $count = $this->x->getLanguageCount();

        $this->assertEquals(count($langs), $count);

        foreach ($langs as $lang) {
            $this->assertTrue($this->x->languageExists($lang), $lang);
        }
    }

    function testLanguageExistsNameMode2()
    {
        $this->x->setNameMode(2);
        $this->assertTrue($this->x->languageExists('en'));
        $this->assertFalse($this->x->languageExists('english'));
    }

    function testLanguageExistsArrayNameMode2()
    {
        $this->x->setNameMode(2);
        $this->assertTrue($this->x->languageExists(array('en', 'de')));
        $this->assertFalse($this->x->languageExists(array('en', 'doesnotexist')));
    }

    /**
     * @expectedException Text_LanguageDetect_Exception
     * @expectedExceptionMessage Unsupported parameter type passed to languageExists()
     */
    function testLanguageExistsUnsupportedType()
    {
        $this->x->languageExists(1.23);
    }

    function testGetLanguages()
    {
        $langs = $this->x->getLanguages();
        $this->assertContains('english', $langs);
        $this->assertContains('swedish', $langs);
    }

    function testGetLanguagesNameMode2()
    {
        $this->x->setNameMode(2);
        $langs = $this->x->getLanguages();
        $this->assertContains('en', $langs);
        $this->assertContains('sv', $langs);
    }

    function testDetect()
    {
        $scores = $this->x->detect('Das ist ein kleiner Text für euch alle');
        $this->assertInternalType('array', $scores);
        $this->assertGreaterThan(5, count($scores));

        list($key, $value) = each($scores);
        $this->assertEquals('german', $key, 'text is german');
    }

    function testDetectNameMode2()
    {
        $this->x->setNameMode(2);
        $scores = $this->x->detect('Das ist ein kleiner Text für euch alle');
        list($key, $value) = each($scores);
        $this->assertEquals('de', $key, 'text is german');
    }

    function testDetectNameMode2Limit()
    {
        $this->x->setNameMode(2);
        $scores = $this->x->detect('Das ist ein kleiner Text für euch alle', 1);
        list($key, $value) = each($scores);
        $this->assertEquals('de', $key, 'text is german');
    }

    function testDetectSimple()
    {
        $lang = $this->x->detectSimple('Das ist ein kleiner Text für euch alle');
        $this->assertInternalType('string', $lang);
        $this->assertEquals('german', $lang, 'text is german');
    }

    function testDetectSimpleNameMode2()
    {
        $this->x->setNameMode(2);
        $lang = $this->x->detectSimple('Das ist ein kleiner Text für euch alle');
        $this->assertInternalType('string', $lang);
        $this->assertEquals('de', $lang, 'text is german');
    }

    function testDetectSimpleNoLanguages()
    {
        $this->x->omitLanguages('english', true);
        $this->x->omitLanguages('english', false);
        $this->assertNull(
            $this->x->detectSimple('Das ist ein kleiner Text für euch alle')
        );
    }

    function testLanguageSimilarity()
    {
        $this->x->setPerlCompatible(true);
        $eng_dan = $this->x->languageSimilarity('english', 'danish');
        $nor_dan = $this->x->languageSimilarity('norwegian', 'danish');
        $swe_dan = $this->x->languageSimilarity('swedish', 'danish');

        // remember, lower means more similar
        $this->assertTrue($eng_dan > $nor_dan); // english is less similar to danish than norwegian is
        $this->assertTrue($eng_dan > $swe_dan); // english is less similar to danish than swedish is
        $this->assertTrue($nor_dan < $swe_dan); // norwegian is more similar to danish than swedish

        // test the range of the results
        $this->assertTrue($eng_dan <= 300, $eng_dan);
        $this->assertTrue($eng_dan >= 0, $eng_dan);

        // test it in perl compatible mode
        $this->x->setPerlCompatible(false);

        $eng_dan = $this->x->languageSimilarity('english', 'danish');
        $nor_dan = $this->x->languageSimilarity('norwegian', 'danish');
        $swe_dan = $this->x->languageSimilarity('swedish', 'danish');

        // now higher is more similar
        $this->assertTrue($eng_dan < $nor_dan);
        $this->assertTrue($eng_dan < $swe_dan);
        $this->assertTrue($nor_dan > $swe_dan);

        $this->assertTrue($eng_dan <= 1, $eng_dan);
        $this->assertTrue($eng_dan >= 0, $eng_dan);

        $this->x->setPerlCompatible(true);

        $eng_all = $this->x->languageSimilarity('english');
        $this->assertEquals($this->x->getLanguageCount() - 1, count($eng_all));
        $this->assertTrue(!isset($eng_all['english']));

        $this->assertTrue($eng_all['italian'] < $eng_all['turkish']);
        $this->assertTrue($eng_all['french'] < $eng_all['kyrgyz']);

        $all = $this->x->languageSimilarity();
        $this->assertTrue(!isset($all['english']['english']));
        $this->assertTrue($all['french']['spanish'] < $all['french']['mongolian']);
        $this->assertTrue($all['spanish']['latin'] < $all['hindi']['finnish']);
        $this->assertTrue($all['russian']['uzbek'] < $all['russian']['english']);
    }


    function testLanguageSimilarityNameMode2()
    {
        $this->x->setNameMode(2);
        $this->x->setPerlCompatible(true);
        $eng_dan = $this->x->languageSimilarity('en', 'dk');
        $nor_dan = $this->x->languageSimilarity('no', 'dk');

        // remember, lower means more similar
        $this->assertTrue($eng_dan > $nor_dan); // english is less similar to danish than norwegian is
    }

    function testLanguageSimilarityUnknownLanguage()
    {
        $this->assertNull($this->x->languageSimilarity('doesnotexist'));
    }

    function testLanguageSimilarityUnknownLanguage2()
    {
        $this->assertNull($this->x->languageSimilarity('english', 'doesnotexist'));
    }

    function test_compatibility ()
    {
        $str = "I am the very model of a modern major general.";


        $this->x->setPerlCompatible(false);
        $result = $this->x->detectConfidence($str);
    
        $this->assertTrue(!is_null($result));
        $this->assertTrue(is_array($result));
        extract($result);
        $this->assertEquals('english', $language);
        $this->assertTrue($similarity <= 1 && $similarity >= 0, $similarity);
        $this->assertTrue($confidence <= 1 && $confidence >= 0, $confidence);

        $this->x->setPerlCompatible(true);
        $result = $this->x->detectConfidence($str);
        extract($result, EXTR_OVERWRITE);
    
        $this->assertEquals('english', $language);

        // technically the lowest possible score is 0 but it's extremely unlikely to hit that
        $this->assertTrue($similarity <= 300 && $similarity >= 1, $similarity);
        $this->assertTrue($confidence <= 1 && $confidence >= 0, $confidence);

    }

    function testDetectConfidenceNoText()
    {
        $this->assertNull($this->x->detectConfidence(''));
    }

    function test_omit_error ()
    {
        $str = 'On January 29, 1737, Thomas Paine was born in Thetford, England. His father, a corseter, had grand visions for his son, but by the age of 12, Thomas had failed out of school. The young Paine began apprenticing for his father, but again, he failed.';

        $myobj = new Text_LanguageDetect;

        $result = $myobj->detectSimple($str);
        $this->assertEquals('english', $result);

        // omit all languages and you should get an error
        $myobj->omitLanguages($myobj->getLanguages());

        $result = $myobj->detectSimple($str);

        $this->assertNull($result, gettype($result));
    }

    function test_cyrillic ()
    {
        // tests whether the cyrillic lower-casing works

        $uppercased = 'А    Б    В    Г    Д    Е    Ж    З    И    Й    К    Л    М    Н    О    П'
                     . 'Р    С    Т    У    Ф    Х    Ц    Ч    Ш    Щ    Ъ    Ы    Ь    Э    Ю    Я';

        $lowercased = 'а    б    в    г    д    е    ж    з    и    й    к    л    м    н    о    п'
                    . 'р    с    т    у    ф    х    ц    ч    ш    щ    ъ    ы    ь    э    ю    я';

        $this->assertEquals(strlen($uppercased), strlen($lowercased));

        $i = 0;
        $j = 0;
        $new_u = '';
        $rm = new ReflectionMethod('Text_LanguageDetect', '_next_char');
        $rm->setAccessible(true);
        while ($i < strlen($uppercased)) {
            $u = $rm->invokeArgs($this->x, [$uppercased, &$i, true]);
            $l = $rm->invokeArgs($this->x, [$lowercased, &$j, true]);
            $this->assertEquals($u, $l);

            $new_u .= $u;
        }

        $this->assertEquals($i, $j);
        $this->assertEquals($i, strlen($lowercased));
        if (function_exists('mb_strtolower')) {
            $this->assertEquals($new_u, mb_strtolower($uppercased, 'UTF-8'));
        }
    }

    function test_block_detection()
    {
        $exp_output = <<<EOF
Array
(
    [Basic Latin] => 37
    [CJK Unified Ideographs] => 2
    [Hiragana] => 1
    [Latin-1 Supplement] => 4
)
EOF;
        $teststr = 'lsdkfj あ 葉  叶 slskdfj s Åj;sdklf ÿjs;kdjåf î';
        $result = $this->x->detectUnicodeBlocks($teststr, false);

        ksort($result);
        ob_start();
        print_r($result);
        $str_result = ob_get_contents();
        ob_end_clean();
        $this->assertEquals(trim($exp_output), trim($str_result));

        // test whether skipping the spaces reduces the basic latin count
        $result2 = $this->x->detectUnicodeBlocks($teststr, true);
        $this->assertTrue($result2['Basic Latin'] < $result['Basic Latin']);

        $result3 = $this->x->unicodeBlockName('и');
        $this->assertEquals('Cyrillic', $result3);

        $this->assertEquals('Basic Latin', $this->x->unicodeBlockName('A'));

        // see what happens when you try an unassigned range
        $utf8 = $this->code2utf(0x0800);

        $this->assertEquals(false, $this->x->unicodeBlockName($utf8));

        // try unicode vals in several different ranges
        $unicode['Supplementary Private Use Area-A'] = 0xF0001;
        $unicode['Supplementary Private Use Area-B'] = 0x100001;
        $unicode['CJK Unified Ideographs Extension B'] = 0x20001;
        $unicode['Ugaritic'] = 0x10381;
        $unicode['Gothic'] = 0x10331;
        $unicode['Low Surrogates'] = 0xDC01;
        $unicode['CJK Unified Ideographs'] = 0x4E00;
        $unicode['Glagolitic'] = 0x2C00;
        $unicode['Latin Extended Additional'] = 0x1EFF;
        $unicode['Devanagari'] = 0x0900;
        $unicode['Hebrew'] = 0x0590;
        $unicode['Latin Extended-B'] = 0x024F;
        $unicode['Latin-1 Supplement'] = 0x00FF;
        $unicode['Basic Latin'] = 0x007F;

        foreach ($unicode as $range => $codepoint) {
            $result = $this->x->unicodeBlockName($this->code2utf($codepoint));
            $this->assertEquals($range, $result, $codepoint);
        }
    }

    /**
     * @expectedException Text_LanguageDetect_Exception
     * @expectedExceptionMessage Pass a single char only to this method
     */
    function testUnicodeBlockNameParamString()
    {
        $this->x->unicodeBlockName('foo bar baz');
    }

    /**
     * @expectedException Text_LanguageDetect_Exception
     * @expectedExceptionMessage Input must be of type string or int
     */
    function testUnicodeBlockNameUnsupportedParamType()
    {
        $this->x->unicodeBlockName(1.23);
    }


    // utility function
    // found in http://www.php.net/manual/en/function.utf8-encode.php#49336
    function code2utf($num)
    {
        if ($num < 128) {
           return chr($num);

        } elseif ($num < 2048) {
           return chr(($num >> 6) + 192) . chr(($num & 63) + 128);

        } elseif ($num < 65536) {
           return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);

        } elseif ($num < 2097152) {
            return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
        } else {
            return '';
        }
    }

    function test_utf8len()
    {
        $str = 'Iñtërnâtiônàlizætiøn';
        $this->assertEquals(20, $this->x->utf8strlen($str), utf8_decode($str));

        $str = '時期日';
        $this->assertEquals(3, $this->x->utf8strlen($str), utf8_decode($str));
    }

    function test_unicode()
    {
        // test whether it can get the right unicode values for utf8 chars

        $chars['ת'] = 0x5EA;

        $chars['ç'] = 0x00E7;

        $chars['a'] = 0x0061;

        $chars['Φ'] = 0x03A6;

        $chars['И'] = 0x0418;

        $chars['ڰ'] = 0x6B0;

        $chars['Ụ'] = 0x1EE4;

        $chars['놔'] = 0xB194;

        $chars['遮'] = 0x906E;

        $chars['怀'] = 0x6000;

        $chars['ฤ'] = 0x0E24;

        $chars['Я'] = 0x042F;

        $chars['ü'] = 0x00FC;

        $chars['Đ'] = 0x0110;

        $chars['א'] = 0x05D0;
        

        foreach ($chars as $utf8 => $unicode) {
            $this->assertEquals($unicode, $this->xproxy->_utf8char2unicode($utf8), $utf8);
        }
    }

    function test_unicode_off()
    {

        // see what happens when you turn the unicode setting off

        $myobj = new Text_LanguageDetect;

        $str = 'This is a delightful sample of English text';

        $myobj->useUnicodeBlocks(true);
        $result1 = $myobj->detectConfidence($str);

        $myobj->useUnicodeBlocks(false);
        $result2 = $myobj->detectConfidence($str);

        $this->assertEquals($result1, $result2);
        
        // note this test doesn't tell if unicode narrowing was actually used or not
    }


    function test_detection()
    {

        // WARNING: the below lines may make your terminal go ape! be warned























        // test strings from the test module used by perl's Language::Guess

        $testarr = array(
            "english" => "This is a test of the language checker",
            "french" => "Verifions que le détecteur de langues marche",
            "polish" => "Sprawdźmy, czy odgadywacz języków pracuje",
            "russian" => "Давай проверим узнает ли нашь угадыватель русский язык",
            "spanish" => "La respuesta de los acreedores a la oferta argentina para salir del default no ha sido muy positiv",
            "romanian" => "în acest sens aparţinînd Adunării Generale a organizaţiei, în ciuda faptului că mai multe dintre solicitările organizaţiei privind organizarea scrutinului nu au fost soluţionate",
            "albanian" => "kaluan ditën e fundit të fushatës në shtetet kryesore për të siguruar sa më shumë votues.",
            "danish" => "På denne side bringer vi billeder fra de mange forskellige forberedelser til arrangementet, efterhånden som vi får dem ",
            "swedish" => "Vi säger att Frälsningen är en gåva till alla, fritt och för intet.  Men som vi nämnt så finns det två villkor som måste",
            "norwegian" => "Nominasjonskomiteen i Akershus KrF har skviset ut Einar Holstad fra stortingslisten. Ytre Enebakk-mannen har plass p Stortinget s lenge Valgerd Svarstad Haugland sitter i",
            "finnish" => "on julkishallinnon verkkopalveluiden yhteinen osoite. Kansalaisten arkielämää helpottavaa tietoa on koottu eri aihealueisiin",
            "estonian" => "Ennetamaks reisil ebameeldivaid vahejuhtumeid vii end kurssi reisidokumentide ja viisade reeglitega ning muu praktilise informatsiooniga",
            "hungarian" => "Hiába jön létre az önkéntes magyar haderő, hiába nem lesz többé bevonulás, változatlanul fennmarad a hadkötelezettség intézménye",
            "uzbek" => "милиция ва уч солиқ идораси ходимлари яраланган. Шаҳарда хавфсизлик чоралари кучайтирилган.",


            "czech" => "Francouzský ministr financí zmírnil výhrady vůči nízkým firemním daním v nových členských státech EU",
            "dutch" => "Die kritiek was volgens hem bitter hard nodig, omdat Nederland binnen een paar jaar in een soort Belfast zou dreigen te nderen",

            "croatian" => "biće prilično izjednačena, sugerišu najnovije ankete. Oba kandidata tvrde da su sposobni da dobiju rat protiv terorizma",

            "romanian" => "în acest sens aparţinînd Adunării Generale a organizaţiei, în ciuda faptului că mai multe dintre solicitările organizaţiei ivind organizarea scrutinului nu au fost soluţionate",
            
            "turkish" => "yakın tarihin en çekişmeli başkanlık seçiminde oy verme işlemi sürerken, katılımda rekor bekleniyor.",

            "kyrgyz" => "көрбөгөндөй элдик толкундоо болуп, Кокон шаарынын көчөлөрүндө бир нече миң киши нааразылык билдирди.",


            "albanian" => "kaluan ditën e fundit të fushatës në shtetet kryesore për të siguruar sa më shumë votues.",


             "azeri" => "Daxil olan xəbərlərdə deyilir ki, 6 nəfər Bağdadın mərkəzində yerləşən Təhsil Nazirliyinin binası yaxınlığında baş vermiş partlayış zamanı həlak olub.",


             "macedonian" => "на јавното мислење покажуваат дека трката е толку тесна, што се очекува двајцата соперници да ја прекршат традицијата и да се појават и на самиот изборен ден.",
            


             "kazakh" => "Сайлау нәтижесінде дауыстардың басым бөлігін ел премьер министрі Виктор Янукович пен оның қарсыласы, оппозиция жетекшісі Виктор Ющенко алды.",


             "bulgarian" => " е готов да даде гаранции, че няма да прави ядрено оръжие, ако му се разреши мирна атомна програма",


             "arabic" => " ملايين الناخبين الأمريكيين يدلون بأصواتهم وسط إقبال قياسي على انتخابات هي الأشد تنافسا منذ عقود",

        );

























        // should be safe at this point


        $languages = $this->x->getLanguages();
        foreach (array_keys($testarr) as $key) {
            $this->assertTrue(in_array($key, $languages), "$key was not in known languages");
        }

        foreach ($testarr as $key=>$value) {
            $this->assertEquals($key, $this->x->detectSimple($value));
        }
    }


    public function test_convertFromNameMode0()
    {
        $this->assertEquals(
            'english',
            $this->xproxy->_convertFromNameMode('english')
        );
    }

    public function test_convertFromNameMode2String()
    {
        $this->x->setNameMode(2);
        $this->assertEquals(
            'english',
            $this->xproxy->_convertFromNameMode('en')
        );
    }

    public function test_convertFromNameMode3String()
    {
        $this->x->setNameMode(3);
        $this->assertEquals(
            'english',
            $this->xproxy->_convertFromNameMode('eng')
        );
    }

    public function test_convertFromNameMode2ArrayVal()
    {
        $this->x->setNameMode(2);
        $this->assertEquals(
            array('english', 'german'),
            $this->xproxy->_convertFromNameMode(array('en', 'de'))
        );
    }

    public function test_convertFromNameMode2ArrayKey()
    {
        $this->x->setNameMode(2);
        $this->assertEquals(
            array('english' => 'foo', 'german' => 'test'),
            $this->xproxy->_convertFromNameMode(
                array('en' => 'foo', 'de' => 'test'),
                true
            )
        );
    }

    public function test_convertFromNameMode3ArrayVal()
    {
        $this->x->setNameMode(3);
        $this->assertEquals(
            array('english', 'german'),
            $this->xproxy->_convertFromNameMode(array('eng', 'deu'))
        );
    }

    public function test_convertFromNameMode3ArrayKey()
    {
        $this->x->setNameMode(3);
        $this->assertEquals(
            array('english' => 'foo', 'german' => 'test'),
            $this->xproxy->_convertFromNameMode(
                array('eng' => 'foo', 'deu' => 'test'),
                true
            )
        );
    }

    public function test_convertToNameMode0()
    {
        $this->assertEquals(
            'english',
            $this->xproxy->_convertToNameMode('english')
        );
    }

    public function test_convertToNameMode2String()
    {
        $this->x->setNameMode(2);
        $this->assertEquals(
            'en',
            $this->xproxy->_convertToNameMode('english')
        );
    }

    public function test_convertToNameMode3String()
    {
        $this->x->setNameMode(3);
        $this->assertEquals(
            'eng',
            $this->xproxy->_convertToNameMode('english')
        );
    }

    public function test_convertToNameMode2ArrayVal()
    {
        $this->x->setNameMode(2);
        $this->assertEquals(
            array('en', 'de'),
            $this->xproxy->_convertToNameMode(array('english', 'german'))
        );
    }

    public function test_convertToNameMode2ArrayKey()
    {
        $this->x->setNameMode(2);
        $this->assertEquals(
            array('en' => 'foo', 'de' => 'test'),
            $this->xproxy->_convertToNameMode(
                array('english' => 'foo', 'german' => 'test'),
                true
            )
        );
    }

    public function test_convertToNameMode3ArrayVal()
    {
        $this->x->setNameMode(3);
        $this->assertEquals(
            array('eng', 'deu'),
            $this->xproxy->_convertToNameMode(array('english', 'german'))
        );
    }

    public function test_convertToNameMode3ArrayKey()
    {
        $this->x->setNameMode(3);
        $this->assertEquals(
            array('eng' => 'foo', 'deu' => 'test'),
            $this->xproxy->_convertToNameMode(
                array('english' => 'foo', 'german' => 'test'),
                true
            )
        );
    }
}
