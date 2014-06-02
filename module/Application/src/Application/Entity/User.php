<?php
namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;
  use Zend\Form\Annotation;


/**
 * A User.
 *
 * @ORM\Entity
 * @ORM\Table(name="user")
* @Annotation\Name("user")
 * 
 */
class User  
{
    const ADMIN = '1';
    const MEMBER = '2';
    protected $inputFilter;

    /**      @var string

      * @ORM\Id
     * @ORM\Column(type="string",name="user_id");
    
	 
     */
	 
	     protected $user_id;

	 
	 /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255, nullable=false)
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Options({"label":"Username: "})
     */
    protected $username;
	
	 /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=false)
     */
   protected $password;
   
   /**
     * @var boolean
     *
* @Annotation\Type("Zend\Form\Element\Radio")
* @Annotation\Options({"label":"Disable Account:", "value_options" : {"0":"NO","1":"YES"}})
* @ORM\Column(name="disable_account", type="boolean", nullable=false)
     */

	
	protected $disable_account = '0';
	
	 
	
	 /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="string", length=255, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */

    /**
     * @var integer
     *
     * @ORM\Column(name="database_id", type="integer", nullable=false)
     */
    private $database_id =0;

   
    /**
     * @var string
     *
     * @ORM\Column(name="email_address", type="string", length=255, nullable=false)
     */
   private $email_address;

    /**
     * @var string
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Options({"label":"Role:",
          *                      "value_options" : {"2":"Member","1":"Admin"}})
     * @ORM\Column(name="role", type="string", length=20, nullable=false)
     */
    private $role;

    /**
     * @var boolean
     *
     * @ORM\Column(name="profile_photo", type="boolean", nullable=false)
     */
    private $profile_photo=0;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook_username", type="string", length=255, nullable=false)
     */
    private $facebook_username='';

    /**
     * @var string
     *
     * @ORM\Column(name="twitter_username", type="string", length=255, nullable=false)
     */
    private $twitter_username='';

     

    /**
     * @var string
     *
     * @ORM\Column(name="create_date", type="string", length=255, nullable=false)
     */
    private $create_date;

    /**
     * @var string
     *
     * @ORM\Column(name="update_time", type="string", length=255, nullable=false)
     */
    private $update_time;


   public function __set($name, $value) {

    $this->$name = $value;
  }

  public function __get($name) {
    
    return $this->$name;
  }
   

    
}