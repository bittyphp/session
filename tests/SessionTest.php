<?php

namespace Bitty\Tests\Http\Session;

use Bitty\Http\Session\Session;
use Bitty\Http\Session\SessionInterface;
use Bitty\Tests\Http\Session\Stubs\TestSessionHandler;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    /**
     * @var Session
     */
    private $fixture = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixture = new Session();
    }

    public function testInstanceOf(): void
    {
        self::assertInstanceOf(SessionInterface::class, $this->fixture);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDefaultOptionsSet(): void
    {
        $this->fixture->start();

        self::assertEquals('1', ini_get('session.use_strict_mode'), 'incorrect use_strict_mode');
        self::assertEquals('0', ini_get('session.use_trans_sid'), 'incorrect use_trans_sid');
        self::assertEquals('1', ini_get('session.use_cookies'), 'incorrect use_cookies');
        self::assertEquals('1', ini_get('session.use_only_cookies'), 'incorrect use_only_cookies');
        self::assertEquals('1', ini_get('session.cookie_httponly'), 'incorrect cookie_httponly');
        self::assertEquals('0', ini_get('session.cookie_secure'), 'incorrect cookie_secure');
        self::assertEquals('0', ini_get('session.cookie_lifetime'), 'incorrect cookie_lifetime');
        self::assertEquals('nocache', ini_get('session.cache_limiter'), 'incorrect cache_limiter');
        self::assertEquals('0', ini_get('session.cache_expire'), 'incorrect cache_expire');
        self::assertEquals('1', ini_get('session.lazy_write'), 'incorrect lazy_write');
        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            self::assertEquals('Strict', ini_get('session.cookie_samesite'), 'incorrect cookie_samesite');
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function testCustomOptionsSet(): void
    {
        $options = [
            'use_strict_mode' => 0,
            'use_trans_sid' => 1,
            'use_cookies' => 0,
            'use_only_cookies' => 0,
            'cookie_httponly' => 0,
            'cookie_secure' => 1,
            'cookie_lifetime' => 1,
            'cache_limiter' => '',
            'cache_expire' => 30,
            'lazy_write' => 0,
        ];
        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            $options['cookie_samesite'] = 'Lax';
        }

        $this->fixture = new Session($options);
        ob_start();
        $this->fixture->start();
        ob_get_clean();
        ob_end_flush();

        self::assertEquals('0', ini_get('session.use_strict_mode'), 'incorrect use_strict_mode');
        self::assertEquals('1', ini_get('session.use_trans_sid'), 'incorrect use_trans_sid');
        self::assertEquals('0', ini_get('session.use_cookies'), 'incorrect use_cookies');
        self::assertEquals('0', ini_get('session.use_only_cookies'), 'incorrect use_only_cookies');
        self::assertEquals('0', ini_get('session.cookie_httponly'), 'incorrect cookie_httponly');
        self::assertEquals('1', ini_get('session.cookie_secure'), 'incorrect cookie_secure');
        self::assertEquals('1', ini_get('session.cookie_lifetime'), 'incorrect cookie_lifetime');
        self::assertEquals('', ini_get('session.cache_limiter'), 'incorrect cache_limiter');
        self::assertEquals('30', ini_get('session.cache_expire'), 'incorrect cache_expire');
        self::assertEquals('0', ini_get('session.lazy_write'), 'incorrect lazy_write');
        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            self::assertEquals('Lax', ini_get('session.cookie_samesite'), 'incorrect cookie_samesite');
        }
    }

    public function testGetIdWhenNotStarted(): void
    {
        $actual = $this->fixture->getId();

        self::assertEquals('', $actual);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetIdWhenStarted(): void
    {
        $this->fixture->start();

        $actual = $this->fixture->getId();

        self::assertNotEquals('', $actual);
    }

    /**
     * @param mixed[] $options
     * @param bool $started
     * @param string $expected
     *
     * @runInSeparateProcess
     * @dataProvider sampleNames
     */
    public function testGetName(array $options, bool $started, string $expected): void
    {
        $this->fixture = new Session($options);
        if ($started) {
            $this->fixture->start();
        }

        $actual = $this->fixture->getName();

        self::assertEquals($expected, $actual);
    }

    public function sampleNames(): array
    {
        $name = uniqid();

        return [
            'not started, no name' => [
                'options' => [],
                'started' => false,
                'expected' => 'PHPSESSID',
            ],
            'not started, with name' => [
                'options' => ['name' => $name],
                'started' => false,
                'expected' => 'PHPSESSID',
            ],
            'started, no name' => [
                'options' => [],
                'started' => true,
                'expected' => 'PHPSESSID',
            ],
            'started, with name' => [
                'options' => ['name' => $name],
                'started' => true,
                'expected' => $name
            ],
        ];
    }

    public function testIsStartWhenNotStarted(): void
    {
        $actual = $this->fixture->isStarted();

        self::assertFalse($actual);
    }

    /**
     * @runInSeparateProcess
     */
    public function testIsStartWhenStarted(): void
    {
        $this->fixture->start();

        $actual = $this->fixture->isStarted();

        self::assertTrue($actual);
    }

    /**
     * @runInSeparateProcess
     */
    public function testStartWhenStarted(): void
    {
        $this->fixture->start();

        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Session has already been started.');

        $this->fixture->start();
    }

    /**
     * @runInSeparateProcess
     */
    public function testStartFail(): void
    {
        $level = error_reporting();
        error_reporting(0);

        $this->fixture = new Session(['save_path' => uniqid('invalid')]);

        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Unable to start session.');

        $this->fixture->start();

        error_reporting($level);
    }

    /**
     * @runInSeparateProcess
     */
    public function testStart(): void
    {
        $actual = $this->fixture->start();

        self::assertTrue($actual);
    }

    public function testRegenerateWhenNotStarted(): void
    {
        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Session has not been started.');

        $this->fixture->regenerate();
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegenerateWhenStarted(): void
    {
        $destroy = (bool) rand(0, 1);

        $this->fixture->start();

        $actual = $this->fixture->regenerate($destroy);

        self::assertTrue($actual);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegenerateDefaultsToNoDestroy(): void
    {
        $handler = new TestSessionHandler();
        $this->fixture->setHandler($handler);

        $this->fixture->start();
        $this->fixture->regenerate();

        $actual = $handler->getCalls();

        self::assertContains('open', $actual);
        self::assertContains('read', $actual);
        self::assertContains('write', $actual);
        self::assertContains('close', $actual);
        self::assertNotContains('destroy', $actual);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegenerateAndDestroy(): void
    {
        $handler = new TestSessionHandler();
        $this->fixture->setHandler($handler);

        $this->fixture->start();
        $this->fixture->regenerate(true);

        $actual = $handler->getCalls();

        self::assertContains('open', $actual);
        self::assertContains('read', $actual);
        self::assertContains('destroy', $actual);
        self::assertContains('close', $actual);
        self::assertNotContains('write', $actual);
    }

    public function testCloseWhenNotStarted(): void
    {
        $actual = $this->fixture->close();

        self::assertFalse($actual);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCloseWhenStarted(): void
    {
        $this->fixture->start();

        $actual = $this->fixture->close();

        self::assertTrue($actual);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCloseWritesAndCloses(): void
    {
        $handler = new TestSessionHandler();
        $this->fixture->setHandler($handler);

        $this->fixture->start();
        $this->fixture->close();

        $actual = $handler->getCalls();

        self::assertContains('open', $actual);
        self::assertContains('read', $actual);
        self::assertContains('write', $actual);
        self::assertContains('close', $actual);
    }

    public function testDestroyWhenNotStarted(): void
    {
        $actual = $this->fixture->destroy();

        self::assertFalse($actual);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDestroyWhenStarted(): void
    {
        $this->fixture->start();

        $actual = $this->fixture->destroy();

        self::assertTrue($actual);
        self::assertFalse($this->fixture->isStarted());
    }

    /**
     * @runInSeparateProcess
     */
    public function testDestroyClearsData(): void
    {
        $this->fixture->start();
        $this->fixture->set(uniqid(), uniqid());

        $this->fixture->destroy();

        self::assertEquals([], $_SESSION);
    }

    public function testHasWhenNotStarted(): void
    {
        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Session has not been started.');

        $this->fixture->has(uniqid());
    }

    /**
     * @runInSeparateProcess
     */
    public function testHasWhenStarted(): void
    {
        $key = uniqid();

        $this->fixture->start();
        $this->fixture->set($key, uniqid());

        self::assertTrue($this->fixture->has($key));
        self::assertFalse($this->fixture->has(uniqid()));
    }

    public function testGetWhenNotStarted(): void
    {
        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Session has not been started.');

        $this->fixture->get(uniqid());
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetWhenStartedAndSet(): void
    {
        $key   = uniqid();
        $value = uniqid();

        $this->fixture->start();
        $this->fixture->set($key, $value);

        $actual = $this->fixture->get($key, uniqid());

        self::assertEquals($value, $actual);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetWhenStartedAndNotSet(): void
    {
        $default = uniqid();

        $this->fixture->start();

        $actual = $this->fixture->get(uniqid(), $default);

        self::assertEquals($default, $actual);
    }

    public function testSetWhenNotStarted(): void
    {
        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Session has not been started.');

        $this->fixture->set(uniqid(), uniqid());
    }

    public function testRemoveWhenNotStarted(): void
    {
        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Session has not been started.');

        $this->fixture->remove(uniqid());
    }

    /**
     * @runInSeparateProcess
     */
    public function testRemoveWhenStartedAndSet(): void
    {
        $key     = uniqid();
        $default = uniqid();

        $this->fixture->start();
        $this->fixture->set($key, uniqid());

        $this->fixture->remove($key);

        $actual = $this->fixture->get($key, $default);

        self::assertEquals($default, $actual);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRemoveWhenStartedAndNotSet(): void
    {
        $this->fixture->start();

        try {
            $this->fixture->remove(uniqid());
        } catch (\Exception $e) {
            self::fail();
        }

        self::assertTrue(true);
    }

    public function testClearWhenNotStarted(): void
    {
        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Session has not been started.');

        $this->fixture->clear();
    }

    /**
    * @runInSeparateProcess
    */
    public function testClearWhenStarted(): void
    {
        $this->fixture->start();
        $this->fixture->set(uniqid(), uniqid());

        $this->fixture->clear();

        $actual = $this->fixture->all();

        self::assertEquals([], $actual);
    }

    public function testAllWhenNotStarted(): void
    {
        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Session has not been started.');

        $this->fixture->all();
    }

    /**
    * @runInSeparateProcess
    */
    public function testAllWhenStarted(): void
    {
        $keyA   = uniqid('a');
        $keyB   = uniqid('b');
        $valueA = uniqid('a');
        $valueB = uniqid('b');

        $this->fixture->start();
        $this->fixture->set($keyA, $valueA);
        $this->fixture->set($keyB, $valueB);

        $actual = $this->fixture->all();

        self::assertEquals([$keyA => $valueA, $keyB => $valueB], $actual);
    }

    /**
    * @runInSeparateProcess
    */
    public function testSetHandlerWhenNotStarted(): void
    {
        $handler = new TestSessionHandler();

        $this->fixture->setHandler($handler);

        $this->fixture->start();

        $actual = $handler->getCalls();

        self::assertContains('open', $actual);
        self::assertContains('read', $actual);
    }

    /**
    * @runInSeparateProcess
    */
    public function testSetHandlerWhenStarted(): void
    {
        $handler = new TestSessionHandler();

        $this->fixture->start();

        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Session has already been started.');

        $this->fixture->setHandler($handler);
    }

    /**
    * @runInSeparateProcess
    */
    public function testSetHandlerInConstructor(): void
    {
        $handler = new TestSessionHandler();

        $this->fixture = new Session([], $handler);
        $this->fixture->start();

        $actual = $handler->getCalls();

        self::assertContains('open', $actual);
        self::assertContains('read', $actual);
    }

    /**
     * @runInSeparateProcess
     */
    public function testShutdown(): void
    {
        $handler = new TestSessionHandler();
        $this->fixture->setHandler($handler);
        $this->fixture->start();

        register_shutdown_function(function () use ($handler) {
            $actual = $handler->getCalls();
            self::assertContains('write', $actual);
            self::assertContains('close', $actual);
        });

        $actual = $handler->getCalls();

        self::assertContains('open', $actual);
        self::assertContains('read', $actual);
    }

    /**
     * @runInSeparateProcess
     */
    public function testShutdownFunctionRegistered(): void
    {
        $handler = new TestSessionHandler();
        session_set_save_handler($handler, false);

        $this->fixture->start();

        register_shutdown_function(function () use ($handler) {
            $actual = $handler->getCalls();
            self::assertContains('write', $actual);
            self::assertContains('close', $actual);
        });

        $actual = $handler->getCalls();

        self::assertContains('open', $actual);
        self::assertContains('read', $actual);
    }
}
