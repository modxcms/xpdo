<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Om\sqlsrv;

use PDO;
use xPDO\xPDO;

/**
 * An extension for generating {@link xPDOObject} class and map files for sqlsrv.
 *
 * A sqlsrv-specific extension to an {@link xPDOManager} instance that can
 * generate class stub and meta-data map files from a provided XML schema of a
 * database structure.
 *
 * @package xPDO\Om\sqlsrv
 */
class xPDOGenerator extends \xPDO\Om\xPDOGenerator {
    public function compile($path = '') {
        return false;
    }

    public function getIndex($index) {
        return '';
    }

    /**
     * Write an xPDO XML Schema from your database.
     *
     * @param string $schemaFile The name (including path) of the schemaFile you
     * want to write.
     * @param string $package Name of the package to generate the classes in.
     * @param string $baseClass The class which all classes in the package will
     * extend; by default this is set to {@link xPDOObject} and any
     * auto_increment fields with the column name 'id' will extend {@link
     * xPDOSimpleObject} automatically.
     * @param string $tablePrefix The table prefix for the current connection,
     * which will be removed from all of the generated class and table names.
     * Specify a prefix when creating a new {@link xPDO} instance to recreate
     * the tables with the same prefix, but still use the generic class names.
     * @param boolean $restrictPrefix Only reverse-engineer tables that have the
     * specified tablePrefix; if tablePrefix is empty, this is ignored.
     * @return boolean True on success, false on failure.
     */
    public function writeSchema($schemaFile, $package= '', $baseClass= '', $tablePrefix= '', $restrictPrefix= false) {
        if (empty ($package))
            $package= $this->manager->xpdo->package;
        if (empty ($baseClass))
            $baseClass= 'xPDO\Om\xPDOObject';
        if (empty ($tablePrefix))
            $tablePrefix= $this->manager->xpdo->config[xPDO::OPT_TABLE_PREFIX];

        $schemaVersion = xPDO::SCHEMA_VERSION;
        $xmlContent = array();
        $xmlContent[] = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $xmlContent[] = "<model package=\"{$package}\" baseClass=\"{$baseClass}\" platform=\"sqlsrv\" version=\"{$schemaVersion}\">";
        //read list of tables
        $tableLike= ($tablePrefix && $restrictPrefix);
        if ($tableLike) {
            $tablesStmt= $this->manager->xpdo->query("
			  SELECT SCHEMA_NAME(schema_id)
			  As schema_name, *
			  from sys.tables
			  WHERE SCHEMA_NAME(schema_id) = '{$tablePrefix}'
			  ORDER BY name
			");
        } else {
            $tablesStmt= $this->manager->xpdo->query("SELECT * FROM sys.Tables ORDER BY name");
        }
        $tables= $tablesStmt->fetchAll(PDO::FETCH_ASSOC);
        if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, print_r($tables, true));
        foreach ($tables as $table) {
            $xmlObject= array();
            $xmlFields= array();
            $xmlIndices= array();
            $tableName= $table['name'];
            $tableId= $table['object_id'];
            $class= $this->getClassName($tableName);
            $extends= $baseClass;
            //COLUMNS
            $fieldsStmt= $this->manager->xpdo->query("
			  SELECT
			    col.*,
				ic.*,
				object_definition(col.default_object_id) AS dflt_value,
				TYPE_NAME(col.user_type_id) AS type,
				i.is_primary_key AS pk,
				i.is_unique AS is_unique
  			  FROM sys.columns col
			  LEFT JOIN sys.index_columns ic ON col.object_id = ic.object_id AND col.column_id = ic.column_id
			  LEFT JOIN sys.indexes i ON col.object_id = i.object_id AND ic.index_id = i.index_id
			  WHERE col.object_id = '{$tableId}'
			");

            $fields= $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);
            if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Fields for table {$tableName}: " . print_r($fields, true));
            $cid = 0;
            foreach ($fields as $field) {
                $name = '';
                $type = '';
                $max_length = '';
                $is_nullable = 0;
                $dflt_value = null;
                $Key = '';
                $pk = 0;
                $is_unique = 0;
                $index_id = '';
                extract($field, EXTR_OVERWRITE);
                $Field= $name;
                $PhpType= $this->manager->xpdo->driver->getPhpType($type);
                $Null= ' null="' . ($is_nullable == 1 ? 'true' : 'false') . '"';
                $Default= $this->getDefault($dflt_value);
                $Extra= '';
                if (!empty($pk)) {
                    if (preg_match('/INT/i', $type)) {
                        if ($baseClass === 'xPDO\Om\xPDOObject' && $Field === 'id') {
                            $extends= 'xPDO\Om\xPDOSimpleObject';
                            continue;
                        } elseif ($cid == 0) {
                            $Extra= ' generated="native"';
                        }
                    }
                    $Key = ' index="pk"';
                } else {
                    if ($is_unique == 1) {
                        $Key = ' index="unique"';
                    } elseif (!empty($index_id)) {
                        $Key = ' index="index"';
                    }
                }
                $xmlFields[]= "\t\t<field key=\"{$Field}\" dbtype=\"{$type}\" precision=\"{$max_length}\" phptype=\"{$PhpType}\"{$Null}{$Default}{$Key}{$Extra} />";
                $cid++;
            }
            //INDEXES
            $indicesStmt= $this->manager->xpdo->query("
			  SELECT
			    i.*
  			  FROM sys.indexes i
			  WHERE i.object_id = '{$tableId}'
			");

            $indices= $indicesStmt->fetchAll(PDO::FETCH_ASSOC);

            if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Indices for table {$tableName}: " . print_r($indices, true));
            foreach ($indices as $index) {
                $primary = ($index['is_primary_key'] == 1) ? 'true' : 'false';
                $unique = ($index['is_unique'] == 1) ? 'true' : 'false';
                $xmlIndices[]= "\t\t<index alias=\"{$index['name']}\" name=\"{$index['name']}\" primary=\"{$primary}\" unique=\"{$unique}\">";

                //INDEX COLUMNS
                $columnsStmt = $this->manager->xpdo->query("
			      SELECT
				  ic.*,col.*
				  FROM sys.index_columns ic
				  LEFT JOIN sys.columns col ON ic.column_id = col.column_id
			      WHERE ic.object_id = '{$tableId}' AND col.object_id = '{$tableId}' AND ic.index_id = '{$index['index_id']}'
			    ");
                $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
                if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Columns of index {$index['name']}: " . print_r($columns, true));
                foreach ($columns as $column) {
                    $Null= ' null="' . ($column['is_nullable'] == 1 ? 'true' : 'false') . '"';
                    $xmlIndices[]= "\t\t\t<column key=\"{$column['name']}\"{$Null} />";
                }
                $xmlIndices[]= "\t\t</index>";
            }
            $xmlObject[] = "\t<object class=\"{$class}\" table=\"{$tableName}\" extends=\"{$extends}\">";
            $xmlObject[] = implode("\n", $xmlFields);
            if (!empty($xmlIndices)) {
                $xmlObject[] = '';
                $xmlObject[] = implode("\n", $xmlIndices);
            }
            $xmlObject[] = "\t</object>";
            $xmlContent[] = implode("\n", $xmlObject);
        }
        $xmlContent[] = "</model>";
        if ($this->manager->xpdo->getDebug() === true) {
            $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, implode("\n", $xmlContent));
        }
        $file= fopen($schemaFile, 'wb');
        $written= fwrite($file, implode("\n", $xmlContent));
        fclose($file);
        return true;
    }
}
