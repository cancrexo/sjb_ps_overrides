<?php
/**
 * Upgrade a 2.4.2: filtro direcciones en SJB clientes + bulk actions (invertir / menú arriba).
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
function upgrade_module_2_4_2($module)
{
    if (!$module) {
        PrestaShopLogger::addLog('Error: $module es null en upgrade 2.4.2');
        return false;
    }

    $version = '2.4.2';
    PrestaShopLogger::addLog(__FUNCTION__);

    try {
        // Sen cambios de config/BD: solo assets JS e lógica de listados.
        PrestaShopLogger::addLog(sprintf(
            'Upgrade módulo %1$s a versión %2$s : OK',
            $module->name,
            $version
        ));

        return true;
    } catch (Exception $e) {
        PrestaShopLogger::addLog('Error en upgrade 2.4.2: ' . $e->getMessage(), 3);
        return false;
    }
}
