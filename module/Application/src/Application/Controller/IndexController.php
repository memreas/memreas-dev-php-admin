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
use Application\Controller\AWSManagerSender;
use Application\Model\MemreasConstants;


class IndexController extends AbstractActionController {

    //Updated....
    protected $url;
    protected $user_id;
    protected $storage;
    protected $authservice;
    protected $userTable;
    protected $eventTable;
    protected $mediaTable;
    protected $friendmediaTable;

    protected $userinfoTable;
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

        error_log("Inside fetch XML request url ---> " . $this->url . PHP_EOL);
        error_log("Inside fetch XML request action ---> " . $action . PHP_EOL);
        error_log("Inside fetch XML request XML ---> " . $xml . PHP_EOL);
        $request = $guzzle->post(
                $this->url, null, array(
            'action' => $action,
            //'cache_me' => true,
            'xml' => $xml,
            'PHPSESSID' => $this->getToken(),
                )
        );
        $response = $request->send();
        error_log("Inside fetch XML response ---> " . $response->getBody(true) . PHP_EOL);
        error_log("Exit fetchXML" . PHP_EOL);
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

   

    public function eventAction() {
        $path = $this->security("application/index/event.phtml");

        $action = 'listallmedia';
        $session = new Container('user');
        $xml = "<xml><listallmedia><event_id></event_id><user_id>" . $session->offsetGet('user_id') . "</user_id><device_id></device_id><limit>10</limit><page>1</page></listallmedia></xml>";
        $result = $this->fetchXML($action, $xml);

        $view = new ViewModel(array('xml' => $result));
        $view->setTemplate($path); // path to phtml file under view folder
        return $view;
        //return new ViewModel();
    }

    public function loginAction() {
        ini_set('max_execution_time', 300);
        //Fetch the post data
        $request = $this->getRequest();
        $postData = $request->getPost()->toArray();
        $username = $postData ['username'];
        $password = $postData ['password'];



error_log("Inside loginresponse setting...".print_r($postData,true).PHP_EOL);
        $this->getAuthService()->getAdapter()->setUsername($username);
        $this->getAuthService()->getAdapter()->setPassword($password);
        $token = empty($this->session->token) ? '' : $this->session->token;
        $this->getAuthService()->getAdapter()->setToken($token);
error_log("Inside loginresponse have session token...");
        $result = $this->getAuthService()->authenticate();
error_log("Inside loginresponse authenticate response --> ... ".print_r($result,true).PHP_EOL);
        $data = $result->getIdentity();

        $redirect = 'manage';
        if ($data) {
            $this->setSession($username);
error_log("Inside loginresponse sending to admin/default...");
            return $this->redirect()->toRoute('admin/default', array('controller' => 'manage', 'action' => 'index'));
        } else {
            error_log("Inside loginresponse else...");
            return $this->redirect()->toRoute('index', array('action' => "index"));
        }
    }

    public function logoutAction() {
        $this->getSessionStorage()->forgetMe();
        $this->getAuthService()->clearIdentity();
        $session = new Container('user');
        $session->getManager()->destroy();

        $view = new ViewModel();
        $view->setTemplate('application/index/index.phtml'); // path to phtml file under view folder
        return $view;
    }

    public function setSession($username) {
        //Fetch the user's data and store it in the session...
        error_log("Inside setSession ...");
        $user = $this->getUserTable()->fetchAll(array('username' => $username));
        $user = $user->current();
        $user->password = '';
        $user->disable_account = '';
        $user->create_date = '';
        $user->update_time = '';

        $_SESSION['user']['user_id'] = $user->user_id;
        $_SESSION['user']['username'] = $user->username;
        $_SESSION['user']['role'] = $user->role;
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

    public function security($path) {
        //if already login do nothing
        //$session = new Container("user");
        //if(!$session->offsetExists('user_id')){
        //	error_log("Not there so logout");
        //	$this->logoutAction();
        //  return "application/index/index.phtml";
        //}
        return $path;
        //return $this->redirect()->toRoute('index', array('action' => 'login'));
    }
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
}

// end class IndexController
