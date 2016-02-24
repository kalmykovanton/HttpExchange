<?php

namespace HttpExchange\Request;

use \InvalidArgumentException;
use \RuntimeException;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\StreamInterface;
use HttpExchange\Request\Helpers\UploadedFileHelper;

/**
 * Class UploadedFile.
 * @package HttpExchange\Request
 */
class UploadedFile implements UploadedFileInterface
{
    use UploadedFileHelper;

    /**
     * Filename.
     *
     * @var string
     */
    protected $filename;

    /**
     * Media type.
     *
     * @var string
     */
    protected $mediaType;

    /**
     * Uploading error.
     *
     * @var int
     */
    protected $error;

    /**
     * Temporary filename.
     *
     * @var null|string
     */
    protected $file;

    /**
     * Moved or not flag.
     *
     * @var bool
     */
    protected $moved = false;

    /**
     * File size.
     *
     * @var int
     */
    protected $size;

    /**
     * Stream instance.
     *
     * @var StreamInterface instance
     */
    protected $stream;

    /**
     * UploadedFile constructor.
     *
     * @param StreamInterface $stream
     */
    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    /**
     * Create new UploadedFile instance.
     *
     * @param mixed $stream         Filename or stream.
     * @param integer $size         Uploaded file size.
     * @param integer $errorFlag    Uploaded errors.
     * @param null $filename        Filename.
     * @param null $mediaType       File media type.
     * @return $this                New UploadedFile inctance.
     */
    public function createUploadedFile($stream, $size, $errorFlag, $filename = null, $mediaType = null)
    {
        if (is_string($stream)) {
            $this->file = $stream;
        }

        if (is_resource($stream)) {
            $this->stream = $this->stream->createStream($stream);
        }

        if (! $this->file && ! $this->stream) {
            if (! $stream instanceof StreamInterface) {
                throw new InvalidArgumentException('Invalid stream or file provided for UploadedFile.');
            }
            $this->stream = $stream;
        }

        // Check file size.
        if (! is_int($size)) {
            throw new InvalidArgumentException('Size of uploaded file must be an integer.');
        }
        $this->size = $size;

        // Check for errors.
        if (! is_int($errorFlag)
            || 0 > $errorFlag
            || 8 < $errorFlag
        ) {
            throw new InvalidArgumentException(
                'Erro status of uploaded file must be an UPLOAD_ERR_* constant.
                See: http://php.net/manual/en/features.file-upload.errors.php.'
            );
        }
        $this->error = $errorFlag;

        // Check filename.
        if ($filename !== null  && ! is_string($filename)) {
            throw new InvalidArgumentException(
                'Invalid filename of uploaded file. Must be null or string.'
            );
        }
        $this->filename = $filename;

        // Check file media type.
        if ($mediaType !== null  && ! is_string($mediaType)) {
            throw new InvalidArgumentException(
                'Invalid client media type of uploaded file. Must be null or string.'
            );
        }
        $this->mediaType = $mediaType;

        return clone $this;
    }

    /**
     * Retrieve a stream representing the uploaded file.
     *
     * {@inheritdoc}
     *
     * @return StreamInterface      Stream representation of the uploaded file.
     * @throws \RuntimeException    In cases when no stream is available.
     * @throws \RuntimeException    In cases when no stream can be created.
     */
    public function getStream()
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Cannot retrieve stream due to upload error.');
        }

        if ($this->moved) {
            throw new RuntimeException('Cannot retrieve stream after it has already been moved.');
        }

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        $this->stream = $this->stream->createStream($this->file);
        return $this->stream;
    }

    /**
     * Move the uploaded file to a new location.
     *
     * {@inheritdoc}
     *
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     *
     * @param string $path                  Path to which to move the uploaded file.
     * @throws \InvalidArgumentException    If the $path specified is invalid.
     * @throws \RuntimeException            On any error during the move operation.
     * @throws \RuntimeException            On the second or subsequent call to the method.
     */
    public function moveTo($path)
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Cannot retrieve stream due to upload error.');
        }

        if (! is_string($path)) {
            throw new InvalidArgumentException(
                'Invalid path provided for move operation. Path must be a string.'
            );
        }

        if (empty($path)) {
            throw new InvalidArgumentException(
                'Invalid path provided for move operation. Path must be a non-empty string.'
            );
        }

        if ($this->moved) {
            throw new RuntimeException('File already moved!');
        }

        $sapi = PHP_SAPI;
        switch (true) {
            case (empty($sapi) || strpos($sapi, 'cli') === 0 || ! $this->file):
                // Non-SAPI environment, or no filename present
                $this->writeFromStream($path);
                break;
            default:
                // SAPI environment, with file present
                if (move_uploaded_file($this->file, $path) === false) {
                    throw new RuntimeException(
                        'Error occurred while moving uploaded file.
                        Check whether the directory exists and or write permissions.'
                    );
                }
                break;
        }

        $this->moved = true;
    }

    /**
     * Retrieve the file size.
     *
     * {@inheritdoc}
     *
     * @return int|null     The file size in bytes or null if unknown.
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Retrieve the error associated with the uploaded file.
     *
     * {@inheritdoc}
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int      One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Retrieve the filename sent by the client.
     *
     * {@inheritdoc}
     *
     * @return string|null      The filename sent by the client
     *                          or null if none was provided.
     */
    public function getClientFilename()
    {
        return $this->filename;
    }

    /**
     * Retrieve the media type sent by the client.
     *
     * {@inheritdoc}
     *
     * @return string|null      The media type sent by the client
     *                          or null if none was provided.
     */
    public function getClientMediaType()
    {
        return $this->mediaType;
    }
}
