<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Products\Attributes;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasStringIdentifierTrait;

/**
 * Object representation of a {@see Attribute} value.
 *
 * @method static static getNewInstance(Attribute $attribute, array $args = [])
 */
class AttributeValue
{
    use CanBulkAssignPropertiesTrait;
    use CanConvertToArrayTrait {
        toArray as protected traitToArray;
    }
    use CanGetNewInstanceTrait;
    use HasLabelTrait;
    use HasStringIdentifierTrait;

    /** @var Attribute intentionally private so it's not converted to array */
    private Attribute $attribute;

    /**
     * Constructor.
     *
     * @param Attribute $attribute
     * @param array<string, mixed> $properties
     */
    public function __construct(Attribute $attribute, array $properties = [])
    {
        $this->attribute = $attribute;

        $this->setProperties($properties);
    }

    /**
     * Determines if the value is mapped to a term.
     *
     * @return bool
     */
    public function isTerm() : bool
    {
        return null !== $this->getTerm();
    }

    /**
     * Gets the associated term, if any.
     *
     * @return Term|null
     */
    public function getTerm() : ?Term
    {
        $termId = $this->getId();
        $taxonomy = $this->attribute->getTaxonomy();

        return is_numeric($termId) && $taxonomy ? Term::get(TypeHelper::int($termId, 0), $taxonomy) : null;
    }

    /**
     * Gets the value name.
     *
     * @return string
     */
    public function getName() : string
    {
        if ($this->name !== null) {
            return $this->name;
        }

        if ($term = $this->getTerm()) {
            return TypeHelper::string($term->getName(), '');
        }

        return '';
    }

    /**
     * Gets the value label.
     *
     * @return string
     */
    public function getLabel() : string
    {
        if ($this->label !== null) {
            return $this->label;
        }

        if ($term = $this->getTerm()) {
            return TypeHelper::string($term->getLabel(), '');
        }

        return '';
    }

    /**
     * Converts the attribute value to array.
     *
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        // ensures that name and label properties are properly set when coming from a taxonomy
        if ($this->isTerm()) {
            $this->name = $this->getName();
            $this->label = $this->getLabel();
        }

        return $this->traitToArray();
    }
}
