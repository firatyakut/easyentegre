<?php

class ModelEntegrasyonOrderHb extends Model
{


    public function getFromMarket($order_type = 'o', $order_id = false, $packageNumber = false, $claim = false)
    {


        $markets = unserialize($this->config->get('mir_marketplaces'));
        $is_active = 1;
        foreach ($markets as $market) {
            if ($market['code'] == 'hb') {
                $is_active = $market['is_active'];
            }
        }
        $result = array();

        if ($is_active) {

            //$url = "https://oms-external.hepsiburada.com/orders/merchantid/".$this->config->get('hb_merchant_id')."?offset=0&limit=100";
            if ($order_id) {
                $url = "https://oms-external.hepsiburada.com/orders/merchantid/" . $this->config->get('hb_merchant_id') . "/ordernumber/" . $order_id;
            } else {
                if ($order_type == 'o') {
                    $url = "https://oms-external.hepsiburada.com/orders/merchantid/" . $this->config->get('hb_merchant_id') . '?offset=0&limit=100';;

                } else if ($order_type == 'p') {

                    $url = "https://oms-external.hepsiburada.com/packages/merchantid/" . $this->config->get('hb_merchant_id') . '?offset=0&limit=100';
                } else if ($order_type == 'd') {

                    $url = "https://oms-external.hepsiburada.com/packages/merchantid/" . $this->config->get('hb_merchant_id') . '/delivered';
                } else if ($order_type == 'u') {

                    $url = "https://oms-external.hepsiburada.com/packages/merchantid/" . $this->config->get('hb_merchant_id') . '/unpacked';
                } else if ($order_type == 'c') {

                    $url = "https://oms-external.hepsiburada.com/packages/merchantid/" . $this->config->get('hb_merchant_id') . '/packagenumber/' . $packageNumber;

                }

                if ($claim) {
                    $url = str_replace(' ','%20',"https://oms-external.hepsiburada.com/claims/merchantid/" . $this->config->get('hb_merchant_id') . '/status/Refunded?offset=0&limit=100&beginDate='.date('Y-m-d H:i',strtotime("-30 days")).'&endDate='.date('Y-m-d H:i'));


                }

            }




            $curl = curl_init($url);

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $headers = array(
                "User-Agent: miryazilim_dev"
            );

            curl_setopt($curl, CURLOPT_USERPWD, $this->config->get('hb_merchant_id') . ":" . $this->config->get('hb_service_key'));
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
//for debug only!
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            $response = json_decode(curl_exec($curl), 1);

            // print_r($response);return


            curl_close($curl);


            if (isset($response['statusCode'])) {

                if ($response['statusCode'] == 403) {

                    $result = array('status' => false, 'message' => 'miryazilim_dev firması mağazınız için yetkili değildir, miryazilim_dev firmasını yetkilendirmek için hepsiburada ile iletişime geçiniz.', 'result' => array());

                    goto son;
                }


            } else {

                $result = array('status' => true, 'message' => '', 'result' => $response);

            }

        } else {

            $result = array('status' => false, 'message' => 'Kullanım süreniz sona erdi, premium hesaba geçiş yapabilirsiniz.', 'result' => array());

        }

        son:
        return $result;

    }

    public function check_order_status($order)
    {

        //$order_info=$this->getFromMarket('p',$order['market_order_id']);


        //  $order_detail = $this->getFromMarket('c', false, explode('@', explode('_', $order['email'])[1])[0]);
        $order_info = $this->getFromMarket('o', $order['market_order_id']);


        if (isset($order_info['result']['items'][0]['packageNumber'])) {
            $order_detail = $this->getFromMarket('c', false, $order_info['result']['items'][0]['packageNumber']);

            $package_org = $order_info['result']['items'][0];

            //print_r($package_org);

            if ($package_org['status'] == 'CancelledByCustomer') {
                return array(
                    'status' => $package_org['status'],
                    'tracking_url' => '',
                    'order_id'=>$order['market_order_id'],
                    'cargo_name' => '',
                    'cargo_number' => '',
                    'shipment_package_id' => '',
                    'invoice_link' => ''

                );
            }

            //if(!isset($order_detail['result'][0])){
            $order_status = isset($order_detail['result'][0]['status']) ? $order_detail['result'][0]['status'] : '';
            $tracking_url = isset($order_detail['result'][0]['trackingInfoUrl']) ? $order_detail['result'][0]['trackingInfoUrl'] : '';
            $cargo_name = isset($order_detail['result'][0]['cargoCompany']) ? $order_detail['result'][0]['cargoCompany'] : '';
            $cargo_number = isset($order_detail['result'][0]['barcode']) ? $order_detail['result'][0]['barcode'] : '';

            /*  if($cargo_number && $tracking_url){
                  $order_status="InTransit";
              }else if($cargo_number && !$tracking_url){
                  $order_status="Packaged";
              }*/

            if($order_detail['result'][0]['status']=='Open'){
                $order_status="Packaged";
            }

            return array(
                'status' => $order_status,
                'tracking_url' => $tracking_url,
                'cargo_name' => $cargo_name,
                'order_id'=>$order['market_order_id'],
                'cargo_number' => $cargo_number,
                'shipment_package_id' => '',
                'invoice_link' => ''

            );
        } else {

            //print_r($order_info['result']);
        }
        //} else {

        //  $this->db->query("DELETE FROM `".DB_PREFIX."es_order` WHERE `order_id` ='".$order['order_id']."'");

        //  }


    }


    public function getClaims()
    {
        $claims = $this->getFromMarket(false, 0, false, true);




        foreach ($claims['result'] as $claim) {

//echo "//select count(*) as total from ".DB_PREFIX."es_order where market_order_id like '".$claim['orderNumber']."' and order_status != 4 ";

            if ($claim['claimType'] == 'Return') {



                $query = $this->db->query("select count(*) as total from " . DB_PREFIX . "es_order where market_order_id like '" . $claim['orderNumber'] . "' and order_status != 4 ");

                if ($query->row['total']) {

                    // echo "update ".DB_PREFIX."es_order SET order_status=6 where market_order_id='".$claim['orderNumber']."'  ";
                    // echo "update ".DB_PREFIX."es_order SET order_status=6 where market_order_id like '".$claim['orderNumber']."'  ";
                    try {
                        $this->db->query("update " . DB_PREFIX . "es_order SET order_status=6 where market_order_id like '" . $claim['orderNumber'] . "'  ");
                    } catch (Exception $exception) {

                        echo $exception->getMessage();
                    }

                }else
                {

                    print_r($claim);
                    return;
                    echo $claim['orderNumber']. '- Buluanmadı!';

                }
            }
        }
    }


    public
    function getOrders($debug = false, $mode = 'add')
    {

        $this->load->model('entegrasyon/general');

        $orderList = array();

        $post_data = array(
            'merchant_id' => 'Created'
        );

        //  $post_data['request_data']=array('status'=>'Created','auto_approve'=>$this->config->get('hb_setting_auto_approve'));

        //$post_data['market']=$this->model_entegrasyon_general->getMarketPlace('hb');

        $orders = array();
        $order_lists = $this->getFromMarket('o');

        $packages = $this->getFromMarket('p');


        foreach ($order_lists['result']['items'] as $item) {

            $orders[] = $item['orderNumber'];

        }


        foreach ($packages['result'] as $item) {


            $orders[] = $item['items'][0]['orderNumber'];

        }


        //  if($orders->statusCode==401)return $orderList;


        if ($orders) {
            foreach ($orders as $order) {
                if ($mode == 'update') {

                    $orderList[] = $this->checkStatues($order);


                } else {
                    if (!$this->entegrasyon->checkOrderByMarketPlaceOrderId($order)) {

                        $order_info = $this->getFromMarket('o', $order)['result'];
                        $orderList[] = $this->getOrderNew($order_info);

                    }
                }
            }
        }


        return $orderList;

    }


    public
    function checkStatues($order)
    {


        //  print_r($order);2
        $statuses = array('ReadyToShip' => 2, 'Delivered' => 5, 'Cancelled' => 7, 'Shipped' => 3, 'Delivered' => 5);


        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order where email='" . $this->db->escape($order['customerEmail']) . "'");

        $order_info = $query->row;


        if ($order_info) {
            $order_id = $order_info['order_id'];
            $status = $order['status'];
            if ($status == 'Shipped') {

                $tracking_number = $order['cargoSenderNumber'];
                $tracking_link = $order['cargoTrackingLink'];
                if ($order['cargoProviderName'] == 'Sürat Kargo Marketplace') {
                    $kargo = 'surat';
                    $name = 'Sürat Kargo';
                } else if ($order['cargoProviderName'] == 'Yurtiçi Kargo Marketplace') {
                    $kargo = 'yurtici';
                    $name = 'Yurtiçi Kargo';

                }

                try {
                    $query = $this->db->query("select * from `oc_order_shipping` where order_id='" . $order_id . "'");
                    if ($query->num_rows) {

                        $this->db->query("update `oc_order_shipping` SET status='" . $status . "', code='" . $kargo . "',name='" . $name . "', status_message='" . $this->db->escape($status) . "',tracking_number='" . $tracking_number . "',tracking_url='" . $tracking_link . "', date_modified=NOW() where order_id='" . $order_id . "' ");

                    } else {

                        $this->db->query("insert into `oc_order_shipping` SET order_id='" . $order_id . "',status='" . $status . "', code='" . $kargo . "',name='" . $name . "', status_message='" . $this->db->escape($status) . "',tracking_number='" . $tracking_number . "',tracking_url='" . $tracking_link . "', date_added=NOW() ");

                    }
                } catch (Exception $exception) {
                    echo $exception->getMessage();
                }


            }


            $order_status_id = isset($statuses[$status]) ? $statuses[$status] : 2;
            $this->db->query("update " . DB_PREFIX . "order_history SET order_status_id='" . $order_status_id . "', notify=0, comment='', date_added=NOW() where order_id='" . $order_id . "'");
            $this->db->query("update " . DB_PREFIX . "order SET order_status_id='" . $order_status_id . "' where order_id='" . $order_id . "'");


        }


    }


    public
    function getOrderNew($order)

    {

        $customer = explode(' ', $order['customer']['name']);
        $first_name = count($customer) == 3 ? $customer[0] . ' ' . $customer[1] : $customer[0];
        $lastname = count($customer) == 3 ? $customer[2] : $customer[1];
        $adres = $order['deliveryAddress']['address'];
        $sehir = $order['deliveryAddress']['city'];
        $ilce = $order['deliveryAddress']['town'];
        $postakodu = '';
        $phone = $order['deliveryAddress']['phoneNumber'];
        $email = $order['deliveryAddress']['email'];
        $tcKimlikNo = isset($order['invoice']['taxNumber']) ? $order['invoice']['taxNumber'] : $order['invoice']['turkishIdentityNumber'];
        $vergidairesi = $order['invoice']['taxOffice'];
        $vergiNo = $order['invoice']['taxNumber'];

        $vergiNo = $vergiNo ? $vergiNo : $tcKimlikNo;


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
            'zone_id' => $this->entegrasyon->findZoneId($sehir)

        );


        $order_data = array(
            'id' => $order['orderId'],
            'firstname' => $first_name,
            'lastname' => $lastname,
            'tckimlikno' => $tcKimlikNo,
            'customer_group_id' => $this->config->get('config_customer_group_id'),
            'email' => $email,
            'telephone' => $phone,
            'fax' => '',
            'custom_field' => '',
            'newsletter' => '',
            'password' => '123456',
            'status' => 1,
            'approved' => 1,
            'safe' => 1,
            'address' => $address_data,
            'order_type' => 'hb',
            'plength' => '',
            'poption' => '',


        );

        $order_data['order_status_id'] = $order['items'][0]['status'];
        $order_data['order_date'] = date('Y-m-d H:i:s', strtotime($order['orderDate']));
        $order_data['payment_company_id'] = $vergidairesi;
        $order_data['payment_tax_id'] = $vergiNo;
        $order_data['invoice_prefix'] = '';
        $order_data['invoice_no'] = 0;
        $order_data['store_id'] = $this->config->get('config_store_id');
        $order_data['store_name'] = $this->config->get('config_name');
        $order_data['invoice_link'] = "";
        if ($order_data['store_id']) {
            $order_data['store_url'] = $this->config->get('config_url');
        } else {
            if ($this->request->server['HTTPS']) {
                $order_data['store_url'] = HTTP_SERVER;
            } else {
                $order_data['store_url'] = HTTP_SERVER;
            }
        }


        $order_data['comment'] = '';

        $order_data['language_id'] = $this->config->get('config_language_id');
        $order_data['currency_id'] = 1;
        $order_data['currency_code'] = $this->session->data['currency'];
        $order_data['currency_value'] = 1;//$this->currency->getValue($this->session->data['currency']);
        $order_data['ip'] = $this->request->server['REMOTE_ADDR'];

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


        $shipment_info = $this->getShipmenInfo($order['items'][0]);

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
            'shipping_custom_field	' => 'Kampanya no:' . $shipment_info['campaign_number'],

            'shipping_code' => $shipment_info['shipment_method'],

        );

        $shipment_method = 'Hepsiburada';
        if ($shipment_info['campaign_number']) $shipment_method .= ' - Kampanya No:' . $shipment_info['campaign_number'];
        if ($shipment_info['shipping_code']) $shipment_method .= ' - Kargo No:' . $shipment_info['shipping_code'];
        if ($shipment_info['shipment_method']) $shipment_method .= ' - Kargo Metodu:' . $shipment_info['shipment_method'];
        $shipping_address['shipping_method'] = $shipment_method;


        $payment_address = array(

            'payment_firstname' => $first_name,
            'payment_lastname' => $lastname,
            'payment_company' => $order['invoice']['address']['name'],
            'payment_address_1' => $order['invoice']['address']['address'],
            'payment_address_2' => '',
            'payment_city' => $order['invoice']['address']['city'],
            'payment_town' => $order['invoice']['address']['town'],

            'payment_postcode' => $postakodu,
            'payment_country_id' => 215,
            'payment_country' => 'Türkiye',
            'payment_zone_id' => $this->entegrasyon->findZoneId($sehir),
            'payment_code' => 'Hepsiburada',
            'payment_zone' => $sehir,
            'payment_address_format' => '{firstname} {lastname}
{company}
{address_1}
{address_2}
{postcode}, {city} - {zone} / {country}',
            'payment_custom_field' => array('Vergi Dairesi' => $vergidairesi, 'Vergi/TC Kimlik' => $vergiNo)

        );

        $order_data['payment_info'] = array('vergi_dairesi' => $vergidairesi, 'vergi_yada_kimlik_no' => $vergiNo);


        $payment_method = 'Hepsiburada';
        if ($vergidairesi) $payment_method .= '-Vergi Dairesi' . $vergidairesi;
        if ($vergiNo) $payment_method .= '-Vergi/TC Kimlik:' . $vergiNo;
        $payment_address['payment_method'] = $payment_method;

        $order_data = array_merge($order_data, $shipping_address);
        $order_data = array_merge($order_data, $payment_address);

        //  $order_data['payment_address']=$payment_address;
        //  $order_data['shipping_address']=$shipping_address;

        $order_data['affiliate_id'] = '';
        $order_data['commission'] = '';
        $order_data['marketing_id'] = '';
        $order_data['tracking'] = '';
        $order_data['custom_field'] = array('fatura_bilgileri' => array('vergi_dairesi' => $vergidairesi, 'vergi_yada_kimlik_no' => $vergiNo), 'kargo_bilgileri' => $shipment_info);

        $order_data['order_status_id'] = $order['items'][0]['status'];

        $order_data['products'] = array();

        $order_data['total'] = 0;

        $total = 0;
        $tax = 0;
        $subtotal = 0;


        $totals = array();

        foreach ($order['items'] as $product) {

            $order_data['order_id'] = $product['orderNumber'];
            $order_data['products'][] = $this->getProductInfo($product, $order);

        }


        foreach ($order_data['products'] as $product) {
            $tax += (float)$product['totaltax'];
            $subtotal += (float)$product['total'];
            $total += (float)$product['price'];
            $order_data['total'] += (float)($product['total'] + $product['totaltax']);
        }


        //    $order_data['order_id']=$order['barcode'];


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
            'title' => 'Ara Toplam',
            'value' => $subtotal,
            'sort_order' => 1
        );


        $totals[] = array(

            'code' => 'total',
            'title' => 'Toplam',
            'value' => $order_data['total'],
            'sort_order' => 9
        );


        $order_data['totals'] = $totals;


        return $order_data;


    }

    private
        $order_id;


    private
    function getProductInfo($product, $order)
    {


        $product_data = array();


        $this->order_id = $product['orderNumber'];
        $product_info = $this->entegrasyon->getProductByOrderModel($product['merchantSKU'], $product['merchantSKU'], $product['name'], 'hb');
        if ($product_info['product']) {
            $model = $product_info['product']['model'];
        } else {
            $model = $product['merchantSKU'];
        }
        $product_data = array(

            'item_id' => '',
            'product_id' => $product_info['product'] ? $product_info['product']['product_id'] : 0,
            'variant_id' => $product_info['variant_id'],
            'name' => $product['name'],
            'barcode' => $model,
            'market_product_id' => $product['sku'],
            'base_model' => $product_info['product'] ? $product_info['product']['model'] : 0,
            'base_price' => $product_info['product'] ? $product_info['product']['price'] : 0,
            'base_special' => $product_info['product'] ? $product_info['product']['special'] : 0,
            'model' => $model,
            'option' => $this->getOrderAtrributes($product),
            'download' => '',
            'quantity' => $product['quantity'],
            'subtract' => '',
            'shipment_info' => $this->getShipmenInfo($product),
            'list_price' => $product['unitPrice']['amount'] - ($product['vat'] / $product['quantity']),
            'price' => $product['unitPrice']['amount'] - ($product['vat'] / $product['quantity']),
            'total' => (float)($product['quantity'] * $product['unitPrice']['amount']) - $product['vat'],
            'tax' => $this->entegrasyon->priceFormat(($product['vat']) / $product['quantity']),
            'tax_range' => $product['vatRate'],

            'totaltax' => (float)($product['vat']),
            'discount' => (float)isset($product['hbDiscount']['totalPrice']['amount']) ? $product['hbDiscount']['totalPrice']['amount'] : 0,
            'reward' => ''
        );


        $get_variant_info = $this->entegrasyon->getVariantByModel('hb', $product['sku'], $product['sku']);

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
                    // $this->entegrasyon->updateProductOptionStock2($option_info['product_option_value_id'],$product['quantity']);

                }

            }


            $product_data['option'] = $options;
            //$options=$this->getProductOptionValue($get_variant_info['product_id'], $product_option_value_id)


        }

        return $product_data;


    }

    private
    function getOrderAtrributes($attributes)
    {

        $options = array();


        return $options;


    }


    private
    function getShipmenInfo($order)
    {

        $shipping = array(

            'shipping_code' => '',//$order['barcode'],
            'campaign_number' => isset($order['barcode']) ? $order['barcode'] : '',
            'shipment_method' => $order['cargoCompany']
        );


        return $shipping;

    }


}





