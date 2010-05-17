<?php

abstract class CTM_Machine
{
    const MACHINE_BROWSER_FIREFOX = 'firefox';
    const MACHINE_BROWSER_EXPLORER = 'ie';
    const MACHINE_BROWSER_CHROME = 'chrome';
    const MACHINE_BROWSER_SAFARI = 'safari';

    protected $guid;
    protected $os;
    protected $browsers = array();

    public function getGuid()
    {
        return $this->guid;
    }

    public function getOs()
    {
        return $this->os;
    }

    public function getBrowsers()
    {
        return $this->browsers;
    }

    protected function init()
    {
        $this->findGuid();
        $this->findOs();
        $this->findBrowsers();
    }

    abstract public function findGuid();
    abstract public function findOs();
    abstract public function findBrowsers();

}

?>
