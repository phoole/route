# route
[![Build Status](https://travis-ci.com/phoole/route.svg?branch=master)](https://travis-ci.com/phoole/route)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phoole/route/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phoole/route/?branch=master)
[![Code Climate](https://codeclimate.com/github/phoole/route/badges/gpa.svg)](https://codeclimate.com/github/phoole/route)
[![PHP 7](https://img.shields.io/packagist/php-v/phoole/route)](https://packagist.org/packages/phoole/route)
[![Latest Stable Version](https://img.shields.io/github/v/release/phoole/route)](https://packagist.org/packages/phoole/route)
[![License](https://img.shields.io/github/license/phoole/route)]()

Slim &amp; fast routing library for PHP

Why another routing library ?
---

- [Super fast](#performance) using [fastRoute algorithm](#fastroute) algorithm.

- [Concise route syntax](#syntax). Route parameters and optional route segments.

- Built-in PSR-7 support.

- Out of box PSR-15 middleware.

Installation
---
Install via the `composer` utility.

```bash
composer require "phoole/route"
```

or add the following lines to your `composer.json`

```json
{
    "require": {
       "phoole/route": "1.*"
    }
}
```

<a name="usage"></a>Usage
---

Inject route definitions (pattern, handler, default values etc.) into the
dispatcher and then call either `match()` or `process()`.

```php
use Phoole\Route\Router;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

$router = (new Router())
    ->addGet(
        '/blog/{action:xd}[/{year:d}[/{month:d}[/{date:d}]]]',
        function(ServerRequestInterface $request) {
            $params = $request->getAttribute(Router::URI_PARAMETERS);
            echo "action is " . $params['action'];
        }
    )->addPost(
        '/blog/post',
        'handler2'
    )->addRoute(new Route(
        'GET,HEAD',
        '/blog/read[/{id:d}]',
        'handler3',
        ['id' => '1'] // default values
    ));

// diaptcher (match & execute controller action)
$result = $router->match(new ServerRequest('GET', '/blog/list/2016/05/01'));

if ($result->isMatched()) {
    echo "WOW matched";
}
```

Or load routes from an array,

```php
$routes = [
    ['GET', '/user/{action:xd}/{id:d}', 'handler1', ['id' => 1]],
    [ ... ],
    ...
];
$router = new Router($routes);
```

<a name="syntax"></a>Route syntax
---

- **{Named} parameters**

  A route pattern syntax is used where `{foo}` specifies a named parameter or
  a placeholder with name `foo` and default regex pattern `[^/]++`. In order to
  match more specific types, you may specify a custom regex pattern like
  `{foo:[0-9]+}`.

  ```php
  // with 'action' & 'id' two named params
  $dispatcher->addGet('/user/{action:[^0-9/][^/]*}/{id:[0-9]+}', 'handler1');
  ```

  Predefined shortcuts can be used for placeholders as follows,

  ```php
  ':d}'   => ':[0-9]++}',             // digit only
  ':l}'   => ':[a-z]++}',             // lower case
  ':u}'   => ':[A-Z]++}',             // upper case
  ':a}'   => ':[0-9a-zA-Z]++}',       // alphanumeric
  ':c}'   => ':[0-9a-zA-Z+_\-\.]++}', // common chars
  ':nd}'  => ':[^0-9/]++}',           // not digits
  ':xd}'  => ':[^0-9/][^/]*+}',       // no leading digits
  ```

  The previous pattern can be rewritten into,

  ```php
  // with 'action' & 'id' two named params
  $router->addGet('/user/{action:xd}/{id:d}', 'handler1');
  ```

- **[Optional] segments**

  Optional segments in the route pattern can be specified with `[]` as follows,

  ```php
  // $action, $year/$month/$date are all optional
  $pattern = '/blog[/{action:xd}][/{year:d}[/{month:d}[/{date:d}]]]';
  ```

  where optional segments can be **NESTED**. Unlike other libraries, optional
  segments are not limited to the end of the pattern, as long as it is a valid
  pattern like the `[/{action:xd}]` in the example.

- **Syntax limitations**

  - Parameter name *MUST* start with a character

    Since `{2}` has special meanings in regex. Parameter name *MUST* start with
    a character. And the use of `{}` inside/outside placeholders may cause
    confusion, thus is not recommended.

  - `[]` outside placeholder means *OPTIONAL* segment only

    `[]` can not be used outside placeholders as part of a regex pattern, *IF
    YOU DO NEED* to use them as part of the regex pattern, please include them
    *INSIDE* a placeholder.

  - Use of capturing groups `()` inside placeholders is not allowed

    Capturing groups `()` can not be used inside placeholders. For example
    `{user:(root|phoole)}` is not valid. Instead, you can use either use
    `{user:root|phoole}` or `{user:(?:root|phoole)}`.

- **Default Values**

  Default values can be added to named parameters at the end in the form of
  `{action:xd=list}`. Default values have to be alphanumeric chars. For example,

  ```php
  // $action, $year/$month/$date are all optional
  $pattern = '/blog[/{action:xd=list}][/{year:d=2016}[/{month:d=01}[/{date:d=01}]]]';
  $router->addGet($pattern, 'handler');
  ```

<a name="routes"></a>Routes
---

- **Same route pattern**

  User can define same route pattern with different http methods.

  ```php
  $router
      ->addGet('/user/{$id}', 'handler1')
      ->addPost('/user/{$id}', 'handler2');
  ```

<a name="handler"></a>Handlers
---

- **Handler resolving**

  Most of the time, matching route will return a handler like
  `[ 'ControllerName', 'actionName' ]`. Handler resolver can be used to
  resolving this pseudo handler into a real callable.

  Users may write their own handler resolver by implementing
  `Phoole\Route\Resolver\ResolverInterface`.

<a name="algorithm"></a>Algorithms

- <a name="fastroute"></a>**FastRoute algorithm**

  This *Fast Route algorithm* is implemented in
  `Phoole\Route\Parser\FastRouteParser` class and explained in  detail in this article
  ["Fast request routing using regular expressions"](http://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html).

  `phoole/route` uses this algorithm by default.

- **Comments on routing algorithms**

  - It does **NOT** matter that much as you may think.

    If you are using routing library in your application, different algorithms
    may differ only 0.1 - 0.2ms for a single request, which seems meaningless
    for an application unless you are using it as a standalone router.

  - Try [network routing or server routing](#issue) if you just **CRAZY ABOUT
    THE SPEED**.

Testing
---

```bash
$ composer test
```

Dependencies
---

- PHP >= 7.2.0

Appendix
---

- <a name="issue"></a>**Routing issues**

  Base on the request informations, such as request device, source ip, request
  method etc., service provider may direct request to different hosts, servers,
  app modules or handlers.

  - *Network level routing*

    Common case, such as routing based on request's source ip, routes the
    request to a *NEAREST* server, this is common in content distribution
    network (CDN), and is done at network level.

  - *Web server routing*

    For performance reason, some of the simple routing can be done at web
    server level, such as using apache or ngix configs to do simple routing.

    For example, if your server goes down for maintenance, you may replace
    the `.htaccess` file as follows,

    ```
    DirectorySlash Off
    Options -MultiViews
    DirectoryIndex maintenance.php
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-l
    RewriteRule ^ maintenance.php [QSA,L]
    ```

  - *App level routing*

    It solves much more complicated issues, and much more flexible.

    Usually, routing is done at a single point `index.php`. All the requests
    are configured to be handled by this script first and routed to different
    routines.