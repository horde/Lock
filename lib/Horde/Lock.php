<?php
/**
 * The Horde_Lock class provides an API to create, store, check and expire locks
 * based on a given resource URI.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-license.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Horde_Lock
 */
class Horde_Lock
{
    /* Class constants. */
    const TYPE_EXCLUSIVE = 1;
    const TYPE_SHARED = 2;

    /**
     * Singleton instances.
     *
     * @var array
     */
    static protected $_instances = array();

    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @param mixed $driver  The type of concrete subclass to return.
     *                       This is based on the storage driver ($driver).
     *                       The code is dynamically included.
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters a subclass might need.
     *
     * @return Horde_Lock_Driver  The newly created concrete instance.
     * @throws Horde_Lock_Exception
     */
    static public function factory($driver, $params = array())
    {
        $driver = Horde_String::ucfirst(basename($driver));
        $class = __CLASS__ . '_' . $driver;

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Lock_Exception('Horde_Lock driver (' . $class . ') not found');
    }

    /**
     * Attempts to return a reference to a concrete instance based on
     * $driver. It will only create a new instance if no instance
     * with the same parameters currently exists.
     *
     * This should be used if multiple authentication sources (and, thus,
     * multiple Horde_Lock instances) are required.
     *
     * @param string $driver  The type of concrete Horde_Lock subclass to
     *                        return.
     *                        This is based on the storage driver ($driver).
     *                        The code is dynamically included.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return Horde_Lock_Driver  The concrete reference.
     * @throws Horde_Lock_Exception
     */
    static public function singleton($driver, $params = array())
    {
        ksort($params);
        $signature = hash('md5', serialize(array($driver, $params)));
        if (empty(self::$_instances[$signature])) {
            self::$_instances[$signature] = self::factory($driver, $params);
        }

        return self::$_instances[$signature];
    }

}
