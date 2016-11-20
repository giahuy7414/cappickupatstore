<?php

//prevent the module folder loading directly from browser
if (!defined('_PS_VERSION_'))
exit;
 
class CapPickupAtStore extends Module
{

    public function __construct()
	{
	    $this->name = 'cappickupatstore';
	    $this->tab = 'others';
	    $this->version = '1.0';
	    $this->author = 'Huy Truong Gia';
	    $this->need_instance = 0;
	    $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.7');
	 
	    parent::__construct();
	 
	    $this->displayName = $this->l('Pickup at Store Module');
	    $this->description = $this->l('Option to pickup at store');
	 
	    $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
	 
	    if (!Configuration::get('cappickupatstore')) {      
	       $this->warning = $this->l('No name provided');
		}	
	}


    public function install()
    { 

		if (parent::install() == false){
			return false;
		} else {
		return true;
		};
    }


    public function uninstall()
    {

		if (!parent::uninstall() || !Configuration::deleteByName('res_emp_group') || !Configuration::deleteByName('res_shop') ){
			return false;
		} else {
            return true;

		} 
    }


	public function displayForm()
	{	
		$helper = new HelperForm();
		//get current selected employee group and shop in the system
		$employee_group_configurations = explode(';', Configuration::get('res_emp_group'));
		$shop_list_configurations = explode(';', Configuration::get('res_shop'));

		//get available employee group and assign to checkbox options and check for its selected status in system, if yes then the option(s) need to be checked when render
		$employee_group_list = $this->getEmployeeGroup();
		$employee_group_options = array();
		foreach ($employee_group_list as $value) {
			$employee_group_options[] = array(
				'idgroup' => (int)$value['id_profile'],
				'namegroup' => $value['name'],
				);
			foreach ($employee_group_configurations as $employee_group_configuration) {
				if ((int)$value['id_profile'] == (int)$employee_group_configuration){
				    $helper->fields_value['idgroup_'.(int)$value['id_profile']] = true;
				    break 1;	
				}
			}
		};

		//get available shop and assign to checkbox options and check for its selected status in system, if yes then the option(s) need to be checked when render
		$shop_list = $this->getShop();
		$shop_list_options = array();
		foreach ($shop_list as $value) {
			$shop_list_options[] = array(
				'idshop' => (int)$value['id_shop'],
				'nameshop' => $value['name'],
				);
			foreach ($shop_list_configurations as $shop_list_configuration) {
				if ((int)$value['id_shop'] == (int)$shop_list_configuration){
				    $helper->fields_value['idshop_'.(int)$value['id_shop']] = true;
				    break 1;	
				}
			}
		};


	    // Get default language
	    $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
	     
	    // Init Fields form array
	    $fields_form[0]['form'] = array(
	        'legend' => array(
	            'title' => $this->l('Settings'),
	        ),
	        'input' => array(
	            array(
	                'type' => 'checkbox',
	                'style' => 'style="text-align:left;"',
	                'label' => $this->l('Restricted Employee Group'),
	                'name' => 'idgroup',
	                'required' => true,
	                'values' => array(
	                	'query' => $employee_group_options,      // $options contains the data itself.
    					'id' => 'idgroup',                           // The value of the 'id' key must be the same as the key for 'value' attribute of 
    					'name' => 'namegroup',
    				)
	            ),
	            array(
	                'type' => 'checkbox',
	                'label' => $this->l('Restricted store'),
	                'name' => 'idshop',
	                'required' => true,
	                'values' => array(
	                	'query' => $shop_list_options,      // $options contains the data itself.
    					'id' => 'idshop',                           // The value of the 'id' key must be the same as the key for 'value' attribute of 
    					'name' => 'nameshop',
    				)
	            )
	        ),
	        'submit' => array(
	            'title' => $this->l('Save'),
	            'class' => 'btn btn-default pull-right'
	        )
	    );
	    

	    
	    // Module, token and currentIndex
	    $helper->module = $this;
	    $helper->name_controller = $this->name;
	    $helper->token = Tools::getAdminTokenLite('AdminModules');
	    $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
	     
	    // Language
	    $helper->default_form_language = $default_lang;
	    $helper->allow_employee_form_lang = $default_lang;
	     
	    // Title and toolbar
	    $helper->title = $this->displayName;
	    $helper->show_toolbar = true;        // false -> remove toolbar
	    $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
	    $helper->submit_action = 'submit'.$this->name;
	    $helper->toolbar_btn = array(
	        'save' =>
	        array(
	            'desc' => $this->l('Save'),
	            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
	            '&token='.Tools::getAdminTokenLite('AdminModules'),
	        ),
	        'back' => array(
	            'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
	            'desc' => $this->l('Back to list')
	        )
	    );
	     
	    return $helper->generateForm($fields_form);
	}


	public function getContent()
	{
	    $output = null;

	    if (Tools::isSubmit('submit'.$this->name))
	    {
	        $this->saveConfiguration();
	        $output .= $this->displayConfirmation($this->l('Settings updated'));
	    }

	    return $output.$this->displayForm();
	}

	public function saveConfiguration()
	{
		$employee_group_list = $this->getEmployeeGroup();
	    $shop_list = $this->getShop();
	    $employee_group_list_saved = array();
	    $shop_list_saved = array();

	    foreach ($employee_group_list as $options) {
	    	if (Tools::getValue('idgroup_'.$options['id_profile'])) {
	    	    $employee_group_list_saved[] = $options['id_profile'];
	    	}
	    }

	    foreach ($shop_list as $options) {
	    	if (Tools::getValue('idshop_'.$options['id_shop'])) {
	    	    $shop_list_saved[] = $options['id_shop'];
	    	}
	    }
	    Configuration::updateValue('res_emp_group', implode(',', $employee_group_list_saved));
	    Configuration::updateValue('res_shop', implode(',', $shop_list_saved));
	}



	protected function getEmployeeGroup()
	{
		$id_lang = $this->context->language->id;
		$sql = 'select id_profile, name from '._DB_PREFIX_.'profile_lang where id_lang = '.$id_lang.' order by id_profile';
		$employee_group_list = DB::getInstance()->ExecuteS($sql);
		return $employee_group_list;
	}

	protected function getShop()
	{
		$sql = 'select id_shop, name from '._DB_PREFIX_.'shop order by id_shop' ;
		$shop_list = DB::getInstance()->ExecuteS($sql);
		return $shop_list;
	}






    /*//function to call the file contain the path to class that need to be loaded when running the moduel
    public function hookModuleRoutes() 
    {

		require_once  (_PS_MODULE_DIR_.'supplyordervoucherpdf'.DIRECTORY_SEPARATOR.'autoload'.DIRECTORY_SEPARATOR.'autoload.php');
		
    }*/


}
