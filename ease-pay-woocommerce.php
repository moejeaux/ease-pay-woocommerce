<?php
/**
 * Plugin Name:       Ease Pay – Pay with USDC or Card on Base
 * Plugin URI:        https://easepay.xyz/woocommerce
 * Description:       Accept instant USDC payments on Base. Customers can pay with wallet or debit/credit card → USDC goes straight to your wallet. Zero custody, 1–2% fees.
 * Version:           1.0.0
 * Author:            NexFlow
 * Author URI:        https://easepay.xyz
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ease-pay-woocommerce
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:      9.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('EASEPAY_VERSION', '1.0.0');
define('EASEPAY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EASEPAY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EASEPAY_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Check if WooCommerce is active
 */
function easepay_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p><strong>Ease Pay</strong> requires WooCommerce to be installed and active.</p></div>';
        });
        return false;
    }
    return true;
}

/**
 * Initialize the gateway
 */
function easepay_init_gateway() {
    if (!easepay_check_woocommerce()) {
        return;
    }
    
    require_once EASEPAY_PLUGIN_PATH . 'includes/class-wc-gateway-easepay.php';
    
    // Add the gateway to WooCommerce
    add_filter('woocommerce_payment_gateways', function($gateways) {
        $gateways[] = 'WC_Gateway_EasePay';
        return $gateways;
    });
}
add_action('plugins_loaded', 'easepay_init_gateway');

/**
 * Add settings link on plugin page
 */
add_filter('plugin_action_links_' . EASEPAY_PLUGIN_BASENAME, function($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=easepay') . '">' . __('Settings', 'ease-pay-woocommerce') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});

/**
 * Declare HPOS compatibility
 */
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Handle webhook callback from Ease Pay
 */
add_action('rest_api_init', function() {
    register_rest_route('easepay/v1', '/webhook', array(
        'methods'  => 'POST',
        'callback' => 'easepay_handle_webhook',
        'permission_callback' => '__return_true',
    ));
});

/**
 * Process incoming webhook from Ease Pay
 */
function easepay_handle_webhook($request) {
    $payload = $request->get_json_params();
    
    if (empty($payload['order_id']) || empty($payload['status'])) {
        return new WP_Error('invalid_payload', 'Missing required fields', array('status' => 400));
    }
    
    $order_id = absint($payload['order_id']);
    $status = sanitize_text_field($payload['status']);
    $tx_hash = isset($payload['tx_hash']) ? sanitize_text_field($payload['tx_hash']) : '';
    
    $order = wc_get_order($order_id);
    
    if (!$order) {
        return new WP_Error('order_not_found', 'Order not found', array('status' => 404));
    }
    
    // Verify the order is pending and uses our gateway
    if ($order->get_payment_method() !== 'easepay') {
        return new WP_Error('invalid_gateway', 'Order does not use Ease Pay', array('status' => 400));
    }
    
    switch ($status) {
        case 'completed':
        case 'confirmed':
            $order->payment_complete($tx_hash);
            $order->add_order_note(sprintf(
                __('Ease Pay payment completed. TX: %s', 'ease-pay-woocommerce'),
                $tx_hash ? '<a href="https://basescan.org/tx/' . $tx_hash . '" target="_blank">' . substr($tx_hash, 0, 16) . '...</a>' : 'N/A'
            ));
            break;
            
        case 'pending':
            $order->update_status('on-hold', __('Ease Pay payment pending confirmation.', 'ease-pay-woocommerce'));
            break;
            
        case 'failed':
        case 'expired':
            $order->update_status('failed', __('Ease Pay payment failed or expired.', 'ease-pay-woocommerce'));
            // Restore stock
            wc_increase_stock_levels($order_id);
            break;
    }
    
    return array('success' => true);
}

/**
 * Add custom order status for crypto pending
 */
add_action('init', function() {
    register_post_status('wc-crypto-pending', array(
        'label'                     => _x('Crypto Pending', 'Order status', 'ease-pay-woocommerce'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Crypto Pending <span class="count">(%s)</span>', 'Crypto Pending <span class="count">(%s)</span>', 'ease-pay-woocommerce')
    ));
});

add_filter('wc_order_statuses', function($order_statuses) {
    $order_statuses['wc-crypto-pending'] = _x('Crypto Pending', 'Order status', 'ease-pay-woocommerce');
    return $order_statuses;
});
