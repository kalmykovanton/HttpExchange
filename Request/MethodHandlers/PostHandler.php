<?php

namespace HttpExchange\Request\MethodHandlers;

/**
 * Class PostHandler.
 * @package HttpExchange\Request\MethodHandlers
 */
class PostHandler extends AbstractHandler
{
    /**
     * {@inheritdoc}
     *
     * @param object $request   This request.
     * @return array|bool       Separated request's body if match
     *                          request method or false if no
     */
    public function update($request)
    {
        if ($request->getMethod() === 'POST') {
            $this->request = $request;
            $this->requestBody['parsedBody'] = $this->getParsedBody();
            $this->requestBody['uploadedFiles'] = $this->getUploadedFiles();
            return $this->requestBody;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return array    Request body or request parameters
     *                  (if any).
     */
    public function getParsedBody()
    {
        if ($this->isJson()) {
            return $this->parseJson($this->request->getBody()->getContents(), true);
        }

        return (isset($_POST)) ? $_POST : [];
    }

    /**
     * {@inheritdoc}
     *
     * @return array    Array of uploaded files (if any).
     */
    public function getUploadedFiles()
    {
        return (isset($_FILES)) ? $_FILES : [];
    }
}
