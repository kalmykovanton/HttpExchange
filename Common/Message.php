<?php

namespace HttpExchange\Common;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use HttpExchange\Common\Helpers\MessageHelper;

/**
 * Class Message.
 * @package HttpExchange\Common
 */
abstract class Message implements MessageInterface
{
    use MessageHelper;

    /**
     * Current HTTP protocol version.
     *
     * @var string
     */
    protected $protocolVersion = '';

    /**
     * List of all headers received
     * from superglobal $_SERVER,
     * as array of key => array with values.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Input or output stream.
     *
     * @var StreamInterface
     */
    protected $stream;

    /**
     * Message constructor.
     */
    protected function __construct()
    {
        // Extract HTTP protocol version from superglobal $_SERVER.
        $this->protocolVersion = str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']);
    }

    /**
    * Retrieves the HTTP protocol version as a string.
    *
    * {@inheritdoc}
    *
    * @return string    HTTP protocol version.
    */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * {@inheritdoc}
     *
     * @param string $version   HTTP protocol version
     * @return self
     */
    public function withProtocolVersion($version)
    {
        // Array with allowed versions of HTTP protocol.
        $allowedHttpVersions = [
            '1.0' => true,
            '1.1' => true,
            '2.0' => true,
            '2' => true
        ];

        if (!isset($allowedHttpVersions[$version])) {
            throw new InvalidArgumentException(
                'Invalid HTTP version. Must be one of: 1.0, 1.1, 2.0, 2.'
            );
        }

        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }

    /**
     * Retrieves all message header values.
     *
     * {@inheritdoc}
     *
     * @return array    Returns an associative array of the message's headers.
     *                  Each key must be a header name, and each value must be
     *                  an array of strings for that header.
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name      Case-insensitive header field name.
     * @return bool             Returns true if any header names match
     *                          the given header. Returns false if
     *                          no matching header name is found
     *                          in the message.
     */
    public function hasHeader($name)
    {
        // Check header name.
        if (!is_string($name)) {
            throw new InvalidArgumentException(
                'Invalid argument. Header name must be a string (e.g., "Host").'
            );
        }

        return (bool) $this->getHeader($name);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * {@inheritdoc}
     *
     * @param string $name      Case-insensitive header field name.
     * @return string[]         An array of string values as provided for the given
     *                          header. If the header does not appear in the message,
     *                          this method return an empty array.
     */
    public function getHeader($name)
    {
        // Check header name.
        if (!is_string($name)) {
            throw new InvalidArgumentException(
                'Invalid argument. Header name must be a string (e.g., "Host").'
            );
        }

        // Normalize header name.
        $name = $this->normalizeHeaderName($name);

        return (array_key_exists($name, $this->headers)) ? $this->headers[$name] : [];
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * {@inheritdoc}
     *
     * @param string $name      Case-insensitive header field name.
     * @return string           A string of values as provided for the given header
     *                          concatenated together using a comma.
     *                          If the header does not appear in the message,
     *                          this method return an empty string.
     */
    public function getHeaderLine($name)
    {
        // Check header name.
        if (!is_string($name)) {
            throw new InvalidArgumentException(
                'Invalid argument. Header name must be a string (e.g., "Host").'
            );
        }

        return implode(', ', $this->getHeader($name));
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * {@inheritdoc}
     *
     * @param string $name              Case-insensitive header field name.
     * @param string|string[] $value    Header value(s).
     * @return self                     Return an instance that has the new
     *                                  and/or updated header and value
     *
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value)
    {
        // Check header name.
        if (!is_string($name)) {
            throw new InvalidArgumentException(
                'Invalid argument. Header name must be a string (e.g., "Host").'
            );
        }

        // Check header value.
        if (is_string($value)) {
            $value = [$value];
        }

        if (!is_array($value) || !$this->arrayContainsOnlyStrings($value)) {
            throw new InvalidArgumentException(
                'Invalid header value. Header value must be a string or an array'
            );
        }

        // Normalize header name.
        $name = $this->normalizeHeaderName($name);

        // New instance.
        $clone = clone $this;

        $clone->headers[$name] = $value;
        return $clone;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * {@inheritdoc}
     *
     * @param string $name              Case-insensitive header field name to add.
     * @param string|string[] $value    Header value(s).
     * @return self
     *
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value)
    {
        // Check header name.
        if (!is_string($name)) {
            throw new InvalidArgumentException(
                'Invalid argument. Header name must be a string (e.g., "Host").'
            );
        }

        // Check header value.
        if (is_string($value)) {
            $value = [$value];
        }

        if (!is_array($value) || !$this->arrayContainsOnlyStrings($value)) {
            throw new InvalidArgumentException(
                'Invalid header value. Header value must be a string or an array.'
            );
        }

        // Normalize header name.
        $name = $this->normalizeHeaderName($name);

        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }

        // New instance.
        $clone = clone $this;

        $clone->headers[$name] = array_merge($this->headers[$name], $value);
        return $clone;
    }

    /**
     * Return an instance without the specified header.
     *
     * {@inheritdoc}
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return self
     */
    public function withoutHeader($name)
    {
        // Check header name.
        if (!is_string($name)) {
            throw new InvalidArgumentException(
                'Invalid argument. Header name must be a string (e.g., "Host").'
            );
        }

        // Normalize header name.
        $name = $this->normalizeHeaderName($name);

        if (!$this->hasHeader($name)) {
            return clone $this;
        }

        // New instance.
        $clone = clone $this;

        unset($clone->headers[$name]);
        return $clone;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody()
    {
        return $this->stream;
    }

    /**
     * Return an instance with the specified message body.
     *
     * {@inheritdoc}
     *
     * @param StreamInterface $body         Body.
     * @return self
     * @throws \InvalidArgumentException    When the body is not valid.
     */
    public function withBody(StreamInterface $body)
    {
        $clone = clone $this;
        $clone->stream = $body;
        return $clone;
    }
}
