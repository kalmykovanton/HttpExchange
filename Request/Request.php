<?php

namespace HttpExchange\Request;

use \InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use HttpExchange\Request\MethodHandlers\AbstractHandler;
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
     * super global $_SERVER.
     *
     * @var array
     */
    protected $serverEnv = [];

    /**
     * Stores information about the
     * method handlers of HTTP request.
     *
     * @var array
     */
    protected $methodHandlers = [];

    /**
     * UploadedFile instance.
     * 
     * @var object
     */
    protected $uploadedFile;

    /**
     * Request constructor.
     *
     * Array of handlers for HTTP methods may look like this:
     * $methodHandlers = [
     *                     methodHandlerObject,
     *                     anotherMethodHandlerObject
     *                   ];
     *
     * @param StreamInterface $stream               Object which contains incoming stream.
     * @param UriInterface $uri                     Object which contains URI parts.
     * @param UploadedFileInterface $uploadedFile   UploadedFile instance.
     * @param array $methodHandlers                 Handlers for HTTP methods.
     */
    public function __construct(
        StreamInterface $stream, 
        UriInterface $uri, 
        UploadedFileInterface $uploadedFile, 
        array $methodHandlers)
    {
        // Message construct.
        parent::__construct();

        // Store UploadedFile instance.
        $this->uploadedFile = $uploadedFile;

        // Store Stream instance.
        $this->stream = $stream->createStream('php://input', 'rb');

        // Server environment.
        $this->serverEnv = $this->normalizeServer($_SERVER);

        // Headers.
        $this->headers = $this->normalizeHeaders($this->serverEnv);

        // URI.
        $this->uri = $this->createUriFromGlobals($uri);

        // HTTP real method.
        $this->realMethod = strtoupper($this->getFromServer('REQUEST_METHOD'));

        // HTTP replaced method (by html form input field '_method').
        $this->replacedMethod = strtoupper(strtoupper($this->input('_method')));

        // Cookie if present.
        $this->cookies = (isset($_COOKIE)) ? $_COOKIE : [];

        // Get params if present.
        $this->queryParams = (isset($_GET)) ? $_GET : [];

        // processing incoming HTTP requests by methods:
        // POST, PUT, PATCH, DELETE
        if ($this->getMethod() !== 'GET') {
            // register HTTP methods handlers
            $this->registerMethodHandler($methodHandlers);
            // notification of all handlers of this incoming request
            $requestBody = $this->notify($this);
            // get parsed body (if exists) from handler
            $this->parsedBody = $requestBody['parsedBody'];
            // get uploaded files (if exists) from handler
            if (! empty($requestBody['uploadedFiles'])) {
                $this->uploadedFiles = $this->normalizeUploadedFiles($requestBody['uploadedFiles']);
            }
        }
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
     * in PHP's super global $_SERVER.
     *
     * NOTE: This method is not a part of PSR-7 recommendations.
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
     * Register HTTP methods handlers for processing incoming HTTP request.
     * Given array may look like: [
     *                               methodHandlerObject,
     *                               anotherMethodHandlerObject
     *                             ]
     *
     * @param array $methodHandlers     Array of handlers for HTTP methods.
     * @return void
     * @throws InvalidArgumentException if given handler not instance of 
     * AbstractHandler.
     */
    protected function registerMethodHandler(array $methodHandlers)
    {
        foreach ($methodHandlers as $handler) {
            if (! $handler instanceof AbstractHandler) {
                throw new InvalidArgumentException('HTTP method handler must be an instance of AbstractHandler.');
            }

            $this->methodHandlers[] = $handler;
        }
    }

    /**
     * This method notifies all attached handlers
     * of this incoming request.
     *
     * NOTE: This method is not a part of PSR-7 recommendations.
     *
     * @param object $request       $this.
     * @return array $requestBody   Current request body.
     * @throws InvalidArgumentException if given $request not instance of $this.
     */
    protected function notify($request)
    {
        if (! $request instanceof $this) {
            throw new InvalidArgumentException('Transferred instance must be $this.');
        }

        if (! empty($this->methodHandlers)) {
            foreach ($request->methodHandlers as $handler) {
                // updates handler and get
                // request body from it (if exists)
                $requestBody = $handler->update($request);
                if ($requestBody) {
                    return $requestBody;
                }
            }
        }
    }
}
