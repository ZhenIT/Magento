<?php
/**
* Our test CC module adapter
*/

class Mage_PayTpvCom_Model_Standard extends Mage_Payment_Model_Method_Abstract
    implements Mage_Payment_Model_Recurring_Profile_MethodInterface
{

    /**
    * unique internal payment method identifier
    *
    * @var string [a-z0-9_]
    */

    protected $_code = 'paytpvcom';


    protected   $_formBlockType = 'paytpvcom/standard_form';

    protected   $_allowCurrencyCode = array('EUR');

    /**
    * Get paytpv.com session namespace
    *
    * @return paytpv.com_Model_Session
    */
    public function getSession() {
      return  Mage::getSingleton('paytpvcom/session');
    }

    /**
    * Get checkout session namespace
    *
    * @return Checkout_Model_Session
    */
    public function getCheckout() {
        return  Mage::getSingleton('checkout/session');
    }

    /**
    * Get current quote
    *
    * @return Mage_Sales_Model_Quote
    */
    public function getQuote() {
        return  $this->getCheckout()->getQuote();
    }

    /**
    * Using internal pages for input payment data
    *
    * @return bool
    */
    public function canUseInternal() {
        return  false;
    }

    /**
    * Using for multiple shipping address
    *
    * @return bool
    */
    public function canUseForMultishipping() {
        return  true;
    }

    public function createFormBlock($name) {
        $block  = $this->getLayout()->createBlock('paytpvcom/form', $name);
		$block->setMethod('paytpvcom')
            ->setPayment($this->getPayment())
            ->setTemplate('paytpvcom/form.phtml');

        return  $block;
    }

    /*validate the currency code is avaialable to use for paytpvcom or not*/
    public function validate() {
        parent::validate();
        $currency_code = $this->getQuote()->getBaseCurrencyCode();
        if(!in_array($currency_code,$this->_allowCurrencyCode)) {
            Mage::throwException(Mage::helper('payment')->__('El codigo de moneda seleccionado (%s) no es compatible con paytpv.com',$currency_code));
        }
        return  $this;
    }

    public function onOrderValidate(Mage_Sales_Model_Order_Payment $payment) {
        return  $this;
    }

    public function onInvoiceCreate(Mage_Sales_Model_Invoice_Payment $payment) {
    }

    public function canCapture() {
        return true;
    }

    public function getOrderPlaceRedirectUrl() {
/*      $state  = $this->getConfigData('redirect_status');
      $order = Mage::getModel('sales/order');
      $order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
      $order->setState($state,$state,Mage::helper('payment')->__('Entra en TPV'),false);
      $order->save();
      return $this->getPayTpvUrl()."?".http_build_query($this->getStandardCheckoutFormFields());
*/
      return Mage::getUrl('paytpvcom/standard/redirect');
    }



    function calcLanguage($lan) {
        $res = "";
        switch($lan) {
            case "es_ES": return "es";
            case "fr_FR": return "fr";
            case "en_GB": return "en";
            case "en_US": return "en";
            case "ca_ES": return "ca";
        }
        return "es";
    }

    public function getStandardCheckoutFormFields()
	{
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());

		$convertor = Mage::getModel('sales/convert_order');
		$invoice = $convertor->toInvoice($order);

		$amount = $order->getTotalDue() * 100;
		$ord = $this->getCheckout()->getLastRealOrderId();
		$currency = "EUR";//strtoupper($this->convertTopaytpvcomCurrency($order->getOrderCurrency()));

		$client = $this->getConfigData('client');
		$user = $this->getConfigData('user');
		$pass = $this->getConfigData('pass');
		$terminal = $this->getConfigData('terminal');

		//Añadido por GRIDSA para indicar el idioma
 	        $pagina = Mage::app()->getWebsite()->getName();
		$language_settings = Mage::app()->getStore()->getCode();
		$language_settings = strtolower($language_settings);

		if ( $language_settings == "default" ) {
			$language = "ES";
		} else {
			$language = "EN";
		}

		$operation = "1";

		$signature = md5($client.$user.$terminal."1".$ord.$amount.$currency.md5($pass));

        $sArr = array
		(
			'ACCOUNT' => $client,
			'USERCODE' => $user,
			'TERMINAL' => $terminal,
			'OPERATION' => $operation,
			'REFERENCE' => $ord,
            'AMOUNT' => round($order->getTotalDue() * 100),    // convert to minor units
			'CURRENCY' => $currency,
			'SIGNATURE' => $signature,
			'CONCEPT' => '',
			'LANGUAGE' => $language,
			'URLOK' => Mage::getUrl('paytpvcom/standard/recibo'),
			'URLKO' => Mage::getUrl('paytpvcom/standard/result')
		);
        //
        // Make into request data
        //
        $sReq = '';
        $rArr = array();
        foreach ($sArr as $k=>$v) {
            /* replacing & char with and. otherwise it will break the post */
            $value =  str_replace("&","and",$v);
            $rArr[$k] =  $value;
            $sReq .= '&'.$k.'='.$value;
        }

        return $rArr;
    }

    //
    // Simply return the url for the paytpv.com Payment window
    //
    public function getPayTpvUrl()
	{
		return "http://www.paytpv.com/gateway/fsgateway.php";
    }

    /*RECURRING PROFILES*/
    /**
     * Validate RP data
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @throws Mage_Core_Exception
     */
    public function validateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        $errors = array();
        if (strlen($profile->getSubscriberName()) > 32) { // up to 32 single-byte chars
            $errors[] = Mage::helper('paypal')->__('Subscriber name is too long.');
        }
        $refId = $profile->getInternalReferenceId(); // up to 127 single-byte alphanumeric
        if (strlen($refId) > 127) { //  || !preg_match('/^[a-z\d\s]+$/i', $refId)
            $errors[] = Mage::helper('paypal')->__('Merchant reference ID format is not supported.');
        }
        $scheduleDescr = $profile->getScheduleDescription(); // up to 127 single-byte alphanumeric
        if (strlen($refId) > 127) { //  || !preg_match('/^[a-z\d\s]+$/i', $scheduleDescr)
            $errors[] = Mage::helper('paypal')->__('Schedule description is too long.');
        }
        if ($errors) {
            Mage::throwException(implode(' ', $errors));
        }
    }

    /**
     * Submit RP to the gateway
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @param Mage_Payment_Model_Info $paymentInfo
     * @throws Mage_Core_Exception
     */
    public function submitRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile,
        Mage_Payment_Model_Info $paymentInfo
    ) {
	$client = new Zend_Soap_Client("https://www.paytpv.com/gateway/xml_bankstore.php?wsdl",
	    array('compression' => SOAP_COMPRESSION_ACCEPT));
	exit();
        $api = $this->getApi();
        Varien_Object_Mapper::accumulateByMap($profile, $api, array(
            'token', // EC fields
            // TODO: DP fields
            // profile fields
            'subscriber_name', 'start_datetime', 'internal_reference_id', 'schedule_description',
            'suspension_threshold', 'bill_failed_later', 'period_unit', 'period_frequency', 'period_max_cycles',
            'billing_amount' => 'amount', 'trial_period_unit', 'trial_period_frequency', 'trial_period_max_cycles',
            'trial_billing_amount', 'currency_code', 'shipping_amount', 'tax_amount', 'init_amount', 'init_may_fail',
        ));
        $api->callCreateRecurringPaymentsProfile();
        $profile->setReferenceId($api->getRecurringProfileId());
        if ($api->getIsProfileActive()) {
            $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE);
        } elseif ($api->getIsProfilePending()) {
            $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_PENDING);
        }
    }

    /**
     * Fetch RP details
     *
     * @param string $referenceId
     * @param Varien_Object $result
     */
    public function getRecurringProfileDetails($referenceId, Varien_Object $result)
    {
        $api = $this->getApi();
        $api->setRecurringProfileId($referenceId)
            ->callGetRecurringPaymentsProfileDetails($result)
        ;
    }

    /**
     * Update RP data
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {

    }

    /**
     * Manage status
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfileStatus(Mage_Payment_Model_Recurring_Profile $profile)
    {
        $api = $this->getApi();
        $action = null;
        switch ($profile->getNewState()) {
            case Mage_Sales_Model_Recurring_Profile::STATE_CANCELED: $action = 'cancel'; break;
            case Mage_Sales_Model_Recurring_Profile::STATE_SUSPENDED: $action = 'suspend'; break;
            case Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE: $action = 'activate'; break;
        }
        $state = $profile->getState();
        $api->setRecurringProfileId($profile->getReferenceId())
            ->setIsAlreadyCanceled($state == Mage_Sales_Model_Recurring_Profile::STATE_CANCELED)
            ->setIsAlreadySuspended($state == Mage_Sales_Model_Recurring_Profile::STATE_SUSPENDED)
            ->setIsAlreadyActive($state == Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE)
            ->setAction($action)
            ->callManageRecurringPaymentsProfileStatus()
        ;
    }

    public function canGetRecurringProfileDetails() {

    }

 }
 ?>
