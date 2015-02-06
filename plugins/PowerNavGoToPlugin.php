<?php

class PowerNavGoToPlugin extends PowerNavPlugin
{
    public function getActions($query)
    {
        $sql = 'SELECT t.class_name, l.name
                FROM '._DB_PREFIX_.'tab t
                INNER JOIN '._DB_PREFIX_.'tab_lang l
                ON l.id_tab = t.id_tab AND l.id_lang = '.(int)$this->getLanguageId();

        $tabs = Db::getInstance()->executeS($sql);

        $results = array();

        foreach ($tabs as $tab)
        {
            $results[] = array(
                'actionString' => sprintf($this->l('Open `%1$s` menu (`%2$s`)'), $tab['name'], $tab['class_name']),
                'actionData' => array(
                    'url' => $this->context->link->getAdminLink($tab['class_name']),
                    'onActivate' => 'updateLocation'
                )
            );
        }

        $sql = 'SELECT m.name, m.active, m.version FROM  '._DB_PREFIX_.'module m';
        $modules = Db::getInstance()->executeS($sql);

        foreach ($modules as $module)
        {
            $results[] = array(
                'actionString' => sprintf($this->l('Configure module `%1$s`'), $module['name']),
                'actionData' => array(
                    'url' => $this->context->link->getAdminLink('AdminModules') . '&configure=' . $module['name'],
                    'onActivate' => 'updateLocation'
                )
            );
        }

        return $results;
    }
}
