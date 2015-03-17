<?php
namespace Authentication;

use Authentication\DependencyInjection\Configuration;
use Safan\GlobalExceptions\FileNotFoundException;
use Safan\Safan;

class AuthManager
{
    /**
     * Available authentication drivers
     *
     * @var array
     */
    private $drivers = array(
        'memcacheAuth' => 'Authentication\Drivers\MemcacheAuth'
    );

    /**
     * Initialize Authentication
     *
     * @param $authParams
     * @throws FileNotFoundException
     */
    public function init($authParams){
        // build config parameters
        $config = new Configuration();
        $config->buildConfig($authParams);
        // check driver
        if(!isset($this->drivers[$authParams['driver']]))
            throw new FileNotFoundException($authParams['driver']);

        $driverClass = $this->drivers[$authParams['driver']];
        $auth = new $driverClass($config);
        $auth->checkStatus();

        // set to object manager
        $om = Safan::handler()->getObjectManager();
        $om->setObject('authentication', $auth);
    }
}