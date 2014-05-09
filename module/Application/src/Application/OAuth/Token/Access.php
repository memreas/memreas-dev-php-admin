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
namespace Application\OAuth\Token;

use Application\OAuth\Config,
    Application\OAuth\Engine\Engine,
    Application\OAuth\OAuth,
    Application\OAuth\Signature\Signature,
    Application\OAuth\Token\Authorize as AuthorizeToken,
    Application\OAuth\Token\Request as RequestToken,
    Application\OAuth\Utils;

/**
 * @category  Modules
 * @package   OAuth
 */
class Access extends AbstractToken
{

    /**
     * Request configuration
     *
     * @var Config
     */
    protected $_config;

    /**
     * Authorize configuration
     *
     * @var AuthorizeToken
     */
    protected $_authorizeToken;


    /**
     * Class constructor
     *
     * @param  Config $config                 Request configuration
     * @param  AuthorizeToken $authorizeToken Previously fetched authorize token
     * @param  bool $autofetch                TRUE to auto-fetch the response
     * @return Access
     */
    public function __construct(Config $config, AuthorizeToken $authorizeToken, $autofetch = false)
    {
        $this->setConfig($config)
             ->setAuthorizeToken($authorizeToken);

        if ($autofetch === true) {
            $this->fetchResponse();
        }
    }

    /**
     * Set configuration
     *
     * @param  Config $config Consumer configuration
     * @return Access
     */
    public function setConfig(Config $config)
    {
        $this->_config = $config;
        return $this;
    }

    /**
     * Get consumer key
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Set authorize token
     *
     * @param  AuthorizeToken $authorizeToken Authorize token
     * @return Access
     */
    public function setAuthorizeToken(AuthorizeToken $authorizeToken)
    {
        $this->_authorizeToken = $authorizeToken;
        return $this;
    }

    /**
     * Get authorize token
     *
     * @return AuthorizeToken
     */
    public function getAuthorizeToken()
    {
        return $this->_authorizeToken;
    }

    /**
     * Assemble parameters required for the request
     *
     * @return array
     */
    protected function _assembleParams()
    {
        $config = $this->getConfig();

        // prepare the params array
        $params = array(
            OAuth::PARAM_CONSUMER_KEY      => $config->getConsumerKey(),
            OAuth::PARAM_TOKEN             => $this->getAuthorizeToken()->getToken(),
            OAuth::PARAM_SIGNATURE_METHOD  => $config->getSignatureMethod(),
            OAuth::PARAM_TIMESTAMP         => Utils::getTimestamp(),
            OAuth::PARAM_NONCE             => Utils::getNonce(),
            OAuth::PARAM_VERSION           => $config->getVersion(),
            OAuth::PARAM_VERIFIER          => $this->getAuthorizeToken()->getVerifier(),
        );

        // get instance of the signature
        $factory = Signature::getFactory($config->getSignatureMethod());
        $factory->setConsumerSecret($config->getConsumerSecret());

        // get the actual signature
        $signature = $factory->getSignature($config->getAccessTokenUrl(), $params, $config->getRequestMethod());
        $params[OAuth::PARAM_SIGNATURE] = $signature;

        return $params;
    }

    /**
     * Fetch access token from the provider
     *
     * @return array
     */
    public function fetchResponse()
    {
        // prepare variables
        $params = $this->_assembleParams();
        $config = $this->getConfig();

        // instantiate the request engine
        $engine = $config->getRequestEngine()
                         ->setUrl($config->getAccessTokenUrl())
                         ->setHeaders('Authorization', Utils::paramsToHeader($params))
                         ->setRequestMethod($config->getRequestMethod())
                         ->setEnctype('application/x-www-form-urlencoded');

        // set post data
        $customData = Utils::paramsToEncodedQueryString($params, true);

        // asign custom data if not empty
        if (!empty($customData)) {
            // set requests post body
            $engine->setRawPostData($customData);
        }

        // execute the call
        $this->_data = $engine->exec();

        return $this->_data;
    }

    /**
     * Return TRUE if the response from the vendor is successful
     *
     * @return bool
     */
    public function isValid()
    {
        return !empty($this->_data[OAuth::PARAM_TOKEN])
            && !empty($this->_data[OAuth::PARAM_TOKEN_SECRET]);
    }

    /**
     * Get the oauth_token value
     *
     * @return string
     */
    public function getToken()
    {
        if ($this->isValid()) {
            return $this->_data[OAuth::PARAM_TOKEN];
        }

        return null;
    }

    /**
     * Get the oauth_token_secret value
     *
     * @return string
     */
    public function getTokenSecret()
    {
        if ($this->isValid()) {
            return $this->_data[OAuth::PARAM_TOKEN_SECRET];
        }

        return null;
    }

}