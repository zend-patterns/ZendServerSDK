<?php
namespace Client\Service;

use Zend\Config\Writer\Ini as ConfigWriter;
use Zend\Config\Reader\Ini as ConfigReader;
use Zend\Config\Exception as ConfigException;

class TargetInvokable
{
    /**
     * @var string
     */
    private $configFile;

    /**
     * Gets the location of the configuration file
     * @return the $configFile
     */
    public function getConfigFile()
    {
        return $this->configFile;
    }

    /**
     * Sets the location of the configuration file
     *
     * @param field_type $configFile
     */
    public function setConfigFile($configFile)
    {
        $this->configFile = $configFile;
    }

 /**
     * Reads all targets
     * @return array associative array with all targets.
     *               - key - name of the the target
     *               - value - target properties
     * @throws ConfigException
     */
    public function load()
    {
        $reader = new ConfigReader();
        return $reader->fromFile($this->configFile);
    }


    /**
     * Saves all target data
     * @param array $data associative array with all targets.
     *               - key - name of the the target
     *               - value - target properties
     *
     * @throws ConfigException
     */
    public function save(array $data)
    {
        $config = new ConfigWriter();
        return $config->toFile($this->configFile, $data);
    }
}
