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

namespace RedisCachePro\ObjectCaches\Concerns;

use Throwable;

use RedisCachePro\Configuration\Configuration;

/**
 * In non-multisite environments and when the `network_flush` configuration option is set to `all`,
 * the `FLUSHDB` command is executed when `wp_cache_flush()` is called.
 *
 * When `network_flush` is set to `site`, only the current blog's cache is cleared using a Lua script.
 *
 * When `network_flush` is set to `global`, in addition to the
 * current blog's cache all global groups are flushed as well.
 */
trait FlushesNetworks
{
    /**
     * Returns `true` when `flushBlog()` should be called over `flush()`.
     *
     * @return bool
     */
    protected function shouldFlushBlog(): bool
    {
        return in_array($this->config->network_flush, [
            $this->config::NETWORK_FLUSH_SITE,
            $this->config::NETWORK_FLUSH_GLOBAL,
        ]);
    }

    /**
     * Removes all cache items for a single blog in multisite environments,
     * otherwise defaults to flushing the entire database.
     *
     * Unless the `$network_flush` parameter is given this method
     * will default to `network_flush` configuration option.
     *
     * @param  int|null  $siteId
     * @param  string|null  $network_flush
     * @return bool
     */
    public function flushBlog(int $siteId = null, string $network_flush = null): bool
    {
        if (is_null($siteId)) {
            $siteId = $this->blogId;
        }

        if (is_null($network_flush)) {
            $network_flush = $this->config->network_flush;
        }

        if (! $this->isMultisite || $network_flush === Configuration::NETWORK_FLUSH_ALL) {
            return $this->flush();
        }

        $originalBlogId = $this->blogId;
        $this->blogId = $siteId;

        $patterns = [
            preg_replace('/:{?deadf00d}?/', '', (string) $this->id('*', dechex(3735941133))),
        ];

        if ($network_flush === Configuration::NETWORK_FLUSH_GLOBAL) {
            array_push($patterns, ...array_map(function ($group) {
                return $this->id('*', $group);
            }, $this->globalGroups()));
        }

        $this->blogId = $originalBlogId;

        try {
            $this->deleteByPattern(array_filter($patterns));
        } catch (Throwable $exception) {
            $this->error($exception);

            return false;
        }

        return parent::flushBlog($siteId, $network_flush);
    }
}
