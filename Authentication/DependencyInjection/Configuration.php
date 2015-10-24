<?php
/**
 * This file is part of the Safan package.
 *
 * (c) Harut Grigoryan <ceo@safanlab.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
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
     * @var bool
     */
    private $crossDomainUrl = false;

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

    /**
     * @return bool
     */
    public function getCrossDomainUrl(){
        return $this->crossDomainUrl;
    }
}