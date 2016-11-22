<?php
class Shop extends ShopCore
{


    /*
    * module: cappickupatstore
    * date: 2016-11-19 11:21:35
    * version: 1.0
    */
    // Modify addSqlRestriction function, added to the restriction the id of the store that user want to included (select in module)
    public static function addSqlRestriction($share = false, $alias = null) //XXX
    {   
    	//Get enable context of the function
        $Enable = Shop::getEnable();
        if ($Enable) {
        	//Get id of added shop
            $restriction_id_shop_added = Configuration::get('res_shop');
            //Get id of the carrier(s) that assigned to the selected shop in multistore drop down
            $assigned_carrier_id = Shop::getCarrierAssigned($share);
            //Only inject the id of added shop in context of viewing all customer oders
            if (debug_backtrace(false)[1]['function'] == 'getList' && $assigned_carrier_id && Context::getContext()->controller instanceof AdminOrdersController && !Tools::isSubmit('id_order') && !Tools::isSubmit('addorder')) {
                if ($alias) {
                    $alias .= '.';
                }
                //Use group id as retrisction when user select group in multistore drop down
                $group = parent::getGroupFromShop(Shop::getContextShopID(), false);
                if ($share == parent::SHARE_CUSTOMER && parent::getContext() == parent::CONTEXT_SHOP && $group['share_customer']) {
                    $restriction = ' AND '.$alias.'id_shop_group = '.(int)parent::getContextShopGroupID().' ';
                } else {
                	//get id of current selected shop in multistore drop down
                    $restrictionid = parent::getContextListShopID($share);
                    //inject the id of added shop to list of id of select shop in multistore drop down 
                    $restrictionid = implode(',', $restrictionid).','.$restriction_id_shop_added;
                    $restriction = ' AND '.$alias.'id_shop IN ('.$restrictionid.') 
                                     AND bb.id_carrier IN ('.$assigned_carrier_id.')';    
                
                }
                return $restriction;
            } else {
                return parent::addSqlRestriction($share, $alias);
            }

        } else {
            return parent::addSqlRestriction($share, $alias);
        }

    }
    
    /*
    * module: cappickupatstore
    * date: 2016-11-19 11:21:35
    * version: 1.0
    */
    // remove the options to select the added shop (in module) from multistore drop down 
    public static function getTree()
    {   
        $remove_id_shops = explode(',', Configuration::get('res_shop'));
        $Enable = Shop::getEnable();
        if ($Enable)
        {
            Shop::cacheShops();
            foreach (self::$shops as $keylv1 => $valuelv1) {
                foreach ($valuelv1['shops'] as $keylv2 => $valuelv2) {
                    foreach ($remove_id_shops as $remove_id_shop) {
                        if ($valuelv2['id_shop'] == $remove_id_shop) {
                        self::$shops[$keylv1]['shops'][$keylv2]['active'] = 0;
                        break 1;
                        }
                    }
                }
            }
            return self::$shops;
        } else {
            return parent::getTree();
        }

    }

    //get the list of carrier assigned to the select shop from multistore drop down
    public static function getCarrierAssigned($share)
    {   
        $Enable = Shop::getEnable();
        if ($Enable)
        {
            $restrictionid = parent::getContextListShopID($share);
            $restrictionid = implode(',', $restrictionid);
            $sql = 'select id_carrier from `'._DB_PREFIX_.'carrier_shop` where id_shop IN ('.$restrictionid.')';
            $assigned_carrier_id = DB::getInstance()->ExecuteS($sql);
            $assigned_carrier_id = implode(', ', array_map(function ($entry) {
                                                 return $entry['id_carrier'];
                                                }, $assigned_carrier_id));
            return $assigned_carrier_id;
        }

    }

    //Check whether or not to enable the functionality of the module
    public static function getEnable()
    {   
        $Enable = false;
        $Error;
        $Employee_Access = true; 
        $bMatch;
        //Get the employee group id select in module
        $Restricted_Employee_Groups = explode(',', Configuration::get('res_emp_group'));
        //Get current employee id
        $Employee_id = context::getContext()->employee->id;
        //Get the id of added shop
        $Employee_Module_Shops_Assigned = explode(',', Configuration::get('res_shop'));
        //Query to check whether or not the current employee is assigned to the added shop
        $sql = 'select id_shop from '._DB_PREFIX_.'employee_shop where id_employee = '.$Employee_id;
        $Employee_Shops_Assigned = DB::getInstance()->ExecuteS($sql);
        foreach ($Employee_Module_Shops_Assigned as $Employee_Module_Shop_Assigned) {
            $bMatch = false;
            foreach ($Employee_Shops_Assigned  as $Employee_Shops_Assigned) {
                if ($Employee_Module_Shop_Assigned == implode($Employee_Shops_Assigned)){
                    $bMatch = true;
                    break 1;
                }
            }
            //Looping all of the shop id that added from module and the shop id that the employee was assigned in permission 
            if ($bMatch == false){
                $Employee_Access = false; 
                context::getContext()->controller->errors[] = 'User did not assigned to store '.$Employee_Module_Shop_Assigned;
                break;
            }

        }



        //User must select both the shop they want to add and the user group they want to restrict in order for the module to work
        if (Configuration::get('res_emp_group') && Configuration::get('res_shop')){
            foreach ($Restricted_Employee_Groups as $Restricted_Employee_Group) {
            	//Check the group of current employee to find whether or not they are in the restricted group 
                if (Context::getContext()->employee->id_profile == $Restricted_Employee_Group){
                	//Check if the current employee have access to the added shop
                    if ($Employee_Access){
                        $Enable = true; 
                    }
                    break; 
                }
           }   
        }

        return $Enable;
    }

}
 
