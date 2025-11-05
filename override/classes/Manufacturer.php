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
 *  Override para ocultar certos manufacturers
 *
 *  @author Cancrexo. cancrexo@gmail.com
 *  @copyright 2024 SJB Dixital
 *  @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
class Manufacturer extends ManufacturerCore
{


/**
     * Return manufacturers.
     *
     * @param bool $getNbProducts [optional] return products numbers for each
     * @param int $idLang Language ID
     * @param bool $active
     * @param int $p
     * @param int $n
     * @param bool $allGroup
     *
     * @return array Manufacturers
     */
    public static function getManufacturers($getNbProducts = false, $idLang = 0, $active = true, $p = false, $n = false, $allGroup = false, $group_by = false, $withProduct = false)
    {
        if (!$idLang) {
            $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        }
        if (!Group::isFeatureActive()) {
            $allGroup = true;
        }

        // en realidade haberia que modificar o modelo pero vanlle dar moito
        if (! Module::isEnabled('sjboverrides') || ! ($SJBOVERRIDES = Module::getInstanceByName('sjboverrides'))) {
            return getManufacturers($getNbProducts, $idLang, $active, $p, $n, $allGroup, $group_by, $withProduct);
        }

        $flag_marca_oculta = $SJBOVERRIDES->getSingleData('general', 'manufacturer_hidden_flag'); // o key pode ir con ou sin underscores (xa se limpan)
        $flag_marca_oculta = ($flag_marca_oculta === false) ? null : $flag_marca_oculta;

        $sql = '
		SELECT m.*, ml.`description`, ml.`short_description`,  ml.`meta_title`, ml.`meta_keywords`, ml.`meta_description`
		FROM `' . _DB_PREFIX_ . 'manufacturer` m'
        . Shop::addSqlAssociation('manufacturer', 'm') .
        'INNER JOIN `' . _DB_PREFIX_ . 'manufacturer_lang` ml ON (m.`id_manufacturer` = ml.`id_manufacturer` AND ml.`id_lang` = ' . (int) $idLang . ')' .
        'WHERE 1 ' .
        ($active ? 'AND m.`active` = 1 ' : '') .

        ($withProduct ? 'AND m.`id_manufacturer` IN (SELECT `id_manufacturer` FROM `' . _DB_PREFIX_ . 'product`) ' : '') .
        ($flag_marca_oculta ?   ' AND ml.`meta_title` <>\''.$flag_marca_oculta .'\'' : '') .
        ($group_by ? ' GROUP BY m.`id_manufacturer`' : '') .
        'ORDER BY m.`name` ASC
		' . ($p ? ' LIMIT ' . (((int) $p - 1) * (int) $n) . ',' . (int) $n : '');

        $manufacturers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if ($manufacturers === false) {
            return false;
        }


        if ($getNbProducts) {
            $sqlGroups = '';
            if (!$allGroup) {
                $groups = FrontController::getCurrentCustomerGroups();
                $sqlGroups = (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '=' . (int) Group::getCurrent()->id);
            }

            $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                '
					SELECT  p.`id_manufacturer`, COUNT(DISTINCT p.`id_product`) as nb_products
					FROM `' . _DB_PREFIX_ . 'product` p USE INDEX (product_manufacturer)
					' . Shop::addSqlAssociation('product', 'p') . '
					LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` as m ON (m.`id_manufacturer`= p.`id_manufacturer`)
					WHERE p.`id_manufacturer` != 0 AND product_shop.`visibility` NOT IN ("none")
					' . ($active ? ' AND product_shop.`active` = 1 ' : '') . '
					' . (Group::isFeatureActive() && $allGroup ? '' : ' AND EXISTS (
						SELECT 1
						FROM `' . _DB_PREFIX_ . 'category_group` cg
						LEFT JOIN `' . _DB_PREFIX_ . 'category_product` cp ON (cp.`id_category` = cg.`id_category`)
						WHERE p.`id_product` = cp.`id_product` AND cg.`id_group` ' . $sqlGroups . '
					)') . '
					GROUP BY p.`id_manufacturer`'
                );


            $counts = [];
            foreach ($results as $result) {
                $counts[(int) $result['id_manufacturer']] = (int) $result['nb_products'];
            }

            foreach ($manufacturers as $key => $manufacturer) {
                if (array_key_exists((int) $manufacturer['id_manufacturer'], $counts)) {
                    $manufacturers[$key]['nb_products'] = $counts[(int) $manufacturer['id_manufacturer']];
                } else {
                    $manufacturers[$key]['nb_products'] = 0;
                }
            }
        }

        $totalManufacturers = count($manufacturers);
        $rewriteSettings = (int) Configuration::get('PS_REWRITING_SETTINGS');
        for ($i = 0; $i < $totalManufacturers; ++$i) {
            $manufacturers[$i]['link_rewrite'] = ($rewriteSettings ? Tools::link_rewrite($manufacturers[$i]['name']) : 0);
        }
        return $manufacturers;
    }
}