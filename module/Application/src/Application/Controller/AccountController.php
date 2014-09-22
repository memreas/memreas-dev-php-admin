<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Application\Controller\Common;
use DoctrineModule\Paginator\Adapter\Selectable as SelectableAdapter;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use Zend\Form\Annotation;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Form\Element;
use Application\Controller\Container;

class AccountController extends AbstractActionController {

    public $messages = array();
    public $status;
    protected $userinfoTable;

    public function getUserInfoTable() {
        if (!$this->userinfoTable) {
            $sm = $this->getServiceLocator();
            $this->userinfoTable = $sm->get('Application\Model\UserInfoTable');
        }
        return $this->userinfoTable;
    }

    protected $userTable;

    public function getUserTable() {
        if (!$this->userTable) {
            $sm = $this->getServiceLocator();
            $this->userTable = $sm->get('Application\Model\UserTable');
        }
        return $this->userTable;
    }

    public function UsageAction() {
        // $role = $this->security();

        $order_by = $this->params()->fromQuery('order_by', 0);
        $order = $this->params()->fromQuery('order', 'DESC');
        $q = $this->params()->fromQuery('q', 0);
        $where = array();
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
            $paginator->setItemCountPerPage(5);

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

    public function AccountSummaryAction() {
        try {
            $total = $this->getUserTable()->getUserRegisterCount(strtotime('01-12-2010'));
            $pastday = $this->getUserTable()->getUserRegisterCount(strtotime(' -1 day'));
            $pastweek = $this->getUserTable()->getUserRegisterCount(strtotime(' -1 week'));
            $pastmonth = $this->getUserTable()->getUserRegisterCount(strtotime('-1 month'));
            // print_r($total); exit;
            $i = $this->getUserInfoTable()->fetchAll();
            $page = $this->params()->fromQuery('page', 1);
            $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($i);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            $paginator->setItemCountPerPage(5);
            $s3Total =  $this->getUserInfoTable()->getUserInfo('total-s3');
        } catch (Exception $exc) {

            return array();
        }
        return array(
            'paginator' => $paginator,
            'total' => $total,
            'pastday' => $pastday,
            'pastweek' => $pastweek,
            'pastmonth' => $pastmonth,
                's3Total'=> $s3Total
        );
    }

    protected function csvAction() {
        $columnHeaders = array(
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
        $info = $this->getUserInfoTable()->userInfoAll()->toArray();
        $filename = 'test.csv';
        $resultset = $info;
        $view = new ViewModel ();
        $view->setTemplate('download/download-csv')->setVariable('results', $resultset)->setTerminal(true);
        $view->setVariable(
                'columnHeaders', $columnHeaders);

        $output = $this->getServiceLocator()->get('viewrenderer')->render($view);

        $response = $this->getResponse();

        $headers = $response->getHeaders();
        $headers->addHeaderLine('Content-Type', 'text/csv')->addHeaderLine(
                'Content-Disposition', sprintf("attachment; filename=\"%s\"", $filename))->addHeaderLine('Accept-Ranges', 'bytes')->addHeaderLine('Content-Length', strlen($output));

        $response->setContent($output);

        return $response;
    }

    public function updateMediaInfoAction() {
        //$session = new Container ( 'user' );
        ini_set('max_execution_time', 500);
        $aws = new AWSManagerSender($this->getServiceLocator());
        $client = $aws->s3;
        $bucket = 'memreasdevsec';
        $user_id = '027ae498-231d-4ee2-9f1e-8eec3e508313';
        $iterator = $client->getIterator('ListObjects', array(
            'Bucket' => $bucket,
            'Prefix' => $user_id
                ));

        $image = $user_id . '/image/';
        $media = $user_id . '/media/';
        $total_used = 0.0;
        $count_image = 0;
        $count_vedio = 0;
        $count_audio = 0;
        $size_vedio = 0;
        $size_audio = 0;
        $size_audio = 0;
        $size_image = 0;

        $audioExt = array(
            'caf' => '',
            'wav' => '',
            'mp3' => '',
            'm4a' => ''
        );
        $userids = array();
        foreach ($iterator as $object) {
            $userid = stristr($object ['Key'], '/', true);
            $ext = pathinfo($object ['Key'], PATHINFO_EXTENSION);
            if (isset($userids [$userid])) {
                
            } else {
                $userids [$userid] = array(
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
                        )
                ;
            }
            $total_used = bcadd($total_used, $object ['Size']);
            $userids [$userid] ['total_used'] = bcadd($userids [$userid] ['total_used'], $object ['Size']);
            if (stripos($object ['Key'], $image) === 0) {
                // echo 'image';
                $size_image = bcadd($size_image, $object ['Size']);
                $userids [$userid] ['size_image'] = bcadd($userids [$userid] ['size_image'], $object ['Size']);

                ++$count_image;
                ++$userids [$userid] ['count_image'];
            } else if (isset($audioExt [$ext])) {
                // echo 'audio';
                $size_audio = bcadd($size_audio, $object ['Size']);
                $userids [$userid] ['size_audio'] = bcadd($userids [$userid] ['size_audio'], $object ['Size']);

                ++$count_audio;
                ++$userids [$userid] ['count_audio'];
            } else {
                // echo 'vedio';
                $size_vedio = bcadd($size_vedio, $object ['Size']);
                $userids [$userid] ['size_vedio'] = bcadd($userids [$userid] ['size_vedio'], $object ['Size']);

                ++$count_vedio;
                ++$userids [$userid] ['count_vedio'];
            }
        }
        $avg_img = empty($count_image) ? $count_image : bcdiv($size_image, $count_image, 0);
        $userids [$userid] ['avg_img'] = empty($userids [$userid] ['count_image']) ? $userids [$userid] ['count_image'] : bcdiv($userids [$userid] ['size_image'], $userids [$userid] ['count_image'], 0);
        $avg_audio = empty($count_audio) ? $count_audio : bcdiv($size_audio, $count_audio, 0);
        $userids [$userid] ['avg_audio'] = empty($userids [$userid] ['count_audio']) ? $userids [$userid] ['count_audio'] : bcdiv($userids [$userid] ['size_audio'], $userids [$userid] ['count_audio'], 0);

        $avg_vedio = empty($count_vedio) ? $count_vedio : bcdiv($size_vedio, $count_vedio, 0);
        $userids [$userid] ['avg_vedio'] = empty($userids [$userid] ['count_vedio']) ? $userids [$userid] ['count_vedio'] : bcdiv($userids [$userid] ['size_vedio'], $userids [$userid] ['count_vedio'], 0);




        foreach ($userids as $key => $row) {
            $data = array(
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
             $this->getUserInfoTable()->saveUserInfo($data);
        }
        $data = array(
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

        $this->getUserInfoTable()->saveUserInfo($data);

        exit();
    }

    public function updateUserPlanAction() {
        $userRec = $this->getUserTable()->fetchAll();
        foreach ($userRec as $user) {
            $this->getPlan($user->user_id);
        }
    }

    public function getPlan($userid = '') {
        $action = "getplans";
        $xml = "<xml><getplans><user_id>$userid</user_id></getplans></xml>";
        // $xml ="<xml><getplans><user_id>d37c751e-54a3-4eb9-88c9-472261e59629</user_id></getplans></xml>";
        //$userid=1;
        $result = Common::fetchXML($action, $xml);
        $data = simplexml_load_string($result);
        $planSize = array(
            'PLAN_A_2GB_MONTHLY'   => '2000000000',
            'PLAN_B_10GB_MONTHLY'  =>'10000000000' ,
            'PLAN_C_50GB_MONTHLY'  =>'50000000000',
            'PLAN_C_100GB_MONTHLY' =>'100000000000',

            
            );
        $plan = trim($data->getplansresponse->plan_id);
        $status = trim($data->getplansresponse->status);
        if ($status == 'Success') {
            $row['allowed_size'] = $planSize[$plan];
            $row['plan'] = $plan;
            $row['user_id'] = $userid;
            $this->getUserInfoTable()->saveUserInfo($row);
        }
    }

}

// end class IndexController
