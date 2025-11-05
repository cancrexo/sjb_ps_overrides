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
 *  Override para Producto: mostrar formulario de consulta en vez de comprar
 *
 *  @author Cancrexo. cancrexo@gmail.com
 *  @copyright 2024 SJB Dixital
 *  @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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