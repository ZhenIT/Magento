INSTALLATION GUIDE

Uncompress module to your magento root directory
Copy all module template files (xml layout file + template folder) from the module to your custom template directory
Flush your magento cache
Log out and log back to admin 
 

The template files to be copied are :
- frontend/default/default/layout/j2tautoadd.xml
- frontend/default/default/template/j2tautoadd


If you are using the product list feature in the shopping cart, please add the following code to your cart.phtml template:

<!-- J2T AUTOADD -->
<?php echo Mage::helper('autoadd')->getExtraProducts();?>

This code should be added right after <tfoot> tag of the cart list table.

In order to be able to see crossed prices Free product feature, modify your checkout/cart/item/default.phtml and checkout/onepage/review/item.phtml design template file as the following:

  - find <?php echo $this->helper('checkout')->formatPrice($_item->getRowTotal()+$_item->getWeeeTaxAppliedRowAmount()+$_item->getWeeeTaxRowDisposition()); ?>
	>> add // before echo
	>> add this right after ?>:
		<!-- J2T AUTOADD UPDATE -->
		<?php echo $this->helper('autoadd/cart')->formatPrice(($_item->getRowTotal()+$_item->getWeeeTaxAppliedRowAmount()+$_item->getWeeeTaxRowDisposition()), $_item, true) ?>

  - find <?php echo $this->helper('checkout')->formatPrice($_item->getRowTotal()) ?>
	>> add // before echo
	>> add this right after ?>:
		<!-- J2T AUTOADD UPDATE -->
		<?php echo $this->helper('autoadd/cart')->formatPrice($_item->getRowTotal(), $_item, true) ?>

  - find <?php echo $this->helper('checkout')->formatPrice($_item->getCalculationPrice()+$_item->getWeeeTaxAppliedAmount()+$_item->getWeeeTaxDisposition()); ?>
	>> add // before echo
        >> add this right after ?>:
		<!-- J2T AUTOADD UPDATE -->
		<?php echo $this->helper('autoadd/cart')->formatPrice(($_item->getCalculationPrice() + $_item->getWeeeTaxAppliedAmount()+$_item->getWeeeTaxDisposition()), $_item) ?>

  - find <?php echo $this->helper('checkout')->formatPrice($_item->getCalculationPrice()) ?>
	>> add // before echo
        >> add this right after ?>:
		<!-- J2T AUTOADD UPDATE -->
		<?php echo $this->helper('autoadd/cart')->formatPrice($_item->getCalculationPrice(), $_item) ?>

UNINSTALL PROCEDURE
  - undo modifications of your template phtml files
  - replace 'true' by 'false' in app/etc/modules/J2t_Autoadd.xml
