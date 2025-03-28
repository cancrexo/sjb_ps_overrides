<?php

// Clase

 class AdminSjboverridesConfigController extends ModuleAdminController{


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

        //$this->context->controller->addCSS( $this->module->path.'views/sjbcarriers-form/assets/index.css', 'all');
        //$this->context->controller->addJS( $this->module->path . 'views/sjbcarriers-form/assets/index.js');

        // $CONTROLLERURL = $this->context->link->getAdminLink('AdminSjbcarriersHttp'); // :-/
        // $token = Tools::getAdminTokenLite('AdminSjbcarriersHttp');
        // $tojs = array(
        //         // variabels NOVAS para tpv webpack
        //     'sjbcarriers_controller'   => $CONTROLLERURL,
        //     'sjbcarriers_token'	      => $token,
        // );

        \Media::addJsDef($tojs);


        /*Media::addJsDef(array(
            $this->name . '_translate' => array(
                'sampletext' => sprintf(
                    '<em><a target="_blank" href="%1$s">%2$s</a></em>',
                    'https://cancrexo.com',
                    $this->l('(Sample custom translatable javascript text)')
                ),
                'errors_on_form' => sprintf('%s', $this->l('Please check the form fields for errors.'))
            ))
        );*/
    }


    // 1o este -->define as views
    public function initProcess(){

        parent::initProcess(); //-->define valor de display!!
        // agora defino as miñas views:

        if ( !$this->ajax)
            $this->display = 'edit';
    }


    /**
    * Formulario que casi lo vamos a quitar
    */
    public function renderForm(){


        $this->html = $this->getMyPanel();

        $this->html .= $this->getInstructions();
            return $this->html;
    }



    public function getMyPanel(){
        return
        '<div class="panel" id="sjbcarriers-panel">
            <div class="panel-heading"><i class="icon-info"></i>Carriers Config</div>
            <!-- content goes here -->
            <div class="panel-body">
            <p>Seleciona el carrier  a configurar</p>
            </div>
            <div class="panel-footer"></div>
        </div>
        <div class="panel" id="sjbconfig-panel">
            <div class="panel-heading"><i class="icon-info"></i>Plugin general config</div>
            <!-- content goes here -->
            <div class="panel-body"></div>
            <div class="panel-footer"></div>
        </div>';
    }
    public function getInstructions(){

        return  $this->context->smarty->fetch(
            $this->module->getLocalPath() . 'views/templates/admin/instructions.tpl');
    }



}
