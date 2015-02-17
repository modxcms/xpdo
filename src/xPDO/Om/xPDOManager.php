<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Om;

use xPDO\xPDO;
use xPDO\Transport\xPDOTransport;

/**
 * Provides data source management for an xPDO instance.
 *
 * These are utility functions that only need to be loaded under special
 * circumstances, such as creating tables, adding indexes, altering table
 * structures, etc.  xPDOManager class implementations are specific to a
 * database driver and should include this base class in order to extend it.
 *
 * @package xPDO\Om
 */
abstract class xPDOManager {
    /**
     * @var xPDO A reference to the XPDO instance using this manager.
     * @access public
     */
    public $xpdo= null;
    /**
     * @var xPDOGenerator The generator class for forward and reverse
     * engineering tasks (loaded only on demand).
     */
    public $generator= null;
    /**
     * @var xPDOTransport The data transport class for migrating data.
     */
    public $transport= null;

    /**
     * Get a xPDOManager instance.
     *
     * @param xPDO &$xpdo A reference to a specific xPDO instance.
     */
    public function __construct(& $xpdo) {
        if ($xpdo !== null && $xpdo instanceof xPDO) {
            $this->xpdo= & $xpdo;
        }
    }

    /**
     * Creates the physical container representing a data source.
     *
     * @param array|null $dsnArray An array of xPDO configuration properties.
     * @param string|null $username Database username with privileges to create tables.
     * @param string|null $password Database user password.
     * @param array $containerOptions An array of options for controlling the creation of the container.
     * @return boolean True if the database is created successfully or already exists.
     */
    abstract public function createSourceContainer($dsnArray = null, $username= null, $password= null, $containerOptions= array());

    /**
     * Drops a physical data source container, if it exists.
     *
     * @param string|null $dsnArray Represents the database connection string.
     * @param string|null $username Database username with privileges to drop tables.
     * @param string|null $password Database user password.
     * @return bool Returns true on successful drop, false on failure.
     */
    abstract public function removeSourceContainer($dsnArray = null, $username= null, $password= null);

    /**
     * Creates the container for a persistent data object.
     *
     * An object container is a synonym for a database table.
     *
     * @param string $className The class of object to create a source container for.
     * @return boolean Returns true on successful creation, false on failure.
     */
    abstract public function createObjectContainer($className);

    /**
     * Alter the structure of an existing persistent data object container.
     *
     * @param string $className The class of object to alter the container of.
     * @param array $options An array of options describing the alterations to be made.
     * @return boolean Returns true on successful alteration, false on failure.
     */
    abstract public function alterObjectContainer($className, array $options = array());

    /**
     * Drop an object container (i.e. database table), if it exists.
     *
     * @param string $className The object container to drop.
     * @return boolean Returns true on successful drop, false on failure.
     */
    abstract public function removeObjectContainer($className);

    /**
     * Add a field to an object container, e.g. ADD COLUMN.
     *
     * @param string $class The object class to add the field to.
     * @param string $name The name of the field to add.
     * @param array $options An array of options for the process.
     * @return boolean True if the column is added successfully, otherwise false.
     */
    abstract public function addField($class, $name, array $options = array());

    /**
     * Alter an existing field of an object container, e.g. ALTER COLUMN.
     *
     * @param string $class The object class to alter the field of.
     * @param string $name The name of the field to alter.
     * @param array $options An array of options for the process.
     * @return boolean True if the column is altered successfully, otherwise false.
     */
    abstract public function alterField($class, $name, array $options = array());

    /**
     * Remove a field from an object container, e.g. DROP COLUMN.
     *
     * @param string $class The object class to drop the field from.
     * @param string $name The name of the field to drop.
     * @param array $options An array of options for the process.
     * @return boolean True if the column is dropped successfully, otherwise false.
     */
    abstract public function removeField($class, $name, array $options = array());

    /**
     * Add an index to an object container, e.g. ADD INDEX.
     *
     * @param string $class The object class to add the index to.
     * @param string $name The name of the index to add.
     * @param array $options An array of options for the process.
     * @return boolean True if the index is added successfully, otherwise false.
     */
    abstract public function addIndex($class, $name, array $options = array());

    /**
     * Remove an index from an object container, e.g. DROP INDEX.
     *
     * @param string $class The object class to drop the index from.
     * @param string $name The name of the index to drop.
     * @param array $options An array of options for the process.
     * @return boolean True if the index is dropped successfully, otherwise false.
     */
    abstract public function removeIndex($class, $name, array $options = array());

    /**
     * Add a constraint to an object container, e.g. ADD CONSTRAINT.
     *
     * @param string $class The object class to add the constraint to.
     * @param string $name The name of the constraint to add.
     * @param array $options An array of options for the process.
     * @return boolean True if the constraint is added successfully, otherwise false.
     */
    abstract public function addConstraint($class, $name, array $options = array());

    /**
     * Remove a constraint from an object container, e.g. DROP CONSTRAINT.
     *
     * @param string $class The object class to drop the constraint from.
     * @param string $name The name of the constraint to drop.
     * @param array $options An array of options for the process.
     * @return boolean True if the constraint is dropped successfully, otherwise false.
     */
    abstract public function removeConstraint($class, $name, array $options = array());

    /**
     * Gets an XML schema parser / generator for this manager instance.
     *
     * @return xPDOGenerator A generator class for this manager.
     */
    public function getGenerator() {
        if ($this->generator === null || !$this->generator instanceof xPDOGenerator) {
            $generatorClass = '\\xPDO\\Om\\'  . $this->xpdo->config['dbtype'] . '\\xPDOGenerator';
            $this->generator= new $generatorClass ($this);
            if ($this->generator === null || !$this->generator instanceof xPDOGenerator) {
                $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not load xPDOGenerator [{$generatorClass}] class.");
            }
        }
        return $this->generator;
    }

    /**
     * Gets a data transport mechanism for this xPDOManager instance.
     *
     * @return xPDOTransport
     */
    public function getTransport() {
        if ($this->transport === null || !$this->transport instanceof xPDOTransport) {
            $transportClass= $this->xpdo->getOption('xPDOTransport.class', null, 'xPDOTransport');
            $this->transport= new $transportClass($this);
            if ($this->transport === null || !$this->transport instanceof xPDOTransport) {
                $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not load xPDOTransport [{$transportClass}] class.");
            }
        }
        return $this->transport;
    }

    /**
     * Get the SQL necessary to define a column for a specific database engine.
     *
     * @param string $class The name of the class the column represents a field of.
     * @param string $name The name of the field and physical database column.
     * @param string $meta The metadata defining the field.
     * @param array $options An array of options for the process.
     * @return string A string of SQL representing the column definition.
     */
    abstract protected function getColumnDef($class, $name, $meta, array $options = array());

    /**
     * Get the SQL necessary to define an index for a specific database engine.
     *
     * @param string $class The name of the class the index is defined for.
     * @param string $name The name of the index.
     * @param string $meta The metadata defining the index.
     * @param array $options An array of options for the process.
     * @return string A string of SQL representing the index definition.
     */
    abstract protected function getIndexDef($class, $name, $meta, array $options = array());
}
