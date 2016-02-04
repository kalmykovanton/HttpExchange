<?php

namespace HttpExchange\Request\Helpers;

use InvalidArgumentException;

/**
 * Class UriHelper.
 * @package HttpExchange\Request\Helpers
 */
trait UriHelper
{
    /**
     * Array regulates legal HTTP schemes
     * and corresponding ports.
     *
     * @var array
     */
    private $allowedSchemes = [
        'http' => 80,
        'https' => 443,
    ];

    /**
     * Parse URI into its parts,
     * and set the properties of Uri instanse.
     *
     * @param $uriString    Row URI string
     */
    private function splitUriString($uriString)
    {
        $uriParts = parse_url($uriString);

        if ($uriParts === false) {
            throw new \InvalidArgumentException(
                'The source URI string does not satisfy the requirements.
                See: https://tools.ietf.org/html/rfc3986.'
            );
        }

        $this->scheme       = isset($uriParts['scheme'])   ? $this->filterScheme($uriParts['scheme']) : '';
        $this->userInfo     = isset($uriParts['user'])     ? $uriParts['user']     : '';
        $this->host         = isset($uriParts['host'])     ? $uriParts['host']     : '';
        $this->port         = isset($uriParts['port'])     ? $uriParts['port']     : null;
        $this->path         = isset($uriParts['path'])     ? $this->urlCharEncode('path', $uriParts['path']) : '';
        $this->query  = isset($uriParts['query'])    ? $this->filterQuery($uriParts['query']) : '';
        $this->fragment     = isset($uriParts['fragment']) ? $this->filterFragment($uriParts['fragment']) : '';

        if (isset($uriParts['pass'])) {
            $this->userInfo .= ':' . $uriParts['pass'];
        }
    }

    /**
     * Check if given host name value is valid.
     *
     * @param string $value
     */
    private function checkHost($value = '')
    {
        return preg_match('/[a-zA-Z0-9.-]{1,253}/', $value);
    }

    /**
     * Filters the scheme to ensure it is a valid scheme.
     *
     * The basis of this function is taken from zend-diactoros,
     * for which them many thanks and slightly modified by me,
     * to be compatible with this application.
     * @see https://github.com/zendframework/zend-diactoros
     *
     * @param string $scheme    Scheme name.
     * @return string           Filtered scheme.
     */
    private function filterScheme($scheme)
    {
        $scheme = strtolower($scheme);
        $scheme = preg_replace('#:(\/\/)?$#', '', $scheme);

        if (empty($scheme)) {
            return '';
        }

        if (!array_key_exists($scheme, $this->allowedSchemes)) {
            throw new InvalidArgumentException(
                'Unsupported scheme, must be any empty string or http/https.'
            );
        }

        return $scheme;
    }

    /**
     * Filter a query string to ensure it is propertly encoded.
     * Ensures that the values in the query string are properly urlencoded.
     *
     * The basis of this function is taken from zend-diactoros,
     * for which them many thanks and slightly modified by me,
     * to be compatible with this application.
     * @see https://github.com/zendframework/zend-diactoros
     *
     * @param string $query
     * @return string
     */
    private function filterQuery($query)
    {
        if (! empty($query) && strpos($query, '?') === 0) {
            $query = substr($query, 1);
        }

        $parts = explode('&', $query);
        foreach ($parts as $index => $part) {
            list($key, $value) = $this->splitQueryValue($part);
            if ($value === null) {
                $parts[$index] = $this->urlCharEncode('query', $key);
                continue;
            }
            $parts[$index] = sprintf(
                '%s=%s',
                $this->urlCharEncode('query', $key),
                $this->urlCharEncode('query', $value)
            );
        }

        return implode('&', $parts);
    }

    /**
     * Split a query value into a key/value tuple.
     *
     * The basis of this function is taken from zend-diactoros,
     * for which them many thanks and slightly modified by me,
     * to be compatible with this application.
     * @see https://github.com/zendframework/zend-diactoros
     *
     * @param string $value
     * @return array A value with exactly two elements, key and value
     */
    private function splitQueryValue($value)
    {
        $data = explode('=', $value, 2);
        if (1 === count($data)) {
            $data[] = null;
        }
        return $data;
    }

    /**
     * Filter a fragment value to ensure it is properly encoded.
     *
     * The basis of this function is taken from zend-diactoros,
     * for which them many thanks and slightly modified by me,
     * to be compatible with this application.
     * @see https://github.com/zendframework/zend-diactoros
     *
     * @param null|string $fragment
     * @return string
     */
    private function filterFragment($fragment)
    {
        if (! empty($fragment) && strpos($fragment, '#') === 0) {
            $fragment = substr($fragment, 1);
        }

        return $this->urlCharEncode('query', $fragment);
    }

    /**
     * Used when filters path, query string or fragment of a URI
     * to ensure it is properly encoded.
     *
     * If necessary filter path, flag must be === 'path',
     * if query string or fragment flag must be === 'query'.
     *
     * The basis of this function is taken from zend-diactoros,
     * for which them many thanks and slightly modified by me,
     * to be compatible with this application.
     * @see https://github.com/zendframework/zend-diactoros
     *
     * @param $flag             What filter (path or query)
     * @param string $value     Value filtering (path, query string or fragment)
     * @return string           Filtered value
     *
     * @throws InvalidArgumentException
     */
    private function urlCharEncode($flag, $value = '')
    {
        if (!is_string($flag)) {
            throw new InvalidArgumentException(
                'Flag must be a string.'
            );
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException(
                'Value must be a string.'
            );
        }

        $pregPattern = '';
        $pathPattern = '/(?:[^a-zA-Z0-9_\-\.~:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/';
        $queryPattern = '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/';

        if ($flag === 'path') {
            $pregPattern = $pathPattern;
        }

        if ($flag === 'query') {
            $pregPattern = $queryPattern;
        }

        $filtered = preg_replace_callback(
            $pregPattern,
            function (array $matches)
            {
                return rawurlencode($matches[0]);
            },
            $value
        );

        if ($flag === 'path') {
            if (empty($filtered)) {
                // No path
                return $filtered;
            } elseif ($filtered[0] !== '/') {
                // Relative path
                return $filtered;
            } else {
                // Ensure only one leading slash, to prevent XSS attempts.
                return '/' . ltrim($filtered, '/');
            }
        }

        return $filtered;
    }
}
