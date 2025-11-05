<?php
/*
	Utiles PS
	By cancrexo
    2 composer
*/
namespace Sjboverrides;

class SJB_PSTools{

    const version = '2.0';

    private static $instancia;

    static

    $_recursive_count = 0,
    $_recursive_array = array(); // para chamadas recursivas

    public  function getInstance(){
        if (!isset(self::$instancia)) {
            self::$instancia = new self;
        }
        return self::$instancia;
    }

    public function __construct(){
        $this->context = \Context::getContext();
        $this->context->smarty->assign('sjboverrides', ['version' => static::version]);
    }

    public  function getShopDomain(){
        return \Configuration::get('PS_SHOP_DOMAIN');
    }


    /*
    Dame as direccións das tiendas ¿pero que pasa co puto contexto? Joderrrrr!
     */
    public  function getStores ($id_lang = 1) {
        $sql = "
			SELECT
			S.id_store, name
			FROM  `" . _DB_PREFIX_ . "store` S JOIN `" . _DB_PREFIX_ . "store_lang` SL ON SL.id_store=S.id_store  AND SL.id_lang='$id_lang'
			AND S.active=1 ORDER BY name ASC";
        return \Db::getInstance()->ExecuteS($sql); // Array multidim.

    }


    public  function getWarehouses(){
        $sql = "
            SELECT
            id_warehouse, reference, name
            FROM  `" . _DB_PREFIX_ . "warehouse` W
            WHERE 1 ORDER BY name ASC";
        return \Db::getInstance()->ExecuteS($sql); // Array multidim.

    }


    public function getEstadosPedidos(){
        $order_state = \OrderStateCore::getOrderStates($this->context->language->id);

        $list_order_state = [];

        foreach ($order_state as $OS) {

            $list_order_state[] = ['id_order_state' => $OS['id_order_state'], 'label' => $OS['name']];
        }
        return $list_order_state;
    }


    // DEvolve array para optgroup de features
    public  function  getFeatures2optgroup(){
        $features = \Feature::getFeatures($this->context->language->id);
        $list_features = [];
        foreach($features as $feature){
            $list_features[] = array(
                'label' => $feature['name'],
                'options' => array()
            );
            // Values
            $values = \FeatureValue::getFeatureValuesWithLang( $this->context->language->id, $feature['id_feature'],);
            foreach($values as $value){
                $list_features[count($list_features)-1]['options'][] = array(
                'id' => $value['id_feature_value'],
                'name' => $value['value']
                );
            }
        }

        return $list_features;

    }


    // Devolve array cos tamaños de imaxxe de sistema
    public  function getImagetypes(){
        $list_imagesizes = array();
        $imagelist = \ImageType::getImagesTypes(null, true); // $type = null, $order_by_size = false
        foreach ($imagelist as $IL) {
            $list_imagesizes[] = array(
                'id_image_type' => (int) $IL['id_image_type'],
                'name' => sprintf('%1$s (<em>%2$s x %3$s</em>)', $IL['name'], $IL['width'], $IL['height']),
                'width' => (int) $IL['width'],
                'height' => (int) $IL['height'],
            );
        }
        return $list_imagesizes;
    }


   /**
    * Devolve as categorias de productos nun array ordenado como estan as categorias
    * Recibe id_lang e obxecto shop
    * Devolve array
    *
    * @param [type] $id_lang
    * @param [type] $shop
    * @param integer $indentado
    * @return void
    */
	public  function getSortedCategories($id_lang, $shop, $indentado = 0){

		//1º precisas root category:
		$category_obj = \Category::getRootCategory($id_lang, $shop); //$this->context->language->id,  $this->context->shop);

		//2º Debes idicar para que user groups vas amosar as categorías. Obviamente quero todas:
 		$user_groups =  NULL; //array(Configuration::get('PS_UNIDENTIFIED_GROUP'));

		self::$_recursive_count = 0;
		self::$_recursive_array = [];
		$primary_array = \Category::getNestedCategories($category_obj->id, $id_lang, false, $user_groups);

 		$devnull = self::recursiveArrayCategorias( $primary_array , $indentado);

 		return self::$_recursive_array;
    }

    /**
     * Pos ezo!!
     *
     * @param [type] $categories
     * @param integer $indentado
     * @return void
     */
    public static  function recursiveArrayCategorias($categories, $indentado = 0){
      $html = '';

      self::$_recursive_count++; // Conservase entre chamadas polo que se usa para o indentando
      /*
         id_category => string
         id_parent => string
         id_shop_default => string
         level_depth => string
         nleft => string
         nright => string
         active => string
         date_add => string
         date_upd => string
         position => string
         is_root_category => string
         id_shop => string
         id_lang => string
         name => string
         description => string
         link_rewrite => string
         meta_title => string
         meta_keywords => string
         meta_description => string
         children =>   array
      */
      foreach ($categories as $key => $category) {
         // var_dump($category);
         if ($category['level_depth'] > 1) {
               $cat = new \Category($category['id_category']);
         }

         /* Whenever a category is not active we shouldnt display it to customer */
         if ((bool)$category['active'] === false) {
               continue;
         }

         $id = $category['id_category'];
         $texto = ($indentado ? str_repeat('&nbsp;&nbsp;',(int) self::$_recursive_count - 1) : '') .$category['name'];
         // var_export($category, true);

         self::$_recursive_array[]= array(
            'id_category'=> (int)$id ,
            'id_parent'=>$category['id_parent'],
            'category'=>$texto,
            'description'=>$category['description'],
            'depth'=> self::$_recursive_count
         );

         $html .= self::$_recursive_count;


         if (isset($category['children']) && !empty($category['children'])) {
               $html .= self::recursiveArrayCategorias($category['children'], $indentado);
         }
      }
      self::$_recursive_count--;
      return $html;
   }



   /**
    * Método para leer os datos dos parametros de configuracion e devolver un array coas claves=>>valores
    * O nome da clave de configuracion será: PREFIXO_MODULO_array_prefix_config
    * Por exempo:
    *   - SJBOVERRIDES_general_config (==> MODULE::PREFIX . $array_prefix .'config')
    *   - SJBOVERRIDES_ticket_config
    *   etc.
    *
    * Recibe:
    *  - prefixo do modulo
    *  - prefixo da variable (pode haber varios grupos para 1 modulo)
    *  - add_prefix=true --> añade o prefixo ás claves (caso renderForm evitar colisión en nomes) xa que se 
    *    usan para o nome dos campos!!

   
    *
    */
    public function getStoredArray( $module_prefix = '_mimodule_', $array_prefix = 'ahilollevas_', $add_prefix = true ){

        // \PrestaShopLogger::addLog(__method__);
        $_config = []; // Configuracion a devolver
        if(strlen($module_prefix) && substr($module_prefix, -1) != '_')$module_prefix .= '_'; // añade _
        if(strlen($array_prefix) && substr($array_prefix, -1) != '_')$array_prefix .= '_'; // añade _

        /*
            Definimos nome  da variable de esta clase que almacena as claves  configuracion
            para este prefixo
            P.e, para ticket_ a clase deberá ter unha propiedade que se chame ticket_config_keys
        */
        $config2load = $module_prefix.$array_prefix.'config'; // p.e  cargará da tabla configuracion APOS_ticket_config
        // \PrestaShopLogger::addLog('clave de configuracion a cargar: ' . $config2load);

        $stored_config = (array)\Tools::jsonDecode(\Configuration::get( $config2load), true); // true pq queres array asociativo!!
        // var_dump($config2load);
        if(!$add_prefix)
            $array_prefix = '';

        foreach($stored_config as $key=>$valor){
            $_config[$array_prefix. $key] = $valor;
        }

        return $_config; // array;
    }

    /**
     * Método para validar e grabar os  datos dos parametros de configuracion
     * Recibe:
     * - module_prefix : prefixo do modulo (p.e.  SJBOVERRIDE)
     * - array_prefix : prefixo do array: p.e tickets, general, etc co que o nome de clave na bbdd seria p.e,
     *
     *    SJBOVERRIDES_general (antes de grabar engadelle _config ao final!!)
     *
     *- config_keys : array con as claves a validar e almacenar e ques emeten en {CONFIG_PREFIX}miclave
     *- $este e referencia ao controller/modulo que chama a isot
     *      LEMBRA QUE VES POR POST MONA!!!!!
     *
     */
    public  function setStoredArray($data, $module_prefix='', $array_prefix = null, $config_keys= null, &$este = null){

        // PrestaShopLogger::addLog(__method__);
        
        $_config = []; // Configuracion a devolver??

        if(strlen($module_prefix) && substr($module_prefix, -1) != '_')$module_prefix .= '_';

        if(!isset($array_prefix))return $_config;

        if(substr($array_prefix, -1) != '_')$array_prefix .= '_';

        /*
            Definimos nome  da variable de esta clase que almacena as claves  configuracion
            para este prefixo
        */
        $config2Store = $module_prefix . $array_prefix.'config';

        foreach($config_keys as $single_key){

            $tmp = explode('_', $single_key);
            $type = $tmp[0]; // Saco tipo de campo, p.e., (int)

            $KEY = $array_prefix .$single_key; //nome da variable post/get a validar. i.e., tickets_int_top_offset

            // Segun o tipo validamos dun xeito ou outro:
            switch ($type) {
                case 'lang': // its a multilang field- TO-DO
                    if (($res = $this->validatelang($KEY)) !== false) {
                        $_config[$single_key] = $res;
                    }
                    break;

                case 'chk':  //TO-DO
                    if (($res = $this->validateArray($KEY)) !== false) {
                        $_config[$single_key] = $res;
                    }
                    break;

                case 'bool': // To-DO
                        $_config[$single_key] = (bool)\Tools::getValue($KEY);
                break;

                default:
                    $value = \Tools::getValue( $KEY );
                    if (($res = $this->validate($type, $value)) !== false) { // Furro!!!!!
                        $_config[$single_key] = $res;
                        //$este->confirmations[] = 'OK (' . $value .') in  '. $KEY . ' as  '. $type .' pois res dame ' . $res .'<br>';
                    } else {
                        //$este->_errors[] = 'Error validating ' . $KEY;
                        if(!is_null($este)){  // instancia de controller
                            @$este->_errors[] = 'Error validating (' . $value .') in  '. $KEY . ' as  '. $type ;
                        }
                    }
                break;
            }
        }

        //if($config2Store=='general')die();
        // This should be mapped to updateValue with  idShopGroup/idshop on multistore (TODO)
        \Configuration::updateValue($config2Store, \Tools::jsonEncode($_config, JSON_HEX_QUOT | JSON_HEX_TAG), true);
        return true;
    }

    // Single validation
    public  function validate($type, $value){
        $res = false;
        switch ($type) {
            case 'int':
                if (\Validate::isInt($value)) {
                    $res = (int) $value;
                }
                break;

            case 'float':
                if (\Validate::isFloat($value)) {
                    $res = (float) $value;
                }
                break;

            case 'crd': // Coordenada gmaps
                if (\Validate::isCoordinate($value)) {
                    $res = \Tools::safeOutput($value);
                }
                break;

            case 'txt':
            case 'str':
                if (\Validate::isString($value)) {
                    $res = strip_tags(html_entity_decode($value));
                    $res = \Tools::safeOutput($value);
                }
                break;

            case 'json':
                if (!\Tools::isEmpty($value)) {
                    $devnull = @\Tools::jsonDecode($value);
                    if (json_last_error() == JSON_ERROR_NONE) {
                        $res = $value;
                    }
                } else {
                    $res = '';
                }
                break;

            case 'html':
                if (\Validate::isCleanHtml($value)) {
                    $res = $value;
                }
                break;
        }
        return $res;
    }

    /**
        * Validate multilang fields
        * $keyarray :@string clave do array config, p.e. lang_html_marcador
        */
    public  function validatelang($keyarray){
        $tmp = explode("_", $keyarray);
        $subtype = $tmp[1]; // p.e. html, int, etc
        $resposta = array(); // return recursive value!
        foreach (\Language::getLanguages(false) as $lang) {
            $id_lang = (int) $lang['id_lang'];
            $valor_lang = \Tools::getValue($keyarray . '_' . $id_lang); //lang_html_marcador_1
            if (($res = $this->validate($subtype, $valor_lang)) !== false) {
                $resposta[$id_lang] = $res;
            } else {
                $resposta[$id_lang] = false;
            }
        }
        return $resposta;
    }

    /**
     * Validate multiple fields as int (checkboxes, radio buttons, multiple selects...)
     * $keyarray :@string clave do array config, p.e. chk_availablemaptypes
     */
    public function validateArray($keyarray){
        // get all keys that begins with $keyarray
        // $sendavlues = Tools::getAllValues(); // get + post if needed!
        $resposta = array();
        $keylength = \Tools::strlen($keyarray);
        foreach ($_POST as $k => $null) {
            if (!strncmp($k, $keyarray, $keylength)) {
                // chk_availablemaptypes_1
                // chk_availablemaptypes_3 //=>value = 'on' sucks!
                $tmp = explode('_', $k);
                if ($tmp) {
                    $resposta[] = (int) array_pop($tmp);
                }
            }
        }
        return $resposta;
    }
}