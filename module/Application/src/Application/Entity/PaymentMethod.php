<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Application\Entity\Event;

/**
 * Media
 *
 * @ORM\Table(name="payment_method")
 * @ORM\Entity
 */
class PaymentMethod {
	
	/**
	 *
	 * @var string @ORM\Column(name="payment_method_id", type="string", length=255, nullable=false)
	 *      @ORM\Id
	 *     
	 */
	private $payment_method_id;
	
	/**
	 *
	 * @var string @ORM\Column(name="account_id", type="string", length=255, nullable=false)
	 */
	private $account_id;
	
	/**
	 *
	 * @var boolean @ORM\Column(name="paypal_card_reference_id", type="boolean", nullable=false)
	 */
	private $paypal_card_reference_id;
	
	/**
	 *
	 * @var integer @ORM\Column(name="card_type", type="integer", nullable=false)
	 */
	private $card_type;
	
	/**
	 *
	 * @var string @ORM\Column(name="obfuscated_card_number", type="text", nullable=false)
	 */
	private $obfuscated_card_number;
	
	/**
	 *
	 * @var string @ORM\Column(name="exp_month", type="string", length=1, nullable=false)
	 */
	private $exp_month;
	
	/**
	 *
	 * @var string @ORM\Column(name="exp_year", type="string", length=255, nullable=false)
	 */
	private $exp_year;

	/**
	 *
	 * @var string @ORM\Column(name="valid_until", type="string", length=255, nullable=false)
	 */
	private $valid_until;

	/**
	 *
	 * @var string @ORM\Column(name="create_time", type="string", length=255, nullable=false)
	 */
	private $create_time;

	/**
	 *
	 * @var string @ORM\Column(name="update_time", type="string", length=255, nullable=false)
	 */
	private $update_time;

		
	
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
