<?php

namespace HttpExchange\Request\Helpers;

use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use HttpExchange\Request\UploadedFile;

//use HttpExchange\Request\Uri;

/**
 * Class RequestHelper.
 * @package HttpExchange\Request\Helpers
 */
trait RequestHelper
{
    private $allowedMethods = [
        'GET' => true,
        'POST' => true,
        'PUT' => true,
        'PATH' => true,
        'DELETE' => true
    ];

    /**
     * Collect URI information from PHP's superglobals.
     *
     * @param UriInterface $uri
     * @return UriInterface instanse
     * @throws \App\Http\Request\InvalidArgumentException
     */
    private function createUriFromGlobals($uri)
    {
        if (! $uri instanceof UriInterface) {
            throw new InvalidArgumentException('Invalid instance  given. Must be UriInterface instanse.');
        }

        // URI scheme.
        $scheme = $this->getFromServer('REQUEST_SCHEME');
        $https = $this->getFromServer('HTTPS');
        $scheme = ($scheme === 'https' && $https === 'on') ? 'https' : 'http';

        if (!empty($scheme)) {
            $uri = $uri->withScheme($scheme);
        }

        // Host.
        $uri = $uri->withHost($this->getHost());

        // Port.
        $uri = $uri->withPort((int) $this->getFromServer('SERVER_PORT'));

        // Path.
        $uri = $uri->withPath($this->trimQuery($this->getFromServer('REQUEST_URI')));

        // Query string
        $query = $this->getFromServer('QUERY_STRING');
        if ($query) {
            $query = ltrim($query, '?');
        }
        $uri = $uri->withQuery($query);

        // Fragment.
        $fragment = '';
        $uri = $uri->withFragment($fragment);

        // User info.
        $user = $this->getFromServer('PHP_AUTH_USER');
        $password = $this->getFromServer('PHP_AUTH_PW');
        $uri = $uri->withUserInfo($user, $password);

        return $uri;
    }

    /**
     * Trim query string from request uri.
     *
     * @param string $value
     * @return string
     */
    private function trimQuery($value = '')
    {
        if (strpos($value, '?') !== false) {
            return substr_replace($value, '', strpos($value, '?'));
        }

        return $value;
    }

    /**
     * Return host name if exist.
     *
     * @return string Host name.
     */
    private function getHost()
    {
        if ($this->hasHeader('Host')) {
            return $this->getHeaderLine('Host');
        }

        if (!empty($this->getFromServer('HTTP_HOST'))) {
            return $this->getFromServer('HTTP_HOST');
        }

        if (!empty($this->getFromServer('SERVER_NAME'))) {
            return $this->getFromServer('SERVER_NAME');
        }

        return '';
    }

    /**
     * This method does a few things:
     * at first, handles headers without HTTP_ prefix and special headers;
     * secondly, normalize headers names to conditional standard.
     * @see http://tools.ietf.org/html/rfc2616#page-100
     *
     * For ideas thanks:
     * Matthew Weier O'Phinney and The Symfony PHP framework developers.
     * @see https://github.com/phly/http
     * @see https://github.com/symfony/symfony
     *
     * @param array $server     Array with request headers, often from superglobal $_SERVER.
     * @return array            Normalized array with request headers.
     */
    protected function normalizeHeaders(array $server)
    {
        $specialHeaders = [
            'CONTENT_TYPE' => true,
            'CONTENT_LENGTH' => true,
            'CONTENT_MD5' => true,
        ];

        $headers = array();
        foreach ($server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $key = substr($key, 5);
                $key = $this->normalizeHeaderName($key);
                $headers[$key] = [$value];
            }

            // CONTENT_* are not prefixed with HTTP_
            elseif (isset($specialHeaders[$key])) {
                $key = $this->normalizeHeaderName($key);
                $headers[$key] = [$value];
            }
        }
        return $headers;
    }

    /**
     * Normalize headers names.
     *
     * @param string $name
     * @return mixed|string
     */
    protected function normalizeHeaderName($name)
    {
        $name = str_replace('_', ' ', strtolower($name));
        $name = ucwords($name);
        $name = str_replace(' ', '-', $name);
        return $name;
    }

    /**
     * Normalize PHP's superglobal $_SERVER.
     * The task of this function is to catch
     * the authorization headers.
     *
     * The basis of this function is taken from zend-diactoros,
     * for which them many thanks and slightly modified by me,
     * to be compatible with this application.
     * @see https://github.com/zendframework/zend-diactoros
     *
     * @param array $server     Array $_SERVER
     * @return array            Normalized $_SERVER
     */
    private function normalizeServer(array $server)
    {
        // This seems to be the only way to get the Authorization header on Apache
        $apacheRequestHeaders = 'apache_request_headers';
        if (isset($server['HTTP_AUTHORIZATION'])
            || !is_callable($apacheRequestHeaders)
        ) {
            return $server;
        }

        $apacheRequestHeaders = $apacheRequestHeaders();
        if (isset($apacheRequestHeaders['Authorization'])) {
            $server['HTTP_AUTHORIZATION'] = $apacheRequestHeaders['Authorization'];
            return $server;
        }

        if (isset($apacheRequestHeaders['authorization'])) {
            $server['HTTP_AUTHORIZATION'] = $apacheRequestHeaders['authorization'];
            return $server;
        }

        return $server;
    }

    /**
     * Validate the HTTP method
     *
     * @param null|string $method   HTTP method which must be parsed.
     * @return string   If HTTP method is valid.
     *
     * @throws InvalidArgumentException on invalid HTTP method.
     */
    private function validateMethod($method)
    {
        if ($method === null) {
            return;
        }

        if (! is_string($method)) {
            throw new InvalidArgumentException('HTTP method must be a string.');
        }

        if (!array_key_exists(strtoupper($method))) {
            throw new InvalidArgumentException(
                'Unsupported HTTP method provided.
                This application supports: GET, POST, PUT, PATH or DELETE.'
            );
        }

        return $method;
    }

    /**
     * This method return any results of deserializing (if needed)
     * the request body content (usually json structure).
     * If request method POST and deserialization is not needed,
     * this method returns structured content (usually array)
     * from superglobal $_POST.
     * A null value indicates the absence of body content.
     *
     * @return null|array
     */
    private function handleBody()
    {
        $httpMethod = strtoupper($this->getMethod());
        $contentType = strtolower($this->getHeaderLine('Content-Type'));
        $postPattern = '/(multipart\/form-data)|(application\/x-www-form-urlencoded)/';
        $jsonPattern = '/application\/json/';

        if (
            $httpMethod === 'POST'
            && preg_match($postPattern, $contentType)
        ) {
            return $_POST;
        }

        if (preg_match($jsonPattern, $contentType)) {
            return json_decode($this->getBody()->getContents(), true);
        }

        return null;
    }

    /**
     * Normalize uploaded files
     *
     * Transforms each value into an UploadedFileInterface instance, and ensures
     * that nested arrays are normalized.
     *
     * The basis of this function is taken from zend-diactoros,
     * for which them many thanks and slightly modified by me,
     * to be compatible with this application.
     * @see https://github.com/zendframework/zend-diactoros
     *
     * @param array $files
     * @return array
     * @throws InvalidArgumentException for unrecognized values
     */
    private function normalizeUploadedFiles(array $files)
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key] = $value;
                continue;
            }

            if (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = $this->createUploadedFilesInstance($value);
                continue;
            }

            if (is_array($value)) {
                $normalized[$key] = $this->normalizeUploadedFiles($value);
                continue;
            }

            throw new InvalidArgumentException('Invalid value in files specification.');
        }

        return $normalized;
    }

    /**
     * Create and return an UploadedFile instance from a $_FILES specification.
     *
     * If the specification represents an array of values, this method will
     * delegate to normalizeNestedFiles() and return that returned value.
     *
     * The basis of this function is taken from zend-diactoros,
     * for which them many thanks and slightly modified by me,
     * to be compatible with this application.
     * @see https://github.com/zendframework/zend-diactoros
     *
     * @param array $value      $_FILES structure
     * @return array|UploadedFileInterface
     */
    private function createUploadedFilesInstance(array $value)
    {
        if (is_array($value['tmp_name'])) {
            return $this->normalizeNestedFiles($value);
        }

        return new UploadedFile(
            $value['tmp_name'],
            $value['size'],
            $value['error'],
            $value['name'],
            $value['type']
        );
    }

    /**
     * Normalize an array of file specifications.
     *
     * Loops through all nested files and returns a normalized array of
     * UploadedFileInterface instances.
     *
     * The basis of this function is taken from zend-diactoros,
     * for which them many thanks and slightly modified by me,
     * to be compatible with this application.
     * @see https://github.com/zendframework/zend-diactoros
     *
     * @param array $files
     * @return UploadedFileInterface[]
     */
    private function normalizeNestedFiles(array $files = [])
    {
        $normalizedFiles = [];

        foreach (array_keys($files['tmp_name']) as $key) {
            $filesSet = [
                'tmp_name' => $files['tmp_name'][$key],
                'size'     => $files['size'][$key],
                'error'    => $files['error'][$key],
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
            ];

            $normalizedFiles[$key] = $this->createUploadedFilesInstance($filesSet);
        }

        return $normalizedFiles;
    }

    /**
     * Recursively validate the structure in an uploaded files array.
     *
     * The basis of this function is taken from zend-diactoros,
     * for which them many thanks and slightly modified by me,
     * to be compatible with this application.
     * @see https://github.com/zendframework/zend-diactoros
     *
     * @param array $uploadedFiles
     * @throws InvalidArgumentException if any leaf is not an UploadedFileInterface instance.
     */
    private function filterUploadedFiles(array $uploadedFiles)
    {
        foreach ($uploadedFiles as $file) {
            if (is_array($file)) {
                $this->filterUploadedFiles($file);
                continue;
            }

            if (! $file instanceof UploadedFileInterface) {
                throw new InvalidArgumentException('Invalid structure of uploaded files.');
            }
        }
    }
}
