<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * This function updates your module from previous versions to the version 3.0.5,
 * usefull when you modify your database, or register a new hook ...
 * Don't forget to create one file per version.
 */
function upgrade_module_1_2($object)
{

     return
            $object->uninstallOverrides() &&
	         $object->installOverrides();
}

