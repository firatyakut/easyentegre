<?php

class ModelEntegrasyonOrder extends Model
{

    public function addOrder($order, $code)
    {


        try {

            /*   $this->db->query("INSERT INTO " . DB_PREFIX . "es_order
                    SET code='" . $code . "',
                    market_order_id='" . $order['order_id'] . "',
                    order_status='" .$this->getOrderStatus($order['order_status_id'], $code). "',
                        first_name='" . $this->db->escape($order['firstname']) . "',
                        last_name='" . $this->db->escape($order['lastname']) . "',
                        shipping_address='" . $this->db->escape($order['address']['address_1']) . "',
                        billing_address='" . $this->db->escape($order['address']['address_1']) . "',
                        phone='" . $order['telephone'] . "',
                        total='" . $order['total'] . "',
                        email='" . $this->db->escape($order['email']) . "',
                        city='" . $this->db->escape($order['address']['city']) . "',
                        town='" . $this->db->escape($order['address']['town']) . "',
                        shipping_info= '".serialize($order['shipping_info'])."',
                        payment_info= '".serialize($order['payment_info'])."',
                        date_added='" . $order['order_date'] . "'

                    "); */

            $id = isset($order['id']) ? $order['id'] : '';

            $this->db->query("INSERT INTO " . DB_PREFIX . "es_order
                SET code='" . $code . "', 
                market_order_id='" . $order['order_id'] . "',
                order_status='" . $this->getOrderStatus($order['order_status_id'], $code) . "',
                	first_name='" . $this->db->escape($order['payment_firstname']) . "',
                	last_name='" . $this->db->escape($order['payment_lastname']) . "',
                	shipping_address='" . $this->db->escape($order['payment_address_1']) . "',
                	billing_address='" . $this->db->escape($order['payment_address_1']) . "',
                	phone='" . $order['telephone'] . "',
                	total='" . $order['total'] . "',
                	invoice_link='" . $order['invoice_link'] . "',
                	id='" . $id . "',
                	email='" . $this->db->escape($order['email']) . "',
                	city='" . $this->db->escape($order['payment_city']) . "',
                	town='" . $this->db->escape($order['payment_town']) . "',
                	cargo_number='" . $order['shipping_info']['campaign_number'] . "',
                	cargo_name='" . $order['shipping_info']['shipment_method'] . "',
                	shipping_info= '" . serialize($order['shipping_info']) . "',
                	payment_info= '" . serialize($order['payment_info']) . "',
                	date_added='" . $order['order_date'] . "'
                
                ");


        } catch (Exception $exception) {

            echo $exception->getMessage();

        }


        $last_insert_id = $this->db->getLastId();


        foreach ($order['products'] as $product) {
            $option_value = array();
            $option_value_string = '';
            if ($product['option']) {
                foreach ($product['option'] as $option) {

                    $option_value [] = $option['value'];

                }
                $option_value_string = "-" . implode(",", $option_value);

            }

            try {
                $this->db->query("INSERT INTO " . DB_PREFIX . "es_ordered_product SET
                    order_id='" . $last_insert_id . "',
                    `name`    ='" . $this->db->escape($product['name'] . $option_value_string) . "',
                    model   ='" . $this->db->escape($product['model']) . "',
                    barcode   ='" . $this->db->escape($product['barcode']) . "',
                    market_product_id   ='" . $this->db->escape($product['market_product_id']) . "',
                    item_id   ='" . $product['item_id'] . "',
                    kdv   ='" . $product['totaltax'] . "',
                    kdv_oran   ='" . $product['tax_range'] . "',
                    discount   ='" . $product['discount'] . "',
                    quantity   ='" . $product['quantity'] . "',
                    price   ='" . $product['price'] . "',
                    list_price   ='" . $product['list_price'] . "'
                 ");

            } catch (Exception $exception) {

                echo $exception->getMessage();

            }

        }

        if ($last_insert_id && $this->config->get($code . '_setting_oc_order')) {


            $this->addToOrder($order, $code);
        }


    }



    public function sendSms($order_data)
    {

        

        $message = array();

        foreach ($order_data['markets'] as $market => $adet) {

            if ($adet) {

                $market_info = $this->entegrasyon->getMarketPlace($market, HTTPS_SERVER);
                $message[] = $market_info['name'] . ': ' . $adet . ' Sipariş Aldınız. sms no:'.rand(1,9999);
            }
        }

        $text = '';
        if ($message) {
            $text .= implode(',', $message);
        }

        $this->load->model('setting/setting');
        $netgsm_ayarlari = $this->model_setting_setting->getSetting('netgsm');


        if ($netgsm_ayarlari['netgsm_status'] == 1) {
            $netgsmsms = new Netgsmsms($netgsm_ayarlari['netgsm_user']  , $netgsm_ayarlari['netgsm_pass'] , $netgsm_ayarlari['netgsm_input_smstitle'] );


            if ($message) {
                $numbers = explode(',', $this->config->get('easy_setting_sms_numbers'));
                foreach ($numbers as $number) {

                    $smsgonder = $netgsmsms->sendSMS($number, $text);

                    if($smsgonder){
                        $this->entegrasyon->log('sms gönderildi', 'Sms başarıyla gönderildi', false);

                    }else {
                        $this->entegrasyon->log('smsm gönderilemedi', $smsgonder, false);
                    }


                }

            }


        }


    }

    public function send($orderData)
    {


        $this->entegrasyon->sendNotification($orderData);

    }


    public function checkOcOrder($order_id)
    {

    }


    public function addToOrder($data, $code)
    {


        $order_status_id = $this->config->get($code . '_setting_order_status_id') ? $this->config->get($code . '_setting_order_status_id') : $this->config->get('config_order_status_id');


        // print_r($data);return;


        $this->db->query("INSERT INTO `" . DB_PREFIX . "order` SET invoice_prefix = '" . $this->db->escape($data['invoice_prefix']) . "',order_status_id='" . $order_status_id . "', store_id = '" . (int)$data['store_id'] . "', store_name = '" . $this->db->escape($data['store_name']) . "', store_url = '" . $this->db->escape($data['store_url']) . "', customer_group_id = '" . (int)$data['customer_group_id'] . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', fax = '" . $this->db->escape($data['fax']) . "', custom_field = '" . $this->db->escape(isset($data['custom_field']) ? json_encode($data['custom_field']) : '') . "', payment_firstname = '" . $this->db->escape($data['payment_firstname']) . "', payment_lastname = '" . $this->db->escape($data['payment_lastname']) . "', payment_company = '" . $this->db->escape($data['payment_company']) . "', payment_address_1 = '" . $this->db->escape($data['payment_address_1']) . "', payment_address_2 = '" . $this->db->escape($data['payment_address_2']) . "', payment_tax_id = '" . $data['payment_tax_id'] . "',payment_company_id = '" . $data['payment_company_id'] . "', payment_city = '" . $this->db->escape($data['payment_town']) . "', payment_postcode = '" . $this->db->escape($data['payment_postcode']) . "', payment_country = '" . $this->db->escape($data['payment_country']) . "', payment_country_id = '" . (int)$data['payment_country_id'] . "', payment_zone = '" . $this->db->escape($data['payment_zone']) . "', payment_zone_id = '" . (int)$data['payment_zone_id'] . "', payment_address_format = '" . $this->db->escape($data['payment_address_format']) . "', payment_custom_field = '" . $this->db->escape(isset($data['payment_custom_field']) ? json_encode($data['payment_custom_field']) : '') . "', payment_method = '" . $this->db->escape($data['payment_method']) . "', payment_code = '" . $this->db->escape($data['payment_code']) . "', shipping_firstname = '" . $this->db->escape($data['shipping_firstname']) . "', shipping_lastname = '" . $this->db->escape($data['shipping_lastname']) . "', shipping_company = '" . $this->db->escape($data['shipping_company']) . "', shipping_address_1 = '" . $this->db->escape($data['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape($data['shipping_address_2']) . "', shipping_city = '" . $this->db->escape($data['shipping_city']) . "', shipping_postcode = '" . $this->db->escape($data['shipping_postcode']) . "', shipping_country = '" . $this->db->escape($data['shipping_country']) . "', shipping_country_id = '" . (int)$data['shipping_country_id'] . "', shipping_zone = '" . $this->db->escape($data['shipping_zone']) . "', shipping_zone_id = '" . (int)$data['shipping_zone_id'] . "', shipping_address_format = '" . $this->db->escape($data['shipping_address_format']) . "', shipping_custom_field = '" . $this->db->escape(isset($data['shipping_custom_field']) ? json_encode($data['shipping_custom_field']) : '') . "', shipping_method = '" . $this->db->escape($data['shipping_method']) . "', shipping_code = '" . $this->db->escape($data['shipping_code']) . "', comment = '" . $this->db->escape($data['comment']) . "', total = '" . (float)$data['total'] . "', affiliate_id = '" . (int)$data['affiliate_id'] . "', commission = '" . (float)$data['commission'] . "', marketing_id = '" . (int)$data['marketing_id'] . "', tracking = '" . $this->db->escape($data['tracking']) . "', language_id = '" . (int)$data['language_id'] . "', currency_id = '" . (int)$data['currency_id'] . "', currency_code = '" . $this->db->escape($data['currency_code']) . "', currency_value = '" . (float)$data['currency_value'] . "', ip = '" . $this->db->escape($data['ip']) . "', forwarded_ip = '" . $this->db->escape($data['forwarded_ip']) . "', user_agent = '" . $this->db->escape($data['user_agent']) . "', accept_language = '" . $this->db->escape($data['accept_language']) . "', date_added = NOW(), date_modified = NOW()");

        $order_id = $this->db->getLastId();

        // Products
        if (isset($data['products'])) {
            foreach ($data['products'] as $product) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "order_product SET order_id = '" . (int)$order_id . "', product_id = '" . (int)$product['product_id'] . "', name = '" . $this->db->escape($product['name']) . "', model = '" . $this->db->escape($product['model']) . "', quantity = '" . (int)$product['quantity'] . "', price = '" . (float)$product['price'] . "', total = '" . (float)$product['total'] . "', tax = '" . (float)$product['tax'] . "', reward = '" . (int)$product['reward'] . "'");
                $order_product_id = $this->db->getLastId();

                foreach ($product['option'] as $option) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "order_option SET order_id = '" . (int)$order_id . "', order_product_id = '" . (int)$order_product_id . "', product_option_id = '" . (int)$option['product_option_id'] . "', product_option_value_id = '" . (int)$option['product_option_value_id'] . "', name = '" . $this->db->escape($option['name']) . "', `value` = '" . $this->db->escape($option['value']) . "'");
                }

            }
        }


        // Totals
        if (isset($data['totals'])) {
            foreach ($data['totals'] as $total) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . (int)$order_id . "', code = '" . $this->db->escape($total['code']) . "', title = '" . $this->db->escape($total['title']) . "', `value` = '" . (float)$total['value'] . "', sort_order = '" . (int)$total['sort_order'] . "'");
            }
        }

        return $order_id;
    }

    public function getOrder($order_id, $order_email = "", $id = "")
    {


        if ($id) {
            //$order_email =  str_replace("@hepsifatura.com","",$order_email);
            // $order_email = explode("_", $order_email);
            $query2 = $this->db->query("select * from " . DB_PREFIX . "es_order where id like '" . $id . "'");
            return $query2->num_rows;

        }

        $query = $this->db->query("select * from " . DB_PREFIX . "es_order where market_order_id like '" . $order_id . "%'");

        return $query->rows;

    }

    public function getOrderStatus($order_status, $code)
    {

        $query = $this->db->query("select * from " . DB_PREFIX . "es_order_status where $code like '%" . $order_status . "%' ");

        if ($query->num_rows) {
            return $query->row['order_status_id'];

        } else {

            return $order_status;

        }
    }

}


