<?php

if (!defined('_PS_VERSION_'))
{
    exit;
}

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'PowerNavPlugin.php';

class PowerNav extends Module
{
    public $name = 'powernav';
    public $tab = 'administration';
    public $version = '0.0.1';
    public $author = 'fmdj';
    public $bootstrap = true;

    public function __construct()
    {
        $this->displayName = $this->l('PowerNav');
        $this->description = $this->l('A collection of Back-Office shortcuts à la Sublime CTRL+P for PrestaShop developers and power users.');

        parent::__construct();
    }

    public function install()
    {
        return  parent::install() &&
                $this->registerHook('actionAdminControllerSetMedia') &&
                $this->registerHook('displayBackOfficeTop') &&
                $this->registerHook('displayBackOfficeHeader') &&
                $this->installTab();
        ;
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->uninstallTab();
    }

    public function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminPowerNav';
        $tab->name = array();

        foreach (Language::getLanguages(true) as $lang)
        $tab->name[$lang['id_lang']] = 'PowerNav';

        $tab->id_parent = -1;
        $tab->module = $this->name;
        return $tab->add();
    }

    public function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminPowerNav');

        if ($id_tab)
        {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }

        return true;
    }

    public function hookActionAdminControllerSetMedia()
    {
        if (!$this->active)
        {
            return;
        }

        if (method_exists($this->context->controller, 'addJquery'))
        {
            $this->context->controller->addJquery();
        }

        $this->context->controller->addJS($this->_path.'js/powernav.js');
        $this->context->controller->addCSS($this->_path.'css/powernav.css');
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (!$this->active)
        {
            return;
        }

        $this->smarty->assign('powerNavControllerURL', $this->context->link->getAdminLink('AdminPowerNav'));

        return $this->display(__FILE__, 'views/template/hook/backoffice_header.tpl');
    }

    public function hookDisplayBackOfficeTop()
    {
        if (!$this->active)
        {
            return;
        }

        return $this->display(__FILE__, 'views/template/hook/backoffice_top.tpl');
    }

    /**
     * Real code starts here.
     */

    public function lookupActions($query)
    {
        $actions = array();

        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'PowerNavScorer.php';

        $scorer = new PowerNavScorer($query);

        foreach (scandir(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'plugins') as $entry)
        {
            $m = array();
            if (preg_match('/^(PowerNav(\w+?)Plugin)\.php$/', $entry, $m))
            {
                $classPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $entry;
                include_once $classPath;
                $className = $m[1];
                $pluginName = $m[2];

                $plugin = new $className();

                $plugin->setContext($this->context)->setModule($this);

                foreach ($plugin->getActions($query) as $action)
                {
                    if (!isset($action['actionString']))
                    {
                        continue;
                    }

                    $action['pluginName'] = $pluginName;
                    if (!isset($action['localScore']))
                    {
                        $action['localScore'] = 0;
                    }

                    $action['lexicalScore'] = $scorer->score($action['actionString']);
                    $action['score'] = $action['lexicalScore'];

                    $actions[] = $action;
                }
            }
        }

        usort($actions, create_function('$a, $b', 'return $b["score"] < $a["score"] ? -1 : 1;'));

        return array_slice($actions, 0, 12);
    }
}
