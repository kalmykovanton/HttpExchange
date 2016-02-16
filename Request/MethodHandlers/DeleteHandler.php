<?php

namespace HttpExchange\Request\MethodHandlers;

/**
 * Class DeleteHandler.
 * @package HttpExchange\Request\MethodHandlers
 */
class DeleteHandler extends AbstractHandler
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
        if ($request->getMethod() === 'DELETE') {
            $this->request = $request;
            $this->requestBody['parsedBody'] = $this->getParsedBody();
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
    public function getUploadedFiles() {}
}
