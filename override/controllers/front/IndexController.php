<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *  @author Cancrexo
 *  @copyright 2021 SJB Dixital
 *  @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 *  OVERRIDE de IndexController para xestionar a paxina de inicio
 *  Segun sexa oa categoria default de un usuario, redirixea certa paxina en vez da home
 *
 */

class IndexController extends IndexControllerCore
{

    /**
     * Assign template vars related to page content
     * @see FrontController::initContent()
     */
   public function initContent()
   {
      // Si usuario catering -->redirixe a categoria catering!
      // if module... , get instance by name..., get variable::statica etc...
      // FrontController::initContent();
      $GRUPOS_CATERTING = array(10,11,12,13);
      $CATEGORIA_CATERING = 12;
      $id_customer_group = (int)Context::getContext()->customer->id_default_group;
      if(in_array($id_customer_group, $GRUPOS_CATERTING)){
         $link = Context::getContext()->link;
         $url = $link->getCategoryLink($CATEGORIA_CATERING);
         Tools::redirect($url);

      }else parent::initContent();
   }
}