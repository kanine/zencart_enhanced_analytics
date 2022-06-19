<?php 
/**
 * @package Analytics and Adwords Conversions
 * @copyright (c) 2018 kanine
 * @copyright Copyright 2003-2017 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart-pro.at/license/2_0.txt GNU Public License V2.0
 * @version $Id: jscript_analytics_and_adwords.php 2018-06-25 20:47:36Z kanine $
 */

LogThis('Start: ' . __FILE__);

if ( defined('GTAG_ANALYTICS') ) { ?>
  <!-- Global Site Tag (gtag.js) - Google Analytics -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo GTAG_ANALYTICS; ?>"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', '<?php echo GTAG_ANALYTICS; ?>');
    <?php if ( GTAG_ADWORDS_ENABLED ): ?>
      gtag('config', '<?php echo GTAG_ADWORDS; ?>');
    <?php endif; ?>

  </script>

<?php
  if ( isset($_SESSION['analytics']) ) {  
    // global $analytics, $cID;
    $gtagCustomerID = ( isset($_SESSION['customer_id']) ) ? "customerID#".$_SESSION['customer_id'] : "guest";
    $analytics = $_SESSION['analytics'];
    // LogThis('Analytics Array: ' . print_r($analytics,true));
    if ( $analytics['action'] == 'Checkout Success' && count($analytics['items']) >= 1 ): ?>

      <script>

      <?php
        // Changed to Load a Variable for Simplified Debugging
        
        $thisGtag  = "gtag('event', 'purchase', {" . PHP_EOL;
        $thisGtag .= '"transaction_id": "' . $analytics['transaction']['transaction_id'] . '",' . PHP_EOL;
        $thisGtag .= '"affiliation": "' . addslashes($analytics['transaction']['affiliation']) . '",' . PHP_EOL;
        $thisGtag .= '"value": ' . $analytics['transaction']['revenue'] . ',' . PHP_EOL;
        $thisGtag .= '"shipping": ' . $analytics['transaction']['shipping'] . ',' . PHP_EOL;
        $thisGtag .= '"currency": "' . $analytics['transaction']['currency'] . '",' . PHP_EOL;
        $thisGtag .= '"items": [' . PHP_EOL;
      
        $isFirst = true;
        $gtagItemTracking = '';
        // LogThis('Items: ' . print_r($analytics['items'],true));
        foreach ( $analytics['items'] as $item ) {
          // LogThis('This Item: ' . print_r($item,true));
          $thisGtag .= ( $isFirst ? '' : ',') . PHP_EOL;
          $thisGtag .= '{"item_id": "' . $item['item_id'] . '",' . PHP_EOL;
          $thisGtag .= '"item_name": "' . addslashes($item['item_name']) . '",' . PHP_EOL;
          $thisGtag .= '"item_brand": "' . addslashes($item['item_brand']) . '",' . PHP_EOL;
          $thisGtag .= '"item_category": "' . addslashes($item['item_category']) . '",' . PHP_EOL;
          $thisGtag .= '"list_position": "' . $item['position'] . '",' . PHP_EOL;
          $thisGtag .= '"item_variant": "' . addslashes($item['item_variant']) . '",' . PHP_EOL;
          $thisGtag .= '"price": ' . $item['price'] . ',' . PHP_EOL;
          $thisGtag .= '"quantity":' . $item['quantity']  . PHP_EOL; 
          $thisGtag .= '}' . PHP_EOL;
          $isFirst = false;
        }
        $thisGtag .= ']});' . PHP_EOL;

        LogThis("Analytics GTAG Output: " . $thisGtag);

        echo $thisGtag;

      ?>

      </script>
    
    <?php if ( GTAG_ADWORDS_ENABLED ) { ?> 

    <script>

      gtag('event', 'conversion', {
          'send_to': '<?php echo GTAG_ADWORDS_SENDTO; ?>',
          'value': <?php echo $analytics['transaction']['revenue']; ?>,
          'currency': '<?php echo $analytics['transaction']['currency']; ?>',
          'transaction_id': '<?php echo $analytics['transaction']['id']; ?>'
      });

    </script>

    <?php } ?>

<?php endif; ?>

<?php
     unset($_SESSION['analytics']);

  } 
} ?>
