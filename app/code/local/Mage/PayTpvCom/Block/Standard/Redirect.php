<?php

class Mage_PayTpvCom_Block_Standard_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $standard = Mage::getModel('paytpvcom/standard');

        $form = new Varien_Data_Form();
        $form->setAction($standard->getPayTpvUrl())
            ->setId('paytpvcom_checkout')
            ->setName('paytpvcom_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);
        foreach ($standard->getStandardCheckoutFormFields() as $field=>$value) {
            $form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value));
        }
        $html = '<html><body>';
        $html.= $this->__('Conectando con paytpv.com. Espere un momento, por favor.');
        $html.= $form->toHtml();
        $html.= '<script type="text/javascript">document.getElementById("paytpvcom_checkout").submit();</script>';
        $html.= '</body></html>';

        return $html;
    }
}
