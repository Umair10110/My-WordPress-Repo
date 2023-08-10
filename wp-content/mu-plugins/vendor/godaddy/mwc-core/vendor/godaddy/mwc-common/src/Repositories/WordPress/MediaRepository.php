<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WordPress;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use WP_Post;

/**
 * Repository to handle WordPress media, such as attachments and images.
 */
class MediaRepository
{
    /**
     * Determines whether an item is a WordPress image media attachment.
     *
     * @param WP_Post|mixed $item
     * @return bool
     */
    public static function isImage($item) : bool
    {
        return $item instanceof WP_Post && 'attachment' === $item->post_type && wp_attachment_is_image($item);
    }

    /**
     * Gets a WordPress image media attachment object.
     *
     * @param int $id
     * @return WP_Post|null
     */
    public static function getImage(int $id) : ?WP_Post
    {
        $attachment = get_post($id);

        return is_object($attachment) && static::isImage($attachment) ? $attachment : null;
    }

    /**
     * Gets WordPress available image sizes.
     *
     * @return array<string, array<string, int|bool>> image size data
     */
    public static function getAvailableImageSizes() : array
    {
        $imageSizes = wp_get_registered_image_subsizes();

        return ArrayHelper::accessible($imageSizes) ? $imageSizes : [];
    }

    /**
     * Gets size data for an image.
     *
     * @param int $identifier
     * @param string $sizeName
     * @return array<bool|int|string>|null
     */
    public static function getImageSize(int $identifier, string $sizeName) : ?array
    {
        $imageData = wp_get_attachment_image_src($identifier, $sizeName);

        /* @phpstan-ignore-next-line */
        return ArrayHelper::accessible($imageData) ? $imageData : null;
    }
}
