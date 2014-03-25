<?php

namespace GoogleApi;

require_once(__DIR__ . DIRECTORY_SEPARATOR . 'GoogleAuth.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'Http.php');

/**
 * Oauth 2.0 for service applications requiring a private key
 * openssl extension for PHP is required!
 *
 * @extends GoogleOauth
 */
class GoogleOauthService extends GoogleOauth
{
    const MAX_LIFETIME_SECONDS = 3600;
    const GRANT_TYPE = 'urn:ietf:params:oauth:grant-type:jwt-bearer';

    /**
     * @var string
     */
    protected $email = '';
    /**
     * @var mixed|null
     */
    protected $privateKey = null;
    /**
     * @var string
     */
    protected $password = 'notasecret';

    /**
     * Constructor
     *
     * @param string $clientId   (default: '') Client-ID of your project from the Google APIs console
     * @param string $email      (default: '') E-Mail address of your project from the Google APIs console
     * @param mixed  $privateKey (default: null) Path to your private key file (*.p12)
     *
     * @throws \Exception
     */
    public function __construct($clientId = '', $email = '', $privateKey = null)
    {
        if (!function_exists('openssl_sign')) {
            throw new \Exception('openssl extension for PHP is needed.');
        }
        $this->clientId = $clientId;
        $this->email = $email;
        $this->privateKey = $privateKey;
    }


    /**
     * @param $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @param $key
     */
    public function setPrivateKey($key)
    {
        $this->privateKey = $key;
    }


    /**
     * Get the accessToken in exchange with the JWT
     *
     * @param mixed $data (default: null) No data needed in this implementation
     *
     * @throws \Exception
     * @return array Array with keys: access_token, expires_in
     */
    public function getAccessToken($data = null)
    {
        if (!$this->clientId || !$this->email || !$this->privateKey) {
            throw new \Exception('You must provide the clientId, email and a path to your private Key');
        }

        $jwt = $this->generateSignedJWT();

        $params = array(
            'grant_type' => self::GRANT_TYPE,
            'assertion' => $jwt,
        );

        $auth = Http::curl(GoogleOauth::TOKEN_URL, $params, true);

        return json_decode($auth, $this->assoc);
    }


    /**
     * Generate and sign a JWT request
     * See: https://developers.google.com/accounts/docs/OAuth2ServiceAccount
     *
     * @throws \Exception
     */
    protected function generateSignedJWT()
    {
        // Check if a valid privateKey file is provided
        if (!file_exists($this->privateKey) || !is_file($this->privateKey)) {
            throw new \Exception('Private key does not exist');
        }

        // Create header, claim and signature
        $header = array(
            'alg' => 'RS256',
            'typ' => 'JWT',
        );

        $timeStamp = time();
        $params = array(
            'iss' => $this->email,
            'scope' => GoogleOauth::SCOPE_URL,
            'aud' => GoogleOauth::TOKEN_URL,
            'exp' => $timeStamp + self::MAX_LIFETIME_SECONDS,
            'iat' => $timeStamp,
        );

        $encodings = array(
            base64_encode(json_encode($header)),
            base64_encode(json_encode($params)),
        );

        // Compute Signature
        $input = implode('.', $encodings);
        $certs = array();
        $pkcs12 = file_get_contents($this->privateKey);
        if (!openssl_pkcs12_read($pkcs12, $certs, $this->password)) {
            throw new \Exception('Could not parse .p12 file');
        }
        if (!isset($certs['pkey'])) {
            throw new \Exception('Could not find private key in .p12 file');
        }
        $keyId = openssl_pkey_get_private($certs['pkey']);
        if (!openssl_sign($input, $sig, $keyId, 'sha256')) {
            throw new \Exception('Could not sign data');
        }

        // Generate JWT
        $encodings[] = base64_encode($sig);
        $jwt = implode('.', $encodings);

        return $jwt;
    }
}
