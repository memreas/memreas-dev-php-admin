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

    
class MediaController extends AbstractActionController {

public $messages = array();
public $status ;

          
    public function indexAction() {
                $this->db = $this->getServiceLocator()->get ( 'doctrine.entitymanager.orm_default' );
                $objectRepository = $this->db->getRepository('Application\Entity\User');

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
      $form = $builder->createForm( 'Application\Entity\User' );
      $form->setHydrator(new DoctrineHydrator($entityManager,false));
      $user =new \Application\Entity\User();
      $form->bind($user);

        $request = $this->getRequest();
        if ($request->isPost()){
          //$form->bind($student);
            $form->setData($request->getPost());
            if ($form->isValid()){
                $entityManager->persist($user);
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
	public function editAction() {
        $entityManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $repository = $entityManager->getRepository('Application\Entity\User');
        //$builder    = new AnnotationBuilder();
        //$form       = $builder->createForm('Application\Entity\User');
        //$form->bind($user);
        if ($this->request->isPost()) {
            $id = $this->params()->fromPost('id');
            $user = $repository->findOneBy(array('user_id' => $id));
            if(empty($id) or empty($user)){
              $this->messages[] ='User Not Found';
            } else if ($this->validate()) { 
             $postData =$this->params()->fromPost();
              $user->username = $postData['username'];
              $user->email_address = $postData['email_address'];
              $user->twitter_username = $postData['twitter_username'];
              $user->disable_account = $postData['disable_account'];

              // Save the changes
              try {
                    $entityManager->flush();
                            } catch (Exception $e) {
                                                         
                            }
              $this->messages[] ='Data Update sucessfully';
              $user = $repository->findOneBy(array('user_id' => $id));

            }
            
          }else{
             $id = (int)$this->params()->fromRoute('id');
             $user = $repository->findOneBy(array('user_id' => $id));

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
    
	public function deleteAction() {
       $entityManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $repository = $entityManager->getRepository('Application\Entity\Media');
        //$builder    = new AnnotationBuilder();
        //$form       = $builder->createForm('Application\Entity\User');
        //$form->bind($user);
        if ($this->request->isPost()) {
            $id = $this->params()->fromRoute('id');
            echo '<pre>';print_r($id);
            
            $media = $repository->findOneBy(array('media_id' => $id));
            echo '<pre>';print_r($media);
            if(empty($id) or empty($media)){
              $this->messages[] ='Media Not Found';
            } else   { 
             //$postData =$this->params()->fromPost();
              $entityManager->remove($media);
              $this->removeFile($media);
              // Save the changes
              try {
                    $entityManager->flush();
                  } catch (Exception $e) {

                  }
              $this->messages[] ='Data Update sucessfully';
              //$user = $repository->findOneBy(array('user_id' => $id));

            }
            
          }else{
             //$id = (int)$this->params()->fromRoute('id');
             //$user = $repository->findOneBy(array('user_id' => $id));

          }
          
                  $view =  new ViewModel();
                //  $view->setVariable('user',$user );
                  $view->setVariable('messages',$this->messages );
                  $view->setVariable('status',$this->status );


        return $view;

  
    }
	public function removeFile($media='')
  {

  }
  public function removeFromRelatedTable($media='')
  {
      
  }
   
	
}

// end class IndexController
