<?php

namespace GoDaddy\WordPress\MWC\Core\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;

/**
 * A base for bad request Exceptions.
 */
abstract class AbstractBadRequestException extends BaseException
{
    /** @var int HTTP status code */
    protected $code = 400;
}
