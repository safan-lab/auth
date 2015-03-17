<?php
namespace Authentication\Classes;

use Authentication\DependencyInjection\Configuration;
use Authentication\Model\UserBase;

abstract class AuthBase
{
    /**
     * Method for set config data
     *
     * @param Configuration $config
     */
    abstract protected function __construct(Configuration $config);

    /**
     * Method for login user
     *
     * @param $email
     * @param $password
     * @param bool $rememberMe
     * @return mixed
     */
    abstract protected function login($email, $password, $rememberMe = true);

    /**
     * Method for logout user
     *
     * @return mixed
     */
    abstract protected function logout();

    /**
     * Method for check authentication status
     *
     * @return mixed
     */
    abstract protected function checkStatus();

    /**
     * Get authenticated user id
     *
     * @return mixed
     */
    abstract protected function getUserID();

    /**
     * Check authenticated status
     *
     * @return mixed
     */
    abstract protected function isGuest();
}