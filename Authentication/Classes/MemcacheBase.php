<?php
namespace Authentication\Classes;

class MemcacheBase
{
    /**
     * @var \Memcache
     */
    private $memcache;

    /**
     *
     */
    public function __construct(){
        $this->memcache = new \Memcache;
        $this->memcache->connect('localhost', 11211) or die ("Could not connect");
    }

    /**
     * @param $key
     * @return array|bool|string
     */
    public function get($key){
        return ($this->memcache) ? $this->memcache->get($key) : false;
    }

    /**
     * Set Memcache
     *
     * @param $key
     * @param $object
     * @param int $timeout
     * @return bool
     */
    public function set($key, $object, $timeout = 60){
        return ($this->memcache) ? $this->memcache->set($key, $object, MEMCACHE_COMPRESSED, $timeout) : false;
    }

    /**
     * Remove Memcache key
     *
     * @param $key
     * @return bool
     */
    public function remove($key){
        return ($this->memcache) ? $this->memcache->delete($key) : false;
    }
}