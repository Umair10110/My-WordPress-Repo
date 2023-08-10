<?php

namespace GoDaddy\WordPress\MWC\Core\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;

/**
 * A base for unauthorized Exceptions.
 */
abstract class AbstractUnauthorizedException extends BaseException
{
    /** @var int HTTP status code */
    protected $code = 401;
}
