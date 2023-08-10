<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts;

/**
 * Has local resource identifiers (bigint).
 */
interface HasLocalIdsContract
{
    /**
     * Sets the local resource IDs.
     *
     * @param ?array<int> $ids
     * @return HasLocalIdsContract
     */
    public function setLocalIds(?array $ids) : HasLocalIdsContract;

    /**
     * Gets the local resource IDs.
     *
     * @return ?array<int>
     */
    public function getLocalIds() : ?array;
}
