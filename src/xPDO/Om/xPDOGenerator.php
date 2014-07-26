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

use xPDO\Reflect\xPDOReflectionClass;
use xPDO\xPDO;

/**
 * A service for reverse and forward engineering xPDO domain models.
 *
 * This service utilizes an xPDOManager instance to generate class stub and
 * meta-data map files from a provided vanilla XML schema of a database
 * structure.  It can also reverse-engineer XML schemas from an existing
 * database.
 *
 * @package xPDO\Om
 */
abstract class xPDOGenerator {
    /**
     * @var array A map of classes already updated during this request.
     */
    public static $updated = array();

    /**
     * @var xPDOManager $manager A reference to the xPDOManager using this
     * generator.
     */
    public $manager= null;
    /**
     * @var string $outputDir The absolute path to output the class and map
     * files to.
     */
    public $outputDir= '';
    /**
     * @var string $schemaFile An absolute path to the schema file.
     */
    public $schemaFile= '';
    /**
     * @var string $schemaContent The stored content of the newly-created schema
     * file.
     */
    public $schemaContent= '';
    /**
     * @var string $classTemplate The class template string to build the class
     * files from.
     */
    public $classTemplate= '';
    /**
     * @var string $platformTemplate The class platform template string to build
     * the class platform files from.
     */
    public $platformTemplate= '';
    /**
     * @var string $metaTemplate The class platform template string to build
     * the meta class map files from.
     */
    public $metaTemplate= '';
    /**
     * @var string $mapHeader The map header string to build the map files from.
     */
    public $mapHeader= '';
    /**
     * @var string $mapFooter The map footer string to build the map files from.
     */
    public $mapFooter= '';
    /**
     * @var array $model The stored model array.
     */
    public $model= array ();
    /**
     * @var array $classes The stored classes array.
     */
    public $classes= array ();
    /**
     * @var array $map The stored map array.
     */
    public $map= array ();
    /**
     * @var \SimpleXMLElement
     */
    public $schema= null;

    /**
     * Construct a new xPDOGenerator instance.
     *
     * @param xPDOManager &$manager A reference to a valid xPDOManager instance.
     * @return xPDOGenerator
     */
    public function __construct(& $manager) {
        $this->manager= & $manager;
    }

    /**
     * Properly indent (4 spaces) var_export() output for arrays.
     *
     * @param mixed $var A valid PHP variable.
     * @param int $indentLevel The current indent level.
     *
     * @return string The exported variable value as a string.
     */
    public static function varExport($var, $indentLevel = 1) {
        $output = array();
        if (is_array($var)) {
            $exploded = explode("\n", var_export($var, true));
            $count = count($exploded);
            $output = array(current($exploded));
            $lineNo = 1;
            while ($line = next($exploded)) {
                $lineNo++;
                if ($lineNo === $count) {
                    $output[] = str_repeat(' ', $indentLevel * 4) . $line;
                    break;
                }
                $split = str_split($line);
                $spaces = 0;
                while ($char = next($split)) {
                    if ($char !== ' ') break;
                    $spaces++;
                }
                $output[] = str_repeat('    ', $indentLevel + 1) . str_repeat('    ', $spaces / 2) . substr($line, ($spaces ? $spaces + 1 : 0));
            }
        }
        return implode("\n", $output);
    }

    /**
     * Formats a class name to a specific value, stripping the prefix if
     * specified.
     *
     * @access public
     * @param string $string The name to format.
     * @param string $prefix If specified, will strip the prefix out of the
     * first argument.
     * @param boolean $prefixRequired If true, will return a blank string if the
     * prefix specified is not found.
     * @return string The formatting string.
     */
    public function getTableName($string, $prefix= '', $prefixRequired= false) {
        if (!empty($prefix) && strpos($string, $prefix) === 0) {
            $string= substr($string, strlen($prefix));
        }
        elseif ($prefixRequired) {
            $string= '';
        }
        return $string;
    }

    /**
     * Gets a class name from a table name by splitting the string by _ and
     * capitalizing each token.
     *
     * @access public
     * @param string $string The table name to format.
     * @return string The formatted string.
     */
    public function getClassName($string) {
        if (is_string($string) && $strArray= explode('_', $string)) {
            $return= '';
            while (list($k, $v)= each($strArray)) {
                $return.= strtoupper(substr($v, 0, 1)) . substr($v, 1) . '';
            }
            $string= $return;
        }
        return trim($string);
    }

    /**
     * Format the passed default value as an XML attribute.
     *
     * Override this in different PDO driver implementations if necessary.
     *
     * @access public
     * @param string $value The value to encapsulate in the default tag.
     * @return string The parsed XML string
     */
    public function getDefault($value) {
        $return= '';
        if ($value !== null) {
            $return= ' default="'.$value.'"';
        }
        return $return;
    }

    /**
     * Format the passed database index value as an XML attribute.
     *
     * @abstract Implement this for specific PDO driver implementations.
     * @access public
     * @param string $index The DB representation string of the index
     * @return string The formatted XML attribute string
     */
    abstract public function getIndex($index);

    /**
     * Parses an xPDO XML schema and generates classes and map files from it.
     *
     * Requires SimpleXML for parsing an XML schema.
     *
     * @param string $schemaFile The name of the XML file representing the
     * schema.
     * @param string $outputDir The directory in which to generate the class and
     * map files into.
     * @param array $options Various options for the process.
     * @return boolean True on success, false on failure.
     */
    public function parseSchema($schemaFile, $outputDir= '', $options = array()) {
        $this->_reset();
        if (!is_array($options)) {
            $compile = (boolean) $options;
        } else {
            $compile = array_key_exists('compile', $options) ? (boolean) $options['compile'] : false;
        }
        $regenerate = array_key_exists('regenerate', $options) ? (integer) $options['regenerate'] : 0;
        $update = array_key_exists('update', $options) ? (integer) $options['update'] : 2;

        $this->schemaFile= $schemaFile;
        $this->classTemplate= $this->getClassTemplate();
        if (!is_file($schemaFile)) {
            $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not find specified XML schema file {$schemaFile}");
            return false;
        }

        $this->schema = new \SimpleXMLElement($schemaFile, 0, true);
        if (isset($this->schema)) {
            foreach ($this->schema->attributes() as $attributeKey => $attribute) {
                /** @var \SimpleXMLElement $attribute */
                $this->model[$attributeKey] = (string) $attribute;
            }
            $this->model['namespace'] = ltrim($this->model['package'], '\\');
            if (isset($this->schema->object)) {
                foreach ($this->schema->object as $object) {
                    /** @var \SimpleXMLElement $object */
                    $class = (string) $object['class'];
                    $extends = isset($object['extends']) ? (string) $object['extends'] : $this->model['baseClass'];
                    $this->classes[$class] = array('extends' => $extends);
                    $this->map[$class] = array(
                        'package' => $this->model['package'],
                        'version' => $this->model['version']
                    );
                    foreach ($object->attributes() as $objAttrKey => $objAttr) {
                        if ($objAttrKey == 'class') continue;
                        $this->map[$class][$objAttrKey]= (string) $objAttr;
                    }

                    $engine = (string) $object['engine'];
                    if (!empty($engine)) {
                        $this->map[$class]['tableMeta'] = array('engine' => $engine);
                    }

                    $this->map[$class]['fields']= array();
                    $this->map[$class]['fieldMeta']= array();
                    if (isset($object->field)) {
                        foreach ($object->field as $field) {
                            $key = (string) $field['key'];
                            $dbtype = (string) $field['dbtype'];
                            $defaultType = $this->manager->xpdo->driver->getPhpType($dbtype);
                            $this->map[$class]['fields'][$key]= null;
                            $this->map[$class]['fieldMeta'][$key]= array();
                            foreach ($field->attributes() as $fldAttrKey => $fldAttr) {
                                $fldAttrValue = (string) $fldAttr;
                                switch ($fldAttrKey) {
                                    case 'key':
                                        continue 2;
                                    case 'default':
                                        if ($fldAttrValue === 'NULL') {
                                            $fldAttrValue = null;
                                        }
                                        switch ($defaultType) {
                                            case 'integer':
                                            case 'boolean':
                                            case 'bit':
                                                $fldAttrValue = (integer) $fldAttrValue;
                                                break;
                                            case 'float':
                                            case 'numeric':
                                                $fldAttrValue = (float) $fldAttrValue;
                                                break;
                                            default:
                                                break;
                                        }
                                        $this->map[$class]['fields'][$key]= $fldAttrValue;
                                        break;
                                    case 'null':
                                        $fldAttrValue = (!empty($fldAttrValue) && strtolower($fldAttrValue) !== 'false') ? true : false;
                                        break;
                                    default:
                                        break;
                                }
                                $this->map[$class]['fieldMeta'][$key][$fldAttrKey]= $fldAttrValue;
                            }
                        }
                    }
                    if (isset($object->alias)) {
                        $this->map[$class]['fieldAliases'] = array();
                        foreach ($object->alias as $alias) {
                            $aliasKey = (string) $alias['key'];
                            $aliasNode = array();
                            foreach ($alias->attributes() as $attrName => $attr) {
                                $attrValue = (string) $attr;
                                switch ($attrName) {
                                    case 'key':
                                        continue 2;
                                    case 'field':
                                        $aliasNode = $attrValue;
                                        break;
                                    default:
                                        break;
                                }
                            }
                            if (!empty($aliasKey) && !empty($aliasNode)) {
                                $this->map[$class]['fieldAliases'][$aliasKey] = $aliasNode;
                            }
                        }
                    }
                    if (isset($object->index)) {
                        $this->map[$class]['indexes'] = array();
                        foreach ($object->index as $index) {
                            $indexNode = array();
                            $indexName = (string) $index['name'];
                            foreach ($index->attributes() as $attrName => $attr) {
                                $attrValue = (string) $attr;
                                switch ($attrName) {
                                    case 'name':
                                        continue 2;
                                    case 'primary':
                                    case 'unique':
                                    case 'fulltext':
                                        $attrValue = (empty($attrValue) || $attrValue === 'false' ? false : true);
                                    default:
                                        $indexNode[$attrName] = $attrValue;
                                        break;
                                }
                            }
                            if (!empty($indexNode) && isset($index->column)) {
                                $indexNode['columns']= array();
                                foreach ($index->column as $column) {
                                    $columnKey = (string) $column['key'];
                                    $indexNode['columns'][$columnKey] = array();
                                    foreach ($column->attributes() as $attrName => $attr) {
                                        $attrValue = (string) $attr;
                                        switch ($attrName) {
                                            case 'key':
                                                continue 2;
                                            case 'null':
                                                $attrValue = (empty($attrValue) || $attrValue === 'false' ? false : true);
                                            default:
                                                $indexNode['columns'][$columnKey][$attrName]= $attrValue;
                                                break;
                                        }
                                    }
                                }
                                if (!empty($indexNode['columns'])) {
                                    $this->map[$class]['indexes'][$indexName]= $indexNode;
                                }
                            }
                        }
                    }
                    if (isset($object->composite)) {
                        $this->map[$class]['composites'] = array();
                        foreach ($object->composite as $composite) {
                            $compositeNode = array();
                            $compositeAlias = (string) $composite['alias'];
                            foreach ($composite->attributes() as $attrName => $attr) {
                                $attrValue = (string) $attr;
                                switch ($attrName) {
                                    case 'alias' :
                                        continue 2;
                                    case 'criteria' :
                                        $attrValue = $this->manager->xpdo->fromJSON(urldecode($attrValue));
                                    default :
                                        $compositeNode[$attrName]= $attrValue;
                                        break;
                                }
                            }
                            if (!empty($compositeNode)) {
                                if (isset($composite->criteria)) {
                                    /** @var \SimpleXMLElement $criteria */
                                    foreach ($composite->criteria as $criteria) {
                                        $criteriaTarget = (string) $criteria['target'];
                                        $expression = (string) $criteria;
                                        if (!empty($expression)) {
                                            $expression = $this->manager->xpdo->fromJSON($expression);
                                            if (!empty($expression)) {
                                                if (!isset($compositeNode['criteria'])) $compositeNode['criteria'] = array();
                                                if (!isset($compositeNode['criteria'][$criteriaTarget])) $compositeNode['criteria'][$criteriaTarget] = array();
                                                $compositeNode['criteria'][$criteriaTarget] = array_merge($compositeNode['criteria'][$criteriaTarget], (array) $expression);
                                            }
                                        }
                                    }
                                }
                                $this->map[$class]['composites'][$compositeAlias] = $compositeNode;
                            }
                        }
                    }
                    if (isset($object->aggregate)) {
                        $this->map[$class]['aggregates'] = array();
                        foreach ($object->aggregate as $aggregate) {
                            $aggregateNode = array();
                            $aggregateAlias = (string) $aggregate['alias'];
                            foreach ($aggregate->attributes() as $attrName => $attr) {
                                $attrValue = (string) $attr;
                                switch ($attrName) {
                                    case 'alias' :
                                        continue 2;
                                    case 'criteria' :
                                        $attrValue = $this->manager->xpdo->fromJSON(urldecode($attrValue));
                                    default :
                                        $aggregateNode[$attrName]= $attrValue;
                                        break;
                                }
                            }
                            if (!empty($aggregateNode)) {
                                if (isset($aggregate->criteria)) {
                                    /** @var \SimpleXMLElement $criteria */
                                    foreach ($aggregate->criteria as $criteria) {
                                        $criteriaTarget = (string) $criteria['target'];
                                        $expression = (string) $criteria;
                                        if (!empty($expression)) {
                                            $expression = $this->manager->xpdo->fromJSON($expression);
                                            if (!empty($expression)) {
                                                if (!isset($aggregateNode['criteria'])) $aggregateNode['criteria'] = array();
                                                if (!isset($aggregateNode['criteria'][$criteriaTarget])) $aggregateNode['criteria'][$criteriaTarget] = array();
                                                $aggregateNode['criteria'][$criteriaTarget] = array_merge($aggregateNode['criteria'][$criteriaTarget], (array) $expression);
                                            }
                                        }
                                    }
                                }
                                $this->map[$class]['aggregates'][$aggregateAlias] = $aggregateNode;
                            }
                        }
                    }
                    if (isset($object->validation)) {
                        $this->map[$class]['validation'] = array();
                        $validation = $object->validation[0];
                        $validationNode = array();
                        foreach ($validation->attributes() as $attrName => $attr) {
                            $validationNode[$attrName]= (string) $attr;
                        }
                        if (isset($validation->rule)) {
                            $validationNode['rules'] = array();
                            foreach ($validation->rule as $rule) {
                                $ruleNode = array();
                                $field= (string) $rule['field'];
                                $name= (string) $rule['name'];
                                foreach ($rule->attributes() as $attrName => $attr) {
                                    $attrValue = (string) $attr;
                                    switch ($attrName) {
                                        case 'field' :
                                        case 'name' :
                                            continue 2;
                                        default :
                                            $ruleNode[$attrName]= $attrValue;
                                            break;
                                    }
                                }
                                if (!empty($field) && !empty($name) && !empty($ruleNode)) {
                                    $validationNode['rules'][$field][$name]= $ruleNode;
                                }
                            }
                            if (!empty($validationNode['rules'])) {
                                $this->map[$class]['validation'] = $validationNode;
                            }
                        }
                    }
                }
            } else {
                $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Schema {$schemaFile} contains no valid object elements.");
            }
        } else {
            $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not read schema from {$schemaFile}.");
        }

        $path= !empty($outputDir) ? $outputDir : 'model/';
        $this->outputMeta($path);
        $this->outputClasses($path, $update, $regenerate);
        if ($compile) {
            $this->compile($path, $this->model, $this->classes, $this->map);
        }
        $this->_reset();
        return true;
    }

    /**
     * Create or update the generated class files to the specified path.
     *
     * @param string $path An absolute path to write the generated class files to.
     * @param int $update Indicates if existing class files should be updated; 0=no,
     * 1=update platform classes, 2=update all classes.
     * @param int $regenerate Indicates if existing class files should be regenerated;
     * 0=no, 1=regenerate platform classes, 2=regenerate all classes.
     */
    public function outputClasses($path, $update = 1, $regenerate = 0) {
        if (isset($this->model['phpdoc-package'])) {
            $this->model['phpdoc-package']= '@package ' . $this->model['phpdoc-package'];
            if (isset($this->model['phpdoc-subpackage']) && !empty($this->model['phpdoc-subpackage'])) {
                $this->model['phpdoc-subpackage']= '@subpackage ' . $this->model['phpdoc-subpackage'];
            }
        } else {
            $this->model['phpdoc-package']= '@package ' . $this->model['namespace'];
        }
        foreach ($this->classes as $className => $classDef) {
            $namespace = $this->model['namespace'];
            $classExploded = explode('\\', $className);
            $classDef['class']= $className;
            $classDef['class-shortname']= $classShortName = array_pop($classExploded);
            if (count($classExploded) > 0) {
                $namespace .= implode('\\', $classExploded);
            }
            $classDef['namespace']= $namespace;
            $classDef['class-fullname']= $classFullName = "{$namespace}\\{$className}";
            $classDef['class-platform']= $platformClass = "{$namespace}\\{$this->model['platform']}\\{$classShortName}";
            $classDef= array_merge($this->model, $classDef);
            $fileName= $path . str_replace('\\', DIRECTORY_SEPARATOR, $classFullName) . '.php';
            $newClass= !file_exists($fileName);
            if (!in_array($classFullName, self::$updated)) {
                if ($newClass || $regenerate === 2) {
                    $this->_loadClass($classFullName, $classDef);
                    self::$updated[] = $classFullName;
                    if (!$this->_constructClass($fileName, $classDef, $this->getClassTemplate())) {
                        $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not construct domain class {$classFullName} to file {$fileName}", '', __METHOD__, __FILE__, __LINE__);
                    }
                } elseif (!$newClass && $update === 2) {
                    $this->_loadExistingClass($classFullName, $classDef);
                    self::$updated[] = $classFullName;
                    if (!$this->_constructClass($fileName, $classDef, $this->getClassTemplate())) {
                        $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not reconstruct domain class {$classFullName} to file {$fileName}", '', __METHOD__, __FILE__, __LINE__);
                    }
                } else {
                    $this->manager->xpdo->log(xPDO::LOG_LEVEL_INFO, "Skipping {$fileName}: Use update or regenerate options to overwrite or update your domain classes.");
                }
            } else {
                $this->manager->xpdo->log(xPDO::LOG_LEVEL_WARN, "Domain class {$classFullName} was already constructed to file {$fileName} in this session", '', __METHOD__, __FILE__, __LINE__);
            }

            $fileName= $path . str_replace('\\', DIRECTORY_SEPARATOR, $platformClass) . '.php';
            $newPlatformClass= !file_exists($fileName);
            if (!in_array($platformClass, self::$updated)) {
                if (isset($this->map[$className])) $classDef['map'] = static::varExport($this->map[$className]);
                if ($newPlatformClass || $regenerate > 0) {
                    $this->_loadClass($platformClass, $classDef);
                    self::$updated[] = $platformClass;
                    if (!$this->_constructClass($fileName, $classDef, $this->getClassPlatformTemplate($this->model['platform']))) {
                        $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not construct platform class {$platformClass} to file {$fileName}", '', __METHOD__, __FILE__, __LINE__);
                    }
                } elseif (!$newClass && $update > 0) {
                    $this->_loadExistingClass($platformClass, $classDef);
                    self::$updated[] = $platformClass;
                    if (!$this->_constructClass($fileName, $classDef, $this->getClassPlatformTemplate($this->model['platform']))) {
                        $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not reconstruct platform class {$platformClass} to file {$fileName}", '', __METHOD__, __FILE__, __LINE__);
                    }
                } else {
                    $this->manager->xpdo->log(xPDO::LOG_LEVEL_INFO, "Skipping {$fileName}: Use update or regenerate options to overwrite or update your platform classes.");
                }
            } else {
                $this->manager->xpdo->log(xPDO::LOG_LEVEL_WARN, "Platform class {$platformClass} was already constructed to file {$fileName} in this session", '', __METHOD__, __FILE__, __LINE__);
            }
        }
    }

    /**
     * Write the generated meta map to the specified path.
     *
     * @param string $path An absolute path to write the generated maps to.
     * @return bool
     */
    public function outputMeta($path) {
        $path .= str_replace('\\', DIRECTORY_SEPARATOR, $this->model['namespace']) . DIRECTORY_SEPARATOR;
        if (!is_dir($path)) {
            if ($this->manager->xpdo->getCacheManager()) {
                if (!$this->manager->xpdo->cacheManager->writeTree($path)) {
                    $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not create model directory at {$path}");
                    return false;
                }
            }
        }
        $placeholders = array();

        if (isset($this->model['phpdoc-package'])) {
            $this->model['phpdoc-package']= '@package ' . $this->model['phpdoc-package'];
            if (isset($this->model['phpdoc-subpackage']) && !empty($this->model['phpdoc-subpackage'])) {
                $this->model['phpdoc-subpackage']= '@subpackage ' . $this->model['phpdoc-subpackage'];
            }
        } else {
            $this->model['phpdoc-package']= '@package ' . $this->model['namespace'];
        }
        $placeholders = array_merge($placeholders, $this->model);

        $classMap = array();
        foreach ($this->classes as $className => $meta) {
            if (!isset($meta['extends'])) {
                $meta['extends'] = 'xPDO\\om\\xPDOObject';
            }
            $parent = ltrim($meta['extends'], '\\');
            if (!isset($classMap[$parent])) {
                $classMap[$parent] = array();
            }
            $classMap[$parent][] = $this->model['namespace'] . '\\' . $className;
        }
        if (version_compare($this->model['version'], '3.0', '>=')) {
            $metaData = array(
                'version' => $this->model['version'],
                'namespace' => $this->model['namespace'],
                'class_map' => $classMap
            );
        } else {
            $metaData = $classMap;
        }
        $written = false;
        if ($this->manager->xpdo->getCacheManager()) {
            $placeholders['map'] = static::varExport($metaData, 0);
            $replaceVars = array();
            foreach ($placeholders as $varKey => $varValue) {
                if (is_scalar($varValue)) $replaceVars["[+{$varKey}+]"]= (string) $varValue;
            }
            $fileContent= str_replace(array_keys($replaceVars), array_values($replaceVars), $this->getMetaTemplate());
            $written = $this->manager->xpdo->cacheManager->writeFile("{$path}metadata.{$this->model['platform']}.php",$fileContent);
        }
        return $written;
    }

    /**
     * Compile the packages into a single file for quicker loading.
     *
     * @param string $path The absolute path to compile into.
     *
     * @return boolean True if the compiling went successfully.
     */
    abstract public function compile($path= '');

    /**
     * Return the class template for the class files.
     *
     * @return string The class template.
     */
    public function getClassTemplate() {
        if ($this->classTemplate) return $this->classTemplate;
        $template= <<<'EOD'
[+class-header+]
[+class-declaration+]
[+class-traits+][+class-constants+][+class-properties+][+class-methods+][+class-close-declaration+][+class-footer+]
EOD;
        return $template;
    }

    /**
     * Return the class platform template for the class files.
     *
     * @return string The class platform template.
     */
    public function getClassPlatformTemplate() {
        if ($this->platformTemplate) return $this->platformTemplate;
        $template= <<<'EOD'
[+class-header+]
[+class-declaration+]
[+class-traits+][+class-constants+][+class-properties+]
    public static $metaMap = [+map+];
[+class-methods+][+class-close-declaration+][+class-footer+]
EOD;
        return $template;
    }

    /**
     * Gets the meta template.
     *
     * @return string The meta template.
     */
    public function getMetaTemplate() {
        if ($this->metaTemplate) return $this->metaTemplate;
        $tpl= <<<'EOD'
<?php
$xpdo_meta_map = [+map+];
EOD;
        return $tpl;
    }

    /**
     * Get the platform class name of the specified domain class.
     *
     * It should be relative to the current model namespace or absolute.
     *
     * @param string $domainClass A domain class to get the platform class name from.
     *
     * @return string The corresponding platform class name for the domain class.
     */
    public function getPlatformClass($domainClass) {
        $relative = (strpos($domainClass, '\\') !== 0);
        $exploded = explode('\\', ltrim($domainClass, '\\'));
        $slice = array_slice($exploded, -1);
        $class = $slice[0];
        $namespace = implode('\\', array_slice($exploded, 0, -1));
        if (!empty($namespace)) $namespace .= '\\';
        if ($relative) {
            $platformClass = "\\{$this->model['package']}\\{$namespace}{$this->model['platform']}\\{$class}";
        } else {
            $platformClass = "\\{$namespace}{$this->model['platform']}\\{$class}";
        }
        return $platformClass;
    }

    /**
     * Load reflection data from an existing class for reconstruction.
     *
     * @param string $class
     * @param array &$meta
     */
    protected function _loadExistingClass($class, &$meta) {
        try {
            $reflector = new xPDOReflectionClass($class);

            $classHeader = rtrim($reflector->getSource(null, 0, $reflector->getStartLine() - 1, false), "\n");
            $classFooter = trim($reflector->getSource(null, $reflector->getEndLine(), null, false), " \n\r\t");
            if (!empty($classFooter)) $classFooter = rtrim($classFooter, "\n");

            $interfaces = $reflector->getInterfaceNames();
            if (!empty($interfaces)) {
                $interfaces = " implements " . implode(', ', $interfaces);
            } else {
                $interfaces = '';
            }

            $constants = $reflector->getConstants();

            $properties = array_filter($reflector->getProperties(), function($property) use ($class) {
                /* @var \ReflectionProperty $property */
                return $property->getDeclaringClass() === ltrim($class, '\\');
            });
            $methods = array_filter($reflector->getMethods(), function($method) use ($class) {
                /* @var \ReflectionMethod $method */
                return $method->getDeclaringClass() === ltrim($class, '\\');
            });

            $traitArray = array();
            if (version_compare(PHP_VERSION, '5.4', '>=')) {
                $traits = $reflector->getTraits();

                /* @var \ReflectionClass $trait */
                foreach ($traits as $trait) {
                    $traitArray[] = "    use \\{$trait->getName()};";
                }
            }

            $constantsArray = array();
            foreach ($constants as $constantKey => $constant) {
                $constantsArray[] = "    const {$constantKey} = " . static::varExport($constant) . ';';
            }

            $propertyArray = array();
            /* @var \ReflectionProperty $property */
            foreach ($properties as $property) {
                $propertyArray[] = $reflector->getSource($property);
            }

            $methodArray = array();
            /* @var \ReflectionMethod $method */
            foreach ($methods as $method) {
                $methodArray[] = $reflector->getSource($method);
            }

            $meta['class-header'] = $classHeader;
            $meta['class-declaration'] = "class {$reflector->getShortName()} extends \\{$reflector->getParentClass()->getName()}{$interfaces}\n{";
            $meta['class-constants'] = implode("\n", $constantsArray);
            if (version_compare(PHP_VERSION, '5.4', '>=')) {
                $meta['class-traits'] = implode("\n", $traitArray);
            }
            $meta['class-properties'] = implode("\n", $propertyArray);
            $meta['class-methods'] = implode("\n", $methodArray);
            $meta['class-close-declaration'] = "}\n";
            $meta['class-footer'] = $classFooter;
        } catch (\Exception $e) {
            $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
    }

    protected function _loadClass($class, &$meta) {
        $meta['class-header'] = $this->_constructClassHeader($class, $meta);
        $meta['class-declaration'] = $this->_constructClassDeclaration($class, $meta);
        $meta['class-traits'] = '';
        if (version_compare(PHP_VERSION, '5.4', '>=')) {
            $meta['class-traits'] = implode("\n", $this->_constructClassTraits($class, $meta));
        }
        $meta['class-constants'] = implode("\n", $this->_constructClassConstants($class, $meta));
        $meta['class-properties'] = implode("\n", $this->_constructClassProperties($class, $meta));
        $meta['class-methods'] = implode("\n", $this->_constructClassMethods($class, $meta));
        $meta['class-close-declaration'] = "}\n";
        $meta['class-footer'] = $this->_constructClassFooter($class, $meta);
    }

    protected function _constructClass($fileName, $meta, $template) {
        $constructed = false;
        if (!empty($template)) {
            try {
                $replaceVars= array ();
                foreach ($meta as $varKey => $varValue) {
                    if (is_scalar($varValue)) {
                        $replaceVars["[+{$varKey}+]"]= (string) $varValue;
                    } elseif (is_array($varValue)) {
                        $replaceVars["[+{$varKey}+]"]= static::varExport($varValue);
                    }
                }
                $fileContent= str_replace(array_keys($replaceVars), array_values($replaceVars), $template);
                $constructed = $this->manager->xpdo->cacheManager->writeFile($fileName, $fileContent);
            } catch (\Exception $e) {
                $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
                return false;
            }
        }
        return $constructed;
    }

    protected function _constructClassHeader($class, $meta) {
        if ($class === $meta['class-platform']) {
            $tpl = <<<EOD
<?php
namespace {$meta['namespace']}\\{$meta['platform']};

use xPDO\\xPDO;

EOD;
            if (!empty($meta['class-platform-imports'])) {
                foreach ($meta['class-platform-imports'] as $useAs => $import) {
                    if (is_int($useAs)) {
                        $tpl .= "use {$import};\n";
                    } else {
                        $tpl .= "use {$import} as {$useAs};\n";
                    }
                }
            }
            if (!empty($meta['class-platform-comment'])) {
                $tpl .= "\n{$meta['class-platform-comment']}\n";
            }
        } else {
            $tpl = <<<EOD
<?php
namespace {$meta['namespace']};

use xPDO\\xPDO;

EOD;
            if (!empty($meta['class-imports'])) {
                foreach ($meta['class-imports'] as $useAs => $import) {
                    if (is_int($useAs)) {
                        $tpl .= "use {$import};\n";
                    } else {
                        $tpl .= "use {$import} as {$useAs};\n";
                    }
                }
            }
            if (!empty($meta['class-comment'])) {
                $tpl .= "\n{$meta['class-comment']}\n";
            }
        }
        return $tpl;
    }

    protected function _constructClassDeclaration($class, $meta) {
        if ($class === $meta['class-platform']) {
            $tpl = "class {$meta['class-shortname']} extends \\{$meta['class-fullname']}";
            if (!empty($meta['class-platform-implements'])) {
                $tpl .= " implements " . implode(', ', $meta['class-platform-implements']);
            }
            $tpl .= "\n{";
        } else {
            $tpl = "class {$meta['class-shortname']} extends \\{$meta['extends']}";
            if (!empty($meta['class-implements'])) {
                $tpl .= " implements " . implode(', ', $meta['class-implements']);
            }
            $tpl .= "\n{";
        }
        return $tpl;
    }

    protected function _constructClassTraits($class, $meta) {
        $tpl = array();
        if ($class === $meta['class-platform']) {
            if (!empty($meta['class-platform-traits'])) {
                foreach ($meta['class-platform-traits'] as $alias => $trait) {
                    $trait = '\\' . ltrim($trait, '\\');
                    if (is_int($alias)) {
                        $tpl[] = "    use {$trait};";
                    } else {
                        $tpl[] = "    use {$trait} as {$alias};";
                    }
                }
            }
        } else {
            if (!empty($meta['class-traits'])) {
                foreach ($meta['class-traits'] as $alias => $trait) {
                    $trait = '\\' . ltrim($trait, '\\');
                    if (is_int($alias)) {
                        $tpl[] = "    use {$trait};";
                    } else {
                        $tpl[] = "    use {$trait} as {$alias};";
                    }
                }
            }
        }
        return $tpl;
    }

    protected function _constructClassConstants($class, $meta) {
        $tpl = array();
        if ($class === $meta['class-platform']) {
            if (!empty($meta['class-platform-constants'])) {
                foreach ($meta['class-platform-constants'] as $const => $value) {
                    $tpl[] = "    const {$const} = " . static::varExport($value) . ";";
                }
            }
        } else {
            if (!empty($meta['class-constants'])) {
                foreach ($meta['class-constants'] as $const => $value) {
                    $tpl[] = "    const {$const} = " . static::varExport($value) . ";";
                }
            }
        }
        return $tpl;
    }

    protected function _constructClassProperties($class, $meta) {
        $tpl = array();
        if ($class === $meta['class-platform']) {
            if (!empty($meta['class-platform-properties'])) {
                foreach ($meta['class-platform-properties'] as $prop => $value) {
                    $tpl[] = "    public {$prop}" . (is_null($value) ? ';' : " = " . static::varExport($value) . ";");
                }
            }
        } else {
            if (!empty($meta['class-properties'])) {
                foreach ($meta['class-properties'] as $prop => $value) {
                    $tpl[] = "    public {$prop}" . (is_null($value) ? ';' : " = " . static::varExport($value) . ";");
                }
            }
        }
        return $tpl;
    }

    protected function _constructClassMethods($class, $meta) {
        return array();
    }

    protected function _constructClassFooter($class, $meta) {
        return '';
    }

    private function _reset() {
        $this->model = null;
        $this->map = array();
        $this->classes = array();
        $this->schemaContent = '';
    }
}
