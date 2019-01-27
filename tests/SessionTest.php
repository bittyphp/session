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
    protected $fixture = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixture = new Session();
    }

    public function testInstanceOf(): void
    {
        self::assertInstanceOf(SessionInterface::class, $this->fixture);
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
}
