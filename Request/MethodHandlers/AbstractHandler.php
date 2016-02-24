<?php

namespace HttpExchange\Request\MethodHandlers;

use \InvalidArgumentException;

/**
 * Class AbstractHandler.
 * @package HttpExchange\Request\MethodHandlers
 */
abstract class AbstractHandler
{
    /**
     * Store represents the contents of the
     * request body divided on parsed body
     * and uploaded files.
     *
     * @var array
     */
    protected $requestBody = [
        'parsedBody' => [],
        'uploadedFiles' => []
    ];

    /**
     * Store current HTTP request instance.
     *
     * @var object
     */
    protected $request;

    /**
     * Store processed request body, divided
     * on parts. Each part contains part's
     * headers and part's body.
     *
     * @var array
     */
    protected $separatedBody;

    /**
     * This method must check given request method with that
     * it has handle. If match, this method should handle an
     * incoming request and return the requets's processed
     * body, divided by the parameters (if any) and the
     * downloaded files (if any). If mismatch, this method
     * must return false.
     *
     * @param object $request   This request.
     * @return array|bool       Separated request's body if match
     *                          request method or false if no.
     */
    abstract public function update($request);

    /**
     * This method must check request's 'Content-Type' header
     * value and depending on the result of processing the request
     * body. Main 'Content-Type' values are 'application/json',
     * 'multipart/form-data', 'application/x-www-form-urlencoded'.
     * When processing 'Content-Type' values, pay attention to
     * HTTP request method, depending on which need processing
     * of a 'Content-Type' value may vary.
     *
     * @return array    Request body or request parameters
     *                  (if any).
     */
    abstract public function getParsedBody();

    /**
     * When header's 'Content-Type' value = 'multipart/form-data',
     * this method must browse separated body for the existence of
     * uploaded files. And if there are uploaded files this method
     * must process them.
     *
     * @return array    Array of uploaded files (if any).
     */
    abstract public function getUploadedFiles();

    /**
     * Determines whether the header 'Content-Type'
     * is 'application/json'.
     *
     * @return bool     True if json, false if no.
     */
    protected function isJson()
    {
        $contentType = strtolower($this->request->getHeaderLine('Content-Type'));
        $jsonPattern = '/application\/json/';
        
        return (preg_match($jsonPattern, $contentType)) ? true : false;
    }

    /**
     * Determines whether the header 'Content-Type'
     * is 'multipart/form-data'.
     *
     * @return bool     True if multipart/form-data,
     *                  false if no.
     */
    protected function isFormData()
    {
        $contentType = strtolower($this->request->getHeaderLine('Content-Type'));
        $formDataPattern = '/multipart\/form-data/';
        
        return (preg_match($formDataPattern, $contentType)) ? true : false;
    }

    /**
     * Determines whether the header 'Content-Type'
     * is 'application/x-www-form-urlencoded'.
     *
     * @return bool     True if application/x-www-form-urlencoded,
     *                  false if no.
     */
    protected function isFormUrlencoded()
    {
        $contentType = strtolower($this->request->getHeaderLine('Content-Type'));
        $formUrlencodedPattern = '/application\/x-www-form-urlencoded/';
        
        return (preg_match($formUrlencodedPattern, $contentType)) ? true : false;
    }

    /**
     * Parse URL encoded data from HTTP request message.
     *
     * @param string $rowURL    Row request message data.
     * @return array            Parsed data.
     */
    protected function parseUrlencoded($rowURL)
    {
        if (! is_string($rowURL)) {
            throw new InvalidArgumentException('Row URL encoded data must be a string.');
        }

        $params = [];
        
        foreach (explode('&', $rowURL) as $part) {
            $param = explode("=", $part);
            if ($param) {
                $params[urldecode($param[0])] = urldecode($param[1]);
            }
        }
        
        return $params;
    }

    /**
     * Parse json data from HTTP request message.
     *
     * @param string $rowJson   Row json data from request message.
     * @return array            Parsed json.
     */
    protected function parseJson($rowJson)
    {
        if (! is_string($rowJson)) {
            throw new InvalidArgumentException('Row json data must be a string.');
        }

        return json_decode($rowJson, true);
    }

    /**
     * This method separate row body derived from the HTTP request.
     *
     * @param string $rowBody   Row body from HTTP request message.
     * @return array            Separated body.
     */
    protected function separateRowBody($rowBody)
    {
        if (! is_string($rowBody)) {
            throw new InvalidArgumentException('Row message body must be a string.');
        }

        if ($rowBody === '') {
            return false;
        }

        // processed body from HTTP request message
        $separatedBody = [];
        // body parts counter
        $contentCounter = 0;

        // receive boundary separator request's body
        $boundary = substr($rowBody, 0, strpos($rowBody, "\r\n"));

        // divide row body by boundary separator
        // and fetch each part in $parts array
        $parts = array_slice(explode($boundary, $rowBody), 1);

        // process body parts
        foreach ($parts as $part) {
            // break, if found the last part
            if ($part == "--\r\n") break;

            // separate content headers from main content
            $part = ltrim($part, "\r\n");
            list($rawHeaders, $content) = explode("\r\n\r\n", $part, 2);

            // parse headers
            $rawHeaders = explode("\r\n", $rawHeaders);
            // process headers
            foreach ($rawHeaders as $header) {
                // separate header name from header value
                list($headerName, $values) = explode(':', $header);
                // parse header values
                $rowValues = explode('; ', trim($values, ' '));
                // process header values
                foreach ($rowValues as $rowValue) {
                    if (! strpos($rowValue, '=')) {
                        $separatedBody[$contentCounter][$headerName][] = $rowValue;
                        continue;
                    }
                    list($key, $value) = explode('=', $rowValue);
                    $separatedBody[$contentCounter][$headerName][$key] = trim($value, '"');
                }
            }
            // add body to current part
            $separatedBody[$contentCounter]['body'] = $content;
            // increase body parts counter
            $contentCounter += 1;
        }
        
        return $separatedBody;
    }

    /**
     * Pull parsed body from separated body parts.
     *
     * @param array $separatedBody  Separated body parts.
     * @return array                Body.
     */
    protected function pullParsedBody(array $separatedBody)
    {
        $parsedBody = [];
        
        foreach ($separatedBody as $bodyPart) {
            if (isset($bodyPart['Content-Disposition']['filename'])) {
                continue;
            }
            $parsedBody[$bodyPart['Content-Disposition']['name']] = $bodyPart['body'];
        }
        
        return $parsedBody;
    }

    /**
     * Pull uploaded files from separated body parts.
     *
     * @param array $separatedBody  Separated body parts.
     * @return array                Array of uloaded files.
     */
    protected function pullUploadedFiles(array $separatedBody)
    {
        $files = [];
        
        foreach ($separatedBody as $bodyPart) {
            // if isset file in requets's separated message body
            if (isset($bodyPart['Content-Disposition']['filename'])) {
                // get template directory
                $uploadTmpDir = (ini_get('upload_tmp_dir')) ? ini_get('upload_tmp_dir') : '/tmp';
                // generate template unique name for uploaded file
                $tmpFilename = 'php' . sha1(uniqid('', true));
                // get full path
                $fullFilePath = rtrim($uploadTmpDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $tmpFilename;
                // put file into template directory
                file_put_contents($fullFilePath, $bodyPart['body']);
                // put uploaded file info into array,
                // how does this happen at POST HTTP request
                $files[$bodyPart['Content-Disposition']['name']]['tmp_name'] = $fullFilePath;
                $files[$bodyPart['Content-Disposition']['name']]['size'] = strlen($bodyPart['body']);
                $files[$bodyPart['Content-Disposition']['name']]['error'] = 0;
                $files[$bodyPart['Content-Disposition']['name']]['name'] = $bodyPart['Content-Disposition']['filename'];
                $files[$bodyPart['Content-Disposition']['name']]['type'] = $bodyPart['Content-Type'][0];
            }
        }
        
        return $files;
    }
}
