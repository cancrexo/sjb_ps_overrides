<?php
/**
* 2012-2020 SJBDIXITAL
*
* NOTICE OF LICENSE
*
* This is a commercial license
* Do not allow to re-sales, edit without permission from PrestaSOO.
* International Registered Trademark & Property of PrestaSOO
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
// use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class sjboverrides extends Module{


   const PREFIX = 'SJBOVERRIDE_';

   protected $_hooks = array(
      'actionAdminControllerSetMedia',
      'actionOrderStatusPostUpdate'
   );

   // Novas rutas seo friendly
   protected $routes = array(

      'category_rule' => '{rewrite}/',
      // 'product_rule' => '{rewrite}.html',  // producto-.html

      //'module' => 'module/{module}{/:controller}',
      //'cms_category_rule' => 'content/category/{id}-{rewrite}',
      //'cms_rule' => 'info/{rewrite}',
      //'layered_rule' => '{id}-{rewrite}{/:selected_filters}',
      //'manufacturer_rule' => 'marca-{id}-{rewrite}',
      //'supplier_rule' => 'proveedor-{id}-{rewrite}',
   );

   public   $config = array(),
            $_loadedconfig,
            $error, $valid, $path,
            $list_order_state;

   protected $_html = '';
    // distonto, uso array d claves que se leen con  Configuration::get(PREFIX . $clave)

    static $debugEnabled = false;


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
   public function __construct()
   {
      $this->name = 'sjboverrides';
      $this->version = '2.1';
      $this->author = 'SJB DIXITAL ';
      // $this->tab = 'front_office_features';
      $this->need_instance = 0;
      $this->bootstrap = true;

      parent::__construct();

      $this->displayName = $this->l('SJB Overrides ');
      $this->description = $this->l('Módulo para insertar overrides, hooks y widgets varios.');
      $this->confirmUninstall = $this->l('¿Seguro que quieres desinstalar este módulo?');

      $this->error = false;
      $this->valid = false;
      $this->ps_versions_compliancy = array('min' => '1.7', 'max' =>_PS_VERSION_);
      $this->path = $this->_path; // Protected polo visto, logo uso unha public para os templates!
      static::$debugEnabled = (bool)\Configuration::get(self::PREFIX.'DEBUG_ENABLED');
   }

   public function install()
   {
      static::debug('INSTALL');

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

      if(!$this->installRoutes())return false;



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
      \Configuration::deleteByName(self::PREFIX.'URLREWRITE' );
      \Configuration::deleteByName(self::PREFIX.'ORDERHISTORY' );
      \Configuration::deleteByName(self::PREFIX.'ORDERHISTORYSTATE' );
      \Configuration::deleteByName(self::PREFIX.'DEBUG_ENABLED');
      \Configuration::deleteByName(self::PREFIX.'REMOVE_DATA');
      $this->restoreRoutes();
      return true;
   }

   /**
   * Valores por defecto
   *
   * @return void
   */
   protected function defaultValues(){
      \Configuration::updateValue(self::PREFIX.'IMAGEMANAGER', FALSE );
      \Configuration::deleteByName(self::PREFIX.'URLREWRITE', FALSE );
      \Configuration::updateValue(self::PREFIX.'ORDERHISTORY', FALSE );
      \Configuration::updateValue(self::PREFIX.'ORDERHISTORYSTATE', 0 );
      \Configuration::updateValue(self::PREFIX.'DEBUG_ENABLED', true );
      \Configuration::updateValue(self::PREFIX.'REMOVE_DATA', false );
      return true;
   }


   /* New permalinks struct*/
   public function installRoutes(){

      // --- BACKUP OLD ROUTES- --- 

      // if exists backup, rename it
      if(\Configuration::hasKey(self::PREFIX . 'OLD_ROUTES')){
         // Reannme to something random:
         $tmpstr =  self::xeneraString(6);
         \Configuration::updateValue(self::PREFIX . 'OLD_ROUTES_' . $tmpstr, \Configuration::get(self::PREFIX . 'OLD_ROUTES'));
      }

      // Now backup routes
      $tmp = [];
      foreach ($this->routes as $route => $newvalue) {
         if(\Configuration::hasKey('PS_ROUTE_' . $route)){
            $tmp = \Configuration::get('PS_ROUTE_' . $route);
            $_myarr['PS_ROUTE_' . $route] = $tmp;
         }
      }
      // And if at least 1 config route:
      if(count($_myarr))
         \Configuration::updateGlobalValue(self::PREFIX . 'OLDROUTES', json_encode($_myarr, JSON_HEX_QUOT | JSON_HEX_TAG), true);


      // Finally install new routes
      foreach($this->routes as $route => $pattern){
          \Configuration::updateValue($route, $pattern);
      }
      return true;
   }


   /* Restaura routes vellas */
   public function restoreRoutes(){
      // Restore old routes
      if(\Configuration::hasKey(self::PREFIX . 'OLD_ROUTES')){
         $oldroutes = json_decode(\Configuration::get(self::PREFIX . 'OLD_ROUTES'), true);
         foreach($oldroutes as $key => $value){
            \Configuration::updateValue($key, $value);
         }
         \Configuration::deleteByName(self::PREFIX . 'OLD_ROUTES');
      }
      return true;
   }


   /**
   * Pantalla de configuracion/ administración
   *
   */
   public function getContent(){
      $this->postProcess();
      $this->_html .=  $this->getInstructions();
      $this->context->smarty->assign('path', $this->getPathUri() . 'views/'); // uri path for images
      $this->_html .= $this->renderForm();
      return $this->_html;
   }

   public function postProcess(){
      if (\Tools::isSubmit('sjbSubmitConfig') ) {

         // static::debug(__METHOD__ . "\r\n" . var_export(Tools::getAllValues(), true));

         $formvalues = array(); // array with sanitized values
         // arrays are not assigned by reference!

         \Configuration::updateGlobalValue(self::PREFIX .'IMAGEMANAGER', (int)Tools::getValue('image_manager'));
         \Configuration::updateGlobalValue(self::PREFIX .'URLREWRITE', (int)Tools::getValue('url_rewrite'));
         \Configuration::updateGlobalValue(self::PREFIX .'ORDERHISTORY', (int)Tools::getValue('order_history'));
         \Configuration::updateGlobalValue(self::PREFIX .'ORDERHISTORYSTATE', (int)Tools::getValue('id_estado_facturado'));
         \Configuration::updateGlobalValue(self::PREFIX .'DEBUG_ENABLED', (int)Tools::getValue('debug_enabled'));
         \Configuration::updateGlobalValue(self::PREFIX .'REMOVE_DATA',(int)Tools::getValue('remove_data'));



         if((int)\Tools::getValue('module_override_reinstall')){
            $overrides = $this->uninstallOverrides() &&  $this->installOverrides();
            // $this->confirmations[]= sprintf('%1$s<br>',  $this->l('Overrides resintalados OK'));
            $this->_html .= $this->displayConfirmation( sprintf('%1$s<br>',  $this->trans('Overrides resintalados OK', [], 'Modules.Sjboverrides.Admin')));
         }

         if((int)\Tools::getValue('module_cache_clear')){
            $this->vaciaCache();
            //$this->_html .=sprintf('%1$s<br>',  $this->l('Overrides resintalados OK'));
            $this->_html .= $this->displayConfirmation( sprintf('%1$s<br>',  $this->trans('Cache vaciado', [], 'Modules.Sjboverrides.Admin')));
         }
         // Para que se amosen de novo en caso de error!! $this->config = $formvalues;
         //   $this->_postErrors[] = $this->trans('Account details are required.', [], 'Modules.Wirepayment.Admin');

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
      $datos2Form['url_rewrite'] = (bool) \Configuration::get(self::PREFIX.'URLREWRITE');
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

      // $templateFile = 'info-plugin.tpl';
      // $templateFilePath = 'module:'.$this->name. '/views/templates/admin/'. $templateFile;
      // if (!$this->isCached($templateFile, $this->getCacheId())){
      //    // if( !($info_cupon = $this->getcuponDiario())) return false;
      //    // $info_cupon['path'] = $this->_path;
      //    // $this->smarty->assign(
      //    //    $info_cupon
      //    // );
      // }
         $this->context->smarty->assign(array(

         'moduleprefix' => self::PREFIX,
         'chacho'=>'Dani'

         ));
      //    //    $info_cupon
      //    // );
      //return $this->fetch($templateFilePath, $this->getCacheId());
      return $this->context->smarty->fetch($this->getLocalPath() . 'views/admin/info-plugin.tpl');
   }

   public function getFormHooks(){


      return array(
         'form' => array(

               'legend' => array(
                  'title' => $this->l('Hooks'),
                  'icon' => 'icon-cog',
               ),
                // success, description, warning, error
               // 'warning' => $this->l('Hooks de configuracion y desarrollo'),

               'input' => array(




                  array(
                     'type' => 'switch',
                     'label' => $this->l('OrderHistory'),
                     'desc' => sprintf('%1$s <strong>%2$s</strong> %3$s',
                           $this->l('Hook para la gestión de'),
                           $this->l('Facturas'),
                           $this->l('Se asigna la Fecha de factura cuado esta se genere y no con la fecha del pedido. Necesario definir variable '). '<b>'.static::PREFIX . 'ESTADO_FACTURADO</b>.'
                     ),
                         //'hint'   => $this->l('Ahi lo llevas.'),
                     'name' => 'order_history',
                     //'is_bool' => true,
                     'values' => array(
                           array(
                              'id' => 'ESTADO_FACTURADO',
                              'value' => 1,
                              'label' => $this->l('On'),
                           ),
                           array(
                              'id' => 'order_history_off',
                              'value' => 0,
                              'label' => $this->l('Off'),
                           ),
                     ),
                  ),
                  array(
                        'form_group_class'=>'grupo-estadofacturado ' . ((bool)\Configuration::get(self::PREFIX.'ORDERHISTORY') ? '' : 'hidden'),
                        'type'     => 'select', // This is a <select> tag.
                        'label'    => $this->l('Estado facturado'), // The <label> for this <select> tag.
                        'desc'   => sprintf(
                           '%1$s <strong>%2$s</strong>.',
                           $this->l('Define cuál es el estado FACTURADO necesario para asignar la fecha a la factura. Se asigna a la variable de config '), static::PREFIX . 'ESTADO_FACTURADO'),

                        'cols' => 3,
                        'class' => 'sjboverrides',
                        'name'     => 'id_estado_facturado', // The content of the 'id' attribute of the <select> tag.
                        //'required' => true, // Se pos true, non pode ir a 0
                        'options'  => array(
                           'id'    => 'id_order_state',
                           'name'  => 'label',
                           'query' => $this->list_order_state,
                        ),
                  ),





               ),
               'submit' => array(
                  'title' => $this->l('Save'),
                  'name' => 'sjbSubmitConfig',
                  'class' => 'sjbsubmit btn btn-default pull-right',
               )
         )
      );
   }

   public function getFormOverrides(){


      return array(
         'form' => array(

               'legend' => array(
                  'title' => $this->l('Overrides'),
                  'icon' => 'icon-cog',
               ),

               // success, description, warning, error
               // 'warning' => $this->l('Hooks de configuracion y desarrollo'),


               'input' => array(


                  array(
                     'type' => 'switch',
                     'label' => $this->l('Image Manager'),
                     'desc' => sprintf(
                        '%1$s <strong>%2$s</strong> %3$s',
                        $this->l('Override de clase'),
                        $this->l('Imagemanager'),
                        $this->l('para recorte preciso de las imágenes. Añade funcionalidad _sjb para fondo transparente.')
                        ),
                     'name' => 'image_manager',
                     //'is_bool' => true,
                     'values' => array(
                           array(
                              'id' => 'image_manager_on',
                              'value' => 1,
                              'label' => $this->l('On'),
                           ),
                           array(
                              'id' => 'image_manager_off',
                              'value' => 0,
                              'label' => $this->l('Off'),
                           ),
                     ),
                  ),
                   array(
                     'type' => 'switch',
                     'label' => $this->l('URL Rewrite'),
                     'desc' => sprintf(
                        '%1$s <strong>%2$s</strong> %3$s',
                        $this->l('Override de '),
                        $this->l('PS_ROUTES'),
                        $this->l('para limpiar las URLs y eque no contengas referencia a los id\'s.')
                        ),
                     'name' => 'url_rewrite',
                     //'is_bool' => true,
                     'values' => array(
                           array(
                              'id' => 'url_rewrite_on',
                              'value' => 1,
                              'label' => $this->l('On'),
                           ),
                           array(
                              'id' => 'url_rewrite_off',
                              'value' => 0,
                              'label' => $this->l('Off'),
                           ),
                     ),
                  ),



                  array(
                     'form_group_class'=>'grupo-reinstalloverrides alert-danger',
                     'type' => 'switch',
                     'label' =>sprintf('<span style="color:#c00">%1$s</span> %2$s',$this->l('REINSTALL'), $this->l('Overrides')),
                     'desc' => sprintf(
                           '<em style="color:#001">%1$s</em>',
                           $this->l('Fuerza la reinstalacion de TODOS los  overrides de este plugin.'),


                     ),
                     'name' => 'module_override_reinstall',
                     'values' => array(
                           array(
                              'id' => 'override_reinstall_on',
                              'value' => 1,
                              'label' => $this->l('Dale!'),
                           ),
                           array(
                              'id' => 'override_reinstall_off',
                              'value' => 0,
                              'label' => $this->l('Off'),
                           ),
                     ),
                  ),



               ),
               'submit' => array(
                  'title' => $this->l('Save'),
                  'name' => 'sjbSubmitConfig',
                  'class' => 'sjbsubmit btn btn-default pull-right',
               )
         )
      );
   }



   public function getFormDebug(){



      return array(
         'form' => array(

               'legend' => array(
                  'title' => $this->l('Parámetros generales módulo'),
                  'icon' => 'icon-cog',
               ),
               // success, description, warning, error
               'error' => sprintf('<strong>%1$s</strong> %2$s','OJO', $this->l('Parámetros de configuracion y desarrollo')),

               'input' => array(

                  array(
                     'form_group_class'=>'grupo-clearcache ',
                     'type' => 'switch',
                     'label' =>sprintf('Vaciar la cache de Prestashop'),
                     'desc' => sprintf(
                           '<em style="color:#001">%1$s</em>',
                           $this->l('Si cambias el metodo de regeneración de imagenes puede que tengas que vaciar el cache. Puedes hacerlo desde aquí o bien desde las opciones de Prestashop.'),


                     ),
                     'name' => 'module_cache_clear',
                     'values' => array(
                           array(
                              'id' => 'cache_clear_on',
                              'value' => 1,
                              'label' => $this->l('Vaciar'),
                           ),
                           array(
                              'id' => 'cache_clear_off',
                              'value' => 0,
                              'label' => $this->l('Off'),
                           ),
                     ),
                  ),



                  array(
                     'type' => 'switch',
                     'label' => $this->l('Debug Mode'),
                     'desc' => sprintf(
                           '%1$s <em style="color:#c00">%2$s</em>',
                           $this->l('If active, module  will log messages to PS registry'),
                           'PS registry'

                     ),
                     'name' => 'debug_enabled',
                     //'is_bool' => true,
                     'values' => array(
                           array(
                              'id' => 'debug_enabled_on',
                              'value' => 1,
                              'label' => $this->l('Yes'),
                           ),
                           array(
                              'id' => 'debug_enabled_off',
                              'value' => 0,
                              'label' => $this->l('No'),
                           ),
                     ),
                  ),

                  array(
                     'type' => 'switch',
                     'label' => $this->l('Remove data on uninstall'),
                     'desc' => $this->l('If set to true, all config data will be
                           removed from database on uninstall.'),
                     'name' => 'remove_data', // Si false amosa true, e viceversa
                     //'is_bool' => true,
                     'values' => array(
                           array(
                              'id' => 'remove_data_on',
                              'value' => 1,
                              'label' => $this->l('Yes'),
                           ),
                           array(
                              'id' => 'remove_data_off',
                              'value' => 0,
                              'label' => $this->l('No'),
                           ),
                     ),
                  ),




               ),
               'submit' => array(
                  'title' => $this->l('Save'),
                  'name' => 'sjbSubmitConfig',
                  'class' => 'sjbsubmit btn btn-default pull-right',

               ),
         ),
      );
   }



   public function hookActionAdminControllerSetMedia($params){
      if (\Tools::getValue('controller') == 'AdminModules' && \Tools::getValue('configure') == $this->name) {
         $this->context->controller->addCSS('//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css', 'all');
         $this->context->controller->addCSS($this->_path . 'views/admin/admin.css', 'all');
         // $this->context->controller->addJS('//code.jquery.com/ui/1.11.4/jquery-ui.js', 'all');
         $this->context->controller->addJS($this->_path . 'views/admin/admin.js');
      }
   }

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
   public function hookActionOrderStatusPostUpdate($params){
      static::debug(__method__);

      $ORDER_HISTORY = (bool) \Configuration::get(self::PREFIX.'ORDERHISTORY');

      if(!$ORDER_HISTORY){
         return;
      }

      $ESTADO_FACTURADO =  (int)\Configuration::get(static::PREFIX . 'ORDERHISTORYSTATE');

      $new_order_state_id = (int)$params['newOrderStatus']->id;

      // if(array_key_exists('oldOrderStatus', $params) && $params['oldOrderStatus'] instanceof OrderState){
      //    $old_order_state_id = (int)$params['oldOrderStatus']->id;
      // }

      if( $new_order_state_id == $ESTADO_FACTURADO){
         $order = new Order($params['id_order']);
         $order->invoice_date = date('Y-m-d H:i:s');
         $order->update();
         \ShopUrl::resetMainDomainCache();
         static::debug('Fecha asignada a factura');
      }
   }

  



   static function xeneraString($lonx = 6){
		$str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
		$cad = '';
		while( strlen($cad)< $lonx )$cad .= substr($str, mt_rand(0,62), 1);
		return $cad;
	}


}
