<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class esig_woo_logic {

    function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->esig_sad = new esig_woocommerce_sad();
    }

    public static function is_product_logic($product_id, $is_true) {
        $logic = get_post_meta($product_id, '_esign_woo_sign_logic', true);
        if ($logic === $is_true) {
            return true;
        } else {
            return false;
        }
    }

    public static function get_global_logic() {
        return get_option('esign_woo_logic');
    }

    public static function is_signature_required($product_id) {
        $product_agreement = get_post_meta($product_id, '_esig_woo_meta_product_agreement', true);

        if ($product_agreement) {
            return true;
        }
        return false;
    }

    public static function get_agreement_id($product_id) {
        $sad_page_id = get_post_meta($product_id, '_esig_woo_meta_sad_page', true);
        $sad = new esig_sad_document();
        return $sad->get_sad_id($sad_page_id);
    }

    public static function get_sad_page_id($agreement_id) {
        $sad = new esig_sad_document();
        return $sad->get_sad_page_id($agreement_id);
    }

    public static function get_agreement_logic($product_id) {
        return get_post_meta($product_id, '_esign_woo_sign_logic', true);
    }

    public static function make_agreement_signed($cart_item_key, $document_id) {
        WC()->cart->cart_contents[$cart_item_key][ESIG_WOOCOMMERCE_Admin::PRODUCT_AGREEMENT]['signed'] = 'yes';
        WC()->cart->cart_contents[$cart_item_key][ESIG_WOOCOMMERCE_Admin::PRODUCT_AGREEMENT]['document_id'] = $document_id;
        WC()->cart->set_session();
    }

    public static function make_global_agreement_signed($document_id) {
        $agreements = self::get_global_agreement();
        $agreements['signed'] = 'yes';
        $agreements['document_id'] = $document_id;
        WC()->session->set(ESIG_WOOCOMMERCE_Admin::GLOBAL_AGREEMENT, $agreements);
    }

    public static function is_global_agreement_enabled() {
        $esig_woo_agreement = get_option('esign_woo_agreement_setting');
        if ($esig_woo_agreement == "yes") {
            return true;
        }
        return false;
    }

    public static function get_global_agreement_id() {
        $esign_woo_sad_page = get_option('esign_woo_sad_page');
        $sad = new esig_sad_document();
        return $sad->get_sad_id($esign_woo_sad_page);
    }

    public static function set_global_agreement() {
        $global_agreement = WC()->session->get(ESIG_WOOCOMMERCE_Admin::GLOBAL_AGREEMENT);
        if (!isset($global_agreement)) {
            $array = array(
                'agreement_id' => self::get_global_agreement_id(),
                'agreement_logic' => self::get_global_logic(),
                'signed' => 'no',
            );
            WC()->session->set(ESIG_WOOCOMMERCE_Admin::GLOBAL_AGREEMENT, $array);
        } else {
            $global_id = self::get_global_agreement_id();
            if (isset($global_agreement) && $global_agreement['agreement_id'] != $global_id) {
                $array = array(
                    'agreement_id' => $global_id,
                    'agreement_logic' => self::get_global_logic(),
                    'signed' => 'no',
                );
                WC()->session->set(ESIG_WOOCOMMERCE_Admin::GLOBAL_AGREEMENT, $array);
            }
        }
    }

    public static function get_global_agreement() {
        $global_agreement = WC()->session->get(ESIG_WOOCOMMERCE_Admin::GLOBAL_AGREEMENT);
        if ($global_agreement) {
            return $global_agreement;
        }
        return false;
    }

    public static function get_global_doc_id_from_session($is_true) {
        $global_settings = self::get_global_agreement();
        if (isset($global_settings)) {
            if ($global_settings['signed'] == 'no' && $global_settings['agreement_logic'] === $is_true) {
                return $global_settings['agreement_id'];
            }
        }
        return false;
    }

    public static function save_temp_order_id($order_id) {
        WC()->session->set(ESIG_WOOCOMMERCE_Admin::TEMP_ORDER_ID, $order_id);
    }

    public static function get_temp_order_id() {
        $order_id = WC()->session->get(ESIG_WOOCOMMERCE_Admin::TEMP_ORDER_ID);
        if ($order_id) {
            return $order_id;
        }
        return false;
    }

    public static function save_document_meta($document_id, $order_id) {
        WP_E_Sig()->meta->add($document_id, 'esig-order_id', $order_id);
    }

    public static function save_after_checkout_doc_list($order_id, $doc_list) {
        update_post_meta($order_id, '_esig_after_checkout_doc_list', json_encode($doc_list));
    }

    public static function get_after_checkout_doc_list($order_id) {
        $doc_list = json_decode(get_post_meta($order_id, '_esig_after_checkout_doc_list', true), true);
        return $doc_list;
    }

    public static function update_after_checkout_doc_list($order_id, $sad_doc_id, $document_id) {
        $doc_list = self::get_after_checkout_doc_list($order_id);

        $doc_list[$sad_doc_id] = 'yes';
        self::save_after_checkout_doc_list($order_id, $doc_list);
        self::save_document_meta($document_id, $order_id);
    }

    public static function is_after_checkout_enable($order_id) {
        if (self::get_after_checkout_doc_list($order_id)) {
            return true;
        } else {
            return false;
        }
    }

    public static function save_after_checkout_order_id($order_id) {
        esig_setcookie('esig-aftercheckout-order-id', $order_id, 60 * 60 * 1);
    }

    public static function get_after_checkout_order_id() {
        if (ESIG_COOKIE('esig-aftercheckout-order-id')) {
            return ESIG_COOKIE('esig-aftercheckout-order-id');
        }
        return false;
    }

    public static function remove_after_checkout_order_id() {
        esig_unsetcookie('esig-aftercheckout-order-id', COOKIEPATH);
    }


    public static function orderDetails($orderId) {

        $order = new WC_Order($orderId);

        $ordermeta = get_post_meta($orderId);
        $unset = [];
        foreach($ordermeta as $key=>$item){

            if(is_array($item) && count($item)==1){
                $item = $item[0];
            }

            $new_key = ltrim($key, '_');

            if(strpos($key, '_')===0){
               $unset[] = $key;
                $ordermeta[$new_key] = $item;

            }

        }

        foreach($unset as $key){
            unset($ordermeta[$key]);
        }
        
        $ordermeta = (object) $ordermeta;
        $result = array(
            "billing_address_1" => $ordermeta->billing_address_1,
            "billing_address_2" => $ordermeta->billing_address_2,
            "billing_city" => $ordermeta->billing_city,
            "billing_company" => $ordermeta->billing_company,
            "billing_country" => $ordermeta->billing_country,
            "billing_email" => $ordermeta->billing_email,
            "billing_first_name" => $ordermeta->billing_first_name,
            "billing_last_name" => $ordermeta->billing_last_name,
            "billing_phone" => $ordermeta->billing_phone,
            "billing_postcode" => $ordermeta->billing_postcode,
            "billing_state" => $ordermeta->billing_state,
            "cart_discount" => $ordermeta->cart_discount,
            "cart_discount_tax" => $ordermeta->cart_discount_tax,
            "customer_ip_address" => $ordermeta->customer_ip_address,
            "customer_message" => ESIG_WOOCOMMERCE::is_wc3()?$order->get_customer_note():$order->customer_message,
            "customer_note" => ESIG_WOOCOMMERCE::is_wc3()?$order->get_customer_note():$order->customer_note ,
            "customer_user_agent" => $ordermeta->customer_user_agent,
            "display_cart_ex_tax" => ('excl' === get_option( 'woocommerce_tax_display_cart' )),
            "display_totals_ex_tax" => ('excl' === get_option( 'woocommerce_tax_display_cart')),
            "order_id" => $orderId,
            "order_currency" => $ordermeta->order_currency,
            "order_date" => ESIG_WOOCOMMERCE::is_wc3()?$order->get_date_created():$order->order_date,
            "order_discount" => ESIG_WOOCOMMERCE::is_wc3()?$order->get_discount_total():$order->order_discount,
            "order_key" => $ordermeta->order_key,
            "order_shipping" => $ordermeta->order_shipping,
            "order_shipping_tax" => $ordermeta->order_shipping_tax,
            "order_tax" => $ordermeta->order_tax,
            "order_total" => $ordermeta->order_total,
            "order_type" => ESIG_WOOCOMMERCE::is_wc3()?$order->get_type():$order->order_type,
            "payment_method" => $ordermeta->payment_method,
            "payment_method_title" => $ordermeta->payment_method_title,
            "shipping_address_1" => $ordermeta->shipping_address_1,
            "shipping_address_2" => $ordermeta->shipping_address_2,
            "shipping_city" => $ordermeta->shipping_city,
            "shipping_company" => $ordermeta->shipping_company,
            "shipping_country" => $ordermeta->shipping_country,
            "shipping_first_name" => $ordermeta->shipping_first_name,
            "shipping_last_name" => $ordermeta->shipping_last_name,
            "shipping_method_title" => ESIG_WOOCOMMERCE::is_wc3()?$order->get_shipping_method(): $order->shipping_method_title,
            "shipping_postcode" => $ordermeta->shipping_postcode,
            "shipping_state" => $ordermeta->shipping_state,
        );
        // customer wordpress user details 
        if ($ordermeta->customer_user) {
            $wpUser = get_userdata($ordermeta->customer_user);
            $result['customer_wp_username'] = $wpUser->user_login;
            $result['customer_wp_user_displayname'] = $wpUser->display_name;
            $result['customer_wp_user_email'] = $wpUser->user_email;
            $result['customer_wp_user_nicename'] = $wpUser->user_nicename;
            $result['customer_wp_user_firstname'] = $wpUser->first_name;
            $result['customer_wp_user_lastname'] = $wpUser->last_name;
        }
        // order product details . 
        $items = $order->get_items();
        if ($items) {
            foreach ($items as $itemId => $itemData) {
                $result['product_' . $itemData['product_id'] . '_name'] = $itemData['name'];
                $result['product_' . $itemData['product_id'] . '_quantity'] = ESIG_WOOCOMMERCE::is_wc3()?wc_get_order_item_meta($itemId, '_qty', true):$order->get_item_meta($itemId, '_qty', true);
            }
        }


        return $result;
    }

}
