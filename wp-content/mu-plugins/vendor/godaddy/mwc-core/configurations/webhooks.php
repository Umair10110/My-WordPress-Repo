<?php

return [
    [
        'namespace'  => 'poynt',
        'eventClass' => \GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\WebhookReceivedEvent::class,
    ],
    [
        'namespace'  => 'marketplaces',
        'eventClass' => \GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\WebhookReceivedEvent::class,
    ],
];
