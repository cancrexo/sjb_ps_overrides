{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    Cancrexo <cancrexo@gmail.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 *}
 <table class="product" width="100%" cellpadding="4" cellspacing="0">

 {assign var='widthColProduct' value=$layout.product.width}
 {if !$isTaxEnabled}
   {assign var='widthColProduct' value=$widthColProduct+$layout.tax_code.width}
 {/if}
 <thead>
 <tr>
   <th class="product header small"  width="15%">Cantidad</th>
   <th class="product header small"  width="60%">Producto</th>
   <th class="product header small"  width="12%">PVP</th>
   <th class="product header small"  width="13%">Total</th>


 </tr>
 </thead>

 <tbody>

 <!-- PRODUCTS -->
 {foreach $order_details as $order_detail}
   {cycle values=["color_line_even", "color_line_odd"] assign=bgcolor_class}
   <tr class="product {$bgcolor_class}">

     <td class="product right">{$order_detail.product_quantity}</td>
     <td class="product left">
         {$order_detail.product_name}<br>
         <strong><small>REF: {$order_detail.product_reference}</small></strong>
     </td>

     <td class="product right pvp">
       {displayPrice currency=$order->id_currency price=$order_detail.unit_price_tax_incl}
     </td>

     <td  class="product right total">
     {displayPrice currency=$order->id_currency price=$order_detail.total_price_tax_incl}
     </td>
   </tr>

 {/foreach}
 <!-- END PRODUCTS -->

 <!-- CART RULES -->

 {assign var="shipping_discount_tax_incl" value="0"}
 {foreach from=$cart_rules_dani item=cart_rule name="cart_rules_loop"}
   {if $smarty.foreach.cart_rules_loop.first}
     <tr class="discount">
       <th class="header" colspan="4">
         {l s='Discounts' d='Shop.Pdf' pdf='true'}
       </th>
     </tr>
   {/if}
   <tr class="discount">
     <td class="white right" colspan="3">
   {*
      Si o cupon e de cantidade fixa, pode ser de digamos 100€ pero o pedido de 80, logo amosas entre parentesis
      o maximo do cupon pero na columna dcto a cantiadde efectiva de desconto
   *}
     {l s='Cupón DCTO'} {$cart_rule.code}
       {if $cart_rule.reduction_percent>0}
         {$cart_rule.reduction_percent}%
       {else}
         {capture name=descuento_maximo}{displayPrice currency=$order->id_currency price=$cart_rule.reduction_amount}{/capture}
        {l s='( Máximo: %s)' sprintf=[$smarty.capture.descuento_maximo] }
       {/if}
     </td>
     <td class="right white">
      {* Aqui desconto efectivo *}
       - {displayPrice currency=$order->id_currency price=$cart_rule.value}
     </td>
   </tr>
 {/foreach}

 </tbody>
</table>
{*
$layout._colCount
$cart_rule.code
$cart_rule.reduction_percent
$cart_rule.reduction_amount
$cart_rule.value
$cart_rule.value_tax_excl
*}
{* <div>{$cart_rules_dani|@var_dump}</div> *}
