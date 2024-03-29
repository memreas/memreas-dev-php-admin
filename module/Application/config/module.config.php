<?php

namespace Application;

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license http://framework.zend.com/license/new-bsd New BSD License
 */
return array (
		'session' => array (
				// 'remember_me_seconds' => 2419200, // 672 hours??
				// 'remember_me_seconds' => 5, // 30 seconds
				//'use_cookies' => true,
				//'cookie_httponly' => true,
				//'cookie_lifetime' => 0 
		) // 30 seconds
,
		'router' => array (
				'routes' => array (
						'home' => array (
								'type' => 'Zend\Mvc\Router\Http\Literal',
								'options' => array (
										'route' => '/',
										'defaults' => array (
												'controller' => 'Application\Controller\Index',
												'action' => 'index' 
										) 
								) 
						),
						'index' => array (
								'type' => 'Segment',
								'options' => array (
										'route' => '/index[/:action][/:id]',
										'constraints' => array (
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\Index',
												'action' => 'index' 
										) 
								) 
						) 
				)
				// The following is a route to simplify getting started creating
				// new controllers and actions without needing to create a new
				// module. Simply drop new controllers in, and you can access them
				// using the path /application/:controller/:action
				/*
				 * 'admin' => array(
				 * 'type' => 'Segment',
				 * 'options' => array(
				 * 'route' => '/admin',
				 * 'defaults' => array(
				 * '__NAMESPACE__' => 'Application\Controller',
				 * 'controller' => 'Index',
				 * 'action' => 'index',
				 * ),
				 * ),
				 * 'may_terminate' => true,
				 * 'child_routes' => array(
				 * 'default' => array(
				 * 'type' => 'Segment',
				 * 'options' => array(
				 * 'route' => '/[:controller][/:action][/:id][/:id2]',
				 * 'constraints' => array(
				 * 'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
				 * 'action' => '[a-zA-Z][a-zA-Z0-9_-]*'
				 * ),
				 * 'defaults' => array(
				 * //'action' => 'index',
				 * '__NAMESPACE__' => 'Application\Controller'
				 * )
				 * ),
				 *
				 * )
				 * )
				 *
				 * ),
				 */
				
				 
		),
		'service_manager' => array (
				'factories' => array (
						'translator' => 'Zend\I18n\Translator\TranslatorServiceFactory' 
				) 
		),
		'translator' => array (
				'locale' => 'en_US',
				'translation_file_patterns' => array (
						array (
								'type' => 'gettext',
								'base_dir' => __DIR__ . '/../language',
								'pattern' => '%s.mo' 
						) 
				) 
		),
		'controllers' => array (
				'factories' => array (
						'Application\Controller\Index' =>    function($cm) {
                                                $sm   = $cm->getServiceLocator();
                                                $controller = new \Application\Controller\IndexController($sm);
                                                return $controller;
                                                }
            ),
				
		),
		'view_manager' => array (
				'display_not_found_reason' => true,
				'display_exceptions' => true,
				'doctype' => 'HTML5',
				'not_found_template' => 'error/404',
				'exception_template' => 'error/index',
				'template_map' => array (
						'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
						'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
						'error/404' => __DIR__ . '/../view/error/404.phtml',
						'error/index' => __DIR__ . '/../view/error/index.phtml',
                                                'index/download-csv' => __DIR__ . '/../view/application/index/download-csv.phtml' 
				),
				'template_path_stack' => array (
						__DIR__ . '/../view' 
				),
				'strategies' => array (
						'ViewJsonStrategy' 
				),
				
		),
		'doctrine' => array (
				'driver' => array (
						'application_entities' => array (
								'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
								'cache' => 'array',
								'paths' => array (
										__DIR__ . '/../src/Application/Entity' 
								) 
						),
						'orm_default' => array (
								
								'drivers' => array (
										'Application\Entity' => 'application_entities' 
								) 
						) 
				) 
		)
);
