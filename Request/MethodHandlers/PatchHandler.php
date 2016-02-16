<?php

namespace HttpExchange\Request\MethodHandlers;

/**
 * Class PatchHandler.
 * @package HttpExchange\Request\MethodHandlers
 */
class PatchHandler extends AbstractHandler
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
        if ($request->getMethod() === 'PATCH') {
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

        if ($this->isFormData()) {
            $this->separatedBody = $this->separateRowBody($this->request->getBody()->getContents());
            return $this->pullParsedBody($this->separatedBody);
        }

        if ($this->isFormUrlencoded()) {
            return $this->parseUrlencoded($this->request->getBody()->getContents());
        }

        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @return array    Array of uploaded files (if any).
     */
    public function getUploadedFiles()
    {
        if ($this->isFormData()) {
            return $this->pullUploadedFiles($this->separatedBody);
        }
        return [];
    }
}
