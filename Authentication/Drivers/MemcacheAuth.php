<?php
/**
 * This file is part of the Safan package.
 *
 * (c) Harut Grigoryan <ceo@safanlab.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Authentication\Drivers;

use Authentication\Classes\AuthBase;
use Authentication\DependencyInjection\Configuration;
use Authentication\Exceptions\ClientDataException;
use Authentication\Exceptions\MemcacheNotFoundException;
use Authentication\Models\RoleBase;
use Authentication\Models\RoleGroupBase;
use Authentication\Models\UserBase;
use Safan\Safan;

class MemcacheAuth extends AuthBase
{
    /**
     * @var string
     */
    private $hashKey;

    /**
     * @var bool
     */
    private $crossDomain = false;

    /**
     * @var string
     */
    private $crossDomainUrl = '';

    /**
     * User id, by default 0 (no authenticated)
     *
     * @var
     */
    private $userID = 0;

    /**
     * Remember property for login
     *
     * @var bool
     */
    private $isRemember = false;

    /**
     * Cookie Hash name
     *
     * @var string
     */
    private $cookieHashPrefix = 'ma_chp';

    /**
     * Cookie Hash name
     *
     * @var string
     */
    private $cookieUserIDPrefix = 'ma_c_id';

    /**
     * Memcache user prefix
     * Full key e.g. - $this->hashKey . 'mem_user_1'
     *
     * @var string
     */
    private $memcacheUserPrefix = '_mem_user';

    /**
     * Memcache key timeout (1 day)
     */
    const MEMCACHE_CODE_TIMEOUT = 50400;

    /**
     * Cookie timeout for remember (1 year)
     */
    const COOKIE_LONG_DATE = 31536000;

    /**
     * Cookie actually timeout  (3 day)
     */
    const COOKIE_SHORT_DATE = 151200;

    /**
     * @param  Configuration $config
     * @throws MemcacheNotFoundException
     */
    public function __construct(Configuration $config){
        if(!class_exists('Memcache'))
            throw new MemcacheNotFoundException();

        // set hash
        $this->hashKey        = $config->getHashKey();
        $this->crossDomain    = $config->getCrossDomain();
        $this->crossDomainUrl = $config->getCrossDomainUrl();
    }

    /**
     * @param $email
     * @param $password
     * @param bool $rememberMe
     * @return mixed|void
     */
    public function login($email, $password, $rememberMe = true){
        // get model and find user
        $userModel = UserBase::instance();
        $user = $userModel->where(['email' => $email])->runOnce();

        if(is_null($user))
            return false;

        $dbPassword = hash('sha256', $password);

        if($dbPassword !== $user->password)
            return false;

        $this->userID        = $user->id;
        $user->lastLoginDate = new \DateTime();

        $roles = $this->getUserRoles($user);

        $this->updateHash($user, $roles);

        return true;
    }

    /**
     * Logout
     *
     * @return bool
     */
    public function logout(){
        if($this->isGuest())
            return false;
        // get instances
        $cookieObj      = Safan::handler()->getObjectManager()->get('cookie');
        $memcacheObj    = Safan::handler()->getObjectManager()->get('memcache');
        $userModel      = UserBase::instance();
        // from cookie
        $cookieObj->remove($this->cookieUserIDPrefix);
        $cookieObj->remove($this->cookieHashPrefix);
        // from memcache
        $memcacheObj->remove($this->getMemcacheKey($this->userID));
        // from db
        $userData = $userModel->findByPK($this->userID);

        if(!is_null($userData)){
            $userData->hash = '';
            $userModel->save($userData);
        }

        return true;
    }

    /**
     * Get authenticated user id
     *
     * @return mixed
     */
    public function getUserID(){
        return $this->userID;
    }

    /**
     * Check Authenticated status from applications
     *
     * @return bool
     */
    public function isGuest(){
        if($this->userID > 0)
            return false;

        return true;
    }

    /**
     * Check Status
     *
     * @return bool
     */
    public function checkStatus(){
        if($this->checkByCookieAndMemcache())
            return true;
        elseif($this->checkByCookieAndDatabase())
            return true;

        return false;
    }

    /**
     * Check Cookie and Memcache cache
     *
     * @return bool
     */
    private function checkByCookieAndMemcache(){
        // get cookie
        $cookieObj      = Safan::handler()->getObjectManager()->get('cookie');
        $cookieUserID   = $cookieObj->get($this->cookieUserIDPrefix);
        $cookieUserHash = $cookieObj->get($this->cookieHashPrefix);

        // check cookies
        if(!$cookieUserID || !$cookieUserHash)
            return false;

        // get memcache
        $memcacheObj  = Safan::handler()->getObjectManager()->get('memcache');
        $memcacheKey  = $this->getMemcacheKey($cookieUserID);
        $memcacheData = $memcacheObj->get($memcacheKey);

        // check memcache
        if(!$memcacheData)
            return false;

        return $this->compareHashes($cookieUserHash, $memcacheData[$this->cookieHashPrefix], $cookieUserID);
    }

    /**
     * Check Cookie and Database cache
     *
     * @return bool
     */
    private function checkByCookieAndDatabase(){
        // get cookie
        $cookieObj      = Safan::handler()->getObjectManager()->get('cookie');
        $cookieUserID   = $cookieObj->get($this->cookieUserIDPrefix);
        $cookieUserHash = $cookieObj->get($this->cookieHashPrefix);

        // check cookies
        if(!$cookieUserID || !$cookieUserHash)
            return false;

        // get user model and data
        $userModel = UserBase::instance();
        $userData = $userModel->findByPK($cookieUserID);

        // check record
        if(is_null($userData))
            return false;

        $compareResult = $this->compareHashes($cookieUserHash, $userData->hash, $cookieUserID);

        if($compareResult) {
            $roles = $this->getUserRoles($userData);
            $this->updateHash($userData, $roles);
        }

        return $compareResult;
    }

    /**
     * Compare cookie and original hash data
     *
     * @param $cookieHash
     * @param $originalHash
     * @param $userID
     * @return bool
     */
    private function compareHashes($cookieHash, $originalHash, $userID){
        // get client data
        $ip      = $this->getClientIp();
        $browser = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : false;

        // check client data
        if($ip <= 0 || !$browser)
            return false;

        // generate cookie compare hash
        $cookieHashForCompare = hash('sha256', $ip . $browser . $cookieHash . $userID);

        // compare
        if($cookieHashForCompare === $originalHash){
            $this->userID = $userID;
            return true;
        }

        return false;
    }

    /**
     * Update hashes
     *
     * @param $userData
     * @param $roles
     * @throws \Authentication\Exceptions\ClientDataException
     */
    private function updateHash($userData, $roles){
        // get client data
        $ip      = $this->getClientIp();
        $browser = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : false;

        // check client data
        if($ip <= 0 || !$browser)
            throw new ClientDataException();

        // generate cookie cache
        $cHash = hash('sha256', $userData->email . $this->hashKey . $userData->password . time());
        // generate hash for db and memcache
        $oHash = hash('sha256', $ip . $browser . $cHash . $userData->id);

        // update db hash
        $userModel = UserBase::instance();
        $userData->hash = $oHash;
        $userData->hashCreationDate = new \DateTime();
        $userModel->save($userData);

        $memcacheRoles = [];
        foreach ($roles as $role) {
            $memcacheRoles[$role->alias] = [
                'id'    => $role->id,
                'alias' => $role->alias,
                'group' => $role->name,
            ];
        }

        // update memcache hash
        $memcacheObj  = Safan::handler()->getObjectManager()->get('memcache');
        $memcacheKey  = $this->getMemcacheKey($userData->id);
        $memcacheData = [
            'id'                    => $userData->id,
            $this->cookieHashPrefix => $oHash,
            'roles'                 => $memcacheRoles
        ];

        $memcacheObj->set($memcacheKey, $memcacheData, self::MEMCACHE_CODE_TIMEOUT);

        // update cookie hash
        $cookieObj = Safan::handler()->getObjectManager()->get('cookie');

        if($this->isRemember)
            $cookieDate = time() + self::COOKIE_LONG_DATE;
        else
            $cookieDate = time() + self::COOKIE_SHORT_DATE;

        $domain = null;

        if($this->crossDomain) {
            if (strlen($this->crossDomainUrl) > 0)
                $domain = '.' . $this->crossDomainUrl;
            else
                $domain = '.' . substr(Safan::handler()->baseUrl, strpos(Safan::handler()->baseUrl, '://') + 3);
        }

        $cookieObj->set($this->cookieUserIDPrefix, $userData->id, $cookieDate, '/', $domain);
        $cookieObj->set($this->cookieHashPrefix, $cHash, $cookieDate, '/', $domain);
    }

    /**
     * Get user roles
     *
     * @param $user
     * @return mixed
     */
    public function getUserRoles($user){
        // get model and find roles
        $roleBaseModel = RoleBase::instance();

        return $roleBaseModel
                    ->join(RoleGroupBase::instance()->table(),
                           'left',
                           RoleBase::instance()->table() . '.id = ' . RoleGroupBase::instance()->table() . '.roleID',
                           RoleGroupBase::instance()->getFields()
                    )
                    ->where([RoleGroupBase::instance()->table() . '.userID' => $user->id])
                    ->run();
    }

    public function hasRole($alias)
    {
        if (!$this->userID)
            return false;

        $memcacheObj = Safan::handler()->getObjectManager()->get('memcache');
        $memcacheKey = $this->getMemcacheKey($this->userID);
        $userCache   = $memcacheObj->get($memcacheKey);

        $roles = $userCache['roles'];

        if (isset($roles[$alias]))
            return true;

        return false;
    }

    /**
     * Get Client IP address, integer
     *
     * @return int|string
     */
    private function getClientIp(){
        if(!isset($_SERVER['REMOTE_ADDR']))
            return 0;

        $ip = ip2long($_SERVER['REMOTE_ADDR']);

        if(($ip != -1) && ($ip !== false))
            $lastLoginIp = sprintf('%u', $ip);
        else
            $lastLoginIp = 0;

        return $lastLoginIp;
    }

    /**
     * Generate key name and return
     *
     * @param $userID
     * @return string
     */
    private function getMemcacheKey($userID){
        return $this->hashKey . $this->memcacheUserPrefix . '_' . $userID;
    }

    /**
     * Return Data in memcache
     *
     * @return mixed
     */
    public function getCurrentUserCache(){
        // get memcache object instance
        $memcache = Safan::handler()->getObjectManager()->get('memcache');

        return $memcache->get($this->getMemcacheKey($this->userID));
    }

    /**
     * Return Key in memcache
     *
     * @return bool|string
     */
    public function getCurrentUserCacheKey(){
        if($this->userID > 0)
            return $this->hashKey . $this->memcacheUserPrefix . '_' . $this->userID;

        return false;
    }
}