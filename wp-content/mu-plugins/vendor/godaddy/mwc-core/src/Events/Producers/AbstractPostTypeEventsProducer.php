<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Producers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\ProducerContract;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\WooCommerce\NewWooCommerceObjectFlag;
use WP_Post;

/**
 * Abstract class for triggering created and updated events for objects stored as WordPress custom post types.
 */
abstract class AbstractPostTypeEventsProducer implements ProducerContract
{
    /**
     * Returns the WordPress post type that this producer will broadcast events for.
     *
     * @return string
     */
    abstract protected function getPostType() : string;

    /**
     * Sets up the events producer.
     *
     * @throws Exception
     * @deprecated
     */
    public function setup() : void
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '2.18.1', __CLASS__.'::load');

        $this->load();
    }

    /**
     * Loads the component.
     *
     * @return void
     * @throws Exception
     */
    public function load() : void
    {
        $this->registerNewObjectFlagHooks();
        $this->registerBroadcastEventsHooks();
    }

    /**
     * Registers the hook to flag new objects.
     *
     * @return void
     * @throws Exception
     */
    protected function registerNewObjectFlagHooks() : void
    {
        Register::action()
            ->setGroup("save_post_{$this->getPostType()}")
            ->setHandler([$this, 'maybeFlagNewObject'])
            ->setArgumentsCount(3)
            ->execute();
    }

    /**
     * Registers hooks to maybe broadcast events.
     *
     * @return void
     */
    abstract protected function registerBroadcastEventsHooks() : void;

    /**
     * Turns the new object flag on when a new post is created.
     *
     * @param int $postId
     * @param WP_Post $post
     * @param bool $isUpdate
     *
     * @return void
     */
    public function maybeFlagNewObject($postId, $post, $isUpdate) : void
    {
        if (! $isUpdate) {
            NewWooCommerceObjectFlag::getNewInstance((int) $postId)->turnOn();
        }
    }

    /**
     * Broadcasts created/updated events.
     *
     * @param int $postId
     */
    protected function broadcastObjectEvents(int $postId) : void
    {
        $newObjectFlag = NewWooCommerceObjectFlag::getNewInstance($postId);

        if ($newObjectFlag->isOn()) {
            $this->broadcastObjectCreatedEvent($postId);

            $newObjectFlag->turnOff();
        } else {
            $this->broadcastObjectUpdatedEvent($postId);
        }
    }

    /**
     * Broadcasts the object's Created event.
     *
     * @param int $postId
     * @return void
     */
    abstract protected function broadcastObjectCreatedEvent(int $postId) : void;

    /**
     * Broadcasts the object's Updated event.
     *
     * @param int $postId
     * @return void
     */
    abstract protected function broadcastObjectUpdatedEvent(int $postId) : void;
}
