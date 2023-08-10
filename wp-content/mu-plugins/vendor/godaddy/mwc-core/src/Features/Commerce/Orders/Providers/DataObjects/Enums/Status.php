<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\EnumTrait;

class Status
{
    use EnumTrait;

    public const Open = 'OPEN';

    public const Completed = 'COMPLETED';

    public const Canceled = 'CANCELED';
}
