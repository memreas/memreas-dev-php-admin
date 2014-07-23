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

    
class AccountController extends AbstractActionController {

public $messages = array();
public $status ;
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
            //$role = $this->security();
			$order_by = $this->params()->fromQuery('order_by', 0);
            $order    = $this->params()->fromQuery('order', 'DESC');
            $q    = $this->params()->fromQuery('q', 0);
            $where =array();
             $column = array('username','data_usage');
             $url_order = 'DESC';
  if (in_array($order_by, $column))
    $url_order = $order == 'DESC' ? 'ASC' : 'DESC';

            
        try {
            $info = $this->getUserInfoTable()->userInfoAll($where, $order_by, $order );
            $page = $this->params()->fromQuery('page', 1);
            $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($info);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            $paginator->setItemCountPerPage(5);

            //$totalused = $this->getUserInfoTable()->totalPercentUsed();
/*$rec = $this->getUserInfoTable()->fetchAll();
print_r($rec);
$allowed_size = $rec-> allowed_size;
$data_usage=$rec-> data_usage;
$totalused = $data_usage*100/allowed_size;
print_r($totalused);*/
        
        } catch (Exception $exc) {
            
           // return array();
        }
        return array('paginator' => $paginator, 'user_total' => count($info),
                'order_by'=>$order_by,'order' => $order,'q'=>$q,'page' => $page,'url_order'=>$url_order
            );
    

    }

    public function AccountSummaryAction() {
         try {
        $total = $this->getUserTable()->getUserRegisterCount(strtotime('01-12-2010'));
         $pastday = $this->getUserTable()->getUserRegisterCount(strtotime(' -1 day'));
         $pastweek = $this->getUserTable()->getUserRegisterCount(strtotime(' -1 week'));
         $pastmonth = $this->getUserTable()->getUserRegisterCount(strtotime('-1 month'));
        //print_r($total); exit;
         $i = $this->getUserInfoTable()->fetchAll();
            $page = $this->params()->fromQuery('page', 1);
            $iteratorAdapter = new \Zend\Paginator\Adapter\Iterator($i);
            $paginator = new Paginator($iteratorAdapter);
            $paginator->setCurrentPageNumber($page);
            $paginator->setItemCountPerPage(5);

        
        } catch (Exception $exc) {
            
            return array();
        }
        return array('paginator' => $paginator,
        'total' => $total, 
            'pastday' => $pastday,
             'pastweek' => $pastweek, 
             'pastmonth' => $pastmonth
             );
    

    }

    protected function csvAction()
{ $columnHeaders=array('username','plan','data_usage','# of image','Avg. image size','# of video','Avg. video size',
                            '# of audio comment','Avg. audio comment size','total % used');
    $info = $this->getUserInfoTable()->userInfoAll()->toArray();
    $filename='test.csv';
    $resultset=$info;
    $view = new ViewModel();
    $view->setTemplate('download/download-csv')
         ->setVariable('results', $resultset)
         ->setTerminal(true);
        $view->setVariable(

            'columnHeaders', $columnHeaders
        );
    

    $output = $this->getServiceLocator()
                   ->get('viewrenderer')
                   ->render($view);

    $response = $this->getResponse();
    
    $headers = $response->getHeaders();
    $headers->addHeaderLine('Content-Type', 'text/csv')
            ->addHeaderLine(

                'Content-Disposition', 
                sprintf("attachment; filename=\"%s\"", $filename)
            )
            ->addHeaderLine('Accept-Ranges', 'bytes')
            ->addHeaderLine('Content-Length', strlen($output));

    $response->setContent($output);

    return $response;
}
  
	
	
}

// end class IndexController
