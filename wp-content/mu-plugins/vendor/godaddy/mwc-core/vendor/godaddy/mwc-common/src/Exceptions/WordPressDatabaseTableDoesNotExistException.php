<?php

namespace GoDaddy\WordPress\MWC\Common\Exceptions;

use Exception;

/**
 * Exception for when a database table does not exist.
 */
class WordPressDatabaseTableDoesNotExistException extends WordPressDatabaseException
{
    /**
     * Constructor.
     *
     * @param string $tableName table name
     * @throws Exception
     */
    public function __construct(string $tableName)
    {
        parent::__construct("Table {$tableName} does not exist");
    }
}
