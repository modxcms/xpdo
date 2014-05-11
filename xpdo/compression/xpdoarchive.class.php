<?php
/*
 * Copyright 2010-2014 by MODX, LLC.
 *
 * This file is part of xPDO.
 *
 * xPDO is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * xPDO is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * xPDO; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 */

/**
 * A class to abstract archiving operations.
 *
 * @package xpdo
 * @subpackage compression
 */

/**
 * Represents a compressed archive.
 *
 * @package xpdo
 * @subpackage compression
 */
class xPDOArchive {

    public $xpdo = null;
    protected $_file = '';
    protected $_options = array();

    /**
     * Construct an instance representing a specific archive.
     *
     * @param xPDO &$xpdo A reference to an xPDO instance.
     * @param array $options An array of options for this instance.
     */
    public function __construct(xPDO &$xpdo, array $options = array()) {
        $this->xpdo =& $xpdo;
        $this->_options = !empty($options) ? $options : array();
        $this->_file = isset($this->_options['file']) ? $this->_options['file'] : '';

    }

    /**
     * Pack the contents from the source into the archive.
     *
     * @todo Implement various ways to pack an archive and add custom file filtering options
     *
     * @param string $source The path to the source file(s) to pack.
     * @param array $options An array of options for the operation.
     * @return array An array of results for the operation.
     */
    public function pack($source, array $options = array()) {

        $results = array();

        $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, '[' . __METHOD__ . '] Not yet implemented');

        return $results;
    }

    /**
     * Unpack the compressed contents from the archive to the target.
     *
     * @param string $target The path of the target location to unpack the files.
     * @param array $options An array of options for the operation.
     * @return array An array of results for the operation.
     */
    public function unpack($target = '', $options = array()) {

        $results = false;

        if (empty($this->_file)) {
            $this->_file = isset($options['file']) ? $options['file'] : '';
        }

        if (file_exists($this->_file)) {
            $options['type'] = pathinfo($this->_file, PATHINFO_EXTENSION);
            
            switch ($options['type']) {
                case 'zip':
                    $results = $this->zipExtract($target, $options);
                    break;

                case 'tar':
                case 'gz':
                case 'bz2':
                case 'bzip2':
                case 'tgz':
                case 'tbz2':
                    $results = $this->tarExtract($target, $options);
                    break;
                
                default:
                    break;
            }
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, '[' . __METHOD__ . '] Archive file to unpack does not exist!');
        }

        return $results;
    }

    /**
     * Unpack a zip file
     *
     * @param string $target The path of the target location to unpack the files.
     * @param array $options An array of options for the operation.
     * @return array|string|boolean An array of results for the operation, a string in case of cli operations or a boolean in case of PharData or failure
     */
    public function zipExtract($target = '', $options = array()) {

        $results = false;

        if (empty($this->_file)) {
            $this->_file = isset($options['file']) ? $options['file'] : '';
        }

        // extract the archive in the same directory if no $target path is specified
        if (empty($target)) {
            $target = dirname($this->_file) . DIRECTORY_SEPARATOR;
        }

        if (!empty($this->_file)) {
            if (class_exists('ZipArchive') && $this->xpdo->getService('zip', 'compression.xPDOZip', XPDO_CORE_PATH, array_merge($options, $this->_options))) {
                $results = $this->xpdo->zip->unpack($target);
                $this->xpdo->zip->close();
            } else if (class_exists('PclZip') || include_once(XPDO_CORE_PATH . 'compression/pclzip.lib.php')) {
                $archive = new PclZip($this->_file);

                if ($archive) {
                    $results = array_map('current', $archive->extract(PCLZIP_OPT_PATH, $target));
                }
            } else if (is_callable('zip_open')) {
                $archive = zip_open($this->_file);

                if (!is_dir($target)) {
                    $this->xpdo->cacheManager->writeTree($target);
                }

                while ($zip_entry = zip_read($archive)) {
                    $filename = zip_entry_name($zip_entry);
                    $path = $target . DIRECTORY_SEPARATOR . substr($filename, 0, strrpos($filename, '/'));
                    $filesize = zip_entry_filesize($zip_entry);

                    if (file_exists($path) || mkdir($path)) {
                        $results[] = $target . DIRECTORY_SEPARATOR . $filename;

                        if ($filesize > 0) {
                            $contents = zip_entry_read($zip_entry, $filesize);
                            file_put_contents($target . DIRECTORY_SEPARATOR . $filename, $contents);
                        }
                    }
                }
            }

            if (!$results) {
                $results = $this->_cliExtract($target, $options);
            }
        }

        return $results;
    }

    /**
     * Unpack a tar, gzip, bzip2 file
     *
     * @param string $target The path of the target location to unpack the files.
     * @param array $options An array of options for the operation.
     * @return array|string|boolean An array of results for the operation, a string in case of cli operations or a boolean in case of PharData or failure
     */
    public function tarExtract($target = '', $options = array()) {

        $results = false;

        if (empty($this->_file)) {
            $this->_file = isset($options['file']) ? $options['file'] : '';
        }

        // extract the archive in the same directory if no $target path is specified
        if (empty($target)) {
            $target = dirname($this->_file) . DIRECTORY_SEPARATOR;
        }

        if (!empty($this->_file)) {
            if (class_exists('PharData')) {
                try {
                    $archive = new PharData($this->_file);
                    $results = $archive->extractTo($target, null, true);
                } catch (Exception $e) {
                    $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, '[' . __METHOD__ . '] An error occured when trying to extract ' . $this->_file . ' to ' . $target . ': ' . $e);
                }
            }

            if (!$results) {
                $results = $this->_cliExtract($target, $options);
            }
        }

        return $results;
    }

    /**
     * Unpack an archive via command line if possible trough PHPs system functions
     *
     * @param string $target The path of the target location to unpack the files.
     * @param array $options An array of options for the operation.
     * @return array|string|boolean An array of results for the operation, a string in case of cli operations or a boolean in case of PharData or failure
     */
    protected function _cliExtract($target = '', $options = array()) {

        $results = false;

        if (empty($this->_file)) {
            $this->_file = isset($options['file']) ? $options['file'] : '';
        }

        // extract the archive in the same directory if no $target path is specified
        if (empty($target)) {
            $target = dirname($this->_file) . DIRECTORY_SEPARATOR;
        }

        if (!empty($this->_file)) {
            // check for windows systems running PHP, as they do not seem to have a native unzip functionality
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                $sysfns = array(
                    'shell_exec' => false,
                    'system' => false,
                    'exec' => false,
                );

                $disabled = array_map('trim', explode(',', ini_get('disable_functions')));

                foreach ($sysfns as $func => $value) {
                    $sysfns[$func] = !in_array($func, $disabled);
                }

                $sysfns = array_filter($sysfns); // remove not available system functions

                if (!empty($sysfns)) {
                    $sysfn = key($sysfns); // get the first available system function
                    $library = $options['type'] === 'zip' ? $sysfn('which unzip') : $sysfn('which tar');
                    
                    if (!empty($library)) {
                        $command = implode(' ', array(
                            $options['type'] === 'zip' ? 'unzip' : 'tar',
                            $options['type'] === 'zip' ? '' : ('-xv' . (stristr($options['type'], 'gz') ? 'z' : (stristr($options['type'], 'bz2') ? 'j' : '')) . 'f'),
                            $this->_file
                        ));
                        
                        // sanitize and escape command
                        $command = escapeshellcmd(preg_replace('#(\s){2,}#is', ' ', str_replace(array("\n", "'"), array('', '"'), $command)));

                        if (!is_dir($target)) {
                            $this->xpdo->cacheManager->writeTree($target);
                        }

                        chdir($target); // switch to target directory

                        $results = $sysfn($command); // execute command
                    } else {
                         $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, '[' . __METHOD__ . '] Could not find necessary command on your system.');
                    }
                }
            }
        }

        return $results;
    }
    /**
     * Get an option from supplied options, the xPDOArchive instance options, or xpdo itself.
     *
     * @param string $key Unique identifier for the option.
     * @param array $options A set of explicit options to override those from xPDO or the
     * xPDOArchive instance.
     * @param mixed $default An optional default value to return if no value is found.
     * @return mixed The value of the option.
     */
    public function getOption($key, $options = null, $default = null) {
        $option = $default;
        if (is_array($key)) {
            if (!is_array($option)) {
                $default= $option;
                $option= array();
            }
            foreach ($key as $k) {
                $option[$k]= $this->getOption($k, $options, $default);
            }
        } elseif (is_string($key) && !empty($key)) {
            if (is_array($options) && !empty($options) && array_key_exists($key, $options)) {
                $option = $options[$key];
            } elseif (is_array($this->_options) && !empty($this->_options) && array_key_exists($key, $this->_options)) {
                $option = $this->_options[$key];
            } else {
                $option = $this->xpdo->getOption($key, null, $default);
            }
        }
        return $option;
    }
}