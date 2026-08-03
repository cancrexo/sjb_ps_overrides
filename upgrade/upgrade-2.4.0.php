<?php
/**
 * Upgrade a 2.4.0: sistema SJB de clientes / direccións (menú + config).
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
function upgrade_module_2_4_0($module)
{
    if (!$module) {
        PrestaShopLogger::addLog('Error: $module es null en upgrade 2.4.0');
        return false;
    }

    $version = '2.4.0';
    PrestaShopLogger::addLog(__FUNCTION__);

    try {
        // 1. Engadir clave de config se falta
        $raw = Configuration::get(sjboverrides::PREFIX . 'general_config');
        $config = $raw ? (array) Tools::jsonDecode($raw, true) : [];
        if (!array_key_exists('hookclientesactive', $config)) {
            $config['hookclientesactive'] = 0;
            Configuration::updateValue(
                sjboverrides::PREFIX . 'general_config',
                Tools::jsonEncode($config, JSON_HEX_QUOT | JSON_HEX_TAG),
                true
            );
        }

        // 2. Crear tabs SJB (ocultas) e deixar nativos visibles segundo config
        $enabled = !empty($config['hookclientesactive']);
        if (!$module->syncClientesMenu((bool) $enabled)) {
            throw new Exception('syncClientesMenu fallou no upgrade 2.4.0');
        }

        // 3. Actualizar nome da tab de configuración
        $idTab = (int) Tab::getIdFromClassName('AdminSjboverridesConfig');
        if ($idTab) {
            $tab = new Tab($idTab);
            if (Validate::isLoadedObject($tab)) {
                $newName = sprintf('%1$s -%2$s', 'SJB Overrides', $version);
                foreach (Language::getLanguages(false) as $lang) {
                    $tab->name[(int) $lang['id_lang']] = $newName;
                }
                $tab->update();
            }
        }

        PrestaShopLogger::addLog(sprintf(
            'Upgrade módulo %1$s a versión %2$s : OK',
            $module->name,
            $version
        ));

        return true;
    } catch (Exception $e) {
        PrestaShopLogger::addLog('Error en upgrade 2.4.0: ' . $e->getMessage(), 3);
        return false;
    }
}
