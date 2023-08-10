<?php

namespace GoDaddy\WordPress\MWC\Core\API;

use GoDaddy\WordPress\MWC\Common\API\API as CommonAPI;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Core\API\Controllers\Orders\ShipmentsController;
use GoDaddy\WordPress\MWC\Core\API\Controllers\Platform\StoresController;

/**
 * Orders REST API handler.
 */
class API extends CommonAPI
{
    use HasComponentsTrait;

    /** @var class-string<ComponentContract>[] */
    protected $componentClasses = [
        ShipmentsController::class,
        StoresController::class,
    ];
}
