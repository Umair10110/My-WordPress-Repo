<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Auth\Providers;

use Exception;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Auth\JWT\ManagedWooCommerce\Http\Providers\KeySetProvider;

/**
 * Decodes a JWT token using the MWC API JWK.
 */
class JwtAuthProvider
{
    use CanGetNewInstanceTrait;

    /**
     * Decodes a JWT token with a known JWK (retrieved from the MWC API).
     *
     * @param string $token
     * @return array
     * @throws Exception
     */
    public function decodeToken(string $token) : array
    {
        return (array) JWT::decode($token, JWK::parseKeySet(KeySetProvider::getNewInstance()->getKeySet(), 'RS256'));
    }
}
