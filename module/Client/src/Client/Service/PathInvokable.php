<?php
namespace Client\Service;

class PathInvokable
{
    /**
     * Checks if we are running on windows system
     * @var boolean
     */
    protected static $isWindows = null;
    protected static $cwd = null;

    /**
     * Gets the absolute path
     * @param string $path
     * @return string
     */
    public function getAbsolute($path)
    {
        $isWindows = $this->isWindows();
        if (!$isWindows && substr($path, 0, 1)=='~') {
            $path = getenv('HOME').substr($path, 1);
        }

        /*
         * A path is relative when it does not start with / and, in the case of a Windows system,
         * it does not start with x:\ where x is letter from a to z.
         */
        if (
            (!$isWindows && strpos($path, '/')!==0) ||
            ($isWindows && !preg_match("/^[a-z]:\\\/i", $path))
        ) { // if we have relative path
            $cwd = $this->getCwd();
            $path = $cwd.'/'.$path;
        }

        $finalPath = realpath($path);
        if ($finalPath!==false) {
            return $finalPath;
        }
        return $path;
    }

    /**
     * Gets the current working directory
     * @return string
     */
    public function getCwd()
    {
        if (!self::$cwd) {
            if (defined('CWD')) {
                $cwd = constant('CWD');
            } else {
                $cwd = getcwd();
            }
            self::$cwd = $cwd;
        }

        return self::$cwd;
    }

    /**
     * Checks if the script is running in a Windows OS
     * @return boolean
     */
    protected function isWindows()
    {
        if (self::$isWindows===null) {
            $os = php_uname('s');
            self::$isWindows = (boolean)stristr($os, 'windows');
        }

        return self::$isWindows;
    }

    /**
     * Allows explicitly setting if this is a Windows system or not.
     * @param boolean $isWindows
     * @return boolean the old state
     */
    public function setWindows($isWindows)
    {
        $currentState = self::$isWindows;
        self::$isWindows = $isWindows;
        return $currentState;
    }
}
