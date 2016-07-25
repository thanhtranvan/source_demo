<?php
$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup */

$installer->startSetup();

// Update table "sales_flat_quote_payment" and "sales_flat_order_payment".
$installer->run("

ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `bluecom_stripe_cc_exp_month` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `bluecom_stripe_cc_exp_year` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `bluecom_stripe_cc_number` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `bluecom_stripe_cc_type` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `bluecom_stripe_cc_cvc` VARCHAR( 255 ) NOT NULL ;



ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `bluecom_stripe_cc_exp_month` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `bluecom_stripe_cc_exp_year` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `bluecom_stripe_cc_number` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `bluecom_stripe_cc_type` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `bluecom_stripe_cc_cvc` VARCHAR( 255 ) NOT NULL ;

");

$installer->endSetup();
