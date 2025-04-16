<?php
/**
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
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Presenter\AbstractLazyArray;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use PrestaShop\PrestaShop\Core\Product\ProductExtraContentFinder;
use PrestaShop\PrestaShop\Core\Product\ProductInterface;

class ProductController extends ProductControllerCore
{
 /**
     * Asigna variable smartys si o producto ten a feature indicada
     *
     * @see FrontController::initContent()
     */
    public function initContent(){

        // if (!Module::isEnabled('sjboverrides') || !($SJBOVERRIDES = Module::getInstanceByName('sjboverrides'))
        // ||  !($override_enabled = (bool) Configuration::get($SJBOVERRIDES::PREFIX.'ORDERHISTORY'))
        // ) {
        //     return  parent::initContent();
        // }
        
        $el_llonatan =  $this->checkFeaturesProducto();

        // Asigna el array de IDs a smarty
        $this->context->smarty->assign(['sjboverride_sjbquestion' => $el_llonatan]);

        parent::initContent();

    }


    private function  checkFeaturesProducto(){

        $id_feature_value = Configuration::get('SJBOVERRIDE_QUESTION_IDFEATUREVALUE');

        if(!$id_feature_value) return false;

        $sql = sprintf(
            'SELECT count(*) as total FROM ' . _DB_PREFIX_ . 'feature_product where id_product = \'%1$d\' AND id_feature_value IN( \'%2$s\')',
            $this->product->id,
            $id_feature_value
        ); // var_dump($sql);
        $result = Db::getInstance()->getValue($sql);
        if ($result)return $result;
        return false;
    }

}