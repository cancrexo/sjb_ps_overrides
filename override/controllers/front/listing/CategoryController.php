<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 *  @author Cancrexo. cancrexo@gmail.com - @cancrexodev
 *  @copyright 2022 SJB Dixital
 *  @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

class CategoryController extends CategoryControllerCore
{

    public function init()
    {

      if (!Module::isEnabled('sjboverrides') || !($SJBOVERRIDES = Module::getInstanceByName('sjboverrides'))
         ||  !($override_enabled = (bool) Configuration::get($SJBOVERRIDES::PREFIX.'URLREWRITE'))
      ){
         \PrestaShopLogger::addLog('Saliendo de override ' . __METHOD__);
         return parent::init();
      }

      \PrestaShopLogger::addLog('Executando override ' . __METHOD__);

      if (Tools::getValue('category_rewrite')) {
         // SQL safe?
         $category_rewrite = Tools::getValue('category_rewrite');

         $sql = 'SELECT `id_category` FROM `' . _DB_PREFIX_ . 'category_lang`
            WHERE `link_rewrite` = \'' . $category_rewrite . '\' AND `id_lang` = ' . Context::getContext()->language->id;

         if (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP) {
               $sql .= ' AND `id_shop` = ' . (int) Shop::getContextShopID();
         }

         $categorys_list = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
         $categorys_count = count($categorys_list);

         if ($categorys_count == 1) {
               // Found ONLY one so dont need to look for parent
               $id_category = (int) $categorys_list[0]['id_category'];
               $_GET['noredirect'] = true;
         } else if ($categorys_count > 1) {
               // Found more than one so look for parent
               // SQL safe?
               $parent_rewrite = Tools::getValue('categories_rewrite');

               $shop_id = (int) Shop::getContextShopID();
               $sql = 'SELECT c.`id_category`
               FROM `ps_category` c
               LEFT JOIN `ps_category_lang` cl ON (c.`id_category` = cl.`id_category` AND cl.id_shop = ' . $shop_id . ' )
                  INNER JOIN ps_category_shop category_shop ON (category_shop.id_category = c.id_category AND category_shop.id_shop = ' . $shop_id . ')
               LEFT JOIN `ps_category_lang` clp ON (c.`id_parent` = clp.`id_category`)
               WHERE cl.`link_rewrite` = \'' . $category_rewrite . '\'
                  AND clp.`link_rewrite` = \'' . $parent_rewrite . '\'
                  AND cl.`id_lang` = ' . Context::getContext()->language->id;

               $id_category = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
               $_GET['noredirect'] = true;
         } else {
               // none found
               $id_category = 0;
         }

         if ($id_category > 0) {
               $_GET['id_category'] = $id_category;
         } else {
               //TODO: Do we need to send 404?
               header('HTTP/1.1 404 Not Found');
               header('Status: 404 Not Found');
         }
      }

      ProductListingFrontController::init(); // insetad of parent::init();
   }
}

