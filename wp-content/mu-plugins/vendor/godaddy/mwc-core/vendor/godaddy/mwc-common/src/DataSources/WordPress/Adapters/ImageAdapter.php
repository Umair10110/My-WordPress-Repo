<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Image;
use GoDaddy\WordPress\MWC\Common\Models\ImageSize;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\MediaRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use WP_Post;

/**
 * Adapts a WordPress image attachment into a native {@see Image} model.
 *
 * @method static static getNewInstance(WP_Post $image)
 */
class ImageAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var WP_Post */
    protected $source;

    /**
     * Constructor.
     *
     * @param WP_Post $image
     */
    public function __construct(WP_Post $image)
    {
        $this->source = $image;
    }

    /**
     * Converts a WordPress image attachment to a native {@see Image} model.
     *
     * @return Image
     * @throws AdapterException
     */
    public function convertFromSource() : Image
    {
        if (! MediaRepository::isImage($this->source)) {
            throw new AdapterException('The source item to adapt is not a valid image.');
        }

        $sizes = [];
        $image = Image::getNewInstance()
            ->setId((int) $this->source->ID)
            ->setName((string) $this->source->post_name ?: '')
            ->setLabel((string) $this->source->post_title ?: '');

        try {
            foreach ($this->getImageSizesToAdapt() as $imageSize) {
                if ($size = $this->convertSizeFromSource($imageSize)) {
                    $sizes[$size->getId()] = $size;
                }
            }
        } catch (Exception $exception) {
            throw new AdapterException($exception->getMessage(), $exception);
        }

        return $image->setSizes($sizes);
    }

    /**
     * Gets a list of image sizes to adapt.
     *
     * Will always include the 'full' original image size.
     *
     * @return string[]
     * @throws Exception
     */
    protected function getImageSizesToAdapt() : array
    {
        /** @var string[] $imageSizes */
        $imageSizes = ArrayHelper::combine(['full'], array_keys(MediaRepository::getAvailableImageSizes()));

        return array_unique($imageSizes);
    }

    /**
     * Converts a source size into a native {@see ImageSize}.
     *
     * @param string $sizeName
     * @return ImageSize|null
     */
    protected function convertSizeFromSource(string $sizeName) : ?ImageSize
    {
        $sizeData = MediaRepository::getImageSize((int) $this->source->ID, $sizeName);

        return null === $sizeData || ! isset($sizeData[0], $sizeData[1], $sizeData[2]) ? null : ImageSize::getNewInstance()
            ->setId($sizeName)
            ->setUrl((string) $sizeData[0])
            ->setHeight((int) $sizeData[2])
            ->setWidth((int) $sizeData[1]);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToSource()
    {
        // TODO: Implement convertToSource() method.
        return null;
    }
}
