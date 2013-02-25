<?php
class Mage_PayTpvCom_Model_System_Config_Source_Entorno {
	public function toOptionArray(){
		return array(
            array('value'=>0, 'label'=>Mage::helper('adminhtml')->__('Real')),
            array('value'=>1, 'label'=>Mage::helper('adminhtml')->__('Pruebas')),
		);
	}
}
?>
