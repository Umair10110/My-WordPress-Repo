<?php

namespace GoDaddy\WordPress\MWC\Core\Admin\Notices;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;

/**
 * Admin Dashboard notices handler class.
 */
class Notices implements ConditionalComponentContract
{
    use HasComponentsTrait;
    use IsConditionalFeatureTrait;

    /** @var string action used to dismiss a notice */
    const ACTION_DISMISS_NOTICE = 'mwc_dismiss_notice';

    /** @var string */
    const DISMISS_META_KEY_NAME = '_mwc_dismissed_notices';

    /** @var array list of enqueued admin notices */
    protected static $enqueuedAdminNotices = [];

    /**
     * Constructor.
     *
     * TODO: remove this method when {@see Package} is converted to use {@see HasComponentsTrait} {nmolham 2021-10-08}
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->load();
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function load()
    {
        $this->registerHooks();
    }

    /**
     * Register actions and filters hooks.
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
        Register::action()
            ->setGroup('admin_notices')
            ->setHandler([$this, 'renderEnqueuedAdminNotices'])
            ->execute();

        Register::action()
            ->setGroup('wp_ajax_'.static::ACTION_DISMISS_NOTICE)
            ->setHandler([$this, 'handleDismissNoticeRequest'])
            ->execute();
    }

    /**
     * Handles the dismiss notice Ajax request.
     *
     * @internal
     */
    public function handleDismissNoticeRequest()
    {
        $user = User::getCurrent();

        // gets the sanitized message ID from the $_REQUEST array
        $messageId = SanitizationHelper::input((string) ArrayHelper::get(ArrayHelper::wrap($_REQUEST), 'messageId', ''));

        $shouldDismiss = $user && $messageId;
        if ($shouldDismiss) {
            static::dismissNotice($user, $messageId);
        }

        $this->sendResponse(['success' => $shouldDismiss]);
    }

    /**
     * Renders all enqueued admin notices.
     */
    public function renderEnqueuedAdminNotices()
    {
        /** @var Notice $enqueuedAdminNotice */
        foreach (static::$enqueuedAdminNotices as $enqueuedAdminNotice) {
            if ($enqueuedAdminNotice->shouldDisplay()) {
                echo $enqueuedAdminNotice->getHtml();
            }
        }
    }

    /**
     * Sends an AJAX response.
     */
    protected function sendResponse(array $parameters) : void
    {
        (new Response())->setBody($parameters)->send();
    }

    /**
     * Marks a notice as dismissed for the given user.
     *
     * @param User $user a user object
     * @param string $messageId an identifier for the notice
     */
    public static function dismissNotice(User $user, string $messageId)
    {
        $dismissedNotices = static::getDismissedNotices($user);

        ArrayHelper::set($dismissedNotices, $messageId, true);

        static::updateDismissedNotices($user, $dismissedNotices);
    }

    /**
     * Gets an array of dismissed notices for the given user.
     *
     * The keys of the array are notice identifier and the value indicates whether
     * the notice is currently dismissed or not.
     *
     * @param User $user a user object
     *
     * @return array
     */
    protected static function getDismissedNotices(User $user) : array
    {
        return ArrayHelper::wrap(get_user_meta($user->getId(), static::DISMISS_META_KEY_NAME, true));
    }

    /**
     * Stores the array of dismissed notices for the given user.
     *
     * @param User $user a user object
     * @param array $dismissedNotices dismissed no tices for the user
     *
     * @return void
     */
    protected static function updateDismissedNotices(User $user, array $dismissedNotices)
    {
        update_user_meta($user->getId(), static::DISMISS_META_KEY_NAME, $dismissedNotices);
    }

    /**
     * Determines whether the given notice is dismissed for the given user.
     *
     * @param User $user a user object
     * @param string $messageId an identifier for the notice
     *
     * @return bool
     */
    public static function isNoticeDismissed(User $user, string $messageId) : bool
    {
        return ArrayHelper::get(static::getDismissedNotices($user), $messageId, false);
    }

    /**
     * Removes a notice from the list of dismissed notices for the given user.
     *
     * @param User $user a user object
     * @param string $messageId an identifier for the notice
     */
    public static function restoreNotice(User $user, string $messageId) : void
    {
        $dismissedNotices = static::getDismissedNotices($user);

        ArrayHelper::remove($dismissedNotices, $messageId);

        static::updateDismissedNotices($user, $dismissedNotices);
    }

    /**
     * Determines whether the component should be loaded or not.
     *
     * @return true
     */
    public static function shouldLoad() : bool
    {
        return is_admin();
    }

    /**
     * Determines whether the Email Notifications feature should load.
     *
     * TODO: remove this method when {@see Package} is converted to use {@see HasComponentsTrait} {nmolham 2021-10-08}
     *
     * @return bool
     */
    public static function shouldLoadConditionalFeature() : bool
    {
        return static::shouldLoad();
    }

    /**
     * May enqueue an admin notice if it's not dismissed yet.
     */
    public static function enqueueAdminNotice(Notice $notice)
    {
        static::$enqueuedAdminNotices[$notice->getId()] = $notice;
    }
}
