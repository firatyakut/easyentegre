<?php

class ModelEntegrasyonOrderPz extends Model
{



    public function check_order_status($order)
    {

        //$order_info=$this->getFromMarket('p',$order['market_order_id']);


        $order_detail = $this->getFromMarket($order['market_order_id']);


        if ($order_detail['result']['content']) {


            $order_status = isset($order_detail['result']['content'][0]['shipmentPackageStatus']) ? $order_detail['result']['content'][0]['shipmentPackageStatus'] : '';
            $tracking_url = isset($order_detail['result']['content'][0]['cargoTrackingLink']) ? $order_detail['result']['content'][0]['cargoTrackingLink'] : '';
            $cargo_name = isset($order_detail['result']['content'][0]['cargoProviderName']) ? $order_detail['result']['content'][0]['cargoProviderName'] : '';
            $cargo_number = isset($order_detail['result']['content'][0]['cargoTrackingNumber']) ? $order_detail['result']['content'][0]['cargoTrackingNumber'] : '';
            $shipmentPackageId = isset($order_detail['result']['content'][0]['id']) ? $order_detail['result']['content'][0]['id'] : '';
            $invoiceLink = isset($order_detail['result']['content'][0]['invoiceLink']) ? $order_detail['result']['content'][0]['invoiceLink'] : '';

            return array(
                'status' => $order_status,
                'tracking_url' => $tracking_url,
                'order_id'=>$order['market_order_id'],
                'cargo_name' => $cargo_name,
                'cargo_number' => $cargo_number,
                'shipment_package_id' => $shipmentPackageId,
                'invoice_link' => $invoiceLink
            );


        } else {

            $this->db->query("DELETE FROM `" . DB_PREFIX . "es_order` WHERE `order_id` ='" . $order['order_id'] . "'");

        }


    }


    public function getOrders($debug = false, $mode = 'add')
    {

        ///$debug=true;

        $orderList = array();

        $post_data['request_data'] = array('start_date'=>date('Y-m-d',strtotime("-10 days")),'end_date'=>date('Y-m-d'));

         $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('pz');

        $orders = $this->entegrasyon->clientConnect($post_data, 'get_orders', 'pz', $debug);



         // print_r($orders);return ;

        if (!$orders['status']) {
            return $orders;

        }

        $order_temps = array();
        if ($orders['status'] && isset($orders['result']['data'])) {


                foreach ($orders['result']['data'] as $order) {

                    if ($mode == 'update') {

                        $orderList[] = $order;

                    } else {
                        if (!$this->entegrasyon->checkOrderByMarketPlaceOrderId($order['orderNumber'])) {

                            //$orderList[] = $this->getOrder($order);
                            $order_temps[] = $order;

                        }

                    }

                }







            foreach ($order_temps as $order_temp) {
                $orderList[] = $this->getOrder($order_temp);
            }
            return $orderList;
        }


    }


    public function getProductFromMarketPlace($model)
    {
        $this->load->model('entegrasyon/general');
        $post_data['request_data'] = array('itemcount' => 1, 'page' => 1, 'barcode' => $model, 'approved' => true);
        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('ty');
        return $this->entegrasyon->clientConnect($post_data, 'get_product', 'ty', false);
    }

    private function getCustomer($nameSurname)
    {

        $customer = explode(' ', $nameSurname);

        $firstname='';
        $lastname ='';

        if (count($customer) == 3) {
            $firstname = $customer[0] . ' ' . $customer[1];
            $lastname = $customer[2];

            $altfirstname = $customer[0];
            $altlastname =   $customer[1].' '.$customer[2];

        } else if(count($customer) == 2) {
            $firstname = $customer[0];
            $lastname = $customer[1];
        }else {
            $firstname = $customer[0];
            $lastname = "";
        }

        return array('first_name'=>$firstname,'last_name'=>$lastname);
    }


    public function getOrder($order)

    {



        $customer=$this->getCustomer($order['shipmentAddress']['nameSurname']);

        $this->load->model('entegrasyon/general');
        $custom_data = $order['shipmentAddress']['nameSurname'];
        $first_name = $customer['first_name'];
        $lastname = $customer['last_name'];
        $adres = $order['shipmentAddress']['displayAddressText'];
        $sehir = $order['shipmentAddress']['cityName'];
        $ilce = $order['shipmentAddress']['districtName'];
        $postakodu = '';
        $phone = $order['shipmentAddress']['phoneNumber'];
        $email=$order['shipmentAddress']['customerEmail'];




            $vergidairesi = $order['billingAddress']['taxOffice'];
            $vergiNo = $order['billingAddress']['taxNumber'] ? $order['billingAddress']['taxNumber'] : '11111111111';
            $company = $order['billingAddress']['companyName'];




        $address_data = array(

            'firstname' => $first_name,
            'lastname' => $lastname,
            'tax_office' => $vergidairesi,
            'tax_id' => $vergiNo,
            'company' => '',
            'address_1' => $adres,
            'address_2' => '',
            'city' => $sehir,
            'town' => $ilce,
            'postcode' => $postakodu,
            'country_id' => 215,
            'zone_id' => ''

        );


        $order_data = array(
            'id' => "",
            'firstname' => $first_name,
            'lastname' => $lastname,
            'tckimlikno' => '',
            'customer_group_id' => $this->config->get('config_customer_group_id'),
            'email' => $order['customerEmail'],
            'telephone' => $phone,
            'fax' => '',
            'custom_field' => '',
            'newsletter' => '',
            'password' => '123456',
            'status' => 1,
            'approved' => 1,
            'safe' => 1,
            'address' => $address_data,
            'order_type' => 'ty',
            'plength' => '',
            'poption' => '',


        );


        $order_data['order_id'] = $order['orderNumber'];
        $order_data['invoice_prefix'] = '';
        $order_data['invoice_no'] = 0;
        $order_data['store_id'] = $this->config->get('config_store_id');
        $order_data['store_name'] = $this->config->get('config_name');

        if ($order_data['store_id']) {
            $order_data['store_url'] = $this->config->get('config_url');
        } else {
            if ($this->request->server['HTTPS']) {
                $order_data['store_url'] = HTTP_SERVER;
            } else {
                $order_data['store_url'] = HTTP_SERVER;
            }
        }


        $order_data['comment'] = $this->config->get('n11_order_comment');
        $order_data['payment_company_id'] = $vergidairesi;
        $order_data['payment_tax_id'] = $vergiNo;
        $order_data['language_id'] = $this->config->get('config_language_id');
        $order_data['currency_id'] = 1;
        $order_data['currency_code'] = $this->session->data['currency'];
        $order_data['currency_value'] = 1;//$this->currency->getValue($this->session->data['currency']);
        $order_data['ip'] = $this->request->server['REMOTE_ADDR'];

        $order_data['invoice_link'] = isset($order['invoiceLink']) ? $order['invoiceLink'] : '';

        if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
            $order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
            $order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
        } else {
            $order_data['forwarded_ip'] = '';
        }

        if (isset($this->request->server['HTTP_USER_AGENT'])) {
            $order_data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
        } else {
            $order_data['user_agent'] = '';
        }

        if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
            $order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
        } else {
            $order_data['accept_language'] = '';
        }


        $shipment_info = $this->getShipmenInfo($order);

        $order_data['shipping_info'] = $shipment_info;

        $shipping_address = array(
            'shipping_firstname' => $first_name,
            'shipping_lastname' => $lastname,
            'shipping_company' => '',
            'shipping_address_1' => $adres,
            'shipping_address_2' => '',
            'shipping_city' => $ilce,
            'shipping_town' => $ilce,
            'shipping_postcode' => $postakodu,
            'shipping_country_id' => 215,
            'shipping_zone_id' => $this->entegrasyon->findZoneId($sehir),
            'shipping_zone' => $sehir,
            'shipping_country' => 'Türkiye',
            'shipping_address_format' => '{firstname} {lastname}
{company}
{address_1}
{address_2}
{postcode}, {city} - {zone} / {country}',
            'shipping_custom_field' => $shipment_info,
            'shipping_code' => $shipment_info['shipment_method'],

        );

        $shipment_method = 'Trendyol';
        $shipment_method .= '- Platform Referans No:' . $order_data['order_id'];
        if ($shipment_info['campaign_number']) $shipment_method .= ' - Kampanya No:' . $shipment_info['campaign_number'];
        if ($shipment_info['shipping_code']) $shipment_method .= ' - Kargo No:' . $shipment_info['shipping_code'];
        if ($shipment_info['shipment_method']) $shipment_method .= ' - Kargo Metodu:' . $shipment_info['shipment_method'];
        $shipping_address['shipping_method'] = $shipment_method;

        $payment_customer=$this->getCustomer($order['billingAddress']['nameSurname']);

        $payment_address = array(

            'payment_firstname' => $payment_customer['first_name'],
            'payment_lastname' => $payment_customer['last_name'],
            'payment_company' => $company,
            'payment_address_1' => $order['billingAddress']['displayAddressText'],
            'payment_address_2' => '',
            'payment_city' => $order['billingAddress']['cityName'],
            'payment_town' => $order['billingAddress']['districtName'],
            'payment_postcode' => $postakodu,
            'payment_country_id' => 215,
            'payment_country' => 'Türkiye',
            'payment_zone_id' => $this->entegrasyon->findZoneId($sehir),
            'payment_code' => 'Pazarama',
            'payment_zone' => $sehir,
            'payment_address_format' => '{firstname} {lastname}
{company}
{address_1}
{address_2}
{postcode}, {city} - {zone} / {country}',

            'payment_custom_field' => array('vd' => $vergidairesi, 'vkn' => $vergiNo, 'company' => $company)

        );


        $payment_method = 'Trendyol';
        if ($vergidairesi) $payment_method .= '-VD' . $vergidairesi;
        if ($vergiNo) $payment_method .= '-VKN:' . $vergiNo;
        $payment_address['payment_method'] = $payment_method;


        $order_data['order_date'] = date('Y-m-d H:i:s', strtotime($order['orderDate']));

        $order_data = array_merge($order_data, $payment_address);
        $order_data = array_merge($order_data, $shipping_address);

        $order_data['affiliate_id'] = '';
        $order_data['commission'] = '';
        $order_data['marketing_id'] = '';
        $order_data['tracking'] = '';
        $order_data['custom_field'] = array('fatura_bilgileri' => array('vergi_dairesi' => $vergidairesi, 'vergi_yada_kimlik_no' => $vergiNo), 'kargo_bilgileri' => $shipment_info);
        $order_data['order_status_id'] = $order['shipmentPackageStatus'];

        $order_data['products'] = array();
        $order_data['payment_info'] = array('vd' => $vergidairesi, 'vkn' => $vergiNo, 'company' => $company);




        $total = 0;
        $tax = 0;
        $subtotal = 0;


        $totals = array();

        foreach ($order['items'] as $product) {
            $order_data['products'][] = $this->getProductInfo($product['product'], $product);

        }


        foreach ($order['items'] as $product) {
            $tax += (float)$product['taxAmount']['value'];
            $subtotal += (float)$product['totalPrice']['value'] - $product['taxAmount']['value'];
            $total += (float)$product['totalPrice']['value'];
            if ($this->config->get('easy_setting_order_price_with_tax')) {
                //$order_data['total'] +=(float)$product['price'];

            }

        }






        if (!$this->config->get('easy_setting_order_price_with_tax')) {

            $totals[] = array(

                'code' => 'tax',
                'title' => 'KDV',
                'value' => (float)$tax,
                'sort_order' => 5
            );


        }

        $totals[] = array(

            'code' => 'sub_total',
            'title' => 'Ara Toplam (KDV HARİÇ)',
            'value' => $subtotal,
            'sort_order' => 1
        );


        $totals[] = array(

            'code' => 'total',
            'title' => 'Toplam',
            'value' => $total,
            'sort_order' => 9
        );


        $order_data['totals'] = $totals;

        $custom_data = array();
        // $custom_data[2] = $result->orderDetail->buyer->taxId ? $result->orderDetail->buyer->taxId : '';
        //$custom_data[1] = $result->orderDetail->buyer->taxOffice ? $result->orderDetail->buyer->taxOffice : '';
        //$custom_data[3] = $result->orderDetail->buyer->tcId ? $result->orderDetail->buyer->tcId : '';

        // $order_data['payment_custom_field'] = $custom_data;
        //$order_data['shipping_custom_field'] = $custom_data;
        //$order_data['custom_field'] = $custom_data;

        $order_data['total'] = $total;
        return $order_data;


    }


    private function getProductInfo($product, $order)
    {
        // print_r($product);return;
        // $product_info = $this->entegrasyon->getProductByMarketId($product['productCode'],'ty');
        //if(!$product_info)
        if (!isset($product['merchantSku'])) {
            $product['merchantSku'] = $product['barcode'];
        }
        $product_info = $this->entegrasyon->getProductByOrderModel($product['code'], $product['stockCode'], $product['name'], 'pz');

        $kdv = $product['vatRate'];


        $price = $order['totalPrice']['value'];
        $tax = $order['taxAmount']['value'];

        if ($product_info['product']) {
            $model = $product_info['product']['model'];
        } else {
            $model = $product['code'];
        }

        $query = $this->db->query("select * from " . DB_PREFIX . "es_market_product where barcode like '" . $product['code'] . "' and code='pz' ");


        $market_product_id = isset($query->row['marketplace_product_id']) ? $query->row['marketplace_product_id'] : $product['code'];
        $product_data = array(
            'item_id' => '',
            'product_id' => $product_info['product'] ? $product_info['product']['product_id'] : 0,
            'variant_id' => $product_info['variant_id'],
            'name' => $product['name'],
            'model' => $model,
            'base_model'=>$product_info['product'] ?$product_info['product']['model']:0 ,
            'base_price'=>$product_info['product'] ?$product_info['product']['price']:0,
            'base_special'=>$product_info['product'] ? $product_info['product']['special']:0,
            'barcode' => $product['code'],
            'market_product_id' => $market_product_id,
            'option' => array(),
            'download' => '',
            'quantity' => $order['quantity'],
            'subtract' => '',
            'shipment_info' => $this->getShipmenInfo($order),
            'list_price' => $this->entegrasyon->priceFormat($order['listPrice']['value']),
            'price' => $this->config->get('easy_setting_order_price_with_tax') ? $product['price'] : $this->entegrasyon->priceFormat($price),
            'total' => $price * $order['quantity'],
            'tax' => $this->entegrasyon->priceFormat($tax),
            'tax_range' => $product['vatRate'],
            'totaltax' => $tax,
            'discount' => $order['discountAmount']['value'],
            'reward' => ''
        );

        $get_variant_info = $this->entegrasyon->getVariantByModel('pz', $product['merchantSku'], $product['code'], $product_data['model']);
        if (!$get_variant_info) {

            $product['merchantSku'] = substr($product['code'], strlen($this->config->get('pz_setting_model_prefix')));
            $product['barcode'] = substr($product['barcode'], strlen($this->config->get('pz_setting_model_prefix')));
            $product['model'] = isset($product['model']) ? substr($product['model'], strlen($this->config->get('pz_setting_model_prefix'))) : "";

            $get_variant_info = $this->entegrasyon->getVariantByModel('pz', $product['code'], $product['code'], $product_data['model']);


        }

        if ($get_variant_info) {

            $product_data['product_id'] = $get_variant_info['product_id'];
            $product_data['variant_id'] = $get_variant_info['variant_id'];
            $variant_infos = explode('|', $get_variant_info['variant_info']);

            $options = array();
            foreach ($variant_infos as $variant_info) {
                $variant_data = explode('+-', $variant_info);
                $option_info = $this->entegrasyon->getProductOptionInfoByProductIdAndOptionValueId($get_variant_info['product_id'], $variant_data[3]);

                if ($option_info) {

                    $options[] = array('product_option_id' => $option_info['option_value_id'], 'product_option_value_id' => $option_info['product_option_value_id'], 'name' => $option_info['option_name'], 'value' => $option_info['value']);
                }

            }

            $product_data['option'] = $options;
        }

        return $product_data;

    }


    private function getShipmenInfo($order)
    {


        return array(

            'shipping_code' => $order['cargoTrackingNumber'],
            'campaign_number' => $order['cargoTrackingNumber'],
            'shipment_method' => $order['cargoProviderName']
        );

    }

    public function getClaims()
    {
        $claims = $this->getFromMarket(false,0,true);




        foreach ($claims['result']['content'] as $claim) {

//echo "//select count(*) as total from ".DB_PREFIX."es_order where market_order_id like '".$claim['orderNumber']."' and order_status != 4 ";

            $query =  $this->db->query("select count(*) as total from ".DB_PREFIX."es_order where market_order_id like '".$claim['orderNumber']."' and order_status != 4 ");

            if($query->row['total']){

                // echo "update ".DB_PREFIX."es_order SET order_status=6 where market_order_id='".$claim['orderNumber']."'  ";
                // echo "update ".DB_PREFIX."es_order SET order_status=6 where market_order_id like '".$claim['orderNumber']."'  ";

                try {


                    $this->db->query("update ".DB_PREFIX."es_order SET order_status=6 where market_order_id like '".$claim['orderNumber']."'  ");


                }catch (Exception $exception){

                    echo $exception->getMessage();
                }

            }

        }
    }

    public function send_invoice()
    {
        $url = "https://api.trendyol.com/sapigw/suppliers/" . $this->config->get('ty_satici_numarasi') . "/supplier-invoice-links";

        echo $url;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
"invoiceLink": "https://aktifmarket.com/faturalar/2023/04/15/180c6a92-df92-11ed-8969-0b570c65ed06.pdf","shipmentPackageId": 1464612250
}',

            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic ' . base64_encode($this->config->get('ty_api_anahtari') . ':' . $this->config->get('ty_api_sifresi')),
                'Cookie: __cfruid=5ea4145c689c0eaa082764aff49f9b5942a0c5cc-1678798330; _cfuvid=1ct6bdXf1UBYQFlqgL2ptzIXgiFrYX8p6fAgDoHYXpM-1678798330240-0-604800000; __cflb=02DiuEkjxji3pxUywYoR8hKcjQvCpYR5YPXNuFxridWZa'
            ),
        ));

        $response = curl_exec($curl);

        print_r($response);

        curl_close($curl);

    }


}





