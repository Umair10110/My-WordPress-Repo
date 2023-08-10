<?php

namespace GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\JWT\ManagedWooCommerce;

use GoDaddy\WordPress\MWC\Common\Auth\JWT\Token as CommonToken;
use GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\JWT\Contracts\SsoTokenContract;

/**
 * SSO JWT token object provided by the MWC API.
 */
class Token extends CommonToken implements SsoTokenContract
{
    /**
     * Gets the WordPress username.
     *
     * @return string
     */
    public function getUsername() : string
    {
        return (string) $this->getData('sub', '');
    }

    /**
     * Gets the customer ID.
     *
     * @return string
     */
    public function getCustomerId() : string
    {
        return (string) $this->getData('godaddy:customerId', '');
    }
}
