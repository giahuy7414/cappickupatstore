<?php
class Shop extends ShopCore
{


    /*
    * module: cappickupatstore
    * date: 2016-11-19 11:21:35
    * version: 1.0
    */
    public static function addSqlRestriction($share = false, $alias = null) //XXX
    {   
        $Enable = Shop::getEnable();
        if ($Enable) {
            $restriction_id_shop_added = Configuration::get('res_shop');
            $assigned_carrier_id = Shop::getCarrierAssigned($restriction_id_shop_added,$share);
            if (debug_backtrace(false)[1]['function'] == 'getList' && $assigned_carrier_id && Context::getContext()->controller instanceof AdminOrdersController && !Tools::isSubmit('id_order') && !Tools::isSubmit('addorder')) {
                if ($alias) {
                    $alias .= '.';
                }
                $group = parent::getGroupFromShop(Shop::getContextShopID(), false);
                if ($share == parent::SHARE_CUSTOMER && parent::getContext() == parent::CONTEXT_SHOP && $group['share_customer']) {
                    $restriction = ' AND '.$alias.'id_shop_group = '.(int)parent::getContextShopGroupID().' ';
                } else {
                    $restrictionid = parent::getContextListShopID($share);
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

    //get the list of carrier assigned to current shop 
    public static function getCarrierAssigned($restriction_id_shop_added,$share)
    {   
        $Enable = Shop::getEnable();
        if ($Enable)
        {
            $restrictionid = parent::getContextListShopID($share);
            $restrictionid = implode(',', $restrictionid).','.$restriction_id_shop_added;
            $sql = 'select id_carrier from `'._DB_PREFIX_.'carrier_shop` where id_shop IN ('.$restrictionid.')';
            $assigned_carrier_id = DB::getInstance()->ExecuteS($sql);
            $assigned_carrier_id = implode(', ', array_map(function ($entry) {
                                                 return $entry['id_carrier'];
                                                }, $assigned_carrier_id));
            return $assigned_carrier_id;
        }

    }

    public static function getEnable()
    {   
        $Enable = false;
        $Error;
        $Employee_Access = true; 
        $bMatch;
        $Restricted_Employee_Groups = explode(',', Configuration::get('res_emp_group'));
        $Employee_id = context::getContext()->employee->id;
        $Employee_Module_Shops_Assigned = explode(',', Configuration::get('res_shop'));
        $sql = 'select id_shop from '._DB_PREFIX_.'employee_shop where id_employee = '.$Employee_id;
        $Employee_Shops_Assigned = DB::getInstance()->ExecuteS($sql);
        foreach ($Employee_Module_Shops_Assigned as $Employee_Module_Shop_Assigned) {
            $bMatch = false;
            foreach ($Employee_Shops_Assigned  as $Employee_Shops_Assigned) {
                if (implode($Employee_Shops_Assigned) == $Employee_Module_Shop_Assigned){
                    $bMatch = true;
                    break 1;
                }
            }
            if ($bMatch == false){
                $Employee_Access = false; 
                context::getContext()->controller->errors[] = 'User did not assigned to store '.$Employee_Module_Shop_Assigned;
                break;
            }

        }




        if (Configuration::get('res_emp_group') && Configuration::get('res_shop')){
            foreach ($Restricted_Employee_Groups as $Restricted_Employee_Group) {
                if (Context::getContext()->employee->id_profile == $Restricted_Employee_Group){
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
 
