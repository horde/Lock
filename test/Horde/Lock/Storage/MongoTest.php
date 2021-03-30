<?php
/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL
 * @package    Lock
 * @subpackage UnitTests
 */
namespace Horde\Lock\Storage;
use Horde_Lock_Storage_TestBase as TestBase;

class MongoTest extends TestBase
{
    private $_dbname = 'horde_lock_mongodbtest';
    private $_mongo;

    protected function _getBackend()
    {
        if (($config = self::getConfig('LOCK_MONGO_TEST_CONFIG', __DIR__ . '/..')) &&
            isset($config['lock']['mongo'])) {
            $factory = new Horde_Test_Factory_Mongo();
            $this->_mongo = $factory->create(array(
                'config' => $config['lock']['mongo'],
                'dbname' => $this->_dbname
            ));
        }

        if (empty($this->_mongo)) {
            $this->markTestSkipped('MongoDB not available.');
        }

        return new Horde_Lock_Mongo(array(
            'mongo_db' => $this->_mongo,
        ));
    }

    public function tearDown(): void
    {
        if (!empty($this->_mongo)) {
            $this->_mongo->selectDB(null)->drop();
        }

        parent::tearDown();
    }

}
