<?php
/*
*
* MÓDULO PARA INSERTAR OVERRIDES
* Ruta: Dispatcher::getInstance()->dispatch()
* chamando a:  setRequest() -->setRequestUri() -->$this->loadRoutes()
* @author Cancrexo <dani@sjbdixital.es>
* @copyright 2024 SJB dixital
* @license   Commercial License. All right reserved
*/
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Dispatcher extends DispatcherCore
{



   /**
      *    Dispatcher::getInstance()->dispatch()
            -->setRequest()
                  -->setRequestUri()
                        -->$this->loadRoutes()
   *    e finalmmente
      Dispatcher::dispatch()
   */

      // O meu rewrite
     /*
     $this->default_routes['category_rule'] = [
         'controller' => 'category',
         'rule' => '{categories:/}{rewrite}/',
         'keywords' => array(
            'id' => array('regexp' => '[0-9]+'),
            'categories' => array('regexp' => '[/_a-zA-Z0-9-\pL]*', 'param' => 'categories_rewrite'),
            'rewrite' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'category_rewrite'),
            'meta_keywords' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
            'meta_title' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
         )
     ];*/

   /**
    * Find the controller and instantiate it.
    * Dispatcher::getInstance()->dispatch()
    *    -->setRequest()
    *       -->setRequestUri()
    *          -->$this->loadRoutes()
    *
    *  e finalmmente  Dispatcher::dispatch()
    *
    *                Dispatcher->getController
    *                ou
    *                useDefaultController
    */
   public function dispatch()
    {
      if (!Module::isEnabled('sjboverrides') || !($SJBOVERRIDES = Module::getInstanceByName('sjboverrides'))
      ||  !($override_enabled = (bool) Configuration::get($SJBOVERRIDES::PREFIX.'URLREWRITE'))
      ) {

         PrestaShopLogger::addLog(__method__ . ' - SJBOVERRIDES: URLREWRITE DISABLED', 1, null, 'Dispatcher', 1);
         parent::dispatch();
         return;
         // PrestaShopLogger::addLog('SJBOVERRIDES: ENDISPOIS DE DISPATCH', 1, null, 'Dispatcher', 1);
      }

      $controller_class = '';
       PrestaShopLogger::addLog('SJB_ '. __method__ . 'ANTES DE getController()', 1);
      // Get current controller
      $this->getController();
      if (!$this->controller) {
         $this->controller = $this->useDefaultController();
      }
      PrestaShopLogger::addLog('SJB_ '. __method__ . 'DESPOIS  DE getController()', 1);
      PrestaShopLogger::addLog('SJB_ '. ' line' . __LINE__ .'  '. $this->controller, 1);

      // Execute hook dispatcher before
      Hook::exec('actionDispatcherBefore', ['controller_type' => $this->front_controller]);

      // Dispatch with right front controller
      switch ($this->front_controller) {
         // Dispatch front office controller
         case self::FC_FRONT:
               $controllers = Dispatcher::getControllers([
                  _PS_FRONT_CONTROLLER_DIR_,
                  _PS_OVERRIDE_DIR_ . 'controllers/front/',
               ]);
               $controllers['index'] = 'IndexController';
               if (isset($controllers['auth'])) {
                  $controllers['authentication'] = $controllers['auth'];
               }
               if (isset($controllers['contact'])) {
                  $controllers['contactform'] = $controllers['contact'];
               }

               if (!isset($controllers[strtolower($this->controller)])) {
                  $this->controller = $this->controller_not_found;
               }

               PrestaShopLogger::addLog('SJB_ '. ' line' . __LINE__ .'  ' .__method__, 1);
               PrestaShopLogger::addLog('SJB_ '. ' line' . __LINE__ .'  ' . $this->controller, 1);


               $controller_class = $controllers[strtolower($this->controller)];
               $params_hook_action_dispatcher = [
                  'controller_type' => self::FC_FRONT,
                  'controller_class' => $controller_class,
                  'is_module' => 0,
               ];

               break;

         // Dispatch module controller for front office
         case self::FC_MODULE:
               $module_name = Validate::isModuleName(Tools::getValue('module')) ? Tools::getValue('module') : '';
               $module = Module::getInstanceByName($module_name);
               $controller_class = 'PageNotFoundController';
               if (Validate::isLoadedObject($module) && $module->active) {
                  $controllers = Dispatcher::getControllers(_PS_MODULE_DIR_ . "$module_name/controllers/front/");
                  if (isset($controllers[strtolower($this->controller)])) {
                     include_once _PS_MODULE_DIR_ . "$module_name/controllers/front/{$this->controller}.php";
                     if (file_exists(
                           _PS_OVERRIDE_DIR_ . "modules/$module_name/controllers/front/{$this->controller}.php"
                     )) {
                           include_once _PS_OVERRIDE_DIR_ . "modules/$module_name/controllers/front/{$this->controller}.php";
                           $controller_class = $module_name . $this->controller . 'ModuleFrontControllerOverride';
                     } else {
                           $controller_class = $module_name . $this->controller . 'ModuleFrontController';
                     }
                  }
               }
               $params_hook_action_dispatcher = [
                  'controller_type' => self::FC_FRONT,
                  'controller_class' => $controller_class,
                  'is_module' => 1,
               ];

         break;

         // Dispatch back office controller + module back office controller
         case self::FC_ADMIN:
               if ($this->use_default_controller
                  && !Tools::getValue('token')
                  && Validate::isLoadedObject(Context::getContext()->employee)
                  && Context::getContext()->employee->isLoggedBack()
               ) {
                  Tools::redirectAdmin(
                     "index.php?controller={$this->controller}&token=" . Tools::getAdminTokenLite($this->controller)
                  );
               }

               $tab = Tab::getInstanceFromClassName($this->controller, Configuration::get('PS_LANG_DEFAULT'));
               $retrocompatibility_admin_tab = null;

               if ($tab->module) {
                  if (file_exists(_PS_MODULE_DIR_ . "{$tab->module}/{$tab->class_name}.php")) {
                     $retrocompatibility_admin_tab = _PS_MODULE_DIR_ . "{$tab->module}/{$tab->class_name}.php";
                  } else {
                     $controllers = Dispatcher::getControllers(_PS_MODULE_DIR_ . $tab->module . '/controllers/admin/');
                     if (!isset($controllers[strtolower($this->controller)])) {
                           $this->controller = $this->controller_not_found;
                           $controller_class = 'AdminNotFoundController';
                     } else {
                           $controller_name = $controllers[strtolower($this->controller)];
                           // Controllers in modules can be named AdminXXX.php or AdminXXXController.php
                           include_once _PS_MODULE_DIR_ . "{$tab->module}/controllers/admin/$controller_name.php";
                           if (file_exists(
                              _PS_OVERRIDE_DIR_ . "modules/{$tab->module}/controllers/admin/$controller_name.php"
                           )) {
                              include_once _PS_OVERRIDE_DIR_ . "modules/{$tab->module}/controllers/admin/$controller_name.php";
                              $controller_class = $controller_name . (
                                 strpos($controller_name, 'Controller') ? 'Override' : 'ControllerOverride'
                              );
                           } else {
                              $controller_class = $controller_name . (
                                 strpos($controller_name, 'Controller') ? '' : 'Controller'
                              );
                           }
                     }
                  }
                  $params_hook_action_dispatcher = [
                     'controller_type' => self::FC_ADMIN,
                     'controller_class' => $controller_class,
                     'is_module' => 1,
                  ];
               } else {
                  $controllers = Dispatcher::getControllers(
                     [
                           _PS_ADMIN_DIR_ . '/tabs/',
                           _PS_ADMIN_CONTROLLER_DIR_,
                           _PS_OVERRIDE_DIR_ . 'controllers/admin/',
                     ]
                  );
                  if (!isset($controllers[strtolower($this->controller)])) {
                     // If this is a parent tab, load the first child
                     if (Validate::isLoadedObject($tab)
                           && $tab->id_parent == 0
                           && ($tabs = Tab::getTabs(Context::getContext()->language->id, $tab->id))
                           && isset($tabs[0])
                     ) {
                           Tools::redirectAdmin(Context::getContext()->link->getAdminLink($tabs[0]['class_name']));
                     }
                     $this->controller = $this->controller_not_found;
                  }

                  $controller_class = $controllers[strtolower($this->controller)];
                  $params_hook_action_dispatcher = [
                     'controller_type' => self::FC_ADMIN,
                     'controller_class' => $controller_class,
                     'is_module' => 0,
                  ];

                  if (file_exists(_PS_ADMIN_DIR_ . '/tabs/' . $controller_class . '.php')) {
                     $retrocompatibility_admin_tab = _PS_ADMIN_DIR_ . '/tabs/' . $controller_class . '.php';
                  }
               }

               // @retrocompatibility with admin/tabs/ old system
               if ($retrocompatibility_admin_tab) {
                  include_once $retrocompatibility_admin_tab;
                  include_once _PS_ADMIN_DIR_ . '/functions.php';
                  runAdminTab($this->controller, !empty($_REQUEST['ajaxMode']));

                  return;
               }

         break;

         default:
            throw new PrestaShopException('Bad front controller chosen');
      }

      // Instantiate controller
      try {
         // Loading controller
         $controller = Controller::getController($controller_class);

         // Execute hook dispatcher
         if (isset($params_hook_action_dispatcher)) {
               Hook::exec('actionDispatcher', $params_hook_action_dispatcher);
         }

         // Running controller
         $controller->run();

         // Execute hook dispatcher after
         if (isset($params_hook_action_dispatcher)) {
               Hook::exec('actionDispatcherAfter', $params_hook_action_dispatcher);
         }
      } catch (PrestaShopException $e) {
         $e->displayMessage();
      }
   }


    /**
     * Retrieve the controller from url or request uri if routes are activated.
     *
     * @param int $id_shop
     *
     * @return string
     */
    public function getController($id_shop = null)
    {
         if (!Module::isEnabled('sjboverrides') || !($SJBOVERRIDES = Module::getInstanceByName('sjboverrides'))
         ||  !($override_enabled = (bool) Configuration::get($SJBOVERRIDES::PREFIX.'URLREWRITE'))
         ) {

            PrestaShopLogger::addLog( __method__ . ' - SJBOVERRIDES: URLREWRITE DISABLED', 1, null, 'Dispatcher', 1);
            parent::getController($id_shop);
            return;
            // PrestaShopLogger::addLog('SJBOVERRIDES: ENDISPOIS DE DISPATCH', 1, null, 'Dispatcher', 1);
         }


        if (defined('_PS_ADMIN_DIR_')) {
            $_GET['controllerUri'] = Tools::getValue('controller');
        }
        if ($this->controller) {
            $_GET['controller'] = $this->controller;

            return $this->controller;
        }

        if (isset(Context::getContext()->shop) && $id_shop === null) {
            $id_shop = (int) Context::getContext()->shop->id;
        }

        $controller = Tools::getValue('controller');

      //   PrestaShopLogger::addLog('SJB_ '. ' line' . __LINE__ .'  '. __method__ , 1);
      //   PrestaShopLogger::addLog('SJB_ '. ' line' . __LINE__ .'  '.'$controller:'. $controller, 1);

        if (isset($controller)
            && is_string($controller)
            && preg_match('/^([0-9a-z_-]+)\?(.*)=(.*)$/Ui', $controller, $m)
        ) {
            $controller = $m[1];
            if (isset($_GET['controller'])) {
                $_GET[$m[2]] = $m[3];
            } elseif (isset($_POST['controller'])) {
                $_POST[$m[2]] = $m[3];
            }
        }

        if (!Validate::isControllerName($controller)) {
            $controller = false;
        }

        // Use routes ? (for url rewriting)
        if ($this->use_routes && !$controller && !defined('_PS_ADMIN_DIR_')) {

            PrestaShopLogger::addLog('SJB_ '. 'non hai controler definido. Vexamos...', 1);
            // PrestaShopLogger::addLog('SJB_ '. ' line' . __LINE__ .'  '.'Determinar controller??', 1);

            if (!$this->request_uri) {
                return strtolower($this->controller_not_found);
                PrestaShopLogger::addLog('SJB_ '. ' nada, devolves controller not found', 1);
            }

            $controller = $this->controller_not_found;
            $test_request_uri = preg_replace('/(=http:\/\/)/', '=', $this->request_uri);

            // If the request_uri matches a static file, unless it's in the upload folder,
            // then there is no need to check the routes, we keep
            // "controller_not_found" (a static file should not go through the dispatcher)
            if (
                !preg_match('/\.(gif|jpe?g|png|css|js|ico)$/i', parse_url($test_request_uri, PHP_URL_PATH))
                || preg_match('/^\/upload/', parse_url($test_request_uri, PHP_URL_PATH))) {
                // Add empty route as last route to prevent this greedy regexp to match request uri before right time
                if ($this->empty_route) {
                    $this->addRoute(
                        $this->empty_route['routeID'],
                        $this->empty_route['rule'],
                        $this->empty_route['controller'],
                        Context::getContext()->language->id,
                        [],
                        [],
                        $id_shop
                    );
                }

                list($uri) = explode('?', $this->request_uri);
                PrestaShopLogger::addLog('SJB_ explotamos request uri', 1);
                PrestaShopLogger::addLog(var_export($uri, true), 1);

                if (isset($this->routes[$id_shop][Context::getContext()->language->id])) {
                      PrestaShopLogger::addLog('Loop para buscar route', 2);
                     /*
                        'category_rule' => [
                           'controller' => 'category',
                           'rule' => '{id}-{rewrite}',
                           'keywords' => [
                              'id' => ['regexp' => '[0-9]+', 'param' => 'id_category'],
                              'rewrite' => ['regexp' => self::REWRITE_PATTERN],
                              'meta_keywords' => ['regexp' => '[_a-zA-Z0-9-\pL]*'],
                              'meta_title' => ['regexp' => '[_a-zA-Z0-9-\pL]*'],
                           ],
                        ],
                     */
                    foreach ($this->routes[$id_shop][Context::getContext()->language->id] as $K=>$route) {

                        PrestaShopLogger::addLog(  'Clave route ($K): ' .$K . '$regexp: '. $route['regexp'], 1); // Regexp REWRITE_PATTERN

                        if (preg_match($route['regexp'], $uri, $m)) {

                           PrestaShopLogger::addLog(  'OK, URI pasa a validacion inicial de REWRITE_PATTERN con  $K ' .$K, 1);
                           PrestaShopLogger::addLog(  'Matches ($m) '. var_export($m, true), 1);

                            PrestaShopLogger::addLog(  var_export($route, true), 1);

                            // Route found ! Now fill $_GET with parameters of uri
                            foreach ($m as $k => $v) {
                              PrestaShopLogger::addLog(  sprintf('%1$s  ==> %2$s', $k, $v), 1);
                                if (!is_numeric($k)) {
                                    $_GET[$k] = $v;
                                }
                            }

                            $controller = $route['controller'] ? $route['controller'] : $_GET['controller'];
                            if (!empty($route['params'])) {
                                foreach ($route['params'] as $k => $v) {
                                    $_GET[$k] = $v;
                                }
                            }

                            // A patch for module friendly urls
                            if (preg_match('#module-([a-z0-9_-]+)-([a-z0-9_]+)$#i', $controller, $m)) {
                                $_GET['module'] = $m[1];
                                $_GET['fc'] = 'module';
                                $controller = $m[2];
                            }

                            if (isset($_GET['fc']) && $_GET['fc'] == 'module') {
                                $this->front_controller = self::FC_MODULE;
                            }

                            break; // Sale de loop
                        }else PrestaShopLogger::addLog(  'uri NON PASA REGEXP --> SEGUINTE iteracion de LOOP ', 1);
                    }
                    PrestaShopLogger::addLog('Fin Loop para buscar route', 2);
                }
            }

            if ($controller == 'index' || preg_match('/^\/index.php(?:\?.*)?$/', $this->request_uri)) {
                $controller = $this->useDefaultController();
            }
        }

        $this->controller = str_replace('-', '', $controller);
        $_GET['controller'] = $this->controller;

        return $this->controller;
    }
}