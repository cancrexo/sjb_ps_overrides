<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * This function updates your module from previous versions to the version 2.1.6,
 * usefull when you modify your database, or register a new hook ...
 * Don't forget to create one file per version.
 */
function upgrade_module_2_2_0($object)
{
   // nada
   PrestaShopLogger::addLog(__METHOD__);
   $config = $object->getData('general', false);
   try{
        // PrestaShopLogger::addLog('JRABADOS!'. print_r($config, true));
        $module = Module::getInstanceByName('sjboverrides');
        $module->uninstallOverrides();
        $result = $module->installOverrides();
         PrestaShopLogger::addLog(sprintf(
            'Update %1$s : %2$s',
            $module->name,
            $result ? 'OK' : 'ERROR')
        );
        return $result;

   }catch(Exception $e){
       PrestaShopLogger::addLog('Error en upgrade : ' . $e->getMessage());
       return false;
   }

}
