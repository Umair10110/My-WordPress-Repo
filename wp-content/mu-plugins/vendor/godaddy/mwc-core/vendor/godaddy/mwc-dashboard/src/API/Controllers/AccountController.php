<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Platforms\Contracts\PlatformRepositoryContract;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use WP_Error;
use WP_REST_Response;

defined('ABSPATH') or exit;

/**
 * AccountController controller class.
 */
class AccountController extends AbstractController
{
    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        $this->route = 'account';
    }

    /**
     * Registers the API routes for the endpoints provided by the controller.
     */
    public function registerRoutes()
    {
        register_rest_route(
            $this->namespace, "/{$this->route}", [
                [
                    'methods'             => 'GET', // \WP_REST_Server::READABLE,
                    'callback'            => [$this, 'getItem'],
                    'permission_callback' => [$this, 'getItemsPermissionsCheck'],
                ],
                'schema' => [$this, 'getItemSchema'],
            ]
        );
    }

    /**
     * Gets the account information.
     *
     * @internal
     *
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function getItem()
    {
        $platformRepository = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository();

        $hostingPlanName = $this->getHostingPlanName($platformRepository);

        return rest_ensure_response([
            'account' => [
                'privateLabelId'       => (int) $platformRepository->getResellerId() ?: null,
                'isVersioningManual'   => (bool) Configuration::get('features.extensions.versionSelect'),
                'isOnResellerAccount'  => $platformRepository->isReseller(),
                'managedWordPressPlan' => $hostingPlanName,
                'plan'                 => $hostingPlanName,
                'platform'             => $platformRepository->getPlatformName(),
                'federationPartnerId'  => $platformRepository->getGoDaddyCustomer()->getFederationPartnerId(),
            ],
        ]);
    }

    /**
     * Gets the name of the hosting plan.
     *
     * The frontend expects to receive `null` when the hosting plan name is unknown.
     *
     * @param PlatformRepositoryContract $platformRepository
     * @return string|null
     */
    protected function getHostingPlanName(PlatformRepositoryContract $platformRepository) : ?string
    {
        return $platformRepository->getPlan()->getName() ?: null;
    }

    /**
     * Gets the schema for REST items provided by the controller.
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'account',
            'type'       => 'object',
            'properties' => [
                'privateLabelId' => [
                    'description' => __('The reseller private label ID (1 means GoDaddy, so not a reseller).', 'mwc-dashboard'),
                    'type'        => 'int',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'isVersioningManual' => [
                    'description' => __('Whether the account can manually switch between extension versions.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'isOnResellerAccount' => [
                    'description' => __('Whether or not the site is sold by a reseller.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'plan' => [
                    'description' => __('The product plan the given account or site has purchased', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view'],
                    'readonly'    => true,
                ],
                'platform' => [
                    'description' => __('The hosting platform the given account or site is running on', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view'],
                    'readonly'    => true,
                ],
                'federationPartnerId' => [
                    'description' => __('The ID of the Federation Partner', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view'],
                    'readonly'    => true,
                ],
            ],
        ];
    }
}
