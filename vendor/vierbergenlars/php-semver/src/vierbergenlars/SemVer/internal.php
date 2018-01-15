<?php

namespace vierbergenlars\SemVer\Internal;
use vierbergenlars\LibJs\JSArray;
use vierbergenlars\LibJs\JString;
use vierbergenlars\LibJs\RegExp;
use vierbergenlars\LibJs\JObject;
use vierbergenlars\LibJs\Util;

class G
{
    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array(__NAMESPACE__.'\\'.$name, $arguments);
    }
}

class Exports
{
    static public $re = array();
    static public $src = array();
    const SEMVER_SPEC_VERSION = '2.0.0';
}

$R = 0;

// The following Regular Expressions can be used for tokenizing,
// validating, and parsing SemVer version strings.

// ## Numeric Identifier
// A single `0`, or a non-zero digit followed by zero or more digits.

define(__NAMESPACE__.'\\NUMERICIDENTIFIER', $R++);
Exports::$src[NUMERICIDENTIFIER] = '0|[1-9]\\d*';
define(__NAMESPACE__.'\\NUMERICIDENTIFIERLOOSE', $R++);
Exports::$src[NUMERICIDENTIFIERLOOSE] = '[0-9]+';


// ## Non-numeric Identifier
// Zero or more digits, followed by a letter or hyphen, and then zero or
// more letters, digits, or hyphens.

define(__NAMESPACE__.'\\NONNUMERICIDENTIFIER', $R++);
Exports::$src[NONNUMERICIDENTIFIER] = '\\d*[a-zA-Z-][a-zA-Z0-9-]*';


// ## Main Version
// Three dot-separated numeric identifiers.

define(__NAMESPACE__.'\\MAINVERSION', $R++);
Exports::$src[MAINVERSION] = '(' . Exports::$src[NUMERICIDENTIFIER] . ')\\.' .
                   '(' . Exports::$src[NUMERICIDENTIFIER] . ')\\.' .
                   '(' . Exports::$src[NUMERICIDENTIFIER] . ')';

define(__NAMESPACE__.'\\MAINVERSIONLOOSE', $R++);
Exports::$src[MAINVERSIONLOOSE] = '(' . Exports::$src[NUMERICIDENTIFIERLOOSE] . ')\\.' .
                        '(' . Exports::$src[NUMERICIDENTIFIERLOOSE] . ')\\.' .
                        '(' . Exports::$src[NUMERICIDENTIFIERLOOSE] . ')';

// ## Pre-release Version Identifier
// A numeric identifier, or a non-numeric identifier.

define(__NAMESPACE__.'\\PRERELEASEIDENTIFIER', $R++);
Exports::$src[PRERELEASEIDENTIFIER] = '(?:' . Exports::$src[NUMERICIDENTIFIER] .
                            '|' . Exports::$src[NONNUMERICIDENTIFIER] . ')';

define(__NAMESPACE__.'\\PRERELEASEIDENTIFIERLOOSE', $R++);
Exports::$src[PRERELEASEIDENTIFIERLOOSE] = '(?:' . Exports::$src[NUMERICIDENTIFIERLOOSE] .
                                 '|' . Exports::$src[NONNUMERICIDENTIFIER] . ')';


// ## Pre-release Version
// Hyphen, followed by one or more dot-separated pre-release version
// identifiers.

define(__NAMESPACE__.'\\PRERELEASE', $R++);
Exports::$src[PRERELEASE] = '(?:-(' . Exports::$src[PRERELEASEIDENTIFIER] .
                  '(?:\\.' . Exports::$src[PRERELEASEIDENTIFIER] . ')*))';

define(__NAMESPACE__.'\\PRERELEASELOOSE', $R++);
Exports::$src[PRERELEASELOOSE] = '(?:-?(' . Exports::$src[PRERELEASEIDENTIFIERLOOSE] .
                       '(?:\\.' . Exports::$src[PRERELEASEIDENTIFIERLOOSE] . ')*))';

// ## Build Metadata Identifier
// Any combination of digits, letters, or hyphens.

define(__NAMESPACE__.'\\BUILDIDENTIFIER', $R++);
Exports::$src[BUILDIDENTIFIER] = '[0-9A-Za-z-]+';

// ## Build Metadata
// Plus sign, followed by one or more period-separated build metadata
// identifiers.

define(__NAMESPACE__.'\\BUILD', $R++);
Exports::$src[BUILD] = '(?:\\+(' . Exports::$src[BUILDIDENTIFIER] .
             '(?:\\.' . Exports::$src[BUILDIDENTIFIER] . ')*))';


// ## Full Version String
// A main version, followed optionally by a pre-release version and
// build metadata.

// Note that the only major, minor, patch, and pre-release sections of
// the version string are capturing groups.  The build metadata is not a
// capturing group, because it should not ever be used in version
// comparison.

define(__NAMESPACE__.'\\FULL', $R++);
define(__NAMESPACE__.'\\FULLPLAIN','v?' . Exports::$src[MAINVERSION] .
                Exports::$src[PRERELEASE] . '?' .
                Exports::$src[BUILD] . '?');

Exports::$src[FULL] = '^' . FULLPLAIN . '$';

// like full, but allows v1.2.3 and =1.2.3, which people do sometimes.
// also, 1.0.0alpha1 (prerelease without the hyphen) which is pretty
// common in the npm registry.
define(__NAMESPACE__.'\\LOOSEPLAIN', '[v=\\s]*' . Exports::$src[MAINVERSIONLOOSE] .
                 Exports::$src[PRERELEASELOOSE] . '?' .
                 Exports::$src[BUILD] . '?');

define(__NAMESPACE__.'\\LOOSE', $R++);
Exports::$src[LOOSE] = '^' . LOOSEPLAIN . '$';

define(__NAMESPACE__.'\\GTLT', $R++);
Exports::$src[GTLT] = '((?:<|>)?=?)';

// Something like "2.*" or "1.2.x".
// Note that "x.x" is a valid xRange identifer, meaning "any version"
// Only the first item is strictly required.
define(__NAMESPACE__.'\\XRANGEIDENTIFIERLOOSE', $R++);
Exports::$src[XRANGEIDENTIFIERLOOSE] = Exports::$src[NUMERICIDENTIFIERLOOSE] . '|x|X|\\*';
define(__NAMESPACE__.'\\XRANGEIDENTIFIER', $R++);
Exports::$src[XRANGEIDENTIFIER] = Exports::$src[NUMERICIDENTIFIER] . '|x|X|\\*';

define(__NAMESPACE__.'\\XRANGEPLAIN', $R++);
Exports::$src[XRANGEPLAIN] = '[v=\\s]*(' . Exports::$src[XRANGEIDENTIFIER] . ')' .
                   '(?:\\.(' . Exports::$src[XRANGEIDENTIFIER] . ')' .
                   '(?:\\.(' . Exports::$src[XRANGEIDENTIFIER] . ')' .
                   '(?:(' . Exports::$src[PRERELEASE] . ')' .
                   ')?)?)?';

define(__NAMESPACE__.'\\XRANGEPLAINLOOSE', $R++);
Exports::$src[XRANGEPLAINLOOSE] = '[v=\\s]*(' . Exports::$src[XRANGEIDENTIFIERLOOSE] . ')' .
                        '(?:\\.(' . Exports::$src[XRANGEIDENTIFIERLOOSE] . ')' .
                        '(?:\\.(' . Exports::$src[XRANGEIDENTIFIERLOOSE] . ')' .
                        '(?:(' . Exports::$src[PRERELEASELOOSE] . ')' .
                        ')?)?)?';

// >=2.x, for example, means >=2.0.0-0
// <1.x would be the same as "<1.0.0-0", though.
define(__NAMESPACE__.'\\XRANGE', $R++);
Exports::$src[XRANGE] = '^' . Exports::$src[GTLT] . '\\s*' . Exports::$src[XRANGEPLAIN] . '$';
define(__NAMESPACE__.'\\XRANGELOOSE', $R++);
Exports::$src[XRANGELOOSE] = '^' . Exports::$src[GTLT] . '\\s*' . Exports::$src[XRANGEPLAINLOOSE] . '$';

// Tilde ranges.
// Meaning is "reasonably at or greater than"
define(__NAMESPACE__.'\\LONETILDE', $R++);
Exports::$src[LONETILDE] = '(?:~>?)';

define(__NAMESPACE__.'\\TILDETRIM', $R++);
Exports::$src[TILDETRIM] = '(\\s*)' . Exports::$src[LONETILDE] . '\\s+';
Exports::$re[TILDETRIM] = new RegExp(Exports::$src[TILDETRIM], 'g');
define(__NAMESPACE__.'\\tildeTrimReplace', '$1~');

define(__NAMESPACE__.'\\TILDE', $R++);
Exports::$src[TILDE] = '^' . Exports::$src[LONETILDE] . Exports::$src[XRANGEPLAIN] . '$';
define(__NAMESPACE__.'\\TILDELOOSE', $R++);
Exports::$src[TILDELOOSE] = '^' . Exports::$src[LONETILDE] . Exports::$src[XRANGEPLAINLOOSE] . '$';

// Caret ranges.
// Meaning is "at least and backwards compatible with"
define(__NAMESPACE__.'\\LONECARET', $R++);
Exports::$src[LONECARET] = '(?:\\^)';

define(__NAMESPACE__.'\\CARETTRIM', $R++);
Exports::$src[CARETTRIM] = '(\\s*)' . Exports::$src[LONECARET] . '\\s+';
Exports::$re[CARETTRIM] = new RegExp(Exports::$src[CARETTRIM], 'g');
define(__NAMESPACE__.'\\caretTrimReplace', '$1^');

define(__NAMESPACE__.'\\CARET', $R++);
Exports::$src[CARET] = '^' . Exports::$src[LONECARET] . Exports::$src[XRANGEPLAIN] . '$';
define(__NAMESPACE__.'\\CARETLOOSE', $R++);
Exports::$src[CARETLOOSE] = '^' . Exports::$src[LONECARET] . Exports::$src[XRANGEPLAINLOOSE] . '$';

// A simple gt/lt/eq thing, or just "" to indicate "any version"
define(__NAMESPACE__.'\\COMPARATORLOOSE', $R++);
Exports::$src[COMPARATORLOOSE] = '^' . Exports::$src[GTLT] . '\\s*(' . LOOSEPLAIN . ')$|^$';
define(__NAMESPACE__.'\\COMPARATOR', $R++);
Exports::$src[COMPARATOR] = '^' . Exports::$src[GTLT] . '\\s*(' . FULLPLAIN . ')$|^$';


// An expression to strip any whitespace between the gtlt and the thing
// it modifies, so that `> 1.2.3` ==> `>1.2.3`
define(__NAMESPACE__.'\\COMPARATORTRIM', $R++);
Exports::$src[COMPARATORTRIM] = '(\\s*)' . Exports::$src[GTLT] .
                      '\\s*(' . LOOSEPLAIN . '|' . Exports::$src[XRANGEPLAIN] . ')';

// this one has to use the /g flag
Exports::$re[COMPARATORTRIM] = new RegExp(Exports::$src[COMPARATORTRIM], 'g');
define(__NAMESPACE__.'\\comparatorTrimReplace', '$1$2$3');


// Something like `1.2.3 - 1.2.4`
// Note that these all use the loose form, because they'll be
// checked against either the strict or loose comparator form
// later.
define(__NAMESPACE__.'\\HYPHENRANGE', $R++);
Exports::$src[HYPHENRANGE] = '^\\s*(' . Exports::$src[XRANGEPLAIN] . ')' .
                   '\\s+-\\s+' .
                   '(' . Exports::$src[XRANGEPLAIN] . ')' .
                   '\\s*$';

define(__NAMESPACE__.'\\HYPHENRANGELOOSE', $R++);
Exports::$src[HYPHENRANGELOOSE] = '^\\s*(' . Exports::$src[XRANGEPLAINLOOSE] . ')' .
                        '\\s+-\\s+' .
                        '(' . Exports::$src[XRANGEPLAINLOOSE] . ')' .
                        '\\s*$';

// Star ranges basically just allow anything at all.
define(__NAMESPACE__.'\\STAR', $R++);
Exports::$src[STAR] = '(<|>)?=?\\s*\\*';

for($i = 0; $i < $R; $i++) {
    if(!isset(Exports::$re[$i]))
        Exports::$re[$i] = new RegExp(Exports::$src[$i]);
}

/**
 *
 * @param JString $version
 * @param bool $loose
 * @return SemVer|null
 */
function parse(JString $version, $loose = false)
{
    $r = $loose?Exports::$re[LOOSE]:Exports::$re[FULL];
    /* @var $r \vierbergenlars\LibJs\RexExp */
    return ($r->test($version))? new SemVer($version, $loose):null;
}

/**
 * @param JString $version
 * @param bool $loose
 * @return JString|null
 */
function valid(JString $version, $loose = false)
{
    $v = parse($version, $loose);
    return $v?$v->version:null;
}

/**
 * @param JString $version
 * @param bool $loose
 * @return JString|null
 */
function clean(JString $version, $loose = false)
{
    $s = parse($version, $loose);
    return $s?$s->version:null;
}

class SemVer extends JObject
{
    /**
     * @var JString
     */
    public $version;

    /**
     * @var bool
     */
    public $loose;

    /**
     * @var JString
     */
    public $raw;

    /**
     *
     * @var int
     */
    public $major;

    /**
     *
     * @var int
     */
    public $minor;

    /**
     *
     * @var int
     */
    public $patch;

    /**
     *
     * @var JSArray
     */
    public $prerelease;

    /**
     *
     * @var JSArray
     */
    public $build;

    /**
     * @param SemVer|JString|string $version
     * @param bool $loose
     */
    public function __construct($version, $loose = false)
    {
        $this->loose = $loose;
        if($version instanceof SemVer) {
            $this->version = $version->version;
            return;
        }

        $version = new JString($version);

        $m = $version->trim()->match($loose?Exports::$re[LOOSE]:Exports::$re[FULL]);

        if(!$m)
            throw new \RuntimeException('Invalid Version: '.$version);

        $this->raw = $version;

        // these are actually numbers
        $this->major = (int)$m[1]->valueOf();
        $this->minor = (int)$m[2]->valueOf();
        $this->patch = (int)$m[3]->valueOf();

        // numberify any prerelease numeric ids
        if (!$m[4])
            $this->prerelease = new JSArray();
        else
            $this->prerelease = $m[4]->split('.')->map(function($id) {
                return (is_numeric($id->valueOf())) ? (int)$id->valueOf() : $id;
            });

        $this->build = $m[5]?$m[5]->split('.') : new JSArray();

        $this->format();
    }

    public function format()
    {
       $this->version = new JString($this->major . '.' . $this->minor . '.' . $this->patch);
        if ($this->prerelease->length)
            $this->version = $this->version->concat('-', $this->prerelease->join('.'));
        return $this->version;
    }

    public function inspect()
    {
        return '<SemVer "'.$this.'">';
    }

    public function __toString()
    {
        return $this->version->valueOf();
    }

    public function compare($other)
    {
        if(!$other instanceof SemVer)
            $other = new SemVer($other, $this->loose);
        return Util::JSor($this->compareMain($other),$this->comparePre($other));
    }

    public function compareMain($other)
    {
        if(!$other instanceof SemVer)
            $other = new SemVer($other, $this->loose);
        return Util::JSor(compareIdentifiers($this->major, $other->major),
                compareIdentifiers($this->minor, $other->minor),
                compareIdentifiers($this->patch, $other->patch));
    }

    public function comparePre($other)
    {
        if(!$other instanceof SemVer)
            $other = new SemVer($other, $this->loose);

        // NOT having a prerelease is > having one
        if($this->prerelease->length && !$other->prerelease->length)
            return -1;
        else if(!$this->prerelease->length && $other->prerelease->length)
            return 1;
        else if(!$this->prerelease->length && !$other->prerelease->length)
            return 0;

        $i = 0;
        do {
            $a = $this->prerelease[$i];
            if($a instanceof JString) $a = $a->valueOf();
            $b = $other->prerelease[$i];
            if($b instanceof JString) $b = $b->valueOf();

            if ($a === null && $b === null)
              return 0;
            else if ($b === null)
              return 1;
            else if ($a === null)
              return -1;
            else if ($a === $b)
              continue;
            else
              return compareIdentifiers($a, $b);
        } while (++$i);
    }

    /**
     * @param string $release
     * @return SemVer
     */
    public function inc($release)
    {
        switch ($release) {
            case 'major':
                $this->major++;
                $this->minor = -1;
            case 'minor':
                $this->minor++;
                $this->patch = -1;
            case 'patch':
                $this->patch++;
                $this->prerelease = new JSArray();
                break;
            case 'prerelease':
                if ($this->prerelease->length === 0)
                      $this->prerelease = new JSArray(array(0));
                else {
                    $i = $this->prerelease->length;
                    while (--$i >= 0) {
                        if (is_numeric($this->prerelease[$i])) {
                            $this->prerelease[$i] = $this->prerelease[$i] + 1;
                            $i = -2;
                        }
                    }
                    if ($i === -1) // didn't increment anything
                        $this->prerelease->push(0);
                }
                break;

            default:
                throw new \LogicException('invalid increment argument: ' .$release);
        }
        $this->format();
        return $this;
    }
}

function inc($version, $release, $loose = false)
{
    try {
        $s = new SemVer($version, $loose);
        return (string)$s->inc($release)->version;
    } catch(\Exception $e) {
        return null;
    }
}

function compareIdentifiers($a, $b)
{
    $anum = is_numeric($a);
    $bnum = is_numeric($b);

    if($anum && $bnum) {
        $a = (int)$a;
        $b = (int)$b;
    }

    if($anum && !$bnum)
        return -1;
    elseif($bnum && !$anum)
        return 1;
    elseif($a < $b)
        return -1;
    elseif($a > $b)
        return 1;
    else
        return 0;
}

function rcompareIdentifiers($a, $b)
{
    return compareIdentifiers($b, $a);
}

function compare($a, $b, $loose = false)
{
    $s = new SemVer($a, $loose);
    return $s->compare($b);
}

function compareLoose($a, $b)
{
    return compare($a, $b, true);
}

function rcompare($a, $b, $loose = false)
{
    return compare($b, $a, $loose);
}

function semver_sort(JSArray $list, $loose = false)
{
    return $list->sort(function ($a, $b) use($loose) {
        return compare($a, $b, $loose);
    });
}

function semver_rsort(JSArray $list, $loose = false)
{
    return $list->sort(function($a, $b)use($loose) {
        return rcompare($a, $b, $loose);
    });
}

function gt($a, $b, $loose = false)
{
    return compare($a, $b, $loose) > 0;
}

function lt($a, $b, $loose = false)
{
    return compare($a, $b, $loose) < 0;
}

function eq($a, $b, $loose = false)
{
    return compare($a, $b, $loose) === 0;
}

function neq($a, $b, $loose = false)
{
    return compare($a, $b, $loose) !== 0;
}

function gte($a, $b, $loose = false)
{
    return compare($a, $b, $loose) >= 0;
}

function lte($a, $b, $loose = false)
{
    return compare($a, $b, $loose) <= 0;
}

function cmp($a, $op, $b, $loose = false)
{
    if($op instanceof JString)
        $op = $op->valueOf();
    switch ($op) {
        case '===': $ret = $a === $b; break;
        case '!==': $ret = $a !== $b; break;
        case '': case '=': case '==': $ret = eq($a, $b, $loose); break;
        case '!=': $ret = neq($a, $b, $loose); break;
        case '>': $ret = gt($a, $b, $loose); break;
        case '>=': $ret = gte($a, $b, $loose); break;
        case '<': $ret = lt($a, $b, $loose); break;
        case '<=': $ret = lte($a, $b, $loose); break;
        default: throw new \LogicException('Invalid operator: ' . $op);
    }
    return $ret;
}

class Comparator extends JObject
{
    /**
     * @var JString
     */
    public $value;

    /**
     * @var bool
     */
    public $loose;

    /**
     * @var SemVer|null
     */
    public $semver;

    /**
     * @var JString
     */
    public $operator;

    public function __construct($comp, $loose = false)
    {
        $this->loose = $loose;
        if($comp instanceof Comparator)
            $comp = $comp->value;

        $comp = new JString($comp);

        $this->parse($comp);

        if(!$this->semver instanceof SemVer)
            $this->value = new JString('');
        else
            $this->value = new JString($this->operator.$this->semver->version);
    }

    public function parse(JString $comp)
    {
        $r = $this->loose?Exports::$re[COMPARATORLOOSE]:Exports::$re[COMPARATOR];
        $m = $comp->match($r);

        if(!$m)
            throw new \RuntimeException('Invalid comparator: '.$comp);

        $this->operator = $m[1];
        if(!$m[2])
            $this->semver = null;
        else {
            $this->semver = new SemVer($m[2], $this->loose);

            // <1.2.3-rc DOES allow 1.2.3-beta (has prerelease)
            // >=1.2.3 DOES NOT allow 1.2.3-beta
            // <=1.2.3 DOES allow 1.2.3-beta
            // However, <1.2.3 does NOT allow 1.2.3-beta,
            // even though `1.2.3-beta < 1.2.3`
            // The assumption is that the 1.2.3 version has something you
            // *don't* want, so we push the prerelease down to the minimum.
            if ($this->operator->valueOf() === '<' && !$this->semver->prerelease->length) {
                $this->semver->prerelease = new JSArray(array(0));
                $this->semver->format();
            }
        }
    }

    public function inspect()
    {
        return '<SemVer Comparator "'.$this.'">';
    }

    public function __toString()
    {
        return $this->value->valueOf();
    }

    public function test($version)
    {
        return ($this->semver == null) ? true:
                cmp($version, $this->operator, $this->semver, $this->loose);
    }
}

class Range extends JObject
{
    /**
     * @var bool
     */
    public $loose;

    /**
     * @var JString
     */
    public $raw;

    /**
     * @var JSArray
     */
    public $set;

    /**
     *
     * @var JString
     */
    public $range;

    public function __construct($range, $loose = false)
    {
        $this->loose = $loose;
        if($range instanceof Range)
            $range = $range->raw;

        $range = new JString($range);

        $this->raw = $range;

        $that = $this;
        // First, split based on boolean or ||
        $this->set = $range->split(new RegExp('\\s*\\|\\|\\s*'))->map(function($range)use($that) {
            return $that->parseRange($range->trim());
        })->filter(function($c) {
            return $c->length;
        });

        if(!$this->set->length) {
            throw new \RuntimeException('Invalid SemVer Range: ' . $range);
        }

        $this->format();
    }

    public function inspect()
    {
        return '<SemVer Range "'.$this->raw.'">';
    }

    public function format()
    {
        $this->range = $this->set->map(function($comps) {
            return $comps->join(' ')->trim();
        })->join('||')->trim();
        return $this->range;
    }

    public function __toString()
    {
        return $this->range->valueOf();
    }

    public function parseRange(JString $range)
    {
        $loose = $this->loose;
        $range = $range->trim();
        // `1.2.3 - 1.2.4` => `>=1.2.3 <=1.2.4`
        $hr = $loose?Exports::$re[HYPHENRANGELOOSE]:Exports::$re[HYPHENRANGE];
        $range = $range->replace($hr, function() {
            return call_user_func_array(__NAMESPACE__.'\\hyphenReplace', func_get_args());
        });

        // `> 1.2.3 < 1.2.5` => `>1.2.3 <1.2.5`
        $range = $range->replace(Exports::$re[COMPARATORTRIM], comparatorTrimReplace);

        // `~ 1.2.3` => `~1.2.3`
        $range = $range->replace(Exports::$re[TILDETRIM], tildeTrimReplace);

        // `^ 1.2.3` => `^1.2.3`
        $range = $range->replace(Exports::$re[CARETTRIM], caretTrimReplace);

        // normalize spaces
        $range = $range->split(new RegExp('\\s+'))->join(' ');

        // At this point, the range is completely trimmed and
        // ready to be split into comparators.

        $compRe = $loose ? Exports::$re[COMPARATORLOOSE] : Exports::$re[COMPARATOR];
        $set = $range->split(' ')->map(function($comp) use($loose) {
            return parseComparator($comp, $loose);
        })->join(' ')->split(new RegExp('\\s+'));

        if ($loose) {
            // in loose mode, throw out any that are not valid comparators
            $set = $set->filter(function($comp)use($compRe) {
                return !!$comp->match($compRe);
            });
        }
        $set = $set->map(function($comp) use($loose) {
            return new Comparator($comp, $loose);
        });


        return $set;
    }

    // Placed after hyphenReplace in JS source
    public function test($version)
    {
        if(!$version)
            return false;
        for($i = 0; $i < $this->set->length; $i++) {
            if(testSet($this->set[$i], $version))
                return true;
        }
        return false;
    }
}

function toComparators($range, $loose = false)
{
    $r = new Range($range, $loose);
    return $r->set->map(function($comp) {
        return $comp->map(function($c) {
            return $c->value;
        })->join(' ')->trim()->split(' ');
    });
}

// comprised of xranges, tildes, stars, and gtlt's at this point.
// already replaced the hyphen ranges
// turn into a set of JUST comparators.
function parseComparator(JString $comp, $loose = false)
{
    $comp = replaceCarets($comp, $loose);
    $comp = replaceTildes($comp, $loose);
    $comp = replaceXRanges($comp, $loose);
    $comp = replaceStars($comp, $loose);
    return $comp;
}

function isX(JString $id = null)
{
    return !$id || $id->toLowerCase()->valueOf() === 'x' || $id->valueOf() === '*';
}

// ~, ~> --> * (any, kinda silly)
// ~2, ~2.x, ~2.x.x, ~>2, ~>2.x ~>2.x.x --> >=2.0.0 <3.0.0
// ~2.0, ~2.0.x, ~>2.0, ~>2.0.x --> >=2.0.0 <2.1.0
// ~1.2, ~1.2.x, ~>1.2, ~>1.2.x --> >=1.2.0 <1.3.0
// ~1.2.3, ~>1.2.3 --> >=1.2.3 <1.3.0
// ~1.2.0, ~>1.2.0 --> >=1.2.0 <1.3.0
function replaceTildes(JString $comp, $loose = false) {
    return $comp->trim()->split(new RegExp('\\s+'))->map(function($comp)use($loose) {
        return replaceTilde($comp, $loose);
    })->join(' ');
}

function replaceTilde(JString $comp, $loose = false) {
  $r = $loose ? Exports::$re[TILDELOOSE] : Exports::$re[TILDE];
  return $comp->replace($r, function(JString $_, JString $M = null, JString $m = null, JString $p = null, JString $pr = null) {
    if (isX($M))
      $ret = '';
    else if (isX($m))
      $ret = '>=' . $M . '.0.0-0 <' . ((int)(string)$M + 1) . '.0.0-0';
    else if (isX($p))
      // ~1.2 == >=1.2.0- <1.3.0-
      $ret = '>=' . $M . '.' . $m . '.0-0 <' . $M . '.' . ((int)(string)$m + 1) . '.0-0';
    else if ($pr) {
      if ($pr->charAt(0) !== '-')
        $pr = '-' . $pr;
      $ret = '>=' . $M . '.' . $m . '.' . $p . $pr .
            ' <' . $M . '.' . ((int)(string)$m + 1) . '.0-0';
    } else
      // ~1.2.3 == >=1.2.3-0 <1.3.0-0
      $ret = '>=' . $M . '.' . $m . '.' . $p . '-0' .
            ' <' . $M . '.' . ((int)(string)$m + 1) . '.0-0';

    return $ret;
  });
}

// ^ --> * (any, kinda silly)
// ^2, ^2.x, ^2.x.x --> >=2.0.0 <3.0.0
// ^2.0, ^2.0.x --> >=2.0.0 <3.0.0
// ^1.2, ^1.2.x --> >=1.2.0 <2.0.0
// ^1.2.3 --> >=1.2.3 <2.0.0
// ^1.2.0 --> >=1.2.0 <2.0.0
function replaceCarets(JString $comp, $loose = false) {
  return $comp->trim()->split(new RegExp('\\s+'))->map(function($comp)use($loose) {
    return replaceCaret($comp, $loose);
  })->join(' ');
}

function replaceCaret(JString $comp, $loose = false) {
  $r = $loose ? Exports::$re[CARETLOOSE] : Exports::$re[CARET];
  return $comp->replace($r, function(JString $_, JString $M = null, JString $m = null, JString $p = null, JString $pr = null) {
    if (isX($M))
      $ret = '';
    else if (isX($m))
      $ret = '>=' . $M . '.0.0-0 <' . ((int)(string)$M + 1) . '.0.0-0';
    else if (isX($p)) {
      if ($M->valueOf() === '0')
        $ret = '>=' . $M . '.' . $m . '.0-0 <' . $M . '.' . ((int)(string)$m + 1) . '.0-0';
      else
        $ret = '>=' . $M . '.' . $m . '.0-0 <' . ((int)(string)$M + 1) . '.0.0-0';
    } else if ($pr) {
      if ($pr->charAt(0) !== '-')
        $pr = '-' . $pr;
      if ($M->valueOf() === '0') {
        if ($m->valueOf() === '0')
          $ret = '=' . $M . '.' . $m . '.' . $p . $pr;
        else
          $ret = '>=' . $M . '.' . $m . '.' . $p . $pr .
                ' <' . $M . '.' . ((int)(string)$m + 1) . '.0-0';
      } else
        $ret = '>=' . $M . '.' . $m . '.' . $p . $pr .
              ' <' . ((int)(string)$M + 1) . '.0.0-0';
    } else {
      if ($M->valueOf() === '0') {
        if ($m->valueOf() === '0')
          $ret = '=' . $M . '.' . $m . '.' . $p;
        else
          $ret = '>=' . $M . '.' . $m . '.' . $p . '-0' .
                ' <' . $M . '.' . ((int)(string)$m + 1) . '.0-0';
      } else
        $ret = '>=' . $M . '.' . $m . '.' . $p . '-0' .
              ' <' . ((int)(string)$M + 1) . '.0.0-0';
    }

    return $ret;
  });
}

function replaceXRanges(JString $comp, $loose = false) {
  return $comp->split(new RegExp('\\s+'))->map(function($comp)use($loose) {
    return replaceXRange($comp, $loose);
  })->join(' ');
}

function replaceXRange(JString $comp, $loose = false) {
  $comp = $comp->trim();
  $r = $loose ? Exports::$re[XRANGELOOSE] : Exports::$re[XRANGE];
  return $comp->replace($r, function(JString $ret, JString $gtlt, JString $M = null, JString $m = null, JString $p = null, JString $pr=null) {
    $xM = isX($M);
    $xm = $xM || isX($m);
    $xp = $xm || isX($p);
    $anyX = $xp;
    $gtlt = (string)$gtlt;

    if ($gtlt === '=' && $anyX)
      $gtlt = '';

    if ($gtlt && $anyX) {
      // replace X with 0, and then append the -0 min-prerelease
      if ($xM)
        $M = 0;
      if ($xm)
        $m = 0;
      if ($xp)
        $p = 0;

      if ($gtlt === '>') {
        // >1 => >=2.0.0-0
        // >1.2 => >=1.3.0-0
        // >1.2.3 => >= 1.2.4-0
        $gtlt = '>=';
        if ($xM) {
          // no change
        } else if ($xm) {
          $M = (int)(string)$M + 1;
          $m = 0;
          $p = 0;
        } else if ($xp) {
          $m = (int)(string)$m + 1;
          $p = 0;
        }
      }


      $ret = $gtlt . $M . '.' . $m . '.' . $p . '-0';
    } else if ($xM) {
      // allow any
      $ret = '*';
    } else if ($xm) {
      // append '-0' onto the version, otherwise
      // '1.x.x' matches '2.0.0-beta', since the tag
      // *lowers* the version value
      $ret = '>=' . $M . '.0.0-0 <' . ((int)(string)$M + 1) . '.0.0-0';
    } else if ($xp) {
      $ret = '>=' . $M . '.' . $m . '.0-0 <' . $M . '.' . ((int)(string)$m + 1) . '.0-0';
    }

    return $ret;
  });
}

// Because * is AND-ed with everything else in the comparator,
// and '' means "any version", just remove the *s entirely.
function replaceStars(JString $comp, $loose = false) {
  // Looseness is ignored here.  star is always as loose as it gets!
  return $comp->trim()->replace(Exports::$re[STAR], '');
}

// This function is passed to string.replace(re[HYPHENRANGE])
// M, m, patch, prerelease, build
// 1.2 - 3.4.5 => >=1.2.0-0 <=3.4.5
// 1.2.3 - 3.4 => >=1.2.0-0 <3.5.0-0 Any 3.4.x will do
// 1.2 - 3.4 => >=1.2.0-0 <3.5.0-0
function hyphenReplace(JString $_,
                       JString $from = null, JString $fM = null, JString $fm = null, JString $fp = null, JString $fpr = null, JString $fb = null,
                       JString $to = null, JString $tM = null, JString $tm = null, JString $tp = null, JString $tpr = null, JString $tb = null) {

  if (isX($fM))
    $from = '';
  else if (isX($fm))
    $from = '>=' . $fM . '.0.0-0';
  else if (isX($fp))
    $from = '>=' . $fM . '.' . $fm . '.0-0';
  else
    $from = '>=' . $from;

  if (isX($tM))
    $to = '';
  else if (isX($tm))
    $to = '<' . ((int)(string)$tM + 1) . '.0.0-0';
  else if (isX($tp))
    $to = '<' . $tM . '.' . ((int)(string)$tm + 1) . '.0-0';
  else if ($tpr)
    $to = '<=' . $tM . '.' . $tm . '.' . $tp . '-' . $tpr;
  else
    $to = '<=' . $to;

  $s = new JString($from.' '.$to);
  return $s->trim();
}

// Range.prototype.test is moved to the Range object in PHP

function testSet(JSArray $set, $version) {
  for ($i = 0; $i < $set->length; $i++) {
    if (!$set[$i]->test($version))
      return false;
  }
  return true;
}

function satisfies($version, $range, $loose = false)
{
    try {
        $range = new Range($range, $loose);
    } catch(\Exception $e) {
        return false;
    }
    return $range->test($version);
}

function maxSatisfying(JSArray $versions, $range, $loose = false)
{
  return $versions->filter(function($version)use($range, $loose) {
    return satisfies($version, $range, $loose);
  })->sort(function($a, $b)use($loose) {
    return rcompare($a, $b, $loose);
  })->offsetGet(0);
}

function validRange($range, $loose = false) {
  try {
    // Return '*' instead of '' so that truthiness works.
    // This will throw if it's invalid anyway
    $r = new Range($range, $loose);
    return (string)Util::JSor($r->range, new JString('*'));
  } catch (\Exception $e) {
    return null;
  }
}

// Determine if version is less than all the versions possible in the range
function ltr($version, $range, $loose = false)
{
    return outside($version, $range, '<', $loose);
}

// Determine if version is greater than all the versions possible in the range.
function gtr($version, $range, $loose = false)
{
    return outside($version, $range, '>', $loose);
}

function outside($version, $range, $hilo, $loose = false)
{
    $version = new SemVer($version, $loose);
    $range = new Range($range, $loose);

    switch($hilo) {
        case '>':
            $gtfn = __NAMESPACE__.'\\gt';
            $ltefn = __NAMESPACE__.'\\lte';
            $ltfn = __NAMESPACE__.'\\lt';
            $comp = '>';
            $ecomp = '>=';
            break;
        case '<':
            $gtfn = __NAMESPACE__.'\\lt';
            $ltefn = __NAMESPACE__.'\\gte';
            $ltfn = __NAMESPACE__.'\\gt';
            $comp = '<';
            $ecomp = '<=';
            break;
        default:
            throw new \LogicException('Must provide a hilo val of "<" or ">"');
      }

    // If it satisifes the range it is not outside
    if (satisfies($version, $range, $loose)) {
      return false;
    }

    // From now on, variable terms are as if we're in "gtr" mode.
    // but note that everything is flipped for the "ltr" function.

    for ($i = 0; $i < $range->set->length; ++$i) {
        $comparators = $range->set[$i];

        $high = null;
        $low = null;

        $comparators->JSforEach(function($comparator)use($loose,&$high,&$low) {
            $high = Util::JSor($high, $comparator);
            $low = Util::JSor($low, $comparator);
            if (gtfn($comparator->semver, $high->semver, $loose)) {
                $high = $comparator;
            } else if (ltfn($comparator->semver, $low->semver, $loose)) {
                $low = $comparator;
            }
        });

        // If the edge version comparator has a operator then our version
        // isn't outside it
        if ($high->operator->valueOf() === $comp || $high->operator->valueOf() === $ecomp) {
            return false;
        }

        // If the lowest version comparator has an operator and our version
        // is less than it then it isn't higher than the range
        if ((!$low->operator->valueOf() || $low->operator->valueOf() === $comp) &&
            ltefn($version, $low->semver)) {
            return false;
        } else if ($low->operator->valueOf() === $ecomp && ltfn($version, $low->semver)) {
            return false;
        }
    }
    return true;
}
