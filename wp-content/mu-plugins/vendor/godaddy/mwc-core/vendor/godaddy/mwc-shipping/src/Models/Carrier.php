<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasStringIdentifierTrait;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\CarrierContract;

class Carrier extends AbstractModel implements CarrierContract
{
    use HasStringIdentifierTrait;
    use HasLabelTrait;
}
