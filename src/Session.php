<?php

namespace Bitty\Http\Session;

use Bitty\Http\Session\SessionInterface;

class Session implements SessionInterface
{
    /**
     * @var mixed[]
     */
    private $options = [];

    /**
     * This sets up a session with defaults that follow OWASP's guide for
     * session management. For enhanced security, some of these defaults differ
     * from PHP's defaults and may not match your server settings.
     *
     * At a minimum, a 'name' option should be provided. It is not secure to use
     * the default 'PHPSESSID' value.
     *
     * If you're not changing any data, you can set 'read_and_close' to `true`
     * to close the session immediately after loading data from it. This can
     * help prevent locking issues.
     *
     * @see http://php.net/manual/en/session.configuration.php
     * @see https://www.owasp.org/index.php/Session_Management_Cheat_Sheet
     *
     * @param mixed[] $options Valid options for session_start(). See manual.
     * @param \SessionHandlerInterface|null $handler
     */
    public function __construct(array $options = [], \SessionHandlerInterface $handler = null)
    {
        $defaults = [
            // Mandatory for general session security
            // Set by default based on OWASP guidelines
            'use_strict_mode' => 1,

            // Session ID should come from the server, not the client
            // Set by default based on OWASP guidelines
            'use_trans_sid' => 0,

            // Use a cookie to give the client the session ID
            'use_cookies' => 1,

            // Disallow passing the session ID in URLs
            'use_only_cookies' => 1,

            // Block script access to the cookie
            // Set by default based on OWASP guidelines
            'cookie_httponly' => 1,

            // Restrict cookie to HTTPS channels
            // Set by default based on OWASP guidelines
            'cookie_secure' => (int) $this->isSecure(),

            // Keep until the browser is closed
            // Set by default based on OWASP guidelines
            'cookie_lifetime' => 0,

            // Mitigate the risk of cross-origin information leakage
            // Set by default based on OWASP guidelines
            'cookie_samesite' => 'Strict',

            // Prevent caching of possibly sensitive session data
            // Set by default based on OWASP guidelines
            'cache_limiter' => 'nocache',

            // Prevent caching of possibly sensitive session data
            // Set by default based on OWASP guidelines
            'cache_expire' => 0,

            // Session data is only rewritten if it changes
            'lazy_write' => 1,
        ];

        if (version_compare(PHP_VERSION, '7.3.0', '<')) {
            unset($defaults['cookie_samesite']);
        }

        $this->options = array_merge($defaults, $options);

        session_register_shutdown();

        if ($handler) {
            $this->setHandler($handler);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return session_id();
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return session_name();
    }

    /**
     * {@inheritDoc}
     */
    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * {@inheritDoc}
     */
    public function start(): bool
    {
        if ($this->isStarted()) {
            throw new \RuntimeException('Session has already been started.');
        }

        if (!session_start($this->options)) {
            throw new \RuntimeException('Unable to start session.');
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function regenerate(bool $destroy = false): bool
    {
        if (!$this->isStarted()) {
            throw new \RuntimeException('Session has not been started.');
        }

        return session_regenerate_id($destroy);
    }

    /**
     * {@inheritDoc}
     */
    public function close(): bool
    {
        if (!$this->isStarted()) {
            return false;
        }

        session_write_close();

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(): bool
    {
        if (!$this->isStarted()) {
            return false;
        }

        $status = session_destroy();

        $_SESSION = [];

        return $status;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        if (!$this->isStarted()) {
            throw new \RuntimeException('Session has not been started.');
        }

        return isset($_SESSION[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, $default = null)
    {
        if (!$this->isStarted()) {
            throw new \RuntimeException('Session has not been started.');
        }

        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        return $default;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, $value): void
    {
        if (!$this->isStarted()) {
            throw new \RuntimeException('Session has not been started.');
        }

        $_SESSION[$key] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $key): void
    {
        if (!$this->isStarted()) {
            throw new \RuntimeException('Session has not been started.');
        }

        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        if (!$this->isStarted()) {
            throw new \RuntimeException('Session has not been started.');
        }

        $_SESSION = [];
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        if (!$this->isStarted()) {
            throw new \RuntimeException('Session has not been started.');
        }

        return $_SESSION;
    }

    /**
     * {@inheritDoc}
     */
    public function setHandler(\SessionHandlerInterface $handler): void
    {
        if ($this->isStarted()) {
            throw new \RuntimeException('Session has already been started.');
        }

        session_set_save_handler($handler);
    }

    /**
     * Checks if the server is running HTTPS.
     *
     * @return bool
     */
    private function isSecure(): bool
    {
        return !empty($_SERVER['HTTPS']) && 'off' !== strtolower($_SERVER['HTTPS']);
    }
}
