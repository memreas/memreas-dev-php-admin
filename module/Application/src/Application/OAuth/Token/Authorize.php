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

use Application\OAuth\OAuth;

/**
 * @category  Modules
 * @package   OAuth
 */
class Authorize extends AbstractToken
{

    /**
     * List of GET parameters from the response
     *
     * @var array
     */
    protected $_data = array();


    /**
     * Class constructor
     *
     * @param  array $params List of GET parameters
     * @return Authorize
     */
    public function __construct(array $params)
    {
        $this->_data = $params;
    }

    /**
     * Verify if the response from the vendor was successful
     *
     * @return bool
     */
    public function isValid()
    {
        return !empty($this->_data[OAuth::PARAM_TOKEN])
            && !empty($this->_data[OAuth::PARAM_VERIFIER]);
    }

    /**
     * Check if the request has been denied
     *
     * @return bool
     */
    public function isDenied()
    {
        return isset($this->_data[OAuth::PARAM_DENIED]);
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
     * Get the oauth_verifier value
     *
     * @return string
     */
    public function getVerifier()
    {
        if ($this->isValid()) {
            return $this->_data[OAuth::PARAM_VERIFIER];
        }

        return null;
    }

    /**
     * Get custom parameters
     *
     * @return array
     */
    public function getCustomParams()
    {
        if ($this->isValid()) {
            $mask = array(
                OAuth::PARAM_TOKEN   => null,
                OAuth::PARAM_VERIFIER => null,
            );
        } else {
            $mask = array(
                OAuth::PARAM_DENIED => null,
            );
        }

        return array_diff_key($this->_data, $mask);
    }

}