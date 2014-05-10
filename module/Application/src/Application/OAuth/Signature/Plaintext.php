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

/**
 * @category  Modules
 * @package   OAuth
 */
class Plaintext extends AbstractSignature
{

    /**
     * Get OAuth signature for the specified data
     *
     * @param  string $url        Url of the request
     * @param  array $params      Array of query parameters
     * @param  string $httpMethod Http request method
     * @throws Exception
     * @return string
     */
    public function getSignature($url, array $params, $httpMethod)
    {
        // get the unencoded signature
        return $this->_getSignatureBaseString($url, $params, $httpMethod);
    }

}