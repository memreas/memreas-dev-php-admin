<?php
/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

return array(
     'db'=> array(
    'adapters'=>array(
        'memreasintdb' => array(
            'driver'         => 'Pdo',
            'dsn'            => 'mysql:dbname=memreaspaymentsdb;host=memreasdev-db.co0fw2snbu92.us-east-1.rds.amazonaws.com',
            'username' => 'memreas2013',
            'password' => ''
        ),
        'memreaspaymentsdb' => array(
            'driver'         => 'Pdo',
            'dsn'            => 'mysql:dbname=memreaspaymentsdb;host=memreasdev-db.co0fw2snbu92.us-east-1.rds.amazonaws.com',
            'username' => 'memreas2013',
            'password' => ''
        ),
        'memreasadmindb' => array(
            'driver'         => 'Pdo',
            'dsn'            => 'mysql:dbname=memreasadmin;host=memreasdev-db.co0fw2snbu92.us-east-1.rds.amazonaws.com',
            'username' => 'memreasdbuser',
            'password' => 'memreas2013'
        ),
    )
),
    'service_manager' => array(
        'abstract_factories' => array(
            'Zend\Db\Adapter\AdapterAbstractServiceFactory',
    ),

        'factories' => array(
            'Zend\Db\Adapter\Adapter'
                    => 'Zend\Db\Adapter\AdapterServiceFactory',
        ),
    ),
    'session' => array(
        'config' => array(
            'class' => 'Zend\Session\Config\SessionConfig',
            'options' => array(
                'name' => 'myapp',
            ),
        ),
        'storage' => 'Zend\Session\Storage\SessionArrayStorage',
        'validators' => array(
            array(
                'Zend\Session\Validator\RemoteAddr',
                'Zend\Session\Validator\HttpUserAgent',
            ),
        ),
    ),
     'doctrine' => array(
        'connection' => array(
            'orm_default' => array(
                'doctrine_type_mappings' => array(
                                                'enum' => 'string',
                                                    'bit' => 'string'),
                'driverClass' => 'Doctrine\DBAL\Driver\PDOMySql\Driver',
                'params' => array(
                    'host' => 'memreasdev-db.co0fw2snbu92.us-east-1.rds.amazonaws.com',
                    //'host' => 'localhost',
                    'port' => '3306',
                    'dbname' => 'memreasintdb',
                    'user'     => 'memreasdbuser',
					'password' => 'memreas2013',)
            ),
        )

    ),

);
