<?php

return [
    /*
     *--------------------------------------------------------------------------
     * GoDaddy Marketplaces API
     *--------------------------------------------------------------------------
     */
    'api' => [
        'url' => defined('MWC_GDM_API_URL') ? MWC_GDM_API_URL : 'https://marketplaces.godaddy.com/api',
    ],

    /*
     *--------------------------------------------------------------------------
     * Marketplaces orders quota for plans
     *--------------------------------------------------------------------------
     */
    'plan_limits' => [
        'essentials'        => 1000,
        'essentials_GDGCPP' => 1000,
        'flex'              => 1000,
        'flex_GDGCPP'       => 1000,
        'expand'            => 2500,
        'expand_GDGCPP'     => 2500,
        'premier'           => 5000,
    ],

    /*
     *--------------------------------------------------------------------------
     * Available Sales Channels
     *--------------------------------------------------------------------------
     */
    'channels' => [
        'types' => defined('MWC_GDM_CHANNEL_TYPES')
            ? (array) MWC_GDM_CHANNEL_TYPES
            : [
                \GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel::TYPE_AMAZON => 'Amazon',
                \GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel::TYPE_EBAY   => 'eBay',
                // \GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel::TYPE_FACEBOOK => 'Facebook', // @TODO uncomment when available {unfulvio 2022-08-17}
                \GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel::TYPE_WALMART => 'Walmart',
                \GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel::TYPE_ETSY    => 'Etsy',
                \GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel::TYPE_GOOGLE  => 'Google',
            ],

        /* Google channel specific settings */
        'google' => [
            'productIdRequestRetryIntervalMinutes' => 5,
            'productIdRequestMaxAttempts'          => 4,
        ],
    ],

    /*
     *--------------------------------------------------------------------------
     * Webhooks
     *--------------------------------------------------------------------------
     */
    'webhooks' => [
        'adapters' => [
            'chatterboxProvisioned' => \GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\Webhooks\MerchantProvisionedViaChatterboxWebhookPayloadAdapter::class,
            'googleTracking'        => \GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\Webhooks\GoogleAdsTrackingWebhookPayloadAdapter::class,
            'googleVerification'    => \GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\Webhooks\GoogleVerificationWebhookPayloadAdapter::class,
            'listing'               => \GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\Webhooks\ListingWebhookPayloadAdapter::class,
            'channel'               => \GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\Webhooks\ChannelWebhookPayloadAdapter::class,
            'order'                 => \GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\Webhooks\OrderWebhookPayloadAdapter::class,
        ],
    ],

    /*
     *--------------------------------------------------------------------------
     * GoDaddy Marketplaces website
     *--------------------------------------------------------------------------
     */
    'website' => [
        'url'              => 'https://marketplaces.godaddy.com',
        'salesChannelsUrl' => 'https://store.commerce.godaddy.com',
        'commerceHubUrl'   => 'https://hub.commerce.godaddy.com',
    ],
];
