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

    private function charCount($str)
    {
        $m = array();
        $n = preg_match_all('/\p{L}|\d/u', mb_strtolower($str), $m);

        $chars = [];

        foreach ($m[0] as $char)
        {
            $chars[$char] = (isset($chars[$char]) ? $chars[$char] : 0) + 1;
        }

        return array(
            'count' => $n,
            'chars' => $chars
        );
    }

    private function orderedPairs($str)
    {
        $m = array();
        preg_match_all('/\p{L}|\d/u', mb_strtolower($str), $m);
        $chars = $m[0];
        $pairs = array();
        for ($i = 0; $i < count($chars); ++$i)
        {
            for ($j = $i + 1; $j < count($chars); ++$j)
            {
                $pair = $chars[$i].$chars[$j];
                $invdist = 1.0 / ($j - $i);

                if (!array_key_exists($pair, $pairs) || $pairs[$pair] < $invdist)
                {
                    $pairs[$pair] = $invdist;
                }
            }
        }

        return $pairs;
    }

    private function getWords($str)
    {
        $m = array();
        preg_match_all('/(\p{L}|\d)+/u', mb_strtolower($str), $m);
        $words = array();

        foreach ($m[0] as $wordStrings)
        {
            preg_match_all('(\p{L}|\d/u', $wordStrings, $m);
            $words[] = $m[0];
        }

        return $words;
    }

    public function score($candidate, $query)
    {
        $c = $this->charCount($candidate);
        $q = $this->charCount($query);

        /**if (preg_match('/rders/', $candidate)) {
            ddd([$candidate, $query, $c, $q]);
        }//*/

        $common = 0;
        foreach ($c['chars'] as $char => $count)
        {
            if (isset($q['chars'][$char]))
            {
                $common += min($count, $q['chars'][$char]);
            }
        }

        $orderedPairs = $this->orderedPairs($candidate);

        $m = array();
        preg_match_all('/\p{L}|\d/u', mb_strtolower($query), $m);
        $queryChars = $m[0];

        $okPairs = 0;
        $totalPairs = 0;
        for ($i = 0; $i < count($queryChars) - 1; ++$i) {
            $pair = $queryChars[$i] . $queryChars[$i+1];
            ++$totalPairs;
            if (array_key_exists($pair, $orderedPairs))
            {
                $okPairs += $orderedPairs[$pair];
            }
        }

        $candidateWords = $this->getWords($candidate);
        $queryWords = $this->getWords($query);

        $okWords = 0;
        $totalWords = count($queryWords);
        foreach ($candidateWords as $cw)
        {
            foreach ($queryWords as $qw)
            {
                if ($qw[0] === $cw[0] && end($qw) === end($cw))
                {
                    ++$okWords;
                }
            }
        }

        return ($common / min($c['count'], $q['count']) + $okPairs / $totalPairs + $okWords / $totalWords) / 3;
    }

    public function lookupActions($query)
    {
        $actions = array();

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

                    $action['lexicalScore'] = $this->score($action['actionString'], $query);
                    $action['score'] = $action['lexicalScore'];

                    $actions[] = $action;
                }
            }
        }

        usort($actions, create_function('$a, $b', 'return $b["score"] < $a["score"] ? -1 : 1;'));

        return array_slice($actions, 0, 20);
    }
}
