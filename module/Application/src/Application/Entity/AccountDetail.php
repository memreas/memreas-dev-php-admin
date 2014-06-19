<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Application\Entity\Event;

/**
 * Media
 *
 * @ORM\Table(name="account_detail")
 * @ORM\Entity
 */
class AccountDetail {
	
	/**
	 *
	 * @var string @ORM\Column(name="account_detail_id", type="string", length=255, nullable=false)
	 *      @ORM\Id
	 *     
	 */
	private $account_detail_id;
	
	/**
	 *
	 * @var string @ORM\Column(name="account_id", type="string", length=255, nullable=false)
	 */
	private $account_id;
	
	/**
	 *
	 * @var boolean @ORM\Column(name="first_name", type="boolean", nullable=false)
	 */
	private $first_name;





	
	/**
	 *
	 * @var integer @ORM\Column(name="last_name", type="integer", nullable=false)
	 */
	private $last_name;





	
	/**
	 *
	 * @var string @ORM\Column(name="address_line_1", type="text", nullable=false)
	 */
	private $address_line_1;
	
	/**
	 *
	 * @var string @ORM\Column(name="address_line_2", type="string", length=1, nullable=false)
	 */
	private $address_line_2;





	
	/**
	 *
	 * @var string @ORM\Column(name="city", type="string", length=255, nullable=false)
	 */
	private $city;

	/**
	 *
	 * @var string @ORM\Column(name="state", type="string", length=255, nullable=false)
	 */
	private $state;

	/**
	 *
	 * @var string @ORM\Column(name="zip_code", type="string", length=255, nullable=false)
	 */
	private $zip_code;

	/**
	 *
	 * @var string @ORM\Column(name="postal_code", type="string", length=255, nullable=false)
	 */
	private $postal_code;

		/**
	 *
	 * @var string @ORM\Column(name="paypal_card_reference_id", type="string", length=255, nullable=false)
	 */
	private $paypal_card_reference_id;

		/**
	 *
	 * @var string @ORM\Column(name="paypal_email_address", type="string", length=255, nullable=false)
	 */
	private $paypal_email_address;

		/**
	 *
	 * @var string @ORM\Column(name="paypal_receiver_phone", type="string", length=255, nullable=false)
	 */
	private $paypal_receiver_phone;

		/**
	 *
	 * @var string @ORM\Column(name="paypal_receiver_id", type="string", length=255, nullable=false)
	 */
	private $paypal_receiver_id;
	
	
	public function __set($name, $value) {
		$this->$name = $value;
	}
	public function __get($name) {
		return $this->$name;
	}
	public function __construct() {
		$this->events = new \Doctrine\Common\Collections\ArrayCollection ();
	}
}
