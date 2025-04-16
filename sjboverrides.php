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
declare(strict_types=1);
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class sjboverrides extends Module implements WidgetInterface
{

    const PREFIX = 'SJBOVERRIDE_';

    protected $_hooks = [
        'actionAdminControllerSetMedia',
        'actionOrderStatusPostUpdate',
    ];

    // Config inicial nome de clave en bbdd =nome de variable para GET/POST =>[valor por defecto, tipo valor(0,1,2,), ...]
    // todo
    protected $default_config = [
        'image_manager'             => [-1,],
        'product_idfeaturevalue'    => [-1],
        'order_history'             => [0],
        'id_order_history_state'    => [-1],
        'url_rewrite'               => [0],
        'debug_enabled'             => [1],
        'remove_data'               => [1],
    ];

    public
        $config,
        $_loadedconfig,
        $error, $valid, $path;

    protected $_html = '';
    // distonto, uso array d claves que se leen con  Configuration::get(PREFIX . $clave)

    static $debugEnabled = true;


    static function myreplace($str){
        $limpia = strtolower(str_ireplace(array('_'), '', $str));
        return $limpia;
    }

    /*
    * Debug de clase
    * Uso:   self::debug('error aqui')
    */
    public static function debug($msg = '', $level = 1){
        if (! static::$debugEnabled) return;
        \PrestaShopLogger::addLog($msg, (int) $level);
    }

    /**
     * Constructor
     */
    public function __construct() {
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
        // static::$debugEnabled = (bool) \Configuration::get(self::PREFIX . 'DEBUG_ENABLED') ?? true;
    }

    public function install() {

        error_log('instalar');

        static::debug($this->name . ' INSTALL');

        if (! parent::install()) {
            static::debug("Error en parent_install");
            return false;
        }

        if ( ! $this->installTabs() ) {
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



    /**
     * Valores por defecto
     *
     * @return void
     */
    protected function defaultValues() {

        \PrestaShopLogger::addLog(__METHOD__);
        $_config=[];
        foreach($this->default_config as $key=> $value) {
            $key = static::myreplace($key);
            $_config[$key] = reset($value);
        }

        \PrestaShopLogger::addLog('Confiemos');
        \Configuration::updateValue(static::PREFIX .'general_config', \Tools::jsonEncode($_config, JSON_HEX_QUOT | JSON_HEX_TAG), true);
        \PrestaShopLogger::addLog('Fin default values');
        return true;
    }

    public function installTabs() {
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
        $improve_tab_id = (int)Tab::getIdFromClassName('IMPROVE');
        $improve_tab    =  new Tab($improve_tab_id);

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

        // Creamos tabs
        $resultado = $this->createTabs($meusTabs, $improve_tab);

        if($resultado)
        PrestaShopLogger::addLog(__method__ .': Tabs creadas con éxito');

        return $resultado;

    }

    // Instalación recursiva de tabs
    protected function createTabs($arrTabs, $parent_tab = null){

        PrestaShopLogger::addLog(__method__ );

        foreach ($arrTabs as $i => $T) {
        $tab = new Tab();
        $tab->active = (int)$T['active'];
        $tab->enabled = (int)$T['enabled'];
        $tab->name = array();

        foreach (Language::getLanguages(false) as $lang) {
                $tab->name[(int) $lang['id_lang']] = $T['nombre'];
        }

        //$tab->id_parent = $parent_tab ? $parent_tab->id : (isset($T['parent_tab']) ? $T['parent_tab'] : 0); // parent -1 será invisile
        $tab->class_name = $T['class_name'];
        $tab->id_parent = $parent_tab ? $parent_tab->id : 0; // parent -1 será invisile

        $tab->module = isset($T['module']) ? $T['module'] : $this->name;

        if (!$tab->add()) {
            PrestaShopLogger::addLog(__method__ .': Error añadiendo tab('.$tab->class_name . ')', 3);
            return false;
        }

        // (Recursivo)
        if (array_key_exists('childtabs', $T)) {
                PrestaShopLogger::addLog('Tab: recursivo. Entrando.');
                return $this->createTabs($T['childtabs'], $tab);
        }
        PrestaShopLogger::addLog('Added Tab '. $T['nombre'] . '('. $T['class_name']. ')');

        }
        return true;
    }






    public function uninstall() {
        $this->_clearCache('*');

        if (! parent::uninstall()) {
            return false;
        }

        if(!$this->uninstallModuleTabs()){
            return false;
         }
        foreach ($this->_hooks as $hook) {
            $this->unregisterHook($hook);
        }
        \Configuration::deleteByName(self::PREFIX . 'config');

        return true;
    }


    // :-O
    private function uninstallModuleTabs(){

        PrestaShopLogger::addLog(__method__ );

        $moduleTabs = Tab::getCollectionFromModule($this->name);
        if (!empty($moduleTabs)) {
        foreach ($moduleTabs as $moduleTab) {
            if (!$moduleTab->delete()) {
                return false;
            }
        }
        }
        // Root tab
        //   $tabMain = new Tab((int) Tab::getIdFromClassName('AdminPdvonlineMain'));
        //   return $tabMain->delete();
        return true;
   }





    /**
     * Pantalla de configuracion/ administración
     */
    public function getContent(){
		Tools::redirectAdmin($this->context->link->getAdminLink('AdminSjboverridesConfigController'));
	}










    // Pretty urls en productos e categorias
    protected function getFormOverrideRewrite(){
        return
        [
            'type'   => 'switch',
            'label'  => $this->l('URL Rewrite'),
            'desc'   => sprintf(
                '%1$s <strong>%2$s</strong> %3$s',
                $this->l('Override de '),
                $this->l('PS_ROUTES'),
                $this->l('para limpiar las URLs y eque no contengas referencia a los id\'s de producto y/o categoria.')
            ),
            //'is_bool' => true,
            'name'   => 'url_rewrite',
            'values' => [
                [
                    'id'    => 'url_rewrite_on',
                    'value' => 1,
                    'label' => $this->l('On'),
                ],
                [
                    'id'    => 'url_rewrite_off',
                    'value' => 0,
                    'label' => $this->l('Off'),
                ],
            ],
        ];

    }



    // Override imageManager **aproveitar
    public function getFormImagesold()
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
                    // Mellox checboxes e si activas, pos a imaxe e ao lado un select co tipo de recorte
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
    /*
          _   _                   _
         | | | |   ___     ___   | | __  ___
         | |_| |  / _ \   / _ \  | |/ / / __|
         |  _  | | (_) | | (_) | |   <  \__ \
         |_| |_|  \___/   \___/  |_|\_\ |___/


    */


    public function hookActionAdminControllerSetMedia($params){
        // Css e js comuns en todo o backedn

    }

    // Pasalom a widget
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
