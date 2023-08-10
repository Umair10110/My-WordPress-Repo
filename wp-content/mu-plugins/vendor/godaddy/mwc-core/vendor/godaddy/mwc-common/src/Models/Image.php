<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters\ImageAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\ImageContract;
use GoDaddy\WordPress\MWC\Common\Models\Exceptions\ImageSizeNotFound;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\MediaRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasNumericIdentifierTrait;

/**
 * A model for handling image files.
 *
 * @method static static getNewInstance(array $properties = [])
 */
class Image extends AbstractModel implements ImageContract
{
    use CanBulkAssignPropertiesTrait;
    use CanGetNewInstanceTrait;
    use HasLabelTrait;
    use HasNumericIdentifierTrait;

    /** @var ImageSize[] */
    protected $sizes = [];

    /**
     * Image constructor.
     *
     * @param array<string, mixed> $properties
     */
    public function __construct(array $properties = [])
    {
        $this->setProperties($properties);
    }

    /**
     * Gets the image sizes.
     *
     * @return ImageSize[]
     */
    public function getSizes() : array
    {
        return $this->sizes;
    }

    /**
     * Determines if the image has a given size.
     *
     * @param string $sizeIdentifier
     * @return bool
     */
    public function hasSize(string $sizeIdentifier) : bool
    {
        return ArrayHelper::has($this->sizes, $sizeIdentifier);
    }

    /**
     * Gets an image size of a given type.
     *
     * @param string $sizeIdentifier
     * @return ImageSize
     * @throws ImageSizeNotFound
     */
    public function getSize(string $sizeIdentifier) : ImageSize
    {
        $size = ArrayHelper::get($this->sizes, $sizeIdentifier);

        if (! $size) {
            throw new ImageSizeNotFound(sprintf('%s size not found for image #%d.', $sizeIdentifier, $this->id));
        }

        return $size;
    }

    /**
     * Sets the image sizes.
     *
     * @param ImageSize[] $value
     * @return $this
     */
    public function setSizes(array $value) : Image
    {
        $this->sizes = $value;

        return $this;
    }

    /**
     * Fetches an image by its identifier.
     *
     * @param int $identifier image ID
     * @return Image|null
     */
    public static function get($identifier) : ?Image
    {
        $sourceImage = MediaRepository::getImage((int) $identifier);

        try {
            return $sourceImage ? ImageAdapter::getNewInstance($sourceImage)->convertFromSource() : null;
        } catch (AdapterException $exception) {
            return null;
        }
    }
}
