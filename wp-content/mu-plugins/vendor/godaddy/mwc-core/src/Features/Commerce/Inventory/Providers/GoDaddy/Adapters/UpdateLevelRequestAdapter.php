<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests\Request;

class UpdateLevelRequestAdapter extends AbstractUpsertLevelRequestAdapter
{
    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : Request
    {
        $level = $this->input->level;

        $data = [];

        if (isset($level->quantity)) {
            ArrayHelper::set($data, 'quantity', $level->quantity);
        }

        if (isset($level->cost)) {
            ArrayHelper::set($data, 'cost', $level->cost->toArray());
        }

        return $this->getBaseRequest()
            ->setPath('/inventory-levels/'.$level->inventoryLevelId)
            ->setMethod('PATCH')
            ->setBody($data);
    }
}
