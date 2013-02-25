<?php

/**
*Servired Standard Checkout Controller
*
*/
class Mage_PayTpvCom_StandardController extends Mage_Core_Controller_Front_Action
    implements Mage_Payment_Model_Recurring_Profile_MethodInterface {
	//
	// Flag only used for callback
	protected $_callbackAction = false;

	protected function _expireAjax() {
		if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
			$this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
			exit;
		}
	}

	/**
	 * Get singleton with servired strandard order transaction information
	 *
	 * @return PayTpvCom_Model_Standard
	 */
	public function getStandard() {
		return Mage::getSingleton('paytpvcom/standard');
	}
	/**
	* When a customer chooses PayTPVGridsa on Checkout/Payment page
	*
	*/
	public function redirectAction()
	{
	    $params =   $this->getRequest()->getParams();

	    $session = Mage::getSingleton('checkout/session');
	    $session->setPayTpvComStandardQuoteId($session->getQuoteId());

	    $state	=	Mage::getModel('paytpvcom/standard')->getConfigData('redirect_status');
	    $order	=	Mage::getModel('sales/order')->load($session->getLastOrderId());
	    $order->setState($state,$state,Mage::helper('payment')->__('Entra en TPV'),false);
	    $order->save();

	    //$this->getResponse()->setBody($this->getLayout()->createBlock('paytpvcom/standard_redirect')->toHtml());

	    //Código añadido por gridsa para permitir que se vea el iframe en la web
            $standard = Mage::getModel('paytpvcom/standard');

	    $htmliframe = '<div class="iframe_paytpv"><iframe title="titulo" src="https://www.paytpv.com/gateway/ifgateway.php?';
 	    $htmliframe .= '';

	    $numparams = count($standard->getStandardCheckoutFormFields());
	    $i = 1;
            foreach ($standard->getStandardCheckoutFormFields() as $field=>$value) {
		fwrite($fp, $field."=".$value."\n");
		if ($i == $numparams) {
			$htmliframe .= $field.'='.$value.'" ';
		} else {
			$htmliframe .= $field.'='.$value.'&';
		}
		$i++;
	    }

	    $htmliframe .= 'width="800" height="350" frameborder="0" marginheight="0"
marginwidth="0" scrolling="no" style="border: 0px solid #000000;
padding: 0px; margin: 0px"></iframe></div>';

	    Mage::app('default');

	    $pagina = Mage::app()->getWebsite()->getName();
	    $layout         = Mage::getSingleton('core/layout');

	    // head block
	    $headBlock = $layout->createBlock('page/html_head');
	    // add JS
	    $headBlock->addJs('prototype/prototype.js');
	    $headBlock->addJs('lib/ccard.js');
	    $headBlock->addJs('prototype/validation.js');
	    $headBlock->addJs('scriptaculous/builder.js');
	    $headBlock->addJs('scriptaculous/effects.js');
	    $headBlock->addJs('scriptaculous/dragdrop.js');
	    $headBlock->addJs('scriptaculous/controls.js');
	    $headBlock->addJs('scriptaculous/slider.js');
	    $headBlock->addJs('varien/js.js');
	    $headBlock->addJs('varien/form.js');
	    $headBlock->addJs('varien/menu.js');
	    $headBlock->addJs('mage/translate.js');
	    $headBlock->addJs('mage/cookies.js');
	    // add CSS
	    $headBlock->addCss('css/styles.css');
	    $headBlock->getCssJsHtml();
	    $headBlock->getIncludes();
	    // header block
	    $headerBlock = $layout->createBlock('page/html_header')->setTemplate('page/html/header.phtml');
	    // footer block
	    $footerBlock = $layout->createBlock('page/html_footer')->setTemplate('page/html/footer.phtml');
	    // the search form block
	    $searchblock = $layout->createBlock('core/template')->setTemplate('catalogsearch/form.mini.phtml');
	    // links block
            $linksBlock = $layout->createBlock('page/template_links')->setTemplate('page/template/links.phtml');
            $linksBlock->addLink($linksBlock->__('My Account'),'/customer/account/',$linksBlock->__('My Account'),'','',10);
            $linksBlock->addLink($linksBlock->__('My Cart'),'/checkout/cart/',$linksBlock->__('My Cart'),'','',10);
	    $linksBlock->addLink($linksBlock->__('Checkout'),'/checkout/',$linksBlock->__('Checkout'),'','',10);
	    if(Mage::getSingleton('customer/session')->isLoggedIn()){
    		$linksBlock->addLink($linksBlock->__('Log Out'), 'customer/account/logout', $linksBlock->__('Log Out'), true, array(), 100, 'class="last"');
	    } else {
    		$linksBlock->addLink($linksBlock->__('Log In'), 'customer/account/login', $linksBlock->__('Log In'), true, array(), 100, 'class="last"');
	    }
	    // language block
	    $language = $layout->createBlock('page/switch')->setTemplate('page/switch/languages.phtml');

	    $headerBlock->setChild('topSearch',$searchblock);
            $headerBlock->setChild('topLinks',$linksBlock);
            $headerBlock->setChild('store_language',$language);

	    $navBlock = $layout->createBlock('catalog/navigation')->setTemplate('catalog/navigation/top.phtml');


            $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">
<head><title>paytvpcom</title>';
	    $html.= $headBlock->toHtml();
	    $html.= '</head><body class=" cms-index-index cms-home"><div class="wrapper"><div class="page">';

	    $html.= $headerBlock->toHtml();
	    $html.= $navBlock->toHtml();
      	    $html.= '<div class="main-container col1-layout"><div class="main"><div class="col-main">';
	    $html.= $htmliframe;
	    $html.= '</div></div><!-- main --></div><!-- main container -->';
	    $html.= $footerBlock->toHtml();
            $html.= '</div><!-- page --></div><!-- wrapper --></body></html>';
            $this->getResponse()->setBody($html);
	    $session->unsQuoteId();
	}

	/**
	 * Comunicación del resultado desde paytpv.com
	 */
	public function resultAction()
	{
		$params =   $this->getRequest()->getParams();
		$model = Mage::getModel('paytpvcom/standard');
		$state  =   $model->getConfigData('error_status');

		$message  =  '';
		$firmaValida = false;
		if(count($params) > 0)
		{
			if($params['h'] == md5($model->getConfigData('user').$params['r'].$model->getConfigData('pass').$params["ret"]))
				$firmaValida = true;

			if($firmaValida  && $params['ret'] != "0")
			{
				$errnum = $params['ret'];
				$message = "No se pudo completar el cobro con &eacute;xito (c&oacute;digo ".$errnum.").";
				$message = Mage::helper('payment')->__($message);
				$comment = Mage::helper('payment')->__('Pedido cancelado desde paytpv.com con error #%s - %s', $errnum, $message);
			}
		}

		if(!$message) // Informacion devuelta no valida
		{
			$message = "Se produjo un error durante el proceso de compra (c&oacute;digo -1).";
			$errnum = -1;
			$message = Mage::helper('payment')->__($message);
			$comment = Mage::helper('payment')->__('Pedido cancelado con error #%s - %s', $errnum, $message);
		}

        $session = Mage::getSingleton('checkout/session');
		$order = Mage::getModel('sales/order')->load($session->getLastOrderId());

		$order->setState($state,$state,$comment,true);
		$order->save();

		$order->sendOrderUpdateEmail(true,$message);

		$session->addError($message);
		$this->_redirect('checkout/cart');

		return;
	}

    /**
     * Página a la que vuelvge el usuario
     */
    public function  reciboAction()
    {
		$model = Mage::getModel('paytpvcom/standard');
		$orderStatus = $model->getConfigData('order_status');
        	$session = Mage::getSingleton('checkout/session');

		$order = Mage::getModel('sales/order');
		$order->load(Mage::getSingleton('checkout/session')->getLastOrderId());

//		$session->addError(Mage::helper('payment')->__('Pago no realizado : %s',$session->getPayTpvComStandardQuoteId()));

        	$session->setQuoteId($session->getPayTpvComStandardQuoteId());
        	$params = $this->getRequest()->getParams();

		$firmaValida = false;
		$pagoOK = true;

        	if(count($params) > 0)
		{
			if($params['h'] == md5($model->getConfigData('user').$params['r'].$model->getConfigData('pass').$params["ret"]))
				$firmaValida = true;

            		if ($firmaValida && $params['ret'] == 0)
			{
				$session->unsErrorMessage();
				$session->addSuccess(Mage::helper('payment')->__('Pago realizado con &eacute;xito'));

				$order->setState($orderStatus, $orderStatus, $comment, true);
				$order->save();
            		}
			else
			{
				$session->addError(Mage::helper('payment')->__('Pago no realizado: %s', utf8_encode("No se pudo completar el cobro con &eacute;xito (c&oacute;digo ".$params["ret"].").")));
			}
        	}
		else
		{
			//$session->addError(Mage::helper('payment')->__('Pago no realizado: %s', utf8_encode("Se produjo un error durante el proceso de compra (c&oacute;digo -1).")));
			$session->unsErrorMessage();
			$session->addSuccess(Mage::helper('payment')->__('Pago realizado con &eacute;xito'));

			$order->setState($orderStatus, $orderStatus, $comment, true);
			//Añadido por GRIDSA para permitir envío de mail de confirmación del pedido.
			$order->sendNewOrderEmail();
			$order->setEmailSent(true);
			$order->save();
		}

		Mage::getSingleton('checkout/session')->getQuote()->setIsActive(true)->save();
		$this->_redirect('checkout/cart');
    }

    /*RECURRING PROFILES*/
    /**
     * Validate RP data
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function validateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        return $this->getStandard()->validateRecurringProfile($profile);
    }

    /**
     * Submit RP to the gateway
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @param Mage_Payment_Model_Info $paymentInfo
     */
    public function submitRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile,
        Mage_Payment_Model_Info $paymentInfo
    ) {
        $token = $paymentInfo->
            getAdditionalInformation(Mage_Paypal_Model_Express_Checkout::PAYMENT_INFO_TRANSPORT_TOKEN);
        $profile->setToken($token);
        $this->getStandard()->submitRecurringProfile($profile, $paymentInfo);
    }

    /**
     * Fetch RP details
     *
     * @param string $referenceId
     * @param Varien_Object $result
     */
    public function getRecurringProfileDetails($referenceId, Varien_Object $result)
    {
        return $this->getStandard()->getRecurringProfileDetails($referenceId, $result);
    }

    /**
     * Whether can get recurring profile details
     */
    public function canGetRecurringProfileDetails()
    {
        return true;
    }

    /**
     * Update RP data
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        return $this->getStandard()->updateRecurringProfile($profile);
    }

    public function updateRecurringProfileStatus(\Mage_Payment_Model_Recurring_Profile $profile) {

    }

}
