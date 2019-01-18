<?php
/**
 * Rave Gateway
 *
 * @package blesta
 * @subpackage blesta.components.gateways.nonmerchant_demo
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Rave extends NonmerchantGateway {
	/**
	 * @var string The version of this gateway
	 */
	private static $version = "1.0.0";
	/**
	 * @var string The authors of this gateway
	 */
	private static $authors = array(array('name' => "Emmajiugo", 'url' => "http://www.github.com/emmajiugo"));
	/**
	 * @var array An array of meta data for this gateway
	 */
	private $meta;
	/**
     * @var string The URL to post payments to in developer mode
     */
    private $test_url = 'https://ravesandboxapi.flutterwave.com';
    /**
     * @var string The URL to use when communicating with the live Rave API
     */
    private $live_url = 'https://api.ravepay.co';
	
	
	/**
	 * Construct a new merchant gateway
	 */
	public function __construct() {
		
		// Load components required by this gateway
		Loader::loadComponents($this, array("Input"));
		
		// Load the language required by this gateway
		Language::loadLang("rave", null, dirname(__FILE__) . DS . "language" . DS);
		
		$this->loadConfig(dirname(__FILE__) . DS . 'config.json');
	}
	
	/**
	 * Returns the name of this gateway
	 *
	 * @return string The common name of this gateway
	 */
	public function getName() {
		return Language::_("Rave.name", true);
	}
	
	/**
	 * Returns the version of this gateway
	 *
	 * @return string The current version of this gateway
	 */
	public function getVersion() {
		return self::$version;
	}

	/**
	 * Returns the name and URL for the authors of this gateway
	 *
	 * @return array The name and URL of the authors of this gateway
	 */
	public function getAuthors() {
		return self::$authors;
	}
	
	/**
	 * Return all currencies supported by this gateway
	 *
	 * @return array A numerically indexed array containing all currency codes (ISO 4217 format) this gateway supports
	 */
	public function getCurrencies() {
		return array("NGN", "USD", "EUR", "GBP", "UGX", "TZS", "ZAR", "GHS", "KES");
	}
	
	/**
	 * Sets the currency code to be used for all subsequent payments
	 *
	 * @param string $currency The ISO 4217 currency code to be used for subsequent payments
	 */
	public function setCurrency($currency) {
		$this->currency = $currency;
	}
	
	/**
	 * Create and return the view content required to modify the settings of this gateway
	 *
	 * @param array $meta An array of meta (settings) data belonging to this gateway
	 * @return string HTML content containing the fields to update the meta data for this gateway
	 */
	public function getSettings(array $meta=null) {
		$this->view = $this->makeView("settings", "default", str_replace(ROOTWEBDIR, "", dirname(__FILE__) . DS));

		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html"));

		$this->view->set("meta", $meta);
		
		return $this->view->fetch();
	}
	
	/**
	 * Validates the given meta (settings) data to be updated for this gateway
	 *
	 * @param array $meta An array of meta (settings) data to be updated for this gateway
	 * @return array The meta data to be updated in the database for this gateway, or reset into the form on failure
	 */
	public function editSettings(array $meta) {
		// Verify meta data is valid
		$rules = array(
			'live_public_key' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Rave.!error.live_public_key', true)
                ]
            ],
            'live_secret_key' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Rave.!error.live_secret_key', true)
                ]
			],
			'test_public_key' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Rave.!error.test_public_key', true)
                ]
            ],
            'test_secret_key' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Rave.!error.test_secret_key', true)
                ]
            ],
            'live_mode' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['true', 'false']],
                    'message' => Language::_('Rave.!error.live_mode.valid', true)
                ]
            ]
			
			#
			# TODO: Do error checking on any other fields that require it
			#
			
		);
		
		$this->Input->setRules($rules);
		
		// Validate the given meta data to ensure it meets the requirements
		$this->Input->validates($meta);
		// Return the meta data, no changes required regardless of success or failure for this gateway
		return $meta;
	}
	
	/**
	 * Returns an array of all fields to encrypt when storing in the database
	 *
	 * @return array An array of the field names to encrypt when storing in the database
	 */
	public function encryptableFields() {
		
		#
		# TODO: return an array of all meta field names to store encrypted
		#
		
		return ['live_public_key', 'live_secret_key'];
	}
	
	/**
	 * Sets the meta data for this particular gateway
	 *
	 * @param array $meta An array of meta data to set for this gateway
	 */
	public function setMeta(array $meta=null) {
		$this->meta = $meta;
	}
	
	/**
	 * Returns all HTML markup required to render an authorization and capture payment form
	 *
	 * @param array $contact_info An array of contact info including:
	 * 	- id The contact ID
	 * 	- client_id The ID of the client this contact belongs to
	 * 	- user_id The user ID this contact belongs to (if any)
	 * 	- contact_type The type of contact
	 * 	- contact_type_id The ID of the contact type
	 * 	- first_name The first name on the contact
	 * 	- last_name The last name on the contact
	 * 	- title The title of the contact
	 * 	- company The company name of the contact
	 * 	- address1 The address 1 line of the contact
	 * 	- address2 The address 2 line of the contact
	 * 	- city The city of the contact
	 * 	- state An array of state info including:
	 * 		- code The 2 or 3-character state code
	 * 		- name The local name of the country
	 * 	- country An array of country info including:
	 * 		- alpha2 The 2-character country code
	 * 		- alpha3 The 3-cahracter country code
	 * 		- name The english name of the country
	 * 		- alt_name The local name of the country
	 * 	- zip The zip/postal code of the contact
	 * @param float $amount The amount to charge this contact
	 * @param array $invoice_amounts An array of invoices, each containing:
	 * 	- id The ID of the invoice being processed
	 * 	- amount The amount being processed for this invoice (which is included in $amount)
	 * @param array $options An array of options including:
	 * 	- description The Description of the charge
	 * 	- return_url The URL to redirect users to after a successful payment
	 * 	- recur An array of recurring info including:
	 * 		- amount The amount to recur
	 * 		- term The term to recur
	 * 		- period The recurring period (day, week, month, year, onetime) used in conjunction with term in order to determine the next recurring payment
	 * @return string HTML markup required to render an authorization and capture payment form
	 */
	/*public function buildProcess(array $contact_info, $amount, array $invoice_amounts=null, array $options=null) {
		$this->view = $this->makeView("process", "default", str_replace(ROOTWEBDIR, "", dirname(__FILE__) . DS));

		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html"));

		$fields = array();
		$post_to = "";
		
		#
		# TODO: Define all form fields and the $post_to fields
		#

		$this->view->set("post_to", $post_to);
		$this->view->set("fields", $fields);
		
		return $this->view->fetch();
	}*/
	public function buildProcess(array $contact_info, $amount, array $invoice_amounts = null, array $options = null)
    {
        // Load the models required
        Loader::loadModels($this, ['Clients']);

		$client = $this->Clients->get($contact_info['client_id']);
		
		// Get the url to send params
		if ($this->meta['live_mode']) {
			$url = $this->live_url."/flwv3-pug/getpaidx/api/v2/hosted/pay";
			$pkey = $this->meta['live_public_key'];
			// $skey = $this->meta['live_secret_key'];
		} else {
			$url = $this->test_url."/flwv3-pug/getpaidx/api/v2/hosted/pay";
			$pkey = $this->meta['test_public_key'];
			// $skey = $this->meta['test_secret_key'];
		}

		// set parameter to send to API
        $params = [
			'amount'=>$amount,
			'customer_email'=>$client->email,
			'currency'=>$this->currency,
			'txref'=>"BLESTA-".time(),
			'PBFPubKey'=>$pkey,
			'redirect_url'=>$this->ifSet($options['return_url']),
		];		

        // Load the Rave Standard
        $hosted_url = $this->postCURL($url, $params);

        return $this->buildForm($hosted_url);
    }

    /**
     * Builds the HTML form.
     *
     * @return string The HTML form
     */
    private function buildForm($post_to)
    {
        $this->view = $this->makeView('process', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('post_to', $post_to);
        return $this->view->fetch();
	}
	
	/**
	 * CURL
	 */
	public function postCURL($url, $params) {

		$curl = curl_init();
		curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => json_encode($params),
		CURLOPT_HTTPHEADER => [
			"content-type: application/json",
			"cache-control: no-cache"
		],
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		if($err){
			// there was an error contacting the rave API
			die('Curl returned error: ' . $err);
		}

		$transaction = json_decode($response);

		if(!$transaction->data && !$transaction->data->link){
			// there was an error from the API
			print_r('API returned error: ' . $transaction->message);
		}

		// uncomment out this line if you want to redirect the user to the payment page
		//print_r($transaction->data->message);


		// redirect to page so User can pay
		// uncomment this line to allow the user redirect to the payment page
		// header('Location: ' . $transaction->data->link);
		return $transaction->data->link;
	}


	/**
	 * Validates the incoming POST/GET response from the gateway to ensure it is
	 * legitimate and can be trusted.
	 *
	 * @param array $get The GET data for this request
	 * @param array $post The POST data for this request
	 * @return array An array of transaction data, sets any errors using Input if the data fails to validate
	 *  - client_id The ID of the client that attempted the payment
	 *  - amount The amount of the payment
	 *  - currency The currency of the payment
	 *  - invoices An array of invoices and the amount the payment should be applied to (if any) including:
	 *  	- id The ID of the invoice to apply to
	 *  	- amount The amount to apply to the invoice
	 * 	- status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
	 * 	- reference_id The reference ID for gateway-only use with this transaction (optional)
	 * 	- transaction_id The ID returned by the gateway to identify this transaction
	 */
	public function validate(array $get, array $post) {

		#
		# TODO: Verify the get/post data, then return the transaction
		#
		#

		// Log the successful response
		$this->log($this->ifSet($_SERVER['REQUEST_URI']), serialize($post), "output", true);
		
		return array();
	}
	
	/**
	 * Returns data regarding a success transaction. This method is invoked when
	 * a client returns from the non-merchant gateway's web site back to Blesta.
	 *
	 * @param array $get The GET data for this request
	 * @param array $post The POST data for this request
	 * @return array An array of transaction data, may set errors using Input if the data appears invalid
	 *  - client_id The ID of the client that attempted the payment
	 *  - amount The amount of the payment
	 *  - currency The currency of the payment
	 *  - invoices An array of invoices and the amount the payment should be applied to (if any) including:
	 *  	- id The ID of the invoice to apply to
	 *  	- amount The amount to apply to the invoice
	 * 	- status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
	 * 	- transaction_id The ID returned by the gateway to identify this transaction
	 */
	public function success(array $get, array $post) {
		
		#
		# TODO: Return transaction data, if possible
		#
		
		$this->Input->setErrors($this->getCommonError("unsupported"));
	}
	
	/**
	 * Captures a previously authorized payment
	 *
	 * @param string $reference_id The reference ID for the previously authorized transaction
	 * @param string $transaction_id The transaction ID for the previously authorized transaction
	 * @return array An array of transaction data including:
	 * 	- status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
	 * 	- reference_id The reference ID for gateway-only use with this transaction (optional)
	 * 	- transaction_id The ID returned by the remote gateway to identify this transaction
	 * 	- message The message to be displayed in the interface in addition to the standard message for this transaction status (optional)
	 */
	public function capture($reference_id, $transaction_id, $amount, array $invoice_amounts=null) {
		
		#
		# TODO: Return transaction data, if possible
		#
		
		$this->Input->setErrors($this->getCommonError("unsupported"));
	}
	
	/**
	 * Void a payment or authorization
	 *
	 * @param string $reference_id The reference ID for the previously submitted transaction
	 * @param string $transaction_id The transaction ID for the previously submitted transaction
	 * @param string $notes Notes about the void that may be sent to the client by the gateway
	 * @return array An array of transaction data including:
	 * 	- status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
	 * 	- reference_id The reference ID for gateway-only use with this transaction (optional)
	 * 	- transaction_id The ID returned by the remote gateway to identify this transaction
	 * 	- message The message to be displayed in the interface in addition to the standard message for this transaction status (optional)
	 */
	public function void($reference_id, $transaction_id, $notes=null) {
		
		#
		# TODO: Return transaction data, if possible
		#
		
		$this->Input->setErrors($this->getCommonError("unsupported"));
	}
	
	/**
	 * Refund a payment
	 *
	 * @param string $reference_id The reference ID for the previously submitted transaction
	 * @param string $transaction_id The transaction ID for the previously submitted transaction
	 * @param float $amount The amount to refund this card
	 * @param string $notes Notes about the refund that may be sent to the client by the gateway
	 * @return array An array of transaction data including:
	 * 	- status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
	 * 	- reference_id The reference ID for gateway-only use with this transaction (optional)
	 * 	- transaction_id The ID returned by the remote gateway to identify this transaction
	 * 	- message The message to be displayed in the interface in addition to the standard message for this transaction status (optional)
	 */
	public function refund($reference_id, $transaction_id, $amount, $notes=null) {
		
		#
		# TODO: Return transaction data, if possible
		#
		
		$this->Input->setErrors($this->getCommonError("unsupported"));
	}
	
}
?>