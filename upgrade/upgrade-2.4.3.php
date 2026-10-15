<?php
/**
 * Upgrade a 2.4.3: mantenimiento HTTP 200 (interruptor en la config general).
 *
 * @author Daniel "Cancrexo" Prol <cancrexo@gmail.com>
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param sjboverrides $module
 * @return bool
 */
function upgrade_module_2_4_3($module)
{
    if (!$module) {
        PrestaShopLogger::addLog('Error: $module es null en upgrade 2.4.3');
        return false;
    }

    $version = '2.4.3';
    PrestaShopLogger::addLog(__FUNCTION__);

    try {
        $raw = Configuration::get(sjboverrides::PREFIX . 'general_config');
        $config = $raw ? (array) Tools::jsonDecode($raw, true) : [];

        if (!array_key_exists('hookmaintenance200', $config)) {
            $config['hookmaintenance200'] = 0;
            Configuration::updateValue(
                sjboverrides::PREFIX . 'general_config',
                Tools::jsonEncode($config, JSON_HEX_QUOT | JSON_HEX_TAG),
                true
            );
        }

        PrestaShopLogger::addLog(sprintf(
            'Upgrade módulo %1$s a versión %2$s : OK',
            $module->name,
            $version
        ));

        return true;
    } catch (Exception $e) {
        PrestaShopLogger::addLog('Error en upgrade 2.4.3: ' . $e->getMessage(), 3);
        return false;
    }
}
