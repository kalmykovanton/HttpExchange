<?php

namespace HttpExchange\Request\Components;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ServerRequestComponent.
 * @package HttpExchange\Request\Components
 */
abstract class ServerRequestComponent extends RequestComponent implements ServerRequestInterface
{
    /**
     * Cookies from superglobal $_COOKIE.
     *
     * @var array
     */
    protected $cookies = [];

    /**
     * Query params from superglobal $_GET.
     *
     * @var array
     */
    protected $queryParams = [];

    /**
     * Request attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Parsed body.
     *
     * @var null|array|object
     */
    protected $parsedBody;

    /**
     * Normalized uploaded files.
     *
     * @var array
     */
    protected $uploadedFiles = [];

    /**
     * Retrieve server parameters.
     *
     * {@inheritdoc}
     *
     * @return array
     */
    public function getServerParams()
    {
        return $this->serverEnv;
    }

    /**
     * Retrieve cookies.
     *
     * {@inheritdoc}
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookies;
    }

    /**
     * Return an instance with the specified cookies.
     *
     * {@inheritdoc}
     *
     * @param array $cookies    Array of key/value pairs representing cookies.
     * @return self
     */
    public function withCookieParams(array $cookies)
    {
        $clone = clone $this;
        $clone->cookies = $cookies;
        return $clone;
    }

    /**
     * Retrieve query string arguments.
     *
     * {@inheritdoc}
     *
     * @return array
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * {@inheritdoc}
     *
     * @param array $query      Array of query string arguments,
     *                          typically from $_GET.
     * @return self
     */
    public function withQueryParams(array $query)
    {
        $clone = clone $this;
        $clone->queryParams = $query;
        return $clone;
    }

    /**
     * Retrieve normalized file upload data.
     *
     * {@inheritdoc}
     *
     * @return array    An array tree of UploadedFileInterface instances.
     *                  An empty array will be returned if no data is present.
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * {@inheritdoc}
     *
     * @param array     An array tree of UploadedFileInterface instances.
     * @return self
     * @throws \InvalidArgumentException    If an invalid structure is provided.
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $this->filterUploadedFiles($uploadedFiles);
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;
        return $clone;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * {@inheritdoc}
     *
     * @return null|array|object    The deserialized body parameters, if any.
     *                              These will typically be an array or object.
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * {@inheritdoc}
     *
     * @param null|array|object $data       The deserialized body data. This will
     *                                      typically be in an array or object.
     * @return self
     *
     * @throws \InvalidArgumentException    If an unsupported argument type
     *                                      is provided.
     */
    public function withParsedBody($data)
    {
        if (! is_null($data) || ! is_array($data) || ! is_object($data)) {
            throw new InvalidArgumentException(
                'Data must be a string, object or null.'
            );
        }

        $clone = clone $this;
        $clone->parsedBody = $data;
        return $clone;
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * {@inheritdoc}
     *
     * @return mixed[]      Attributes derived from the request.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * {@inheritdoc}
     *
     * @see getAttributes()
     * @param string $name      The attribute name.
     * @param mixed $default    Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        if (! is_string($name) || ! is_numeric($name)) {
            throw new InvalidArgumentException(
                'Attribute name must be a string or integer.'
            );
        }

        if (! array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * {@inheritdoc}
     *
     * @see getAttributes()
     * @param string $name      The attribute name.
     * @param mixed $value      The value of the attribute.
     * @return self
     */
    public function withAttribute($name, $value)
    {
        if (! is_string($name) || ! is_numeric($name)) {
            throw new InvalidArgumentException(
                'Attribute name must be a string or integer.'
            );
        }

        $clone = clone $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * {@inheritdoc}
     *
     * @see getAttributes()
     * @param string $name      The attribute name.
     * @return self
     */
    public function withoutAttribute($name)
    {
        if (! is_string($name) || ! is_numeric($name)) {
            throw new InvalidArgumentException(
                'Attribute name must be a string or integer.'
            );
        }

        if (! isset($this->attributes[$name])) {
            return clone $this;
        }

        $clone = clone $this;
        unset($clone->attributes[$name]);
        return $clone;
    }
}
