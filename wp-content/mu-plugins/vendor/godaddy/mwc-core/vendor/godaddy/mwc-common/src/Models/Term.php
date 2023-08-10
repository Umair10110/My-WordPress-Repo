<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters\TaxonomyTermAdapter;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\TaxonomyContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\TaxonomyTermContact;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\TermsRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasNumericIdentifierTrait;
use WP_Term;

/**
 * A base abstract class for taxonomy term models.
 *
 * @method static static getNewInstance(TaxonomyContract $taxonomy, array $properties = [])
 */
class Term extends AbstractModel implements TaxonomyTermContact
{
    use CanBulkAssignPropertiesTrait;
    use HasNumericIdentifierTrait;
    use HasLabelTrait;

    /** @var TaxonomyContract */
    protected $taxonomy;

    /**
     * Term constructor.
     *
     * @param TaxonomyContract $taxonomy
     * @param array<string, mixed> $properties
     */
    public function __construct(TaxonomyContract $taxonomy, array $properties = [])
    {
        $this->setTaxonomy($taxonomy);
        $this->setProperties($properties);
    }

    /**
     * Gets a term instance.
     *
     * @NOTE if querying a term only by its ID without specifying a taxonomy, you should check if the taxonomy is the expected type as any term with the given ID will be returned, if found {unfulvio 2022-09-08}
     *
     * @param int|string $identifier term ID (integer) or slug (string)
     * @param TaxonomyContract|null $taxonomy taxonomy is required if the identifier is a string/slug
     * @return Term|null
     */
    public static function get($identifier, TaxonomyContract $taxonomy = null) : ?Term
    {
        $term = TermsRepository::getTerm($identifier, $taxonomy ? $taxonomy->getName() : '');

        if ($term instanceof WP_Term) {
            return TaxonomyTermAdapter::getNewInstance($term)->convertFromSource();
        }

        return null;
    }

    /**
     * Gets the term taxonomy.
     *
     * @return TaxonomyContract
     */
    public function getTaxonomy() : TaxonomyContract
    {
        return $this->taxonomy;
    }

    /**
     * Sets the term taxonomy.
     *
     * @param TaxonomyContract $value
     * @return $this
     */
    public function setTaxonomy(TaxonomyContract $value) : Term
    {
        $this->taxonomy = $value;

        return $this;
    }
}
