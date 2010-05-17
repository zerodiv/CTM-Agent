<?php

class CTM_Machine_Factory
{
    /**
     * Factory method to create machine instances
     *
     * @todo Figure out mac detection
     * @return CTM_Machine
     */
    public static function factory()
    {
        $os = trim(php_uname('s'));

        if (strcasecmp($os, 'darwin') == 0) {
            require_once 'CTM/Machine/Mac.php';
            return new CTM_Machine_Mac();
        }

        if (stripos($os, 'linux') !== false) {
            require_once 'CTM/Machine/Linux.php';
            return new CTM_Machine_Linux();
        }

        if (stripos($os, 'win') !== false) {
            require_once 'CTM/Machine/Windows.php';
            return new CTM_Machine_Windows();
        }

        throw new Exception('Could not identify OS.');
    }

}

?>
