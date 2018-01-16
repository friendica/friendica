<?php

namespace vierbergenlars\SemVer\Tests;

use vierbergenlars\SemVer;

class VersionTest extends \PHPUnit_Framework_TestCase
{
    public function testKeepSimpleversion()
    {
        $t = array(
            '1.0.0',
            '2.0.0',
            '1.0.1',
            '1.3.2',
            '1.02.0' => '1.2.0',
            '0.2.5',
            '01.2.6' => '1.2.6',
            '2.0.03' => '2.0.3',
            '0.0.0',
        );
        foreach ($t as $original => $result) {
            if ( ! is_string($original)) {
                $original = $result;
            }
            $v = new SemVer\version($original, true);
            $this->assertEquals($v->__toString(), $result, '[' . $original . '] %s');
        }
    }

    public function testKeepSimpleversionComparator()
    {
        $t = array(
            '>1.0.0',
            '>1.1.0',
            '>1.2.3',
            '>0.5.6',
            '>0.025.6'  => '>0.25.6',
            '>=1.0.0',
            '>=1.2.0',
            '>=1.4.5',
            '>=0.9.3',
            '>=05.3.6'  => '>=5.3.6',
            '<2.0.0-0',
            '<5.6.0-0',
            '<2.3.5-0',
            '<0.2.3-0',
            '<0.2.05'   => '<0.2.5-0',
            '<=7.0.0',
            '<=1.3.0',
            '<=1.4.3',
            '<=0.2.6',
            '<=00.05.6' => '<=0.5.6',
        );
        foreach ($t as $original => $result) {
            if ( ! is_string($original)) {
                $original = $result;
            }
            $v = new SemVer\expression($original, true);
            $this->assertEquals($v->getString(), $result, '[' . $original . '] %s');
        }
    }

    public function testShortSimpleversion()
    {
        $t = array(
            '1'   => '>=1.0.0-0 <2.0.0-0',
            '1.2' => '>=1.2.0-0 <1.3.0-0',
            '1.0' => '>=1.0.0-0 <1.1.0-0',
            '501' => '>=501.0.0-0 <502.0.0-0',
        );
        foreach ($t as $original => $result) {
            $v = new SemVer\expression($original, true);
            $this->assertEquals($v->getString(), $result, '[' . $original . '] %s');
        }
    }

    public function testShortSimpleversionComparator()
    {
        $t = array(
            '>1.0.0' => '>1.0.0',
            '<2.0.0' => '<2.0.0-0',
            '<=5.2'  => '<=5.2.0-0',
            '>=3'    => '>=3.0.0-0',
        );
        foreach ($t as $original => $result) {
            $v = new SemVer\expression($original, true);
            $this->assertEquals($v->getString(), $result, '[' . $original . '] %s');
        }
    }

    public function testSimpleversionWildcard()
    {
        $t = array(
            '1.x.x' => '>=1.0.0-0 <2.0.0-0',
            '1.x'   => '>=1.0.0-0 <2.0.0-0',
            '1.x.5' => '>=1.0.0-0 <2.0.0-0',
            '3.x'   => '>=3.0.0-0 <4.0.0-0',
            '1.X.X' => '>=1.0.0-0 <2.0.0-0',
            '1.*.*' => '>=1.0.0-0 <2.0.0-0',
            '2.X.x' => '>=2.0.0-0 <3.0.0-0',
            '5.*.x' => '>=5.0.0-0 <6.0.0-0',
            'x'     => '',
        );
        foreach ($t as $original => $result) {
            $v = new SemVer\expression($original, true);
            $this->assertEquals($v->getString(), $result, '[' . $original . '] %s');
        }
    }

    public function testSimpleversionRange()
    {
        $t = array(
            '1.0.0 - 2.0.0' => '>=1.0.0 <=2.0.0',
            '1.2.3 - 1.3.0' => '>=1.2.3 <=1.3.0',
            '4.3.0 - 4.3.1' => '>=4.3.0 <=4.3.1',
        );
        foreach ($t as $original => $result) {
            $v = new SemVer\expression($original, true);
            $this->assertEquals($v->getString(), $result, '[' . $original . '] %s');
        }
    }

    public function testShortversionRange()
    {
        $t = array(
            '1 - 2'     => '>=1.0.0-0 <3.0.0-0',
            '1.2 - 2.1' => '>=1.2.0-0 <2.2.0-0',
        );
        foreach ($t as $original => $result) {
            $v = new SemVer\expression($original, true);
            $this->assertEquals($v->getString(), $result, '[' . $original . '] %s');
        }
    }

    public function testSpermies()
    {
        $t = array(
            '~1'     => '>=1.0.0-0 <2.0.0-0',
            '~2.3'   => '>=2.3.0-0 <2.4.0-0',
            '~3.7.2' => '>=3.7.2-0 <3.8.0-0',
            '~1.x'   => '>=1.0.0-0 <2.0.0-0',
            '~1.2.x' => '>=1.2.0-0 <1.3.0-0',
        );
        foreach ($t as $original => $result) {
            $v = new SemVer\expression($original, true);
            $this->assertEquals($v->getString(), $result, '[' . $original . '] %s');
        }
    }

    public function testInvalidVersion()
    {
        $t = array(
            '3.x2',
            'xx',
            '2.xx',
            '**.2',
            'Xx*',
            '.2.2',
            '1..2',
            '1.5.6.x',
            '1.5.6.7',
        );
        foreach ($t as $original) {
            $ex = false;
            try {
                $v = new SemVer\expression($original);
            } catch (SemVer\SemVerException $e) {
                $ex = true;
            }
            $this->assertTrue($ex);
        }
    }

    public function testComplexExpessions()
    {
        $t = array(
            '1.x || 2.0 - 2.3 || >4.x.x'  => '>=1.0.0-0 <2.0.0-0||>=2.0.0-0 <2.4.0-0||>=5.0.0-0',
            '2.0.x || 2.1 - 4 || 4 - 4.5' => '>=2.0.0-0 <2.1.0-0||>=2.1.0-0 <5.0.0-0||>=4.0.0-0 <4.6.0-0',
        );
        foreach ($t as $original => $result) {
            $v = new SemVer\expression($original, true);
            $this->assertEquals($v->getString(), $result, '[' . $original . '] %s');
        }
    }

    public function testSatisfiedBy()
    {
        $t = array(
            '1.0.0'            => '1.0.0',
            '1.2.3'            => '1.2.3',
            '>=1.0.1'          => array('1.0.1', '1.0.2', '1.2.0', '2.0.0'),
            '>=2'              => array('2.0.0', '2.0.1', '2.1.5', '3.0.0'),
            '<=2.4'            => array('1.2.0', '2.0.0', '2.3.999999999999'),
            '3.x'              => array('3.0.1', '3.2.0', '3.1.5'),
            '1.5.6 - 2.3.4'    => array('1.5.6', '1.5.7', '1.6.0', '2.1.0', '2.3.0', '2.3.3', '2.3.4'),
            '1 - 2 || >=2.0.5' => array('1.0.0', '2.1.0', '1.4.0', '1.0.2', '3.0.4', '2.0.6', '3.0.0'),
            '>4.0.0 <=4.2.3'   => array('4.0.1', '4.1.2', '4.2.3', '4.1.0'),
        );
        foreach ($t as $range => $satisfies) {
            $e = new SemVer\expression($range, true);
            if ( ! is_array($satisfies)) {
                $satisfies = array($satisfies);
            }
            foreach ($satisfies as $version) {
                $v = new SemVer\version($version, true);
                $this->assertTrue($e->satisfiedBy($v), '[' . $range . ' :: ' . $version . '] %s');
                $this->assertTrue($v->satisfies($e), '[' . $range . ' :: ' . $version . '] %s');
            }
        }
    }

    public function testNotSatisfiedBy()
    {
        $t = array(
            '1.0.0'                    => '1.0.1',
            '1.2.3'                    => '2.0.0',
            '<1.0.1'                   => array('1.0.1', '1.0.2', '1.2.0', '2.0.0'),
            '<=2'                      => array('2.0.1', '2.1.5', '3.0.0'),
            '>=2.4'                    => array('1.2.0', '2.0.0'),
            '3.x'                      => array('1.0.0', '1.9.9', '2.999.9999', '4.0.0'),
            '<1.5.6 || >=2.3.4 <3.0.0' => array('1.5.6', '1.5.7', '1.6.0', '2.1.0', '2.3.0', '2.3.3', '3.0.0', '3.2.1'),
            '1.2.0 - 2.1.2'            => array('1.1.2', '2.2.0'),
            '>4.0.0 <=4.2.3'           => array('4.0.0', '4.2.4', '4.5.0', '3.2.2'),
        );
        foreach ($t as $range => $satisfies) {
            $e = new SemVer\expression($range, true);
            if ( ! is_array($satisfies)) {
                $satisfies = array($satisfies);
            }
            foreach ($satisfies as $version) {
                $v = new SemVer\version($version, true);
                $this->assertFalse($e->satisfiedBy($v), '[' . $range . ' :: ' . $version . '] %s');
                $this->assertFalse($v->satisfies($e), '[' . $range . ' :: ' . $version . '] %s');
            }
        }
    }

    /**
     * @dataProvider invalidSemanticVersionProvider
     * @expectedException \vierbergenlars\SemVer\SemVerException
     */
    public function testInvalidExpressionString($version)
    {
        new SemVer\expression($version);
    }

    /**
     * @dataProvider invalidSemanticVersionProvider
     * @expectedException \vierbergenlars\SemVer\SemVerException
     */
    public function testInvalidVersionString($version)
    {
        new SemVer\version($version);
    }

    public function invalidSemanticVersionProvider()
    {
        return array(
            array('ce16575adbf06e5771b4bb4d5ac9609a685c1504'),
            array('faeih178.58498uinv-dibqo'),
            array('1.2.vqosbie'),
            array('vce16575adbf06e5771b4bb4d5ac9609a685.c1504'),
        );
    }

    function testVersionFunctions()
    {
        $t = array(
            '1.0.0-alpha'      => array('M' => 1, 'm' => 0, 'p' => 0, 'pr' => array('alpha'), 'b' => array()),
            '1.0.0-alpha.1'    => array('M' => 1, 'm' => 0, 'p' => 0, 'pr' => array('alpha', 1), 'b' => array()),
            '1.0.0-0.3.7'      => array('M' => 1, 'm' => 0, 'p' => 0, 'pr' => array(0, 3, 7), 'b' => array()),
            '1.0.0-x.7.z.92'   => array('M' => 1, 'm' => 0, 'p' => 0, 'pr' => array('x', 7, 'z', 92), 'b' => array()),
            '1.0.0-alpha+001'  => array('M' => 1, 'm' => 0, 'p' => 0, 'pr' => array('alpha'), 'b' => array('001')),
            '1.2.3-alpha.2+02' => array('M' => 1, 'm' => 2, 'p' => 3, 'pr' => array('alpha', 2), 'b' => array('02')),
            '1.2.3-a.3+02.5.a' => array(
                'M'  => 1,
                'm'  => 2,
                'p'  => 3,
                'pr' => array('a', 3),
                'b'  => array('02', 5, 'a'),
            ),

        );
        foreach ($t as $version => $parts) {
            $v = new SemVer\version($version);
            $this->assertEquals($v->getMajor(), $parts['M']);
            $this->assertEquals($v->getMinor(), $parts['m']);
            $this->assertEquals($v->getPatch(), $parts['p']);
            $this->assertEquals($v->getPrerelease(), $parts['pr']);
            $this->assertEquals($v->getBuild(), $parts['b']);
        }
    }
}
