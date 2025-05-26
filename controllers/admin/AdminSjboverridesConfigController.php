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




    public function postProcess() {
        if (\Tools::isSubmit('sjbSubmitConfig')) {

                              // arrays are not assigned by reference!
            $formvalues = []; // array with sanitized values

            // foreach($this->config as $key=> $value) {
            //     $upper = static::myreplace($key);
            //     \Configuration::updateValue($this->module::PREFIX . $upper, (int) Tools::getValue($key));
            // }


            // if ((int) \Tools::getValue('module_override_reinstall')) {
            //     $overrides = $this->uninstallOverrides() && $this->installOverrides();
            //     $this->_html .= $this->displayConfirmation(sprintf('%1$s<br>', $this->trans('Overrides resintalados OK', [], 'Modules.Sjboverrides.Admin')));
            // }

            // if ((int) \Tools::getValue('module_cache_clear')) {
            //     $this->vaciaCache();
            //     $this->_html .= $this->displayConfirmation(sprintf('%1$s<br>', $this->trans('Cache vaciado', [], 'Modules.Sjboverrides.Admin')));
            // }

            $this->_html .= $this->displayConfirmation($this->trans('Configuracion de modulo actualizada.', [], 'Modules.Sjboverrides.Admin'));
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
        $datos2Form = SJB_PSTools::getInstance()->getStoredArray($this->module::PREFIX, 'general', true);
        //  var_dump($datos2Form);
        // die();

        // Fijos, non bbdd
        $datos2Form['module_override_reinstall'] = false;
        $datos2Form['module_cache_clear']        = false;
        $datos2Form['issjbsubmit']               = 0; // Cambiase por js

        $helper->identifier   = $this->identifier; // indice de this->table, neste caso  id_configuration
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
        . '&configure=' . $this->name . '&tab_module=' . $this->module->tab . '&module_name=' . $this->module->name;
        $helper->token = \Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $datos2Form, //$this->config,
            'languages'    => $this->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm(
            [
                // $this->getFormOverrideImages(),
                $this->getFormPedidos(),
                // $this->getFormOverrideRewrite(),
                $this->getFormOverrideProducto(),
                // $this->getFormOverrideReinstalls(),
                $this->getFormDebug(),
            ]
        );

    }

    // override pedidos
    public function getFormPedidos(){

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
                        'name'   => 'general_orderhistory',
                        'values' => [
                            [
                                'id'    => 'ESTADO_FACTURADO',
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
                    'form_group_class'=>'grupo-preguntar-porproducto',
                    'type'     => 'select', // This is a <select> tag.
                    'label'    => $this->l('Preguntar por producto'), // The <label> for this <select> tag.
                    'desc'   => sprintf('%1$s <strong style="color:#c00">%2$s</strong> %3$s',
                        $this->l('Si el'),
                        $this->l('producto no tiene stock') ,
                        $this->l('se muestra FORMULARIO para consultas en lugar del mensaje de STOCK AGOTADO.'),
                    ),
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
        ];
        // var_dump($form);
        return ['form'=>$form];
    }


    // Para usar a miña rutina de imaxes chula (ao rexenerar)
    protected function getFormOverrideImages(){
        return [
            'form' => [

                'legend' => [
                    'title' => $this->l('Override de imagenes'),
                    'icon'  => 'icon-cog',
                ],
                // 'error'  => sprintf('<strong>%1$s</strong> %2$s', 'OJO', $this->l('Parámetros de configuracion y desarrollo')),
                'input'  => [
                    'type'   => 'switch',
                    'label'  => $this->l('Image Manager'),
                    'desc'   => sprintf(
                        '%1$s <strong>%2$s</strong> %3$s',
                        $this->l('Override de clase'),
                        $this->l('Imagemanager'),
                        $this->l('para recorte preciso de las imágenes. Añade funcionalidad _sjb para fondo transparente.')),
                    'name'   => 'image_manager',
                    'values' => [
                        [
                            'id'    => 'image_manager_on',
                            'value' => 1,
                            'label' => $this->l('On'),
                        ],
                        [
                            'id'    => 'image_manager_off',
                            'value' => 0,
                            'label' => $this->l('Off'),
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
            $this->module->getLocalPath() . 'views/templates/admin/instructions.tpl');
    }




    public function vaciaCache()
    {
        static::debug('Borrando cache prestashop', 2);
        \Tools::clearSmartyCache();
        \Tools::clearXMLCache();
        \Media::clearCache();
        \Tools::generateIndex();
        static::debug('cache prestashop borrado', 2);
    }


}
