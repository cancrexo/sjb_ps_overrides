<?php

use \Sjboverrides\SJB_PSTools;

 class AdminSjboverridesConfigController extends ModuleAdminController{

    private $_html = '';

    public  $config;

	public function __construct(   ){

        $this->bootstrap = true;  // Obviamente podes especificalo según controlador!!!

        parent::__construct();

        $this->toolbar_title =  $this->l('Configuración general');

        $this->meta_title = $this->title = $this->displayName = $this->l('Configuración '). $this->module->name;

        $this->name = str_replace(array('admin', 'controller'), '', Tools::strtolower(get_class($this)));

        $this->ucasename = str_ireplace(array('admin', 'controller'), '', get_class($this));

	}


    public function setMedia($isNewTheme = false){
        if ($this->ajax)return; //  Ccomprobamolo antes. ostia!!!
        parent::setMedia(); // Carga datos comuns, css comun, js común (incluso inline con mediaAddJsDef)

        $this->context->controller->addCSS('//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css', 'all');
        $this->context->controller->addCSS($this->module->path . 'views/admin/admin.css', 'all');
        $this->context->controller->addJS($this->module->path . 'views/admin/admin.js');

        $tojs = ['sjboverride_links' =>
            [
                'sjboverrides'       => $this->context->link->getAdminLink('AdminModules') . "&configure=sjboverrides",
                'ps_faceted'         => $this->context->link->getAdminLink('AdminModules') . "&configure=ps_facetedsearch",
                'catalogo_etiquetas' => $this->context->link->getAdminLink('AdminFeatures') . "&viewfeature&id_feature=8",
                'pk_themesettings'   => $this->context->link->getAdminLink('AdminModules') . "&configure=pkthemesettings",
                'etiquetas_id'       => 1, // id_feature

            ],
        ];
        // \Media::addJsDef($tojs);
        // $this->context->controller->addCSS($this->_path . 'views/admin/comun.css', 'all');
        // $this->context->controller->addJS($this->_path . 'views/admin/comun.js');


    }


    // 1o este -->define as views
    public function initProcess(){

        parent::initProcess(); //-->define valor de display!!
        // agora defino as miñas views:

        if ( !$this->ajax)
            $this->display = 'edit';
    }



    // Procesado formulario
    public function postProcess() {

        if (\Tools::isSubmit('sjbSubmitConfig')) {

            $formvalues = []; // array with sanitized values

            // General (con prefijo "general_" en todas as claves)
            $this->config = $this->module->getData('general'); // Leense co prefixo "general_" incluid nas claves pq no POST veñen con el!!OSTIAS

            foreach($this->config as $key=> $previo) {
                $valor =\Tools::getValue($key, $previo); // getValue($key, $default_value)
                // $this->_html .= "$key =>$valor <br>";
                $formvalues[$key] =  $valor != $previo ? $valor : $previo; // si non cambia mantén o valor previo
            }


            // Eliminar el prefijo 'general_' de todas las claves del array formValues
            $formvalues = array_combine(
                array_map(function($key) {
                    return str_ireplace('general_', '', $key);
                }, array_keys($formvalues)),
                array_values($formvalues)
            );

            // Grabar
            $this->module->saveData('general', $formvalues);


            if ((int) \Tools::getValue('module_override_reinstall')) {
                $overrides = $this->module->uninstallOverrides() && $this->module->installOverrides();
                $this->confirmations[] = $this->trans('OVERRIDES reinstalados OK.<br>', [], 'Modules.Sjboverrides.Admin');
            }

            if ((int) \Tools::getValue('module_hooks_reinstall')) {
                $reinstalados = $this->module->install_hooks();
                if($reinstalados === false){
                    $this->errors[] = $this->trans('HOOKS no reinstalados.<br>', [], 'Modules.Sjboverrides.Admin');
                } else {
                    $this->confirmations[] = $this->trans('HOOKS reinstalados OK.<br>', [], 'Modules.Sjboverrides.Admin');
                }
            }


            if ((int) \Tools::getValue('module_cache_clear')) {
                $this->vaciaCache();
                // $this->_html .= $this->displayConfirmation(sprintf('%1$s<br>', $this->trans('Cache vaciado', [], 'Modules.Sjboverrides.Admin')));
                 $this->confirmations[] =$this->trans('Cache vaciado.<br>', [], 'Modules.Sjboverrides.Admin');
            }
            $this->confirmations[] = $this->trans('Configuracion de modulo actualizada.<br>', [], 'Modules.Sjboverrides.Admin');

        }
    } // postProcess



   /**
     * Formulario
     */
    public function renderForm() {

        $helper                           = new \HelperForm();
        $helper->module                   = $this;
        $helper->show_toolbar             = true;
        $helper->table                    = 'configuration'; // PS_CONFIGURATION!!
        $lang                             = new \Language((int) \Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language    = $lang->id; //$this->context->anguage->id
        $helper->allow_employee_form_lang =$this->context->language->id;
        // \Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

        // carga ddatos de configuracion
        // $datos2Form = $this->module->getData('general');
        $this->config = $this->module->getData('general');
       
        if(!$this->config){
            $this->config = $this->module->setDefaultValues();
        }
        // Fijos, non bbdd
        $this->config['module_override_reinstall'] = false;
        $this->config['module_hooks_reinstall']     = false;
        $this->config['module_cache_clear']        = false;
        $this->config['issjbsubmit']               = 0; // Cambiase por js
        $helper->identifier   = $this->identifier; // indice de this->table, neste caso  id_configuration
        $helper->currentIndex = $this->context->link->getAdminLink('AdminSjboverridesConfig');
        $helper->token = \Tools::getAdminTokenLite('AdminSjboverridesConfig');

        $helper->tpl_vars = [
            'fields_value' => $this->config, //$this->config,
            'languages'    => $this->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        $el_llonatan =  $helper->generateForm(
            [
                $this->getFormOverrideImagenamager(),
                $this->getFormOverridePedidos(),
                // $this->getFormOverrideRewrite(),
                $this->getFormOverrideProducto(),
                $this->getFormOverrideManufacturer(),
                // $this->getFormOverrideReinstalls(),
                $this->getFormDebug(),
            ]
        );

        $this->context->smarty->assign('DATA', $this->config);
        return $this->_html . $el_llonatan . $this->getInstructions() . $this->getDebugData() ;

    }

    // override imagemanager (provisional):
    public function getFormOverrideImagenamager(){
        $form = [

            'legend' => [
                'title' => $this->l('Image Manager'),
                'icon'  => 'icon-cog',
            ],
            'input'  => [
                [
                    'type'   => 'switch',
                    'label'  => $this->l('usar Imagemanager propio'),
                    'desc'   => sprintf('%1$s', $this->l('Usa un override de imagemanager avanzado con soporte para imagenes trasnparentes y recorte preciso')                        
                    ),

                    'name'   => 'general_hookimagemanager',
                    'values' => [
                        [
                            'id'    => 'imagemanager_on',
                            'value' => 1,
                            'label' => $this->l('On'),
                        ],
                        [
                            'id'    => 'imagemanager_off',
                            'value' => 0,
                            'label' => $this->l('Off'),
                        ],
                    ],
                ],
             
            ],



            'submit' => [
                'title' => $this->l('Save'),
                'name'  => 'sjbSubmitConfig',
                'class' => 'sjbsubmit btn btn-default pull-right',
            ],
        ];
        // var_dump($form);
        return ['form'=>$form];
    }

    // override pedidos
    public function getFormOverridePedidos(){

        return [
            'form' => [

                'legend' => [
                    'title' => $this->l('Orders'),
                    'icon'  => 'icon-cog',
                ],
                'input'  => [
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('OrderHistory'),
                        'desc'   => sprintf('%1$s <strong>%2$s</strong> %3$s',
                            $this->l('Hook para la gestión de'),
                            $this->l('Facturas'),
                            $this->l('Se asigna la Fecha de factura cuado esta se genere y no con la fecha del pedido. Al activarlo se define la variable ') . '<b>' . $this->module::PREFIX . 'ESTADO_FACTURADO</b>.'
                        ),
                        'name'   => 'general_hookorderhistoryactive',
                        'values' => [
                            [
                                'id'    => 'order_history_on',
                                'value' => 1,
                                'label' => $this->l('On'),
                            ],
                            [
                                'id'    => 'order_history_off',
                                'value' => 0,
                                'label' => $this->l('Off'),
                            ],
                        ],
                    ],
                    [
                        'form_group_class' => 'grupo-estadofacturado ' . ((bool) \Configuration::get($this->module::PREFIX . 'ORDERHISTORY') ? '' : 'hidden'),
                        'type'             => 'select',                     // This is a <select> tag.
                        'label'            => $this->l('Estado facturado'), // The <label> for this <select> tag.
                        'desc'             => sprintf(
                            '%1$s <strong>%2$s</strong>.',
                            $this->l('Define cuál es el estado FACTURADO necesario para asignar la fecha a la factura. Se asigna a la variable de config '), $this->module::PREFIX . 'ORDERHISTORYSTATE'),

                        'cols'             => 3,
                        'class'            => 'sjboverrides',
                        'name'             => 'general_idorderhistorystate', // The content of the 'id' attribute of the <select> tag.
                        'options'          => [
                            'id'    => 'id_order_state',
                            'name'  => 'label',
                            'query' => SJB_PSTools::getInstance()->getEstadosPedidos()
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'name'  => 'sjbSubmitConfig',
                    'class' => 'sjbsubmit btn btn-default pull-right',
                ],
            ],
        ];
    }


    // Para ter posibiliadde "PREGUNTAR" por producto
    protected function   getFormOverrideProducto(){

        $form = [

            'legend' => [
                'title' => $this->l('Override Producto'),
                'icon'  => 'icon-cog',
            ],
            // 'error'  => sprintf('<strong>%1$s</strong> %2$s', 'OJO', $this->l('Parámetros de configuracion y desarrollo')),
            'input'  => [
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Preguntar por producto'),
                    'desc'   => sprintf('%1$s <strong style="color:#c00">%2$s</strong> %3$s',
                        $this->l('Si el'),
                        $this->l('producto no tiene stock') ,
                        $this->l('se muestra FORMULARIO para consultas en lugar del mensaje de STOCK AGOTADO.'),
                    ),
                    'name'   => 'general_hookpreguntarproducto',
                    'values' => [
                        [
                            'id'    => 'preguntar_on',
                            'value' => 1,
                            'label' => $this->l('On'),
                        ],
                        [
                            'id'    => 'preguntar_off',
                            'value' => 0,
                            'label' => $this->l('Off'),
                        ],
                    ],
                ],
                [
                    'form_group_class'=>'grupo-preguntar-porproducto ' . ((int)$this->config['general_hookpreguntarproducto'] ? '' : 'hidden'),
                    'type'     => 'select', // This is a <select> tag.
                    'label'    => $this->l('Característica'), // The <label> for this <select> tag.
                    'desc'   => $this->l('Selecciona la característica que activa el formulario .'),
                    'cols' => 3,
                    'class' => 'sjboverrides',
                    'name'     => 'general_productidfeaturevalue', // The content of the 'id' attribute of the <select> tag.
                    'options' => array(
                        'optiongroup'=>array(
                            'label'=>'label',
                            'query'=>SJB_PSTools::getInstance()->getFeatures2optgroup(),

                        ),
                        'options'=>array(
                            'query'=>'options',
                            'id'=>'id',
                            'name'=>'name'
                        )
                    ),
                ],

            ],



            'submit' => [
                'title' => $this->l('Save'),
                'name'  => 'sjbSubmitConfig',
                'class' => 'sjbsubmit btn btn-default pull-right',
            ],
        ];
        // var_dump($form);
        return ['form'=>$form];
    }
 // Para ter posibiliadde "PREGUNTAR" por producto
    protected function   getFormOverrideManufacturer(){

        $form = [

            'legend' => [
                'title' => $this->l('Override Manufacturer'),
                'icon'  => 'icon-cog',
            ],
            'input'  => [
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Ocultar marcas con metatitulo dado'),
                    'desc'   => sprintf('%1$s <strong style="color:#c00">%2$s</strong> %3$s <strong>%4$s</strong> %5$s',
                        $this->l('Si la'),
                        $this->l('marca') ,
                        $this->l(' tiene un'),
                        $this->l('metatitulo'),
                        $this->l('igual al valor definido aquí, esta no se mostrará en el FRONTEND.')
                    ),

                    'name'   => 'general_hookocultarmanufacturer',
                    'values' => [
                        [
                            'id'    => 'ocultar_on',
                            'value' => 1,
                            'label' => $this->l('On'),
                        ],
                        [
                            'id'    => 'ocultar_off',
                            'value' => 0,
                            'label' => $this->l('Off'),
                        ],
                    ],
                ],
                [
                    'form_group_class'=>'grupo-ocultar-manufacturer ' . ((int)$this->config['general_hookocultarmanufacturer'] ? '' : 'hidden'),
                    'type'     => 'text', // This is a <select> tag.
                    'label'    => $this->l('Texto a incluir'), // The <label> for this <select> tag.
                    'desc'   => $this->l('El texto que filtrará la marca.'),
                    'cols' => 3,
                    'class' => 'sjboverrides',
                    'name'     => 'general_manufacturerhiddenflag', // The content of the 'id' attribute of the <select> tag.
                ]
            ],



            'submit' => [
                'title' => $this->l('Save'),
                'name'  => 'sjbSubmitConfig',
                'class' => 'sjbsubmit btn btn-default pull-right',
            ],
        ];
        // var_dump($form);
        return ['form'=>$form];
    }


    // Forumlario debug e config
    public function getFormDebug(){

        $form=[

            'legend' => [
                'title' => $this->l('Parámetros generales módulo'),
                'icon'  => 'icon-cog',
            ],
            'error'  => sprintf('<strong>%1$s</strong> %2$s', 'OJO', $this->l('Parámetros de configuracion y desarrollo')),
            'input'  => [

                [
                    'form_group_class' => 'grupo-clearcache ',
                    'type'             => 'switch',
                    'label'            => 'Vaciar la cache de Prestashop',
                    'desc'             => sprintf('<em style="color:#001">%1$s</em>', $this->l('Si cambias el metodo de regeneración de imagenes puede que tengas que vaciar el cache. Puedes hacerlo desde aquí o bien desde las opciones de Prestashop.')),
                    'name'             => 'module_cache_clear',
                    'values'           => [
                        [
                            'id'    => 'cache_clear_on',
                            'value' => 1,
                            'label' => $this->l('Vaciar'),
                        ],
                        [
                            'id'    => 'cache_clear_off',
                            'value' => 0,
                            'label' => $this->l('Off'),
                        ]],
                ],

                [
                    'form_group_class' => 'grupo-reinstallHook alert-warning',
                    'type'             => 'switch',
                    'label'            => sprintf('<span style="color:#c00">%1$s</span> %2$s', $this->l('REINSTALL'), $this->l('Hooks')),
                    'desc'             => sprintf('<em style="color:#001">%1$s</em>', $this->l('Fuerza la reinstalacion de TODOS los HOOKS de este  plugin.')),
                    'name'             => 'module_hooks_reinstall',
                    'values'           => [
                        [
                            'id'    => 'hooks_reinstall_on',
                            'value' => 1,
                            'label' => $this->l('Ejecutar'),
                        ],
                        [
                            'id'    => 'hooks_reinstall_off',
                            'value' => 0,
                            'label' => $this->l('Off'),
                        ],
                    ],
                ],

                [
                    'type'   => 'switch',
                    'label'  => $this->l('Debug Mode'),
                    'desc'   => sprintf(
                        '%1$s <em style="color:#c00">%2$s</em>',
                        $this->l('If active, module  will log messages to PS registry'),
                        'PS registry'),
                    'name'   => 'general_debugenabled',
                    'values' => [
                        [
                            'id'    => 'debug_enabled_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'debug_enabled_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ]],
                ],
                [
                    'type'   => 'switch',
                    'label'  => $this->l('Remove data on uninstall'),
                    'desc'   => $this->l('If set to true, all config data will be removed from database on uninstall.'),
                    'name'   => 'general_removedata',
                    'values' => [
                        [
                            'id'    => 'remove_data_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id'    => 'remove_data_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
            ],

            'submit' => [
                'title' => $this->l('Save'),
                'name'  => 'sjbSubmitConfig',
                'class' => 'sjbsubmit btn btn-default pull-right',
            ],
        ];

        // Añadimso o formgroup de reinstalar hooverrides
        array_unshift($form['input'], $this->getFormGroupOverrideReinstalls());
        return ['form'=>$form];
    }


    protected function getFormGroupOverrideReinstalls(){
        return

        [
            'form_group_class' => 'grupo-reinstalloverrides alert-danger',
            'type'             => 'switch',
            'label'            => sprintf('<span style="color:#c00">%1$s</span> %2$s', $this->l('REINSTALL'), $this->l('Overrides')),
            'desc'             => sprintf('<em style="color:#001">%1$s</em>', $this->l('Fuerza la reinstalacion de TODOS los  overrides de este plugin.')),
            'name'             => 'module_override_reinstall',
            'values'           => [
                [
                    'id'    => 'override_reinstall_on',
                    'value' => 1,
                    'label' => $this->l('Ejecutar'),
                ],
                [
                    'id'    => 'override_reinstall_off',
                    'value' => 0,
                    'label' => $this->l('Off'),
                ],
            ],
        ];
    }





    public function getInstructions(){

        return  $this->context->smarty->fetch(
            $this->module->getLocalPath() . 'views/templates/admin/info-plugin.tpl');
    }


    public function getDebugData(){

        return  $this->context->smarty->fetch(
            $this->module->getLocalPath() . 'views/templates/admin/debug.tpl');

    }

    public function vaciaCache()
    {
        $this->module::debug('Borrando cache prestashop', 2);
        \Tools::clearSmartyCache();
        \Tools::clearXMLCache();
        \Media::clearCache();
        \Tools::generateIndex();
        $this->module::debug('cache prestashop borrado', 2);
    }


}
