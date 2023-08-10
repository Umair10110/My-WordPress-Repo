<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\HasLabelContract;
use GoDaddy\WordPress\MWC\Common\Contracts\HasNumericIdentifierContract;

/**
 * A contract for taxonomy terms.
 */
interface TaxonomyTermContact extends ModelContract, HasLabelContract, HasNumericIdentifierContract
{
    /**
     * Gets the term taxonomy.
     *
     * @return TaxonomyContract
     */
    public function getTaxonomy() : TaxonomyContract;

    /**
     * Sets the term taxonomy.
     *
     * @param TaxonomyContract $value
     * @return $this
     */
    public function setTaxonomy(TaxonomyContract $value);
}
