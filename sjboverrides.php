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

class sjboverrides extends Module implements WidgetInterface{


    const PREFIX = 'SJBOVERRIDE_';

    protected $_hooks = array(
        'actionAdminControllerSetMedia',
        'actionOrderStatusPostUpdate'
    );

    public   $config = array(),
                $_loadedconfig,
                $error, $valid, $path,
                $list_order_state;

    protected $_html = '';
        // distonto, uso array d claves que se leen con  Configuration::get(PREFIX . $clave)

    static $debugEnabled = true;


    /*
        * Debug de clase
        * Uso:   self::debug('error aqui')
        */
    static function debug($msg = '', $level = 1){
        if(!static::$debugEnabled)return;
        \PrestaShopLogger::addLog($msg, (int)$level);
    }

    /**
     * Constructor
     */
    public function __construct(){
        $this->name = 'sjboverrides';
        $this->tab = 'front_office_features';
        $this->version = '2.0.0';
        $this->need_instance = 0;
        $this->author = 'Daniel Prol «Cancrexo» ';
        $this->ps_versions_compliancy = array('min' => '1.7.6',  'max' =>_PS_VERSION_);
        $this->bootstrap = true;
        //$this->module_key = '';
        parent::__construct();

        $this->displayName = $this->l('SJB Overrides ');
        $this->description = $this->l('Módulo para insertar overrides, hooks y widgets varios.');
        $this->confirmUninstall = $this->l('¿Seguro que quieres desinstalar este módulo?');

        $this->error = false;
        $this->valid = false;
        $this->path = $this->_path; // Protected polo visto, logo uso unha public para os templates!
        static::$debugEnabled = (bool)\Configuration::get(self::PREFIX.'DEBUG_ENABLED');
    }

    public function install()
    {
        error_log('instalar');
        static::debug($this->name  . ' INSTALL');

        if (!parent::install()) {
            static::debug("Error en parent_install");
            return false;
        }

        foreach ($this->_hooks as $hook) {
            if (!$this->registerHook($hook)) {
                static::debug("Error en Hook $hook");
                return false;
            }
            static::debug("Hook $hook OK");
        }
        return $this->defaultValues(); // trivial!
    }

    public function uninstall(){
        $this->_clearCache('*');

        if (!parent::uninstall()) {
            return false;
        }

        foreach ($this->_hooks as $hook) {
            $this->unregisterHook($hook);
        }

        \Configuration::deleteByName(self::PREFIX.'IMAGEMANAGER' );
        \Configuration::deleteByName(self::PREFIX.'ORDERHISTORY' );
        \Configuration::deleteByName(self::PREFIX.'ORDERHISTORYSTATE' );
        \Configuration::deleteByName(self::PREFIX.'DEBUG_ENABLED');
        \Configuration::deleteByName(self::PREFIX.'REMOVE_DATA');

        return true;
    }

    /**
     * Valores por defecto
     *
     * @return void
     */
    protected function defaultValues(){


        \Configuration::updateValue(self::PREFIX.'IMAGEMANAGER', false );
        \Configuration::updateValue(self::PREFIX.'ORDERHISTORY', false );
        \Configuration::updateValue(self::PREFIX.'ORDERHISTORYSTATE', 0 );
        \Configuration::updateValue(self::PREFIX.'DEBUG_ENABLED', true );
        \Configuration::updateValue(self::PREFIX.'REMOVE_DATA', false );
        return true;
    }


    /**
     * Pantalla de configuracion/ administración
     *
     */
    public function getContent(){
        $this->postProcess();
        $this->context->smarty->assign('path', $this->getPathUri() . 'views/'); // uri path for images
        $this->_html .= $this->renderForm();
        return $this->_html;
    }

    public function postProcess()
    {
        if (\Tools::isSubmit('sjbSubmitConfig') ) {

            // arrays are not assigned by reference!
            $formvalues = array(); // array with sanitized values

            \Configuration::updateGlobalValue(self::PREFIX .'IMAGEMANAGER', (int)Tools::getValue('image_manager'));
            \Configuration::updateGlobalValue(self::PREFIX .'ORDERHISTORY', (int)Tools::getValue('order_history'));
            \Configuration::updateGlobalValue(self::PREFIX .'ORDERHISTORYSTATE', (int)Tools::getValue('id_estado_facturado'));
            \Configuration::updateGlobalValue(self::PREFIX .'DEBUG_ENABLED', (int)Tools::getValue('debug_enabled'));
            \Configuration::updateGlobalValue(self::PREFIX .'REMOVE_DATA',(int)Tools::getValue('remove_data'));



            if((int)\Tools::getValue('module_override_reinstall')){
                $overrides = $this->uninstallOverrides() &&  $this->installOverrides();
                $this->_html .= $this->displayConfirmation( sprintf('%1$s<br>',  $this->trans('Overrides resintalados OK', [], 'Modules.Sjboverrides.Admin')));
            }

            if((int)\Tools::getValue('module_cache_clear')){
                $this->vaciaCache();
                $this->_html .= $this->displayConfirmation( sprintf('%1$s<br>',  $this->trans('Cache vaciado', [], 'Modules.Sjboverrides.Admin')));
            }

            $this->_html .= $this->displayConfirmation( $this->trans('Configuracion de modulo actualizada.', [], 'Modules.Sjboverrides.Admin') );
        }
    } // postProcess


   /**
   * Formulario
   */
   public function renderForm(){

      $helper = new \HelperForm();
      $helper->module = $this;
      $helper->show_toolbar = true;
      $helper->table = $this->table; // PS_CONFIGURATION!!
      $lang = new \Language((int) Configuration::get('PS_LANG_DEFAULT'));
      $helper->default_form_language = $lang->id; //$this->context->anguage->id
      $helper->allow_employee_form_lang =
      \Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

      // Estad pedidos
		$order_state = \OrderStateCore::getOrderStates( $this->context->language->id);

		$this->list_order_state = array();

		foreach($order_state as $OS){

			$this->list_order_state[] = array( 'id_order_state'  => $OS['id_order_state'], 'label' => $OS['name']);
		}



      $datos2Form['image_manager'] = (bool) \Configuration::get(self::PREFIX.'IMAGEMANAGER');
      $datos2Form['order_history'] = (bool) \Configuration::get(self::PREFIX.'ORDERHISTORY');
      $datos2Form['id_estado_facturado'] = (int) Configuration::get(self::PREFIX.'ORDERHISTORYSTATE');
      $datos2Form['module_override_reinstall']= false;
      $datos2Form['module_cache_clear']= false;
      $datos2Form['debug_enabled'] = (bool) \Configuration::get(self::PREFIX.'DEBUG_ENABLED');
      $datos2Form['remove_data'] = (bool) \Configuration::get(self::PREFIX.'REMOVE_DATA');
      $datos2Form['issjbsubmit'] = 0; // Cambiase por js

      $helper->identifier = $this->identifier; // indice de this->table, neste caso  id_configuration
      $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
      . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
      $helper->token = \Tools::getAdminTokenLite('AdminModules');

      $helper->tpl_vars = array(
         'fields_value' => $datos2Form, //$this->config,
         'languages' => $this->context->controller->getLanguages(),
         'id_language' => $this->context->language->id,
      );


      return $helper->generateForm(
         array(
            $this->getFormOverrides(),
            $this->getFormHooks(),
            $this->getFormDebug(),
         )
      );
   }

   public function vaciaCache(){
      static::debug('Borrando cache prestashop', 2);
      \Tools::clearSmartyCache();
      \Tools::clearXMLCache();
      \Media::clearCache();
      \Tools::generateIndex();
      static::debug('cache prestashop borrado', 2);
   }



   public function getInstructions(){
         $this->context->smarty->assign(array(
         'moduleprefix' => self::PREFIX,
         'chacho'=>'Dani'
         ));
      return $this->context->smarty->fetch($this->getLocalPath() . 'views/admin/info-plugin.tpl');
   }

   public function getFormHooks(){


      return array(
         'form' => array(

               'legend' => array(
                  'title' => $this->l('Hooks'),
                  'icon' => 'icon-cog'
                ),

               'input' => array(

                    array(
                        'type' => 'switch',
                        'label' => $this->l('OrderHistory'),
                        'desc' => sprintf('%1$s <strong>%2$s</strong> %3$s',
                            $this->l('Hook para la gestión de'),
                            $this->l('Facturas'),
                            $this->l('Se asigna la Fecha de factura cuado esta se genere y no con la fecha del pedido. Necesario definir variable '). '<b>'.static::PREFIX . 'ESTADO_FACTURADO</b>.'
                        ),

                        'name' => 'order_history',

                        'values' => array(
                            array(
                                'id' => 'ESTADO_FACTURADO',
                                'value' => 1,
                                'label' => $this->l('On')
                            ),
                            array(
                                'id' => 'order_history_off',
                                'value' => 0,
                                'label' => $this->l('Off')
                            )
                        )
                    ),
                    array(
                        'form_group_class'=>'grupo-estadofacturado ' . ((bool)\Configuration::get(self::PREFIX.'ORDERHISTORY') ? '' : 'hidden'),
                        'type'     => 'select', // This is a <select> tag.
                        'label'    => $this->l('Estado facturado'), // The <label> for this <select> tag.
                        'desc'   => sprintf(
                            '%1$s <strong>%2$s</strong>.',
                            $this->l('Define cuál es el estado FACTURADO necesario para asignar la fecha a la factura. Se asigna a la variable de config '), static::PREFIX . 'ORDERHISTORYSTATE'),

                        'cols' => 3,
                        'class' => 'sjboverrides',
                        'name'     => 'id_estado_facturado', // The content of the 'id' attribute of the <select> tag.
                        'options'  => array(
                            'id'    => 'id_order_state',
                            'name'  => 'label',
                            'query' => $this->list_order_state,
                        )
                    )
               ),
               'submit' => array(
                  'title' => $this->l('Save'),
                  'name' => 'sjbSubmitConfig',
                  'class' => 'sjbsubmit btn btn-default pull-right'
               )
         )
      );
   }

   public function getFormOverrides(){


      return array(
         'form' => array(

               'legend' => array(
                  'title' => $this->l('Overrides'),
                  'icon' => 'icon-cog'
               ),

               'input' => array(


                  array(
                     'type' => 'switch',
                     'label' => $this->l('Image Manager'),
                     'desc' => sprintf(
                        '%1$s <strong>%2$s</strong> %3$s',
                        $this->l('Override de clase'),
                        $this->l('Imagemanager'),
                        $this->l('para recorte preciso de las imágenes. Añade funcionalidad _sjb para fondo transparente.')),
                     'name' => 'image_manager',
                     'values' => array(
                           array(
                              'id' => 'image_manager_on',
                              'value' => 1,
                              'label' => $this->l('On')
                           ),
                           array(
                              'id' => 'image_manager_off',
                              'value' => 0,
                              'label' => $this->l('Off')
                           )
                     )
                  ),

                  array(
                     'form_group_class'=>'grupo-reinstalloverrides alert-danger',
                     'type' => 'switch',
                     'label' =>sprintf('<span style="color:#c00">%1$s</span> %2$s',$this->l('REINSTALL'), $this->l('Overrides')),
                     'desc' => sprintf('<em style="color:#001">%1$s</em>',$this->l('Fuerza la reinstalacion de TODOS los  overrides de este plugin.')),
                     'name' => 'module_override_reinstall',
                     'values' => array(
                           array(
                              'id' => 'override_reinstall_on',
                              'value' => 1,
                              'label' => $this->l('Ejecutar')
                           ),
                           array(
                              'id' => 'override_reinstall_off',
                              'value' => 0,
                              'label' => $this->l('Off')
                           )
                     )
                  )

               ),

               'submit' => array(
                  'title' => $this->l('Save'),
                  'name' => 'sjbSubmitConfig',
                  'class' => 'sjbsubmit btn btn-default pull-right'
               )
         )
      );
   }



   public function getFormDebug(){



      return array(
         'form' => array(

               'legend' => array(
                  'title' => $this->l('Parámetros generales módulo'),
                  'icon' => 'icon-cog'
               ),

               'error' => sprintf('<strong>%1$s</strong> %2$s','OJO', $this->l('Parámetros de configuracion y desarrollo')),

               'input' => array(

                array(
                    'form_group_class'=>'grupo-clearcache ',
                    'type' => 'switch',
                    'label' =>'Vaciar la cache de Prestashop',
                    'desc' => sprintf('<em style="color:#001">%1$s</em>',$this->l('Si cambias el metodo de regeneración de imagenes puede que tengas que vaciar el cache. Puedes hacerlo desde aquí o bien desde las opciones de Prestashop.')),

                    'name' => 'module_cache_clear',

                    'values' => array(
                        array(
                            'id' => 'cache_clear_on',
                            'value' => 1,
                            'label' => $this->l('Vaciar')
                        ),
                        array(
                            'id' => 'cache_clear_off',
                            'value' => 0,
                            'label' => $this->l('Off')
                        ))
                    ),

                    array(
                        'type' => 'switch',
                        'label' => $this->l('Debug Mode'),
                        'desc' => sprintf(
                            '%1$s <em style="color:#c00">%2$s</em>',
                            $this->l('If active, module  will log messages to PS registry'),
                            'PS registry'),

                        'name' => 'debug_enabled',

                        'values' => array(
                            array(
                                'id' => 'debug_enabled_on',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'debug_enabled_off',
                                'value' => 0,
                                'label' => $this->l('No')
                        ))
                    ),

                    array(
                        'type' => 'switch',
                        'label' => $this->l('Remove data on uninstall'),
                        'desc' => $this->l('If set to true, all config data will be removed from database on uninstall.'),
                        'name' => 'remove_data',
                        'values' => array(
                            array(
                                'id' => 'remove_data_on',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'remove_data_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            )
                        )
                    )
               ),

               'submit' => array(
                  'title' => $this->l('Save'),
                  'name' => 'sjbSubmitConfig',
                  'class' => 'sjbsubmit btn btn-default pull-right'

               )
         )
      );
   }



   public function hookActionAdminControllerSetMedia($params)
   {
      if (\Tools::getValue('controller') == 'AdminModules' && \Tools::getValue('configure') == $this->name) {
         $this->context->controller->addCSS('//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css', 'all');
         $this->context->controller->addCSS($this->_path . 'views/admin/admin.css', 'all');
         $this->context->controller->addJS($this->_path . 'views/admin/admin.js');
      }
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

      $ORDER_HISTORY = (bool) \Configuration::get(self::PREFIX.'ORDERHISTORY');

      if(!$ORDER_HISTORY){
         return;
      }

      $ESTADO_FACTURADO =  (int)\Configuration::get(static::PREFIX . 'ORDERHISTORYSTATE');

      $new_order_state_id = (int)$params['newOrderStatus']->id;

      if( $new_order_state_id == $ESTADO_FACTURADO){
         $order = new Order($params['id_order']);
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
   public function renderWidget($hookName = null, array $configuration = []){

      switch($hookName){


         default:
            return '<p>'.$hookName.' <strong style="color:#c00">DESCONOCIDO</strong>.</p>';
         break;

      }
   }



   public function getWidgetVariables($hookName = null , array $configuration = [] ) {
      return [
        'Variable 1' => 'Valor 1',
        'Variable 2'=> 'Valor 2'
      ];
   }


}
