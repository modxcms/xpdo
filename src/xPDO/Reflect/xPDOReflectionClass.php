<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Reflect;

use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionProperty;
use xPDO\Om\xPDOGenerator;
use xPDO\xPDOException;

class xPDOReflectionClass extends ReflectionClass
{
    public $defaultProperties;

    public function __construct($argument)
    {
        parent::__construct($argument);
        $this->defaultProperties = $this->getDefaultProperties();
    }

    /**
     * Returns only interface names implemented directly by this class.
     *
     * @return array
     */
    public function getLocalInterfaceNames()
    {
        $interfaceNames = $this->getInterfaceNames();

        $parentClass = $this->getParentClass();
        if (!$parentClass instanceof ReflectionClass) {
            return $interfaceNames;
        }

        $localInterfaceNames = [];
        foreach ($interfaceNames as $interfaceName) {
            if ($parentClass->implementsInterface($interfaceName)) {
                continue;
            }

            $localInterfaceNames[] = $interfaceName;
        }

        return $localInterfaceNames;
    }

    /**
     * Get the reconstructed source of the Class.
     *
     * @param null $element
     * @param null $start
     * @param bool $end
     * @param bool $includeComment
     *
     * @return bool|string
     * @throws xPDOException
     */
    public function getSource($element = null, $start = null, $end = false, $includeComment = true)
    {
        $source = false;
        /* @var ReflectionClass|ReflectionFunctionAbstract|ReflectionProperty $element */
        if ($element === null) {
            $element =& $this;
        }
        if ($element instanceof ReflectionClass || $element instanceof ReflectionFunctionAbstract) {
            if (is_readable($element->getFileName())) {
                try {
                    $sourceArray = $this->getSourceArray($element, $start, $end);
                    if ($includeComment) {
                        $comment = $element->getDocComment();
                        if (!empty($comment)) {
                            array_unshift($sourceArray,
                                ($element instanceof ReflectionClass ? '' : '    ') . "{$comment}\n");
                        }
                    }
                    $source = implode('', $sourceArray);
                } catch (\Exception $e) {
                    throw new xPDOException("Error getting source from Reflection element: {$e->getMessage()}");
                }
            }
        } elseif ($element instanceof ReflectionProperty) {
            $source = '    ';
            if ($includeComment) {
                $comment = $element->getDocComment();
                if (!empty($comment)) {
                    $source = "\n    {$comment}\n    ";
                }
            }
            if ($element->isPublic()) {
                $source .= 'public ';
            } elseif ($element->isProtected()) {
                $source .= 'protected ';
            } elseif ($element->isPrivate()) {
                $source .= 'private ';
            }
            if ($element->isStatic()) {
                $source .= 'static ';
            }
            $source .= '$' . $element->getName();
            if (array_key_exists($element->getName(),
                    $this->defaultProperties) && !is_null($this->defaultProperties[$element->getName()])) {
                $source .= ' = ' . xPDOGenerator::varExport($this->defaultProperties[$element->getName()], 1);
            }
            $source .= ';';
        }
        return $source;
    }

    /**
     * @param ReflectionClass|ReflectionFunctionAbstract $element
     * @param int|null                                   $start
     * @param bool|int|null                              $end
     *
     * @return array
     */
    public function getSourceArray($element = null, $start = null, $end = false)
    {
        $sourceArray = false;
        /* @var ReflectionClass|ReflectionFunctionAbstract $element */
        if ($element === null) {
            $element =& $this;
        }
        if (($element instanceof ReflectionClass || $element instanceof ReflectionFunctionAbstract) && is_readable($element->getFileName())) {
            $startOffset = is_int($start) ? $start : $element->getStartLine() - 1;
            $endOffset = is_int($end) || is_null($end) ? $end : ($element->getEndLine() - $element->getStartLine()) + 1;
            $sourceArray = array_slice(file($element->getFileName()), $startOffset, $endOffset);
        }
        return $sourceArray;
    }
}
