<?php

namespace GoDaddy\WordPress\MWC\Common\API\Controllers;

use GoDaddy\WordPress\MWC\Common\API\Response;
use WP_REST_Response;

abstract class AbstractController
{
    /**
     * Converts the given {@see Response} into an instance of {@see WP_REST_Response}.
     *
     * @param Response $response
     * @return WP_REST_Response
     */
    protected function getWordPressResponse(Response $response) : WP_REST_Response
    {
        return new WP_REST_Response($response->getBody(), $response->getStatus() ?? 500);
    }
}
