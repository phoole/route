<?php

declare(strict_types = 1);

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
     * @covers Phoole\Route\Util\RouteAwareTrait::extractPrefix()
     */
    public function testExtractPrefix()
    {
        $this->assertEquals(
            '/usr',
            $this->invokeMethod('extractPrefix', ['/usr[/bingo]'])
        );
        $this->assertEquals(
            '/usr',
            $this->invokeMethod('extractPrefix', ['/usr/bingo/2'])
        );
        $this->assertEquals(
            '/',
            $this->invokeMethod('extractPrefix', ['/[usr[/bingo/]]'])
        );
    }

    protected function invokeMethod($methodName, array $parameters = array())
    {
        $method = $this->ref->getMethod($methodName);
        $method->setAccessible(TRUE);
        return $method->invokeArgs($this->obj, $parameters);
    }

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
        $groups = $this->getPrivateProperty($this->obj, 'groups');
        $this->assertEquals(1, count($groups));
        $this->assertTrue(isset($groups['/usr']));
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
        $groups = $this->getPrivateProperty($this->obj, 'groups');
        $grp = $groups['/usr'];
        $this->assertTrue($grp instanceof RouteGroup);
        $routes = $this->getPrivateProperty($grp, 'routes');
        $this->assertEquals(1, count($routes));
        $this->obj->addPost(
            '/usr', function() {
            return FALSE;
        }, ['uid' => 20]
        );
        $routes = $this->getPrivateProperty($grp, 'routes');
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
        $groups = $this->getPrivateProperty($this->obj, 'groups');
        $this->assertEquals(1, count($groups));
        $grp = $groups['/usr'];
        $routes = $this->getPrivateProperty($grp, 'routes');
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
            'groupMatch', [new Result(
                               new ServerRequest('GET', 'http://bingo.com/xusr/10/2')
                           )
            ]
        );
        $this->assertFalse($result->isMatched());
        $result = $this->invokeMethod(
            'groupMatch', [new Result(
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
