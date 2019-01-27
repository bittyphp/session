<?php

namespace Bitty\Http\Session;

interface SessionInterface
{
    /**
     * Gets the ID of the session.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Gets the name of the session, e.g. PHPSESSID.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Checks if the session has been started.
     *
     * @return bool
     */
    public function isStarted(): bool;

    /**
     * Starts the session.
     *
     * @return bool
     *
     * @throws \RuntimeException If unable to start the session.
     */
    public function start(): bool;

    /**
     * Closes the session and saves all changes.
     *
     * @return bool
     */
    public function close(): bool;

    /**
     * Regenerates the session, optionally destroying the old one.
     *
     * This should be called after any privilege level change happens, but
     * before any new data gets written. Most commonly, this should be called
     * during the authentication process.
     *
     * @param bool $destroy Whether or not to destroy the old session.
     *
     * @return bool
     *
     * @throws \RuntimeException If session hasn't been started.
     */
    public function regenerate(bool $destroy = false): bool;

    /**
     * Completely destroys the entire session.
     *
     * @return bool
     */
    public function destroy(): bool;

    /**
     * Checks if the given key exists.
     *
     * @param string $key
     *
     * @return bool
     *
     * @throws \RuntimeException If session hasn't been started.
     */
    public function has(string $key): bool;

    /**
     * Gets the given data, if it exists.
     *
     * Returns the default value if nothing is set.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     *
     * @throws \RuntimeException If session hasn't been started.
     */
    public function get(string $key, $default = null);

    /**
     * Sets the given data.
     *
     * @param string $key
     * @param mixed $value
     *
     * @throws \RuntimeException If session hasn't been started.
     */
    public function set(string $key, $value): void;

    /**
     * Removes a given key.
     *
     * @param string $key
     *
     * @throws \RuntimeException If session hasn't been started.
     */
    public function remove(string $key): void;

    /**
     * Removes all the data.
     *
     * @throws \RuntimeException If session hasn't been started.
     */
    public function clear(): void;

    /**
     * Gets all the data.
     *
     * @return mixed[]
     *
     * @throws \RuntimeException If session hasn't been started.
     */
    public function all(): array;

    /**
     * Sets the session handler.
     *
     * @param \SessionHandlerInterface $handler
     *
     * @throws \RuntimeException If session has already started.
     */
    public function setHandler(\SessionHandlerInterface $handler): void;
}
