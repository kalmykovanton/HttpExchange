<?php

namespace HttpExchange\Response;

use \InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use HttpExchange\Common\Stream;
use HttpExchange\Common\Message;
use HttpExchange\Response\Helpers\ResponseHelper;

/**
 * Class Response.
 * @package HttpExchange\Response
 */
class Response extends Message implements ResponseInterface
{
    use ResponseHelper;

    /**
     * HTTP response status code.
     * @See https://tools.ietf.org/html/rfc7231.
     *
     * @var integer
     */
    protected $statusCode = 200;

    /**
     * HTTP response reason phrase.
     * @See https://tools.ietf.org/html/rfc7231.
     *
     * @var string
     */
    protected $reasonPhrase = '';

    /**
     * Response constructor.
     *
     * @param Stream $stream        Stream instance.
     * @param string $statusCode    Response status code.
     * @param string $reasonPhrase  Response reason phrase.
     */
    public function __construct(Stream $stream, $statusCode = '', $reasonPhrase = '')
    {
        // Message construct
        parent::__construct();

        // Row stream for response body.
        $this->stream = $stream->createStream('php://temp', 'wb+');

        // Set status code.
        $this->statusCode = ($statusCode === '') ? 200 : $this->checkStatusCode($statusCode);

        // Set reason phrase.
        $this->reasonPhrase = ($reasonPhrase === '') ? 'OK' : $this->checkReasonPhrase($reasonPhrase);
    }

    /**
     * Gets the response status code.
     *
     * {@inheritdoc}
     *
     * @return int Status code.
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     *
     * {@inheritdoc}
     *
     * @see http://tools.ietf.org/html/rfc7231#section-6
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param int $code                 The 3-digit integer result code to set.
     * @param string $reasonPhrase      The reason phrase to use with the
     *                                  provided status code.
     * @return self
     *
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        if (! is_numeric($code)) {
            throw new InvalidArgumentException(
                'Status code must be a numeric.'
            );
        }

        $clone = clone $this;
        $clone->statusCode = $this->checkStatusCode($code);

        if ($reasonPhrase === '') {
            $clone->reasonPhrase = $this->reasonPhrases[$code];
            return $clone;
        }

        $clone->reasonPhrase = $this->checkReasonPhrase($reasonPhrase);
        return $clone;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * {@inheritdoc}
     *
     * @see http://tools.ietf.org/html/rfc7231#section-6
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string   Reason phrase or empty string if none present.
     */
    public function getReasonPhrase()
    {
        if ($this->reasonPhrase) {
            return $this->reasonPhrase;
        }

        if (! $this->reasonPhrase && $this->statusCode) {
            return $this->reasonPhrases[$this->statusCode];
        }

        if (! $this->reasonPhrase && ! $this->statusCode) {
            return '';
        }
    }

    /**
     * Generate and send HTTP response (status code and reasone phrase
     * headers, body).
     *
     * NOTE: This method is not a part of PSR-7 recommendations.
     */
    public function send()
    {
        // Send - HTTP potocol/version (e.g. HTTP/1.1), if otherwise indicated,
        // status code and reason phrase, if present in the object,
        // otherwise send default (200 OK).
        $this->sendStatusCodeAndReasonPhrase();

        // Send headers, if present in the object.
        $this->sendHeaders();

        // Send response body.
        $this->sendBody();
    }
}
