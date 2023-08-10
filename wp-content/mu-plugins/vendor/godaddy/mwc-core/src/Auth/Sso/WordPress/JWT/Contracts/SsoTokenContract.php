<?php

namespace GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\JWT\Contracts;

use GoDaddy\WordPress\MWC\Common\Auth\JWT\Contracts\TokenContract;

/**
 * Contract for object representations of a token that can be used for SSO.
 */
interface SsoTokenContract extends TokenContract
{
    /**
     * Gets the WordPress username to login as.
     *
     * @return string
     */
    public function getUsername() : string;

    /**
     * Gets the customer ID.
     *
     * @return string
     */
    public function getCustomerId() : string;
}
