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

if (!defined('_PS_VERSION_')) {
    exit;
}

class sjboverrides extends Module
{

    const PREFIX = 'SJB_OVERRIDE_';

    protected $_hooks = array(
        'actionAdminControllerSetMedia',
    );

    public $config = array();
    public $_loadedconfig;
    private $html = '';

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
      $this->version = '1.4';
      $this->author = 'SJB DIXITAL ';
      $this->tab = 'front_office_features';
      $this->need_instance = 0;
      $this->bootstrap = true;

      parent::__construct();

      $this->displayName = $this->l('SJB Overrides ');
      $this->description = $this->l('Módulo para insertar overrides. Sin hooks excepto actionAdminControllerSetMedia');
      $this->confirmUninstall = $this->l('¿Seguro que quieres desinstalar este módulo?');

      $this->error = false;
      $this->valid = false;
      $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.7.9.99');
      $this->path = $this->_path; // Protected polo visto, logo uso unha public para os templates!
      self::$debugEnabled = (bool) Configuration::get(self::PREFIX.'DEBUG_ENABLED');
   }

   public function install()
   {
      self::debug('INSTALL');

      if (!parent::install()) {
         self::debug("Error en parent_install");
         return false;
      }

      foreach ($this->_hooks as $hook) {
         if (!$this->registerHook($hook)) {
               self::debug("Error en Hook $hook");
               return false;
         }
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


      Configuration::deleteByName(self::PREFIX.'DEBUG_ENABLED');
      Configuration::deleteByName(self::PREFIX.'REMOVE_DATA');

      return true;
   }

   /**
   * Valores por defecto
   *
   * @return void
   */
   protected function defaultValues(){


   Configuration::updateValue(self::PREFIX.'DEBUG_ENABLED', true );
   Configuration::updateValue(self::PREFIX.'REMOVE_DATA', false );
   return true;
   }


   /**
   * Pantalla de configuracion/ administración
   *
   */
   public function getContent(){
      $this->postProcess();
      $this->context->smarty->assign('path', $this->getPathUri() . 'views/'); // uri path for images
      $this->html .= $this->renderForm();
      $this->html .=  $this->getInstructions();
      return $this->html;
   }

public function postProcess()
   {
      if (Tools::isSubmit('submitUpdatesjboverrides') && (int) Tools::getValue('submitUpdatesjboverrides') == 1) {

         self::debug(__METHOD__ . "\r\n" . var_export(Tools::getAllValues(), true));

         $formvalues = array(); // array with sanitized values
         // arrays are not assigned by reference!

         Configuration::updateGlobalValue(self::PREFIX .'DEBUG_ENABLED', (int)Tools::getValue('debug_enabled'));
         Configuration::updateGlobalValue(self::PREFIX .'REMOVE_DATA',(int)Tools::getValue('remove_data'));

         // Para que se amosen de novo en caso de error!! $this->config = $formvalues;

      }

   } // postProcess


   /**
   * Formulario
   */
public function renderForm(){

   $helper = new HelperForm();
   $helper->module = $this;
   $helper->show_toolbar = true;
   $helper->table = $this->table; // PS_CONFIGURATION!!
   $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
   $helper->default_form_language = $lang->id; //$this->context->anguage->id
   $helper->allow_employee_form_lang =
   Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

   $datos2Form =$this->config;
   $datos2Form['debug_enabled'] = (bool) Configuration::get(self::PREFIX.'DEBUG_ENABLED');
   $datos2Form['remove_data'] = (bool) Configuration::get(self::PREFIX.'REMOVE_DATA');
   $datos2Form['issjbsubmit'] = 0; // Cambiase por js

   $helper->identifier = $this->identifier; // indice de this->table, neste caso  id_configuration
   $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
   . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
   $helper->token = Tools::getAdminTokenLite('AdminModules');

   $helper->tpl_vars = array(
      'fields_value' => $datos2Form, //$this->config,
      'languages' => $this->context->controller->getLanguages(),
      'id_language' => $this->context->language->id,
   );


   return $helper->generateForm(
   array(
      $this->getFormDebug(),
      )
   );
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
   //return $this->fetch($templateFilePath, $this->getCacheId());
      return $this->context->smarty->fetch($this->getLocalPath() . 'views/admin/info-plugin.tpl');
}




public function getFormDebug(){



      return array(
         'form' => array(

               'legend' => array(
                  'title' => $this->l('Debug Options'),
                  'icon' => 'icon-cog',
               ),

               'description' => $this->l('Parámetros de configuracion y desarrollo'),

               'input' => array(


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
                  'name' => 'submitConfig',
                  'class' => 'sjbsubmit btn btn-default pull-right',

               ),
         ),
      );
   }



   public function hookActionAdminControllerSetMedia($params)
   {
      if (Tools::getValue('controller') == 'AdminModules' && Tools::getValue('configure') == $this->name) {
         $this->context->controller->addCSS('//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css', 'all');
         $this->context->controller->addCSS($this->_path . 'views/css/admin.css', 'all');
         $this->context->controller->addJS('//code.jquery.com/ui/1.11.4/jquery-ui.js', 'all');
         $this->context->controller->addJS($this->_path . 'views/js/admin.js');
      }
   }

}
