<?php
/**
 * Copyright © 2019-2023 Rhubarb Tech Inc. All Rights Reserved.
 *
 * The Object Cache Pro Software and its related materials are property and confidential
 * information of Rhubarb Tech Inc. Any reproduction, use, distribution, or exploitation
 * of the Object Cache Pro Software and its related materials, in whole or in part,
 * is strictly forbidden unless prior permission is obtained from Rhubarb Tech Inc.
 *
 * In addition, any reproduction, use, distribution, or exploitation of the Object Cache Pro
 * Software and its related materials, in whole or in part, is subject to the End-User License
 * Agreement accessible in the included `LICENSE` file, or at: https://objectcache.pro/eula
 */

declare(strict_types=1);

namespace RedisCachePro\Plugin;

use RedisCachePro\ObjectCaches\MeasuredObjectCacheInterface;

/**
 * @mixin \RedisCachePro\Plugin
 */
trait Analytics
{
    /**
     * Boot analytics component.
     *
     * @return void
     */
    public function bootAnalytics()
    {
        global $wp_object_cache;

        add_action('rest_api_init', [new Api\Analytics, 'register_routes']);

        if (! $this->analyticsEnabled()) {
            return;
        }

        if (! $wp_object_cache instanceof MeasuredObjectCacheInterface) {
            return;
        }

        add_action('wp_footer', [$this, 'shouldPrintMetricsComment']);
        add_action('wp_body_open', [$this, 'shouldPrintMetricsComment']);
        add_action('login_head', [$this, 'shouldPrintMetricsComment']);
        add_action('in_admin_header', [$this, 'shouldPrintMetricsComment']);
        add_action('rss_tag_pre', [$this, 'shouldPrintMetricsComment']);

        add_action('shutdown', [$this, 'maybePrintMetricsComment'], PHP_INT_MAX);

        add_action('objectcache_prune_analytics', [$this, 'pruneAnalytics']);

        if (wp_doing_cron() && ! wp_next_scheduled('objectcache_prune_analytics')) {
            wp_schedule_event(time(), 'hourly', 'objectcache_prune_analytics');
        }
    }

    /**
     * Whether analytics are enabled.
     *
     * @return bool
     */
    public function analyticsEnabled()
    {
        return $this->config->analytics->enabled;
    }

    /**
     * Callback for the scheduled `objectcache_prune_analytics` hook.
     *
     * @return void
     */
    public function pruneAnalytics()
    {
        global $wp_object_cache;

        $wp_object_cache->pruneMeasurements();
    }

    /**
     * Print the request's metrics as HTML comment.
     *
     * @return bool|void
     */
    public function shouldPrintMetricsComment()
    {
        static $shouldPrint;

        /**
         * Filters whether the analytics footnote is printed.
         *
         * @param  bool  $omit  Whether to omit printing the analytics footnote.
         */
        if ((bool) apply_filters('objectcache_omit_analytics_footnote', false)) {
            return;
        }

        if (doing_action('shutdown')) {
            return $shouldPrint;
        }

        $shouldPrint = true;
    }

    /**
     * Print the request's metrics as HTML comment.
     *
     * @return void
     */
    public function maybePrintMetricsComment()
    {
        global $wp_object_cache;

        if (
            ! \WP_DEBUG
            && ! $this->config->debug
            && ! $this->config->analytics->footnote
        ) {
            return;
        }

        if (! $this->shouldPrintMetricsComment()) {
            return;
        }

        if (is_robots() || is_trackback()) {
            return;
        }

        if (
            (defined('\WP_CLI') && constant('\WP_CLI')) ||
            (defined('\REST_REQUEST') && constant('\REST_REQUEST')) ||
            (defined('\XMLRPC_REQUEST') && constant('\XMLRPC_REQUEST')) ||
            (defined('\DOING_AJAX') && constant('\DOING_AJAX')) ||
            (defined('\DOING_CRON') && constant('\DOING_CRON')) ||
            (defined('\DOING_AUTOSAVE') && constant('\DOING_AUTOSAVE')) ||
            (function_exists('wp_is_json_request') && wp_is_json_request()) ||
            (function_exists('wp_is_jsonp_request') && wp_is_jsonp_request())
        ) {
            return;
        }

        if ($this->incompatibleContentType()) {
            return;
        }

        if (! $measurement = $wp_object_cache->requestMeasurement()) {
            return;
        }

        printf(
            "\n<!-- plugin=%s client=%s %s -->\n",
            'object-cache-pro',
            strtolower($wp_object_cache->clientName()),
            (string) $measurement
        );
    }

    /**
     * Whether the sent headers are incompatible with HTML comments.
     *
     * @see RedisCachePro\Plugin\Analytics::maybePrintMetricsComment()
     *
     * @return bool
     */
    protected function incompatibleContentType()
    {
        $jsonContentType = static function ($headers) {
            foreach ($headers as $header => $value) {
                if (stripos((string) $header, 'content-type') === false) {
                    continue;
                }

                if (stripos((string) $value, '/json') === false) {
                    continue;
                }

                return true;
            }

            return false;
        };

        if (function_exists('headers_list')) {
            $headers = [];

            foreach (headers_list() as $header) {
                [$name, $value] = explode(':', $header);
                $headers[$name] = $value;
            }

            if ($jsonContentType($headers)) {
                return true;
            }
        }

        if (function_exists('apache_response_headers')) {
            if ($headers = apache_response_headers()) {
                return $jsonContentType($headers);
            }
        }

        return false;
    }
}
