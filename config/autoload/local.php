<?php
/**
 * Local Configuration Override
 *
 * This configuration override file is for overriding environment-specific and
 * security-sensitive configuration information. Copy this file without the
 * .dist extension at the end and populate values as needed.
 *
 * @NOTE: This file is ignored from Git by default with the .gitignore included
 * in ZendSkeletonApplication. This is a good practice, as it prevents sensitive
 * credentials from accidentally being committed into version control.
 */

return array(
    // Whether or not to enable a configuration cache.
    // If enabled, the merged configuration will be cached and used in
    // subsequent requests.
    //'config_cache_enabled' => false,
    // The key used to create the configuration cache file name.
    //'config_cache_key' => 'module_config_cache',
    // The path in which to cache merged configuration.
    //'cache_dir' =>  './data/cache',
    // ...

                'db'=> array(
                        'adapters'=>array(
                                'memreasdb' => array(
                                                'dsn'      => 'mysql:dbname=memreasintdb;host=aa1qhjij4wk4yji.co0fw2snbu92.us-east-1.rds.amazonaws.com',
                                                'username' => 'memreasdbuser',
                                                'password' => 'memreas2013'
                                ),
                                'memreaspaymentsdb' => array(
                                                'dsn'      => 'mysql:dbname=memreaspaymentsdb;host=aa1qhjij4wk4yji.co0fw2snbu92.us-east-1.rds.amazonaws.com',
                                                'username' => 'memreasdbuser',
                                                'password' => 'memreas2013'
                                ),
                                'memreasbackenddb' => array(
                                                'dsn'      => 'mysql:dbname=memreasbackenddb;host=aa1qhjij4wk4yji.co0fw2snbu92.us-east-1.rds.amazonaws.com',
                                                'username' => 'memreasdbuser',
                                                'password' => 'memreas2013'
                                ),
                        )
                ),

/*
                'db'=> array(
                        'adapters'=>array(
                                'memreasintdb' => array(
                                                'dsn'      => 'mysql:dbname=memreasintdb;host=localhost',
                                                'username' => 'root',
                                                'password' => 'john1016'
                                ),
                                'memreaspaymentsdb' => array(
                                                'dsn'           => 'mysql:dbname=memreaspaymentsdb;host=localhost',
                                                'username'      => 'root',
                                                'password'      => 'john1016'
                                ),
                                'memreasbackenddb' => array(
                                                'dsn'            => 'mysql:dbname=memreasbackenddb;host=localhost',
                                                'username' => 'root',
                                                'password' => 'john1016'
                                ),
                        )
                ),
*/

);

