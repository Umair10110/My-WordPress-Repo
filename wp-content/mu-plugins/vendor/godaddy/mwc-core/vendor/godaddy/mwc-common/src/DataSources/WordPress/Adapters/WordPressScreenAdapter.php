<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use WP_Screen;

/**
 * WordPress screen adapter.
 */
class WordPressScreenAdapter implements DataSourceAdapterContract
{
    /** @var WP_Screen WordPress screen object */
    protected $screen;

    /**
     * Adapter constructor.
     *
     * @param WP_Screen $screen
     */
    public function __construct(WP_Screen $screen)
    {
        $this->screen = $screen;
    }

    /**
     * Gets the data for the post list page.
     *
     * For example, the 'product' post type would yield a pageId value of 'product_list' in the resulting array.
     *
     * @return array<string, string|string[]>
     */
    protected function getPostListPageData() : array
    {
        $pageId = "{$this->getNormalizedPostType()}_list";

        return [
            'pageId'       => $pageId,
            'pageContexts' => [$pageId],
        ];
    }

    /**
     * Gets the data for the add post page.
     *
     * @return array<string, string|string[]>
     */
    protected function getAddPostPageData() : array
    {
        $pageId = "add_{$this->getNormalizedPostType()}";

        return [
            'pageId'       => $pageId,
            'pageContexts' => [$pageId],
        ];
    }

    /**
     * Gets the data for the edit post page.
     *
     * @return array<string, mixed>
     */
    protected function getEditPostPageData() : array
    {
        $postId = TypeHelper::int(ArrayHelper::get($_REQUEST, 'post'), 0);

        return [
            'pageId'       => "edit_{$this->getNormalizedPostType()}",
            'pageContexts' => ["edit_{$this->getNormalizedPostType()}"],
            'objectId'     => $postId,
            'objectType'   => $this->getNormalizedPostType(),
            'objectStatus' => $this->getNormalizedPostStatus((string) $postId),
        ];
    }

    /**
     * Builds the page data array for a given WooCommerce page.
     *
     * @param string $page
     * @param string $tab
     * @param string $section
     * @return array<string, mixed>
     */
    protected function getWooCommercePageData(string $page, string $tab, string $section) : array
    {
        return [
            'pageId'       => $this->getWooCommercePageId($page, $tab, $section),
            'pageContexts' => $this->getWooCommercePageContexts($page, $tab, $section),
        ];
    }

    /**
     * Gets the data for the WooCommerce settings page.
     *
     * @return array<string, mixed>
     */
    protected function getWooCommerceSettingsPageData() : array
    {
        $tab = TypeHelper::string(ArrayHelper::get($_REQUEST, 'tab'), '');
        $section = TypeHelper::string(ArrayHelper::get($_REQUEST, 'section'), '');

        return $this->getWooCommercePageData('settings', $tab, $section);
    }

    /**
     * Gets the data for the WooCommerce admin page.
     *
     * @return array<string, mixed>
     */
    protected function getWooCommerceAdminPageData() : array
    {
        $rawPath = ArrayHelper::get($_REQUEST, 'path', '');
        $path = explode('/', is_string($rawPath) ? urldecode($rawPath) : '');

        $tab = TypeHelper::string($path[1] ?? '', '');
        $section = TypeHelper::string($path[2] ?? '', '');

        return $this->getWooCommercePageData('admin', $tab, $section);
    }

    /**
     * Generates the current screen's ID.
     *
     * @return string
     */
    protected function getGenericId() : string
    {
        $page = ArrayHelper::get($_REQUEST, 'page');

        // Sometimes, WP_Screen::id is translate-able, so in cases where it is translate-able,
        // generate a non-translate-able ID instead of using WP_Screen::id directly.
        if ($page) {
            $base = $this->screen->parent_base ?? 'admin';
            $result = SanitizationHelper::slug($base.'-'.$page);
        } else {
            $result = $this->screen->id ?? 'unknown';
        }

        return $result;
    }

    /**
     * Gets the data for a generic page.
     *
     * @return array<string, string|string[]>
     */
    protected function getGenericPageData() : array
    {
        $id = $this->getGenericId();

        return [
            'pageId'       => $id,
            'pageContexts' => [$id],
        ];
    }

    /**
     * Gets the WooCommerce settings page ID.
     *
     * @deprecated
     *
     * @param string $tab
     * @param string $section
     * @return false|string
     */
    protected function getWooCommerceSettingsPageId(string $tab, string $section)
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '3.4.1', static::class.'::getWooCommercePageId');

        return $this->getWooCommercePageId('settings', $tab, $section);
    }

    /**
     * Gets the WooCommerce page ID.
     *
     * @param string $page
     * @param string $tab
     * @param string $section
     * @return string|false
     */
    protected function getWooCommercePageId(string $page, string $tab, string $section)
    {
        $pageContexts = $this->getWooCommercePageContexts($page, $tab, $section);

        return end($pageContexts);
    }

    /**
     * Gets WooCommerce settings page contexts.
     *
     * @deprecated
     *
     * @param string $tab
     * @param string $section
     * @return string[]
     */
    protected function getWooCommerceSettingsPageContexts(string $tab, string $section) : array
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '3.4.1', static::class.'::getWooCommercePageContexts');

        return $this->getWooCommercePageContexts('settings', $tab, $section);
    }

    /**
     * Gets WooCommerce page contexts.
     *
     * @param string $page
     * @param string $tab
     * @param string $section
     * @return string[]
     */
    protected function getWooCommercePageContexts(string $page, string $tab, string $section) : array
    {
        $contexts = ["woocommerce_{$page}"];

        if (! empty($tab)) {
            $contexts[] = "woocommerce_{$page}_{$tab}";

            if (! empty($section)) {
                $contexts[] = "woocommerce_{$page}_{$tab}_{$section}";
            }
        }

        return $contexts;
    }

    /**
     * Gets the normalized post type for the current screen.
     *
     * @return string
     */
    protected function getNormalizedPostType() : string
    {
        return str_replace('shop_', '', (string) $this->screen->post_type);
    }

    /**
     * Gets the normalized post status for the current screen.
     *
     * @param string $postId
     * @return string
     */
    protected function getNormalizedPostStatus(string $postId) : string
    {
        return str_replace('wc-', '', TypeHelper::string((string) get_post_status((int) $postId), ''));
    }

    /**
     * Converts from Data Source format.
     *
     * @return array<mixed>
     */
    public function convertFromSource() : array
    {
        $id = $this->getGenericId();

        if (ArrayHelper::contains(['admin-wc-admin', 'woocommerce-wc-admin'], $id)) {
            return $this->getWooCommerceAdminPageData();
        }

        // If this is any of the woocommerce_settings pages, get data specific to that screen.
        if ('admin-wc-settings' === $id) {
            return $this->getWooCommerceSettingsPageData();
        }

        // If this is the edit or add screen for a post, get data for that screen.
        if ('post' === $this->screen->base) {
            return 'add' === $this->screen->action ? $this->getAddPostPageData() : $this->getEditPostPageData();
        }

        // If this is an edit screen for a post list, get data for that screen.
        if ('edit' === $this->screen->base) {
            return $this->getPostListPageData();
        }

        return $this->getGenericPageData();
    }

    /**
     * Converts to Data Source format.
     *
     * @return array<mixed>
     */
    public function convertToSource() : array
    {
        return [];
    }
}
