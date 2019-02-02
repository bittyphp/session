# Bitty Session

[![Build Status](https://travis-ci.org/bittyphp/session.svg?branch=master)](https://travis-ci.org/bittyphp/session)
[![Codacy Badge](https://api.codacy.com/project/badge/Coverage/4885046e530f4b9e9e4c1376598fc43a)](https://www.codacy.com/app/bittyphp/session)
[![PHPStan Enabled](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)
[![Mutation Score](https://badge.stryker-mutator.io/github.com/bittyphp/session/master)](https://infection.github.io)
[![Total Downloads](https://poser.pugx.org/bittyphp/session/downloads)](https://packagist.org/packages/bittyphp/session)
[![License](https://poser.pugx.org/bittyphp/session/license)](https://packagist.org/packages/bittyphp/session)

Session management with secure defaults.

## Installation

It's best to install using [Composer](https://getcomposer.org/).

```sh
$ composer require bittyphp/session
```

## Setup

Bitty's session management is configured with default values that follow the [Open Web Application Security Project (OWASP)](https://www.owasp.org/index.php/Session_Management_Cheat_Sheet) security recommendations. This includes:

- Defaulting to strict session management (PHP normally defaults to permissive).

- Using non-persistent (or session) cookies.

- Setting `HttpOnly` on cookies to prevent script access and session ID stealing through XSS attacks.

- Setting `SameSite` on cookies to mitigate the risk of cross-origin information leakage (PHP 7.3+).

- Setting `Secure` on cookies if HTTPS is detected. This prevents exposing the session ID over non-secure channels.

- Disabling caching of session data.

You can tweak all of the above as needed, just be mindful of the security risks in doing so.

### Basic Usage

Creating a session is as easy as `new Session()`, but OWASP recommends that you set the cookie name that the session ID gets stored in. Letting PHP use the default `PHPSESSID` allows an attacker to know: 1) that you use PHP, and 2) the value of that cookie would allow them to hijack someone's session.

```php
<?php

use Bitty\Http\Session\Session;

// Use a generic name.
// Something that doesn't scream "this is a session ID."
$cookieName = 'my_site';

$session = new Session(['name' => $cookieName]);
$session->start();

```

### Advanced Usage

You can create a session using any of the [session configuration](http://php.net/manual/en/session.configuration.php) options available, minus the `session.` prefix.

You can also pass in a custom save handler that implements the built-in [`\SessionHandlerInterface`](http://php.net/manual/en/class.sessionhandlerinterface.php).

```php
<?php

use Bitty\Http\Session\Session;

/** @var \SessionHandlerInterface */
$myHandler = ...;

$session = new Session(
    [
        'name' => 'my_site',
        'cookie_domain' => 'example.com',
        'cookie_path' => '/myapp',

        // Open in read-only mode
        'read_and_close' => true,
    ],
    $myHandler
);

```

## Starting Up

Before you store any data in the session, you need to start it. If it fails to start for any reason, it will throw a `\RuntimeException`.

```php
<?php

use Bitty\Http\Session\Session;

$session = new Session(...);
$session->start();

// If you're not sure if it started, you can check:
if ($session->isStarted()) {
    // Yep, it started
}

```

## Storing Data

Storing data is easy.

```php
<?php

use Bitty\Http\Session\Session;

$session = new Session(...);
$session->start();

$session->set('my_data', 'this is important stuff');

```

## Getting Data

Getting data is just as easy. If the data requested isn't found, you can return a default value.

```php
<?php

use Bitty\Http\Session\Session;

$session = new Session(...);
$session->start();

// Get a single item
$foo = $session->get('foo');
$bar = $session->get('bar', null);

// Get everything
$data = $session->all();

```

You can also check if data exists without getting it.

```php
<?php

use Bitty\Http\Session\Session;

$session = new Session(...);
$session->start();

if ($session->has('foo')) {
    // Foo is set
}

```

## Removing Data

You can remove a single item or you can remove everything.

```php
<?php

use Bitty\Http\Session\Session;

$session = new Session(...);
$session->start();

// Remove a single item
$session->remove('my_data');

// Remove everything
$session->clear();

```

## Closing the Session

To prevent session locking issues, you are encouraged to close the session as soon as you're done getting/setting data. However, you're not required to. When the session is created, it automatically registers a shutdown function that will close it for you when the script finishes.

```php
<?php

use Bitty\Http\Session\Session;

$session = new Session(...);
$session->start();

// Do some things...

$session->close();

```

## Advanced

### Regenerate

Whenever a privilege level change happens you should regenerate the session. Most commonly, this should be called during the authentication process. This helps to prevent [session fixation](https://www.owasp.org/index.php/Session_fixation) attacks.

You can optionally destroy the old session during this process. Be careful in doing so, as many modern applications are asynchronous and multiple requests may be getting processed at once. If you destroy the session in one request, you may entirely kill a different request by mistake.

```php
<?php

use Bitty\Http\Session\Session;

$session = new Session(...);
$session->start();

$destroyOldSession = true;

$session->regenerate($destroyOldSession);

```

### Destroy

If you're completely done with a session, such as on a user logout, you can destroy the session and all related data.

```php
<?php

use Bitty\Http\Session\Session;

$session = new Session(...);
$session->start();

// Do some things

$session->destroy();

```
