<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Application\Controller\Common;
use DoctrineModule\Paginator\Adapter\Selectable as SelectableAdapter;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
// use DoctrineORMModule\Form\Annotation\AnnotationBuilder;
use Zend\Form\Annotation;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Form\Form;
use Zend\Form\Element;
use Application\Model\User;

class ManageAdminController extends AbstractActionController {

    public $messages = array();
    public $status;
    protected $AdminUserTable;

    public function getAdminUserTable() {
        if (!$this->AdminUserTable) {
            $sm = $this->getServiceLocator();
            $this->AdminUserTable = $sm->get('Application\Model\AdminUserTable');
        }
        return $this->AdminUserTable;
    }

    protected $accountTable;

    public function getAccountTable() {
        if (!$this->accountTable) {
            $sm = $this->getServiceLocator();
            $this->accountTable = $sm->get('Application\Model\AccountTable');
        }
        return $this->accountTable;
    }

    protected $adminLogTable;

    public function getAdminLogTable() {
        if (!$this->adminLogTable) {
            $sm = $this->getServiceLocator();
            $this->adminLogTable = $sm->get('Application\Model\AdminLogTable');
        }
        return $this->adminLogTable;
    }

    public function indexAction() {
        $order_by = $this->params()->fromQuery('order_by', 0);
        $order = $this->params()->fromQuery('order', 'DESC');
        $q = $this->params()->fromQuery('q', 0);
        $where = array();
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
            $paginator->setItemCountPerPage(10);
        } catch (Exception $exc) {

            return array();
        }
        return array('paginator' => $paginator, 'admin_total' => count($admin),
            'order_by' => $order_by, 'order' => $order, 'q' => $q, 'page' => $page, 'url_order' => $url_order);
    }

     
   
    public function addadminAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $postData = $this->params()->fromPost();
            $user['username'] = $postData['username'];
            $user['email_address'] = $postData['email_address'];
            $user['password'] = $postData['password'];
            $user['disable_account'] = 1;
            $user['role'] = $postData['role'];

            $this->getAdminUserTable()->saveUser($user);




            $this->getAdminLogTable()->saveLog(array('log_type' => 'new_user_added', 'admin_id' => $_SESSION['user']['user_id'], 'entity_id' => $user->user_id));


            $this->messages[] = 'Data Added sucessfully';
        }


        $view = new ViewModel();
        //$view->setVariable('form',$form);

        return $view;
    }

    public function editAction() {
        $postData = array();
        if ($this->request->isPost()) {
            $user_id = $this->params()->fromPost('id');
            $user = $this->getAdminUserTable()->getUser($user_id);


            if (empty($user_id) or empty($user)) {
                $this->messages[] = 'Admin Not Found';
            } else {
                $postData = $this->params()->fromPost();
                if ($user['username'] != $postData['username'] || $user['email_address'] != $postData['email_address']) {
                    $where['email_address'] = $postData['email_address'];
                    $where['username'] = $postData['username'];
                    $userExist = $this->getAdminUserTable()->isExist($where);

                    if ($userExist) {
                        $this->messages[] = 'User Name or email already exist';
                        $this->status = 'error';
                    } else {

                        $user['username'] = $postData['username'];
                        $user['email_address'] = $postData['email_address'];
                    }
                }
                if (!empty($postData['role'])) {
                    $user['role'] = $postData['role'];
                }

                $user['update_time'] = time();
                $user['disable_account'] = $postData['disable_account'];
                
                // Save the changes
                if ($this->status != 'error') {
                    $this->getAdminUserTable()->saveUser($user);
                    $this->getAdminLogTable()->saveLog(array('log_type' => 'user_update', 'admin_id' => $_SESSION['user']['user_id'], 'entity_id' => $user_id));
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

    

    public function deactivateAction() {
      
        $request = $this->getRequest();
        if ($request->isPost()) {
            $del = $request->getPost('del', 'No');
            if(empty($postdata['reason'])){
              $status='error';
            }

            if ($del == 'Yes') {
                $id = $this->params()->fromPost('id');
                $this->getAdminUserTable()->updateUser(array('disable_account' => '1'), $id);
                $this->messages[] = 'User Dactivated';
            }

            // Redirect to list of albums
        }else{
              $id = $this->params()->fromRoute('id', 0);
        $user = $this->getAdminUserTable()->getUser($id);


        }

        return array(
            'id' => $id,
            'user' => $user,
            'messages' => $this->messages,
            'status' => $this->status
        );
    }

}

// end class IndexController
