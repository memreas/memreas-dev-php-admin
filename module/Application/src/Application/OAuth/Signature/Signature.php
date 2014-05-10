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
class Signature
{

    /**
     * Get instance of the required signature method class
     *
     * @param  mixed $signatureMethod
     * @throws Exception
     * @return AbstractSignature
     */
    public static function getFactory($signatureMethod)
    {
        // extract parts
        @list($method, $algo) = explode('-', $signatureMethod);

        // proper-ize the method and algorythm to instantiate the class
        $method = ucfirst(strtolower($method));
        $algo = strtolower((string) $algo);

        // generate the class name to instantiate
        $classname = __NAMESPACE__ . '\\' . $method;

        // fail if method class does not exist
        if (!class_exists($classname)) {
            throw new Exception('Signature method class "' . $classname . '" not found');
        }

        // instantiate the class
        $class = new $classname($algo);

        return $class;
    }

}