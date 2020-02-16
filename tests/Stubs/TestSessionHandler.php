<?php

namespace Bitty\Tests\Http\Session\Stubs;

class TestSessionHandler implements \SessionHandlerInterface
{
    /**
     * @var string[]
     */
    private $calls = [];

    /**
     * @param string $savePath
     * @param string $sessionName
     *
     * @return bool
     */
    public function open($savePath, $sessionName)
    {
        $this->calls[] = 'open';

        return true;
    }

    /**
     * @return bool
     */
    public function close()
    {
        $this->calls[] = 'close';

        return true;
    }

    /**
     * @param string $sessionId
     *
     * @return string
     */
    public function read($sessionId)
    {
        $this->calls[] = 'read';

        return '';
    }

    /**
     * @param string $sessionId
     * @param string $sessionData
     *
     * @return bool
     */
    public function write($sessionId, $sessionData)
    {
        $this->calls[] = 'write';

        return true;
    }

    /**
     * @param int $maxLifetime
     *
     * @return bool
     */
    public function gc($maxLifetime)
    {
        $this->calls[] = 'gc';

        return true;
    }

    /**
     * @param string $sessionId
     *
     * @return bool
     */
    public function destroy($sessionId)
    {
        $this->calls[] = 'destroy';

        return true;
    }

    /**
     * @return string[]
     */
    public function getCalls(): array
    {
        return $this->calls;
    }
}
