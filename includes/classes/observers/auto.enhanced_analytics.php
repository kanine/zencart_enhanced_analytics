<?php 
/**
 * @package Google Enhanced E-Commerce Analytics
 * @copyright (c) 2022 bti
 * @license GNU Public License V2.0
 * @version $Id: class.enahnced_analytics.php 2022-07-09 kanine $
 */

 class zcObserverEnhancedAnalytics extends base {

  public $code = 'enhanced_analytics';
  private $_logDir = DIR_FS_CATALOG . 'logs/plugins/'; // Change this to preference

  function __construct() {
  
    $this->attach($this, array('NOTIFY_HEADER_START_CHECKOUT_SUCCESS'));
  
  }

  public function update(&$callingClass, $notifier, $paramsArray) {
  
    global $db, $analytics;

    switch ($notifier) {
      case 'NOTIFY_HEADER_START_CHECKOUT_SUCCESS': //  All Checkout complete/successful 
        $order_summary = $_SESSION['order_summary'];
        
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
          
          $variant = $db->Execute("SELECT products_options_values FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " WHERE orders_products_id = " . (string)$items_in_cart->fields['orders_products_id']);
          $varTxt = ($variant->fields['products_options_values'] != "") ? $variant->fields['products_options_values']:"n/a";
          
          // Example Additional Product Details to get a brand name for a product
          $productDetails = $this->getAdditionalProductDetails($items_in_cart->fields['products_model']); 
          $brandTxt = ( $productDetails ? $productDetails['brand'] : 'Generic' );
          
          $analytics['items'][] = array('item_id' => $items_in_cart->fields['products_id'],
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

    $_SESSION['analytics'] = $analytics;
  
  }

  private function getAdditionalProductDetails($productModel) {

    return false;

    /*
    global $db;

    $sql = "select barcode, brand, manufacturerCode
            from another_table
            WHERE sku = :productModel LIMIT 1";

    $sql = $db->bindVars($sql, ':productModel', $productModel, 'string');
    
    $detail = $db->Execute($detail_query);

    return ($detail->RecordCount() > 0) ? $product->fields : false;
    */

  }

  private function zcLog($stage, $message = '', $doBackTrace = false) {
    include(DIR_WS_CLASSES . 'vendors/bti/zcLog.php');
  }

}