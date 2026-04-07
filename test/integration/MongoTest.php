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
use Horde_Lock_Mongo;
use Horde_Test_Factory_Mongo;
use PHPUnit\Framework\Attributes\CoversNothing;
use StorageTestBase;

#[CoversNothing]
class MongoTest extends StorageTestBase
{
    private string $_dbname = 'horde_lock_mongodbtest';
    private mixed $_mongo = null;

    protected function _getBackend(): Horde_Lock
    {
        $config = null;
        $envConfig = getenv('LOCK_MONGO_TEST_CONFIG');
        if ($envConfig) {
            $json = json_decode($envConfig, true);
            if ($json) {
                $config = $json;
            } elseif (file_exists($envConfig)) {
                require $envConfig;
            }
        } else {
            $confFile = __DIR__ . '/conf.php';
            if (file_exists($confFile)) {
                require $confFile;
            }
        }

        if (isset($conf)) {
            $config = $conf;
        }

        if ($config && isset($config['lock']['mongo'])) {
            $factory = new Horde_Test_Factory_Mongo();
            $this->_mongo = $factory->create([
                'config' => $config['lock']['mongo'],
                'dbname' => $this->_dbname,
            ]);
        }

        if (empty($this->_mongo)) {
            $this->markTestSkipped('MongoDB not available.');
        }

        return new Horde_Lock_Mongo([
            'mongo_db' => $this->_mongo,
        ]);
    }

    protected function tearDown(): void
    {
        if (!empty($this->_mongo)) {
            $this->_mongo->selectDB(null)->drop();
        }

        parent::tearDown();
    }
}
