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
 * La página de mantenimiento responde 200 para que los monitores
 * no marquen la tienda como caída.
 *
 *  @author Daniel "Cancrexo" Prol <cancrexo@gmail.com>
 *  @copyright 2026 SJB Dixital
 *  @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class FrontController extends FrontControllerCore
{
    /**
     * La página de mantenimiento responde 200 para que los monitores
     * no marquen la tienda como caída.
     */
    protected function displayMaintenancePage()
    {
        if (!Module::isEnabled('sjboverrides')) {
            return parent::displayMaintenancePage();
        }
        /** @var sjboverrides|false $SJBOVERRIDES */
        $SJBOVERRIDES = Module::getInstanceByName('sjboverrides');
        if (!$SJBOVERRIDES
            || $SJBOVERRIDES->hayOverrideAjeno('front')
            || !(bool) $SJBOVERRIDES->getSingleData('general', 'hookmaintenance200')
        ) {
            return parent::displayMaintenancePage();
        }

        if ($this->maintenance == true || !(int) Configuration::get('PS_SHOP_ENABLE')) {
            $this->maintenance = true;
            $allowed_ips = array_map('trim', explode(',', Configuration::get('PS_MAINTENANCE_IP')));
            if (!\Symfony\Component\HttpFoundation\IpUtils::checkIp(Tools::getRemoteAddr(), $allowed_ips)) {
                header('HTTP/1.1 200 OK');

                $this->registerStylesheet('theme-error', '/assets/css/error.css', ['media' => 'all', 'priority' => 50]);
                $this->context->smarty->assign([
                    'urls' => $this->getTemplateVarUrls(),
                    'shop' => $this->getTemplateVarShop(),
                    'HOOK_MAINTENANCE' => Hook::exec('displayMaintenance', []),
                    'maintenance_text' => Configuration::get('PS_MAINTENANCE_TEXT', (int) $this->context->language->id),
                    'stylesheets' => $this->getStylesheets(),
                ]);
                $this->smartyOutputContent('errors/maintenance.tpl');

                exit;
            }
        }
    }
}
