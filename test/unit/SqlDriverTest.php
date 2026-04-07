<?php

declare(strict_types=1);

/**
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Lock\Test\Unit;

use Horde_Db_Adapter;
use Horde_Lock;
use Horde_Lock_Exception;
use Horde_Lock_Sql;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Lock_Sql::class)]
class SqlDriverTest extends TestCase
{
    public function testConstructorRequiresDbParam(): void
    {
        $this->expectException(Horde_Lock_Exception::class);
        $this->expectExceptionMessage('Missing db parameter');

        new Horde_Lock_Sql();
    }

    public function testConstructorDefaultTableName(): void
    {
        $db = $this->createStub(Horde_Db_Adapter::class);
        $driver = new Horde_Lock_Sql(['db' => $db]);

        // getLockInfo will exercise the table param in the SQL query.
        // We verify the default table name by checking the SQL passed to selectOne.
        $db = $this->createMock(Horde_Db_Adapter::class);
        $db->expects($this->once())
            ->method('selectOne')
            ->with(
                $this->stringContains('horde_locks'),
                $this->anything()
            )
            ->willReturn([]);

        $driver = new Horde_Lock_Sql(['db' => $db]);
        $driver->getLockInfo('test-id');
    }

    public function testConstructorCustomTableName(): void
    {
        $db = $this->createMock(Horde_Db_Adapter::class);
        $db->expects($this->once())
            ->method('selectOne')
            ->with(
                $this->stringContains('custom_locks'),
                $this->anything()
            )
            ->willReturn([]);

        $driver = new Horde_Lock_Sql(['db' => $db, 'table' => 'custom_locks']);
        $driver->getLockInfo('test-id');
    }

    public function testClearLockThrowsOnEmptyId(): void
    {
        $db = $this->createStub(Horde_Db_Adapter::class);
        $driver = new Horde_Lock_Sql(['db' => $db]);

        $this->expectException(Horde_Lock_Exception::class);
        $this->expectExceptionMessage('Must supply a valid lock ID');

        $driver->clearLock('');
    }

    public function testClearLockDelegatesToDb(): void
    {
        $db = $this->createMock(Horde_Db_Adapter::class);
        $db->expects($this->once())
            ->method('delete')
            ->with(
                $this->stringContains('DELETE FROM horde_locks WHERE lock_id = ?'),
                $this->equalTo(['some-lock-id'])
            );

        $driver = new Horde_Lock_Sql(['db' => $db]);
        $result = $driver->clearLock('some-lock-id');

        $this->assertTrue($result);
    }

    public function testGetLockInfoDelegatesToDb(): void
    {
        $expectedRow = [
            'lock_id' => 'abc-123',
            'lock_owner' => 'testuser',
            'lock_scope' => 'testapp',
            'lock_principal' => 'resource',
            'lock_origin_timestamp' => 1000,
            'lock_update_timestamp' => 1000,
            'lock_expiry_timestamp' => 1100,
            'lock_type' => Horde_Lock::TYPE_SHARED,
        ];

        $db = $this->createMock(Horde_Db_Adapter::class);
        $db->expects($this->once())
            ->method('selectOne')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('SELECT'),
                    $this->stringContains('horde_locks'),
                    $this->stringContains('lock_id = ?')
                ),
                $this->callback(function (array $values): bool {
                    return $values[0] === 'abc-123'
                        && is_int($values[1])
                        && $values[2] === Horde_Lock::PERMANENT;
                })
            )
            ->willReturn($expectedRow);

        $driver = new Horde_Lock_Sql(['db' => $db]);
        $info = $driver->getLockInfo('abc-123');

        $this->assertSame($expectedRow, $info);
    }

    public function testSetLockDeniedWhenExclusiveLockExists(): void
    {
        $existingLock = [
            'existing-lock' => [
                'lock_id' => 'existing-lock',
                'lock_owner' => 'otheruser',
                'lock_scope' => 'myapp',
                'lock_principal' => 'resource',
                'lock_type' => Horde_Lock::TYPE_EXCLUSIVE,
            ],
        ];

        $db = $this->createMock(Horde_Db_Adapter::class);
        $db->expects($this->once())
            ->method('select')
            ->willReturn([$existingLock['existing-lock']]);

        $driver = new Horde_Lock_Sql(['db' => $db]);
        $result = $driver->setLock(
            'myuser',
            'myapp',
            'resource',
            100,
            Horde_Lock::TYPE_SHARED
        );

        $this->assertFalse($result);
    }

    public function testSetLockGrantedWhenNoConflict(): void
    {
        $db = $this->createMock(Horde_Db_Adapter::class);
        $db->expects($this->once())
            ->method('select')
            ->willReturn([]);
        $db->expects($this->once())
            ->method('insert')
            ->with(
                $this->stringContains('INSERT INTO horde_locks'),
                $this->callback(fn(mixed $v): bool => is_array($v)),
                $this->anything(),
                $this->anything(),
                $this->callback(fn(mixed $v): bool => is_string($v)),
                $this->anything()
            );

        $driver = new Horde_Lock_Sql(['db' => $db]);
        $lockId = $driver->setLock(
            'myuser',
            'myapp',
            'resource',
            100,
            Horde_Lock::TYPE_SHARED
        );

        $this->assertIsString($lockId);
        $this->assertNotEmpty($lockId);
    }

    public function testSetLockExclusiveChecksAllTypes(): void
    {
        // When requesting exclusive lock, getLocks should check for ANY existing lock (type=null)
        $db = $this->createMock(Horde_Db_Adapter::class);
        $db->expects($this->once())
            ->method('select')
            ->with(
                // The SQL should NOT contain lock_type filter when type is null
                $this->logicalAnd(
                    $this->stringContains('horde_locks'),
                    $this->stringContains('lock_principal = ?'),
                    $this->stringContains('lock_scope = ?')
                ),
                $this->callback(fn(mixed $v): bool => is_array($v))
            )
            ->willReturn([]);
        $db->expects($this->once())
            ->method('insert');

        $driver = new Horde_Lock_Sql(['db' => $db]);
        $driver->setLock(
            'myuser',
            'myapp',
            'resource',
            100,
            Horde_Lock::TYPE_EXCLUSIVE
        );
    }

    public function testDoGCDeletesExpiredLocks(): void
    {
        $db = $this->createMock(Horde_Db_Adapter::class);
        $db->expects($this->once())
            ->method('delete')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('DELETE FROM horde_locks'),
                    $this->stringContains('lock_expiry_timestamp < ?'),
                    $this->stringContains('lock_expiry_timestamp != ?')
                ),
                $this->callback(function (array $values): bool {
                    return is_int($values[0])
                        && $values[1] === Horde_Lock::PERMANENT;
                })
            )
            ->willReturn(3);

        $driver = new Horde_Lock_Sql(['db' => $db]);
        $driver->doGC();
    }

    public function testResetLockReturnsFalseForMissingLock(): void
    {
        $db = $this->createMock(Horde_Db_Adapter::class);
        $db->expects($this->once())
            ->method('selectOne')
            ->willReturn([]);

        $driver = new Horde_Lock_Sql(['db' => $db]);
        $result = $driver->resetLock('nonexistent-id', 100);

        $this->assertFalse($result);
    }

    public function testResetLockUpdatesTimestamps(): void
    {
        $existingLock = [
            'lock_id' => 'abc-123',
            'lock_owner' => 'user',
            'lock_scope' => 'app',
            'lock_principal' => 'resource',
            'lock_origin_timestamp' => 1000,
            'lock_update_timestamp' => 1000,
            'lock_expiry_timestamp' => 1100,
            'lock_type' => Horde_Lock::TYPE_EXCLUSIVE,
        ];

        $db = $this->createMock(Horde_Db_Adapter::class);
        $db->expects($this->once())
            ->method('selectOne')
            ->willReturn($existingLock);
        $db->expects($this->once())
            ->method('update')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('UPDATE horde_locks'),
                    $this->stringContains('lock_update_timestamp = ?'),
                    $this->stringContains('lock_expiry_timestamp = ?'),
                    $this->stringContains('lock_expiry_timestamp <> ?')
                ),
                $this->callback(function (array $values): bool {
                    // [now, now+200, lockid, PERMANENT]
                    return is_int($values[0])
                        && is_int($values[1])
                        && $values[2] === 'abc-123'
                        && $values[3] === Horde_Lock::PERMANENT;
                })
            );

        $driver = new Horde_Lock_Sql(['db' => $db]);
        $result = $driver->resetLock('abc-123', 200);

        $this->assertTrue($result);
    }

    public function testGetLocksWithFilters(): void
    {
        $db = $this->createMock(Horde_Db_Adapter::class);
        $db->expects($this->once())
            ->method('select')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('horde_locks'),
                    $this->stringContains('lock_principal = ?'),
                    $this->stringContains('lock_scope = ?'),
                    $this->stringContains('lock_type = ?')
                ),
                $this->callback(fn(mixed $v): bool => is_array($v))
            )
            ->willReturn([
                [
                    'lock_id' => 'lock-1',
                    'lock_owner' => 'user',
                    'lock_scope' => 'myapp',
                    'lock_principal' => 'res',
                    'lock_origin_timestamp' => 1000,
                    'lock_update_timestamp' => 1000,
                    'lock_expiry_timestamp' => 1100,
                    'lock_type' => Horde_Lock::TYPE_SHARED,
                ],
            ]);

        $driver = new Horde_Lock_Sql(['db' => $db]);
        $locks = $driver->getLocks('myapp', 'res', Horde_Lock::TYPE_SHARED);

        $this->assertCount(1, $locks);
        $this->assertArrayHasKey('lock-1', $locks);
    }
}
