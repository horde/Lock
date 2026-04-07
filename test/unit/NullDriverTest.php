<?php

declare(strict_types=1);

/**
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Lock\Test\Unit;

use Horde_Lock;
use Horde_Lock_Null;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Lock_Null::class)]
class NullDriverTest extends TestCase
{
    private Horde_Lock_Null $driver;

    protected function setUp(): void
    {
        $this->driver = new Horde_Lock_Null();
    }

    public function testGetLockInfoReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->driver->getLockInfo('nonexistent-id'));
    }

    public function testGetLocksReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->driver->getLocks());
    }

    public function testGetLocksWithFiltersReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->driver->getLocks('myapp'));
        $this->assertSame([], $this->driver->getLocks('myapp', 'myprincipal'));
        $this->assertSame(
            [],
            $this->driver->getLocks('myapp', 'myprincipal', Horde_Lock::TYPE_SHARED)
        );
        $this->assertSame(
            [],
            $this->driver->getLocks(null, null, Horde_Lock::TYPE_EXCLUSIVE)
        );
    }

    public function testResetLockReturnsTrue(): void
    {
        $this->assertTrue($this->driver->resetLock('any-lock-id', 100));
    }

    public function testSetLockReturnsString(): void
    {
        $lockId = $this->driver->setLock(
            'myuser',
            'myapp',
            'myprincipal',
            100,
            Horde_Lock::TYPE_SHARED
        );

        $this->assertIsString($lockId);
        $this->assertNotEmpty($lockId);
    }

    public function testSetLockReturnsUniqueIds(): void
    {
        $lock1 = $this->driver->setLock('user', 'app', 'principal', 100);
        $lock2 = $this->driver->setLock('user', 'app', 'principal', 100);

        $this->assertNotEquals($lock1, $lock2);
    }

    public function testSetLockExclusiveReturnsString(): void
    {
        $lock1 = $this->driver->setLock(
            'user',
            'app',
            'principal',
            100,
            Horde_Lock::TYPE_EXCLUSIVE
        );

        // Null driver never blocks — even a second exclusive lock succeeds.
        $lock2 = $this->driver->setLock(
            'user',
            'app',
            'principal',
            100,
            Horde_Lock::TYPE_EXCLUSIVE
        );

        $this->assertIsString($lock1);
        $this->assertIsString($lock2);
    }

    public function testSetLockPermanentReturnsString(): void
    {
        $lockId = $this->driver->setLock(
            'user',
            'app',
            'principal',
            Horde_Lock::PERMANENT,
            Horde_Lock::TYPE_EXCLUSIVE
        );

        $this->assertIsString($lockId);
    }

    public function testClearLockReturnsTrue(): void
    {
        $this->assertTrue($this->driver->clearLock('any-lock-id'));
    }
}
