<?php
/**
 * 2012-2020 SJBDIXITAL
 *
 * NOTICE OF LICENSE
 *
 * This is a commercial license
 * Do not allow to re-sales, edit without permission from PrestaSOO.
 * International Registered Trademark & Property of PrestaSOO
 *
 * NON TEÑO NIN IDEA DE QUE FAI ESTE OVERRIDE!
 *
 * @author Cancrexo <dani@sjbdixital.es>
 * @copyright SJB dixital
 * @license   Commercial License. All right reserved
 */

class DiscountController extends DiscountControllerCore
{

   public function initContent(){


      $cart_rules = $this->getTemplateVarCartRules();

      // $cart_rules = CartRule::getCustomerCartRules($this->context->language->id, $this->context->customer->id, true, false, true);

      $nb_cart_rules = count($cart_rules);

      foreach ($cart_rules as $key => &$discount ) {
         if ($discount['quantity_for_user'] === 0 || !$discount['quantity']) {
               unset($cart_rules[$key]);
         }

         $discount['value'] = Tools::convertPriceFull(
               $discount['value'],
               new Currency((int)$discount['reduction_currency']),
               new Currency((int)$this->context->cart->id_currency)
         );

         if ((int)$discount['gift_product'] !== 0) {


               $product = new Product((int) $discount['gift_product'], false, (int)$this->context->language->id);
               if (!Validate::isLoadedObject($product) || !$product->isAssociatedToShop() || !$product->active) {
                  unset($cart_rules[$key]);
               }
               if (Combination::isFeatureActive() && $discount['gift_product_attribute'] !== 0) {
                  $attributes = $product->getAttributeCombinationsById((int)$discount['gift_product_attribute'], (int)$this->context->language->id);
                  $giftAttributes = array();
                  foreach ($attributes as $attribute) {
                     $giftAttributes[] = $attribute['group_name'] . ' : ' . $attribute['attribute_name'];
                  }
                  $discount['gift_product_attributes'] = implode(', ', $giftAttributes);
               }
               $discount['gift_product_name'] = $product->name;
               $discount['gift_product_link'] = $this->context->link->getProductLink(
                  $product,
                  $product->link_rewrite,
                  $product->category,
                  $product->ean13,
                  $this->context->language->id,
                  $this->context->shop->id,
                  $discount['gift_product_attribute'],
                  false,
                  false,
                  true
               );
         }
      }

      $this->context->smarty->assign(array(
         'nb_cart_rules' => (int)$nb_cart_rules,
         'cart_rules' => $cart_rules,
         'discount' => $cart_rules, // retro compatibility
         'nbDiscounts' => (int)$nb_cart_rules, // retro compatibility
         'chacho'=>'vamos lenhe'
      ));

      FrontController::initContent();

      $this->setTemplate('customer/discount');

      // $this->setTemplate(_PS_THEME_DIR_.'discount.tpl');
   }

}
