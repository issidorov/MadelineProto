<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use danog\MadelineProto\Settings\Database\DatabaseAbstract as DatabaseDatabaseAbstract;
use danog\MadelineProto\Settings\Database\Memory;
use danog\MadelineProto\Settings\Database\Mysql;
use danog\MadelineProto\Settings\Database\Postgres;
use danog\MadelineProto\Settings\Database\Redis;
use danog\MadelineProto\Settings\DatabaseAbstract;
use InvalidArgumentException;

/**
 * This factory class initializes the correct database backend for MadelineProto.
 */
abstract class DbPropertiesFactory
{
    /**
     * Indicates a simple K-V array stored in a database backend.
     * Values can be objects or other arrays, but when lots of nesting is required, it's best to split the array into multiple arrays.
     */
    const TYPE_ARRAY = 'array';
    /**
     * @param self::TYPE_*|array $propertyType
     * @return DbType
     * @internal
     * @uses \danog\MadelineProto\Db\MemoryArray
     * @uses \danog\MadelineProto\Db\MysqlArray
     * @uses \danog\MadelineProto\Db\PostgresArray
     * @uses \danog\MadelineProto\Db\RedisArray
     */
    public static function get(DatabaseAbstract $dbSettings, string $table, $propertyType, ?DbType $value = null)
    {
        $config = $propertyType['config'] ?? [];
        $propertyType = \is_array($propertyType) ? $propertyType['type'] : $propertyType;
        $propertyType = \strtolower($propertyType);
        $class = $dbSettings instanceof DatabaseDatabaseAbstract && (!($config['enableCache'] ?? true) || !$dbSettings->getCacheTtl())
            ? __NAMESPACE__.'\\NullCache'
            : __NAMESPACE__;

        switch (true) {
            case $dbSettings instanceof Memory:
                $class .= '\\Memory';
                break;
            case $dbSettings instanceof Mysql:
                $class .= '\\Mysql';
                break;
            case $dbSettings instanceof Postgres:
                $class .= '\\Postgres';
                break;
            case $dbSettings instanceof Redis:
                $class .= '\\Redis';
                break;
            default:
                throw new InvalidArgumentException('Unknown dbType: '.$dbSettings::class);
        }

        /** @var DbType $class */
        switch (\strtolower($propertyType)) {
            case self::TYPE_ARRAY:
                $class .= 'Array';
                break;
            default:
                throw new InvalidArgumentException("Unknown $propertyType: {$propertyType}");
        }

        return $class::getInstance($table, $value, $dbSettings);
    }
}
