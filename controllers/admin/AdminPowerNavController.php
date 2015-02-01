<?php

class AdminPowerNavController extends ModuleAdminController
{
	public function ajaxProcessPowerNavQuery()
	{
		$query = Tools::getValue('query');
		$response = $this->module->lookupActions($query);
		ob_clean();
		die(Tools::jsonEncode($response));
	}
}
