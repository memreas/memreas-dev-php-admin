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

use Application\OAuth\Exception;

/**
 * @category  Modules
 * @package   OAuth
 */
abstract class AbstractToken implements \ArrayAccess
{

    /**
     * Result received from the vendor
     *
     * @var array
     */
    protected $_data = array();


    /**
     * Set value of an offset
     * Required by \ArrayAccess
     *
     * @param  mixed $offset Offset
     * @param  mixed $value  Value to set the offset to
     * @throws Exception
     * @return void
     */
    final public function offsetSet($offset, $value)
    {
        throw new Exception('Setting values on the token is forbidden');
    }

    /**
     * Get value of an offset
     * Required by \ArrayAccess
     *
     * @param  mixed $offset Offset to retrieve
     * @return mixed
     */
    final public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->_data[$offset];
        }

        return null;
    }

    /**
     * Check whether an offset exists
     * Required by \ArrayAccess
     *
     * @param  mixed $offset Offset
     * @return bool
     */
    final public function offsetExists($offset)
    {
        // make sure offset is a string
        $offset = (string) $offset;
        return isset($this->_data[$offset]);
    }

    /**
     * Unset an offset
     * Required by \ArrayAccess
     *
     * @param  mixed $offset Offset to unset
     * @throws Exception
     * @return void
     */
    final public function offsetUnset($offset)
    {
        throw new Exception('Unsetting keys on the token is forbidden');
    }

    /**
     * Get the token as an array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->_data;
    }

}