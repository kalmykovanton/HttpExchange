<?php

namespace HttpExchange\Common;

use InvalidArgumentException;
use RuntimeException;
use Psr\Http\Message\StreamInterface;

/**
 * Class Stream.
 * @package HttpExchange\Common
 */
class Stream implements StreamInterface
{
    /**
     * Stores resource id.
     *
     * @var resource
     */
    protected $resource;

    /**
     * Stores stream path or resource id.
     *
     * @var string|resource
     */
    protected $stream;

    /**
     * Stream constructor.
     *
     * @param string|resource $stream   String stream target or stream resource.
     * @param string $mode              Mode with which to open stream.
     * @throws InvalidArgumentException
     */
    public function __construct($stream, $mode = 'r')
    {
        $this->stream = $stream;

        if (is_resource($stream)) {
            return $this->resource = $stream;
        }

        if (is_string($stream)) {
            set_error_handler(function ($errno, $errstr) {
                throw new InvalidArgumentException(
                    'Invalid file provided for stream. Must be a valid path with valid permissions.'
                );
            }, E_WARNING);
            $this->resource = fopen($stream, $mode);
            restore_error_handler();
            return;
        } else {
            throw new InvalidArgumentException(
                'Invalid stream provided. Must be a string stream identifier or resource.'
            );
        }
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * {@inheritdoc}
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString()
    {
        if (! $this->resource) {
            throw new RuntimeException("No resource available, can't read.");
        }

        if (! $this->isReadable()) {
            throw new RuntimeException('Stream is not readable.');
        }

        try {
            $this->rewind();
            return $this->getContents();
        } catch (RuntimeException $e) {
            return '';
        }
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * {@inheritdoc}
     *
     * @return void
     */
    public function close()
    {
        if (! $this->resource) {
            return;
        }

        $resource = $this->detach();
        fclose($resource);
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * {@inheritdoc}
     *
     * @return resource|null    Underlying PHP stream, if any
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        return $resource;
    }

    /**
     * Get the size of the stream if known.
     *
     * {@inheritdoc}
     *
     * @return int|null     Returns the size in bytes if known,
     *                      or null if unknown.
     */
    public function getSize()
    {
        if ($this->resource === null) {
            return null;
        }

        $stats = fstat($this->resource);

        return $stats['size'];
    }

    /**
     * Returns the current position of the file read/write pointer.
     *
     * {@inheritdoc}
     *
     * @return int      Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell()
    {
        if (! $this->resource) {
            throw new RuntimeException("No resource available, can't tell position.");
        }

        $result = ftell($this->resource);

        if (! is_int($result)) {
            throw new RuntimeException('Error occurred during tell operation.');
        }

        return $result;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * {@inheritdoc}
     *
     * @return bool
     */
    public function eof()
    {
        if (! $this->resource) {
            throw new RuntimeException("No resource available, can't tell position.");
        }

        return feof($this->resource);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isSeekable()
    {
        if (!$this->resource) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        return (bool) $meta['seekable'];
    }

    /**
     * Seek to a position in the stream.
     *
     * {@inheritdoc}
     *
     * @see http://www.php.net/manual/en/function.fseek.php
     *
     * @param int $offset       Stream offset
     * @param int $whence       Specifies how the cursor position
     *                          will be calculated based on the seek offset.
     *                          Valid values:
     *                          SEEK_SET: Set position equal to offset bytes (by default);
     *                          SEEK_CUR: Set position to current location plus offset;
     *                          SEEK_END: Set position to end-of-stream plus offset.
     * @throws \RuntimeException On failure.
     * @return bool             On success.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->resource) {
            throw new RuntimeException("No resource available, can't seek position.");
        }

        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable.');
        }

        $result = fseek($this->resource, $offset, $whence);

        if ($result !== 0) {
            throw new RuntimeException('Error seeking within stream.');
        }

        return true;
    }

    /**
     * Seek to the beginning of the stream.
     *
     * {@inheritdoc}
     *
     * @see seek()
     * @see http://www.php.net/manual/en/function.fseek.php
     * @throws \RuntimeException on failure.
     */
    public function rewind()
    {
        return $this->seek(0);
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isWritable()
    {
        if (! $this->resource) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'];

        return (
            strstr($mode, 'x')
            || strstr($mode, 'w')
            || strstr($mode, 'c')
            || strstr($mode, 'a')
            || strstr($mode, '+')
        );
    }

    /**
     * Write data to the stream.
     *
     * {@inheritdoc}
     *
     * @param string $string    The string that is to be written.
     * @return int              Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string)
    {
        if (! $this->resource) {
            throw new RuntimeException("No resource available, can't write.");
        }

        if (! $this->isWritable()) {
            throw new RuntimeException('Stream is not writable.');
        }

        $result = fwrite($this->resource, $string);

        if ($result === false) {
            throw new RuntimeException('Error writing to stream.');
        }
        return $result;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isReadable()
    {
        if (! $this->resource) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'];

        return (strstr($mode, 'r') || strstr($mode, '+'));
    }

    /**
     * Read data from the stream.
     *
     * {@inheritdoc}
     *
     * @param int $length   Read up to $length bytes from the object and return
     *                      them. Fewer than $length bytes may be returned
     *                      if underlying stream call returns fewer bytes.
     * @return string   Returns the data read from the stream, or an empty string
     *                  if no bytes are available.
     * @throws \RuntimeException If an error occurs.
     */
    public function read($length)
    {
        if (! $this->resource) {
            throw new RuntimeException("No resource available, can't read.");
        }

        if (! $this->isReadable()) {
            throw new RuntimeException('Stream is not readable.');
        }

        $result = fread($this->resource, $length);

        if ($result === false) {
            throw new RuntimeException('Error reading stream.');
        }

        return $result;
    }

    /**
     * Returns the remaining contents in a string.
     *
     * {@inheritdoc}
     *
     * @return string
     * @throws \RuntimeException    If unable to read.
     * @throws \RuntimeException    If error occurs while reading.
     */
    public function getContents()
    {
        if (! $this->isReadable()) {
            throw new RuntimeException('Stream is not readable.');
        }

        $result = stream_get_contents($this->resource);

        if ($result === false) {
            throw new RuntimeException('Error reading from stream.');
        }

        return $result;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * {@inheritdoc}
     *
     * @see http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key           Specific metadata to retrieve.
     * @return array|mixed|null     Returns an associative array if no key is
     *                              provided. Returns a specific key value if
     *                              a key is provided and the value is found,
     *                              or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        if (!is_string($key) && !is_null($key)) {
            throw new InvalidArgumentException(
                'Invalid type of argument; must be a string or null.'
            );
        }

        if ($key === null) {
            return stream_get_meta_data($this->resource);
        }

        $metadata = stream_get_meta_data($this->resource);

        if (!array_key_exists($key, $metadata)) {
            return null;
        }

        return $metadata[$key];
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
