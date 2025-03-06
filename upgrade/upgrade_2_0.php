<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * This function updates your module from previous versions to the version 3.0.5,
 * usefull when you modify your database, or register a new hook ...
 * Don't forget to create one file per version.
 */
function upgrade_module_2_0($object)
{
   $PREFIX = 'SJBOVERRIDE_';
   Configuration::updateValue($PREFIX.'IMAGEMANAGER', FALSE );
   Configuration::updateValue($PREFIX.'ORDERHISTORY', FALSE );
   Configuration::updateValue($PREFIX.'ORDERHISTORYSTATE', 0 );
   Configuration::updateValue($PREFIX.'DEBUG_ENABLED', true );
   Configuration::updateValue($PREFIX.'REMOVE_DATA', false );

   
   return
            $object->uninstallOverrides() &&
	         $object->installOverrides();
}

