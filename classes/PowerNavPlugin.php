<?php

abstract class PowerNavPlugin
{
    private $module;
    protected $context;

    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    public function setModule($module)
    {
        $this->module = $module;

        return $this;
    }

    public function getLanguageId()
    {
        return $this->context->language->id;
    }

    public function l($str)
    {
        return $this->module->l($str);
    }

    public function score($candidate, $query)
    {
        return $this->module->score($candidate, $query);
    }
}
