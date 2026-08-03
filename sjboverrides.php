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
 * @author Cancrexo <cancrexo@gmail.com>
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
        // 'actionFrontControllerSetMedia',
        // 'actionFrontControllerInitAfter',
        // 'actionFrontControllerSetVariables'
        // 'displayProductBuilder',
        // 'displayBeforeBodyClosingTag'
    ];

    // Config inicial nome de clave en bbdd =nome de variable para GET/POST =>[valor por defecto, tipo valor(0,1,2,), ...]
    // todo
    protected $default_config = [
        'hook_image_manager'            =>[0], // Este non se usa de momento

        // TODO ARREGLAR ZONA ADMINISTRACION
        'hook_ocultar_manufacturer'           => [0], // si o hook esta activado
        'manufacturer_hidden_flag'      => [''], // valor que debe ter o campo meta-titulo da marca PARA OCULTARSE NO FRONT

        'hook_preguntar_producto'       => [0], // si o hook esta activado
        'product_idfeaturevalue'        => [-1],

        'hook_order_history_active'     => [0], // si o hook esta activado
        'id_order_history_state'        => [-1],

        // Sistema SJB de clientes / direccións (listados propios)
        'hook_clientes_active'          => [0],

        'url_rewrite'                   => [0], // si o hook esta activado
        'debug_enabled'                 => [1],
        'remove_data'                   => [1],
    ];

    public
        $config,
        $_loadedconfig,
        $error, $valid, $path;

    protected $_html = '';
    // distonto, uso array d claves que se leen con  Configuration::get(PREFIX . $clave)

    static $debugEnabled = false;

    // Quita underscores
    static function myreplace($str){
        // $limpia = strtolower(str_ireplace(array('_'), '', $str));
        $limpia = preg_replace('/[-_]/', '', $str);
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
        $this->version                = '2.4.0';
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

        \PrestaShopLogger::addLog(__METHOD__);

        if (! parent::install()) {
            static::debug("Error en parent_install");
            return false;
        }

        if ( ! $this->installTabs() ) {
            return false;
        }

        // Instalamos hooks:
        if(!$this->install_hooks()){
            return false;
        }
        \PrestaShopLogger::addLog('Hooks  OK');


        return (bool)$this->defaultValues(); // trivial!
    }


    public function install_hooks(){

        foreach ($this->_hooks as $hook) {
            if (! $this->registerHook($hook)) {
                static::debug("Error en Hook $hook");
                return false;
            }
        }
        return true;
    }

    /**
     * Valores por defecto
     *
     * @return array de datos
     */
    protected function defaultValues() {

        \PrestaShopLogger::addLog(__METHOD__);
        $_config=[];
        foreach($this->default_config as $key=> $value) {
            $key = static::myreplace($key); // Quita underscores e guions
            $_config[$key] = reset($value);
        }
        $this->saveData('general', $_config);
        \PrestaShopLogger::addLog('Fin default values');
        return $_config;
    }

    // para acceder dende controler -->setea valores por defecto e devolveos!
    public function setDefaultValues(){
        $_config = $this->defaultValues();
        $_config = array_combine(
            array_map(fn($key) => 'general_' . $key, array_keys($_config)),
            $_config
        );
        return $_config;
    }


    /**
     * Carga a configuracio que lle indiques
     *
     * @param [type] $key
     * @return ARRAY
     */
    public function getData( $key='general', $add_prefix = true) {

        return \Sjboverrides\SJB_PSTools::getInstance()->getStoredArray(static::PREFIX, $key,  $add_prefix);
    }

    /**
     * Pasas array a buscar e clave deseada de dito array:
     * p.e., general, manufacturer_hidden_flag
     *
     * @return void
     */
    public function getSingleData($arr, $key){
        $data = $this->getData($arr, false); // sin rpefixo general_ p.e.
        $key = static::myreplace($key);  // eliminamos guiones y underscores con preg_replace
        return $data[$key] ?? null;
    }


    // as claves xa deben vir sn undersocres nin guions
    public function saveData($key, $data){
        \Configuration::updateValue(static::PREFIX . $key.'_config' ,  \Tools::jsonEncode($data, JSON_HEX_QUOT | JSON_HEX_TAG), true);
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
                'nombre'     => sprintf('%1$s -%2$s',$this->l('SJB Overrides'),  $this->version),
                'class_name' => 'AdminSjboverridesConfig',
                'enabled'    => 1,
                'active'     => 1,
                'icon' => '',
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

        if ($resultado) {
            // Tabs SJB clientes/direccións (ocultas por defecto)
            $resultado = $this->syncClientesMenu(false);
            PrestaShopLogger::addLog(__method__ . ': Tabs creadas con éxito');
        }

        return $resultado;

    }

    /**
     * Crea (se faltan) e sincroniza a visibilidade do menú:
     * ON  -> oculta AdminCustomers/AdminAddresses e amosa SJB clientes/direccións
     * OFF -> restaura nativos e oculta os SJB
     *
     * @param bool|null $enabled null = ler da configuración gardada
     * @return bool
     */
    public function syncClientesMenu($enabled = null)
    {
        if ($enabled === null) {
            $enabled = (bool) $this->getSingleData('general', 'hook_clientes_active');
        }

        $parentId = (int) Tab::getIdFromClassName('AdminParentCustomer');
        if (!$parentId) {
            static::debug('AdminParentCustomer non atopado', 3);
            return false;
        }
        $parentTab = new Tab($parentId);

        $sjbTabs = [
            [
                'nombre'     => $this->l('SJB clientes'),
                'class_name' => 'AdminSjbClientes',
            ],
            [
                'nombre'     => $this->l('SJB direcciones'),
                'class_name' => 'AdminSjbDirecciones',
            ],
        ];

        foreach ($sjbTabs as $T) {
            $idTab = (int) Tab::getIdFromClassName($T['class_name']);
            if (!$idTab) {
                $creado = $this->createTabs([[
                    'nombre'     => $T['nombre'],
                    'class_name' => $T['class_name'],
                    'enabled'    => 1,
                    'active'     => $enabled ? 1 : 0,
                ]], $parentTab);
                if (!$creado) {
                    return false;
                }
                continue;
            }

            $tab = new Tab($idTab);
            $tab->active = $enabled ? 1 : 0;
            if (!$tab->update()) {
                static::debug('Erro actualizando tab ' . $T['class_name'], 3);
                return false;
            }
        }

        foreach (['AdminCustomers', 'AdminAddresses'] as $className) {
            $idTab = (int) Tab::getIdFromClassName($className);
            if (!$idTab) {
                continue;
            }
            $tab = new Tab($idTab);
            $tab->active = $enabled ? 0 : 1;
            if (!$tab->update()) {
                static::debug('Erro actualizando tab nativo ' . $className, 3);
                return false;
            }
        }

        if (method_exists('Tab', 'resetStaticCache')) {
            Tab::resetStaticCache();
        }

        return true;
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

        // Agregar soporte para iconos
        if (isset($T['icon'])) {
            $tab->icon = $T['icon'];
        }

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
        PrestaShopLogger::addLog(__method__);
        $this->_clearCache('*');

        // Restaurar menú nativo de clientes/direccións antes de borrar tabs do módulo
        $this->syncClientesMenu(false);

        if(!$this->uninstallModuleTabs()){
            PrestaShopLogger::addLog('Error uninstalling module tabs', 3);
            // return false; // Podemos decidir si continuar o no
        } else {
            PrestaShopLogger::addLog('uninstallModuleTabs OK');
        }

        \Configuration::deleteByName(self::PREFIX . 'general_config');
        PrestaShopLogger::addLog('delete general_config OK');

        // La llamada a parent::uninstall() ya se encarga de desregistrar los hooks.
        // Debe ser la última llamada para asegurar que las tareas personalizadas se completan primero.
        if (!parent::uninstall()) {
            PrestaShopLogger::addLog('parent::uninstall() failed', 3);
            return false;
        }

        PrestaShopLogger::addLog('parent::uninstall() OK');
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
     * Pantalla de configuracion/ administración -->CONTROLLERR
     */
    public function getContent(){
		Tools::redirectAdmin($this->context->link->getAdminLink('AdminSjboverridesConfig'));
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
    public function getFormImagesold(){
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
                            $this->l('Selecciona que tipo de miniaturas se procesarán de la misma forma '), static::PREFIX . 'ORDERHISTORYSTATE'),

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
        $this->context->controller->addCSS($this->_path . 'views/admin/comun.css', 'all');

        // Autocomplete cliente no alta de dirección (só se o sistema SJB clientes está ON)
        if ((bool) $this->getSingleData('general', 'hook_clientes_active')) {
            $this->loadAddressCustomerAutocompleteAssets();
        }
    }

    /**
     * Assets do autocomplete de cliente na ficha nativa de dirección.
     */
    protected function loadAddressCustomerAutocompleteAssets()
    {
        $requestUri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $ctrlName = isset($this->context->controller->controller_name)
            ? (string) $this->context->controller->controller_name
            : '';

        $isAddressPage = ($ctrlName === 'AdminAddresses')
            || (stripos($requestUri, '/sell/addresses') !== false);

        if (!$isAddressPage) {
            return;
        }

        $searchUrl = $this->context->link->getAdminLink('AdminCustomers', true, [
            'route' => 'admin_customers_search',
            'sf2' => 1,
        ]);

        Media::addJsDef([
            'sjbAddressCustomerSearch' => [
                'searchUrl' => $searchUrl,
                'label' => $this->l('Buscar cliente'),
                'placeholder' => $this->l('Nombre, apellidos o email…'),
                'help' => $this->l('Escribe para buscar y selecciona un cliente. Se rellenará el email oculto.'),
                'noResults' => $this->l('Sin resultados'),
                'error' => $this->l('Error al buscar clientes'),
            ],
            'sjbAddressStateSearch' => [
                'label' => $this->l('Provincia'),
                'placeholder' => $this->l('Escribe el nombre de la provincia…'),
                'help' => $this->l('Busca y selecciona la provincia. Se actualizará el estado nativo oculto.'),
                'noResults' => $this->l('Sin provincias coincidentes'),
            ],
            'sjbAddressAliasDefault' => $this->l('Mi nueva dirección.'),
        ]);

        $this->context->controller->addJS(
            $this->_path . 'views/admin/address-customer-autocomplete.js'
        );
    }


    // Pasalom a widget
    /**
     * Grestionamos o feito de que rpestasgop asigna fecha da factura á fecha do pedidoUndocumented function
     *
     * Asignamos fecha da factura cando o pedido pasa o estado definido como FACTURADO
     *
     * @param [type] $params
     * @return void
     */
    public function hookActionOrderStatusPostUpdate($params){
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

            METODO ANTIGO:
            <!-- php-->
            Hook::exec($hook_name)
            ou
            <!-- smarty -->
            {hook h='<hook_name>'}

        ou
        METODO NOVO:

            Hook::coreRenderWidget(Module $module, $hook_name, $params);

            ou

            <!-- ( smarty metodo 1) Generic call (metodo 1)-->
            {widget name='<module_name>'}

            <!-- ( smarty metodo 2) Call with a hook name -->
            {widget name='<module_name>' hook='<hook_name>'}


            OLLO:

            lembra
            Si enganchas o modulo tipo widget nun hook calquera no backend,
            si o modulo non ten ese metodo hook definido,
            enton sempre pasa por renderWidget e xa dependerá do que fags ali:
            amosarase a no ser que discrimines por metodo 2 (hook name)



                Mais info https://devdocs.prestashop-project.org/8/modules/concepts/widgets/
     */



    public function renderWidget($hookName = null, array $configuration = [])
    {

        switch ($hookName) {


            case 'displayProductBuilder':
                $this->context->controller->addJS($this->_path . 'views/product.js');
            break;

            default:
                return '<p>' . $hookName . ' <strong style="color:#c00"> No definido en '.__method__.'</strong>.</p>';
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
