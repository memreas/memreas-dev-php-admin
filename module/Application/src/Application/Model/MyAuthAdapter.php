<?php
namespace Application\Model;

use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\Result;
use Guzzle\Http\Client;
use Zend\Session\Container;
use Application\Model\MemreasConstants;

class MyAuthAdapter implements AdapterInterface
{
    //protected $url = "http://test/";    
    protected $url = MemreasConstants::MEMREAS_WS;

    protected $username = '';
    protected $password = '';
    protected $token = '';

    /**
     * Sets username and password for authentication
     *
     * @return void
     */
    public function __construct($username='', $password='')
    {
         $this->username = $username;
        $this->password = $password;
    }

    /**
     * Performs an authentication attempt
     *
     * @return \Zend\Authentication\Result
     * @throws \Zend\Authentication\Adapter\Exception\ExceptionInterface
     *               If authentication cannot be performed
     */
    public function authenticate()
    {
         
        $guzzle = new Client();
        $action = 'login';
        $xml = '<xml><login><username>'.$this->username.'</username><password>'. $this->password.'</password></login></xml>';
        $redirect = 'memreas';
        $request = $guzzle->post(
            $this->url,
            null,
            array(
            'action' => $action,
            //'cache_me' => true,
            'xml' => $xml,
            'PHPSESSID' => $this->token,
            )
        );
        $response = $request->send();
        $response_body = $response->getBody(true);
        $data = \simplexml_load_string($response_body);
        if ($data->loginresponse->status == 'success') {
           
            $out = new Result(Result::SUCCESS,array('userid'=>(string)$data->loginresponse->userid,'identity' => $this->username,'token' => (string)$data->loginresponse->sid), array('Authentication successful.'));

        }else {
            
            $out = new Result(Result::FAILURE, NULL , array('Invalid credentials.'));
        }
        
        
        return $out;

    }
    
    public function  setUsername($username)
    {
        $this->username = $username;
    }

    public function  setPassword($password)
    {
        $this->password =$password;
        
    }
    public function  setToken($token)
    {
        $this->token =$token;
        
    }

        }

?>
