<?php
use PrestaShopBundle\Entity\Repository\TabRepository;
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * This function updates your module from previous versions to the version 2.3.0,
 * usefull when you modify your database, or register a new hook ...
 * Don't forget to create one file per version.
 */
function upgrade_module_2_3_0($module)
{
    if (!$module) {
        PrestaShopLogger::addLog('Error: $module es null');
        return false;
    }

    $version = '2.3.0';

    try {
        // =======================================================
        // 1. Reinstalar Overrides
        // =======================================================
        PrestaShopLogger::addLog(__METHOD__);
        $module->uninstallOverrides();
        $result = $module->installOverrides();

        // =======================================================
        // 2.  Modificar Tab administracion
        // =======================================================
        upgradeTabname_2_3_0($module, $version);


        PrestaShopLogger::addLog(sprintf(
           'Upgrade módulo %1$s a versión %2$s : %3$s',
            $module->name,
            $version,
            $result ? 'OK' : 'FALLO'
        ));
        return $result;

    } catch (Exception $e) {
        PrestaShopLogger::addLog('Error en upgrade : ' . $e->getMessage());
        return false;
    }

}

function upgradeTabname_2_3_0($module, $version){

    // Suponiendo que el controlador de tu Tab se llama AdminMiModuloController
    $controller_class_name = 'AdminSjboverridesConfig';
    $new_tab_name =  sprintf('%1$s -%2$s', 'SJB Overrides', $version);

    // PrestaShop 1.7.8 usa el servicio para obtener el repositorio de Tabs
    $tabRepository = $module->get('prestashop.core.admin.tab.repository');

    if ($tabRepository instanceof TabRepository) {
        $id_tab = (int) $tabRepository->findOneIdByClassName($controller_class_name);

        if ($id_tab) {

            $tab = new Tab($id_tab);

            if(!Validate::isLoadedObject($tab))
                throw new Exception('Error: No se pudo cargar objeto Tab con ID: ' . $id_tab);

            $languages = Language::getLanguages(false);

            // Asignar el nuevo nombre a todos los idiomas
            foreach ($languages as $lang) {
                $tab->name[(int) $lang['id_lang']] = $new_tab_name;
            }
            // Actualizar y combinar resultado
            if (!$tab->update())
                throw new Exception('Error al actualizar nombre de Tab: ' . $controller_class_name);

            // Correcto
            return true;

        } else {
            throw new Exception('Tab no encontrado para el controlador: ' . $controller_class_name);
        }
    }
}
