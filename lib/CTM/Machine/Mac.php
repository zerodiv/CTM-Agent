<?php

require_once 'CTM/Machine.php';

class CTM_Machine_Mac extends CTM_Machine
{
    public function __construct()
    {
        parent::init();
    }
    
    public function findGuid()
    {
        exec('hostname', $output, $return);

        if ((string) $return === '0') {
            if (!empty($output) && is_array($output)) {
                $this->guid = array_pop($output);
            }
        }
    }

    public function findIp()
    {
        preg_match_all('/inet addr:\s?([^\s]+)/', `ifconfig -a`, $ips);
        $this->ip =  $ips[1][0]; // this is unreliable
    }

    public function findOs()
    {
        exec('uname -sp', $output, $return);

        if ((string) $return === '0') {

            if (!empty($output) && is_array($output)) {
                $this->os = array_pop($output);
            }
            
        }
    }

    public function findBrowsers()
    {
        $this->findFirefox();
        $this->findChrome();
        $this->findSafari();
    }

    protected function findFirefox()
    {
        if (is_file('/Applications/Firefox.app/Contents/MacOS/updates.xml')) {

            try {

                $xml = simplexml_load_file('/Applications/Firefox.app/Contents/MacOS/updates.xml');

                if (isset($xml->update[0])) {

                    $version = null;

                    foreach ($xml->update[0]->attributes() as $f => $f_v) {
                        if ($f == 'version') {
                            $version = (string) $f_v;
                        }
                    }

                    if (isset($version)) {
                        $this->browsers[self::MACHINE_BROWSER_FIREFOX] = $version;
                    }
                }

            } catch (Exception $e) {
                $e = null;
            }
        }
    }


    protected function findChrome()
    {
        if (is_dir('/Applications/Google Chrome.app/Contents/Versions/')) {

            $fds = scandir('/Applications/Google Chrome.app/Contents/Versions/');

            $high_version_id = null;
            
            foreach ($fds as $f) {

                if ($f != '.' && $f != '..') {

                    list($major, $minor, $patch) = explode('.', $f);

                    if (isset($major) && isset($minor) && isset($patch)) {

                        if ($high_version_id == null) {

                            $high_version_id = $f;
                            
                        } else {

                            list($h_major, $h_minor, $h_patch) = explode('.', $f);

                            if ($h_major < $major) {
                                $high_version_id = $f;
                            } else if ($h_major == $major && $h_minor < $minor) {
                                $high_version_id = $f;
                            } else if ($h_major == $major && $h_minor == $minor && $h_patch < $patch) {
                                $high_version_id = $f;
                            }
                        }
                    }
                }
            }

            if (isset($high_version_id)) {
                $this->browsers[self::MACHINE_BROWSER_CHROME] = (string) $high_version_id;
            }
        }
    }




    protected function findSafari()
    {
        if (is_file('/Applications/Safari.app/Contents/version.plist')) {

            try {

                $xml = simplexml_load_file( '/Applications/Safari.app/Contents/version.plist' );

                if (isset($xml->dict->string[1])) {
                    $this->browsers[self::MACHINE_BROWSER_SAFARI] = (string) $xml->dict->string[1];
                }
                
            } catch (Exception $e) {
                $e = null;
            }
            
        }
    }
    
}


