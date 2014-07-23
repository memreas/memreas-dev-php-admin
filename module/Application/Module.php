<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Session\Container;
use Zend\Session\Config\SessionConfig;
use Zend\Session\SessionManager;
use Zend\Authentication\Storage;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\DbTable as DbTableAuthAdapter;
use Zend\Stdlib\Hydrator\ObjectProperty;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Application\Model\User;
use Application\Model\UserTable;
use Application\Model\MyAuthStorage;
use Application\Model;
 
class Module {

    public function onBootstrap(MvcEvent $e) {
        $e->getApplication()->getServiceManager()->get('translator');
        $eventManager = $e->getApplication()->getEventManager();
        $serviceManager = $e->getApplication()->getServiceManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        $session = $e->getApplication()
                ->getServiceManager()
                ->get('Zend\Session\SessionManager');
        $session->start();
        $this->initAcl($e);
        $eventManager->attach('route', array($this, 'checkAcl'));
 
    }

    public function initAcl(MvcEvent $e) {
        error_log('initAcl');
        $acl = new \Zend\Permissions\Acl\Acl();
        // $roles = include __DIR__ . '/config/module.acl.roles.php';
        $roles = array(
            'guest' => array(
                'home',
                'index'
            ),
            'admin' => array(
                'admin',
                'admin/default',
            ),
        );
        $allResources = array();
        foreach ($roles as $role => $resources) {

            $role = new \Zend\Permissions\Acl\Role\GenericRole($role);
            $acl->addRole($role);

            $allResources = array_merge($resources, $allResources);

            //adding resources
            foreach ($resources as $resource) {
                // Edit 4
                if (!$acl->hasResource($resource))
                    $acl->addResource(new \Zend\Permissions\Acl\Resource\GenericResource($resource));
            }
            //adding restrictions
            foreach ($allResources as $resource) {
                $acl->allow($role, $resource);
            }
        }
        //testing
        //var_dump($acl->isAllowed('guest','admin/default'));
        //true
        //setting to view
        $e->getViewModel()->acl = $acl;
    }

    public function checkAcl(MvcEvent $e) {
        $route = $e->getRouteMatch()->getMatchedRouteName();

        //you set your role
        $userRole = 'guest';
        if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] == 1) {
            $userRole = 'admin';
        }
        error_log('checking acess ->' . $userRole . '->' . $route);
        if (!$e->getViewModel()->acl->isAllowed($userRole, $route)) {
            error_log('access deny');

            $response = $e->getResponse();
            //location to page or what ever
            /*  $response -> getHeaders() -> addHeaderLine('Location', $e -> getRequest() -> getBaseUrl() . '/');
              $response -> setStatusCode(404); */
            $response->getHeaders()->clearHeaders()->addHeaderLine('Location', '/');
            // Set the status code to redirect
            return $response->setStatusCode(302)->sendHeaders();
            // Don't forget to exit
            exit;
        }
    }

    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getViewHelperConfig() {
        return array('factories' => array(
                'mem' =>
                function($s) {
            $sm = $s->getServiceLocator();
            return new \Application\View\Helper\Mem($sm);
        }
            )
        );
    }

    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ));
    }

    public function getServiceConfig() {
        return array(
            'factories' => array(
                //ZF2 Session Setup...
                'Zend\Session\SessionManager' => function ($sm) {
            $config = $sm->get('config');
            if (isset($config['session'])) {
                $session = $config['session'];

                $sessionConfig = null;
                if (isset($session['config'])) {
                    $class = isset($session['config']['class']) ? $session['config']['class'] : 'Zend\Session\Config\SessionConfig';
                    $options = isset($session['config']['options']) ? $session['config']['options'] : array();

                    //setting this for AWS permissions error
                    //Note: must specify full path
                    $options['save_path'] = getcwd() . "/data/session/";
//error_log("save_path ---> ".$options['save_path'].PHP_EOL);

                    $sessionConfig = new $class();
                    $sessionConfig->setOptions($options);
                }

                $sessionStorage = null;
                if (isset($session['storage'])) {
                    $class = $session['storage'];
                    $sessionStorage = new $class();
                }

                $sessionSaveHandler = null;
                if (isset($session['save_handler'])) {
                    // class should be fetched from service manager since it will require constructor arguments
                    $sessionSaveHandler = $sm->get($session['save_handler']);
                }

                $sessionManager = new SessionManager($sessionConfig, $sessionStorage, $sessionSaveHandler);

                if (isset($session['validator'])) {
                    $chain = $sessionManager->getValidatorChain();
                    foreach ($session['validator'] as $validator) {
                        $validator = new $validator();
                        $chain->attach('session.validate', array($validator, 'isValid'));
                    }
                }
            } else {
                $sessionManager = new SessionManager();
            }
            Container::setDefaultManager($sessionManager);
            return $sessionManager;
        },
                'Application\Model\MyAuthStorage' => function($sm) {
            return new \Application\Model\MyAuthStorage('user');
        },
                'AuthService' => function($sm) {
            //My assumption, you've alredy set dbAdapter
            //and has users table with columns : user_name and pass_word
            //that password hashed with md5
            //$dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            //$dbTableAuthAdapter = new DbTableAuthAdapter($dbAdapter,
            //'user', 'username', 'password', 'MD5(?)');
            $AuthAdapter = new \Application\Model\MyAuthAdapter();
            $authService = new AuthenticationService();
            $authService->setAdapter($AuthAdapter);
            $authService->setStorage($sm->get('Application\Model\MyAuthStorage'));

            return $authService;
        },
                //Tables
                'Application\Model\UserTable' => function($sm) {
            $dbAdapter = $sm->get('memreasintdb');
            $resultSetPrototype = new \Zend\Db\ResultSet\HydratingResultSet();
            $resultSetPrototype->setHydrator(new ObjectProperty());
            $resultSetPrototype->setObjectPrototype(new Model\User());
            $tableGateway = new TableGateway('user', $dbAdapter, null, $resultSetPrototype);

            $table = new Model\UserTable($tableGateway);
            return $table;
        },
        'Application\Model\UserInfoTable' => function($sm) {
            $dbAdapter = $sm->get('memreasintdb');
            $resultSetPrototype = new \Zend\Db\ResultSet\HydratingResultSet();
            $resultSetPrototype->setHydrator(new ObjectProperty());
            $resultSetPrototype->setObjectPrototype(new Model\UserInfo());
            $tableGateway = new TableGateway('user_info', $dbAdapter, null, $resultSetPrototype);

            $table = new Model\UserInfoTable($tableGateway);
            return $table;
        },
                'Application\Model\EventTable' => function($sm) {
            $dbAdapter = $sm->get('memreasintdb');
            $resultSetPrototype = new \Zend\Db\ResultSet\HydratingResultSet();
            $resultSetPrototype->setHydrator(new ObjectProperty());
            $resultSetPrototype->setObjectPrototype(new Model\Event());
            $tableGateway = new TableGateway('event', $dbAdapter, null, $resultSetPrototype);

            $table = new Model\EventTable($tableGateway);
            return $table;
        },
                'Application\Model\FeedbackTable' => function($sm) {
            $dbAdapter = $sm->get('memreasintdb');
            $resultSetPrototype = new \Zend\Db\ResultSet\HydratingResultSet();
            $resultSetPrototype->setHydrator(new ObjectProperty());
            $resultSetPrototype->setObjectPrototype(new Model\Feedback());
            $tableGateway = new TableGateway('feedback', $dbAdapter, null, $resultSetPrototype);

            $table = new Model\FeedbackTable($tableGateway);
            return $table;
        },

        'Application\Model\AccountTable' => function($sm) {
            $dbAdapter = $sm->get('memreaspaymentsdb');
            $resultSetPrototype = new \Zend\Db\ResultSet\HydratingResultSet();
            $resultSetPrototype->setHydrator(new ObjectProperty());
            $resultSetPrototype->setObjectPrototype(new Model\Account());
            $tableGateway = new TableGateway('account', $dbAdapter, null, $resultSetPrototype);

            $table = new Model\AccountTable($tableGateway);
            return $table;
        },

        'Application\Model\AdminLogTable' => function($sm) {
            $dbAdapter = $sm->get('memreasintdb');
             
            $resultSetPrototype = new \Zend\Db\ResultSet\HydratingResultSet();
            $resultSetPrototype->setHydrator(new ObjectProperty());
            $resultSetPrototype->setObjectPrototype(new Model\AdminLog());
            $tableGateway = new TableGateway('admin_log', $dbAdapter, null, $resultSetPrototype);

            $table = new Model\AdminLogTable($tableGateway);
            return $table;
        },

            )
        );
    }

}
