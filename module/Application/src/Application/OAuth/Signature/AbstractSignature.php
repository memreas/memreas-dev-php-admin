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
namespace Application\OAuth\Signature;

use Application\OAuth\Exception,
    Application\OAuth\Utils;

/**
 * @category  Modules
 * @package   OAuth
 */
abstract class AbstractSignature
{

    /**
     * Consumer secret
     *
     * @var string
     */
    protected $_consumerSecret = null;

    /**
     * Token secret
     *
     * @var string
     */
    protected $_tokenSecret = null;

    /**
     * Hash algorythm
     *
     * @var string
     */
    protected $_hashAlgo = null;


    /**
     * Class constructor
     *
     * @param  string $consumerSecret Consumer secret
     * @param  string $tokenSecret    Token secret
     * @param  string $hashAlgo       Hashing algorythm
     * @return AbstractSignature
     */
    public function __construct($hashAlgo = null)
    {
        // set the hasing algorythm
        if (!empty($hashAlgo)) {
            $this->_hashAlgo = strtolower($hashAlgo);
        }
    }

    /**
     * Set consumer secret
     *
     * @param  string $secret Consumer secret
     * @throws Exception
     * @return AbstractSignature
     */
    public function setConsumerSecret($secret)
    {
        // fail if the consumer secret is not a string
        if (!is_string($secret)) {
            throw new Exception('Consumer secret must be a string');
        }

        // set the consumer secret
        $this->_consumerSecret = (string) $secret;

        return $this;
    }

    /**
     * Set token secret
     *
     * @param  string $secret Token secret
     * @throws Exception
     * @return AbstractSignature
     */
    public function setTokenSecret($secret)
    {
        // assign the token secret if it's not empty
        if (!empty($secret)) {
            // fail if token secret is not a string
            if (!is_string($secret)) {
                throw new Exception('Token secret must be a string');
            }

            // set the token secret
            $this->_tokenSecret = (string) $secret;
        }

        return $this;
    }

    /**
     * Assemble key from consumer and token secrets
     *
     * @throws Exception
     * @return string
     */
    protected function _getKey()
    {
        // fail if consumer secret hasn't been set
        if (empty($this->_consumerSecret)) {
            throw new Exception('Consumer secret has not been set');
        }

        // start with having consumer secret as the first part of the string
        $parts = array($this->_consumerSecret);

        // add the token secret
        $parts[] = (string) $this->_tokenSecret;

        // url-encode all parts
        foreach ($parts as $key => $value) {
            $parts[$key] = Utils::encodeUrl($value);
        }

        return join('&', $parts);
    }

    /**
     * Get signature base string for the specified parts
     *
     * @param  string $url        Url
     * @param  array $params      Array of query parameters
     * @param  string $httpMethod Http request method
     * @return string
     */
    protected function _getSignatureBaseString($url, array $params, $httpMethod)
    {
        // sort params alphabetically
        uksort($params, 'strnatcmp');

        $encoded = array();

        // url-encode all keys and values
        foreach ($params as $key => $value) {
            $key = Utils::encodeUrl($key);
            $value = Utils::encodeUrl($value);
            $encoded[$key] = $value;
        }

        // prepare the signature base string parts' array
        $data = array(
            strtoupper($httpMethod),
            Utils::encodeUrl($url),
            Utils::paramsToEncodedQueryString($encoded),
        );

        // join all entries with the ampersand
        return join('&', $data);
    }

    /**
     * Get OAuth signature for the specified data
     *
     * @param  string $url        Url of the request
     * @param  array $params      Array of query parameters
     * @param  string $httpMethod Http request method
     * @throws Exception
     * @return string
     */
    abstract public function getSignature($url, array $params, $httpMethod);

}