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
class Utils
{

    /**
     * Generate a random nonce
     *
     * @return string
     */
    public static function getNonce()
    {
        return md5(uniqid(rand(), true));
    }

    /**
     * Generate the current timestamp
     *
     * @return int
     */
    public static function getTimestamp()
    {
        return time();
    }

    /**
     * Encode a url
     *
     * @param  string $url URL to encode
     * @return string
     */
    public static function encodeUrl($url)
    {
        $url = rawurlencode($url);
        $url = str_replace('%7E', '~', $url);
        return $url;
    }

    /**
     * Get query parameters encoded as a query string
     *
     * @param  array $params Array of paramters
     * @return string
     */
    public static function paramsToEncodedQueryString(array $params, $customParamsOnly = false)
    {
        // remove all oauth_ params if required
        if ($customParamsOnly === true) {
            foreach ($params as $key => $value) {
                if (preg_match("/^oauth_/", $key)) {
                    unset($params[$key]);
                }
            }
        }

        $encoded = array();

        // sort params alphabetically
        uksort($params, 'strnatcmp');

        foreach ($params as $key => $value) {
            // join each key/value pair with the equals sign (=)
            $encoded[] = static::encodeUrl($key) . '%3D' . static::encodeUrl($value);
        }

        // join with the ampersand (&)
        return join('%26', $encoded);
    }

    /**
     * Get query parameters encoded for the signature base string
     *
     * @param array $params Array of paramters
     * @return string
     */
    public static function paramsToHeader(array $params, $realm = null, $excludeCustomParams = true)
    {
        $encoded = array();

        // add the realm entry
        if (!empty($realm)) {
            $encoded[] = 'realm="' . (string) $realm . '"';
        }

        foreach ($params as $key => $value) {
            if ($excludeCustomParams === true) {
                if (!preg_match("/^oauth_/", $key)) {
                    continue;
                }
            }

            // join each key/value pair with the equals sign (=)
            $encoded[] = static::encodeUrl($key) . '="' . static::encodeUrl($value) . '"';
        }

        // join all entries with the comma
        return 'OAuth ' . join(',', $encoded);
    }

}
