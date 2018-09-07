<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 01/08/18
 * Time: 09:42
 */

namespace Crawler\Services;

use Laravel\Dusk\Chrome\ChromeProcess;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

class DuskService
{
    protected $disable_gpu;
    protected $headless;
    protected $driver;

    public function __construct()
    {
        $process = (new ChromeProcess)->toProcess();
        $process->start();
        $prefs = array('download.default_directory' => storage_path('app/public'));
        $options = (new ChromeOptions)->addArguments([
            env('DUSK_DISABLE_GPU','--disable-gpu'),
            env('DUSK_HEADLESS','#--headless'),


        ]);
        $options->setExperimentalOption('prefs',$prefs);
        $capabilities = DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options);
        $this->driver = retry(5, function () use($capabilities) {
            return RemoteWebDriver::create('http://localhost:9515', $capabilities);
        }, 50);
    }

    public  function remoteDriver()
    {
        return  $this->driver;
    }
}