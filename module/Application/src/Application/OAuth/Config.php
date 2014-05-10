<?php
/**
 * Copyright 2012 Andris Causs (http://codeaid.net)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @category  Modules
 * @package   OAuth
 */
namespace Application\OAuth;

/**
 * @category  Modules
 * @package   OAuth
 */
class Config
{

    /**
     * The identifier portion of the client credentials (equivalent to a username).
     * The parameter name reflects a deprecated term (Consumer Key) used in previous
     * revisions of the specification, and has been retained to
     * maintain backward compatibility
     *
     * @var string
     */
    protected $_consumerKey = null;

    /**
     * Consumer secret
     *
     * @var string
     */
    protected $_consumerSecret = null;

    /**
     * The name of the signature method used by the client to sign the request
     *
     * @var string
     */
    protected $_signatureMethod = OAuth::SIG_METHOD_HMACSHA1;

    /**
     * Authorization type
     *
     * @var int
     */
    protected $_authType = OAuth::AUTH_TYPE_AUTHORIZATION;

    /**
     * The HTTP request method
     *
     * @var string
     */
    protected $_requestMethod = OAuth::HTTP_METHOD_POST;

    /**
     * The token value used to associate the request with the resource owner.
     * If the request is not associated with a resource owner
     * (no token available), clients MAY omit the parameter
     *
     * @var string
     */
    protected $_token = null;

    /**
     * OPTIONAL.  If present, MUST be set to "1.0".  Provides the
     * version of the authentication process
     *
     * @var string
     */
    protected $_version = '1.0';

    /**
     * An absolute URI back to which the server will
     * redirect the resource owner when the Resource Owner
     * Authorization step is completed.  If
     * the client is unable to receive callbacks or a
     * callback URI has been established via other means,
     * the parameter value MUST be set to "oob" (case
     * sensitive), to indicate an out-of-band
     * configuration.
     *
     * @var mixed
     */
    protected $_callbackUrl = OAuth::CALLBACK_OOB;

    /**
     * Temporary credential request URL
     *
     * @var string
     */
    protected $_requestTokenUrl = null;

    /**
     * Token request URI
     *
     * @var mixed
     */
    protected $_accessTokenUrl = null;

    /**
     * Resource owner authorization URI
     *
     * @var string
     */
    protected $_authorizeUrl = null;

    /**
     * Request engine to use
     *
     * @var int
     */
    protected $_requestEngine = null;

    /**
     * Save handler for temporary objects (e.g. request token)
     *
     * @var Storage\AbstractStorage
     */
    protected $_storageHandler = null;


    /**
     * Set consumer key
     *
     * @param  mixed $key
     * @return Config
     */
    public function setConsumerKey($key)
    {
        $this->_consumerKey = $key;
        return $this;
    }

    /**
     * Get consumer key
     *
     * @return string
     */
    public function getConsumerKey()
    {
        return $this->_consumerKey;
    }

    /**
     * Set consumer secret
     *
     * @param  mixed $key
     * @return Config
     */
    public function setConsumerSecret($secret)
    {
        $this->_consumerSecret = $secret;
        return $this;
    }

    /**
     * Get consumer secret
     *
     * @return string
     */
    public function getConsumerSecret()
    {
        return $this->_consumerSecret;
    }

    /**
     * Set signature method
     *
     * @param  mixed $key
     * @throws Exception
     * @return Config
     */
    public function setSignatureMethod($method)
    {
        // define valid signature methods
        $validMethods = array(
            OAuth::SIG_METHOD_HMACSHA1,
            OAuth::SIG_METHOD_RSASHA1,
            OAuth::SIG_METHOD_PLAINTEXT,
        );

        // fail if an invalid signature method is defined
        if (!in_array($method, $validMethods)) {
            throw new Exception('Invalid signature method specified: ' . $method);
        }

        // set the method
        $this->_signatureMethod = $method;
        return $this;
    }

    /**
     * Get signature method
     *
     * @return string
     */
    public function getSignatureMethod()
    {
        return $this->_signatureMethod;
    }

    /**
     * Set authentication type
     *
     * @param  int $key
     * @return Config
     */
    public function setAuthType($type)
    {
        $this->_authType = $type;
        return $this;
    }

    /**
     * Get authentication type
     *
     * @return int
     */
    public function getAuthType()
    {
        return $this->_authType;
    }

    /**
     * Set version
     *
     * @param  string $key
     * @return Config
     */
    public function setVersion($version)
    {
        $this->_version = $version;
        return $this;
    }

    /**
     * Get version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->_version;
    }

    /**
     * Set callback url
     *
     * @param  string $key
     * @return Config
     */
    public function setCallbackUrl($url)
    {
        $this->_callbackUrl = $url;
        return $this;
    }

    /**
     * Get callback url
     *
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->_callbackUrl;
    }

    /**
     * Set request token url
     *
     * @param  string $key
     * @return Config
     */
    public function setRequestTokenUrl($url)
    {
        $this->_requestTokenUrl = $url;
        return $this;
    }

    /**
     * Get request token url
     *
     * @return string
     */
    public function getRequestTokenUrl()
    {
        return $this->_requestTokenUrl;
    }

    /**
     * Set request method
     *
     * @param  string $method Request method
     * @throws Exception
     * @return Config
     */
    public function setRequestMethod($method)
    {
        // define valid request methods
        $validMethods = array(
            OAuth::HTTP_METHOD_POST,
            OAuth::HTTP_METHOD_GET,
            OAuth::HTTP_METHOD_HEAD,
            OAuth::HTTP_METHOD_PUT,
            OAuth::HTTP_METHOD_DELETE,
        );

        // fail if an invalid request method is defined
        if (!in_array($method, $validMethods)) {
            throw new Exception('Invalid request method specified: ' . $method);
        }

        // set the method
        $this->_requestMethod = $method;
        return $this;
    }

    /**
     * Get request method
     *
     * @return string
     */
    public function getRequestMethod()
    {
        return $this->_requestMethod;
    }

    /**
     * Set access token url
     *
     * @param  string $key
     * @return Config
     */
    public function setAccessTokenUrl($url)
    {
        $this->_accessTokenUrl = (string) $url;
        return $this;
    }

    /**
     * Get access token url
     *
     * @return string
     */
    public function getAccessTokenUrl()
    {
        return $this->_accessTokenUrl;
    }

    /**
     * Set 'authorize' url
     *
     * @param  string $key
     * @return Config
     */
    public function setAuthorizeUrl($url)
    {
        $this->_authorizeUrl = (string) $url;
        return $this;
    }

    /**
     * Get 'authorize' url
     *
     * @param  string $token Token to pass as a oauth_token variable
     * @return string
     */
    public function getAuthorizeUrl($token = null)
    {
        if ($token === null) {
            return $this->_authorizeUrl;
        }

        return sprintf('%s?%s=%s', $this->_authorizeUrl, OAuth::PARAM_TOKEN, $token);
    }

    /**
     * Set token
     *
     * @param  string $key
     * @return Config
     */
    public function setToken($token)
    {
        $this->_token = $token;
        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * Set request engine to use for http requests
     *
     * @param  Engine\AbstractEngine $engine Instance of a request engine
     * @return Config
     */
    public function setRequestEngine(Engine\AbstractEngine $engine)
    {
        // set the engine
        $this->_requestEngine = $engine;
        return $this;
    }

    /**
     * Get request engine
     *
     * @return int
     */
    public function getRequestEngine()
    {
        // only initialize the storage handler when needed
        if ($this->_requestEngine === null) {
            // set the default storage handler
            $this->_requestEngine = new Engine\Curl();
        }

        return $this->_requestEngine;
    }

    /**
     * Set storage handler
     *
     * @param Storage\AbstractStorage $handler Instance of the storage handler
     * @return Config
     */
    public function setStorageHandler(Storage\AbstractStorage $handler)
    {
        // set the storage handler
        $this->_storageHandler = $handler;
        return $this;
    }

    /**
     * Get storage handler
     *
     * @return Storage\AbstractStorage
     */
    public function getStorageHandler()
    {
        // only initialize the storage handler when needed
        if ($this->_storageHandler === null) {
            // set the default storage handler
            $this->_storageHandler = new Storage\Session();
        }

        return $this->_storageHandler;
    }

}