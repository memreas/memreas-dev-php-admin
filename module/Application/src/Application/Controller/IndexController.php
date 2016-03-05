<?php

/**
 * Copyright (C) 2015 memreas llc. - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 */
namespace Application\Controller;

use Application\memreas\AWSManagerSender;
use Application\memreas\AWSMemreasRedisCache;
use Application\memreas\AWSMemreasAdminRedisSessionHandler;
use Application\memreas\Mlog;
use Application\memreas\MUUID;
use Application\memreas\User;
use Application\Model;
use Application\Model\MemreasConstants;
use Application\Model\UserTable;
use Application\View\Helper\S3;
use Application\View\Helper\S3Service;
use GuzzleHttp\Client;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Paginator\Paginator;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController {
	
	// Updated....
	protected $url = MemreasConstants::MEMREAS_WS;
	public $sid = '';
	protected $user_id;
	protected $storage;
	protected $authservice;
	protected $userTable;
	protected $eventTable;
	protected $mediaTable;
	protected $friendmediaTable;
	public $messages = array ();
	public $status;
	protected $userinfoTable;
	protected $adminLogTable;
	protected $sessHandler;
	protected $redis;
	
	//
	// start session by fetching and starting from REDIS - security check
	//
	public function setupSaveHandler() {
		// Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		// start capture
		ob_start ();
		
		$this->redis = new AWSMemreasRedisCache ( $this->getServiceLocator () );
		$this->sessHandler = new AWSMemreasAdminRedisSessionHandler ( $this->redis, $this->getServiceLocator () );
		session_set_save_handler ( $this->sessHandler );
		
		// clean the buffer we don't need to send back session data
		ob_end_clean ();
	}
	public function fetchSession() {
		$cm = __CLASS__ . __METHOD__;
		// Mlog::addone ( $cm . '$_POST', $_POST );
		// Mlog::addone ( $cm . '$_GET', $_GET );
		
		// Mlog::addone ( $cm . '$_COOKIE', $_COOKIE );
		/**
		 * Setup save handler and start session
		 */
		$hasSession = false;
		header ( 'Access-Control-Allow-Origin: *' );
		$this->setupSaveHandler ();
		try {
			if (! empty ( $_REQUEST ['sid'] )) {
				$sid = $_REQUEST ['sid'];
				// Mlog::addone ( __CLASS__ . __METHOD__ . __LINE__ . '::fetching redis session for $_COOKIE [memreascookie]->', $_COOKIE ['memreascookie'] );
				$this->sessHandler->startSessionWithSID ( $sid );
				$hasSession = true;
				// Mlog::addone ( __CLASS__ . __METHOD__ . __LINE__ . '::Redis Session found->', $_SESSION );
			} else if (! empty ( $_COOKIE ['memreascookie'] )) {
				// Mlog::addone ( __CLASS__ . __METHOD__ . __LINE__ . '::fetching redis session for $_COOKIE [memreascookie]->', $_COOKIE ['memreascookie'] );
				$hasSession = $this->sessHandler->startSessionWithMemreasCookie ( $_COOKIE ['memreascookie'] );
				$hasSession = true;
				// Mlog::addone ( __CLASS__ . __METHOD__ . __LINE__ . '::Redis Session found->', $_SESSION );
			}
		} catch ( \Exception $e ) {
			// Mlog::addone ( __CLASS__ . __METHOD__ . __LINE__ . '::Redis Session lookup error->', $e->getMessage () );
			$hasSession = false;
		}
		
		//
		// If session is valid on wsj we can proceed
		//
		if ($hasSession) {
			$this->security ();
			return $hasSession;
		}
		
		$this->logoutAction ();
		return $hasSession;
	}
	public function xml2array($xmlstring) {
		$xml = simplexml_load_string ( $xmlstring );
		$json = json_encode ( $xml );
		$arr = json_decode ( $json, TRUE );
		
		return $arr;
	}
	public function array2xml($array, $xml = false) {
		if ($xml === false) {
			$xml = new \SimpleXMLElement ( '<?xml version=\'1.0\' encoding=\'utf-8\'?><' . key ( $array ) . '/>' );
			$array = $array [key ( $array )];
		}
		foreach ( $array as $key => $value ) {
			if (is_array ( $value )) {
				array2xml ( $value, $xml->addChild ( $key ) );
			} else {
				$xml->addChild ( $key, $value );
			}
		}
		return $xml->asXML ();
	}
	public function fetchXML($action, $xml) {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
                                
		$guzzle = new \GuzzleHttp\Client ();
		if (empty ( $_SESSION ['sid'] )) {
			Mlog::addone ( __CLASS__ . __METHOD__ . __LINE__, "::guzzle::action:: $action ::xml::$xml" );
			$response = $guzzle->post ( $this->url, [ 
					'form_params' => [ 
							'action' => $action,
							'xml' => $xml 
					] 
			] );
		} else {
                    $admin_key = MUUID::fetchUUID();
		    
		    $this->redis->setCache('admin_key', $admin_key, MemreasConstants::REDIS_CACHE_USER_TTL);
                    //$admin_key = $this->redis->getCache('admin_key');
			Mlog::addone ( __CLASS__ . __METHOD__ . __LINE__, "::guzzle::action:: $action ::xml::$xml sid::" . $_SESSION ['sid']."admin:key".$admin_key );
                        
			$response = $guzzle->request ( 'POST', $this->url, [ 
					'form_params' => [ 
                                            
							'action' => $action,
							'xml' => $xml,
							'sid' => empty ( $_SESSION ['sid'] ) ? '' : $_SESSION ['sid'] ,
                                                         'admin_key' => $admin_key 
					] 
			] );
		}
                                
		return $response->getBody ();
	}
	public function getAdminLogTable() {
		if (! $this->adminLogTable) {
			$sm = $this->getServiceLocator ();
			$this->adminLogTable = $sm->get ( 'Application\Model\AdminLogTable' );
		}
		return $this->adminLogTable;
	}
	protected $feedbackTable;
	public function getFeedbackTable() {
		if (! $this->feedbackTable) {
			$sm = $this->getServiceLocator ();
			$this->feedbackTable = $sm->get ( 'Application\Model\FeedbackTable' );
		}
		return $this->feedbackTable;
	}
	public function getUserInfoTable() {
		if (! $this->userinfoTable) {
			$sm = $this->getServiceLocator ();
			$this->userinfoTable = $sm->get ( 'Application\Model\UserInfoTable' );
		}
		return $this->userinfoTable;
	}
	public function indexAction() {
		// Mlog::addone ( __CLASS__ . __METHOD__ . __LINE__, 'Enter indexAction' );
		$path = "application/index/index.phtml";
		$view = new ViewModel ();
		$view->setTemplate ( $path ); // path to phtml file under view folder
		                              // Mlog::addone ( __CLASS__ . __METHOD__ . __LINE__, 'returning $path ' . $path );
		
		return $view;
	}
	public function ApiServerSideAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			if (isset ( $_REQUEST ['callback'] )) {
				// Fetch parms
				$callback = $_REQUEST ['callback'];
				$json = $_REQUEST ['json'];
				$message_data = json_decode ( $json, true );
				// Setup the URL and action
				$ws_action = $message_data ['ws_action'];
				$type = $message_data ['type'];
				$xml = $message_data ['json'];
				
				// Guzzle the LoginWeb Service
				$result = $this->fetchXML ( $ws_action, $xml );
                                 
				// Return the ajax call...
				$callback_json = $callback . "(" . $result . ")";
                                error_log('tag----'.print_r($result,TRUE));
				$output = ob_get_clean ();
				header ( "Content-type: plain/text" );
				echo $callback_json;
				// Need to exit here to avoid ZF2 framework view.
			}
		}
		exit ();
	}
	public function s3uploadAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			$S3Service = new S3Service ();
			$session = new Container ( 'user' );
			$data ['bucket'] = 'memreasdev';
			$data ['folder'] = $_SESSION ['user_id'] . '/image/';
			$data ['user_id'] = $_SESSION ['user_id'];
			$data ['ACCESS_KEY'] = $S3Service::getAccessKey ();
			list ( $data ['policy'], $data ['signature'] ) = $S3Service::get_policy_and_signature ( array (
					'bucket' => $data ['bucket'],
					'folder' => $data ['folder'] 
			) );
			$view = new ViewModel ( array (
					'data' => $data 
			) );
			$path = $this->security ( "application/index/s3upload.phtml" );
			$view->setTemplate ( $path );
			return $view;
		}
	}
	public function addmediaAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			$session = new Container ( 'user' );
			$s3 = new S3 ( MemreasConstants::S3_APPKEY, MemreasConstants::S3_APPSEC );
			$target_path = '/' . $_SESSION ['user_id'] . '/image/' . $_FILES ['upl'] ['name'];
			$s3->putBucket ( 'memreasdev', S3::ACL_PUBLIC_READ );
			echo '{"status":"success"}';
			/*
			 * if ($s3->putObjectFile($_FILES['upl']['tmp_name'], 'memreasdev', $target_path, S3::ACL_PUBLIC_READ, array(), 'image/jpeg')){
			 * $ws_action = "addmediaevent";
			 * $xml = "<xml><addmediaevent><s3url>http://s3.amazonaws.com/memreasdev/" . $_SESSION['user_id'] . '/image/' . $_FILES['upl']['name'] . "</s3url><is_server_image>0</is_server_image><content_type>" . $_FILES['upl']['type'] . "</content_type><s3file_name>" . $_FILES['upl']['name'] . "</s3file_name><device_id></device_id><event_id></event_id><media_id></media_id><user_id>" . $_SESSION['user_id'] . "</user_id><is_profile_pic>0</is_profile_pic><location></location></addmediaevent></xml>";
			 * $result = $this->fetchXML($ws_action, $xml);
			 * echo '{"status":"success"}';
			 * }
			 * else echo '{"status":"error"}';
			 */
			die ();
		}
	}
	public function loginAction() {
		// Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		// Fetch the post data
		$request = $this->getRequest ();
		
		if ($request->isPost ()) {
			
			$postData = $request->getPost ()->toArray ();
			$username = $postData ['username'];
			$password = $postData ['password'];
			
			if ($this->setSession ( $username, $password )) {
				return $this->redirect ()->toRoute ( 'index', array (
						'controller' => 'index',
						'action' => 'manage' 
				) );
			}
		}
		return $this->redirect ()->toRoute ( 'index', array (
				'action' => "index" 
		) );
	}
	public function logoutAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		error_log ( 'IndexController -> logout->exec()...' . PHP_EOL );
		try {
			if (! empty ( $_SESSION ['sid'] )) {
				$result = $this->sessHandler->closeSessionWithSID ();
				Mlog::addone ( 'logout sid result ', $result );
			} else {
				$result = $this->sessHandler->closeSessionWithMemreasCookie ();
				Mlog::addone ( 'logout cookie result ', $result );
			}
			;
		} catch ( \Exception $e ) {
			error_log ( 'Caught exception: ' . $e->getMessage () . PHP_EOL );
		}
		Mlog::addone ( 'redirecting to index ', '..' );
		return $this->redirect ()->toRoute ( 'index', array (
				'action' => "index" 
		) );
	}
	public function setSession($username, $password) {
		// Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		// Fetch the user's data and store it in the session...
		// error_log ( "Inside setSession ..." );
		$user = $this->getAminUserTable ()->fetchAll ( array (
				'username' => $username,
				'password' => md5 ( $password ) 
		) );
		$user = $user->current ();
		if (empty ( $user->user_id ) || $user->role == 2) {
			return false;
		}
		$user->password = '';
		$user->disable_account = '';
		$user->create_date = '';
		$user->update_time = '';
		$ipAddress = $this->fetchUserIPAddress ();
		
		/**
		 * setup savehandler
		 */
		$this->setupSaveHandler ();
		$this->sessHandler->setSession ( $user, $ipAddress, 'web', $_COOKIE ['memreascookie'], $ipAddress );
		return true;
	}
	public function fetchUserIPAddress() {
		// Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		/*
		 * Fetch the user's ip address
		 */
		$ipAddress = $this->getServiceLocator ()->get ( 'Request' )->getServer ( 'REMOTE_ADDR' );
		if (! empty ( $_SERVER ['HTTP_CLIENT_IP'] )) {
			$ipAddress = $_SERVER ['HTTP_CLIENT_IP'];
		} else if (! empty ( $_SERVER ['HTTP_X_FORWARDED_FOR'] )) {
			$ipAddress = $_SERVER ['HTTP_X_FORWARDED_FOR'];
		} else {
			$ipAddress = $_SERVER ['REMOTE_ADDR'];
		}
		// error_log ( 'ip is ' . $ipAddress );
		
		return $ipAddress;
	}
	public function registrationAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			// Fetch the post data
			$postData = $this->getRequest ()->getPost ()->toArray ();
			$email = $postData ['email'];
			$username = $postData ['username'];
			$password = $postData ['password'];
			$invited_by = $postData ['invited_by'];
			// Setup the URL and action
			$action = 'registration';
			$xml = "<xml><registration><email>$email</email><username>$username</username><password>$password</password><invited_by>$invited_by</invited_by></registration></xml>";
			$redirect = 'event';
			
			// Guzzle the Registration Web Service
			$result = $this->fetchXML ( $action, $xml );
			
			$data = simplexml_load_string ( $result );
			
			// ZF2 Authenticate
			if ($data->registrationresponse->status == 'success') {
				$this->setSession ( $username );
				
				// If there's a profile pic upload it...
				if (isset ( $_FILES ['file'] )) {
					$file = $_FILES ['file'];
					$fileName = $file ['name'];
					$filetype = $file ['type'];
					$filetmp_name = $file ['tmp_name'];
					$filesize = $file ['size'];
					
					$url = MemreasConstants::MEMREAS_WS;
					$guzzle = new Client ();
					$request = $guzzle->post ( $url )->addPostFields ( array (
							'action' => 'addmediaevent',
							'user_id' => $_SESSION ['user_id'],
							'filename' => $fileName,
							'event_id' => "",
							'device_id' => "",
							'is_profile_pic' => 1,
							'is_server_image' => 0 
					) )->addPostFiles ( array (
							'f' => $filetmp_name 
					) );
				}
				$response = $request->send ();
				$data = $response->getBody ( true );
				$xml = simplexml_load_string ( $result );
				if ($xml->addmediaeventresponse->status == 'success') {
					// Do nothing even if it fails...
				}
				
				// Redirect here
				return $this->redirect ()->toRoute ( 'index', array (
						'action' => $redirect 
				) );
			} else {
				return $this->redirect ()->toRoute ( 'index', array (
						'action' => "index" 
				) );
			}
		}
	}
	public function getUserTable() {
		if (! $this->userTable) {
			$sm = $this->getServiceLocator ();
			$this->userTable = $sm->get ( 'Application\Model\UserTable' );
			;
		}
		return $this->userTable;
	}
	public function getAminUserTable() {
		if (! $this->userTable) {
			$sm = $this->getServiceLocator ();
			$this->userTable = $sm->get ( 'Application\Model\AdminUserTable' );
			;
		}
		return $this->userTable;
	}
	public function forgotpasswordAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			$request = $this->getRequest ();
			$postData = $request->getPost ()->toArray ();
			$email = isset ( $postData ['email'] ) ? $postData ['email'] : '';
			// Setup the URL and action
			$action = 'forgotpassword';
			$xml = "<xml><forgotpassword><email>$email</email></forgotpassword></xml>";
			// $redirect = 'gallery';
			// Guzzle the LoginWeb Service
			$result = $this->fetchXML ( $action, $xml );
			
			$data = simplexml_load_string ( $result );
			echo json_encode ( $data );
			return '';
		}
	}
	public function changepasswordAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			$request = $this->getRequest ();
			$postData = $request->getPost ()->toArray ();
			
			$new = isset ( $postData ['new'] ) ? $postData ['new'] : '';
			$retype = isset ( $postData ['reytpe'] ) ? $postData ['reytpe'] : '';
			$token = isset ( $postData ['token'] ) ? $postData ['token'] : '';
			
			// Setup the URL and action
			$action = 'forgotpassword';
			$xml = "<xml><changepassword><new>$new</new><retype>$retype</retype><token>$token</token></changepassword></xml>";
			// $redirect = 'gallery';
			// Guzzle the LoginWeb Service
			$result = $this->fetchXML ( $action, $xml );
			
			$data = simplexml_load_string ( $result );
			echo json_encode ( $data );
			return '';
		}
	}
	public function showlogAction() {
		// Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		echo '<pre>' . file_get_contents ( getcwd () . '/php_errors.log' );
		exit ();
	}
	public function clearlogAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		unlink ( getcwd () . '/php_errors.log' );
		error_log ( "Log has been cleared!" );
		echo '<pre>' . file_get_contents ( getcwd () . '/php_errors.log' );
		exit ();
	}
	public function manageAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			error_log ( "Enter manageAction " . __FUNCTION__ . PHP_EOL );
			// $path = $this->security("application/index/index.phtml");
			$path = "application/manage/index.phtml";
			$view = new ViewModel ();
			$view->setTemplate ( $path ); // path to phtml file under view folder
			error_log ( "Exit manageAction " . __FUNCTION__ . PHP_EOL );
			return $view;
		}
	}
	public function userAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			$order_by = $this->params ()->fromQuery ( 'order_by', 0 );
			$order = $this->params ()->fromQuery ( 'order', 'DESC' );
			$page = $this->params ()->fromQuery ( 'page', 1 );
			
			$q = $this->getUserName ();
			$where = '';
			if ($q) {
				$where = new \Zend\Db\Sql\Where ();
				$where->like ( 'username', "$q%" );
			}
			
			$column = array (
					'username',
					'email_address',
					'role',
					'disable_account' 
			);
			$url_order = 'DESC';
			if (in_array ( $order_by, $column ))
				$url_order = $order == 'DESC' ? 'ASC' : 'DESC';
			
			try {
				$users = $this->getUserTable ()->fetchAll ( $where, $order_by, $order );
				
				$iteratorAdapter = new \Zend\Paginator\Adapter\Iterator ( $users );
				$paginator = new Paginator ( $iteratorAdapter );
				$paginator->setCurrentPageNumber ( $page );
				// $paginator->setItemCountPerPage(ADMIN_QUERY_LIMIT);
				$paginator->setItemCountPerPage ( MemreasConstants::NUMBER_OF_ROWS );
			} catch ( Exception $exc ) {
				
				return array ();
			}
			return array (
					'paginator' => $paginator,
					'user_total' => count ( $users ),
					'order_by' => $order_by,
					'order' => $order,
					'q' => $q,
					'page' => $page,
					'url_order' => $url_order 
			);
		}
	}
	public function userViewAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			if ($this->request->isPost ()) {
				$id = $this->params ()->fromPost ( 'id' );
				$user = $this->getUserTable ()->getUser ( $id );
				if (empty ( $id ) or empty ( $user )) {
					$this->messages [] = 'User Not Found';
				} else if ($this->validate ()) {
					$postData = $this->params ()->fromPost ();
					$user->username = $postData ['username'];
					$user->email_address = $postData ['email_address'];
					// $user->facebook_username = $postData['facebook_username'];
					// $user->twitter_username = $postData['twitter_username'];
					$user->disable_account = $postData ['disable_account'];
					
					// Save the changes
					
					// $this->getUserTable()->saveUser($user);
					$this->getAdminLogTable ()->saveLog ( array (
							'log_type' => 'user_update',
							'admin_id' => $_SESSION ['user_id'],
							'entity_id' => $id 
					) );
					
					$this->messages [] = 'Data Update sucessfully';
					$user = $this->getUserTable ()->getUser ( $id );
				}
			} else {
				$id = $this->params ()->fromRoute ( 'id' );
				// $user = $this->getUserTable()->getUser($id);
				$user = $this->getUserTable ()->getUserData ( array (
						'user.user_id' => $id 
				) );
				// echo '<pre>';print_r($userProfile);
			}
			
			$view = new ViewModel ();
			$view->setVariable ( 'user', $user );
			$view->setVariable ( 'messages', $this->messages );
			$view->setVariable ( 'status', $this->status );
			
			return $view;
		}
	}
	function validate() {
		$result = true;
		return $result;
	}
	public function userDeactiveAction() {
		if ($this->fetchSession ()) {
			Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ . $_SESSION ['user_id'] );
			
			$vdata = array ();
			$request = $this->getRequest ();
			if ($request->isPost ()) {
				
				$id = $this->params ()->fromPost ( 'id' );
				$postdata = $this->params ()->fromPost ();
				
				if (empty ( $postdata ['reason'] )) {
					$this->status = 'error';
					$this->messages [] = 'Provide reason';
				} elseif ($postdata ['reason'] == 'other' && empty ( $postdata ['other_reason'] )) {
					$this->status = 'error';
					$this->messages [] = 'Provide reason';
				} 

				else {
					$description = $postdata ['reason'];
					if ($postdata ['reason'] == 'other') {
						$description = $postdata ['other_reason'];
					}
					Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ . $_SESSION ['user_id'] . $description );
					
					$this->getUserTable ()->updateUser ( array (
							'disable_account' => '1' 
					), $id );
					$this->getAdminLogTable ()->saveLog ( array (
							'log_type' => 'user_deactivated',
							'admin_id' => $_SESSION ['user_id'],
							'entity_id' => $id,
							'description' => $description 
					) );
					
					$this->messages [] = 'User Dactivated';
					$this->status = 'success';
				}
				
				// Redirect to list of albums
			} else {
				$id = $this->params ()->fromRoute ( 'id', 0 );
			}
			$user = $this->getUserTable ()->getUser ( $id );
			$vdata ['user'] = $user;
			$vdata ['messages'] = $this->messages;
			$vdata ['status'] = $this->status;
			return $vdata;
		}
	}
	public function userActiveAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			$vdata = array ();
			$request = $this->getRequest ();
			if ($request->isPost ()) {
				$id = $this->params ()->fromPost ( 'id' );
				$postdata = $this->params ()->fromPost ();
				
				if (empty ( $postdata ['reason'] )) {
					$this->status = 'error';
					$this->messages [] = 'Provide reason';
				} elseif ($postdata ['reason'] == 'other' && empty ( $postdata ['other_reason'] )) {
					$this->status = 'error';
					$this->messages [] = 'Provide reason';
				} 

				else {
					$description = $postdata ['reason'];
					if ($postdata ['reason'] == 'other') {
						$description = $postdata ['other_reason'];
					}
					$this->getUserTable ()->updateUser ( array (
							'disable_account' => 0 
					), $id );
					$this->getAdminLogTable ()->saveLog ( array (
							'log_type' => 'user_activated',
							'admin_id' => $_SESSION ['user_id'],
							'entity_id' => $id,
							'description' => $description 
					) );
					
					$this->messages [] = 'User activated';
					$this->status = 'success';
				}
				
				// Redirect to list of albums
			} else {
				$id = $this->params ()->fromRoute ( 'id', 0 );
			}
			$user = $this->getUserTable ()->getUser ( $id );
			$vdata ['user'] = $user;
			$vdata ['messages'] = $this->messages;
			$vdata ['status'] = $this->status;
			return $vdata;
		}
	}
	public function feedbackAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			$order_by = $this->params ()->fromQuery ( 'order_by', 0 );
			$order = $this->params ()->fromQuery ( 'order', 'DESC' );
			$q = $this->getUserName ();
			$where = '';
			if ($q) {
				$where = new \Zend\Db\Sql\Where ();
				$where->like ( 'username', "$q%" );
			}
			$column = array (
					'username',
					'create_time' 
			);
			$url_order = 'DESC';
			if (in_array ( $order_by, $column ))
				$url_order = $order == 'DESC' ? 'ASC' : 'DESC';
			try {
				
				$feedback = $this->getFeedbackTable ()->FetchFeedDescAll ( $where, $order_by, $order );
				$page = $this->params ()->fromQuery ( 'page', 1 );
				$iteratorAdapter = new \Zend\Paginator\Adapter\Iterator ( $feedback );
				$paginator = new Paginator ( $iteratorAdapter );
				$paginator->setCurrentPageNumber ( $page );
				// $paginator->setItemCountPerPage(ADMIN_QUERY_LIMIT);
				$paginator->setItemCountPerPage ( MemreasConstants::NUMBER_OF_ROWS );
			} catch ( Exception $exc ) {
				
				return array ();
			}
			return array (
					'paginator' => $paginator,
					'feedback_total' => count ( $feedback ),
					'order_by' => $order_by,
					'order' => $order,
					'q' => $q,
					'page' => $page,
					'url_order' => $url_order 
			);
		}
	}
	public function feedbackViewAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			$feedback_id = $this->params ()->fromRoute ( 'id' );
			$this->getAdminLogTable ()->saveLog ( array (
					'log_type' => 'feedback_view',
					'admin_id' => $_SESSION ['user_id'],
					'entity_id' => $feedback_id 
			) );
			
			$feedback = $this->getFeedbackTable ()->getFeedback ( $feedback_id );
			
			return array (
					'feedback' => $feedback 
			);
		}
	}
	protected $AdminUserTable;
	public function getAdminUserTable() {
		if (! $this->AdminUserTable) {
			$sm = $this->getServiceLocator ();
			$this->AdminUserTable = $sm->get ( 'Application\Model\AdminUserTable' );
		}
		return $this->AdminUserTable;
	}
	public function adminAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			$order_by = $this->params ()->fromQuery ( 'order_by', 0 );
			$order = $this->params ()->fromQuery ( 'order', 'DESC' );
			$q = $this->getUserName ();
			$where = '';
			if ($q) {
				$where = new \Zend\Db\Sql\Where ();
				$where->like ( 'username', "$q%" );
			}
			$column = array (
					'username',
					'role',
					'create_date' 
			);
			$url_order = 'DESC';
			if (in_array ( $order_by, $column ))
				$url_order = $order == 'DESC' ? 'ASC' : 'DESC';
			
			try {
				// $account = $this->getAccountTable()->getAccount(array('user_id'=>$id));
				// $account_id = $account;
				// echo '<pre>'; print_r($account->account_id); exit;
				
				$admin = $this->getAdminUserTable ()->FetchAdmins ( $where, $order_by, $order );
				
				$page = $this->params ()->fromQuery ( 'page', 1 );
				$iteratorAdapter = new \Zend\Paginator\Adapter\Iterator ( $admin );
				$paginator = new Paginator ( $iteratorAdapter );
				$paginator->setCurrentPageNumber ( $page );
				// $paginator->setItemCountPerPage(ADMIN_QUERY_LIMIT);
				$paginator->setItemCountPerPage ( MemreasConstants::NUMBER_OF_ROWS );
			} catch ( Exception $exc ) {
				
				return array ();
			}
			return array (
					'paginator' => $paginator,
					'admin_total' => count ( $admin ),
					'order_by' => $order_by,
					'order' => $order,
					'q' => $q,
					'page' => $page,
					'url_order' => $url_order 
			);
		}
	}
	public function adminTranAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			$user_id = $this->params ()->fromRoute ( 'id' );
			$page = $this->params ()->fromQuery ( 'page', 1 );
			$order_by = $this->params ()->fromQuery ( 'order_by', 0 );
			$order = $this->params ()->fromQuery ( 'order', 'DESC' );
			$q = $this->params ()->fromQuery ( 'q', 0 );
			$where = array ();
			$column = array (
					'username',
					'create_time' 
			);
			$url_order = 'DESC';
			
			$users_log = $this->getAdminLogTable ()->fetchAll ( array (
					'admin_id' => $user_id 
			) );
			// $this->getAdminLogTable()->saveLog(array('log_type'=>'admin_view', 'admin_id'=>$_SESSION['user']['user_id'], 'entity_id'=>$user_id));
			// $admin = $this->getAdminUserTable()->adminLog($user_id);
			
			// echo '<pre>'; print_r($users_log); exit;
			// $users = $this->getAdminUserTable()->adminLog();
			
			$iteratorAdapter = new \Zend\Paginator\Adapter\Iterator ( $users_log );
			$paginator = new Paginator ( $iteratorAdapter );
			$paginator->setCurrentPageNumber ( $page );
			$paginator->setItemCountPerPage ( 10 );
			
			return array (
					'paginator' => $paginator,
					'row' => $users_log,
					
					'order_by' => $order_by,
					'order' => $order,
					'q' => $q,
					'page' => $page,
					'url_order' => $url_order 
			);
		}
	}
	public function adminAddAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			$request = $this->getRequest ();
			if ($request->isPost ()) {
				$postData = $this->params ()->fromPost ();
				$where ['email_address'] = $postData ['email_address'];
				$where ['username'] = $postData ['username'];
				$userExist = $this->getAdminUserTable ()->isExist ( $where );
				
				if ($userExist) {
					$this->messages [] = 'User Name or email already exist';
					$this->status = 'error';
				} else {
					
					$user ['username'] = $postData ['username'];
					$user ['email_address'] = $postData ['email_address'];
					$user ['password'] = md5 ( $postData ['password'] );
					$user ['disable_account'] = 0;
					$user ['role'] = $postData ['role'];
					
					$user_id = $this->getAdminUserTable ()->saveUser ( $user );
					
					$this->getAdminLogTable ()->saveLog ( array (
							'log_type' => 'admin_user_added',
							'admin_id' => $_SESSION ['user_id'],
							'entity_id' => $user_id 
					) );
					
					$this->messages [] = 'Data Added sucessfully';
					$to [] = $postData ['email_address'];
					$viewVar = array (
							'email' => $postData ['email_address'],
							'username' => $postData ['username'],
							'passwrd' => $postData ['password'] 
					);
					$viewModel = new ViewModel ( $viewVar );
					$viewModel->setTemplate ( 'email/register' );
					$viewRender = $this->getServiceLocator ()->get ( 'ViewRenderer' );
					$html = $viewRender->render ( $viewModel );
					$subject = 'Welcome to Event App';
					if (empty ( $aws_manager ))
						$aws_manager = new AWSManagerSender ( $this->getServiceLocator () );
					$aws_manager->sendSeSMail ( $to, $subject, $html ); // Active this line when app go live
					$this->status = $status = 'Success';
					$message = "Welcome to Event App. Your profile has been created.";
				}
			}
			
			return array (
					'status' => $this->status,
					'messages' => $this->messages 
			);
		}
	}
	public function adminEditAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			$postData = array ();
			if ($this->request->isPost ()) {
				$user_id = $this->params ()->fromPost ( 'user_id' );
				$user = $this->getAdminUserTable ()->getUser ( $user_id );
				
				if (empty ( $user_id ) or empty ( $user )) {
					$this->messages [] = 'Admin Not Found';
				} else {
					$postData = $this->params ()->fromPost ();
					if ($user ['username'] != $postData ['username'] || $user ['email_address'] != $postData ['email_address']) {
						// $where['email_address'] = $postData['email_address'];
						// $where['username'] = $postData['username'];
						// $userExist = $this->getAdminUserTable()->isExist($where);
						
						/*
						 * if ($userExist) {
						 * $this->messages[] = 'User Name or email already exist';
						 * $this->status = 'error';
						 * } else {
						 *
						 * $user['username'] = $postData['username'];
						 * $user['email_address'] = $postData['email_address'];
						 * }
						 */
					}
					if (! empty ( $postData ['role'] )) {
						$user ['role'] = $postData ['role'];
					}
					if (! empty ( $postData ['password'] )) {
						$user ['password'] = md5 ( $postData ['password'] );
					}
					
					$user ['update_time'] = time ();
					// $user['disable_account'] = $postData['disable_account'];
					
					// Save the changes
					if ($this->status != 'error') {
						$this->getAdminUserTable ()->saveUser ( $user );
						$this->getAdminLogTable ()->saveLog ( array (
								'log_type' => 'admin_info_updated',
								'admin_id' => $_SESSION ['user_id'],
								'entity_id' => $user_id 
						) );
						$this->messages [] = 'Data Update sucessfully';
						$user = $this->getAdminUserTable ()->getUser ( $user_id );
					}
				}
			} else {
				$id = $this->params ()->fromRoute ( 'id' );
				$user = $this->getAdminUserTable ()->getUser ( $id );
			}
			
			return array (
					'admin' => $user,
					'messages' => $this->messages,
					'status' => $this->status,
					'post' => $postData 
			);
		}
	}
	public function adminDeactivateAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			
			$vdata = array ();
			$request = $this->getRequest ();
			if ($request->isPost ()) {
				$id = $this->params ()->fromPost ( 'aid' );
				$postdata = $this->params ()->fromPost ();
				// echo '<pre>';print_r($postData);exit;
				if (empty ( $postdata ['reason'] )) {
					$this->status = 'error';
					$this->messages [] = 'Provide reason';
				} elseif ($postdata ['reason'] == 'other' && empty ( $postdata ['other_reason'] )) {
					$this->status = 'error';
					$this->messages [] = 'Provide reason';
				} 

				else {
					
					$description = $postdata ['reason'];
					if ($postdata ['reason'] == 'other') {
						$description = $postdata ['other_reason'];
					}
					$this->getAdminUserTable ()->updateUser ( array (
							'disable_account' => '1' 
					), $id );
					$this->getAdminLogTable ()->saveLog ( array (
							'log_type' => 'admin_deactivated',
							'admin_id' => $_SESSION ['user_id'],
							'entity_id' => $id,
							'description' => $description 
					) );
					
					$this->messages [] = ' Admin User Dactivated';
					$this->status = 'success';
				}
				
				// Redirect to list of albums
			} else {
				$id = $this->params ()->fromRoute ( 'id', 0 );
			}
			// error_log ( 'user-id---' . $id );
			$user = $this->getAdminUserTable ()->getUser ( $id );
			$vdata ['user'] = $user;
			$vdata ['messages'] = $this->messages;
			$vdata ['status'] = $this->status;
			// print_r ( $vdata );
			return $vdata;
		}
	}
	public function adminActivateAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			$vdata = array ();
			$request = $this->getRequest ();
			if ($request->isPost ()) {
				$id = $this->params ()->fromPost ( 'aid' );
				$postdata = $this->params ()->fromPost ();
				
				if (empty ( $postdata ['reason'] )) {
					$this->status = 'error';
				} elseif ($postdata ['reason'] == 'other' && empty ( $postdata ['other_reason'] )) {
					$this->status = 'error';
				} 

				else {
					
					$description = $postdata ['reason'];
					if ($postdata ['reason'] == 'other') {
						$description = $postdata ['other_reason'];
					}
					
					$this->getAdminUserTable ()->updateUser ( array (
							'disable_account' => '0' 
					), $id );
					$this->getAdminLogTable ()->saveLog ( array (
							'log_type' => 'admin_activate',
							'admin_id' => $_SESSION ['user_id'],
							'entity_id' => $id,
							'description' => $description 
					) );
					
					$this->messages [] = 'Admin User activated';
					$this->status = 'success';
				}
			} else {
				$id = $this->params ()->fromRoute ( 'id', 0 );
			}
			$user = $this->getAdminUserTable ()->getUser ( $id );
			$vdata ['user'] = $user;
			$vdata ['messages'] = $this->messages;
			$vdata ['status'] = $this->status;
			return $vdata;
		}
	}
	protected $notifcationTable;
	public function getNotificationTable() {
		if (! $this->notifcationTable) {
			$sm = $this->getServiceLocator ();
			$this->notifcationTable = $sm->get ( 'Application\Model\NotficationTable' );
		}
		return $this->notifcationTable;
	}
	protected $friendTable;
	public function getFriendTable() {
		if (! $this->friendTable) {
			$sm = $this->getServiceLocator ();
			$this->friendTable = $sm->get ( 'Application\Model\FriendTable' );
		}
		return $this->friendTable;
	}
	public function accountSummaryAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			try {
				$total = $this->getUserTable ()->getUserRegisterCount ( strtotime ( '01-12-2010' ) );
				$pastday = $this->getUserTable ()->getUserRegisterCount ( strtotime ( ' -1 day' ) );
				$pastweek = $this->getUserTable ()->getUserRegisterCount ( strtotime ( ' -1 week' ) );
				$pastmonth = $this->getUserTable ()->getUserRegisterCount ( strtotime ( '-1 month' ) );
				// print_r($total); exit;
				/*
				 * $i = $this->getUserInfoTable()->fetchAll();
				 * $page = $this->params()->fromQuery('page', 1);
				 * $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($i);
				 * $paginator = new Paginator($iteratorAdapter);
				 * $paginator->setCurrentPageNumber($page);
				 * $paginator->setItemCountPerPage(5);
				 */
				$s3Total = $this->getUserInfoTable ()->getUserInfo ( 'total-s3' );
				$totalfriendsinvites = $this->getNotificationTable ()->getInviteCount ( strtotime ( '01-12-2010' ) );
				$totaleventfriendsinvites = $this->getNotificationTable ()->getInviteCount ( strtotime ( '01-12-2010' ), 1 );
				$fbpastday = $this->getFriendTable ()->getOtherInviteCount ( strtotime ( ' -1 day' ), 'facebook' );
				$fbpastweek = $this->getFriendTable ()->getOtherInviteCount ( strtotime ( ' -1 week' ), 'facebook' );
				$fbpastmonth = $this->getFriendTable ()->getOtherInviteCount ( strtotime ( '-1 month' ), 'facebook' );
				$twpastday = $this->getFriendTable ()->getOtherInviteCount ( strtotime ( ' -1 day' ), 'twitter' );
				$twpastweek = $this->getFriendTable ()->getOtherInviteCount ( strtotime ( ' -1 week' ), 'twitter' );
				$twpastmonth = $this->getFriendTable ()->getOtherInviteCount ( strtotime ( '-1 month' ), 'twitter' );
				$emailpastday = $this->getNotificationTable ()->getEmailInviteCount ( strtotime ( '-1 day' ) );
				$emailpastweek = $this->getNotificationTable ()->getEmailInviteCount ( strtotime ( '-1 week' ) );
				$emailpastmonth = $this->getNotificationTable ()->getEmailInviteCount ( strtotime ( '-1 month' ) );
				$sid = $_SESSION['sid'];
				$result = $this->fetchXML ( 'getplansstatic', '<xml><sid>$sid</sid><getplansstatic><static>1</static></getplansstatic></xml>' );
				$summaryData = simplexml_load_string ( $result );
				
				// echo '<pre>';print_r($summaryData);exit;
			} catch ( Exception $exc ) {
				
				return array ();
			}
			return array (
					// 'paginator' => $paginator,
					'total' => $total,
					'pastday' => $pastday,
					'pastweek' => $pastweek,
					'pastmonth' => $pastmonth,
					's3Total' => $s3Total,
					'fbpastday' => $fbpastday,
					'fbpastweek' => $fbpastweek,
					'fbpastmonth' => $fbpastmonth,
					'twpastday' => $twpastday,
					'twpastweek' => $twpastweek,
					'twpastmonth' => $twpastmonth,
					'emailpastday' => $emailpastday,
					'emailpastweek' => $emailpastweek,
					'emailpastmonth' => $emailpastmonth,
					'totaleventfriendsinvites' => $totaleventfriendsinvites,
					'totalfriendsinvites' => $totalfriendsinvites,
					'summaryData' => $summaryData 
			);
		}
	}
	public function accountUsageAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			$order_by = $this->params ()->fromQuery ( 'order_by', 0 );
			$order = $this->params ()->fromQuery ( 'order', 'DESC' );
			$q = $this->getUserName ();
			$where = new \Zend\Db\Sql\Where ();
			if ($q) {
				$where->like ( 'username', "$q%" );
			}
			$where->notEqualTo ( 'user_info.user_id', 'total-s3' );
			$column = array (
					'username',
					'data_usage' 
			);
			$url_order = 'DESC';
			if (in_array ( $order_by, $column ))
				$url_order = $order == 'DESC' ? 'ASC' : 'DESC';
			
			try {
				$info = $this->getUserInfoTable ()->userInfoAll ( $where, $order_by, $order );
				$page = $this->params ()->fromQuery ( 'page', 1 );
				$iteratorAdapter = new \Zend\Paginator\Adapter\Iterator ( $info );
				$paginator = new Paginator ( $iteratorAdapter );
				$paginator->setCurrentPageNumber ( $page );
				$paginator->setItemCountPerPage ( MemreasConstants::NUMBER_OF_ROWS );
				
				// $totalused = $this->getUserInfoTable()->totalPercentUsed();
				/*
				 * $rec = $this->getUserInfoTable()->fetchAll(); print_r($rec); $allowed_size = $rec-> allowed_size; $data_usage=$rec-> data_usage; $totalused = $data_usage*100/allowed_size; print_r($totalused);
				 */
			} catch ( Exception $exc ) {
				
				// return array();
			}
			return array (
					'paginator' => $paginator,
					'user_total' => count ( $info ),
					'order_by' => $order_by,
					'order' => $order,
					'q' => $q,
					'page' => $page,
					'url_order' => $url_order 
			);
		}
	}
	public function orderHistoryAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			$sid = $_SESSION['sid'];
			// $id = $this->params()->fromRoute('id');
			// $this->getAdminLogTable()->saveLog(array('log_type'=>'feedback_view', 'admin_id'=>$_SESSION['user']['user_id'], 'entity_id'=>$id));
			$username = $this->getUserName ();
                        //$username = "jmeah80";
			$page = $this->params ()->fromQuery ( 'page', 1 );
			
			// $result = $this->fetchXML ( 'getorderhistory', "<xml><getorderhistory><user_id>0</user_id><search_username>$username</search_username><page>$page</page><limit>15</limit></getorderhistory></xml>" );
			/**
			 * Set admin key as UUID with username as value 
			 * - pass admin_key as request parameter
			 * - proxy will check for admin_key and pass through
			 * - set ttl t0 5 mins since this is user data
			 */
			
			$result = $this->fetchXML ( "getorderhistory", "
					<xml>
						<sid>$sid</sid>
                                                <search_username>$username</search_username>
						<getorderhistory>
						<user_id></user_id>
						<page>$page</page>
						<limit>15</limit>
						</getorderhistory></xml>" );
			$orderData = json_decode ((string) $result);
                               //error_log(print_r($orderData,true));
			return array (
					'orderData' => $orderData,
					'page' => $page 
			);
		}
	}
	public function orderHistoryDetailAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
                    $sid = $_SESSION['sid'];
			$transaction_id = $this->params ()->fromRoute ( 'id' );
			$this->getAdminLogTable ()->saveLog ( array (
					'log_type' => 'feedback_view',
					'admin_id' => $_SESSION ['user_id'],
					'entity_id' => $transaction_id 
			) );
			$result = $this->fetchXML ( 'getorder',
                                "<xml><sid>$sid</sid><getorder><transaction_id>$transaction_id</transaction_id></getorder></xml>" );
                                
                        $orderData = json_decode ((string) $result);
			//echo '<pre>a';print_r($orderData,true);exit;
			return array (
					'orderData' => $orderData 
			);
		}
	}
	public function eventAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			$order_by = $this->params ()->fromQuery ( 'order_by', 0 );
			$order = $this->params ()->fromQuery ( 'order', 'DESC' );
			$q = $this->params ()->fromQuery ( 'q', 0 );
			$where = new \Zend\Db\Sql\Where ();
			if ($q) {
				$q = $this->getUserName ();
				
				$where->like ( 'username', "$q%" );
			}
			$where->equalTo ( 'public', 1 );
			$column = array (
					'username',
					'name' 
			);
			$url_order = 'DESC';
			if (in_array ( $order_by, $column ))
				$url_order = $order == 'DESC' ? 'ASC' : 'DESC';
			
			try {
				
				$event = $this->getEventTable ()->moderateFetchAll ( $where, $order_by, $order );
				$page = $this->params ()->fromQuery ( 'page', 1 );
				$iteratorAdapter = new \Zend\Paginator\Adapter\Iterator ( $event );
				$paginator = new Paginator ( $iteratorAdapter );
				$paginator->setCurrentPageNumber ( $page );
				// $paginator->setItemCountPerPage(ADMIN_QUERY_LIMIT);
				$paginator->setItemCountPerPage ( MemreasConstants::NUMBER_OF_ROWS );
			} catch ( Exception $exc ) {
				
				return array ();
			}
			return array (
					'paginator' => $paginator,
					'event_total' => count ( $event ),
					'order_by' => $order_by,
					'order' => $order,
					'q' => $q,
					'page' => $page,
					'url_order' => $url_order 
			);
		}
	}
	public function getEventTable() {
		if (! $this->eventTable) {
			$sm = $this->getServiceLocator ();
			$this->eventTable = $sm->get ( 'Application\Model\EventTable' );
		}
		return $this->eventTable;
	}
	public function eventMediaAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			$event_id = $this->params ()->fromRoute ( 'id' );
			$this->getAdminLogTable ()->saveLog ( array (
					'log_type' => 'media_view',
					'admin_id' => $_SESSION ['user_id'],
					'entity_id' => $event_id 
			) );
			
			$event = $this->getEventTable ()->getEventMedia ( $event_id );
			
			$view = new ViewModel ();
			$view->setVariable ( 'medias', $event );
			
			return $view;
		}
	}
	public function eventChangeStatusAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			
			$eventTable = $this->getEventTable ();
			$event_id = $this->params ()->fromRoute ( 'id' );
			$event = $eventTable->getEvent ( $event_id );
			$date = strtotime ( date ( 'd-m-Y' ) );
			$eventStatus = 'inactive';
			if (($event->viewable_to >= $date || $event->viewable_to == '') && ($event->viewable_from <= $date || $event->viewable_from == '') && ($event->self_destruct >= $date || $event->self_destruct == ''))
				$eventStatus = 'active';
			
			return array (
					'eventStatus' => $eventStatus,
					'event' => $event 
			);
		}
	}
	public function eventApproveAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			
			$date1 = strtotime ( 'today + 1year' );
			$date = strtotime ( 'NOW' );
			$eventTable = $this->getEventTable ();
			if ($this->request->isPost ()) {
				$postdata = $this->params ()->fromPost ();
				if (empty ( $postdata ['reason'] )) {
					$messages [] = 'Please give reason';
					$this->status = 'error';
				} elseif ($postdata ['reason'] == 'other' && empty ( $postdata ['other_reason'] )) {
					$messages [] = 'Please give reason';
					$this->status = 'error';
				} else {
					
					$description = $postdata ['reason'];
					if ($postdata ['reason'] == 'other') {
						$description = $postdata ['other_reason'];
					}
					$event = $eventTable->getEvent ( $postData ['event_id'] );
					
					$eventStatus = 'inactive';
					if (($event->viewable_to >= $date || $event->viewable_to == '') && ($event->viewable_from <= $date || $event->viewable_from == '') && ($event->self_destruct >= $date || $event->self_destruct == ''))
						$eventStatus = 'active';
					$this->getAdminLogTable ()->saveLog ( array (
							'log_type' => 'event_disable',
							'admin_id' => $_SESSION ['user_id'],
							'entity_id' => $postdata ['event_id'],
							'description' => $description 
					) );
					$messages [] = 'Event approve succesfully';
					$status = 'success';
					
					$eventTable->update ( array (
							'event_id' => $postdata ['event_id'],
							'self_destruct' => $date1 
					), $postdata ['event_id'] );
					return array (
							'eventStatus' => $eventStatus,
							'event' => $event,
							'messages' => $messages,
							'status' => $status 
					);
				}
			}
		}
	}
	public function eventDisapproveAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			
			$date1 = strtotime ( 'today - 1 month' );
			$eventTable = $this->getEventTable ();
			if ($this->request->isPost ()) {
				$postdata = $this->params ()->fromPost ();
				if (empty ( $postdata ['reason'] )) {
					$messages [] = 'Please give reason';
					$this->status = 'error';
				} elseif ($postdata ['reason'] == 'other' && empty ( $postdata ['other_reason'] )) {
					$messages [] = 'Please give reason';
					$this->status = 'error';
				} else {
					
					$description = $postdata ['reason'];
					if ($postdata ['reason'] == 'other') {
						$description = $postdata ['other_reason'];
					}
					$event = $eventTable->getEvent ( $postData ['event_id'] );
					$eventTable->update ( array (
							'event_id' => $postdata ['event_id'],
							'self_destruct' => $date1 
					), $postdata ['event_id'] );
					$this->getAdminLogTable ()->saveLog ( array (
							'log_type' => 'event_disable',
							'admin_id' => $_SESSION ['user_id'],
							'entity_id' => $postdata ['event_id'],
							'description' => $description 
					) );
					$this->messages [] = 'Event disapprove succesfully';
					$this->status = 'success';
				}
				return array (
						'messages' => $this->messages,
						'status' => $this->status 
				);
			}
		}
	}
	public function getUserName() {
		$username = '';
		$q = $this->params ()->fromQuery ( 'q', 0 );
		if (empty ( $q )) {
			return 0;
		}
		$t = $q [0];
		
		if ($t == '@') {
			$username = $search = substr ( $q, 1 );
		}
		return $username;
	}
	public function getEventName() {
		$q = $this->params ()->fromQuery ( 'q', 0 );
		if (empty ( $q )) {
			return 0;
		}
		$t = $q [0];
		$name = '';
		if ($t == '!') {
			$username = $search = substr ( $q, 1 );
		}
		return $name;
	}
	public function payoutAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			
			$action = "listpayees";
			$page = $this->params ()->fromQuery ( 'page', 1 );
			$q = $this->params ()->fromQuery ( 'q', 0 );
			$t = $q [0];
			$username = '';
			if ($t == '@') {
				$username = $search = substr ( $q, 1 );
			}
			$sid = $_SESSION['sid'];
			$xml = "<xml><sid>$sid</sid><listpayees><username>$username</username><page>$page</page><limit>10</limit></listpayees></xml>";
			$result = $this->fetchXML ( $action, $xml );
			$data = json_decode ((string) $result);
			echo '<pre>';print_r($data);
			return array (
					'listpayees' => $data,
					'page' => $page,
					'q' => $q 
			);
		}
	}
	// public function payoutReasonAction() {
	// return array ();
	// }
	public function doPayoutAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			
			$action = "makepayout";
			$description = $page = $this->params ()->fromPost ( 'other_reason', '' );
			$payee = $page = $this->params ()->fromPost ( 'ids', array () );
			$sid = $_SESSION['sid'];
			try {
				foreach ( $payee as $account_id => $amount ) {
					$xml = "<xml><sid>$sid</sid><makepayout><account_id>$account_id</account_id><amount>$amount</amount><description>$description</description></makepayout></xml>";
					error_log ( $xml );
					
					$result = $this->fetchXML ( $action, $xml );
					 $data = json_decode ((string) $result);
                                
					$response [] = array (
							'account_id' => $account_id,
							'status' => $data->status,
							'amount' => $amount,
							'message' => $data->message 
					);
				}
			} catch ( \Exception $e ) {
			}
			
			return array (
					'response' => $response 
			);
		}
	}
	public function accountAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			
			$page = $this->params ()->fromQuery ( 'page', 1 );
			$username = $this->getUserName ();
			$sid = $_SESSION['sid'];
			$result = $this->fetchXML ( 'getorderhistory', "<xml><sid>$sid</sid><getorderhistory><user_id>0</user_id><search_username>$username</search_username><page>$page</page><limit>15</limit></getorderhistory></xml>" );
			$orderData = json_decode ((string) $result);
                        echo '<pre>';print_r($orderData);
			return array (
					'orderData' => $orderData,
					'page' => $page 
			);
		}
	}
	public function refundAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			
			$action = "listpayees";
			$page = $this->params ()->fromQuery ( 'page', 1 );
			$sid = $_SESSION['sid'];
			$xml = "<xml><sid>$sid</sid><listpayees><page>$page</page><limit>10</limit></listpayees></xml>";
			$result = $this->fetchXML ( $action, $xml );
			$data = simplexml_load_string ( $result );
			
			return array (
					'listpayees' => $data,
					'page' => $page 
			);
		}
	}
	public function security() {
		$cm = __CLASS__ . __METHOD__;
		$roles = array (
				'guest' => array (
						'index' 
				),
				
				'admin' => array (
						'logout',
						'manage',
						'user',
						'account',
						'orderhistory',
						'userView',
						'userActive',
						'userDeactive',
						'event',
						'eventApprove',
						'eventDisapprove',
						'eventChangeStatus',
						'feedback',
						'feedbackView',
						'account-summary',
						'account-usage',
						'order-history' 
				),
				'superadmin' => array (
						'logout',
						'manage',
						'user',
						'account',
						'orderhistory',
						'userView',
						'userActive',
						'userDeactive',
						'event',
						'eventApprove',
						'eventDisapprove',
						'eventChangeStatus',
						'feedback',
						'feedbackView',
						'account-summary',
						'account-usage',
						'order-history',
						'payout',
						'doPayout',
						'refund' 
				) 
		);
		
		$userRole = 'guest';
		$action = $this->params ( 'action' );
		error_log ( 'reuested ---' . print_r ( $action, true ) );
		if (isset ( $_SESSION ['user'] ['role'] )) {
			switch ($_SESSION ['user'] ['role']) {
				default :
					$userRole = 'guest';
					break;
				case '1' :
					$userRole = 'admin';
					break;
				case '3' :
					$userRole = 'superadmin';
					break;
			}
		}
		Mlog::addone ( $cm . '$userRole--->', $userRole );
		if ($userRole == 'superadmin') {
			return true;
		} elseif ($userRole == 'admin' && in_array ( $action, $roles ['admin'] )) {
			return true;
		} elseif ($userRole == 'guest' && in_array ( $action, $roles ['guest'] )) {
			return true;
		}
                                
		die ( '<b>your account is not authorized for this function</b>' ); // donot change this otherwise all action will be allowed
	}
	public function updateMediaInfoAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		
		if ($this->fetchSession ()) {
			ini_set ( 'max_execution_time', 500 );
			$aws = new AWSManagerSender ( $this->getServiceLocator () );
			$client = $aws->s3;
			$bucket = 'memreasdevsec';
			$total_used = 0.0;
			$count_image = 0;
			$count_vedio = 0;
			$count_audio = 0;
			$size_vedio = 0;
			$size_audio = 0;
			$size_audio = 0;
			$size_image = 0;
			
			$audioExt = array (
					'caf' => '',
					'wav' => '',
					'mp3' => '',
					'm4a' => '' 
			);
			$users = $this->getUserTable ()->fetchall ( array (
					'disable_account' => 0 
			) );
			
			foreach ( $users as $user ) {
				$user_id = $user->user_id;
				// $user_id="c96f0282-8f3a-414b-bd7a-ead57b1bfa4e";
				
				$iterator = $client->getIterator ( 'ListObjects', array (
						'Bucket' => $bucket,
						'Prefix' => $user_id 
				) );
				
				$userids = array ();
				foreach ( $iterator as $object ) {
					$userid = stristr ( $object ['Key'], '/', true );
					// echo $object ['Key'] ,'-------------',$object ['Size'],'<br>';
					$ext = pathinfo ( $object ['Key'], PATHINFO_EXTENSION );
					$image = $user_id . '/image/';
					$media = $user_id . '/media/';
					if (isset ( $userids [$userid] )) {
					} else {
						$userids [$userid] = array (
								'total_used' => 0,
								'size_image' => 0,
								'count_image' => 0,
								'size_audio' => 0,
								'count_audio' => 0,
								'size_vedio' => 0,
								'count_vedio' => 0,
								'avg_img' => 0,
								'avg_audio' => 0,
								'avg_vedio' => 0 
						);
					}
					$total_used = bcadd ( $total_used, $object ['Size'] );
					$userids [$userid] ['total_used'] = bcadd ( $userids [$userid] ['total_used'], $object ['Size'] );
					if (stripos ( $object ['Key'], $image ) === 0) {
						// echo 'image';
						$size_image = bcadd ( $size_image, $object ['Size'] );
						$userids [$userid] ['size_image'] = bcadd ( $userids [$userid] ['size_image'], $object ['Size'] );
						
						++ $count_image;
						++ $userids [$userid] ['count_image'];
					} else if (isset ( $audioExt [$ext] )) {
						// echo 'audio';
						$size_audio = bcadd ( $size_audio, $object ['Size'] );
						$userids [$userid] ['size_audio'] = bcadd ( $userids [$userid] ['size_audio'], $object ['Size'] );
						
						++ $count_audio;
						++ $userids [$userid] ['count_audio'];
					} else {
						// echo 'vedio';
						$size_vedio = bcadd ( $size_vedio, $object ['Size'] );
						$userids [$userid] ['size_vedio'] = bcadd ( $userids [$userid] ['size_vedio'], $object ['Size'] );
						
						++ $count_vedio;
						++ $userids [$userid] ['count_vedio'];
					}
				}
				$avg_img = empty ( $count_image ) ? $count_image : bcdiv ( $size_image, $count_image, 0 );
				$userids [$userid] ['avg_img'] = empty ( $userids [$userid] ['count_image'] ) ? $userids [$userid] ['count_image'] : bcdiv ( $userids [$userid] ['size_image'], $userids [$userid] ['count_image'], 0 );
				$avg_audio = empty ( $count_audio ) ? $count_audio : bcdiv ( $size_audio, $count_audio, 0 );
				$userids [$userid] ['avg_audio'] = empty ( $userids [$userid] ['count_audio'] ) ? $userids [$userid] ['count_audio'] : bcdiv ( $userids [$userid] ['size_audio'], $userids [$userid] ['count_audio'], 0 );
				
				$avg_vedio = empty ( $count_vedio ) ? $count_vedio : bcdiv ( $size_vedio, $count_vedio, 0 );
				$userids [$userid] ['avg_vedio'] = empty ( $userids [$userid] ['count_vedio'] ) ? $userids [$userid] ['count_vedio'] : bcdiv ( $userids [$userid] ['size_vedio'], $userids [$userid] ['count_vedio'], 0 );
				
				foreach ( $userids as $key => $row ) {
					if (empty ( $key ))
						continue;
					$data = array (
							'user_id' => $key,
							'data_usage' => $row ['total_used'],
							'total_image' => $row ['count_image'],
							'total_vedio' => $row ['count_vedio'],
							'total_audio' => $row ['count_audio'],
							'average_image' => $row ['avg_img'],
							'average_vedio' => $row ['avg_vedio'],
							'average_audio' => $row ['avg_audio'],
							'plan' => '' 
					);
					$this->getUserInfoTable ()->saveUserInfo ( $data );
				}
				// break;
			}
			$data = array (
					'user_id' => 'total-s3',
					'data_usage' => $total_used,
					'total_image' => $count_image,
					'total_vedio' => $count_vedio,
					'total_audio' => $count_audio,
					'average_image' => $avg_img,
					'average_vedio' => $avg_vedio,
					'average_audio' => $avg_audio,
					'plan' => '' 
			);
			$this->getUserInfoTable ()->saveUserInfo ( $data );
			
			die ( 'done' );
		}
	}
	protected function csvAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		if ($this->fetchSession ()) {
			$columnHeaders = array (
					'username',
					'plan',
					'data_usage',
					'# of image',
					'Avg. image size',
					'# of video',
					'Avg. video size',
					'# of audio comment',
					'Avg. audio comment size',
					'total % used' 
			);
			$info = $this->getUserInfoTable ()->userInfoAll ()->toArray ();
			$filename = 'test.csv';
			$resultset = $info;
			$view = new ViewModel ();
			$view->setTemplate ( 'download/download-csv' )->setVariable ( 'results', $resultset )->setTerminal ( true );
			$view->setVariable ( 'columnHeaders', $columnHeaders );
			
			$output = $this->getServiceLocator ()->get ( 'viewrenderer' )->render ( $view );
			
			$response = $this->getResponse ();
			
			$headers = $response->getHeaders ();
			$headers->addHeaderLine ( 'Content-Type', 'text/csv' )->addHeaderLine ( 'Content-Disposition', sprintf ( "attachment; filename=\"%s\"", $filename ) )->addHeaderLine ( 'Accept-Ranges', 'bytes' )->addHeaderLine ( 'Content-Length', strlen ( $output ) );
			
			$response->setContent ( $output );
			
			return $response;
		}
	}
	public function updateUserPlanAction() {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		/**
		 * TODO: This code doesn't work??
		 */
		if ($this->fetchSession ()) {
			$action = "login";
			$xml = "<xml><login><username>kamlesh</username><password>123456</password><devicetype></devicetype><devicetoken></devicetoken></login></xml>";
			// $xml ="<xml><getplans><user_id>d37c751e-54a3-4eb9-88c9-472261e59629</user_id></getplans></xml>";
			// $userid=1;
			$result = $this->fetchXML ( $action, $xml );
			$data = simplexml_load_string ( $result );
			$status = trim ( $data->loginresponse->status );
			error_log ( 'response from server---' . print_r ( $status, true ) );
			if ('success' == strtolower ( $status )) {
				$_SESSION ['sid'] = trim ( $data->loginresponse->sid );
			}
			$userRec = $this->getUserTable ()->fetchAll ();
			foreach ( $userRec as $user ) {
				$this->getPlan ( $user->user_id );
			}
			die ( 'done' );
		}
	}
	public function getPlan($userid = '') {
		Mlog::addone ( __CLASS__ . __METHOD__, __LINE__ );
		$action = "getplans";
		$xml = "<xml><getplans><user_id>$userid</user_id></getplans></xml>";
		$result = $this->fetchXML ( $action, $xml );
		$data = simplexml_load_string ( $result );
		$planSize = array (
				'PLAN_A_2GB_MONTHLY' => MemreasConstants::_2GB,
				'PLAN_B_10GB_MONTHLY' => MemreasConstants::_10GB,
				'PLAN_C_50GB_MONTHLY' => MemreasConstants::_50GB,
				'PLAN_C_100GB_MONTHLY' => MemreasConstants::_100GB 
		);
		
		$plan = trim ( $data->getplansresponse->plan_id );
		$status = trim ( $data->getplansresponse->status );
		if ($status == 'Success') {
			$row ['allowed_size'] = $planSize [$plan];
			$row ['plan'] = $plan;
			$row ['user_id'] = $userid;
			$this->getUserInfoTable ()->saveUserInfo ( $row );
		} else {
			$row ['allowed_size'] = $planSize ['PLAN_A_2GB_MONTHLY'];
			$row ['plan'] = 'PLAN_A_2GB_MONTHLY';
			$row ['user_id'] = $userid;
			$this->getUserInfoTable ()->saveUserInfo ( $row );
		}
	}
}

// end class IndexController
