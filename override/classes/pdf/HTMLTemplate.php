<?php 
class HTMLTemplate extends HTMLTemplateCore
{
    protected function getTemplate($template_name)
    {
        if (Module::isEnabled('sjboverrides')) {
            $overriddenTemplate = _PS_MODULE_DIR_ . 'sjboverrides/views/templates/hook/' . $template_name . '.tpl';
            if (file_exists($overriddenTemplate)) {
                return $overriddenTemplate;
            }
        }
        return parent::getTemplate($template_name);
    }

    public function getContent()
    {
    }

    public function getFilename()
    {
    }

    public function getBulkFilename()
    {
    }
}