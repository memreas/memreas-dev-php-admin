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
        //mysql --host=aa19n8yspndox3g.co0fw2snbu92.us-east-1.rds.amazonaws.com --user=memreasdbuser --password=memreas2013 memreasbackenddb
            'adapters'=>array(
                'memreasdevdb' => array(
                        'dsn'      => 'mysql:dbname=jhon;host=localhost',
                        'username' => 'root',
                        'password' => '',
                        'driver'         => 'Pdo',
                ),
                'memreasbackenddb' => array(
                        'dsn'            => 'mysql:dbname=memreasbackenddb;host=aa19n8yspndox3g.co0fw2snbu92.us-east-1.rds.amazonaws.com',
                        'username' => 'memreasdbuser',
                        'password' => 'memreas2013'
                ),
                'memreasintdb' => array(
                'driver' => 'Pdo',
                       'dsn'      => 'mysql:dbname=jhon;host=localhost',
                        'username' => 'root',
                        'password' => '',
                         
                'driver_options' => array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
                ),
            ),
/*
                'memreasdevdb' => array(
                        'dsn'      => 'mysql:dbname=memreasdevdb;host=localhost',
                        'username' => 'root',
                        'password' => 'john1016'
                ),
                'memreasbackenddb' => array(
                        'dsn'      => 'mysql:dbname=memreasbackenddb;host=localhost',
                        'username' => 'root',
                        'password' => 'john1016'
                ),
*/
            )
        ),
    'doctrine' => array(
        'connection' => array(
            'orm_default' => array(
                'params' => array(
                                        'host' => 'localhost',

                    'user'     => 'root',
                    'password' => '',
                     
                    'port' => '3306',
                    'dbname' => 'jhon',
                    
                )
            )
        )
    ),

);
