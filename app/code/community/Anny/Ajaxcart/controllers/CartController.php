<?php
require_once Mage::getModuleDir('controllers', 'Mage_Checkout').DS.'CartController.php';

class Anny_Ajaxcart_CartController extends Mage_Checkout_CartController {
	public function addAction() {
		if (! $this->getRequest()->isXmlHttpRequest()) {
			return parent::addAction();
		}

		$cart = $this->_getCart();
		$params = $this->getRequest()->getParams();
		try {
			if (isset($params['qty'])) {
				$filter = new Zend_Filter_LocalizedToNormalized(
					array('locale' => Mage::app()->getLocale()->getLocaleCode())
				);
				$params['qty'] = $filter->filter($params['qty']);
			}

			$product = $this->_initProduct();
			if (! $product) {
				$this->_goBack();
				return;
			}
			$related = $this->getRequest()->getParam('related_product');

			$cart->addProduct($product, $params);
			if (! empty($related)) {
				$cart->addProductsByIds(explode(',', $related));
			}
			$cart->save();
			$this->_getSession()->setCartWasUpdated(true);

			Mage::dispatchEvent('checkout_cart_add_product_complete',
				array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
			);

			$message = $this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName()));
			$this->loadLayout();
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
				'success' => true,
				'messages' => array($message),
				'cart_html' => $this->getLayout()->getBlock('root')->toHtml()
			)));
		}
		catch (Mage_Core_Exception $e) {
			Mage::logException($e);
			$messages = array_unique(explode("\n", $e->getMessage()));
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
				'success' => false,
				'messages' => $messages
			)));
		}
		catch (Exception $e) {
			Mage::logException($e);
			$message = $this->__('Cannot add the item to shopping cart.');
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
				'success' => false,
				'messages' => array($message)
			)));
		}
	}
}
