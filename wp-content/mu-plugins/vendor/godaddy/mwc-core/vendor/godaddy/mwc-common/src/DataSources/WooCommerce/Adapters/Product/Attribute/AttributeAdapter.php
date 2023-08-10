<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Product\Attribute;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Products\Attributes\Attribute;
use GoDaddy\WordPress\MWC\Common\Models\Products\Attributes\AttributeValue;
use GoDaddy\WordPress\MWC\Common\Models\Taxonomy;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use WC_Product_Attribute;

/**
 * Adapter for converting between WooCommerce product attributes and native product attributes.
 */
class AttributeAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var WC_Product_Attribute */
    protected WC_Product_Attribute $source;

    /**
     * Constructor.
     *
     * @param WC_Product_Attribute $attribute
     */
    public function __construct(WC_Product_Attribute $attribute)
    {
        $this->source = $attribute;
    }

    /**
     * Converts a source {@see WC_Product_Attribute} into a native {@see Attribute}.
     *
     * @return Attribute
     */
    public function convertFromSource() : Attribute
    {
        $sourceTaxonomyName = $this->source->get_taxonomy();
        $attributeTaxonomy = $sourceTaxonomyName ? Taxonomy::get($sourceTaxonomyName) : null;
        $attributeName = TypeHelper::string($attributeTaxonomy ? $attributeTaxonomy->getName() : $this->source->get_name(), '');
        $attributeLabel = TypeHelper::string($attributeTaxonomy ? $attributeTaxonomy->getLabel() : $this->source->get_name(), '');

        $attribute = Attribute::seed()
            ->setId(TypeHelper::int($this->source->get_id(), 0))
            ->setName(strtolower($attributeName))
            ->setLabel($attributeLabel)
            ->setIsForVariant((bool) $this->source->get_variation())
            ->setIsVisible((bool) $this->source->get_visible());

        if ($attributeTaxonomy) {
            $attribute->setTaxonomy($attributeTaxonomy);
        }

        $this->convertAttributeValuesFromSource($attribute);

        return $attribute;
    }

    /**
     * Converts the source attribute values into {@see AttributeValue} objects and sets them on the parent attribute.
     *
     * @param Attribute $attribute
     * @return void
     */
    protected function convertAttributeValuesFromSource(Attribute $attribute) : void
    {
        $nativeValues = [];

        foreach ($this->source->get_options() as $sourceValue) {
            $nativeValue = AttributeValue::getNewInstance($attribute)->setId(strtolower((string) $sourceValue));

            if (! $attribute->hasTaxonomy()) {
                $nativeValue->setLabel((string) $sourceValue);
                $nativeValue->setName(strtolower((string) $sourceValue));
            }

            $nativeValues[] = $nativeValue;
        }

        $attribute->setValues($nativeValues);
    }

    /**
     * Converts a native {@see Attribute} into a source {@see WC_Product_Attribute}.
     *
     * @NOTE at the moment the native attribute does not handle the attribute position, this may need to be set after the source attribute is returned {unfulvio 2023-03-16}
     *
     * @param Attribute|null $nativeAttribute
     * @return WC_Product_Attribute|null
     */
    public function convertToSource(Attribute $nativeAttribute = null) : ?WC_Product_Attribute
    {
        if (! $nativeAttribute) {
            return null;
        }

        $this->source->set_id($nativeAttribute->getId() ?: 0);
        $this->source->set_name($nativeAttribute->getName());
        $this->source->set_visible($nativeAttribute->isVisible());
        $this->source->set_variation($nativeAttribute->isForVariant());
        $this->source->set_options($this->convertAttributeValuesToSource($nativeAttribute));

        return  $this->source;
    }

    /**
     * Converts native attribute values into source values.
     *
     * @param Attribute $nativeAttribute
     * @return array<int|string>
     */
    protected function convertAttributeValuesToSource(Attribute $nativeAttribute) : array
    {
        $sourceValues = [];

        foreach ($nativeAttribute->getValues() as $nativeValue) {
            if (! $nativeAttribute->hasTaxonomy()) {
                $sourceValues[] = $nativeValue->getLabel();
            } elseif ($termId = TypeHelper::int($nativeValue->getId(), 0)) {
                $sourceValues[] = $termId;
            }
        }

        return $sourceValues;
    }
}
