<?php
namespace Authentication\DependencyInjection;

class Configuration
{
    /**
     * @var string
     */
    private $hashKey = '454w#7_=kd78$9+89sa][pi9a1d^&*d4s11as$*(ad2';

    /**
     * @var bool
     */
    private $crossDomain = false;

    /**
     * @param $params
     */
    public function buildConfig($params){
        if(isset($params['token']) && strlen($params['token']) > 0)
            $this->hashKey = hash('sha256', $this->hashKey . $params['token']);

        if(isset($params['crossDomain']))
            $this->crossDomain = $params['crossDomain'];
    }

    /**
     * @return string
     */
    public function getHashKey(){
        return $this->hashKey;
    }

    /**
     * @return bool
     */
    public function getCrossDomain(){
       return $this->crossDomain;
    }
}