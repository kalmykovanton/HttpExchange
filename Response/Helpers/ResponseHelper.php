<?php

namespace HttpExchange\Response\Helpers;

use InvalidArgumentException;

/**
 * Class ResponseHelper.
 * @package HttpExchange\Response\Helpers
 */
trait ResponseHelper
{
    /**
     * Standard HTTP status code => reason phrases
     *
     * @var array
     */
    private $reasonPhrases = array(
        // INFORMATIONAL
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // SUCCESS
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        // REDIRECTION
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated
        307 => 'Temporary Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        // SERVER ERROR
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    );

    /**
     * Check status code for compliance with RFC 7231.
     *
     * @See https://tools.ietf.org/html/rfc7231
     * @param $statusCode   Status code. Must be 3-digit integer.
     * @throws \InvalidArgumentException    For invalid status code.
     */
    private function checkStatusCode($statusCode)
    {
        if (! is_numeric($statusCode)) {
            throw new InvalidArgumentException(
                'Status code must be a numeric.'
            );
        }

        $statusCode = (int) $statusCode;
        $pattern = '/^[1-5]{1}[0-9]{1}[0-9]{1}/';

        if (
            ! preg_match($pattern, $statusCode)
            && ! array_key_exists($statusCode, $this->reasonPhrases)
        ) {
            throw new InvalidArgumentException(
                'Status code must be a valid 3-digit integer.
                See: https://tools.ietf.org/html/rfc7231.'
            );
        }
    }

    /**
     * Check whether reason phrase is a string.
     *
     * @param $reasonPhrase     Http reason phrase.
     * @throws \InvalidArgumentException    If reason phrase not a string.
     */
    private function checkReasonPhrase($reasonPhrase)
    {
        if (! is_string($reasonPhrase)) {
            throw new InvalidArgumentException(
                'Reason phrase must be a string.'
            );
        }
    }

    /**
     * Generate and send HTTP potocol/version,
     * status code and reason phrase (e.g. HTTP/1.1 200 OK).
     * Otherwise if status code not exists in the object
     * do nothing.
     */
    private function sendStatusCodeAndReasonPhrase()
    {
        if ($this->statusCode) {
            header(
                'HTTP/' . $this->protocolVersion . ' ' . $this->statusCode . ' ' . $this->getReasonPhrase()
            );
        }
    }

    /**
     * Generate and send headers (e.g. Cache-Control: no-cache).
     * Otherwise, if headers array is empty do nothing.
     */
    private function sendHeaders()
    {
        $headers = $this->getHeaders();

        if (! empty($headers)) {
            foreach ($headers as $name => $value) {
                if (strtolower($name) === 'cookie') {
                    continue;
                }
                header($name . ': ' . $this->getHeaderLine($name));
            }
        }
    }

    /**
     * Send response body.
     */
    private function sendBody()
    {
        // Send response body variant 1
        // New output stream.
        // $output = new Stream('php://output', 'wb');
        // Set the cursor to the start of the response body.
        // $this->stream->rewind();
        // echo response body.
        // $output->write($this->stream->getContents());

        // Send response body variant 2
        echo $this->stream;
    }
}
