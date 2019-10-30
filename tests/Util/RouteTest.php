<?php

declare(strict_types=1);

namespace Phoole\Tests;

use PHPUnit\Framework\TestCase;
use Phoole\Route\Util\Route;

class RouteTest extends TestCase
{
    private $obj;
    private $ref;

    protected function setUp(): void
    {
        parent::setUp();
        $this->obj = new Route('GET,HEAD', '/usr/*', function() {
            return false;
        }, ['uid' => 100]);
        $this->ref = new \ReflectionClass(get_class($this->obj));
    }

    protected function tearDown(): void
    {
        $this->obj = $this->ref = null;
        parent::tearDown();
    }

    protected function invokeMethod($methodName, array $parameters = array())
    {
        $method = $this->ref->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->obj, $parameters);
    }

    /**
     * @covers Phoole\Route\Util\Route::validatePattern()
     */
    public function testValidatePattern()
    {
        $pattern = '/blog[/{section}][/{year:d}[/{month:d}[/{date:d}]]]';
        $this->assertTrue($this->invokeMethod('validatePattern', [$pattern]));

        $pattern .= ']';
        $this->expectExceptionMessage('Invalid route');
        $this->assertTrue($this->invokeMethod('validatePattern', [$pattern]));
    }

    /**
     * @covers Phoole\Route\Util\Route::validatePattern()
     */
    public function testValidatePattern2()
    {
        $this->expectExceptionMessage('Invalid route');
        $pattern = '/blog[/{section}][/{year:d}[/{month:d}[/{date:d}]]]' . '{';
        $this->assertTrue($this->invokeMethod('validatePattern', [$pattern]));
    }

    /**
     * @covers Phoole\Route\Util\Route::extractDefaults()
     */
    public function testExtractDefaults()
    {
        $pattern = '/blog[/{section=1}][/{year:d=2}[/{month:d=03}[/{date:d=08}]]]';
        list($p, $d) = $this->invokeMethod('extractDefaults', [$pattern]);

        $this->assertEquals('/blog[/{section}][/{year:d}[/{month:d}[/{date:d}]]]', $p);
        $this->assertEquals(
            ['section' => '1', 'year' => '2', 'month' => '03', 'date' => '08'], $d
        );
    }

    /**
     * @covers Phoole\Route\Util\Route::setPattern()
     * @covers Phoole\Route\Util\Route::getPattern()
     */
    public function testSetPattern()
    {
        $pattern = '/blog[/{section=1}][/{year:d=2}[/{month:d=03}[/{date:d=08}]]]';
        $defaults = ['test' => '1'];
        $this->obj->setPattern($pattern, $defaults);

        $this->assertEquals(
            '/blog[/{section}][/{year:d}[/{month:d}[/{date:d}]]]',
            $this->obj->getPattern()
        );

        $this->assertEquals(
            ['section' => '1', 'year' => '2', 'month' => '03', 'date' => '08', 'test' => '1'],
            $defaults
        );
    }

    /**
     * @covers Phoole\Route\Util\Route::setMethods()
     * @covers Phoole\Route\Util\Route::getMethods()
     */
    public function testSetMethods()
    {
        $method = 'GET,HEAD';
        $handler = function() { return true; };
        $defaults = ['test' => 'bingo'];
        $this->obj->setMethods($method, $handler, $defaults);

        $methods = $this->obj->getMethods();
        $this->assertEquals(2, count($methods));

        $this->assertEquals($methods['GET'][0], $handler);
        $this->assertEquals($methods['GET'][1], $defaults);

        $this->assertTrue(isset($methods['HEAD']));
        $this->assertTrue($methods['HEAD'][0] === $methods['GET'][0]);
    }

    /**
     * @covers Phoole\Route\Util\Route::addMethods()
     */
    public function testAddMethods()
    {
        $method = 'GET,HEAD';
        $handler = function() { return true; };
        $defaults = ['test' => 'bingo'];
        $this->obj->setMethods($method, $handler, $defaults);

        $obj = new Route('POST', '/usr/*', function() {
            return false;
        }, ['uid' => 100]);

        $this->obj->addMethods($obj);
        $methods = $this->obj->getMethods();
        $this->assertEquals(3, count($methods));
        $this->assertEquals(['uid' => 100], $methods['POST'][1]);
    }

    /**
     * @covers Phoole\Route\Util\Route::__construct()
     */
    public function testConstruct()
    {
        $pattern = '/blog[/{section=1}][/{year:d=2}[/{month:d=03}[/{date:d=08}]]]';
        $obj = new Route('POST', '/usr[/{uid:d=20}][/{pid:d=1}]', function() {
            return false;
        }, ['uid' => 100]);

        $this->assertEquals('/usr[/{uid:d}][/{pid:d}]', $obj->getPattern());
        list($handler, $defaults) = $obj->getMethods()['POST'];
        $this->assertEquals(['uid' => 100, 'pid' => '1'], $defaults);
    }
}