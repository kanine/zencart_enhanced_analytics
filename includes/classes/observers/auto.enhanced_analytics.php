<?php 
/**
 * @package Google Enhanced E-Commerce Analytics
 * @copyright (c) 2015 RodG
 * @copyright Copyright 2003-2017 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart-pro.at/license/2_0.txt GNU Public License V2.0
 * @version $Id: class.ec_analytics.php 2017-12-11  DrByte $
 */
class zcObserverEnhancedAnalytics extends base {

  function __construct() {
      global $zco_notifier;
      LogThis('Start: ' . __METHOD__);
      $zco_notifier->attach($this, array('NOTIFY_HEADER_TIMEOUT'));

      $this->attach($this, array(
          // actionable events //
          'NOTIFY_HEADER_START_CHECKOUT_SUCCESS'
      ));
  }

  function getID() {
      $id = '';
      LogThis('Start: ' . __METHOD__);
      if (isset($_REQUEST['products_id'])) {
          if (is_array($_REQUEST['products_id'])) {  $id = explode(":", $_REQUEST['products_id'][0]);
          } else {  $id = explode(":", $_REQUEST['products_id']);  }
      } else {
          if (isset($_REQUEST['product_id'])) {
              if (is_array($_REQUEST['product_id'])) { $id = explode(":", $_REQUEST['product_id'][0]);
              } else {  $id = explode(":", $_REQUEST['product_id']);  }
          }
      }
      $id = (int)$id[0];
      if ($id === 0) $id = "";
  return $id;
  }

    
 function getCatString($id) {
  global $db, $cPath ;
  LogThis('Start: ' . __METHOD__);
  $masterCat = zen_get_categories_name_from_product($id) ;
  $catTxt = '';
     $i = 0 ; $flag = 0 ;
        if(isset($cPath)) {
            $p = explode('_',$cPath) ;
            while ($i < count($p)) {
                $the_categories_name= $db->Execute("select categories_name from " . TABLE_CATEGORIES_DESCRIPTION . " where categories_id= '" . $p[$i] . "' and language_id= '" . $_SESSION['languages_id'] . "'");
                if ($masterCat ==  $the_categories_name->fields['categories_name'])  {$flag = 1 ;}
                     
            $i++ ;        
            } 
        $catTxt = substr($catTxt, 1);
        }     
  return ($flag != 1 ) ? $masterCat:$catTxt;      
// return $catTxt ;    
 }   
    
 function addProductItemsStr() { 
    LogThis('Start: ' . __METHOD__);
    $itemsStr = "" ;  $i=0 ; 
    $products = $_SESSION['cart']->get_products(); 
    if(is_array($products)) { 
      foreach ($products as $item) { 
        if(is_array($item['attributes'])) $varTxt = zen_values_name($item['attributes'][1]);                     
        if(!$varTxt) $varTxt = "n/a"; 
        
        $itemID = explode(":", $item['id'] ) ;

        $brand = zen_get_products_manufacturers_name($itemID[0]) ; 
        $brandTxt = ($brand != "") ? $brand:"n/a";
        $itemsStr .= "ga('ec:addProduct',"
                  . " {'id': '{$itemID[0]}',"
                              . " 'name': '".addslashes($item['name'])."',"
                              . " 'brand': '{$brandTxt}',"
                              . " 'category': '". zen_get_categories_name_from_product($itemID[0])."' ,"
                              . " 'variant': '{$varTxt}',"
                              .  " 'price': '".number_format((float)($item['price'] + ($item['price'] *  $item['tax_class_id'] / 100 )) ,2,'.','')."',"
                              . " 'quantity': '{$item['quantity']}',"
                                . " 'position': '{$i}' } );\n";
        $i++ ;  
      }
    }    
    return $itemsStr ;           
  }

  /////////////////////////////          
  function update(&$callingClass, $notifier, $paramsArray) {
    global $db, $analytics;

    LogThis('Start: ' . __METHOD__ . ' ' . $notifier);
    switch ($notifier) {
      case 'NOTIFY_HEADER_START_CHECKOUT_SUCCESS': //  All Checkout complete/successful 
        $order_summary = $_SESSION['order_summary'];
        
        // LogThis('Order Summary $_SESSION: ' . print_r($order_summary,true));

        $coupon = isset($order_summary['coupon_code']) ? $order_summary['coupon_code'] : "n/a";
        
        $analytics['transaction'] = array('transaction_id' => (string)$order_summary['order_number'],
                                          'affiliation' => $order_summary['shipping_method'],
                                          'revenue'  => number_format($order_summary['order_total'],2,'.',''),
                                          'tax'  => number_format($order_summary['tax'],2,'.',''),
                                          'shipping'  => number_format($order_summary['shipping'],2,'.',''),
                                          'currency'  => $order_summary['currency_code']
                                        );
        
        $items_query = "SELECT DISTINCT orders_products_id, products_id, products_name, products_model, final_price, products_tax, products_quantity
            FROM " . TABLE_ORDERS_PRODUCTS . " WHERE orders_id = :ordersID ORDER BY products_name";

        $items_query = $db->bindVars($items_query, ':ordersID', $order_summary['order_number'], 'integer');
        $items_in_cart = $db->Execute($items_query);

        
        
        $i = 0 ; 
        
        while (!$items_in_cart->EOF) {
          
          // LogThis($items_in_cart->fields);
          
          $variant = $db->Execute("SELECT products_options_values FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " WHERE orders_products_id = " . (string)$items_in_cart->fields['orders_products_id']);
          $varTxt = ($variant->fields['products_options_values'] != "") ? $variant->fields['products_options_values']:"n/a";
          $productDetails = btiGetProductDetails($items_in_cart->fields['products_model']); 
          $brandTxt = ( $productDetails ? $productDetails['brand'] : 'Generic' );
          
          $analytics['items'][] = Array('item_id' => $items_in_cart->fields['products_id'],
                                        'item_name' => $items_in_cart->fields['products_name'],
                                        'item_brand' => $brandTxt,
                                        'item_category' => zen_get_categories_name_from_product($items_in_cart->fields['products_id']),
                                        'item_variant' => $varTxt,
                                        'price' => number_format($items_in_cart->fields['final_price'] + ($items_in_cart->fields['final_price'] *  $items_in_cart->fields['products_tax'] / 100 ),2,'.',''),
                                        'quantity' => $items_in_cart->fields['products_quantity'],
                                        'coupon' => $coupon,
                                        'position' => $i + 1);
          $i++;
          $items_in_cart->MoveNext();
        }
        $analytics['action'] = "Checkout Success";
        break;

      default:   
      $notifyArr = explode("_", $notifier, 2);
      $analytics['action'] = ucwords(strtolower(str_replace("_", " ", $notifyArr[1])));
    }

    // LogThis('Set Analytics Info: ' . print_r($analytics,true));
    $_SESSION['analytics'] = $analytics;
  
  }
}