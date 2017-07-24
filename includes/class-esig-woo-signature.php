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

        $result = array(
            "billing_address_1" => $order->billing_address_1,
            "billing_address_2" => $order->billing_address_2,
            "billing_city" => $order->billing_city,
            "billing_company" => $order->billing_company,
            "billing_country" => $order->billing_country,
            "billing_email" => $order->billing_email,
            "billing_first_name" => $order->billing_first_name,
            "billing_last_name" => $order->billing_last_name,
            "billing_phone" => $order->billing_phone,
            "billing_postcode" => $order->billing_postcode,
            "billing_state" => $order->billing_state,
            "cart_discount" => $order->cart_discount,
            "cart_discount_tax" => $order->cart_discount_tax,
            "customer_ip_address" => $order->customer_ip_address,
            "customer_message" => $order->customer_message,
            "customer_note" => $order->customer_note,
            //"customer_user"=>$order->customer_user,
            "customer_user_agent" => $order->customer_user_agent,
            "display_cart_ex_tax" => $order->display_cart_ex_tax,
            "display_totals_ex_tax" => $order->display_totals_ex_tax,
            "order_id" => $order->id,
            "order_currency" => $order->order_currency,
            "order_date" => $order->order_date,
            "order_discount" => $order->order_discount,
            "order_key" => $order->order_key,
            "order_shipping" => $order->order_shipping,
            "order_shipping_tax" => $order->order_shipping_tax,
            "order_tax" => $order->order_tax,
            "order_total" => $order->order_total,
            "order_type" => $order->order_type,
            "payment_method" => $order->payment_method,
            "payment_method_title" => $order->payment_method_title,
            "shipping_address_1" => $order->shipping_address_1,
            "shipping_address_2" => $order->shipping_address_2,
            "shipping_city" => $order->shipping_city,
            "shipping_company" => $order->shipping_company,
            "shipping_country" => $order->shipping_country,
            "shipping_first_name" => $order->shipping_first_name,
            "shipping_last_name" => $order->shipping_last_name,
            "shipping_method_title" => $order->shipping_method_title,
            "shipping_postcode" => $order->shipping_postcode,
            "shipping_state" => $order->shipping_state,
        );
        // customer wordpress user details 
        if ($order->customer_user) {
            $wpUser = get_userdata($order->customer_user);
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
                $result['product_' . $itemData['product_id'] . '_quantity'] = $order->get_item_meta($itemId, '_qty', true);
            }
        }


        return $result;
    }

}
