<?php

namespace Waynik\Views;

use Psr\Http\Message\ResponseInterface;

/**
 * @property ResponseInterface response
 */
class Json
{
    private $response;

    /**
     * Json constructor.
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function render()
    {
        echo $this->response->getBody();
    }
}