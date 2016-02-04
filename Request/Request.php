<?php

namespace HttpExchange\Request;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use HttpExchange\Request\Components\ServerRequestComponent;
use HttpExchange\Request\Helpers\RequestHelper;

/**
 * Class Request.
 * @package HttpExchange\Request
 */
class Request extends ServerRequestComponent
{
    use RequestHelper;

    /**
     * Contain server environment from PHP's
     * superglobal $_SERVER.
     *
     * @var array
     */
    protected $serverEnv = [];

    /**
     * Request constructor.
     *
     * Example: $request = new Request(new Stream('php://input', 'rb'), new Uri());.
     *
     * @param StreamInterface $stream
     * @param UriInterface $uri
     */
    public function __construct(StreamInterface $stream, UriInterface $uri)
    {
        parent::__construct();
        // Server environment.
        $this->serverEnv = $this->normalizeServer($_SERVER);
        // Headers.
        $this->headers = $this->normalizeHeaders($this->serverEnv);
        // URI.
        $this->uri = $this->createUriFromGlobals($uri);
        // Row stream.
        $this->stream = $stream;
        // HTTP real method.
        $this->realMethod = $this->getFromServer('REQUEST_METHOD');
        // Parsed body.
        $this->parsedBody = $this->handleBody();
        // HTTP replaced method (by html form input field '_method').
        $this->replacedMethod = strtoupper($this->input('_method'));
        // Get params if present.
        $this->queryParams = (isset($_GET)) ? $_GET : [];
        // Cookie if present.
        $this->cookies = (isset($_COOKIE)) ? $_COOKIE : [];
        // Uploaded files if present.
        $this->uploadedFiles = (isset($_FILES)) ? $this->normalizeUploadedFiles($_FILES) : [];
    }

    /**
     * Checks whether the data transferred via the Ajax.
     *
     * NOTE: This method is not a part of PSR-7 recommendations.
     *
     * @return bool     If Ajax returns true, else false.
     */
    public function isAjax()
    {
        if (
            $this->hasHeader('X-Requested-With')
            && strtolower($this->getHeaderLine('X-Requested-With')) === 'xmlhttprequest'
        ) {
            return true;
        }
        return false;
    }

    /**
     * Using this method, you may access all user input,
     * like query params and parsed body params (POST, json or other).
     * You may pass a default value as the second argument.
     * This value will be returned if the requested input value is not present.
     *
     * NOTE: This method is not a part of PSR-7 recommendations.
     *
     * @param $name
     * @param null $default
     * @return string
     */
    public function input($name, $default = null)
    {
        if (! is_string($name) && ! is_integer($name)) {
            throw new InvalidArgumentException(
                'Invalid argument. Input name must be a string or number.'
            );
        }

        if (array_key_exists($name, (array) $this->getQueryParams())) {
            return $this->getQueryParams()[$name];
        }

        if (array_key_exists($name, (array) $this->getParsedBody())) {
            return $this->getParsedBody()[$name];
        }

        if (! is_null($default)) {
            return $default;
        }

        return '';
    }

    /**
     * Looking for a given value
     * in PHP's superglobal $_SERVER.
     *
     * @param string $value
     * @return string
     */
    public function getFromServer($value = '')
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Given value must be a string.');
        }

        $value = str_replace('-', '_', strtoupper($value));

        return (isset($this->serverEnv[$value])) ? $this->serverEnv[$value] : '';
    }

    /**
     * This is dummy method.
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        // Dummy act.
    }
}
