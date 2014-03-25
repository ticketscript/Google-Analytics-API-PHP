<?php

namespace GoogleApi;

/**
 * Abstract Auth class
 */
abstract class GoogleOauth
{
    const TOKEN_URL = 'https://accounts.google.com/o/oauth2/token';
    const SCOPE_URL = 'https://www.googleapis.com/auth/analytics.readonly';

    /**
     * @var bool
     */
    protected $assoc = true;
    /**
     * @var string
     */
    protected $clientId = '';

    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->{$key} = $value;
    }

    /**
     * @param $identifier
     */
    public function setClientId($identifier)
    {
        $this->clientId = $identifier;
    }

    /**
     * @param $bool
     */
    public function returnObjects($bool)
    {
        $this->assoc = !$bool;
    }

    /**
     * To be implemented by the subclasses
     */
    public function getAccessToken($data = null)
    {
    }
}
