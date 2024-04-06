<?php
/**
 * 2012-2024 SJBDIXITAL
 *
 * NOTICE OF LICENSE
 *
 * This is a commercial license
 * Do not allow to re-sales, edit without permission from PrestaSOO.
 * International Registered Trademark & Property of PrestaSOO
 *
 * QUE FAI?
 * SOLO 1 DESCONTO POR CARRITO
 *
 * @author Cancrexo <dani@sjbdixital.es>
 * @copyright SJB dixital
 * @license   Commercial License. All right reserved
 */

class CartRule extends CartRuleCore
{


   public function checkValidity( Context $context, $alreadyInCart = false, $display_error = true, $check_carrier = true, $useOrderPrices = false){

		$value = parent::checkValidity($context, $alreadyInCart, $display_error );
		/* Filters used when retrieving the cart rules applied to a cart of when calculating the value of a reduction */
		$error = false;
		if ( $display_error && !is_null( $value ) ) { $error = true; }

		if ( !$display_error && $value === false ) { $error = true; }

		if ( !$error ){

			$otherCartRules = $context->cart->getCartRules();

			if ( count( $otherCartRules ) )

				foreach ( $otherCartRules as $otherCartRule ){

					if ( $otherCartRule['id_cart_rule'] == $this->id && !$alreadyInCart )
						//return ( !$display_error) ? false : Tools::displayError( 'This voucher is already in your cart' );
                  return (!$display_error) ? false : $this->trans('This voucher is already in your cart', [], 'Shop.Notifications.Error');

					if ( $otherCartRule['id_cart_rule'] != $this->id){

						$cart_rule = new CartRule( ( int )$otherCartRule['id_cart_rule'], $context->cart->id_lang );

						//return ( !$display_error ) ? false : Tools::displayError( 'This voucher is not combinable with an other voucher already in your cart:' );
                  return (!$display_error) ? false : $this->trans('This voucher is not combinable with an other voucher already in your cart', [], 'Shop.Notifications.Error');
					}
				}
		}

		return $value;
	}

}