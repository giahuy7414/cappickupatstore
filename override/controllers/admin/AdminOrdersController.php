<?php

class AdminOrdersController extends AdminOrdersControllerCore
{

	public function __construct()
	{
		parent::__construct();
		$this->_join .= '
						LEFT JOIN `'._DB_PREFIX_.'order_carrier` bb ON a.id_order = bb.id_order';
	}


}

