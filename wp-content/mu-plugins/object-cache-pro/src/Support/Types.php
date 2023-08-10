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

namespace RedisCachePro\Support;

class PluginApiResponse
{
    //
}

class AnalyticsConfiguration
{
    /** @var bool */
    public $enabled;

    /** @var bool */
    public $persist;

    /** @var int */
    public $retention;

    /** @var bool */
    public $footnote;
}

class RelayConfiguration
{
    /** @var bool */
    public $cache;

    /** @var bool */
    public $listeners;

    /** @var bool */
    public $invalidations;

    /** @var ?array<string> */
    public $allowed;

    /** @var ?array<string> */
    public $ignored;
}

class ObjectCacheInfo
{
    /** @var bool */
    public $status;

    /** @var object */
    public $groups;

    /** @var array<string> */
    public $errors;

    /** @var array<string, string> */
    public $meta;
}

class ObjectCacheMetricsGroup
{
    /** @var int */
    public $keys = 0;

    /** @var int */
    public $memory = 0;

    /** @var float */
    public $wait = 0.0;
}
