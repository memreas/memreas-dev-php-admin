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

use Application\OAuth\Engine,
    Application\OAuth\Storage,
    Application\OAuth\Token;

/**
 * @category  Modules
 * @package   OAuth
 */
class OAuth
{

    // signature methods
    const SIG_METHOD_HMACSHA1  = 'HMAC-SHA1';
    const SIG_METHOD_RSASHA1   = 'RSA-SHA1';
    const SIG_METHOD_PLAINTEXT = 'PLAINTEXT';

    // append the OAuth parameters to the request URI
    const AUTH_TYPE_URI = 1;

    // append the OAuth parameters to the HTTP POST request body
    const AUTH_TYPE_FORM = 2;

    // pass the OAuth parameters in the HTTP Authorization header
    const AUTH_TYPE_AUTHORIZATION = 3;

    // http methods
    const HTTP_METHOD_GET    = 'GET';
    const HTTP_METHOD_POST   = 'POST';
    const HTTP_METHOD_PUT    = 'PUT';
    const HTTP_METHOD_HEAD   = 'HEAD';
    const HTTP_METHOD_DELETE = 'DELETE';

    // out-of-band configuration callback
    const CALLBACK_OOB = 'oob';

    // oauth_ param names
    const PARAM_CONSUMER_KEY       = 'oauth_consumer_key';
    const PARAM_TOKEN              = 'oauth_token';
    const PARAM_TOKEN_SECRET       = 'oauth_token_secret';
    const PARAM_SIGNATURE          = 'oauth_signature';
    const PARAM_SIGNATURE_METHOD   = 'oauth_signature_method';
    const PARAM_TIMESTAMP          = 'oauth_timestamp';
    const PARAM_NONCE              = 'oauth_nonce';
    const PARAM_VERSION            = 'oauth_version';
    const PARAM_ASH                = 'oauth_session_handle';
    const PARAM_VERIFIER           = 'oauth_verifier';
    const PARAM_CALLBACK           = 'oauth_callback';
    const PARAM_CALLBACK_CONFIRMED = 'oauth_callback_confirmed';
    const PARAM_DENIED             = 'denied';

    // http return codes
    const HTTP_CODE_OK           = 200;
    const HTTP_CODE_BAD_REQUEST  = 400;
    const HTTP_CODE_UNAUTHORIZED = 401;

}