
<?php
/**
 *
 * Necesitamos añadir codigo de desconto empleado en fapedido, a hra de sacar unha factura
 */
class HTMLTemplateInvoice extends HTMLTemplateInvoiceCore
{
   public function getContent(){

      $sql = '
      SELECT cr.code, cr.reduction_percent, cr.reduction_amount, ocr.*
      FROM `' . _DB_PREFIX_ . 'cart_rule` cr
      JOIN  `' . _DB_PREFIX_ . 'order_cart_rule` ocr ON ocr.id_cart_rule = cr.id_cart_rule  AND ocr.`id_order_invoice` = ' . (int) $this->order_invoice->id;

      $danistuff =  Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

      // $cart_rules = $this->order->getCartRules($this->order_invoice->id);
      $this->smarty->assign(array('cart_rules_dani'=> $danistuff));
      $this->smarty->assign(array('dani_sql'=> $sql));


      return parent::getContent();

    }


}