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
namespace Application\OAuth\Engine;

use Application\OAuth\Exception,
    Application\OAuth\OAuth;

/**
 * @category  Modules
 * @package   OAuth
 */
class Curl extends AbstractEngine
{

    /**
     * Generate array of headers for use with cURL
     *
     * @return array
     */
    protected function _prepareHeaders()
    {
        // set Content-Length header if we have raw POST data
        if ($this->_method == OAuth::HTTP_METHOD_POST) {
            $this->setHeaders('Content-Length', strlen($this->_rawData));
        }

        // set the Content-Type header
        if (!empty($this->_enctype)) {
            $this->setHeaders('Content-Type', $this->_enctype);
        }

        // close connection after completion
        $this->setHeaders('Connection', 'close');

        // prepare the array of headers to send
        $headers = array();

        // concatenate all headers
        foreach ($this->_headers as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }

        return $headers;
    }

    /**
     * Execute the call
     *
     * @return string
     */
    public function exec()
    {
        // initialize curl
        $curl = curl_init();

        // set url to send data to
        curl_setopt($curl, CURLOPT_URL, $this->_url);

        // always return result
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // follow all redirects
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        // do not return headers
        curl_setopt($curl, CURLOPT_HEADER, false);

        // enable/disable ssl verifications
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        // determine the request options
        switch ($this->_method) {
            case OAuth::HTTP_METHOD_POST:
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $this->_rawData);
                break;

            case OAuth::HTTP_METHOD_GET:
                curl_setopt($curl, CURLOPT_POST, false);
                curl_setopt($curl, CURLOPT_HTTPGET, true);
                break;

            default:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->_method);
                break;
        }

        // set headers to send
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->_prepareHeaders());

        // execute the call
        $exec = curl_exec($curl);

        // extract the http status code
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        // return the returned data as an array if all fine or throw an exception
        switch ($statusCode) {
            case OAuth::HTTP_CODE_OK:
                $result = array();
                parse_str($exec, $result);
                return $result;

            case OAuth::HTTP_CODE_BAD_REQUEST:
                throw new Exception($exec, $statusCode);

            case OAuth::HTTP_CODE_UNAUTHORIZED:
                throw new Exception($exec, $statusCode);
        }

        return null;
    }

}