#!/usr/bin/php -q
<?php

require_once dirname(__FILE__) . '/../bootstrap.php';
require_once 'Light/CommandLine/Script.php';
require_once 'CTM/Machine/Factory.php';

class CTM_Register_Linux extends Light_CommandLine_Script {

    protected $machine;

    public function init() {

        $this->machine = CTM_Machine_Factory::factory();

        $this->arguments()->addStringArgument('server', 'Path to server', true);
        
    }

    public function run() {

        $this->message('Running registration script.');

        $post_values = array();
        $post_values['guid'] = $this->machine->getGuid();
        $post_values['ip'] = $this->machine->getIp();
        $post_values['os'] = $this->machine->getOs();

        $browsers = $this->machine->getBrowsers();

        foreach ($browsers as $browser => $browser_version) {
            $post_values[$browser] = 'yes';
            $post_values[$browser . '_version'] = $browser_version;
        }

        $this->message("Post Values:\n" . print_r($post_values, true));

        $ch = curl_init($this->arguments()->getArgument('server')->getValue() . '/et/phone/home/1.0/');
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_values );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        $return_xml = curl_exec($ch);
        $return_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $this->message("Return Status: " . $return_status);
        $this->message("Return XML:\n" . $return_xml);

    }
}

$ctm_register_obj = new CTM_Register_Linux();
