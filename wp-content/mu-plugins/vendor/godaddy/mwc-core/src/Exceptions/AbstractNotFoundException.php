<?php

namespace GoDaddy\WordPress\MWC\Core\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;

/**
 * A base for not found Exceptions.
 */
abstract class AbstractNotFoundException extends BaseException
{
    /** @var int HTTP status code */
    protected $code = 404;
}
