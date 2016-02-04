<?php

namespace HttpExchange\Request;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use HttpExchange\Request\Helpers\UriHelper;

/**
 * Class Uri.
 * @package HttpExchange\Request
 */
class Uri implements UriInterface
{
    use UriHelper;

    /**
     * HTTP scheme.
     *
     * @var string
     */
    protected $scheme = '';

    /**
     * Host name.
     *
     * @var string
     */
    protected $host = '';

    /**
     * Port.
     *
     * @var int
     */
    protected $port;

    /**
     * Path.
     *
     * @var string
     */
    protected $path = '';

    /**
     * Query string.
     *
     * @var string
     */
    protected $query = '';

    /**
     * Fragment.
     *
     * @var string
     */
    protected $fragment = '';

    /**
     * User info.
     *
     * @var string
     */
    protected $userInfo = '';

    /**
     * Uri constructor.
     *
     * @param string $uriString     Row URI string
     */
    public function __construct($uriString = '')
    {
        if (! is_string($uriString)) {
            throw new InvalidArgumentException('URI must be a string.');
        }

        if (! empty($uri)) {
            $this->splitUriString($uriString);
        }
    }

    /**
     * Retrieve the scheme component of the URI.
     *
     * {@inheritdoc}
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme.
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * {@inheritdoc}
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string       The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority()
    {
        if (empty($this->host)) {
            return '';
        }

        $authority = $this->host;
        if (! empty($this->userInfo)) {
            $authority = $this->userInfo . '@' . $authority;
        }

        $authority .= ($this->getPort()) ? ':' . $this->getPort() : '';

        return $authority;
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * {@inheritdoc}
     *
     * @return string       The URI user information,
     *                      in "username[:password]" format.
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * Retrieve the host component of the URI.
     *
     * {@inheritdoc}
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string       The URI host.
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Retrieve the port component of the URI.
     *
     * {@inheritdoc}
     *
     * @return null|int     The URI port.
     */
    public function getPort()
    {
        if (! isset($this->port) && ! isset($this->scheme)) {
            return null;
        }

        if (! isset($this->port) && isset($this->scheme)) {
            return null;
        }

        if (isset($this->port)
            && (int) $this->allowedSchemes[$this->scheme] !== (int) $this->port
        ) {
            return (int) $this->port;
        }

        if (
            array_key_exists($this->scheme, $this->allowedSchemes)
            && (int) $this->allowedSchemes[$this->scheme] === (int) $this->port
        ) {
            return null;
        }
    }

    /**
     * Retrieve the path component of the URI.
     *
     * {@inheritdoc}
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     *
     * @return string       The URI path.
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Retrieve the query string of the URI.
     *
     * {@inheritdoc}
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     *
     * @return string       The URI query string.
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Retrieve the fragment component of the URI.
     *
     * {@inheritdoc}
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     *
     * @return string       The URI fragment.
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Return an instance with the specified scheme.
     *
     * {@inheritdoc}
     *
     * @param string $scheme    The scheme to use with the new instance.
     * @return self             A new instance with the specified scheme.
     *
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme)
    {
        if (!is_string($scheme)) {
            throw new InvalidArgumentException('HTTP scheme value must be a string.');
        }

        $scheme = $this->filterScheme($scheme);

        $clone = clone $this;
        $clone->scheme = $scheme;

        return $clone;
    }

    /**
     * Return an instance with the specified user information.
     *
     * {@inheritdoc}
     *
     * @param string $user              The user name to use for authority.
     * @param null|string $password     The password associated with $user.
     *
     * @return self A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null)
    {
        if (! is_string($user)) {
            throw new InvalidArgumentException('User name must be a string.');
        }
        if ($password !== null  && ! is_string($password)) {
            throw new InvalidArgumentException('User password must be a string.');
        }

        $info = $user;
        if ($password) {
            $info .= ':' . $password;
        }

        $clone = clone $this;
        $clone->userInfo = $info;

        return $clone;
    }

    /**
     * Return an instance with the specified host.
     *
     * {@inheritdoc}
     *
     * @param string $host      The hostname to use with the new instance.
     * @return self             A new instance with the specified host.
     *
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host)
    {
        if (!is_string($host)) {
            throw new InvalidArgumentException('Given value must be a string.');
        }

        $valid = $this->checkHost($host);

        if (!$valid) {
            throw new InvalidArgumentException(
                'Given value not valid host name.
                See https://tools.ietf.org/html/rfc952, https://tools.ietf.org/html/rfc1123'
            );
        }

        $clone = clone $this;
        $clone->host = $host;

        return $clone;
    }

    /**
     * Return an instance with the specified port.
     *
     * {@inheritdoc}
     *
     * @param null|int $port    The port to use with the new instance;
     *                          a null value removes the port information.
     * @return self             A new instance with the specified port.
     *
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port)
    {
        if (!is_numeric($port) && !is_null($port)) {
            throw new InvalidArgumentException(
                'Invalid port specified; must be an integer, an integer string, or null.'
            );
        }

        if ($port !== null) {
            $port = (int) $port;
        }

        if ($port === 'null') {
            $port = null;
        }

        if ($port !== null && $port < 1 || $port > 65535) {
            throw new InvalidArgumentException(
                'Invalid port specified; must be a valid TCP/UDP port.
                See: https://tools.ietf.org/html/rfc6335.'
            );
        }

        $clone = clone $this;
        $clone->port = $port;

        return $clone;
    }

    /**
     * Return an instance with the specified path.
     *
     * {@inheritdoc}
     *
     * @param string $path      The path to use with the new instance.
     * @return self             A new instance with the specified path.
     *
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath($path)
    {
        if (! is_string($path)) {
            throw new InvalidArgumentException(
                'Invalid path provided; must be a string'
            );
        }

        if (strpos($path, '?') !== false) {
            throw new InvalidArgumentException(
                'Invalid path provided; must not contain a query string'
            );
        }

        if (strpos($path, '#') !== false) {
            throw new InvalidArgumentException(
                'Invalid path provided; must not contain a URI fragment'
            );
        }

        // Filters the path
        $path = $this->urlCharEncode('path', $path);

        $clone = clone $this;
        $clone->path = $path;
        return $clone;
    }

    /**
     * Return an instance with the specified query string.
     *
     * {@inheritdoc}
     *
     * @param string $query     The query string to use with the new instance.
     * @return self             A new instance with the specified query string.
     *
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query)
    {
        if (! is_string($query)) {
            throw new InvalidArgumentException(
                'Query string must be a string'
            );
        }

        if (strpos($query, '#') !== false) {
            throw new InvalidArgumentException(
                'Query string must not include a URI fragment'
            );
        }

        $query = $this->filterQuery($query);

        $clone = clone $this;
        $clone->query = $query;

        return $clone;
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * {@inheritdoc}
     *
     * @param string $fragment      The fragment to use with the new instance.
     * @return self                 A new instance with the specified fragment.
     *
     * @throws \InvalidArgumentException for invalid fragment.
     */
    public function withFragment($fragment)
    {
        if (! is_string($fragment)) {
            throw new InvalidArgumentException('Fragment must be a string.');
        }

        $fragment = $this->filterFragment($fragment);

        $clone = clone $this;
        $clone->fragment = $fragment;

        return $clone;
    }

    /**
     * Return the string representation as a URI reference.
     *
     * {@inheritdoc}
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     * @return string
     */
    public function __toString()
    {
        $uriString = '';
        // Scheme.
        $uriString .= (! empty($this->getScheme())) ? $this->getScheme() . '://' : '';
        // Authority.
        $uriString .= (! empty($this->getAuthority())) ? $this->getAuthority() : '';
        // Path.
        $path = $this->getPath();
        if ($path) {
            if (empty($path) || '/' !== substr($path, 0, 1)) {
                $path = '/' . $path;
            }
            $uriString .= $path;
        }
        // Query string.
        $query = $this->getQuery();
        if ($query) {
            $uriString .= sprintf('?%s', $query);
        }
        // Fragment.
        $fragment = $this->getFragment();
        if ($fragment) {
            $uriString .= sprintf('#%s', $fragment);
        }

        return $uriString;
    }

    /**
     * This is dummy method,
     * it is not part of the PSR-7: HTTP message interfaces.
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        // Dummy act.
    }
}
