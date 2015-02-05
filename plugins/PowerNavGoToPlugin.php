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

        foreach ($tabs as $tab) {
            $results[] = array(
                'actionString' => sprintf($this->l('Open `%1$s` menu (`%2$s`)'), $tab['name'], $tab['class_name']),
                'actionData' => array(
                    'className' => $tab['class_name'],
                    'url' => $this->context->link->getAdminLink($tab['class_name']),
                    'onActivate' => 'updateLocation'
                )
            );
        }

        return $results;
    }
}
