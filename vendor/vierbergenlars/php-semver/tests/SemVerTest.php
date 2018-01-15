<?php

namespace vierbergenlars\SemVer\Tests\Internal;

use vierbergenlars\SemVer\Internal as SemVer;

class SemVerTest extends \PHPUnit_Framework_TestCase
{
    public function testComparison()
    {
        $compare = array(
            array("0.0.0", "0.0.0-foo"),
            array("0.0.1", "0.0.0"),
            array("1.0.0", "0.9.9"),
            array("0.10.0", "0.9.0"),
            array("0.99.0", "0.10.0"),
            array("2.0.0", "1.2.3"),
            array("v0.0.0", "0.0.0-foo", true),
            array("v0.0.1", "0.0.0", true),
            array("v1.0.0", "0.9.9", true),
            array("v0.10.0", "0.9.0", true),
            array("v0.99.0", "0.10.0", true),
            array("v2.0.0", "1.2.3", true),
            array("0.0.0", "v0.0.0-foo", true),
            array("0.0.1", "v0.0.0", true),
            array("1.0.0", "v0.9.9", true),
            array("0.10.0", "v0.9.0", true),
            array("0.99.0", "v0.10.0", true),
            array("2.0.0", "v1.2.3", true),
            array("1.2.3", "1.2.3-asdf"),
            array("1.2.3", "1.2.3-4"),
            array("1.2.3", "1.2.3-4-foo"),
            array("1.2.3-5-foo", "1.2.3-5"),
            array("1.2.3-5", "1.2.3-4"),
            array("1.2.3-5-foo", "1.2.3-5-Foo"),
            //array('1.2.3-0','1.2.3'),
            array('3.0.0', '2.7.2+asdf'),
            array('1.2.3-a.10', '1.2.3-a.5'),
            array('1.2.3-a.b', '1.2.3-a.5'),
            array('1.2.3-a.b', '1.2.3-a'),
            array('1.2.3-a.b.c.10.d.5', '1.2.3-a.b.c.5.d.100')
        );
        foreach ($compare as $set) {
            $a = $set[0];
            $b = $set[1];
            $loose = isset($set[2]) && $set[2];
            $this->assertTrue(SemVer\G::gt($a, $b, $loose), "%s > gt('" . $a . "', '" . $b . "')");
            $this->assertTrue(SemVer\G::lt($b, $a, $loose), "%s > lt('" . $b . "', '" . $a . "')");
            $this->assertFalse(SemVer\G::gt($b, $a, $loose), "%s > !gt('" . $b . "', '" . $a . "')");
            $this->assertFalse(SemVer\G::lt($a, $b, $loose), "%s > !lt('" . $a . "', '" . $b . "')");
            $this->assertTrue(SemVer\G::eq($a, $a, $loose), "%s > eq('" . $a . "', '" . $a . "')");
            $this->assertTrue(SemVer\G::eq($b, $b, $loose), "%s > eq('" . $b . "', '" . $b . "')");
            $this->assertTrue(SemVer\G::neq($a, $b, $loose), "%s > neq('" . $a . "', '" . $b . "')");
            $this->assertTrue(SemVer\G::cmp($b, "==", $b, $loose), "%s > cmp('" . $b . "' == '" . $b . "')");
            $this->assertTrue(SemVer\G::cmp($a, ">=", $b, $loose), "%s > cmp('" . $a . "' >= '" . $b . "')");
            $this->assertTrue(SemVer\G::cmp($b, "<=", $a, $loose), "%s > cmp('" . $b . "' <= '" . $a . "')");
            $this->assertTrue(SemVer\G::cmp($a, "!=", $b, $loose), "%s > cmp('" . $a . "' != '" . $b . "')");
        }
    }

    public function testEquality()
    {
        $compare = array(
            array("1.2.3", "v1.2.3", true),
            array("1.2.3", "=1.2.3", true),
            array("1.2.3", "v 1.2.3", true),
            array("1.2.3", "= 1.2.3", true),
            array("1.2.3", " v1.2.3", true),
            array("1.2.3", " =1.2.3", true),
            array("1.2.3", " v 1.2.3", true),
            array("1.2.3", " = 1.2.3", true),
            array("1.2.3-0", "v1.2.3-0", true),
            array("1.2.3-0", "=1.2.3-0", true),
            array("1.2.3-0", "v 1.2.3-0", true),
            array("1.2.3-0", "= 1.2.3-0", true),
            array("1.2.3-0", " v1.2.3-0", true),
            array("1.2.3-0", " =1.2.3-0", true),
            array("1.2.3-0", " v 1.2.3-0", true),
            array("1.2.3-0", " = 1.2.3-0", true),
            array("1.2.3-01", "v1.2.3-1", true),
            array("1.2.3-01", "=1.2.3-1", true),
            array("1.2.3-01", "v 1.2.3-1", true),
            array("1.2.3-01", "= 1.2.3-1", true),
            array("1.2.3-01", " v1.2.3-1", true),
            array("1.2.3-01", " =1.2.3-1", true),
            array("1.2.3-01", " v 1.2.3-1", true),
            array("1.2.3-01", " = 1.2.3-1", true),
            array("1.2.3-beta", "v1.2.3-beta", true),
            array("1.2.3-beta", "=1.2.3-beta", true),
            array("1.2.3-beta", "v 1.2.3-beta", true),
            array("1.2.3-beta", "= 1.2.3-beta", true),
            array("1.2.3-beta", " v1.2.3-beta", true),
            array("1.2.3-beta", " =1.2.3-beta", true),
            array("1.2.3-beta", " v 1.2.3-beta", true),
            array("1.2.3-beta", " = 1.2.3-beta", true),
            array("1.2.3-beta+build", " = 1.2.3-beta+otherbuild", true),
            array("1.2.3+build", " = 1.2.3+otherbuild", true),
            array("1.2.3-beta+build", "1.2.3-beta+otherbuild", true),
            array("1.2.3+build", "1.2.3+otherbuild"),
            array("1.2.3+build", '1.2.3+otherbuild'),
            array("  v1.2.3+build", "1.2.3+otherbuild")

        );
        foreach ($compare as $set) {
            $a = $set[0];
            $b = $set[1];
            $loose = isset($set[2]) && $set[2];
            try {
                $this->assertTrue(SemVer\G::eq($a, $b, $loose), "%s > eq('" . $a . "', '" . $b . "')");
                $this->assertFalse(SemVer\G::neq($a, $b, $loose), "%s > !neq('" . $a . "', '" . $b . "')");
                $this->assertTrue(SemVer\G::cmp($a, "==", $b, $loose), "%s > cmp(" . $a . "==" . $b . ")");
                $this->assertFalse(SemVer\G::cmp($a, "!=", $b, $loose), "%s > !cmp(" . $a . "!=" . $b . ")");
                $this->assertFalse(SemVer\G::cmp($a, "===", $b, $loose), "%s > !cmp(" . $a . "===" . $b . ")");
                $this->assertTrue(SemVer\G::cmp($a, "!==", $b, $loose), "%s > cmp(" . $a . "!==" . $b . ")");
                $this->assertFalse(SemVer\G::gt($a, $b, $loose), "%s > !gt('" . $a . "', '" . $b . "')");
                $this->assertTrue(SemVer\G::gte($a, $b, $loose), "%s > gte('" . $a . "', '" . $b . "')");
                $this->assertFalse(SemVer\G::lt($a, $b, $loose), "%s > !lt('" . $a . "', '" . $b . "')");
                $this->assertTrue(SemVer\G::lte($a, $b, $loose), "%s > lte('" . $a . "', '" . $b . "')");
            } catch (\Exception $e) {
                $this->fail("Exception while comparing $set[0] and $set[1]");
            }
        }
    }

    public function testRange()
    {
        $compare = array(
            array("1.0.0 - 2.0.0", "1.2.3"),
            array("1.0.0", "1.0.0"),
            array(">=*", "0.2.4"),
            array("", "1.0.0"),
            array("*", "1.2.3"),
            array('*', 'v1.2.3-foo', true),
            array(">=1.0.0", "1.0.0"),
            array(">=1.0.0", "1.0.1"),
            array(">=1.0.0", "1.1.0"),
            array(">1.0.0", "1.0.1"),
            array(">1.0.0", "1.1.0"),
            array("<=2.0.0", "2.0.0"),
            array("<=2.0.0", "1.9999.9999"),
            array("<=2.0.0", "0.2.9"),
            array("<2.0.0", "1.9999.9999"),
            array("<2.0.0", "0.2.9"),
            array(">= 1.0.0", "1.0.0"),
            array(">=  1.0.0", "1.0.1"),
            array(">=   1.0.0", "1.1.0"),
            array("> 1.0.0", "1.0.1"),
            array(">  1.0.0", "1.1.0"),
            array("<=   2.0.0", "2.0.0"),
            array("<= 2.0.0", "1.9999.9999"),
            array("<=  2.0.0", "0.2.9"),
            array("<    2.0.0", "1.9999.9999"),
            array("<\t2.0.0", "0.2.9"),
            array(">=0.1.97", "v0.1.97", true),
            array(">=0.1.97", "0.1.97"),
            array("0.1.20 || 1.2.4", "1.2.4"),
            array(">=0.2.3 || <0.0.1", "0.0.0"),
            array(">=0.2.3 || <0.0.1", "0.2.3"),
            array(">=0.2.3 || <0.0.1", "0.2.4"),
            array("||", "1.3.4"),
            array("2.x.x", "2.1.3"),
            array("1.2.x", "1.2.3"),
            array("1.2.x || 2.x", "2.1.3"),
            array("1.2.x || 2.x", "1.2.3"),
            array("x", "1.2.3"),
            array("2.*.*", "2.1.3"),
            array("1.2.*", "1.2.3"),
            array("1.2.* || 2.*", "2.1.3"),
            array("1.2.* || 2.*", "1.2.3"),
            array("*", "1.2.3"),
            array("2", "2.1.2"),
            array("2.3", "2.3.1"),
            array("~2.4", "2.4.0"), // >=2.4.0 <2.5.0
            array("~2.4", "2.4.5"),
            array("~>3.2.1", "3.2.2"), // >=3.2.1 <3.3.0
            array("~1", "1.2.3"), // >=1.0.0 <2.0.0
            array("~>1", "1.2.3"),
            array("~> 1", "1.2.3"),
            array("~1.0", "1.0.2"), // >=1.0.0 <1.1.0
            array("~ 1.0", "1.0.2"),
            array("~1.0.3", "1.0.12"),
            array(">=1", "1.0.0"),
            array(">= 1", "1.0.0"),
            array("<1.2", "1.1.1"),
            array("< 1.2", "1.1.1"),
            array("1", "1.0.0beta", true),
            array("~v0.5.4-pre", "0.5.5"),
            array("~v0.5.4-pre", "0.5.4"),
            array('=0.7.x', '0.7.2'),
            array('>=0.7.x', '0.7.2'),
            array('=0.7.x', '0.7.0-asdf'),
            array('>=0.7.x', '0.7.0-asdf'),
            array('<=0.7.x', '0.6.2'),
            array('~1.2.1 >=1.2.3', '1.2.3'),
            array('~1.2.1 =1.2.3', '1.2.3'),
            array('~1.2.1 1.2.3', '1.2.3'),
            array('~1.2.1 >=1.2.3 1.2.3', '1.2.3'),
            array('~1.2.1 1.2.3 >=1.2.3', '1.2.3'),
            array('~1.2.1 1.2.3', '1.2.3'),
            array('>=1.2.1 1.2.3', '1.2.3'),
            array('1.2.3 >=1.2.1', '1.2.3'),
            array('>=1.2.3 >=1.2.1', '1.2.3'),
            array('>=1.2.1 >=1.2.3', '1.2.3'),
            array('<=1.2.3', '1.2.3-beta'),
            array('>1.2', '1.3.0-beta'),
            array('>=1.2', '1.2.8'),
            array('^1.2.3', '1.8.1'),
            array('^1.2.3', '1.2.3-beta'),
            array('^0.1.2', '0.1.2'),
            array('^0.1', '0.1.2'),
            array('^1.2', '1.4.2'),
            array('^1.2 ^1', '1.4.2'),
            array('^1.2', '1.2.0-pre'),
            array('^1.2.3', '1.2.3-pre')
        );
        foreach ($compare as $set) {
            $loose = isset($set[2]) && $set[2];

            $this->assertTrue(SemVer\G::satisfies($set[1], $set[0], $loose),
                "%s > $set[0] should be satisfied by $set[1]");
        }
    }

    public function testNegativeRange()
    {
        $compare = array(
            array("1.0.0 - 2.0.0", "2.2.3"),
            array("1.0.0", "1.0.1"),
            array(">=1.0.0", "0.0.0"),
            array(">=1.0.0", "0.0.1"),
            array(">=1.0.0", "0.1.0"),
            array(">1.0.0", "0.0.1"),
            array(">1.0.0", "0.1.0"),
            array("<=2.0.0", "3.0.0"),
            array("<=2.0.0", "2.9999.9999"),
            array("<=2.0.0", "2.2.9"),
            array("<2.0.0", "2.9999.9999"),
            array("<2.0.0", "2.2.9"),
            array(">=0.1.97", "v0.1.93", true),
            array(">=0.1.97", "0.1.93"),
            array("0.1.20 || 1.2.4", "1.2.3"),
            array(">=0.2.3 || <0.0.1", "0.0.3"),
            array(">=0.2.3 || <0.0.1", "0.2.2"),
            array("2.x.x", "1.1.3"),
            array("2.x.x", "3.1.3"),
            array("1.2.x", "1.3.3"),
            array("1.2.x || 2.x", "3.1.3"),
            array("1.2.x || 2.x", "1.1.3"),
            array("2.*.*", "1.1.3"),
            array("2.*.*", "3.1.3"),
            array("1.2.*", "1.3.3"),
            array("1.2.* || 2.*", "3.1.3"),
            array("1.2.* || 2.*", "1.1.3"),
            array("2", "1.1.2"),
            array("2.3", "2.4.1"),
            array("~2.4", "2.5.0"), // >=2.4.0 <2.5.0
            array("~2.4", "2.3.9"),
            array("~>3.2.1", "3.3.2"), // >=3.2.1 <3.3.0
            array("~>3.2.1", "3.2.0"), // >=3.2.1 <3.3.0
            array("~1", "0.2.3"), // >=1.0.0 <2.0.0
            array("~>1", "2.2.3"),
            array("~1.0", "1.1.0"), // >=1.0.0 <1.1.0
            array("<1", "1.0.0"),
            array(">=1.2", "1.1.1"),
            array("1", "2.0.0beta", true),
            array("~v0.5.4-beta", "0.5.4-alpha"),
            array('<1', '1.0.0beta', true),
            array('< 1', '1.0.0beta', true),
            array('=0.7.x', '0.8.2'),
            array('>=0.7.x', '0.6.2'),
            array('<=0.7.x', '0.7.2'),
            array('<1.2.3', '1.2.3-beta'),
            array('=1.2.3', '1.2.3-beta'),
            array('>1.2', '1.2.8'),
            array('^1.2.3', '2.0.0-alpha'),
            array('^1.2.3', '1.2.2'),
            array('^1.2', '1.1.9'),
            // Invalid ranges are never satisfied
            array('blerg', '1.2.3'),
            array('git+https://user:password0123@github.com/foo', '123.0.0', true),
            array('^1.2.3', '2.0.0-pre')
        );
        foreach ($compare as $set) {
            $loose = isset($set[2]) && $set[2];
            $this->assertFalse(SemVer\G::satisfies($set[1], $set[0], $loose),
                "%s > $set[0] should not be satisfied by $set[1]");
        }
    }

    public function testIncrementVersions()
    {
        $compare = array(
            array("1.2.3", "major", "2.0.0"),
            array("1.2.3", "minor", "1.3.0"),
            array("1.2.3", "patch", "1.2.4"),
            array("1.2.3tag", "major", "2.0.0", true),
            array("1.2.3-tag", "major", "2.0.0"),
            array("1.2.3", "fake", null),
            array("fake", "major", null),
            array("1.2.3", "prerelease", "1.2.3-0"),
            array("1.2.3-0", "prerelease", "1.2.3-1"),
            array("1.2.3-alpha.0.beta", "prerelease", "1.2.3-alpha.1.beta"),
            array("1.2.3-alpha.1.beta", "prerelease", "1.2.3-alpha.2.beta"),
            array("1.2.3-alpha.2.beta", "prerelease", "1.2.3-alpha.3.beta"),
            array("1.2.3-alpha.10.0.beta", "prerelease", "1.2.3-alpha.10.1.beta"),
            array("1.2.3-alpha.10.1.beta", "prerelease", "1.2.3-alpha.10.2.beta"),
            array("1.2.3-alpha.10.2.beta", "prerelease", "1.2.3-alpha.10.3.beta"),
            array("1.2.3-alpha.10.beta.0", "prerelease", "1.2.3-alpha.10.beta.1"),
            array("1.2.3-alpha.10.beta.1", "prerelease", "1.2.3-alpha.10.beta.2"),
            array("1.2.3-alpha.10.beta.2", "prerelease", "1.2.3-alpha.10.beta.3"),
            array("1.2.3-alpha.9.beta", "prerelease", "1.2.3-alpha.10.beta"),
            array("1.2.3-alpha.10.beta", "prerelease", "1.2.3-alpha.11.beta"),
            array("1.2.3-alpha.12.beta", "prerelease", "1.2.3-alpha.13.beta")
        );
        foreach ($compare as $set) {
            $s = $set[0];
            $loose = isset($set[3]) && $set[3];

            $found = SemVer\G::inc($set[0], $set[1], $loose);
            $this->assertEquals($found, $set[2]);
        }
    }

    public function testValidRange()
    {
        $compare = array(
            array("1.0.0 - 2.0.0", ">=1.0.0 <=2.0.0"),
            array("1.0.0", "1.0.0"),
            array(">=*", ">=0.0.0-0"),
            array("", "*"),
            array("*", "*"),
            array(">=1.0.0", ">=1.0.0"),
            array(">1.0.0", ">1.0.0"),
            array("<=2.0.0", "<=2.0.0"),
            array("1", ">=1.0.0-0 <2.0.0-0"),
            array("<=2.0.0", "<=2.0.0"),
            array("<2.0.0", "<2.0.0-0"),
            array(">= 1.0.0", ">=1.0.0"),
            array(">=  1.0.0", ">=1.0.0"),
            array(">=   1.0.0", ">=1.0.0"),
            array("> 1.0.0", ">1.0.0"),
            array(">  1.0.0", ">1.0.0"),
            array("<=   2.0.0", "<=2.0.0"),
            array("<= 2.0.0", "<=2.0.0"),
            array("<=  2.0.0", "<=2.0.0"),
            array("<    2.0.0", "<2.0.0-0"),
            array("<	2.0.0", "<2.0.0-0"),
            array(">=0.1.97", ">=0.1.97"),
            array(">=0.1.97", ">=0.1.97"),
            array("0.1.20 || 1.2.4", "0.1.20||1.2.4"),
            array(">=0.2.3 || <0.0.1", ">=0.2.3||<0.0.1-0"),
            array(">=0.2.3 || <0.0.1", ">=0.2.3||<0.0.1-0"),
            array(">=0.2.3 || <0.0.1", ">=0.2.3||<0.0.1-0"),
            array("||", "||"),
            array("2.x.x", ">=2.0.0-0 <3.0.0-0"),
            array("1.2.x", ">=1.2.0-0 <1.3.0-0"),
            array("1.2.x || 2.x", ">=1.2.0-0 <1.3.0-0||>=2.0.0-0 <3.0.0-0"),
            array("x", "*"),
            array("2.*.*", '>=2.0.0-0 <3.0.0-0'),
            array("1.2.*", '>=1.2.0-0 <1.3.0-0'),
            array("1.2.* || 2.*", '>=1.2.0-0 <1.3.0-0||>=2.0.0-0 <3.0.0-0'),
            array("*", "*"),
            array("2", ">=2.0.0-0 <3.0.0-0"),
            array("2.3", ">=2.3.0-0 <2.4.0-0"),
            array("~2.4", ">=2.4.0-0 <2.5.0-0"),
            array("~>3.2.1", ">=3.2.1-0 <3.3.0-0"),
            array("~1", ">=1.0.0-0 <2.0.0-0"),
            array("~>1", ">=1.0.0-0 <2.0.0-0"),
            array("~> 1", ">=1.0.0-0 <2.0.0-0"),
            array("~1.0", ">=1.0.0-0 <1.1.0-0"),
            array("~ 1.0", ">=1.0.0-0 <1.1.0-0"),
            array("^0", ">=0.0.0-0 <1.0.0-0"),
            array("^ 1", ">=1.0.0-0 <2.0.0-0"),
            array("^0.1", ">=0.1.0-0 <0.2.0-0"),
            array("^1.0", ">=1.0.0-0 <2.0.0-0"),
            array("^1.2", ">=1.2.0-0 <2.0.0-0"),
            array("^0.0.1", "=0.0.1"),
            array("^0.0.1-beta", "=0.0.1-beta"),
            array("^0.1.2", ">=0.1.2-0 <0.2.0-0"),
            array("^1.2.3", ">=1.2.3-0 <2.0.0-0"),
            array("^1.2.3-beta.4", ">=1.2.3-beta.4 <2.0.0-0"),
            array("<1", "<1.0.0-0"),
            array("< 1", "<1.0.0-0"),
            array(">=1", ">=1.0.0-0"),
            array(">= 1", ">=1.0.0-0"),
            array("<1.2", "<1.2.0-0"),
            array("< 1.2", "<1.2.0-0"),
            array("1", ">=1.0.0-0 <2.0.0-0"),
            array(">01.02.03", ">1.2.3", true),
            array(">01.02.03", null),
            array("~1.2.3beta", ">=1.2.3-beta <1.3.0-0", true),
            array("~1.2.3beta", null),
            array("^ 1.2 ^ 1", ">=1.2.0-0 <2.0.0-0 >=1.0.0-0 <2.0.0-0")
        );
        foreach ($compare as $set) {
            $loose = isset($set[2]) && $set[2];

            $this->assertEquals(SemVer\G::validRange($set[0], $loose), $set[1]);
        }
    }

    public function testStrictVsLoose()
    {
        $compare = array(
            array('=1.2.3', '1.2.3'),
            array('01.02.03', '1.2.3'),
            array('1.2.3-beta.01', '1.2.3-beta.1'),
            array('   =1.2.3', '1.2.3'),
            array('1.2.3foo', '1.2.3-foo')
        );

        foreach ($compare as $set) {
            $ex = false;
            try {
                new SemVer\SemVer($set[0]);
            } catch (\Exception $e) {
                $ex = true;
            }
            $this->assertTrue($ex,
                "%s > Creating version with loose version $set[0], without loose flag set should throw exception.");

            $vers = new SemVer\SemVer($set[0], true);

            $this->assertEquals($vers->version->valueOf(), $set[1],
                "%s > (new version($set[0], true))->getVersion() == $set[1]");
            $this->assertTrue(SemVer\G::eq($set[0], $set[1], true), "%s > eq($set[0], $set[1], true)");

            $ex = false;
            try {
                SemVer\G::eq($set[0], $set[1]);
            } catch (\Exception $e) {
                $ex = true;
            }
            $this->assertTrue($ex, "%s > eq($set[0], $set[1]) should throw");
        }
    }
}
