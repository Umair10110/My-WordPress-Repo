<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Pages;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Content\AbstractAdminPage;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Http\Redirect;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedExtensionsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;
use WC_Admin_Addons;
use WC_Helper;
use WC_Helper_Updater;

/**
 * The WooCommerce Extensions page.
 */
class WooCommerceExtensionsPage extends AbstractAdminPage
{
    use IsConditionalFeatureTrait;

    /** @var string the slug of the Available Extensions tab */
    const TAB_AVAILABLE_EXTENSIONS = 'available_extensions';

    /** @var string the slug of the Browse Extensions tab */
    const TAB_BROWSE_EXTENSIONS = 'browse_extensions';

    /** @var string the slug of the Subscriptions tab */
    const TAB_SUBSCRIPTIONS = 'subscriptions';

    /** @var string ID of the div element inside which the page will be rendered */
    protected $divId;

    /** @var string String of styles to apply to the div element */
    protected $divStyles;

    /**
     * Constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->screenId = 'wc-addons';
        $this->title = __('WooCommerce extensions', 'mwc-dashboard');
        $this->menuTitle = __('Extensions', 'mwc-dashboard');
        $this->parentMenuSlug = 'woocommerce';

        $this->capability = 'manage_woocommerce';

        $this->divId = 'mwc-extensions';
        $this->divStyles = '';

        parent::__construct();

        $this->addHooks();
    }

    /**
     * Renders the page HTML.
     */
    public function renderDivContainer()
    {
        ?>
        <div id="<?php echo $this->divId; ?>" style="<?php echo $this->divStyles; ?>"></div>
        <?php
    }

    /**
     * Adds the menu page.
     *
     * @internal
     *
     * @see https://developer.wordpress.org/reference/functions/add_submenu_page/
     *
     * @return self
     * @throws Exception
     */
    public function addMenuItem() : AbstractAdminPage
    {
        if ($count = $this->getUpdatesCountHtml()) {
            $this->menuTitle = sprintf(esc_html__('Extensions %s', 'mwc-dashboard'), $count);
        }

        return parent::addMenuItem();
    }

    /**
     * Registers the page hooks.
     *
     * @throws Exception
     */
    public function addHooks()
    {
        Register::filter()
            ->setGroup('woocommerce_show_addons_page')
            ->setHandler('__return_false')
            ->setPriority(10)
            ->setArgumentsCount(1)
            ->execute();

        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeRedirectToAvailableExtensionsTab'])
            ->execute();
    }

    /**
     * Registers the menu page.
     *
     * Overridden to change the priority of the handler to 20.
     *
     * @return self
     * @throws Exception
     */
    protected function registerMenuItem() : AbstractAdminPage
    {
        try {
            if ($this->shouldAddMenuItem()) {
                Register::action()
                    ->setGroup('admin_menu')
                    ->setHandler([$this, 'addMenuItem'])
                    ->setPriority(100)
                    ->execute();
            }
        } catch (Exception $ex) {
            // TODO: log an error using a wrapper for WC_Logger {WV 2021-02-15}
            // throw new Exception('Cannot register the menu item: '.$ex->getMessage());
        }

        return $this;
    }

    /**
     * Checks if assets should be enqueued or not.
     *
     * @return bool
     */
    protected function shouldEnqueueAssets() : bool
    {
        if ($screen = $this->getCurrentScreen()) {
            return 'woocommerce_page_'.$this->screenId === $screen->id;
        }

        return false;
    }

    /**
     * Gets the current admin screen.
     *
     * TODO: move to WordPressRepository
     *
     * @return \WP_Screen|null
     */
    protected function getCurrentScreen()
    {
        return get_current_screen();
    }

    /**
     * Enqueues/loads registered assets.
     *
     * @throws Exception
     */
    protected function enqueuePageAssets()
    {
        Enqueue::style()
            ->setHandle("{$this->divId}-style")
            ->setSource(Configuration::get('mwc_extensions.assets.css.admin.url'))
            ->execute();
    }

    /**
     * Redirects the default page to the Available Extensions tab.
     *
     * @internal
     *
     * @throws Exception
     */
    public function maybeRedirectToAvailableExtensionsTab()
    {
        $page = SanitizationHelper::input((string) ArrayHelper::get($_GET, 'page'));
        $tab = SanitizationHelper::input((string) ArrayHelper::get($_GET, 'tab'));
        $section = SanitizationHelper::input((string) ArrayHelper::get($_GET, 'section'));
        $helperConnect = (bool) ArrayHelper::get($_GET, 'wc-helper-connect', false);

        /* @NOTE we need to be past the `admin_init` hook to use {@see WordPressRepository::isCurrentScreen()} here {unfulvio 2022-02-10} */
        if (WordPressRepository::isAdmin() && 'wc-addons' === $page && ! $helperConnect && ! $section && ! $tab) {
            $this->getRedirect()->setLocation(admin_url('admin.php'))
                ->setQueryParameters([
                    'page' => 'wc-addons',
                    'tab'  => self::TAB_AVAILABLE_EXTENSIONS,
                ])
                ->execute();
        }
    }

    /**
     * Returns a new redirect object.
     *
     * @return Redirect
     */
    protected function getRedirect() : Redirect
    {
        return new Redirect();
    }

    /**
     * Renders the page HTML.
     */
    public function render()
    {
        // @NOTE: Clearing at beginning and end is required as the count is loaded and cache set multiple times during page render {JO 2021-02-15}
        $this->maybeClearUpdatesCacheCount();

        $current_tab = $this->getCurrentTab(); ?>

        <div class="wrap woocommerce wc_addons_wrap mwc-dashboard-wc-addons-wrap">

            <nav class="nav-tab-wrapper woo-nav-tab-wrapper mwc-dashboard-nav-tab-wrapper">
			<?php
                foreach ($this->getTabs() as $slug => $tab) {
                    printf(
                        '<a href="%1$s" class="nav-tab%2$s">%3$s</a>',
                        esc_url($tab['url']),
                        ($current_tab === $slug) ? ' nav-tab-active' : '',
                        $tab['label']
                    );
                } ?>
            </nav>

            <h1 class="screen-reader-text"><?php esc_html_e('WooCommerce Extensions', 'woocommerce'); ?></h1>

        <?php $this->renderTab($current_tab); ?>

        </div>

        <div class="clear"></div>

        <?php

        // @NOTE: Clearing at beginning and end is required as the count is loaded and cache set multiple times during page render {JO 2021-02-15}
        $this->maybeClearUpdatesCacheCount();
    }

    /**
     * Deletes the updates count cache if the current tab is the Subscriptions tab.
     */
    private function maybeClearUpdatesCacheCount()
    {
        if ($this->getCurrentTab() === self::TAB_SUBSCRIPTIONS) {
            delete_transient('_woocommerce_helper_updates_count');
        }
    }

    /**
     * Gets the slug for the currently active tab.
     *
     * @return string
     */
    private function getCurrentTab() : string
    {
        if (! $tab = SanitizationHelper::input(ArrayHelper::get($_GET, 'tab', ''))) {
            $tab = static::TAB_AVAILABLE_EXTENSIONS;
        }

        if ($section = ArrayHelper::get($_GET, 'section')) {
            // self::TAB_SUBSCRIPTIONS necessary to support redirect requests after a merchant connects the site to WooCommerce.com and filter views in the Subscriptions tab
            // self::TAB_BROWSE_EXTENSIONS necessary to support the extensions search and extension cateogires features in the Browse Extensions tab
            $tab = $section === 'helper' ? self::TAB_SUBSCRIPTIONS : self::TAB_BROWSE_EXTENSIONS;
        }

        return $tab;
    }

    /**
     * Gets a list of tabs to render indexed by the tab slug.
     *
     * @return array[]
     * @throws PlatformRepositoryException
     */
    protected function getTabs() : array
    {
        $url = admin_url('admin.php?page=wc-addons');

        $tabs = [
            self::TAB_AVAILABLE_EXTENSIONS => [
                'label' => PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isReseller()
                    ? esc_html__('Included Extensions', 'mwc-dashboard')
                    : esc_html__('GoDaddy Included Extensions', 'mwc-dashboard'),
                'url' => $url.'&'.ArrayHelper::query(['tab' => self::TAB_AVAILABLE_EXTENSIONS]),
            ],
            self::TAB_BROWSE_EXTENSIONS => [
                'label' => esc_html__('Browse Extensions', 'woocommerce'),
                'url'   => $url.'&'.ArrayHelper::query(['tab' => self::TAB_BROWSE_EXTENSIONS]),
            ],
            self::TAB_SUBSCRIPTIONS => [
                'label' => esc_html__('WooCommerce.com Subscriptions', 'woocommerce').$this->getUpdatesCountHtml(),
                'url'   => $url.'&'.ArrayHelper::query(['tab' => self::TAB_SUBSCRIPTIONS, 'section' => 'helper']),
            ],
        ];

        if (PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getGoDaddyCustomer()->getFederationPartnerId() === 'WORLDPAY') {
            unset($tabs[self::TAB_BROWSE_EXTENSIONS]);
        }

        return $tabs;
    }

    /**
     * Gets the HTML for the number of products that have updates, with managed plugins removed from the count.
     *
     * @return string
     */
    protected function getUpdatesCountHtml() : string
    {
        $filter = Register::filter()
            ->setGroup('transient__woocommerce_helper_updates')
            ->setHandler([$this, 'removeManagedPluginsFromCount'])
            ->setPriority(10)
            ->setArgumentsCount(1);

        try {
            $filter->execute();

            $html = WC_Helper_Updater::get_updates_count_html();

            $filter->deregister();
        } catch (Exception $exception) {
            $html = '';
        }

        return $html;
    }

    /**
     * Removes managed plugins from the list of plugins that have updates.
     *
     * @internal
     *
     * @param mixed $transient_value array of cached WooCommerce plugins data
     * @return mixed
     * @throws Exception
     */
    public function removeManagedPluginsFromCount($transient_value)
    {
        // bail if not an array
        if (! ArrayHelper::accessible($transient_value)) {
            return $transient_value;
        }

        $urls = array_map(static function ($plugin) {
            return $plugin->getHomepageUrl();
        }, ManagedExtensionsRepository::getManagedPlugins());

        $transient_value['products'] = ArrayHelper::where(ArrayHelper::get($transient_value, 'products', []), static function ($value) use ($urls) {
            return ! in_array(ArrayHelper::get($value, 'url'), $urls, true);
        });

        return $transient_value;
    }

    /**
     * Renders the content for the given tab.
     *
     * @param string $currentTab
     */
    protected function renderTab(string $currentTab)
    {
        $methodName = 'render'.str_replace(' ', '', ucwords(str_replace('_', ' ', $currentTab))).'Tab';

        if (method_exists($this, $methodName)) {
            $this->{$methodName}();
        }
    }

    /**
     * Renders the content for the GoDaddy Included Extensions tab.
     */
    protected function renderAvailableExtensionsTab()
    {
        $this->renderDivContainer();
    }

    /**
     * Renders the content for the Browse Extensions tab.
     */
    protected function renderBrowseExtensionsTab()
    {
        WC_Admin_Addons::output();
    }

    /**
     * Renders the content for the Subscriptions tab.
     */
    protected function renderSubscriptionsTab()
    {
        WC_Helper::render_helper_output();
    }

    /**
     * Determines whether the feature can be loaded.
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoadConditionalFeature() : bool
    {
        return WooCommerceRepository::isWooCommerceActive()
            && PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->hasEcommercePlan();
    }
}
