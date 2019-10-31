<?php

declare(strict_types = 1);

namespace Phoole\Tests;

use PHPUnit\Framework\TestCase;
use Phoole\Route\Parser\FastRouteParser;

class FastRouteParserTest extends TestCase
{
    private $obj;

    private $ref;

    public function getPrivateProperty($obj, $propertyName)
    {
        $ref = new \ReflectionClass(get_class($obj));
        $property = $ref->getProperty($propertyName);
        $property->setAccessible(TRUE);
        return $property->getValue($obj);
    }

    /**
     * @covers Phoole\Route\Parser\FastRouteParser::parse()
     * @covers Phoole\Route\Parser\FastRouteParser::match()
     */
    public function testParse()
    {
        // parse route
        $p1 = '/user[/{name:c}]';
        $p2 = '/blog/{section:xd}[/{year:d}]';
        $p3 = '/news[/{year:d}[/{month:d}[/{date:d}]]]';
        $p4 = '/sport/{name:xd}/{season:xd}';
        $this->obj->parse('p1', $p1);
        $this->obj->parse('p2', $p2);
        $this->obj->parse('p3', $p3);
        $this->obj->parse('p4', $p4);
        // match
        $this->assertTrue([] === $this->obj->match('/sport/bike'));
        list($r1, $a1) = $this->obj->match('/news/2016/12');
        $this->assertTrue('p3' === $r1);
        $this->assertEquals(['year' => '2016', 'month' => '12'], $a1);
        list($r2, $a2) = $this->obj->match('/user');
        $this->assertTrue('p1' === $r2);
        $this->assertEquals([], $a2);
        list($r3, $a3) = $this->obj->match('/blog/list');
        $this->assertTrue('p2' === $r3);
        $this->assertEquals(['section' => 'list'], $a3);
        list($r4, $a4) = $this->obj->match('/user/phossa');
        $this->assertTrue('p1' === $r4);
        $this->assertEquals(['name' => 'phossa'], $a4);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->obj = new FastRouteParser();
        $this->ref = new \ReflectionClass(get_class($this->obj));
    }

    protected function tearDown(): void
    {
        $this->obj = $this->ref = NULL;
        parent::tearDown();
    }

    protected function invokeMethod($methodName, array $parameters = array())
    {
        $method = $this->ref->getMethod($methodName);
        $method->setAccessible(TRUE);
        return $method->invokeArgs($this->obj, $parameters);
    }
}
