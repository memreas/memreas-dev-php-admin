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

use Application\OAuth\Exception;

/**
 * @category  Modules
 * @package   OAuth
 */
class Hmac extends AbstractSignature
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
        // get the signature base string
        $signatureBase = $this->_getSignatureBaseString($url, $params, $httpMethod);

        // verify that the hash algorythm is supported
        $algoSupported = false;

        if (function_exists('hash_algos') && in_array($this->_hashAlgo, hash_algos())) {
            $algoSupported = true;
        }

        // fail if hash algorythm is not supoorted
        if ($algoSupported === false) {
            throw new Exception('Hash algorythm ' . $this->_hashAlgo . ' is not supported on your PHP installation');
        }

        // get the binary signature
        $signature = hash_hmac($this->_hashAlgo, $signatureBase, $this->_getKey(), true);

        return base64_encode($signature);
    }

}