<?php

namespace GoDaddy\WordPress\MWC\Common\Database;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;

abstract class AbstractDatabaseTableAction implements ConditionalComponentContract
{
    /**
     * Gets the name of the table.
     *
     * @return string
     */
    abstract public static function getTableName() : string;

    /**
     * Gets the database table version (time of the latest version using YmdHis format).
     *
     * @return int
     */
    abstract protected static function getTableVersion() : int;

    /**
     * Gets the name of the database option that stores the version of the table.
     *
     * @return string
     */
    protected static function getTableVersionOptionName() : string
    {
        return static::getTableName().'_table_version';
    }

    /**
     * {@inheritDoc}
     */
    public static function shouldLoad() : bool
    {
        return static::getTableVersion() > TypeHelper::int(get_option(static::getTableVersionOptionName()), 0);
    }

    /**
     * Initializes the component.
     *
     * @throws WordPressDatabaseException
     * @throws BaseException
     */
    public function load() : void
    {
        $this->createTableIfNotExists();
    }

    /**
     * Creates a table if it doesn't exist.
     *
     * @throws WordPressDatabaseException
     * @throws BaseException
     */
    protected function createTableIfNotExists() : void
    {
        if (DatabaseRepository::tableExists(static::getTableName())) {
            return;
        }

        $this->createTable();

        update_option(static::getTableVersionOptionName(), static::getTableVersion());
    }

    /**
     * Creates a database table.
     *
     * @throws WordPressDatabaseException
     * @throws BaseException
     */
    abstract protected function createTable() : void;
}
