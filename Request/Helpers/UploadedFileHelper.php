<?php

namespace HttpExchange\Request\Helpers;

use RuntimeException;

/**
 * Class UploadedFileHelper.
 * @package HttpExchange\Request\Helpers
 */
trait UploadedFileHelper
{
    /**
     * Write from internal stream to given path.
     *
     * The basis of this function is taken from zend-diactoros,
     * for which them many thanks and slightly modified by me,
     * to be compatible with this application.
     * @see https://github.com/zendframework/zend-diactoros
     *
     * @param $path                 Path to write.
     * @throws RuntimeException     On Error.
     */
    private function writeFromStream($path)
    {
        $handle = fopen($path, 'wb+');
        if (false === $handle) {
            throw new RuntimeException('Unable to write to the final path.');
        }

        $stream = $this->getStream();
        $stream->rewind();
        while (! $stream->eof()) {
            fwrite($handle, $stream->read(4096));
        }

        fclose($handle);
    }
}
