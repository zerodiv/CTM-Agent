#!/usr/bin/php
<?php

require_once dirname( __FILE__ ) . '/../bootstrap.php';
require_once 'Light/CommandLine/Script.php';
require_once 'CTM/Machine/Factory.php';
require_once 'CTM/Files.php';

class CTM_Test_Agent extends Light_CommandLine_Script
{
    protected $machine;
    protected $files;
    protected $testRunBrowserId;
    protected $downloadUrl;
    protected $testBrowser;
    protected $testStatus; // 1 success, 0 failure
    protected $testDuration;

    public function init()
    {
        $this->machine = CTM_Machine_Factory::factory();
        $this->files = new CTM_Files();
        $this->testStatus = 0;
        $this->testDuration = 0;

        $this->arguments()->addStringArgument('server', 'Path to server', true);
    }

    /**
     * @todo selenium-server.jar path should be inside the config?
     * @todo Is this going to execute on Windows or Mac?
     * 
     */
    public function run()
    {
        $this->message("Running CTM_Test_Agent.");
        
        $this->getTestData();

        if (!empty($this->downloadUrl)) {
            
            $this->downloadTest();

            if ($this->testBrowser) {

                $this->message("Running suite.");

                // see -browserSideLog and -log for debugging information at
                // http://seleniumhq.org/docs/05_selenium_rc.html#selenium-server-logging
                $commandString = "java -jar selenium-server.jar -multiwindow -htmlSuite '*" . $this->testBrowser . "' 'http://www.adicio.com/' '" .  $this->files->getSuite() . "' '" . $this->files->getLogFile() . "'";

                $this->message("Running $commandString");

                $testStart = microtime(true);
                system($commandString, $returnValue);
                $testEnd = microtime(true);

                $this->testDuration = $testEnd - $testStart;

                if ($returnValue == 0) {
                    $this->message("############# Succeeded! #############");
                    $this->testStatus = 1;
                } else {
                    $this->message("############# Failed! #############");
                    $this->testStatus = 0;
                }

                $this->sendLog();
                
            } else {
                $this->message("No test browser defined");
            }
            
        } else {
            $this->message("Could not get the download URL.");
        }

    }

    /**
     * Polls the CTM server for work and extracts the test download url
     *
     */
    protected function getTestData()
    {
        $this->message("Requesting work.");

        $post_values = array();
        $post_values['guid'] = $this->machine->getGuid();

        $this->message("Post Values:\n" . print_r($post_values, true));

        $ch = curl_init($this->arguments()->getArgument('server')->getValue() . '/et/poll/1.0/');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_values);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $return_xml = curl_exec($ch);
        $return_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->message("Return Status for getDownloadLink(): " . $return_status);
        $this->message("Return XML for getDownloadLink():\n" . $return_xml);

        try {
            
            // if we have a valid download url
            $xml = simplexml_load_string($return_xml);
            
            $this->testRunBrowserId = (string) @$xml->testRunBrowserId;
            $this->downloadUrl = (string) @$xml->downloadUrl;
            $this->testBrowser = (string) @$xml->testBrowser;

        } catch (Exception $e) {
            $this->message("Could not parse XML: {$e->getMessage()}");
            $e = null;
        }
    }

    /**
     * Downloads the test data to local disk for a given url
     *
     */
    protected function downloadTest()
    {
        $this->message("Downloading test data from {$this->downloadUrl}.");

        $this->message("Writing test data to {$this->files->getSuiteFile()}.");
        $handle = fopen($this->files->getSuiteFile(), 'w');

        $ch = curl_init($this->downloadUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array());
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FILE, $handle);
        
        curl_exec($ch);

        $return_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        fclose($handle);

        $this->message("Return Status for downloadTest(): " . $return_status);
        
        // extract suite zip
        $this->message("Unzipping file to {$this->files->getSuiteDir()}");
        system("unzip {$this->files->getSuiteFile()} -d {$this->files->getSuiteDir()}");
    }

    /**
     * Sends back output colleced during the agent's run back to the CTM server
     *
     */
    protected function sendLog()
    {
        $this->message("Sending log file {$this->files->getLogFile()} data back.");

        // send the output back to CTM
        $ch = curl_init($this->arguments()->getArgument('server')->getValue() . '/et/log/');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            'testRunBrowserId' => $this->testRunBrowserId,
            'testDuration' => $this->testDuration,
            'testStatus' => $this->testStatus,
            'logData' => file_exists($this->files->getLogFile()) ? file_get_contents($this->files->getLogFile()) : null,
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $return_xml = curl_exec($ch);
        $return_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->message("Return Status for sendLog(): " . $return_status);
        $this->message("Return XML for sendLog():\n" . $return_xml);
    }

}


$ctm_test_agent = new CTM_Test_Agent();

