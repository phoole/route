<?php

declare(strict_types=1);

namespace Phoole\Tests;

use PHPUnit\Framework\TestCase;
use Phoole\Route\Util\Route;
use Phoole\Route\Util\Result;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class ResultTest extends TestCase
{
    private $obj;
    private $ref;

    protected function setUp(): void
    {
        parent::setUp();
        $this->obj = new Result(new ServerRequest('GET', 'http://bingo.com/usr/10/2'));
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

    public function getPrivateProperty($obj, $propertyName)
    {
        $ref = new \ReflectionClass(get_class($obj));
        $property = $ref->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }

    /**
     * @covers Phoole\Route\Util\Result::setRoute()
     * @covers Phoole\Route\Util\Result::getRoute()
     */
    public function testSetRoute()
    {
        $pattern = '/usr[/{uid:d}][/{pid:d}]';
        $this->obj->setRoute(new Route('GET,HEAD', $pattern, function() {
            return false;
        }, ['uid' => 100]));

        $route = $this->obj->getRoute();
        $this->assertEquals($pattern, $route->getPattern());
        $this->assertEquals(2, count($route->getMethods()));
    }

    /**
     * @covers Phoole\Route\Util\Result::getRequest()
     */
    public function testGetRequest()
    {
        $this->assertTrue($this->obj->getRequest() instanceof ServerRequestInterface);
    }

    /**
     * @covers Phoole\Route\Util\Result::setHandler()
     * @covers Phoole\Route\Util\Result::getHandler()
     */
    public function testGetHandler()
    {
        $handler = function() { return true; };
        $this->obj->setHandler($handler);
        $this->assertTrue($handler === $this->obj->getHandler());
    }

    /**
     * @covers Phoole\Route\Util\Result::isMatched()
     */
    public function testIsMatched()
    {
        $this->assertFalse($this->obj->isMatched());

        $pattern = '/usr[/{uid:d}][/{pid:d}]';
        $this->obj->setRoute(new Route('GET,HEAD', $pattern, function() {
            return false;
        }, ['uid' => 100]));

        $this->assertTrue($this->obj->isMatched());
    }
}