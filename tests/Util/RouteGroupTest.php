<?php

declare(strict_types=1);

namespace Phoole\Tests;

use PHPUnit\Framework\TestCase;
use Phoole\Route\Router;
use Phoole\Route\Util\Route;
use Phoole\Route\Util\Result;
use Phoole\Route\Util\RouteGroup;
use Phoole\Route\Parser\FastRouteParser;
use GuzzleHttp\Psr7\ServerRequest;

class RouteGroupTest extends TestCase
{
    private $obj;
    private $ref;

    protected function setUp(): void
    {
        parent::setUp();
        $this->obj = new RouteGroup(new FastRouteParser());
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

    public function getPrivateProperty($obj, $propertyName) {     
        $ref = new \ReflectionClass(get_class($obj));
        $property = $ref->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }
    
    /**
     * @covers Phoole\Route\Util\Route::addRoute()
     * @covers Phoole\Route\Util\Route::match()
     */
    public function testAddRoute()
    {
        $this->obj->addRoute(new Route('GET,HEAD', '/usr[/{uid:d}][/{pid:d}]', function() {
            return false;
        }, ['uid' => 100]));

        $this->assertEquals(1, count($this->getPrivateProperty($this->obj, 'routes')));

        $result = new Result(new ServerRequest('GET', 'http://bingo.com/usr/10/2'));
        $this->assertTrue($this->obj->match($result));

        $this->assertEquals(
            ['uid' => '10', 'pid' => '2'],
            $result->getRequest()->getAttribute(Router::URI_PARAMETERS)
        );
    }
}