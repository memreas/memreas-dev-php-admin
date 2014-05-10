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
abstract class AbstractEngine
{

    /**
     * HTTP request method
     *
     * @var string
     */
    protected $_method = OAuth::HTTP_METHOD_POST;

    /**
     * Request URL
     *
     * @var string
     */
    protected $_url = null;

    /**
     * Request headers
     *
     * @var array
     */
    protected $_headers = array();

    /**
     * Raw post data
     *
     * @var string
     */
    protected $_rawData = null;

    /**
     * Content type used to encode the form data set for submission to the server
     *
     * @var string
     */
    protected $_enctype = null;


    /**
     * Set HTTP request method to use
     *
     * @param  int $engine Request method (OAuth::HTTP_METHOD_*)
     * @throws Exception
     * @return AbstractEngine
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
        $this->_method = strtoupper($method);

        return $this;
    }

    /**
     * Set request URL
     *
     * @param  string $url Request URL
     * @return AbstractEngine
     */
    public function setUrl($url)
    {
        $this->_url = (string) $url;
        return $this;
    }

    /**
     * Add headers
     *
     * @param  string $url Request URL
     * @return AbstractEngine
     */
    public function setHeaders($name, $value = null)
    {
        // if value is null or false, unset the header
        if ($value === null || $value === false) {
            unset($this->_headers[$name]);

        // set the header
        } else {
            $value = trim($value);
            $this->_headers[$name] = $value;
        }

        return $this;
    }

    /**
     * Set raw POST data
     *
     * @param  string $data Raw POST data
     * @return AbstractEngine
     */
    public function setRawPostData($data)
    {
        $this->_rawData = $data;
        return $this;
    }

    /**
     * Set data enctype
     *
     * @param  string $type Content type
     * @return AbstractEngine
     */
    public function setEnctype($type)
    {
        $this->_enctype = $type;
        return $this;
    }

    /**
     * Execute the call
     *
     * @return string
     */
    abstract public function exec();

}