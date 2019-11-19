<?php

declare(strict_types=1);

namespace Phoole\Tests;

use Phoole\Route\Util\Route;
use Phoole\Route\Util\Result;
use PHPUnit\Framework\TestCase;
use Phoole\Route\Util\RouteGroup;
use GuzzleHttp\Psr7\ServerRequest;
use Phoole\Route\Util\RouteAwareTrait;
use Phoole\Route\Parser\FastRouteParser;

class myRouter
{
    use RouteAwareTrait;
}

class RouteAwareTraitTest extends TestCase
{
    private $obj;

    private $ref;

    /**
     * @covers Phoole\Route\Util\RouteAwareTrait::addRoute()
     * @covers Phoole\Route\Util\RouteAwareTrait::setParser()
     */
    public function testAddRoute()
    {
        $this->invokeMethod('setParser', [new FastRouteParser()]);
        $route = new Route(
            'POST', '/usr[/{uid:d=20}][/{pid:d=1}]', function() {
            return FALSE;
        }, ['uid' => 100]
        );
        $this->obj->addRoute($route);
        $routes = $this->getPrivateProperty($this->obj, 'routes');
        $this->assertEquals(1, count($routes));
    }

    protected function invokeMethod($methodName, array $parameters = array())
    {
        $method = $this->ref->getMethod($methodName);
        $method->setAccessible(TRUE);
        return $method->invokeArgs($this->obj, $parameters);
    }

    public function getPrivateProperty($obj, $propertyName)
    {
        $ref = new \ReflectionClass(get_class($obj));
        $property = $ref->getProperty($propertyName);
        $property->setAccessible(TRUE);
        return $property->getValue($obj);
    }

    /**
     * @covers Phoole\Route\Util\RouteAwareTrait::addGet()
     * @covers Phoole\Route\Util\RouteAwareTrait::addPost()
     */
    public function testAddGet()
    {
        $this->invokeMethod('setParser', [new FastRouteParser()]);
        $this->obj->addGet(
            '/usr[/{uid:d=20}][/{pid:d=1}]', function() {
            return FALSE;
        }, ['uid' => 100]
        );
        $this->obj->addPost(
            '/usr[/{uid:d=20}][/{pid:d=1}]', function() {
            return FALSE;
        }, ['uid' => 20]
        );

        $routes = $this->getPrivateProperty($this->obj, 'routes');
        $this->assertEquals(1, count($routes));
        $this->obj->addPost(
            '/usr', function() {
            return FALSE;
        }, ['uid' => 20]
        );
        $routes = $this->getPrivateProperty($this->obj, 'routes');
        $this->assertEquals(2, count($routes));
    }

    /**
     * @covers Phoole\Route\Util\RouteAwareTrait::loadRoutes()
     */
    public function testLoadRoutes()
    {
        $this->invokeMethod('setParser', [new FastRouteParser()]);
        $this->invokeMethod(
            'loadRoutes', [
                [
                    ['GET', '/usr[/{uid:d=20}][/{pid:d=1}]', function() {
                        return TRUE;
                    }, ['pid' => 2]
                    ],
                    ['POST', '/usr/add', function() {
                        return TRUE;
                    }
                    ],
                ]
            ]
        );
        $routes = $this->getPrivateProperty($this->obj, 'routes');
        $this->assertEquals(2, count($routes));
    }

    /**
     * @covers Phoole\Route\Util\RouteAwareTrait::groupMatch()
     */
    public function testGroupMatch()
    {
        $this->invokeMethod('setParser', [new FastRouteParser()]);
        $this->invokeMethod(
            'loadRoutes', [
                [
                    ['GET', '/usr[/{uid:d=20}][/{pid:d=1}]', function() {
                        return TRUE;
                    }, ['pid' => 2]
                    ],
                    ['POST', '/usr/add', function() {
                        return TRUE;
                    }
                    ],
                ]
            ]
        );
        $result = $this->invokeMethod(
            'routeMatch', [new Result(
                               new ServerRequest('GET', 'http://bingo.com/xusr/10/2')
                           )
            ]
        );
        $this->assertFalse($result->isMatched());
        $result = $this->invokeMethod(
            'routeMatch', [new Result(
                               new ServerRequest('GET', 'http://bingo.com/usr/10/2')
                           )
            ]
        );
        $this->assertTrue($result->isMatched());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->obj = new myRouter();
        $this->ref = new \ReflectionClass(get_class($this->obj));
    }

    protected function tearDown(): void
    {
        $this->obj = $this->ref = NULL;
        parent::tearDown();
    }
}
