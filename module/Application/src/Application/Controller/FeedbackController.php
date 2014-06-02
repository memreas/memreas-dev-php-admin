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

    
class FeedbackController extends AbstractActionController {

public $messages = array();
public $status ;

          
    public function indexAction() {
                $this->db = $this->getServiceLocator()->get ( 'doctrine.entitymanager.orm_default' );
                $objectRepository = $this->db->getRepository('Application\Entity\Feedback');



// Create the adapter
$adapter = new SelectableAdapter($objectRepository); // An object repository implements Selectable

// Create the paginator itself
$paginator = new Paginator($adapter);
    $paginator->setCurrentPageNumber((int)$this->params()->fromQuery('page', 1));

$paginator->setItemCountPerPage(5);

                
                

        return new ViewModel(
            array(
                'paginator' => $paginator 
            )
        );

    }
	public function addAction() {
      $entityManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
      
      

      $builder = new AnnotationBuilder( $entityManager);
      $form = $builder->createForm( 'Application\Entity\Feedback' );
      $form->setHydrator(new DoctrineHydrator($entityManager,false));
      $Feedback =new \Application\Entity\Feedback();
      $form->bind($Feedback);

        $request = $this->getRequest();
        if ($request->isPost()){
          //$form->bind($student);
            $form->setData($request->getPost());
            if ($form->isValid()){
                $entityManager->persist($Feedback);
                $entityManager->flush();

            }
          }

                  $send = new Element ( 'send' );
        $send->setValue ( 'Create' ); // submit
        $send->setAttributes ( array ('type' => 'submit' ) );
        $form->add ( $send );

                  $view =  new ViewModel();
                  $view->setVariable('form',$form);

  return $view;
    }
	public function viewAction() {
        $entityManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $repository = $entityManager->getRepository('Application\Entity\Feedback');
        //$builder    = new AnnotationBuilder();
        //$form       = $builder->createForm('Application\Entity\Feedback');
        //$form->bind($Feedback);
        if ($this->request->isPost()) {
            $id = $this->params()->fromPost('id');
            $Feedback = $repository->findOneBy(array('user_id' => $id));
            if(empty($id) or empty($Feedback)){
              $this->messages[] ='Feedback Not Found';
            } else if ($this->validate()) { 
             $postData =$this->params()->fromPost();
              $Feedback->username = $postData['username'];
              $Feedback->email_address = $postData['email_address'];
              $Feedback->twitter_username = $postData['twitter_username'];

              // Save the changes
              try {
                    $entityManager->flush();
                            } catch (Exception $e) {
                                                         
                            }
              $this->messages[] ='Data Update sucessfully';
              $Feedback = $repository->findOneBy(array('user_id' => $id));

            }
            
          }else{
             $id = (int)$this->params()->fromRoute('id');
             $Feedback = $repository->findOneBy(array('user_id' => $id));

          }
          
                  $view =  new ViewModel();
                  $view->setVariable('Feedback',$Feedback );
                  $view->setVariable('messages',$this->messages );
                  $view->setVariable('status',$this->status );


        return $view;
    }

function validate(){
  $result = true ;
return $result;
}
    
	public function deleteAction() {

  
    }
	
   public function init()
    {
        
    }
	
}

// end class IndexController
