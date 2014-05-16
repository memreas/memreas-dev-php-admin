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


      use Zend\Form\Element;

    
class ManageController extends AbstractActionController {

public $messages = array();
public $status ;

          
    public function indexAction() {
      // $path = $this->security("application/index/manage.phtml");

    
    $view = new ViewModel(array('xml'=>''));
    //$view->setTemplate($path); // path to phtml file under view folder
    return $view;
        //return new ViewModel();
    
    }
	 
	
}

// end class IndexController
