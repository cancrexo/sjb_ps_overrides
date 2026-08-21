<?php
/**
 * Upgrade a 2.4.1: posición de recorte Image Manager (topcenter|center|bottomcenter).
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
function upgrade_module_2_4_1($module)
{
    if (!$module) {
        PrestaShopLogger::addLog('Error: $module es null en upgrade 2.4.1');
        return false;
    }

    $version = '2.4.1';
    PrestaShopLogger::addLog(__FUNCTION__);

    try {
        $raw = Configuration::get(sjboverrides::PREFIX . 'general_config');
        $config = $raw ? (array) Tools::jsonDecode($raw, true) : [];

        if (!array_key_exists('imagecropposition', $config)) {
            $config['imagecropposition'] = 'center';
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
        PrestaShopLogger::addLog('Error en upgrade 2.4.1: ' . $e->getMessage(), 3);
        return false;
    }
}
