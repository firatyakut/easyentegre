<?php

class ModelEntegrasyonOrderTy extends Model
{


    public function getFromMarket($order_number = false, $page = 0,$claims=false)
    {


        $markets = unserialize($this->config->get('mir_marketplaces'));
        $is_active = 1;
        foreach ($markets as $market) {
            if ($market['code'] == 'ty') {
                $is_active = $market['is_active'];
            }
        }

        if ($is_active) {

            if ($order_number) {
                $url = 'https://api.trendyol.com/sapigw/suppliers/' . $this->config->get('ty_satici_numarasi') . '/orders?orderNumber=' . $order_number;

            } else {

                // $url='https://api.trendyol.com/sapigw/suppliers/' . $this->config->get('ty_satici_numarasi') . '/orders?size=650&orderByDirection=DESC&orderByField=PackageLastModifiedDate';

                if ($this->config->get('ty_setting_order_range') && !$claims) {


                    $enddate = date('Y-m-d H:i:s');
                    $startdate = date('Y-m-d H:i:s', strtotime($this->config->get('ty_setting_order_range')));
                    // $startdate=date('Y-m-d H:i:s', strtotime("-2 days"));


                    $url = 'https://api.trendyol.com/sapigw/suppliers/' . $this->config->get('ty_satici_numarasi') . '/orders?size=100&startDate=' . strtotime($startdate) . '000' . '&endDate=' . strtotime($enddate) . '000';

                } else if($claims) {
                    $enddatew= date('Y-m-d H:i:s');
                    $startdatew = date('Y-m-d H:i:s', strtotime("-15 days"));
                    $url = 'https://api.trendyol.com/sapigw/suppliers/' . $this->config->get('ty_satici_numarasi') . '/claims?claimItemStatus=Accepted&size=100&startDate=' . strtotime($startdatew) . '000' . '&endDate=' . strtotime($enddatew) . '000';

                }else {

                    $url = 'https://api.trendyol.com/sapigw/suppliers/' . $this->config->get('ty_satici_numarasi') . '/orders?size=650&orderByDirection=DESC&orderByField=PackageLastModifiedDate';

                }

            }




            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_USERAGENT =>$this->shopId . " - easyentegre",
                CURLOPT_HTTPHEADER => array(
                    'Content-Type:application/json',
                    'Authorization: Basic ' . base64_encode($this->config->get('ty_api_anahtari') . ':' . $this->config->get('ty_api_sifresi')),
                    'Cookie: __cfruid=5ea4145c689c0eaa082764aff49f9b5942a0c5cc-1678798330; _cfuvid=1ct6bdXf1UBYQFlqgL2ptzIXgiFrYX8p6fAgDoHYXpM-1678798330240-0-604800000; __cflb=02DiuEkjxji3pxUywYoR8hKcjQvCpYR5YPXNuFxridWZa'
                ),
            ));

            $response = curl_exec($curl);
            if (curl_errno($curl)) {
                $error_msg = curl_error($curl);
                echo $error_msg;
            }

          /*  $info = curl_getinfo($curl);
            print_r($info);
            curl_close($curl);
*/
            return array('status' => true, 'message' => '', 'result' => json_decode($response, 1));

        } else {

            return array('status' => false, 'message' => 'Kullanım süreniz sona erdi, premium hesaba geçiş yapabilirsiniz.', 'result' => array());

        }

    }


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
                'cargo_name' => $cargo_name,
                'order_id'=>$order['market_order_id'],
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


        $orderList = array();

        $post_data['request_data'] = 'Created';

        //  $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('ty');

        $orders = $this->getFromMarket();//$this->entegrasyon->clientConnect($post_data, 'get_orders', 'ty', $debug);


       //  print_r($orders);return ;

        if (!$orders['status']) {
            return $orders;

        }

        $order_temps = array();
        if ($orders['status'] && isset($orders['result']['content'])) {


            for ($i = 0; $i < $orders['result']['totalPages']; $i++) {


                if ($i > 0) {
                    $orders = $this->getFromMarket(false, $i);

                }


                foreach ($orders['result']['content'] as $order) {

                    if ($mode == 'update') {

                        $orderList[] = $order;

                    } else {
                        if (!$this->entegrasyon->checkOrderByMarketPlaceOrderId($order['orderNumber']) && $order['shipmentPackageStatus'] != 'Cancelled') {

                            //$orderList[] = $this->getOrder($order);
                            $order_temps[] = $order;

                        }

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


    public function getOrder($order)

    {


        $this->load->model('entegrasyon/general');
        $custom_data = $order['shipmentAddress']['fullName'];
        $first_name = $order['shipmentAddress']['firstName'];
        $lastname = $order['shipmentAddress']['lastName'];
        $adres = $order['shipmentAddress']['fullAddress'];
        $sehir = $order['shipmentAddress']['city'];
        $ilce = $order['shipmentAddress']['district'];
        $postakodu = '';
        $phone = '';

        if ($order['commercial']) {
            $vergidairesi = $order['invoiceAddress']['taxOffice'];
            $vergiNo = $order['invoiceAddress']['taxNumber'];
            $company = $order['invoiceAddress']['company'];

        } else {

            $vergidairesi = isset($order['taxNumber']) ? $order['taxNumber'] : '';
            $vergiNo = isset($order['taxNumber']) ? $order['taxNumber'] : $order['tcIdentityNumber'];
            $company = $order['invoiceAddress']['company'];

        }


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


        $payment_address = array(

            'payment_firstname' => $order['invoiceAddress']['firstName'],
            'payment_lastname' => $order['invoiceAddress']['lastName'],
            'payment_company' => '',
            'payment_address_1' => $order['invoiceAddress']['fullAddress'],
            'payment_address_2' => '',
            'payment_city' => $order['invoiceAddress']['city'],
            'payment_town' => $order['invoiceAddress']['district'],
            'payment_postcode' => $postakodu,
            'payment_country_id' => 215,
            'payment_country' => 'Türkiye',
            'payment_zone_id' => $this->entegrasyon->findZoneId($sehir),
            'payment_code' => 'Trendyol',
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


        $order_data['order_date'] = date('Y-m-d H:i:s', substr($order['orderDate'], 0, 10));

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

        $order_data['total'] = $order['totalPrice'];


        $total = 0;
        $tax = 0;
        $subtotal = 0;


        $totals = array();

        foreach ($order['lines'] as $product) {
            $order_data['products'][] = $this->getProductInfo($product, $order);

        }


        foreach ($order_data['products'] as $product) {
            $tax += (float)$product['totaltax'];
            $subtotal += (float)$product['total'];
            $total += (float)$product['price'];
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
            'value' => $order['totalPrice'],
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
        $product_info = $this->entegrasyon->getProductByOrderModel($product['barcode'], $product['merchantSku'], $product['productName'], 'ty');

        $kdv = $product['vatBaseAmount'];


        $price = ($product['price'] / ((100 + $kdv) / 100));
        $tax = (($product['price'] - $price));

        if ($product_info['product']) {
            $model = $product_info['product']['model'];
        } else {
            $model = $product['merchantSku'];
        }

        $query = $this->db->query("select * from " . DB_PREFIX . "es_market_product where barcode like '" . $product['barcode'] . "' and code='ty' ");


        $market_product_id = isset($query->row['marketplace_product_id']) ? $query->row['marketplace_product_id'] : $product['productCode'];
        $product_data = array(
            'item_id' => '',
            'product_id' => $product_info['product'] ? $product_info['product']['product_id'] : 0,
            'variant_id' => $product_info['variant_id'],
            'name' => $product['productName'],
            'model' => $model,
            'base_model'=>$product_info['product'] ?$product_info['product']['model']:0 ,
            'base_price'=>$product_info['product'] ?$product_info['product']['price']:0,
            'base_special'=>$product_info['product'] ? $product_info['product']['special']:0,
            'barcode' => $product['barcode'],
            'market_product_id' => $market_product_id,
            'option' => array(),
            'download' => '',
            'quantity' => $product['quantity'],
            'subtract' => '',
            'shipment_info' => $this->getShipmenInfo($order),
            'list_price' => $this->entegrasyon->priceFormat($product['amount']),
            'price' => $this->config->get('easy_setting_order_price_with_tax') ? $product['price'] : $this->entegrasyon->priceFormat($price),
            'total' => $this->config->get('easy_setting_order_price_with_tax') ? $this->entegrasyon->priceFormat($product['price'] * $product['quantity']) : $this->entegrasyon->priceFormat($price * $product['quantity']),
            'tax' => $this->entegrasyon->priceFormat($tax),
            'tax_range' => $product['vatBaseAmount'],
            'totaltax' => $this->entegrasyon->priceFormat($tax * $product['quantity']),
            'discount' => $this->entegrasyon->priceFormat($product['discount']),
            'reward' => ''
        );

        $get_variant_info = $this->entegrasyon->getVariantByModel('ty', $product['merchantSku'], $product['barcode'], $product_data['model']);
        if (!$get_variant_info) {

            $product['merchantSku'] = substr($product['merchantSku'], strlen($this->config->get('ty_setting_model_prefix')));
            $product['barcode'] = substr($product['barcode'], strlen($this->config->get('ty_setting_model_prefix')));
            $product['model'] = isset($product['model']) ? substr($product['model'], strlen($this->config->get('ty_setting_model_prefix'))) : "";

            $get_variant_info = $this->entegrasyon->getVariantByModel('ty', $product['merchantSku'], $product['barcode'], $product_data['model']);


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





