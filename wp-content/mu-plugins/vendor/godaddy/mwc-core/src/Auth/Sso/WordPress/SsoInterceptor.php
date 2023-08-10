<?php

namespace GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress;

use Exception;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\JwtAuthServiceException;
use GoDaddy\WordPress\MWC\Common\Auth\JWT\Contracts\TokenContract;
use GoDaddy\WordPress\MWC\Common\Auth\JWT\JwtAuthFactory;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\JwtDecoderException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Exceptions\UserLogInException;
use GoDaddy\WordPress\MWC\Common\Exceptions\ValidationException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Http\Redirect;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\JWT\Contracts\SsoTokenContract;

/**
 * Intercepts a request with the SSO query args set, and attempts to sign the user on.
 */
class SsoInterceptor extends AbstractInterceptor
{
    /**
     * {@inheritDoc}
     */
    public function addHooks()
    {
        Register::action()
                ->setGroup('init')
                ->setHandler([$this, 'maybeHandleSso'])
                ->setPriority(-1)
                ->execute();
    }

    /**
     * {@inheritDoc}
     */
    public static function shouldLoad() : bool
    {
        return Configuration::get('wordpress.sso_enabled', false);
    }

    /**
     * Checks if the query args are set and attempts to sign the user on.
     *
     * @internal
     *
     * @return void
     */
    public function maybeHandleSso() : void
    {
        $jwt = ArrayHelper::get($_GET, 'sso');

        if (empty($jwt)) {
            // no query arg provided, bail
            return;
        }

        try {
            $ssoType = ArrayHelper::get($_GET, 'ssotypeid');
            if (empty($ssoType)) {
                throw new JwtAuthServiceException('SSO type query argument not provided.');
            }

            $token = $this->getDecodedToken($jwt, $ssoType);

            if (! $token instanceof SsoTokenContract) {
                throw new JwtAuthServiceException('Token is not an SsoTokenContract.');
            }

            $this->handleValidToken($token);
        } catch (Exception $exception) {
            $this->handleError($exception);
        }
    }

    /**
     * Gets the decoded token from the encoded JWT.
     *
     * @param string $jwt
     * @param string $ssoType
     *
     * @return TokenContract
     * @throws JwtAuthServiceException|ValidationException|JwtDecoderException
     */
    protected function getDecodedToken(string $jwt, string $ssoType) : TokenContract
    {
        return JwtAuthFactory::getNewInstance()
                             ->getServiceByType($ssoType)
                             ->decodeToken($jwt);
    }

    /**
     * Handles a valid token.
     *
     * Signs the user on and removes the query args.
     *
     * @param SsoTokenContract $token
     * @return void
     * @throws UserLogInException|Exception
     */
    protected function handleValidToken(SsoTokenContract $token) : void
    {
        $user = User::getByHandle(SanitizationHelper::username($token->getUsername()));

        if (! $user) {
            throw new UserLogInException(sprintf(__('User %s could not be logged in.', 'mwc-core'), $token->getUsername()));
        }

        if (! $user->isLoggedIn()) {
            $user->logIn();
        }

        Redirect::to(remove_query_arg(['sso', 'ssotypeid'], $_SERVER['REQUEST_URI']))->execute();
    }

    /**
     * Handles an error.
     *
     * Redirects to the WordPress login page.
     *
     * @param Exception $exception
     * @return void
     */
    protected function handleError(Exception $exception) : void
    {
        new SentryException("Failed SSO attempt: {$exception->getMessage()}", $exception);

        // remove the query args
        $redirectTo = remove_query_arg(['sso', 'ssotypeid']);

        // set this so that WP does not add the sso query args back when redirecting
        $_SERVER['REQUEST_URI'] = $redirectTo;

        try {
            // redirect to login page
            Redirect::to(wp_login_url(home_url($redirectTo)))->execute();
        } catch (SentryException $exception) {
            // the error will be automatically reported to sentry
        } catch (Exception $exception) {
            new SentryException('Failed to redirect to log in page', $exception);
        }
    }
}
