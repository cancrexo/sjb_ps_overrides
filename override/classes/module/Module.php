<?php 
class Module extends ModuleCore
{
 /**
     * @param string $template
     * @param string|null $cache_id
     * @param string|null $compile_id
     *
     * @return Smarty_Internal_Template
     */
    protected function getCurrentSubTemplate($template, $cache_id = null, $compile_id = null)
    {
        if ($compile_id === null) {
            $compile_id = $this->getDefaultCompileId();
        }

        if (!isset($this->current_subtemplate[$template . '_' . $cache_id . '_' . $compile_id])) {
            if (false === strpos($template, 'module:') &&
                !file_exists(_PS_ROOT_DIR_ . '/' . $template) &&
                !file_exists(_PS_ROOT_DIR_ . $template)
                //!file_exists($template)
            ) {
                $template = $this->getTemplatePath($template);
            }

            $this->current_subtemplate[$template . '_' . $cache_id . '_' . $compile_id] = $this->context->smarty->createTemplate(
                $template,
                $cache_id,
                $compile_id,
                $this->smarty
            );
        }

        return $this->current_subtemplate[$template . '_' . $cache_id . '_' . $compile_id];
    }
}