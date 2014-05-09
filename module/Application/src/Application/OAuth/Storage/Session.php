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
namespace Application\OAuth\Storage;

use Application\OAuth\Token\AbstractToken;

/**
 * @category  Modules
 * @package   OAuth
 */
class Session extends AbstractStorage
{

    /**
     * Save the specified object in the container
     *
     * @param  AbstractToken $object Instance of the object to serialize and save
     * @param  string $key           Key under which to save the object
     * @return bool
     */
    public function save(AbstractToken $object, $key)
    {
        // start the session
        session_start();

        // save the object
        $serialized = serialize($object);
        $_SESSION[$key] = $serialized;

        return true;
    }

    /**
     * Fetch an object from the container
     *
     * @param  string $key Key under which the object is saved
     * @return AbstractToken
     */
    public function fetch($key)
    {
        // start the session
        session_start();

        if (isset($_SESSION[$key])) {
            // unserialize the object
            return unserialize($_SESSION[$key]);
        }

        return null;
    }

}