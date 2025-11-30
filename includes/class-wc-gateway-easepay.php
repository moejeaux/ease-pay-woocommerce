<?php
/**
 * Ease Pay WooCommerce Payment Gateway
 *
 * @package EasePay
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_Gateway_EasePay Class
 */
class WC_Gateway_EasePay extends WC_Payment_Gateway {

    /**
     * API Base URL
     * @var string
     */
    private $api_base;

    /**
     * Merchant wallet address
     * @var string
     */
    private $merchant_wallet;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id                 = 'easepay';
        $this->icon               = EASEPAY_PLUGIN_URL . 'assets/icon.png';
        $this->has_fields         = true;
        $this->method_title       = __('Ease Pay', 'ease-pay-woocommerce');
        $this->method_description = __('Accept USDC payments on Base. Customers can pay with crypto wallet or debit/credit card (via Coinbase Onramp). Funds go directly to your wallet – zero custody.', 'ease-pay-woocommerce');
        $this->supports           = array('products', 'refunds');

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        // Get settings
        $this->title           = $this->get_option('title', __('Pay with USDC or Card', 'ease-pay-woocommerce'));
        $this->description     = $this->get_option('description', __('Instant, low-fee payments on Base. Pay with your crypto wallet or debit/credit card.', 'ease-pay-woocommerce'));
        $this->api_base        = rtrim($this->get_option('api_base', 'https://easepay.xyz'), '/');
        $this->merchant_wallet = $this->get_option('merchant_wallet', '');
        $this->enabled         = $this->get_option('enabled', 'no');

        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_api_easepay', array($this, 'handle_callback'));
        
        // Add custom styles to checkout
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Initialize form fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Enable/Disable', 'ease-pay-woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable Ease Pay', 'ease-pay-woocommerce'),
                'default' => 'no',
            ),
            'title' => array(
                'title'       => __('Title', 'ease-pay-woocommerce'),
                'type'        => 'text',
                'description' => __('Payment method title shown to customers.', 'ease-pay-woocommerce'),
                'default'     => __('Pay with USDC or Card', 'ease-pay-woocommerce'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'ease-pay-woocommerce'),
                'type'        => 'textarea',
                'description' => __('Payment method description shown to customers.', 'ease-pay-woocommerce'),
                'default'     => __('Pay instantly with USDC on Base. Use your crypto wallet or debit/credit card. Low fees, instant settlement.', 'ease-pay-woocommerce'),
                'desc_tip'    => true,
            ),
            'merchant_wallet' => array(
                'title'       => __('Merchant Wallet Address', 'ease-pay-woocommerce'),
                'type'        => 'text',
                'description' => __('Your Base wallet address where USDC payments will be sent. Must start with 0x.', 'ease-pay-woocommerce'),
                'default'     => '',
                'placeholder' => '0x...',
                'desc_tip'    => true,
                'custom_attributes' => array(
                    'pattern' => '^0x[a-fA-F0-9]{40}$',
                ),
            ),
            'api_base' => array(
                'title'       => __('Ease Pay API URL', 'ease-pay-woocommerce'),
                'type'        => 'url',
                'description' => __('Your Ease Pay instance URL. Default: https://easepay.xyz', 'ease-pay-woocommerce'),
                'default'     => 'https://easepay.xyz',
                'desc_tip'    => true,
            ),
            'debug' => array(
                'title'       => __('Debug Mode', 'ease-pay-woocommerce'),
                'type'        => 'checkbox',
                'label'       => __('Enable debug logging', 'ease-pay-woocommerce'),
                'default'     => 'no',
                'description' => __('Log Ease Pay events to WooCommerce logs.', 'ease-pay-woocommerce'),
            ),
        );
    }

    /**
     * Check if gateway is available
     */
    public function is_available() {
        if ($this->enabled !== 'yes') {
            return false;
        }

        // Check if wallet is configured
        if (empty($this->merchant_wallet) || !preg_match('/^0x[a-fA-F0-9]{40}$/', $this->merchant_wallet)) {
            return false;
        }

        return true;
    }

    /**
     * Admin options validation
     */
    public function process_admin_options() {
        $saved = parent::process_admin_options();
        
        // Validate wallet address
        $wallet = $this->get_option('merchant_wallet');
        if (!empty($wallet) && !preg_match('/^0x[a-fA-F0-9]{40}$/', $wallet)) {
            WC_Admin_Settings::add_error(__('Invalid wallet address. Must be a valid Ethereum address starting with 0x.', 'ease-pay-woocommerce'));
        }
        
        return $saved;
    }

    /**
     * Payment fields shown at checkout
     */
    public function payment_fields() {
        if ($this->description) {
            echo '<p>' . wp_kses_post($this->description) . '</p>';
        }
        
        echo '<div class="easepay-payment-info">';
        echo '<div class="easepay-features">';
        echo '<span class="easepay-feature">💳 Card or Wallet</span>';
        echo '<span class="easepay-feature">⚡ Instant Settlement</span>';
        echo '<span class="easepay-feature">🔒 Non-Custodial</span>';
        echo '</div>';
        echo '<p class="easepay-network"><img src="' . esc_url(EASEPAY_PLUGIN_URL . 'assets/base-logo.svg') . '" alt="Base" style="height:16px;vertical-align:middle;margin-right:4px;"> Powered by Base</p>';
        echo '</div>';
    }

    /**
     * Process payment
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wc_add_notice(__('Order not found.', 'ease-pay-woocommerce'), 'error');
            return array('result' => 'failure');
        }

        $amount = $order->get_total();
        $currency = $order->get_currency();
        
        // Build checkout URL with all necessary parameters
        $checkout_url = add_query_arg(array(
            'amount'          => $amount,
            'currency'        => $currency,
            'merchant_wallet' => $this->merchant_wallet,
            'order_id'        => $order_id,
            'return_url'      => $this->get_return_url($order),
            'cancel_url'      => wc_get_checkout_url(),
            'webhook_url'     => get_rest_url(null, 'easepay/v1/webhook'),
            'store_name'      => get_bloginfo('name'),
            'customer_email'  => $order->get_billing_email(),
        ), $this->api_base . '/pay/' . $this->merchant_wallet);

        // Update order status
        $order->update_status('pending', __('Awaiting Ease Pay payment.', 'ease-pay-woocommerce'));
        
        // Reduce stock levels
        wc_reduce_stock_levels($order_id);
        
        // Clear cart
        WC()->cart->empty_cart();

        // Log if debug enabled
        if ($this->get_option('debug') === 'yes') {
            $this->log('Payment initiated for order #' . $order_id . '. Redirect URL: ' . $checkout_url);
        }

        return array(
            'result'   => 'success',
            'redirect' => $checkout_url,
        );
    }

    /**
     * Handle callback from Ease Pay
     */
    public function handle_callback() {
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        
        if ($this->get_option('debug') === 'yes') {
            $this->log('Callback received: ' . $payload);
        }
        
        if (empty($data['order_id'])) {
            wp_send_json_error('Missing order_id', 400);
            exit;
        }
        
        $order = wc_get_order(absint($data['order_id']));
        
        if (!$order) {
            wp_send_json_error('Order not found', 404);
            exit;
        }
        
        $status = isset($data['status']) ? sanitize_text_field($data['status']) : '';
        $tx_hash = isset($data['tx_hash']) ? sanitize_text_field($data['tx_hash']) : '';
        
        switch ($status) {
            case 'completed':
            case 'confirmed':
                $order->payment_complete($tx_hash);
                $order->add_order_note(sprintf(
                    __('Payment completed via Ease Pay. Transaction: %s', 'ease-pay-woocommerce'),
                    $tx_hash ? '<a href="https://basescan.org/tx/' . $tx_hash . '" target="_blank">' . $tx_hash . '</a>' : 'N/A'
                ));
                break;
                
            case 'pending':
                $order->update_status('on-hold', __('Payment pending blockchain confirmation.', 'ease-pay-woocommerce'));
                break;
                
            case 'failed':
            case 'expired':
                $order->update_status('failed', __('Payment failed or expired.', 'ease-pay-woocommerce'));
                wc_increase_stock_levels($order->get_id());
                break;
        }
        
        wp_send_json_success();
        exit;
    }

    /**
     * Thank you page content
     */
    public function thankyou_page($order_id) {
        $order = wc_get_order($order_id);
        
        if ($order && $order->has_status('processing')) {
            echo '<div class="easepay-thankyou">';
            echo '<h3>' . esc_html__('Payment Successful!', 'ease-pay-woocommerce') . '</h3>';
            echo '<p>' . esc_html__('Your USDC payment has been received and confirmed on Base.', 'ease-pay-woocommerce') . '</p>';
            echo '</div>';
        }
    }

    /**
     * Receipt page (redirect page)
     */
    public function receipt_page($order_id) {
        echo '<p>' . esc_html__('Redirecting to Ease Pay secure checkout...', 'ease-pay-woocommerce') . '</p>';
        echo '<p><a href="' . esc_url($this->api_base) . '" class="button">' . esc_html__('Click here if not redirected', 'ease-pay-woocommerce') . '</a></p>';
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (!is_checkout()) {
            return;
        }
        
        wp_enqueue_style(
            'easepay-checkout',
            EASEPAY_PLUGIN_URL . 'assets/checkout.css',
            array(),
            EASEPAY_VERSION
        );
    }

    /**
     * Log messages
     */
    private function log($message) {
        if (class_exists('WC_Logger')) {
            $logger = wc_get_logger();
            $logger->info($message, array('source' => 'easepay'));
        }
    }

    /**
     * Process refund
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        // Refunds need to be handled manually for crypto payments
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return new WP_Error('invalid_order', __('Order not found.', 'ease-pay-woocommerce'));
        }
        
        $order->add_order_note(sprintf(
            __('Refund of %s requested. Reason: %s. Please process manually via your wallet.', 'ease-pay-woocommerce'),
            wc_price($amount),
            $reason ? $reason : 'N/A'
        ));
        
        return true;
    }
}
