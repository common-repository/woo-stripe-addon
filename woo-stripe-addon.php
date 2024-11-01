<?php
ini_set('display_errors', 0);
/**
 * Plugin Name:       Addon for Stripe and WooCommerce
 * Plugin URL:        Addon for Stripe and WooCommerce
 * Description:       Woo Stripe Addon allows you to accept payments on your Woocommerce store. It accpets credit card payments and processes them securely with your merchant account.
 * Version:           2.0.4
 * WC requires at least: 2.3
 * WC tested up to:     3.8.1
 * Requires at least:   4.0+
 * Tested up to:        5.3.2
 * Contributors:        wp_estatic
 * Author:            Estatic Infotech Pvt Ltd
 * Author URI:        http://estatic-infotech.com/
 * License:           GPLv3
 * @package WooCommerce
 * @category Woocommerce Payment Gateway
 */
require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    deactivate_plugins(plugin_basename(__FILE__));
    add_action('load-plugins.php', function() {
        add_filter('gettext', 'change_text', 99, 3);
    });

    function change_text($translated_text, $untranslated_text, $domain) {
        $old = array(
            "Plugin activated.",
            "Selected plugins <strong>activated</strong>."
        );

        $new = "Please activate <b>Woocommerce</b> Plugin to use WooCommerce Stripe Addon plugin";

        if (in_array($untranslated_text, $old, true)) {
            $translated_text = $new;
            remove_filter(current_filter(), __FUNCTION__, 99);
        }
        return $translated_text;
    }

    return FALSE;
}

add_action('plugins_loaded', 'init_stripe_payment_gateway');

function init_stripe_payment_gateway() {

    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links');

    function add_action_links($links) {
        $action_links = array(
            'settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_stripe_gateway_ei') . '" title="' . esc_attr(__('View WooCommerce Settings', 'woocommerce')) . '">' . __('Settings', 'woocommerce') . '</a>',
        );
        return array_merge($links, $action_links);
    }

    if (class_exists('WC_Payment_Gateway')) {

        function add_stripe_gateway_class_ei($methods) {
            $methods[] = 'WC_Stripe_Gateway_EI';
            return $methods;
        }

        add_filter('woocommerce_payment_gateways', 'add_stripe_gateway_class_ei');

        class WC_Stripe_Gateway_EI extends WC_Payment_Gateway {

            public function __construct() {

                $this->id = 'ei_stripe';
                $this->has_fields = true;
                $title = $this->get_option('stripe_title');
                if (!empty($title)) {
                    $this->title = $this->get_option('stripe_title');
                    $this->getway_name = $title;
                } else {
                    $this->title = 'Credit Card';
                    $this->getway_name = 'Stripe';
                }

                $getway_description = $this->get_option('stripe_description');
                if (!empty($getway_description)) {
                    $this->getway_description = $getway_description;
                } else {
                    $this->getway_description = 'Stripe allows you to accept payments on your Woocommerce store. It accepts credit card payments and processes them securely with your merchant account.Please dont forget to test with sandbox account first.';
                }

                $this->method_title = $this->getway_name;
                $this->method_description = $this->getway_description;
                $this->desc = 'Test Description Test description.';
                $this->init_form_fields();
                $this->init_settings();
                $this->supports = array('products', 'refunds');
                $this->stripe_api_key = base64_encode($this->get_option('stripe_api_key'));
                $this->stripe_cardtypes = $this->get_option('stripe_cardtypes');
                $this->mode = $this->get_option('mode');
                $this->currency = $this->get_option('currency');
                $this->stripe_zerocurrency = array("BIF", "CLP", "DJF", "GNF", "JPY", "KMF", "KRW", "MGA", "PYG", "RWF", "VND", "VUV", "XAF", "XOF", "XPF");
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

                add_action('woocommerce_order_status_processing_to_cancelled', array($this, 'restore_stock_stripe'), 10, 1);
                add_action('woocommerce_order_status_completed_to_cancelled', array($this, 'restore_stock_stripe'), 10, 1);
                add_action('woocommerce_order_status_on-hold_to_cancelled', array($this, 'restore_stock_stripe'), 10, 1);
                add_action('woocommerce_order_status_processing_to_refunded', array($this, 'restore_stock_stripe'), 10, 1);
                add_action('woocommerce_order_status_completed_to_refunded', array($this, 'restore_stock_stripe'), 10, 1);
                add_action('woocommerce_order_status_on-hold_to_refunded', array($this, 'restore_stock_stripe'), 10, 1);
            }

            public function restore_stock_stripe($order_id) {
                include(plugin_dir_path(__FILE__) . "init.php");
                $stripe_key = base64_decode($this->stripe_api_key);
                if (!empty($stripe_key)) {
                    \Stripe\Stripe::setApiKey($stripe_key);
                } else {
                    wc_add_notice('Please Do correct setup of Stripe Payment Gateway', $notice_type = 'error');
                    return array(
                        'result' => 'success',
                        'redirect' => wc_get_checkout_url(),
                    );
                    die;
                }
                $order = wc_get_order( $order_id );
                $order_id  = $order->get_id();
                $CHARGE_ID = get_post_meta($order_id, '_transaction_id', true);

                // $refund = self::process_refund($order_id, $amount = NULL);

                $refund=  \Stripe\Refund::create([
                    'charge' =>  $CHARGE_ID,
                ]);

                if ($refund) {
                    $repoch = $refund->created;
                    $rdt = new DateTime("@$repoch");
                    $rtimestamp = $rdt->format('Y-m-d H:i:s e');
                    $refundid = $refund->id;

                    $wc_order = new WC_Order($order_id);
                    $wc_order->add_order_note(__('Stripe Refund completed at. ' . $rtimestamp . ' with Refund ID = ' . $refundid, 'woocommerce'));
                    //var_dump($refundid); exit;
                    return true;
                } else {
                    return false;
                }

            }

            public function get_icon() {
                if ($this->get_option('show_accepted') == 'yes') {
                    $get_cardtypes = $this->get_option('stripe_cardtypes');
                    $icons = "";
                    foreach ($get_cardtypes as $val) {
                        $cardimage = plugins_url('images/' . $val . '.png', __FILE__);
                        $icons .= '<img src="' . $cardimage . '" alt="' . $val . '" />';
                    }
                } else {
                    $icons = "";
                }
                return apply_filters('woocommerce_gateway_icon', $icons, $this->id);
            }

            public function init_form_fields() {
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __('Enable/Disable', 'woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Enable Stripe', 'woocommerce'),
                        'default' => 'yes',
                    ),
                    'stripe_title' => array(
                        'title' => __('Getway Name', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('Display this name on checkout page.', 'woocommerce'),
                        'default' => 'Stripe',
                        'desc_tip' => true,
                        'css' => 'width: 100% !important;max-width: 400px;',
                    ),
                    'stripe_description' => array(
                        'title' => __('GateWay Description', 'woocommerce'),
                        'type' => 'textarea',
                        'description' => __('Display this description on checkout page.', 'woocommerce'),
                        'default' => $this->getway_description,
                        'desc_tip' => true,
                        'css' => 'width: 100% !important;max-width: 400px;',
                    ),
                    'mode' => array(
                        'title' => __('Mode', 'woocommerce'),
                        'type' => 'select',
                        'class' => 'chosen_select',
                        'css' => 'width: 350px;',
                        'desc_tip' => __('Select the mode to accept.', 'woocommerce'),
                        'options' => array(
                            'sandbox' => 'SandBox (Test)',
                            'live' => 'Live',
                        ),
                        'default' => array('sandbox'),
                    ),
                    'stripe_api_key' => array(
                        'title' => __('Stripe Api Key (Secret Key)', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('<span style="color:red;">Get your API key from your Stripe account. Please dont forget to add Your Stripe API key. <br/>If you have selected Mode as Sandbox then Add here Test Secret Key.'
                                . ' <br/>If you have selected Mode as Live then Add here Live Secret Key.</span>', 'woocommerce'),
                        'desc_tip' => __('Please dont forget to add Your Stripe API key.', 'woocommerce'),
                        'class' => 'api_key_toggle',
                        'default' => ''
                    ),
                    'stripe_api_key_hide_show' => array(
                        'title' => '',
                        'type' => 'checkbox',
                        'label' => __('Hide/Show Stripe Api Secret Key', 'woocommerce'),
                        'class' => 'api_toggle_unique',
                        'default' => ''
                    ),
                    'show_accepted' => array(
                        'title' => __('Show Accepted Card Icons', 'woocommerce'),
                        'type' => 'select',
                        'class' => 'chosen_select',
                        'css' => 'width: 350px;',
                        'desc_tip' => __('Select the mode to accept.', 'woocommerce'),
                        'options' => array(
                            'yes' => 'Yes',
                            'no' => 'No',
                        ),
                        'default' => array('yes'),
                    ),
                    'stripe_cardtypes' => array(
                        'title' => __('Accepted Card Types', 'woocommerce'),
                        'type' => 'multiselect',
                        'class' => 'chosen_select',
                        'css' => 'width: 350px;',
                        'desc_tip' => __('Add/Remove credit card types to accept.', 'woocommerce'),
                        'options' => array(
                            'mastercard' => 'MasterCard',
                            'visa' => 'Visa',
                            'discover' => 'Discover',
                            'amex' => 'AMEX',
                            'jcb' => 'JCB',
                            'dinersclub' => 'Dinners Club',
                        ),
                        'default' => array('mastercard' => 'MasterCard',
                            'visa' => 'Visa',
                            'discover' => 'Discover',
                            'amex' => 'AMEX'),
                    )
                );
            }

            function get_card_type($number) {
                $number = preg_replace('/[^\d]/', '', $number);

                if (preg_match('/^3[47][0-9]{13}$/', $number)) {
                    $card = 'amex';
                } elseif (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/', $number)) {
                    $card = 'dinersclub';
                } elseif (preg_match('/^6(?:011|5[0-9][0-9])[0-9]{12}$/', $number)) {
                    $card = 'discover';
                } elseif (preg_match('/^(?:2131|1800|35\d{3})\d{11}$/', $number)) {
                    $card = 'jcb';
                } elseif (preg_match('/^5[1-5][0-9]{14}$/', $number)) {
                    $card = 'mastercard';
                } elseif (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/', $number)) {
                    $card = 'visa';
                } else {
                    $card = 'unknown card';
                }

                return $card;
            }

            function get_client_ip() {
                $ipaddress = '';
                if (getenv('HTTP_CLIENT_IP'))
                    $ipaddress = getenv('HTTP_CLIENT_IP');
                else if (getenv('HTTP_X_FORWARDED_FOR'))
                    $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
                else if (getenv('HTTP_X_FORWARDED'))
                    $ipaddress = getenv('HTTP_X_FORWARDED');
                else if (getenv('HTTP_FORWARDED_FOR'))
                    $ipaddress = getenv('HTTP_FORWARDED_FOR');
                else if (getenv('HTTP_FORWARDED'))
                    $ipaddress = getenv('HTTP_FORWARDED');
                else if (getenv('REMOTE_ADDR'))
                    $ipaddress = getenv('REMOTE_ADDR');
                else
                    $ipaddress = '0.0.0.0';
                return $ipaddress;
            }

            public function process_payment($order_id) {

                global $error;
                global $woocommerce;
                include(plugin_dir_path(__FILE__) . "init.php");
                $stripe_key = base64_decode($this->stripe_api_key);
                $user_id = get_current_user_id();
                if (!empty($stripe_key)) {
                    \Stripe\Stripe::setApiKey($stripe_key);
                } else {
                    wc_add_notice('Please Do correct setup of Stripe Payment Gateway', $notice_type = 'error');
                    return array(
                        'result' => 'success',
                        'redirect' => wc_get_checkout_url(),
                    );
                    die;
                }
                $wc_order = new WC_Order($order_id);

                $wc_currency = get_option('woocommerce_currency');

                if (in_array($wc_currency, $this->stripe_zerocurrency)) {
                    $amount = $wc_order->order_total * 1;
                } else {
                    $amount = $wc_order->order_total * 100;
                }

                $cardtype = $this->get_card_type(sanitize_text_field(str_replace(' ', '', $_POST['ei_stripe-card-number'])));

                if (!in_array($cardtype, $this->stripe_cardtypes)) {
                    wc_add_notice('Merchant do not accept/support payments using ' . ucwords($cardtype) . ' card', $notice_type = 'error');
                    return array(
                        'result' => 'success',
                        'redirect' => wc_get_checkout_url(),
                    );
                    die;
                }
                try {
                    $card_num = sanitize_text_field(str_replace(' ', '', $_POST['ei_stripe-card-number']));
                    $exp_date = explode("/", sanitize_text_field($_POST['ei_stripe-card-expiry']));
                    $exp_month = str_replace(' ', '', $exp_date[0]);
                    $exp_year = str_replace(' ', '', $exp_date[1]);
                    $cvc = sanitize_text_field($_POST['ei_stripe-card-cvc']);
                    if (strlen($exp_year) == 2) {
                        $exp_year += 2000;
                    }
                    $paymentmethod = \Stripe\PaymentMethod::create([
                        'type' => 'card',
                        'card' => [
                            'number' => $card_num,
                            'exp_month' => $exp_month,
                            'exp_year' => $exp_year,
                            'cvc' => $cvc,
                        ],
                    ]);

                    $intent = null;
                    try {
                        if (isset($paymentmethod->id)) {
                            # Create the PaymentIntent
                            $intent = \Stripe\PaymentIntent::create([
                                'payment_method' => $paymentmethod->id,
                                'amount' => $amount,
                                'currency' => $wc_currency,
                                "statement_descriptor" => NAME,
                                'confirmation_method' => 'manual',
                                'confirm' => true,
                                'metadata' => ['order_id' => $wc_order->get_order_number()],
                            ]);
                        }

                        if (isset($intent->id)) {
                            $intent = \Stripe\PaymentIntent::retrieve($intent->id);
                            $intent->confirm(['payment_method' => 'pm_card_visa']);
                            $SetupIntent = \Stripe\SetupIntent::create([
                                'payment_method_types' => ['card']['request_three_d_secure'],
                            ]);
                            $setup_intent = \Stripe\SetupIntent::retrieve(
                                $SetupIntent->id
                            );
                            $setup_intent->confirm([
                                'payment_method' => 'pm_card_visa',
                            ]);
                        }

                            // generatePaymentResponse($intent);
                    } catch (Exception $e) {
                        # Display error on client
                        $body = $e->getJsonBody();
                        $error = $body['error']['message'];
                        $wc_order->add_order_note(__('Stripe payment failed due to.' . $error, 'woocommerce'));
                        wc_add_notice($error, $notice_type = 'error');
                    }

                    $charge =  $intent->charges->data[0];
                    if ($charge->paid == true) {
                        $epoch = $charge->created;
                        $dt = new DateTime("@$epoch");
                        $timestamp = $dt->format('Y-m-d H:i:s e');
                        $chargeid = $charge->id;
                        $wc_order->add_order_note(__('Stripe payment completed at-' . $timestamp . '-with Charge ID=' . $chargeid, 'woocommerce'));
                        $wc_order->payment_complete($chargeid);
                        WC()->cart->empty_cart();
                        if ('yes' == $this->stripe_meta_cartspan) {
                            $stripe_metas_for_cartspan = array(
                                'cc_type' => $charge->source->brand,
                                'cc_last4' => $charge->source->last4,
                                'cc_trans_id' => $charge->id,
                            );
                            add_post_meta($order_id, '_stripe_metas_for_cartspan', $stripe_metas_for_cartspan);
                        }
                        return array(
                            'result' => 'success',
                            'redirect' => $this->get_return_url($wc_order),
                        );
                    } else {
                        $wc_order->add_order_note(__('Stripe payment failed.' . $error, 'woocommerce'));
                        wc_add_notice($error, $notice_type = 'error');
                    }
                } catch (Exception $e) {
                    $body = $e->getJsonBody();
                    $error = $body['error']['message'];
                    $wc_order->add_order_note(__('Stripe payment failed due to.' . $error, 'woocommerce'));
                    wc_add_notice($error, $notice_type = 'error');
                }
            }

            public function process_refund($order_id, $amount = NULL, $reason = '') {
                include(plugin_dir_path(__FILE__) . "init.php");

                if ($amount > 0) {
                    $stripe_key = base64_decode($this->stripe_api_key);
                    if (!empty($stripe_key)) {
                        \Stripe\Stripe::setApiKey($stripe_key);
                    }

                    $CHARGE_ID = get_post_meta($order_id, '_transaction_id', true);

                    $charge = \Stripe\Charge::retrieve($CHARGE_ID);
                    $product_id = wc_get_order_item_meta($order_id, '_product_id', true);
                    $wc_currency = get_option('woocommerce_currency');

                    if (in_array($wc_currency, $this->stripe_zerocurrency)) {
                        $amount = $amount * 1;
                    } else {
                        $amount = $amount * 100;
                    }
                    $refund = $charge->refunds->create(
                            array(
                                'amount' => $amount,
                                'metadata' => array('Order #' => $order_id,
                                    'Refund reason' => $reason
                                ),
                            )
                    );
                    if ($refund) {
                        $repoch = $refund->created;
                        $rdt = new DateTime("@$repoch");
                        $rtimestamp = $rdt->format('Y-m-d H:i:s e');
                        $refundid = $refund->id;

                        $wc_order = new WC_Order($order_id);
                        $wc_order->add_order_note(__('Stripe Refund completed at. ' . $rtimestamp . ' with Refund ID = ' . $refundid, 'woocommerce'));
                        //var_dump($refundid); exit;
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    $stripe_key = base64_decode($this->stripe_api_key);
                    if (!empty($stripe_key)) {
                        \Stripe\Stripe::setApiKey($stripe_key);
                    }
                    $CHARGE_ID = get_post_meta($order_id, '_transaction_id', true);
                    $order_total = get_post_meta($order_id, '_order_total', true);
                    $charge = \Stripe\Charge::retrieve($CHARGE_ID);
                    $product_id = wc_get_order_item_meta($order_id, '_product_id', true);
                    $wc_currency = get_option('woocommerce_currency');

                    if (in_array($wc_currency, $this->stripe_zerocurrency)) {
                        $amount = $order_total * 1;
                    } else {
                        $amount = $order_total * 100;
                    }
                    $refund = $charge->refunds->create(
                            array(
                                'amount' => $amount,
                                'metadata' => array('Order #' => $order_id,
                                    'Refund reason' => $reason
                                ),
                            )
                    );
                    if ($refund) {
                        $repoch = $refund->created;
                        $rdt = new DateTime("@$repoch");
                        $rtimestamp = $rdt->format('Y-m-d H:i:s e');
                        $refundid = $refund->id;

                        $wc_order = new WC_Order($order_id);
                        $wc_order->add_order_note(__('Stripe Refund completed at. ' . $rtimestamp . ' with Refund ID = ' . $refundid, 'woocommerce'));
                        //var_dump($refundid); exit;
                        return true;
                    } else {
                        return false;
                    }
                }
            }

            public function stripe_get_active_card_logo_url($type) {

                $image_type = strtolower($type);
                return WC_HTTPS::force_https_url(plugins_url('images/' . $image_type . '.png', __FILE__));
            }

            /* Start of credit card form */

            public function payment_fields() {
                echo apply_filters('wc_stripe_description', wpautop(wp_kses_post(wptexturize(trim($this->method_description)))));
                $this->form();
            }

            public function field_name($name) {
                return $this->supports('tokenization') ? '' : ' name="' . esc_attr($this->id . '-' . $name) . '" ';
            }

            public function form() {
                wp_enqueue_script('wc-credit-card-form');
                $fields = array();
                $cvc_field = '<p class="form-row form-row-last">
	<label for="ei_stripe-card-cvc">' . __('Card Code', 'woocommerce') . ' <span class="required">*</span></label>
	<input id="ei_stripe-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="' . esc_attr__('CVC', 'woocommerce') . '" ' . $this->field_name('card-cvc') . ' />
</p>';
                $default_fields = array(
                    'card-number-field' => '<p class="form-row form-row-wide">
	<label for="ei_stripe-card-number">' . __('Card Number', 'woocommerce') . ' <span class="required">*</span></label>
	<input id="ei_stripe-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name('card-number') . ' />
</p>',
                    'card-expiry-field' => '<p class="form-row form-row-first">
<label for="ei_stripe-card-expiry">' . __('Expiry (MM/YY)', 'woocommerce') . ' <span class="required">*</span></label>
<input id="ei_stripe-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="' . esc_attr__('MM / YY', 'woocommerce') . '" ' . $this->field_name('card-expiry') . ' />
</p>',
                    'card-cvc-field' => $cvc_field
                );

                $fields = wp_parse_args($fields, apply_filters('woocommerce_credit_card_form_fields', $default_fields, $this->id));
                ?>

                <fieldset id="wc-<?php echo esc_attr($this->id); ?>-cc-form" class='wc-credit-card-form wc-payment-form'>
                    <?php do_action('woocommerce_credit_card_form_start', $this->id); ?>
                    <?php
                    foreach ($fields as $field) {
                        echo $field;
                    }
                    ?>
                    <?php do_action('woocommerce_credit_card_form_end', $this->id); ?>
                    <div class="clear"></div>
                </fieldset>
                <?php
            }

            public function sslerror() {
                $html = '<div class="error">';
                $html .= '<p>';
                $html .= __('Please use <b>ssl</b> and activate Force secure checkout to use this plugin');
                $html .= '</p>';
                $html .= '</div>';
                echo $html;
            }

        }

    } else {
        if (!class_exists('WC_Payment_Gateway')) {
            add_action('admin_notices', 'activate_error');
        }
        deactivate_plugins(plugin_basename(__FILE__));
        return FALSE;
    }
}

function activate_error() {
    $html = '<div class="error">';
    $html .= '<p>';
    $html .= __('Please activate <b>Woocommerce</b> Plugin to use this plugin');
    $html .= '</p>';
    $html .= '</div>';
    echo $html;
}

function add_custom_js() {
    if (is_checkout()) {
        // apply only if it is checkout page...
        wp_enqueue_script('jquery-cc-stripe', plugin_dir_url(__FILE__) . 'js/cc.custom_stripe.js', array('jquery'), '1.0', True);
    }
    wp_enqueue_style('stripe-css', plugin_dir_url(__FILE__) . 'css/style.css');
}

add_action('wp_enqueue_scripts', 'add_custom_js');

$woo_stripe_settings = get_option('woocommerce_ei_stripe_settings');
if (!empty($woo_stripe_settings)) {
    if ($woo_stripe_settings['enabled'] == 'yes' && $woo_stripe_settings['mode'] == 'live' && !is_ssl()) {
        add_action('admin_notices', array('WC_Stripe_Gateway_EI', 'sslerror'));
    } else {
        remove_action('admin_notices', array('WC_Stripe_Gateway_EI', 'sslerror'));
    }
}

function wpdocs_enqueue_custom_admin_script_stripe() {
    wp_enqueue_script('jquery-cc-stripe', plugin_dir_url(__FILE__) . 'js/cc.custome_stripe_settings.js', array('jquery'), '1.0', True);
}

add_action('admin_enqueue_scripts', 'wpdocs_enqueue_custom_admin_script_stripe');



