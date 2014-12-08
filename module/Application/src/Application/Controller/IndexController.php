<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Application\Model;
use Application\Model\UserTable;
use Application\Form;
use Zend\Mail\Message;
use Zend\Mail\Transport\Sendmail as SendmailTransport;
use Guzzle\Http\Client;
use Zend\Http\ClientStatic;
use Application\View\Helper\S3Service;
use Application\View\Helper\S3;
use Aws\S3\S3Client;
use Application\Memreas\AWSManagerSender;
use Application\Model\MemreasConstants;
use Application\Memreas\User;
use Application\Controller\Common;
use DoctrineModule\Paginator\Adapter\Selectable as SelectableAdapter;
use Zend\Paginator\Paginator;


class IndexController extends AbstractActionController {

    //Updated....
    protected $url = MemreasConstants::MEMREAS_WS;
    public   $sid = '';   
    protected $user_id;
    protected $storage;
    protected $authservice;
    protected $userTable;
    protected $eventTable;
    protected $mediaTable;
    protected $friendmediaTable;
    public $messages = array();
public $status ;

    protected $userinfoTable;
     protected $adminLogTable;
    public function getAdminLogTable() {
        if (!$this->adminLogTable) {
            $sm = $this->getServiceLocator();
            $this->adminLogTable = $sm->get('Application\Model\AdminLogTable');
        }
        return $this->adminLogTable;
    }
    protected $feedbackTable;
    public function getFeedbackTable() {
        if (!$this->feedbackTable) {
            $sm = $this->getServiceLocator();
            $this->feedbackTable = $sm->get('Application\Model\FeedbackTable');
        }
        return $this->feedbackTable;
    }
     public function getUserInfoTable() {
        if (!$this->userinfoTable) {
            $sm = $this->getServiceLocator();
            $this->userinfoTable = $sm->get('Application\Model\UserInfoTable');
        }
        return $this->userinfoTable;
    }

    public function getToken() {
        $session = $this->getAuthService()->getIdentity();
        return empty($session['token']) ? '' : $session['token'];
        ;
    }

    public function fetchXML($action, $xml) {
         $guzzle = new Client();
//error_log("Inside fetch XML request url ---> " . $this->url . PHP_EOL);
//error_log("Inside fetch XML request action ---> " . $action . PHP_EOL);
//error_log("Inside fetch XML request XML ---> " . $xml . PHP_EOL);
        $request = $guzzle->post(
            $this->url,
            null,
            array(
            'action' => $action,
            //'cache_me' => true,
            'xml' => $xml,
            'sid' =>empty($_SESSION['user']['sid'])?'':$_SESSION['user']['sid'],
            //'user_id' => empty($_SESSION['user']['user_id'])?'':$_SESSION['user']['user_id']
            )
        );
        $response = $request->send();
//error_log("Inside fetch XML response ---> " . $response->getBody(true) . PHP_EOL);
//error_log("Exit fetchXML".PHP_EOL);
        return $data = $response->getBody(true);
    }

    public function indexAction() {

error_log("Enter admin " . __FUNCTION__ . PHP_EOL);
        //$path = $this->security("application/index/index.phtml");
        $path = "application/index/index.phtml";
        $view = new ViewModel();
        $view->setTemplate($path); // path to phtml file under view folder
        return $view;
error_log("Exit admin " . __FUNCTION__ . PHP_EOL);
    }

    public function ApiServerSideAction() {
        if (isset($_REQUEST['callback'])) {
            //Fetch parms
            $callback = $_REQUEST['callback'];
            $json = $_REQUEST['json'];
            $message_data = json_decode($json, true);
            //Setup the URL and action
            $ws_action = $message_data['ws_action'];
            $type = $message_data['type'];
            $xml = $message_data['json'];

            //Guzzle the LoginWeb Service
            $result = $this->fetchXML($ws_action, $xml);

            $json = json_encode($result);
            //Return the ajax call...
            $callback_json = $callback . "(" . $json . ")";
            $output = ob_get_clean();
            header("Content-type: plain/text");
            echo $callback_json;
            //Need to exit here to avoid ZF2 framework view.
        }
        exit;
    }

    public function buildvideocacheAction() {
        if (isset($_POST['video_url'])) {
            $cache_dir = $_SERVER['DOCUMENT_ROOT'] . '/memreas/js/jwplayer/jwplayer_cache/';
            $video_name = explode("/", $_POST['video_url']);
            $video_name = $video_name[count($video_name) - 1];
            $cache_file = $this->generateVideoCacheFile($cache_dir, $video_name);
            $file_handle = fopen($cache_dir . $cache_file, 'w');
            $content = '<!doctype html>
                            <html>
                            <head>
                            <meta charset="utf-8">
                            <title>Untitled Document</title>
                            <script type="text/javascript" src="../jwplayer.js"></script>
                            <script type="text/javascript" src="../jwplayer.html5.js"></script>
                            <style>
                            #myElement_wrapper{
                                margin:0 auto !important;
                                width: 100% !important;
                                min-height: 310px !important;
                            }
                            </style>
                            </head>
                            <body>
                            <div id="myElement">Loading the player...</div>
                            <script type="text/javascript">
                                jwplayer("myElement").setup({
                                    flashplayer: "../jwplayer.flash.swf",
                                    file: "' . $_POST['video_url'] . '",
                                    "autostart": "true",
                                    "width": "100%",
                                });
                            </script>
                            </body>
                            </html>';
            fwrite($file_handle, $content, 5000);
            fclose($file_handle);
            $response = array('video_link' => $cache_file, 'thumbnail' => $_POST['thumbnail'], 'media_id' => $_POST['media_id']);
            echo json_encode($response);
        }
        exit();
    }

    private function generateVideoCacheFile($cache_dir, $video_name) {
        $cache_file = uniqid('jwcache_') . substr(md5($video_name), 0, 10) . '.html';
        if (!file_exists($cache_file))
            return $cache_file;
        else
            $this->generateVideoCacheFile($cache_dir, $video_name);
    }

    public function sampleAjaxAction() {

        $path = $this->security("application/index/sample-ajax.phtml");

        if (isset($_REQUEST['callback'])) {
            //Fetch parms
            $callback = $_REQUEST['callback'];
            $json = $_REQUEST['json'];
            $message_data = json_decode($json, true);
            //Setup the URL and action
            $ws_action = $message_data['ws_action'];
            $type = $message_data['type'];
            $xml = $message_data['json'];

            //Guzzle the LoginWeb Service
            $result = $this->fetchXML($ws_action, $xml);

            $json = json_encode($result);
            //Return the ajax call...
            $callback_json = $callback . "(" . $json . ")";
            $output = ob_get_clean();
            header("Content-type: plain/text");
            echo $callback_json;
            //Need to exit here to avoid ZF2 framework view.
            exit;
        } else {
            $view = new ViewModel();
            $view->setTemplate($path); // path to phtml file under view folder
        }

        return $view;
    }

    public function s3uploadAction() {
        $S3Service = new S3Service();
        $session = new Container('user');
        $data['bucket'] = 'memreasdev';
        $data['folder'] = $session->offsetGet('user_id') . '/image/';
        $data['user_id'] = $session->offsetGet('user_id');
        $data['ACCESS_KEY'] = $S3Service::getAccessKey();
        list($data['policy'], $data['signature']) = $S3Service::get_policy_and_signature(array(
                    'bucket' => $data['bucket'],
                    'folder' => $data['folder'],
        ));
        $view = new ViewModel(array('data' => $data));
        $path = $this->security("application/index/s3upload.phtml");
        $view->setTemplate($path);
        return $view;
    }

    public function addmediaAction() {
        $session = new Container('user');
        $s3 = new S3('AKIAJMXGGG4BNFS42LZA', 'xQfYNvfT0Ar+Wm/Gc4m6aacPwdT5Ors9YHE/d38H');
        $target_path = '/' . $session->offsetGet('user_id') . '/image/' . $_FILES['upl']['name'];
        $s3->putBucket('memreasdev', S3::ACL_PUBLIC_READ);
        echo '{"status":"success"}';
        /* if ($s3->putObjectFile($_FILES['upl']['tmp_name'], 'memreasdev', $target_path, S3::ACL_PUBLIC_READ, array(), 'image/jpeg')){
          $ws_action = "addmediaevent";
          $xml = "<xml><addmediaevent><s3url>http://s3.amazonaws.com/memreasdev/" . $session->offsetGet('user_id') . '/image/' . $_FILES['upl']['name'] . "</s3url><is_server_image>0</is_server_image><content_type>" . $_FILES['upl']['type'] . "</content_type><s3file_name>" . $_FILES['upl']['name'] . "</s3file_name><device_id></device_id><event_id></event_id><media_id></media_id><user_id>" . $session->offsetGet('user_id') . "</user_id><is_profile_pic>0</is_profile_pic><location></location></addmediaevent></xml>";
          $result = $this->fetchXML($ws_action, $xml);
          echo '{"status":"success"}';
          }
          else echo '{"status":"error"}'; */
        die();
    }

   

     

    public function loginAction() {
         //Fetch the post data
        $request = $this->getRequest();

        
  if($request->isPost() ){
    
        $postData = $request->getPost()->toArray();
        $username = $postData ['username'];
        $password = $postData ['password'];
        if($this->setSession($username,$password) ){
        return $this->redirect()->toRoute('index', array('controller' => 'index', 'action' => 'manage'));

        }
  }

     return $this->redirect()->toRoute('index', array('action' => "index"));
 
 }
 
    public function logoutAction() {
        //$this->getSessionStorage()->forgetMe();
        //$this->getAuthService()->clearIdentity();
        $session = new Container('user');
        $session->getManager()->destroy();

        return $this->redirect()->toRoute('index', array('controller' => 'index', 'action' => 'manage'));
    }

    public function setSession($username,$password) {
        //Fetch the user's data and store it in the session...
        error_log("Inside setSession ...");
        $user = $this->getAminUserTable()->fetchAll(array('username' => $username,'password' => md5($password)));
        $user = $user->current();
        if(empty($user->user_id) || $user->role == 2 ){
            return false;
        }
        $user->password = '';
        $user->disable_account = '';
        $user->create_date = '';
        $user->update_time = '';

        $_SESSION['user']['user_id'] = $user->user_id;
        $_SESSION['user']['username'] = $user->username;
        $_SESSION['user']['role'] = $user->role;
        return true;
    }

    public function registrationAction() {
        //Fetch the post data
        $postData = $this->getRequest()->getPost()->toArray();
        $email = $postData ['email'];
        $username = $postData ['username'];
        $password = $postData ['password'];
        $invited_by = $postData ['invited_by'];
        //Setup the URL and action
        $action = 'registration';
        $xml = "<xml><registration><email>$email</email><username>$username</username><password>$password</password><invited_by>$invited_by</invited_by></registration></xml>";
        $redirect = 'event';

        //Guzzle the Registration Web Service
        $result = $this->fetchXML($action, $xml);


        $data = simplexml_load_string($result);


        //ZF2 Authenticate
        if ($data->registrationresponse->status == 'success') {
            $this->setSession($username);

            //If there's a profile pic upload it...
            if (isset($_FILES['file'])) {
                $file = $_FILES['file'];
                $fileName = $file['name'];
                $filetype = $file['type'];
                $filetmp_name = $file['tmp_name'];
                $filesize = $file['size'];

                $url = MemreasConstants::ORIGINAL_URL;
                $guzzle = new Client();
                $session = new Container('user');
                $request = $guzzle->post($url)
                        ->addPostFields(
                                array(
                                    'action' => 'addmediaevent',
                                    'user_id' => $session->offsetGet('user_id'),
                                    'filename' => $fileName,
                                    'event_id' => "",
                                    'device_id' => "",
                                    'is_profile_pic' => 1,
                                    'is_server_image' => 0,
                                )
                        )
                        ->addPostFiles(
                        array(
                            'f' => $filetmp_name,
                        )
                );
            }
            $response = $request->send();
            $data = $response->getBody(true);
            $xml = simplexml_load_string($result);
            if ($xml->addmediaeventresponse->status == 'success') {
                //Do nothing even if it fails...
            }

            //Redirect here
            return $this->redirect()->toRoute('index', array('action' => $redirect));
        } else {
            return $this->redirect()->toRoute('index', array('action' => "index"));
        }
    }

public function getUserTable() {
        if (!$this->userTable) {
            $sm = $this->getServiceLocator();
            $this->userTable = $sm->get('Application\Model\UserTable');;
        }
        return $this->userTable;
    }
      public function getAminUserTable() {
        if (!$this->userTable) {
            $sm = $this->getServiceLocator();
            $this->userTable = $sm->get('Application\Model\AdminUserTable');;
        }
        return $this->userTable;
    }

    public function forgotpasswordAction() {
        $request = $this->getRequest();
        $postData = $request->getPost()->toArray();
        $email = isset($postData ['email']) ? $postData ['email'] : '';
        //Setup the URL and action
        $action = 'forgotpassword';
        $xml = "<xml><forgotpassword><email>$email</email></forgotpassword></xml>";
        //$redirect = 'gallery';
        //Guzzle the LoginWeb Service
        $result = $this->fetchXML($action, $xml);

        $data = simplexml_load_string($result);
        echo json_encode($data);
        return '';
    }

    public function changepasswordAction() {
        $request = $this->getRequest();
        $postData = $request->getPost()->toArray();

        $new = isset($postData ['new']) ? $postData ['new'] : '';
        $retype = isset($postData ['reytpe']) ? $postData ['reytpe'] : '';
        $token = isset($postData ['token']) ? $postData ['token'] : '';

        //Setup the URL and action
        $action = 'forgotpassword';
        $xml = "<xml><changepassword><new>$new</new><retype>$retype</retype><token>$token</token></changepassword></xml>";
        //$redirect = 'gallery';
        //Guzzle the LoginWeb Service
        $result = $this->fetchXML($action, $xml);

        $data = simplexml_load_string($result);
        echo json_encode($data);
        return '';
    }

    public function getAuthService() {
        if (!$this->authservice) {
            $this->authservice = $this->getServiceLocator()
                    ->get('AuthService');
        }

        return $this->authservice;
    }

    public function getSessionStorage() {
        if (!$this->storage) {
            $this->storage = $this->getServiceLocator()
                    ->get('application\Model\MyAuthStorage');
        }

        return $this->storage;
    }

   /* public function security($path) {
        //if already login do nothing
        //$session = new Container("user");
        //if(!$session->offsetExists('user_id')){
        //	error_log("Not there so logout");
        //	$this->logoutAction();
        //  return "application/index/index.phtml";
        //}
        return $path;
        //return $this->redirect()->toRoute('index', array('action' => 'login'));
    }*/
public function showlogAction() {
     echo '<pre>' . file_get_contents(getcwd() . '/php_errors.log');
                exit();

}
public function clearlogAction() {
     unlink(getcwd().'/php_errors.log');
                error_log("Log has been cleared!");
                echo '<pre>' . file_get_contents(getcwd() . '/php_errors.log');
                exit();

}
public function manageAction() {
 //$this->security();
error_log("Enter admin " . __FUNCTION__ . PHP_EOL);
        //$path = $this->security("application/index/index.phtml");
        $path = "application/manage/index.phtml";
        $view = new ViewModel();
        $view->setTemplate($path); // path to phtml file under view folder
        return $view;
error_log("Exit admin " . __FUNCTION__ . PHP_EOL);
    }


     public function userAction() {
                    $this->security();

            $order_by = $this->params()->fromQuery('order_by', 0);
            $order    = $this->params()->fromQuery('order', 'DESC');
            $page = $this->params()->fromQuery('page', 1);

            $q    = $this->getUserName();
            $where='';
            if($q){
                $where = new \Zend\Db\Sql\Where();
                $where->like('username',"$q%");
            }
            
 
             $column = array('username','email_address','role','disable_account');
             $url_order = 'DESC';
  if (in_array($order_by, $column))
    $url_order = $order == 'DESC' ? 'ASC' : 'DESC';
     
            
        try {
        $users = $this->getUserTable()->fetchAll($where, $order_by, $order);
        
            $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($users);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            //$paginator->setItemCountPerPage(ADMIN_QUERY_LIMIT);
                      $paginator->setItemCountPerPage(MemreasConstants::NUMBER_OF_ROWS);

        
        } catch (Exception $exc) {
            
            return array();
        }
        return array('paginator' => $paginator, 'user_total' => count($users),
                      'order_by'=>$order_by,'order' => $order,'q'=>$q,'page' => $page, 'url_order'=>$url_order

          );
    

    }

     
    public function userViewAction() {
        $this->security();

        if ($this->request->isPost()) {
            $id = $this->params()->fromPost('id');
        $user = $this->getUserTable()->getUser($id);
            if(empty($id) or empty($user)){
              $this->messages[] ='User Not Found';
            } else if ($this->validate()) { 
             $postData =$this->params()->fromPost();
              $user->username = $postData['username'];
              $user->email_address = $postData['email_address'];
             // $user->facebook_username = $postData['facebook_username'];
             // $user->twitter_username = $postData['twitter_username'];
              $user->disable_account = $postData['disable_account'];

              // Save the changes

             // $this->getUserTable()->saveUser($user);
              $this->getAdminLogTable()->saveLog(array('log_type'=>'user_update', 'admin_id'=>$_SESSION['user']['user_id'], 'entity_id'=>$id));


              $this->messages[] ='Data Update sucessfully';
              $user = $this->getUserTable()->getUser($id);

            }
            
          }else{
              $id = $this->params()->fromRoute('id');
              //$user = $this->getUserTable()->getUser($id);
                             $user = $this->getUserTable()->getUserData(array('user.user_id' =>$id ));
                          //   echo '<pre>';print_r($userProfile);

          }
            
          
                  $view =  new ViewModel();
                  $view->setVariable('user',$user );
                  $view->setVariable('messages',$this->messages );
                  $view->setVariable('status',$this->status );


        return $view;
    }

function validate(){
  $result = true ;
return $result;
}
    
    
    public function userDeactiveAction() {
        
                      $this->security();

      $vdata=array();
        $request = $this->getRequest();
        if ($request->isPost()) {
             $id = $this->params()->fromPost('id');
                             $postData = $this->params()->fromPost();

             if(empty($postdata['reason'])){
              $this->status='error';
            }else{
                $this->getUserTable()->updateUser(array('disable_account' => '1'), $id);
                $this->getAdminLogTable()->saveLog(array('log_type' => 'user_deactivated', 'admin_id' => $_SESSION['user']['user_id'], 'entity_id' => $id));
               
                $this->messages[] = 'User Dactivated';
                $this->status = 'success';
            }

                
                
 
            // Redirect to list of albums
        }else{
            $id = $this->params()->fromRoute('id', 0);
             
        }
$user = $this->getUserTable()->getUser($id);
             $vdata['user'] = $user;
            $vdata['messages']= $this->messages;
            $vdata['status'] = $this->status;
        return $vdata;
    
    }
    
    public function userActiveAction() {
        
                      $this->security();

      $vdata=array();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $id = $this->params()->fromPost('id');
            $postData = $this->params()->fromPost();

            if(empty($postdata['reason'])){
              $this->status='error';
            }else{
               $this->getUserTable()->updateUser(array('disable_account' => 0), $id);
                $this->getAdminLogTable()->saveLog(array('log_type' => 'user_activated', 'admin_id' => $_SESSION['user']['user_id'], 'entity_id' => $id));
               
                $this->messages[] = 'User activated';
                $this->status = 'success'; 
            }
                
                
                
 
            // Redirect to list of albums
        }else{
            $id = $this->params()->fromRoute('id', 0);
             
        }
$user = $this->getUserTable()->getUser($id);
             $vdata['user'] = $user;
            $vdata['messages']= $this->messages;
            $vdata['status'] = $this->status;
        return $vdata;
    
    }
    
    public function feedbackAction() {
        $this->security();

      $order_by = $this->params()->fromQuery('order_by', 0);
            $order    = $this->params()->fromQuery('order', 'DESC');
            $q    = $this->getUserName();
            $where='';
            if($q){
                $where = new \Zend\Db\Sql\Where();
                $where->like('username',"$q%");
            }
             $column = array('username','create_time');
             $url_order = 'DESC';
  if (in_array($order_by, $column))
    $url_order = $order == 'DESC' ? 'ASC' : 'DESC';
                 try {
                 
        $feedback = $this->getFeedbackTable()->FetchFeedDescAll($where, $order_by, $order);
            $page = $this->params()->fromQuery('page', 1);
            $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($feedback);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            //$paginator->setItemCountPerPage(ADMIN_QUERY_LIMIT);
                      $paginator->setItemCountPerPage(MemreasConstants::NUMBER_OF_ROWS);

        
        } catch (Exception $exc) {
            
            return array();
        }
        return array('paginator' => $paginator, 'feedback_total' => count($feedback),
                      'order_by'=>$order_by,'order' => $order,'q'=>$q,'page' => $page, 'url_order'=>$url_order);

    }

    public function feedbackViewAction() {
                $this->security();

             $feedback_id = $this->params()->fromRoute('id'); 
        $this->getAdminLogTable()->saveLog(array('log_type'=>'feedback_view', 'admin_id'=>$_SESSION['user']['user_id'], 'entity_id'=>$feedback_id));

             $feedback = $this->getFeedbackTable()->getFeedback($feedback_id);

       return array('feedback' => $feedback);

  
    }
    
    protected $AdminUserTable;

     public function getAdminUserTable() {
        if (!$this->AdminUserTable) {
            $sm = $this->getServiceLocator();
            $this->AdminUserTable = $sm->get('Application\Model\AdminUserTable');
        }
        return $this->AdminUserTable;
    }

    public function adminAction() {
        $this->security();
        $order_by = $this->params()->fromQuery('order_by', 0);
        $order = $this->params()->fromQuery('order', 'DESC');
        $q    = $this->getUserName();
            $where='';
            if($q){
                $where = new \Zend\Db\Sql\Where();
                $where->like('username',"$q%");
            }
        $column = array('username', 'role', 'create_date');
        $url_order = 'DESC';
        if (in_array($order_by, $column))
            $url_order = $order == 'DESC' ? 'ASC' : 'DESC';

        try {
            //$account = $this->getAccountTable()->getAccount(array('user_id'=>$id));
            // $account_id =   $account;
            //echo '<pre>'; print_r($account->account_id); exit;


            $admin = $this->getAdminUserTable()->FetchAdmins($where, $order_by, $order);

            $page = $this->params()->fromQuery('page', 1);
            $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($admin);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            //$paginator->setItemCountPerPage(ADMIN_QUERY_LIMIT);
            $paginator->setItemCountPerPage(MemreasConstants::NUMBER_OF_ROWS);
        } catch (Exception $exc) {

            return array();
        }
        return array('paginator' => $paginator, 'admin_total' => count($admin),
            'order_by' => $order_by, 'order' => $order, 'q' => $q, 'page' => $page, 'url_order' => $url_order);
    }

    public function adminTranAction() { 
            $this->security();
             
        $user_id = $this->params()->fromRoute('id');
        $page = $this->params()->fromQuery('page', 1);
        $order_by = $this->params()->fromQuery('order_by', 0);
            $order    = $this->params()->fromQuery('order', 'DESC');
            $q    = $this->params()->fromQuery('q', 0);
            $where =array();
             $column = array('username','create_time');
             $url_order = 'DESC';

        $users_log =  $this->getAdminLogTable()->fetchAll(array('admin_id' =>$user_id));
        //$this->getAdminLogTable()->saveLog(array('log_type'=>'admin_view', 'admin_id'=>$_SESSION['user']['user_id'], 'entity_id'=>$user_id));
    //$admin = $this->getAdminUserTable()->adminLog($user_id);
             

         // echo '<pre>'; print_r($users_log); exit;
                  //  $users = $this->getAdminUserTable()->adminLog();

             $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($users_log);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            $paginator->setItemCountPerPage(10);

        
        
        return array('paginator' => $paginator, 'row' => $users_log,

       'order_by' => $order_by, 'order' => $order, 'q' => $q, 'page' => $page, 'url_order' => $url_order);
        
  
    }
     public function adminAddAction() {
               // $this->security();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $postData = $this->params()->fromPost();
            $where['email_address'] = $postData['email_address'];
                    $where['username'] = $postData['username'];
                    $userExist = $this->getAdminUserTable()->isExist($where);

                   if ($userExist) {
                        $this->messages[] = 'User Name or email already exist';
                        $this->status = 'error';
                    } else {

                        $user['username'] = $postData['username'];
            $user['email_address'] = $postData['email_address'];
            $user['password'] = md5($postData['password']);
            $user['disable_account'] = 0;
            $user['role'] = $postData['role'];

            $user_id=$this->getAdminUserTable()->saveUser($user);

            $this->getAdminLogTable()->saveLog(array('log_type' => 'admin_user_added', 'admin_id' => $_SESSION['user']['user_id'], 'entity_id' => $user_id));


            $this->messages[] = 'Data Added sucessfully';
            $to[] = $postData['email_address'];
                        $viewVar = array (
                                'email'    => $postData['email_address'],
                                'username' => $postData['username'],
                                'passwrd'  => $postData['password']
                        );
                        $viewModel = new ViewModel ( $viewVar );
                        $viewModel->setTemplate ( 'email/register' );
                        $viewRender = $this->getServiceLocator()->get ( 'ViewRenderer' );
                        $html = $viewRender->render ( $viewModel );
                        $subject = 'Welcome to Event App';
                        if (empty ( $aws_manager ))
                            $aws_manager = new AWSManagerSender ( $this->getServiceLocator() );
                        $aws_manager->sendSeSMail ( $to, $subject, $html ); //Active this line when app go live
                        $this->status = $status = 'Success';
                        $message = "Welcome to Event App. Your profile has been created.";
                    }
            
        }

        return array('status'=>$this->status,'messages'=>$this->messages);
    }

    public function adminEditAction() {
                $this->security();

        $postData = array();
        if ($this->request->isPost()) {
            $user_id = $this->params()->fromPost('user_id');
            $user = $this->getAdminUserTable()->getUser($user_id);


            if (empty($user_id) or empty($user)) {
                $this->messages[] = 'Admin Not Found';
            } else {
                $postData = $this->params()->fromPost();
                if ($user['username'] != $postData['username'] || 
                    $user['email_address'] != $postData['email_address']) {
                    //$where['email_address'] = $postData['email_address'];
                    //$where['username'] = $postData['username'];
                    //$userExist = $this->getAdminUserTable()->isExist($where);

                  /*  if ($userExist) {
                        $this->messages[] = 'User Name or email already exist';
                        $this->status = 'error';
                    } else {

                        $user['username'] = $postData['username'];
                        $user['email_address'] = $postData['email_address'];
                    }*/
                }
                if (!empty($postData['role'])) {
                    $user['role'] = $postData['role'];
                }
                if (!empty($postData['password'])) {
                    $user['password'] = md5($postData['password']);
                }

                $user['update_time'] = time();
                //$user['disable_account'] = $postData['disable_account'];
                
                // Save the changes
                if ($this->status != 'error') {
                    $this->getAdminUserTable()->saveUser($user);
                    $this->getAdminLogTable()->saveLog(array('log_type' => 'admin_info_updated', 'admin_id' => $_SESSION['user']['user_id'], 'entity_id' => $user_id));
                    $this->messages[] = 'Data Update sucessfully';
                    $user = $this->getAdminUserTable()->getUser($user_id);
                }
            }
        } else {
            $id = $this->params()->fromRoute('id');
            $user = $this->getAdminUserTable()->getUser($id);
         }



        return array('admin' => $user, 'messages' => $this->messages, 'status' => $this->status, 'post' => $postData);
    }
    
    

    

    public function adminDeactivateAction() {
                $this->security();

      $vdata=array();
        $request = $this->getRequest();
        if ($request->isPost()) {
             $id = $this->params()->fromPost('aid');
                             $postdata = $this->params()->fromPost();
//echo '<pre>';print_r($postData);exit;
             if(empty($postdata['reason'])){
             $this->status='error';
            }elseif ( $postdata['reason'] == 'other' && empty($postdata['other_reason'])){
                             $this->status='error';

            } 


            else{


                $description = $postdata['reason'];
                if( $postdata['reason'] == 'other'){
                    $description = $postdata['other_reason'];

                }
                $this->getAdminUserTable()->updateUser(array('disable_account' => '1'), $id);
                $this->getAdminLogTable()->saveLog(array('log_type' => 'admin_deactivated', 'admin_id' => $_SESSION['user']['user_id'], 'entity_id' => $id, 'description' => $description));
               
                $this->messages[] = ' Admin User Dactivated';
                $this->status = 'success';
            }

                
                
 
            // Redirect to list of albums
        }else{
            $id = $this->params()->fromRoute('id', 0);
             
        }
        error_log('user-id---'.$id);
         $user = $this->getAdminUserTable()->getUser($id);
            $vdata['user'] = $user;
            $vdata['messages']= $this->messages;
            $vdata['status'] = $this->status;
            print_r($vdata);
                  return $vdata;
    }
    
    public function adminActivateAction() {
                $this->security();

      $vdata=array();
        $request = $this->getRequest();
        if ($request->isPost()) {
             $id = $this->params()->fromPost('aid');
                             $postdata = $this->params()->fromPost();

             if(empty($postdata['reason']) ){
              $this->status='error';
            }elseif ( $postdata['reason'] == 'other' && empty($postdata['other_reason'])){
                             $this->status='error';

            } 


            else{


                $description = $postdata['reason'];
                if( $postdata['reason'] == 'other'){
                    $description = $postdata['other_reason'];

                }

             
                $this->getAdminUserTable()->updateUser(array('disable_account' => '0'), $id);
                $this->getAdminLogTable()->saveLog(array('log_type' => 'admin_activate', 'admin_id' => $_SESSION['user']['user_id'], 'entity_id' => $id));
                
                $this->messages[] = 'Admin User activated';
                $this->status = 'success';
            }

                
 
        }else{
            $id = $this->params()->fromRoute('id', 0);
        }
         $user = $this->getAdminUserTable()->getUser($id);
            $vdata['user'] = $user;
            $vdata['messages']= $this->messages;
            $vdata['status'] = $this->status;
         return $vdata;
    }
    protected $notifcationTable;

    public function getNotificationTable() {
        if (!$this->notifcationTable) {
            $sm = $this->getServiceLocator();
            $this->notifcationTable = $sm->get('Application\Model\NotficationTable');
        }
        return $this->notifcationTable;
    }
     protected $friendTable;

    public function getFriendTable() {
        if (!$this->friendTable) {
            $sm = $this->getServiceLocator();
            $this->friendTable = $sm->get('Application\Model\FriendTable');
        }
        return $this->friendTable;
    }
public function accountSummaryAction() {
            $this->security();

        try {
            $total = $this->getUserTable()->getUserRegisterCount(strtotime('01-12-2010'));
            $pastday = $this->getUserTable()->getUserRegisterCount(strtotime(' -1 day'));
            $pastweek = $this->getUserTable()->getUserRegisterCount(strtotime(' -1 week'));
            $pastmonth = $this->getUserTable()->getUserRegisterCount(strtotime('-1 month'));
            // print_r($total); exit;
            /*
            $i = $this->getUserInfoTable()->fetchAll();
            $page = $this->params()->fromQuery('page', 1);
            $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($i);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            $paginator->setItemCountPerPage(5);
            */
            $s3Total =  $this->getUserInfoTable()->getUserInfo('total-s3');
            $totalfriendsinvites = $this->getNotificationTable()->getInviteCount(strtotime('01-12-2010'));
            $totaleventfriendsinvites = $this->getNotificationTable()->getInviteCount(strtotime('01-12-2010'), 1);
            $fbpastday = $this->getFriendTable()->getOtherInviteCount(strtotime(' -1 day'),'facebook');
            $fbpastweek = $this->getFriendTable()->getOtherInviteCount(strtotime(' -1 week'),'facebook');
            $fbpastmonth = $this->getFriendTable()->getOtherInviteCount(strtotime('-1 month'),'facebook');
            $twpastday = $this->getFriendTable()->getOtherInviteCount(strtotime(' -1 day'),'twitter');
            $twpastweek = $this->getFriendTable()->getOtherInviteCount(strtotime(' -1 week'),'twitter');
            $twpastmonth = $this->getFriendTable()->getOtherInviteCount(strtotime('-1 month'),'twitter');
            $emailpastday = $this->getNotificationTable()->getEmailInviteCount(strtotime('-1 day'));
            $emailpastweek = $this->getNotificationTable()->getEmailInviteCount(strtotime('-1 week'));
            $emailpastmonth = $this->getNotificationTable()->getEmailInviteCount(strtotime('-1 month'));


            $result = $this->fetchXML('getplansstatic','<xml><getplansstatic><static>1</static></getplansstatic></xml>');
 $summaryData = simplexml_load_string($result);

//echo '<pre>';print_r($summaryData);exit;
         } catch (Exception $exc) {

            return array();
        }
        return array(
            //'paginator' => $paginator,
            'total' => $total,
            'pastday' => $pastday,
            'pastweek' => $pastweek,
            'pastmonth' => $pastmonth,
            's3Total'=> $s3Total,
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
            'summaryData'=> $summaryData
        );
    }
      public function accountUsageAction() {
        // $role = $this->security();
        $this->security();

        $order_by = $this->params()->fromQuery('order_by', 0);
        $order = $this->params()->fromQuery('order', 'DESC');
        $q    = $this->getUserName();
        $where = new \Zend\Db\Sql\Where();
            if($q){                
                $where->like('username',"$q%");
            }
            $where->notEqualTo('user_info.user_id','total-s3');
        $column = array(
            'username',
            'data_usage'
        );
        $url_order = 'DESC';
        if (in_array($order_by, $column))
            $url_order = $order == 'DESC' ? 'ASC' : 'DESC';

        try {
            $info = $this->getUserInfoTable()->userInfoAll($where, $order_by, $order);
            $page = $this->params()->fromQuery('page', 1);
            $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($info);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            $paginator->setItemCountPerPage(MemreasConstants::NUMBER_OF_ROWS);

            // $totalused = $this->getUserInfoTable()->totalPercentUsed();
            /*
             * $rec = $this->getUserInfoTable()->fetchAll(); print_r($rec); $allowed_size = $rec-> allowed_size; $data_usage=$rec-> data_usage; $totalused = $data_usage*100/allowed_size; print_r($totalused);
             */
        } catch (Exception $exc) {

            // return array();
        }
        return array(
            'paginator' => $paginator,
            'user_total' => count($info),
            'order_by' => $order_by,
            'order' => $order,
            'q' => $q,
            'page' => $page,
            'url_order' => $url_order
        );
    }
    public function orderHistoryAction() {
                $this->security();

         //  $id = $this->params()->fromRoute('id'); 
       // $this->getAdminLogTable()->saveLog(array('log_type'=>'feedback_view', 'admin_id'=>$_SESSION['user']['user_id'], 'entity_id'=>$id));
             $username=$this->getUserName();
                     $page = $this->params()->fromQuery('page', 1);

            $result = $this->fetchXML('getorderhistory',"<xml><getorderhistory><user_id>0</user_id><search_username>$username</search_username><page>$page</page><limit>15</limit></getorderhistory></xml>");
 $orderData = simplexml_load_string($result);
 //echo '<pre>';print_r($orderData); 
     return array('orderData' => $orderData,'page' => $page);

    }
    public function orderHistoryDetailAction() {
        $transaction_id = $this->params()->fromRoute('id'); 
        $this->getAdminLogTable()->saveLog(array('log_type'=>'feedback_view', 'admin_id'=>$_SESSION['user']['user_id'], 'entity_id'=>$transaction_id));
        $result = $this->fetchXML('getorder',"<xml><getorder><transaction_id>$transaction_id</transaction_id></getorder></xml>");
        $orderData = simplexml_load_string($result);
        //echo '<pre>';print_r($orderData);exit;
        return array('orderData' => $orderData);

  
    }

    public function eventAction() {
        $this->security();

         $order_by = $this->params()->fromQuery('order_by', 0);
        $order = $this->params()->fromQuery('order', 'DESC');
        $q = $this->params()->fromQuery('q', 0);
        $q    = $this->getUserName();
        $where = new \Zend\Db\Sql\Where();
            if($q){                
                $where->like('username',"$q%");
            }
            $where->equalTo('public', 1);
        $column = array('username', 'name');
        $url_order = 'DESC';
        if (in_array($order_by, $column))
            $url_order = $order == 'DESC' ? 'ASC' : 'DESC';

        try {

            $event = $this->getEventTable()->moderateFetchAll($where, $order_by, $order);
            $page = $this->params()->fromQuery('page', 1);
            $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($event);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            //$paginator->setItemCountPerPage(ADMIN_QUERY_LIMIT);
            $paginator->setItemCountPerPage(MemreasConstants::NUMBER_OF_ROWS);
        } catch (Exception $exc) {

            return array();
        }
        return array('paginator' => $paginator, 'event_total' => count($event),
            'order_by' => $order_by, 'order' => $order, 'q' => $q, 'page' => $page, 'url_order' => $url_order
        );
    }


       
     public function getEventTable() {
        if (!$this->eventTable) {
            $sm = $this->getServiceLocator();
            $this->eventTable = $sm->get('Application\Model\EventTable');
        }
        return $this->eventTable;
    }

   public function eventMediaAction() {
                $this->security();

        $event_id = $this->params()->fromRoute('id');
        $this->getAdminLogTable()->saveLog(array('log_type' => 'media_view', 'admin_id' => $_SESSION['user']['user_id'], 'entity_id' => $event_id));

        $event = $this->getEventTable()->getEventMedia($event_id);

        $view = new ViewModel();
        $view->setVariable('medias', $event);

        return $view;
    }
 public function eventChangeStatusAction() {
                $this->security();

        $eventTable = $this->getEventTable();
        $event_id = $this->params()->fromRoute('id');
        $event = $eventTable->getEvent($event_id);
        $date = strtotime(date('d-m-Y'));
        $eventStatus = 'inactive';
        if (($event->viewable_to >= $date || $event->viewable_to == '') && ($event->viewable_from <= $date || $event->viewable_from == '') && ($event->self_destruct >= $date || $event->self_destruct == '')
        )
            $eventStatus = 'active';

        return array('eventStatus' => $eventStatus, 'event' => $event);
    }
     public function eventApproveAction() {
                $this->security();


        $date1 = strtotime('today + 1year');
        $date = strtotime('NOW');
        $eventTable = $this->getEventTable();
        if ($this->request->isPost()) {
            $postdata = $this->params()->fromPost();
            if(empty($postdata['reason']) ){
                $messages[] = 'Please give reason';
              $this->status='error';
            }elseif ( $postdata['reason'] == 'other' && empty($postdata['other_reason'])){
                $messages[] = 'Please give reason';
              $this->status='error';

            } 
            else{


                $description = $postdata['reason'];
                    if( $postdata['reason'] == 'other'){
                        $description = $postdata['other_reason'];

                    }
                    $event = $eventTable->getEvent($postData['event_id']);

                    $eventStatus = 'inactive';
                    if (($event->viewable_to >= $date || $event->viewable_to == '') && ($event->viewable_from <= $date || $event->viewable_from == '') && ($event->self_destruct >= $date || $event->self_destruct == '')
                    )
                        $eventStatus = 'active';
                    $this->getAdminLogTable()->saveLog(array('log_type' => 'event_disable', 'admin_id' => $_SESSION['user']['user_id'], 'entity_id' => $postdata['event_id'], 'description' => $description));
                    $messages[] = 'Event approve succesfully';
                    $status = 'success';
               
                 
                    $eventTable->update(array('event_id' => $postdata['event_id'], 'self_destruct' => $date1), $postdata['event_id']);
                    return array('eventStatus' => $eventStatus, 'event' => $event, 'messages' => $messages, 'status' => $status);
            }
        }
 }

    public function eventDisapproveAction() {
                $this->security();

        $date1 = strtotime('today - 1 month');
        $eventTable = $this->getEventTable();
        if ($this->request->isPost()) {
            $postdata = $this->params()->fromPost();
            if(empty($postdata['reason']) ){
                $messages[] = 'Please give reason';
              $this->status='error';
            }elseif ( $postdata['reason'] == 'other' && empty($postdata['other_reason'])){
                $messages[] = 'Please give reason';
              $this->status='error';

            } 
            else{


                $description = $postdata['reason'];
                if( $postdata['reason'] == 'other'){
                    $description = $postdata['other_reason'];

                }
                $event = $eventTable->getEvent($postData['event_id']);
                $eventTable->update(array('event_id' => $postdata['event_id'], 'self_destruct' => $date1), $postdata['event_id']);
                $this->getAdminLogTable()->saveLog(array('log_type' => 'event_disable', 'admin_id' => $_SESSION['user']['user_id'], 'entity_id' => $postdata['event_id'],'description' => $description));
                $this->messages[] = 'Event disapprove succesfully';
                $this->status= 'success';    
         

            }
        return array( 'messages' => $this->messages, 'status' => $this->status);
        }
    }
    public   function getUserName()
    {$username = '';
        $q = $this->params()->fromQuery('q', 0);
        if(empty($q)){
            return 0;
        }
        $t =$q[0];
        
        if($t == '@'){
            $username =$search = substr ( $q, 1 );
        }
        return $username;
    }
    public   function getEventName()
    {
        $q = $this->params()->fromQuery('q', 0);
        if(empty($q)){
            return 0;
        }
        $t =$q[0];
        $name = '';
        if($t == '!'){
            $username =$search = substr ( $q, 1 );
        }
        return $name;
    }
    public function payoutAction() {
                $this->security();

        $action = "listpayees";
                    $page = $this->params()->fromQuery('page', 1);
 $q = $this->params()->fromQuery('q', 0);
        $t =$q[0];
        $username = '';
        if($t == '@'){
            $username =$search = substr ( $q, 1 );
        }

        $xml = "<xml><listpayees><username>$username</username><page>$page</page><limit>10</limit></listpayees></xml>";
        $result = $this->fetchXML($action, $xml);
         $data = simplexml_load_string($result);
        
     return array('listpayees' => $data,'page' => $page, 'q'=>$q);
     }
     public function payoutReasonAction() {
        return array();
     }
public function doPayoutAction() {
                $this->security();

        $action = "makepayout";
        $description = $page = $this->params()->fromPost('other_reason', '');
                $payee = $page = $this->params()->fromPost('ids',array());

 
        try {
            foreach ($payee as $account_id => $amount) {
             $xml = "<xml><makepayout><account_id>$account_id</account_id><amount>$amount</amount><description>$description</description></makepayout></xml>";
             error_log($xml);

              $result = $this->fetchXML($action, $xml);

                $data = simplexml_load_string($result);
                     $response[] = array('account_id' =>$account_id, 'status' => $data->makepayoutresponse->status ,'amount' => $amount ,'message' => $data->makepayoutresponse->message)  ;

                
        }
         
        } catch (\Exception $e) {
            
        }
         
        
     return array('response' =>$response );
     }

     public function accountAction() {
                $this->security();

        $page = $this->params()->fromQuery('page', 1);
         $username   = $this->getUserName();
         
        $result = $this->fetchXML('getorderhistory',"<xml><getorderhistory><user_id>0</user_id><search_username>$username</search_username><page>$page</page><limit>15</limit></getorderhistory></xml>");
         $orderData = simplexml_load_string($result);
      return array('orderData' => $orderData,'page' => $page);
      
     }

     public function refundAction() {
                $this->security();

        $action = "listpayees";
                    $page = $this->params()->fromQuery('page', 1);

        $xml = "<xml><listpayees><page>$page</page><limit>10</limit></listpayees></xml>";
        $result = $this->fetchXML($action, $xml);
         $data = simplexml_load_string($result);
        
     return array('listpayees' => $data,'page' => $page);
     }

     public function security()
     {
        $roles = array(
            'guest' => array(
                    'index'
            ),
           
            
            'admin' => array(
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
              //  'payout',
              //  'doPayout',
              //  'refund' 

            )
        );
        $userRole = 'guest';
        $action = $this->params('action');
error_log('reuested ---'.print_r($action,true));         
        if (isset($_SESSION['user']['role']) ) {
            switch ($_SESSION['user']['role']) {
                default:  $userRole = 'guest'; break;
                case '1': $userRole = 'admin'; break;
                case '3': $userRole = 'superadmin'; break;
                
            }
            
        }

        if($userRole  == 'superadmin'){
            return true;

        }elseif($userRole  == 'admin' && in_array($action,$roles['admin'])){
             return true;


        }elseif($userRole  == 'guest' && in_array($action, $roles['guest'])){
             return true;
        }
        
        die('<b>not autherise</>');//donot change this otherwise all action will be allowed
      }
}

// end class IndexController
