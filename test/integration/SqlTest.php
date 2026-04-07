<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Lock\Test\Integration;

use Horde_Lock;
use Horde_Lock_Sql;
use Horde_Test_Factory_Db;
use PHPUnit\Framework\Attributes\CoversClass;
use StorageTestBase;

#[CoversClass(Horde_Lock_Sql::class)]
class SqlTest extends StorageTestBase
{
    protected static string $_migrationDir;

    public static function setUpBeforeClass(): void
    {
        $migrationDir = __DIR__ . '/../../migration/Horde/Lock';

        if (!is_dir($migrationDir)) {
            self::markTestSkipped('Migration directory not found.');
        }

        self::$_migrationDir = $migrationDir;
    }

    protected function _getBackend(): Horde_Lock
    {
        $factory_db = new Horde_Test_Factory_Db();

        $db = $factory_db->create([
            'migrations' => [
                'migrationsPath' => self::$_migrationDir,
            ],
        ]);

        return new Horde_Lock_Sql([
            'db' => $db,
        ]);
    }
}
