<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests\Request;

class CreateLevelRequestAdapter extends AbstractUpsertLevelRequestAdapter
{
    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : Request
    {
        $level = $this->input->level;

        $externalIds = array_map(function ($externalId) {
            return $externalId->toArray();
        }, $level->externalIds);

        $data = [
            'externalIds'         => $externalIds,
            'inventoryLocationId' => $level->inventoryLocationId,
            'productId'           => $level->productId,
            'quantity'            => $level->quantity,
            'cost'                => isset($level->cost) ? $level->cost->toArray() : null,
        ];

        return $this->getBaseRequest()
            ->setPath('/inventory-levels')
            ->setMethod('POST')
            ->setBody($data);
    }
}
