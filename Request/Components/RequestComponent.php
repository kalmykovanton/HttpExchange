<?php

namespace HttpExchange\Request\Components;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;
use HttpExchange\Common\Message;

/**
 * Class RequestComponent.
 * @package HttpExchange\Request\Components
 */
abstract class RequestComponent extends Message implements RequestInterface
{
    /**
     * The request-target.
     * In most cases, this will be
     * the origin-form of the composed URI.
     *
     * @var null|string
     */
    protected $requestTarget;

    /**
     * Real request HTTP method.
     *
     * @var string
     */
    protected $realMethod = '';

    /**
     * Replaced request HTTP method.
     *
     * @var string
     */
    protected $replacedMethod = '';

    /**
     * Contain UriInterface instance.
     * Created in Request __construct().
     *
     * @var null|UriInterface
     */
    protected $uri;

    /**
     * Retrieves the message's request target.
     *
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRequestTarget()
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        if (! $this->uri) {
            return '/';
        }

        $target = $this->uri->getPath();
        if ($this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        if (empty($target)) {
            $target = '/';
        }

        return $target;
    }

    /**
     * Return an instance with the specific request-target.
     *
     * {@inheritdoc}
     *
     * @see http://tools.ietf.org/html/rfc7230#section-2.7 (for the various
     *     request-target forms allowed in request messages)
     * @param mixed $requestTarget
     * @return self
     */
    public function withRequestTarget($requestTarget)
    {
        if (! is_string($requestTarget)) {
            throw new InvalidArgumentException(
                'Request target must be a string.'
            );
        }
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException(
                'Request target cannot contain whitespaces.'
            );
        }

        $clone = clone $this;
        $clone->requestTarget = $requestTarget;
        return $clone;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod()
    {
        return $this->realMethod;
    }

    /**
     * Retrieves the HTTP replaced method of the request.
     *
     * NOTE: This method is not a part of PSR-7 recommendations.
     *
     * Usually, by specifying attributes name="_method" and
     * value="PUT" (or else valid method) at html form 'input'
     * fileds.
     *
     * @return string   Returns the replaced request method.
     */
    public function getReplacedMethod()
    {
        return $this->replacedMethod;
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * {@inheritdoc}
     *
     * @param string $method    Case-sensitive method.
     * @return self
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method)
    {
        $this->validateMethod($method);
        $clone = clone $this;
        $clone->realMethod = $method;
        return $clone;
    }

    /**
     * Retrieves the URI instance.
     *
     * {@inheritdoc}
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface     Returns a UriInterface instance
     *                          representing the URI of the request.
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * {@inheritdoc}
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri     New request URI to use.
     * @param bool $preserveHost    Preserve the original state of the Host header.
     * @return self
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $clone = clone $this;
        $clone->uri = $uri;

        if ($preserveHost && $this->hasHeader('Host')) {
            return $clone;
        }

        if (! $uri->getHost()) {
            return $clone;
        }

        $host = $uri->getHost();
        if ($uri->getPort()) {
            $host .= ':' . $uri->getPort();
        }

        // Remove an existing host header if present
        foreach (array_keys($clone->headers) as $header) {
            if (strtolower($header) === 'host') {
                unset($clone->headers[$header]);
            }
        }

        $clone->headers['Host'] = [$host];

        return $clone;
    }
}
