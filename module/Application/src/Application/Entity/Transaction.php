<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Application\Entity\Event;

/**
 * Media
 *
 * @ORM\Table(name="transaction")
 * @ORM\Entity
 */
class Transaction {
	
	/**
	 *
	 * @var string @ORM\Column(name="transaction_id", type="string", length=255, nullable=false)
	 *      @ORM\Id
	 *     
	 */
	private $transaction_id;
	
	/**
	 *
	 * @var string @ORM\Column(name="account_id", type="string", length=255, nullable=false)
	 */
	private $account_id;
	
	/**
	 *
	 * @var boolean @ORM\Column(name="transaction_type", type="boolean", nullable=false)
	 */
	private $transaction_type;


   /**
	 *
	 * @var integer @ORM\Column(name="pass_fail", type="integer", nullable=false)
	 */
	private $pass_fail;


    /**
	 *
	 * @var string @ORM\Column(name="amount", type="text", nullable=false)
	 */
	private $amount;
	
	/**
	 *
	 * @var string @ORM\Column(name="currency", type="string", length=1, nullable=false)
	 */
	private $currency;

	
	/**
	 *
	 * @var string @ORM\Column(name="transaction_request", type="string", length=255, nullable=false)
	 */
	private $transaction_request;

	/**
	 *
	 * @var string @ORM\Column(name="transaction_response", type="string", length=255, nullable=false)
	 */
	private $transaction_response;

	/**
	 *
	 * @var string @ORM\Column(name="transaction_sent", type="string", length=255, nullable=false)
	 */
	private $transaction_sent;

	/**
	 *
	 * @var string @ORM\Column(name="transaction_receive", type="string", length=255, nullable=false)
	 */
	private $transaction_receive;

	/**
	 *
	 * @var string @ORM\Column(name="paypal_txn_type", type="string", length=255, nullable=false)
	 */
	private $paypal_txn_type;

	/**
	 *
	 * @var string @ORM\Column(name="paypal_txn_id", type="string", length=255, nullable=false)
	 */
	private $paypal_txn_id;

	/**
	 *
	 * @var string @ORM\Column(name="paypal_correlation_id", type="string", length=255, nullable=false)
	 */
	private $paypal_correlation_id;

	/**
	 *
	 * @var string @ORM\Column(name="paypal_ipn_track_id", type="string", length=255, nullable=false)
	 */
	private $paypal_ipn_track_id;

	/**
	 *
	 * @var string @ORM\Column(name="paypal_parent_payment_id", type="string", length=255, nullable=false)
	 */
	private $paypal_parent_payment_id;

	/**
	 *
	 * @var string @ORM\Column(name="paypal_payment_status", type="string", length=255, nullable=false)
	 */
	private $paypal_payment_status;

	/**
	 *
	 * @var string @ORM\Column(name="paypal_payment_amount", type="string", length=255, nullable=false)
	 */
	private $paypal_payment_amount;

	/**
	 *
	 * @var string @ORM\Column(name="paypal_payment_fee", type="string", length=255, nullable=false)
	 */
	private $paypal_payment_fee;

	/**
	 *
	 * @var string @ORM\Column(name="paypal_tax", type="string", length=255, nullable=false)
	 */
	private $paypal_tax;

	/**
	 *
	 * @var string @ORM\Column(name="paypal_item_name", type="string", length=255, nullable=false)
	 */
	private $paypal_item_name;

	/**
	 *
	 * @var string @ORM\Column(name="paypal_item_number", type="string", length=255, nullable=false)
	 */
	private $paypal_item_number;

	/**
	 *
	 * @var string @ORM\Column(name="paypal_payment_currency", type="string", length=255, nullable=false)
	 */
	private $paypal_payment_currency;

	/**
	 *
	 * @var string @ORM\Column(name="paypal_payer_id", type="string", length=255, nullable=false)
	 */
	private $paypal_payer_id;

	/**
	 *
	 * @var string @ORM\Column(name="paypal_payer_email", type="string", length=255, nullable=false)
	 */
	private $paypal_payer_email;

	/**
	 *
	 * @var string @ORM\Column(name="paypal_receiver_id", type="string", length=255, nullable=false)
	 */
	private $paypal_receiver_id;

	/**
	 *
	 * @var string @ORM\Column(name="paypal_receiver_email", type="string", length=255, nullable=false)
	 */
	private $paypal_receiver_email;

		
	
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
