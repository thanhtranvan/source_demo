<?php

require_once 'Mage/Checkout/controllers/CartController.php';
class Bluecom_Magento1_CartController extends Mage_Checkout_CartController
{
	public function addAction()
	{
		$cart   = $this->_getCart();
		$params = $this->getRequest()->getParams();
		if($params['isAjax'] == 1){
			$response = array();
			$response['cart_url'] = Mage::getUrl('checkout/cart');
			$response['redirect_status'] = Mage::getStoreConfig('bluecom/all_settings/checkout_cart_redirect');
			$response['redirect_timeout'] = Mage::getStoreConfig('bluecom/all_settings/checkout_cart_redirect_timeout');
			$response['show_pop_up'] = Mage::getStoreConfig('bluecom/all_settings/show_pop_up_after_add');

			try {
				if (isset($params['qty'])) {
					$filter = new Zend_Filter_LocalizedToNormalized(
					array('locale' => Mage::app()->getLocale()->getLocaleCode())
					);
					$params['qty'] = $filter->filter($params['qty']);
				}

				$session = $this->_getSession();
	            //if button not contains id in url - load id by url
	            if(array_key_exists('url', $params)) {
	                $productUrl = str_replace(Mage::getBaseUrl(), "", $params['url']);
	                $productId = Mage::getModel('core/url_rewrite')->setStoreId(Mage::app()->getStore()->getId())->loadByRequestPath($productUrl)->getProductId();
	                $product = Mage::getModel('catalog/product')->load($productId);
	            } elseif($wishlistItem = (int)$this->getRequest()->getParam('item')) { //if press button add to cart from sidebar wishlist
	                $item = Mage::getModel('wishlist/item')->load($wishlistItem);
	                $product = Mage::getModel('catalog/product')->load($item->getProductId());

	                //Add to session wishlist id. It be useful in future if product has options
	                $session->setWishlistItem($wishlistItem);
	            } else {
	                $product = $this->_initProduct();
	            }

	            /**
	             *  If product Type_Grouped create another response, but if request from option icon
	             *  not redirect twice
	             */
	            if($product->getTypeInstance(true) instanceof Mage_Catalog_Model_Product_Type_Grouped){
	                if(!array_key_exists('ajaxAdd', $params)) {
	                    $url = Mage::getUrl('*/*/options') . 'product_id/' . $product->getEntityId();
	                    $response['status'] = 'SUCCESS';
	                    $response['options_url'] = $url;
	                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
	                    var_dump("test product");die;
	                    return;
	                }
	            }
	            /**
	             *  If product have options create another response, but if request from option icon
	             *  not redirect twice
	             */
	            if($product->getTypeInstance(true)->hasOptions($product) && !array_key_exists('qty', $params)) {
	                $url = Mage::getUrl('*/*/options') . 'product_id/' . $product->getEntityId();
	                $response['status'] = 'SUCCESS';
	                $response['options_url'] = $url;
	                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
	                return;
	            }

				$related = $this->getRequest()->getParam('related_product');

				/**
				 * Check product availability
				 */
				if (!$product) {
					$response['status'] = 'ERROR';
					$response['message'] = $this->__('Unable to find Product ID');
				}

				$cart->addProduct($product, $params);
				if (!empty($related)) {
					$cart->addProductsByIds(explode(',', $related));
				}

				$cart->save();

				$this->_getSession()->setCartWasUpdated(true);

				/**
				 * @todo remove wishlist observer processAddToCart
				 */
				Mage::dispatchEvent('checkout_cart_add_product_complete',
				array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
				);

				if (!$cart->getQuote()->getHasError()){
					$message = $this->__('%s was added to your shopping cart.', Mage::helper('core')->htmlEscape($product->getName()));
					$response['status'] = 'SUCCESS';
					$response['message'] = $message;
					//New Code Here
					$this->loadLayout();
					$toplink = $this->getLayout()->getBlock('top.links')->toHtml();
					$response['toplink'] = $toplink;
					Mage::register('referrer_url', $this->_getRefererUrl());

					$sidebar_block = $this->getLayout()->getBlock('cart_sidebar');
					if( $sidebar_block ) {
						$sidebar = $sidebar_block->toHtml();
						$response['sidebar'] = $sidebar;
					}
					
					//check if theme contains minicart block
	                if($minicart = $this->getMinicartBlock()) {
	                    $response['minicart'] = $minicart;
	                }
				}

				//Create choice block
                Mage::register('current_product', $product);
                $response['productinfo'] = $this->getLayout()->createBlock('core/template')->setTemplate('magento1/catalog/product/view/ajaxcart.phtml')->toHtml();
			} catch (Mage_Core_Exception $e) {
				$msg = "";
				if ($this->_getSession()->getUseNotice(true)) {
					$msg = $e->getMessage();
				} else {
					$messages = array_unique(explode("\n", $e->getMessage()));
					foreach ($messages as $message) {
						$msg .= $message.'<br/>';
					}
				}

				$response['status'] = 'ERROR';
				$response['message'] = $msg;
			} catch (Exception $e) {
				$response['status'] = 'ERROR';
				$response['message'] = $this->__('Cannot add the item to shopping cart.');
				Mage::logException($e);
			}
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
			return;
		}else{
			return parent::addAction();
		}
	}

	/**
     * Get product infomation
     *
     * @author Thanh-TV
     * @since 2016-07-21
     */
	public function optionsAction() {
        $productId = $this->getRequest()->getParam('product_id');
        // Prepare helper and params
        $viewHelper = Mage::helper('catalog/product_view');

        $params = new Varien_Object();
        $params->setCategoryId(false);
        $params->setSpecifyOptions(false);

        // Render page
        try {
            $viewHelper->prepareAndRender($productId, $this, $params);
        } catch(Exception $e) {
            if($e->getCode() == $viewHelper->ERR_NO_PRODUCT_LOADED) {
                if(isset($_GET['store']) && !$this->getResponse()->isRedirect()) {
                    $this->_redirect('');
                } elseif(!$this->getResponse()->isRedirect()) {
                    $this->_forward('noRoute');
                }
            } else {
                Mage::logException($e);
                $this->_forward('noRoute');
            }
        }
    }

	/**
     * Create block for rwd template
     */
    private function getMinicartBlock() {
        return ($minicart_block = $this->getLayout()->getBlock('minicart_head')) ? $minicart_block->toHtml() : null;
    }
}