<?php
/**
 * 2012-2024 SJBDIXITAL
 *
 * NOTICE OF LICENSE
 *
 * This is a commercial license
 * Do not allow to re-sales, edit without permission from SJB Dixital
 * International Registered Trademark & Property of SJB Dixital
 *
 *
 * MÓDULO PARA INSERTAR OVERRIDES
 *
 * @author Cancrexo <dani@sjbdixital.es>
 * @copyright SJB dixital
 * @license   Commercial License. All right reserved
 */
declare (strict_types = 1);
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class sjboverrides extends Module implements WidgetInterface
{

    const PREFIX = 'SJBOVERRIDE_';

    protected $_hooks = [
        'actionAdminControllerSetMedia',
        'actionOrderStatusPostUpdate',
    ];

    // Config inicial nome de clave en bbdd, nome de variable para GET/POST, valor por defecto, tipo valor(0,1,2,)
    // todo
    protected $config = [
        'image_manager'             => [],
        'order_history'             => [],
        'id_order_history_state'    => [],
        'debug_enabled'             => [true],
        'remove_data'               => [true],
    ];

    public
        $_loadedconfig,
        $error, $valid, $path,
        $list_order_state;

    protected $_html = '';
    // distonto, uso array d claves que se leen con  Configuration::get(PREFIX . $clave)

    static $debugEnabled = true;


    static function myreplace($str){
        $limpia = strtoupper(str_ireplace(array('_'), '', $str));
        return $limpia;
    }

    /*
    * Debug de clase
    * Uso:   self::debug('error aqui')
    */
    public static function debug($msg = '', $level = 1)
    {
        if (! static::$debugEnabled) return;
        \PrestaShopLogger::addLog($msg, (int) $level);
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->name                   = 'sjboverrides';
        $this->tab                    = 'front_office_features';
        $this->version                = '2.1.2';
        $this->need_instance          = 0;
        $this->author                 = 'Daniel Prol «Cancrexo» ';
        $this->ps_versions_compliancy = ['min' => '1.7.6', 'max' => _PS_VERSION_];
        $this->bootstrap              = true;
        //$this->module_key = '';
        parent::__construct();

        $this->displayName      = $this->l('SJB Overrides ');
        $this->description      = $this->l('Módulo para insertar overrides, hooks y widgets varios.');
        $this->confirmUninstall = $this->l('¿Seguro que quieres desinstalar este módulo?');

        $this->error          = false;
        $this->valid          = false;
        $this->path           = $this->_path; // Protected polo visto, logo uso unha public para os templates!
        static::$debugEnabled = (bool) \Configuration::get(self::PREFIX . 'DEBUG_ENABLED') ?? true;
    }

    public function install()
    {
        error_log('instalar');
        static::debug($this->name . ' INSTALL');

        if (! parent::install()) {
            static::debug("Error en parent_install");
            return false;
        }

        foreach ($this->_hooks as $hook) {
            if (! $this->registerHook($hook)) {
                static::debug("Error en Hook $hook");
                return false;
            }
            static::debug("Hook $hook OK");
        }

        return $this->defaultValues(); // trivial!
    }

    public function uninstall()
    {
        $this->_clearCache('*');

        if (! parent::uninstall()) {
            return false;
        }

        foreach ($this->_hooks as $hook) {
            $this->unregisterHook($hook);
        }
        foreach($this->config as $key) {
            $key = static::myreplace($key);
            \Configuration::deleteByName(self::PREFIX . $key);
        }
     
        return true;
    }

    /**
     * Valores por defecto
     *
     * @return void
     */
    protected function defaultValues()
    {
        foreach($this->config as $key=> $value) {
            $key = static::myreplace($key);
            \Configuration::updateValue(self::PREFIX . $key, $value ?? false);
        }
        return true;
    }

    public function installTabs()
    {
        \PrestaShopLogger::addLog(__METHOD__);
        /*
         $tab->id_parent = 0; main tab
         $tab->id_parent = -1; // parent -1 será invisible
         $tab->id_parent = (int)Tab::getIdFromClassName('AdminCatalog'); // por exemplo, ou  AdminCarriers ->60
         $improve_tab_id = (int) Tab::getIdFromClassName('IMPROVE'); //  $meutab    =  new Tab($improve_tab_id);
         $modulestab     = (int) Tab::getIdFromClassName('AdminParentModulesSf');
         $improve_tab    =  new Tab($improve_tab_id);

         // ou se queremos que estea dentro de transporte
         $tmp = (int)Tab::getIdFromClassName('AdminCarriers');
         $carrierTab = new Tab($tmp);
         $tab->id_parent = $carrierTab->id_parent; //pq AdminCarriers non e a raíz!!

      */
        $tmp         = (int) Tab::getIdFromClassName('AdminCarriers'); // por exemplo, ou  AdminCarriers ->60
        $carrierTab  = new Tab($tmp);
        $parentTabId = $carrierTab->id_parent; // ou colles AdminParentShipping como carrierTab!

        $meusTabs = [
            [
                'nombre'     => $this->l('SJB Overrides'),
                'class_name' => 'AdminSjboverridesConfig',
                'enabled'    => 1,
                'active'     => 1,
            ],
            /*[
                'nombre'     => $this->l('SJB Carriers Http Requests'),
                'class_name' => 'AdminSjbcarriersHttp',
                'enabled'    => 1,
                'active'     => 0, // not visible
            ],*/
        ];

        foreach ($meusTabs as $i => $T) {
            \PrestaShopLogger::addLog('Tab ' . $i);
            $tab = new \Tab();
            $tab->id_parent = $parentTabId;
            $tab->module = $this->name;
            $tab->class_name = $T['class_name'];
            $tab->active = $T['active'];
            $tab->enabled = $T['enabled'];
            $tab->name = [];
            // \PrestaShopLogger::addLog( var_export($tab, true));
            # Lista de idiomas disponibles y activados
            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = $T['nombre'];
            }
            if (! $tab->add()) {
                return false;
            }
            \PrestaShopLogger::addLog('Tab ' . $i . ' ADDED');
        }
        return true;
    }


    /**
     * Pantalla de configuracion/ administración
     *
     */
    public function getContent()
    {
        $this->postProcess();
        $this->context->smarty->assign('path', $this->getPathUri() . 'views/'); // uri path for images
        $this->_html .= $this->renderForm();
        // $improve_tab_id = (int) Tab::getIdFromClassName('IMPROVE'); //  $meutab    =  new Tab($improve_tab_id);
        // $modulestab     = (int) Tab::getIdFromClassName('AdminParentModulesSf');
        // $improve_tab    =  new Tab($improve_tab_id);

        // $this->_html .= '<p>improve_tab_id: ' . $improve_tab_id . '</p>'
        //     . '<p>modulestab: ' . $modulestab . '</p>';
        return $this->_html;
    }

    public function postProcess()
    {
        if (\Tools::isSubmit('sjbSubmitConfig')) {

                              // arrays are not assigned by reference!
            $formvalues = []; // array with sanitized values

            foreach($this->config as $key=> $value) {
                $upper = static::myreplace($key);
                \Configuration::updateValue(self::PREFIX . $upper, (int) Tools::getValue($key));
            }


            if ((int) \Tools::getValue('module_override_reinstall')) {
                $overrides = $this->uninstallOverrides() && $this->installOverrides();
                $this->_html .= $this->displayConfirmation(sprintf('%1$s<br>', $this->trans('Overrides resintalados OK', [], 'Modules.Sjboverrides.Admin')));
            }

            if ((int) \Tools::getValue('module_cache_clear')) {
                $this->vaciaCache();
                $this->_html .= $this->displayConfirmation(sprintf('%1$s<br>', $this->trans('Cache vaciado', [], 'Modules.Sjboverrides.Admin')));
            }

            $this->_html .= $this->displayConfirmation($this->trans('Configuracion de modulo actualizada.', [], 'Modules.Sjboverrides.Admin'));
        }
    } // postProcess

    /**
     * Formulario
     */
    public function renderForm()
    {

        $helper                           = new \HelperForm();
        $helper->module                   = $this;
        $helper->show_toolbar             = true;
        $helper->table                    = $this->table; // PS_CONFIGURATION!!
        $lang                             = new \Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language    = $lang->id; //$this->context->anguage->id
        $helper->allow_employee_form_lang =
        \Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

        // Estad pedidos
        $order_state = \OrderStateCore::getOrderStates($this->context->language->id);

        $this->list_order_state = [];

        foreach ($order_state as $OS) {

            $this->list_order_state[] = ['id_order_state' => $OS['id_order_state'], 'label' => $OS['name']];
        }
        foreach($this->config as $key=> $value) {
            $upper = static::myreplace($key);
            $datos2Form[$key] = \Configuration::get(self::PREFIX . $upper);
        }
        // $datos2Form['image_manager']             = (bool) \Configuration::get(self::PREFIX . 'IMAGEMANAGER');
        // $datos2Form['url_rewrite']               = (bool) \Configuration::get(self::PREFIX . 'URLREWRITE');
        // $datos2Form['order_history']             = (bool) \Configuration::get(self::PREFIX . 'ORDERHISTORY');
        // $datos2Form['id_order_history_state']       = (int) Configuration::get(self::PREFIX . 'ORDERHISTORYSTATE');
        // $datos2Form['debug_enabled']             = (bool) \Configuration::get(self::PREFIX . 'DEBUG_ENABLED');
        // $datos2Form['remove_data']               = (bool) \Configuration::get(self::PREFIX . 'REMOVE_DATA');

        // Fijos, non bbdd
        $datos2Form['module_override_reinstall'] = false;
        $datos2Form['module_cache_clear']        = false;
        $datos2Form['issjbsubmit']               = 0; // Cambiase por js

        $helper->identifier   = $this->identifier; // indice de this->table, neste caso  id_configuration
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
        . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = \Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $datos2Form, //$this->config,
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm(
            [
                $this->getFormOverrides(),
                $this->getFormPedidos(),
                $this->getFormDebug(),
            ]
        );
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

    public function getInstructions()
    {
        $this->context->smarty->assign([
            'moduleprefix' => self::PREFIX,
            'chacho'       => 'Dani',
        ]);
        return $this->context->smarty->fetch($this->getLocalPath() . 'views/admin/info-plugin.tpl');
    }

    // override pedidos
    public function getFormPedidos()
    {

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
                            $this->l('Se asigna la Fecha de factura cuado esta se genere y no con la fecha del pedido. Al activarlo se define la variable ') . '<b>' . static::PREFIX . 'ESTADO_FACTURADO</b>.'
                        ),

                        'name'   => 'order_history',

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
                        'form_group_class' => 'grupo-estadofacturado ' . ((bool) \Configuration::get(self::PREFIX . 'ORDERHISTORY') ? '' : 'hidden'),
                        'type'             => 'select',                     // This is a <select> tag.
                        'label'            => $this->l('Estado facturado'), // The <label> for this <select> tag.
                        'desc'             => sprintf(
                            '%1$s <strong>%2$s</strong>.',
                            $this->l('Define cuál es el estado FACTURADO necesario para asignar la fecha a la factura. Se asigna a la variable de config '), static::PREFIX . 'ORDERHISTORYSTATE'),

                        'cols'             => 3,
                        'class'            => 'sjboverrides',
                        'name'             => 'id_order_history_state', // The content of the 'id' attribute of the <select> tag.
                        'options'          => [
                            'id'    => 'id_order_state',
                            'name'  => 'label',
                            'query' => $this->list_order_state,
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

    // Override imageManager
    public function getFormImages()
    {
        // Cargar imaxes disponibles

        /*Tamaños de imaxe*/
		$this->list_imagesizes = array();
		$imagelist = ImageType::getImagesTypes(null, true); // $type = null, $order_by_size = false
        // $this->list_imagesizes[] = array('id_image_type' => 0, 'name' => $this->l('No mostrar'));

        foreach ($imagelist as $IL) {
            $this->list_imagesizes[] = array(
                'id_image_type' => (int) $IL['id_image_type'],
                'name' => sprintf('%1$s (<em>%2$s x %3$s</em>)', $IL['name'], $IL['width'], $IL['height']),
            );
        }

        $FORM =  [
            'form' => [

                'legend' => [
                    'title' => $this->l('Imagenes'),
                    'icon'  => 'icon-cog',
                ],

                'input'  => [

                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Imagenes chulas'),
                        'desc'   => sprintf('%1$s <strong>%2$s</strong> %3$s',
                            $this->l('Override de clase'),
                            $this->l('Imagemanager'),
                            $this->l('para recorte preciso de las imágenes. Se aplica a por defecto a las imagenes con nombre _sjb')
                        ),

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
                    // Mellox checboxes e si activas, pos a imaxe e ao lafdo un select co tipo de recorte
                    // SJB_CROP_HARD, SJB_CROP_AXUSTAR, SJB_CROP_FIT, etc
                    [
                        'form_group_class' => 'grupo-imagemanager ' . ((bool) \Configuration::get(self::PREFIX . 'IMAGEMANAGER') ? '' : 'hidden'),
                        'type'             => 'select',                     // This is a <select> tag.
                        'label'            => $this->l('Imagenes adicionales a procesar'), // The <label> for this <select> tag.
                        'desc'             => sprintf(
                            '%1$s <strong>%2$s</strong>.',
                            $this->l('elecciona que tipode iminaituras se procesarán de la misma forma '), static::PREFIX . 'ORDERHISTORYSTATE'),

                        'cols'             => 3,
                        'class'            => 'sjboverrides',
                        'name'             => 'id_order_history_state', // The content of the 'id' attribute of the <select> tag.
                        'options'          => [
                            'id'    => 'id_order_state',
                            'name'  => 'label',
                            'query' => $this->list_order_state,
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

        foreach($this->list_imagesizes as $item){
           // Añadir html e xestionalo pot js e pista??elemetnos js por exemplo???
        }


        $FORM['form']['input'][]= [
            'form_group_class' => 'grupo-imagemanager ' . ((bool) \Configuration::get(self::PREFIX . 'IMAGEMANAGER') ? '' : 'hidden'),
            'type'             => 'select',                     // This is a <select> tag.
            'label'            => $this->l('Imagenes adicionales a procesar'), // The <label> for this <select> tag.
            'desc'             => sprintf(
                '%1$s <strong>%2$s</strong>.',
                $this->l('selecciona que tipo de miniaturas se procesarán de la misma forma '), static::PREFIX . 'ORDERHISTORYSTATE'),

            'cols'             => 3,
            'class'            => 'sjboverrides',
            'name'             => 'id_order_history_state', // The content of the 'id' attribute of the <select> tag.
            'options'          => [
                'id'    => 'id_order_state',
                'name'  => 'label',
                'query' => $this->list_order_state,
            ],
        ];




        return $FORM;
    }
    // Resintalar overrides y activar overrdeis sin opciones
    public function getFormOverrides()
    {

        return [
            'form' => [

                'legend' => [
                    'title' => $this->l('Overrides'),
                    'icon'  => 'icon-cog',
                ],

                'input'  => [

                    [
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
                   
                    // [
                    //     'type'   => 'switch',
                    //     'label'  => $this->l('URL Rewrite'),
                    //     'desc'   => sprintf(
                    //         '%1$s <strong>%2$s</strong> %3$s',
                    //         $this->l('Override de '),
                    //         $this->l('PS_ROUTES'),
                    //         $this->l('para limpiar las URLs y eque no contengas referencia a los id\'s.')
                    //     ),
                    //     'name'   => 'url_rewrite',
                    //     //'is_bool' => true,
                    //     'values' => [
                    //         [
                    //             'id'    => 'url_rewrite_on',
                    //             'value' => 1,
                    //             'label' => $this->l('On'),
                    //         ],
                    //         [
                    //             'id'    => 'url_rewrite_off',
                    //             'value' => 0,
                    //             'label' => $this->l('Off'),
                    //         ],
                    //     ],
                    // ],

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

    public function getFormDebug()
    {

        return [
            'form' => [

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

                        'name'   => 'debug_enabled',

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
                        'name'   => 'remove_data',
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
            ],
        ];
    }

    public function hookActionAdminControllerSetMedia($params)
    {

        // Segun parametros de configuracion creas variables js que servirán apara definir enlaces de backedn
        // sin ter que trastear cos tabs e os controllers!!

        /// if Module::isEnabled('ps_facetedsearch') || ! ($SJBOVERRIDES = Module::getInstanceByName('ps_facetedsearch'))
        // usar  Media::addJsDef($tojs);

        if (\Tools::getValue('controller') == 'AdminModules' && \Tools::getValue('configure') == $this->name) {
            $this->context->controller->addCSS('//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css', 'all');
            $this->context->controller->addCSS($this->_path . 'views/admin/admin.css', 'all');
            $this->context->controller->addJS($this->_path . 'views/admin/admin.js');
        }

        // Esto vai comun (menus backedn)
        //http://ps17.lan/adminweb/index.php?controller=AdminFeatures&updatefeature=&id_feature=1&token=c990a641087071078ae14e7f810b5620
        // AdminFeatures&viewfeature=&id_feature=1
        // "&updatefeature&id_feature=1",

        $tojs = ['sjboverride_links' =>
            [
                'sjboverrides'       => $this->context->link->getAdminLink('AdminModules') . "&configure=sjboverrides",
                'ps_faceted'         => $this->context->link->getAdminLink('AdminModules') . "&configure=ps_facetedsearch",
                'catalogo_etiquetas' => $this->context->link->getAdminLink('AdminFeatures') . "&viewfeature&id_feature=8",
                'pk_themesettings'   => $this->context->link->getAdminLink('AdminModules') . "&configure=pkthemesettings",
                'etiquetas_id'       => 1, // id_feature

            ],
        ];
        Media::addJsDef($tojs);
        $this->context->controller->addCSS($this->_path . 'views/admin/comun.css', 'all');
        $this->context->controller->addJS($this->_path . 'views/admin/comun.js');

    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        /*
            $params array(
            'newOrderStatus' => (object) OrderState,
            'oldOrderStatus' => (object) OrderState,
            'id_order' => (int) Order ID,
            cart,
            cookie,
            altern
            );
            */

        static::debug(__method__);

        $ORDER_HISTORY = (bool) \Configuration::get(self::PREFIX . 'ORDERHISTORY');

        if (! $ORDER_HISTORY) {
            return;
        }

        $ESTADO_FACTURADO = (int) \Configuration::get(static::PREFIX . 'ORDERHISTORYSTATE');

        $new_order_state_id = (int) $params['newOrderStatus']->id;

        if ($new_order_state_id == $ESTADO_FACTURADO) {
            $order               = new Order($params['id_order']);
            $order->invoice_date = date('Y-m-d H:i:s');
            $order->update();
            \ShopUrl::resetMainDomainCache();
            static::debug('Fecha asignada a factura');
        }
    }

    /*
            __        __  _       _                  _
            \ \      / / (_)   __| |   __ _    ___  | |_   ___
             \ \ /\ / /  | |  / _` |  / _` |  / _ \ | __| / __|
              \ V  V /   | | | (_| | | (_| | |  __/ | |_  \__ \
               \_/\_/    |_|  \__,_|  \__, |  \___|  \__| |___/
                                      |___/

        Debes usar o interface PrestaShop\PrestaShop\Core\Module\WidgetInterface

        e definir 2 metodos: renderWidget e getWidgetVariable

        Podes usar o widgets de 2 formas:

            <!-- php-->
            Hook::exec($hook_name)
            ou
            <!-- smarty -->
            {hook h='<hook_name>'}

        ou podes usar metodo novo:

            Hook::coreRenderWidget(Module $module, $hook_name, $params);

            ou

            <!-- ( smarty metodo 1) Generic call (metodo 1)-->
            {widget name='<module_name>'}

            <!-- ( smarty metodo 2) Call with a hook name -->
            {widget name='<module_name>' hook='<hook_name>'}

        */

    // https://devdocs.prestashop-project.org/8/modules/concepts/widgets/
    //Si implementas estos dejemplo:  debes hacer la llamada en el tpl!!
    public function renderWidget($hookName = null, array $configuration = [])
    {

        switch ($hookName) {

            default:
                return '<p>' . $hookName . ' <strong style="color:#c00">DESCONOCIDO</strong>.</p>';
                break;

        }
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        return [
            'Variable 1' => 'Valor 1',
            'Variable 2' => 'Valor 2',
        ];
    }
}
