<?php

class ModelEntegrasyonOrderN11 extends Model
{

    public function getFromMarket($order_id=false)
    {


        $markets = unserialize($this->config->get('mir_marketplaces'));
        $is_active = 1;
        foreach ($markets as $market) {
            if ($market['code'] == 'n11') {
                $is_active = $market['is_active'];
            }
        }




        if ($is_active) {
            $startdate=date("d/m/Y", strtotime($this->config->get('n11_setting_order_range')?$this->config->get('n11_setting_order_range'):"-10 day"));


            if($order_id){

                $request='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sch="http://www.n11.com/ws/schemas">
   <soapenv:Header/>
   <soapenv:Body>
      <sch:OrderDetailRequest>
             <auth>
            <appKey>'.$this->config->get("n11_api_key").'</appKey>
            <appSecret>'.$this->config->get("n11_api_secret").'</appSecret>
         </auth>
         <orderRequest>
            <id>'.$order_id.'</id>
         </orderRequest>
      </sch:OrderDetailRequest>
   </soapenv:Body>
</soapenv:Envelope>
';

            }else {

                $request='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"   xmlns:sch="http://www.n11.com/ws/schemas">
<soapenv:Header/>
   <soapenv:Body>
      <sch:DetailedOrderListRequest>
         <auth>
            <appKey>'.$this->config->get("n11_api_key").'</appKey>
            <appSecret>'.$this->config->get("n11_api_secret").'</appSecret>
         </auth>
         
          <searchData>
            <productId></productId>
            <status></status>
            <buyerName></buyerName>
            <orderNumber></orderNumber>
            <productSellerCode></productSellerCode>
            <recipient></recipient>
            <sameDayDelivery></sameDayDelivery>
           
            <period>
               <startDate>'.$startdate.'</startDate>
               <endDate>'.date("d/m/Y", strtotime("2 day")).'</endDate>
            </period>
            <sortForUpdateDate>true</sortForUpdateDate>
         </searchData>

      </sch:DetailedOrderListRequest>
   </soapenv:Body>
</soapenv:Envelope>';

            }





            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.n11.com/ws/OrderService.wsdl',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>$request,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: text/xml',
                    'Cookie: e2f9affc532f36c6aec1c8bd433e2a59=16781bcdd0351a279620b66b366945a0'
                ),
            ));

            $response = curl_exec($curl);
            // print_r($response);return;
            $xml = simplexml_load_string($response);



            $result = $xml->xpath('//result');



            $resultdata=array();
            if(isset($result[0]->status)){

                $status=$result[0]->status;

                if($status=='success'){
                    $resultdata['status']=true;
                    if($order_id){

                        $resultdata['orders']=json_decode(json_encode($xml->xpath('// orderDetail')),1);

                    }else {

                        $resultdata['orders']=json_decode(json_encode($xml->xpath('//orderList')),1);
                        $resultdata['pagingData']=json_decode(json_encode($xml->xpath('//pagingData')),1)[0];


                    }


                }
            }

            curl_close($curl);

            return array('status' => true, 'message' => '', 'result' => $resultdata);

        } else {

            return array('status' => false, 'message' => 'Kullanım süreniz sona erdi, premium hesaba geçiş yapabilirsiniz.', 'result' => array());

        }

    }

    public function check_order_status($order)
    {

        //$order_info=$this->getFromMarket('p',$order['market_order_id']);

        $order_detail = $this->getFromMarket($order['market_order_id']);

        $order_status = isset($order_detail['result']['orders'][0]['status']) ? $order_detail['result']['orders'][0]['status'] : '';
        $tracking_url = '';
        $cargo_number = isset($order_detail['result']['orders'][0]['itemList']['item']['shipmentInfo']['shipmentCode']) ? $order_detail['result']['orders'][0]['itemList']['item']['shipmentInfo']['shipmentCode'] : '';
        $cargo_name = isset($order_detail['result']['orders'][0]['itemList']['item']['shipmentInfo']['shipmentCompany']['name']) ? $order_detail['result']['orders'][0]['itemList']['item']['shipmentInfo']['shipmentCompany']['name'] : '';

        return array(
            'status' => $order_status,
            'tracking_url' => $tracking_url,
            'cargo_name' => $cargo_name,
            'order_id'=>$order['market_order_id'],
            'cargo_number' => $cargo_number,
            'shipment_package_id' =>'',
            'invoice_link' =>''
        );

        /*  }else {

              $this->db->query("DELETE FROM `".DB_PREFIX."es_order` WHERE `order_id` ='".$order['order_id']."'");

          }*/


    }

    public function getOrderById($id)
    {


        $markets = unserialize($this->config->get('mir_marketplaces'));
        $is_active = 1;
        foreach ($markets as $market) {
            if ($market['code'] == 'n11') {
                $is_active = $market['is_active'];
            }
        }

        if ($is_active) {

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.n11.com/ws/OrderService.wsdl',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"   xmlns:sch="http://www.n11.com/ws/schemas">
<soapenv:Header/>
   <soapenv:Body>
      <sch:OrderDetailRequest>
         <auth>
            <appKey>'.$this->config->get("n11_api_key").'</appKey>
            <appSecret>'.$this->config->get("n11_api_secret").'</appSecret>
         </auth>
         
         
         <orderRequest>
            <id>'.$id.'</id>
         </orderRequest>


      </sch:OrderDetailRequest>
   </soapenv:Body>
</soapenv:Envelope>',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: text/xml',
                    'Cookie: e2f9affc532f36c6aec1c8bd433e2a59=16781bcdd0351a279620b66b366945a0'
                ),
            ));

            $response = curl_exec($curl);
            // print_r($response);return;
            $xml = simplexml_load_string($response);



            $result = $xml->xpath('//result');

            $resultdata=array();
            if(isset($result[0]->status)){

                $status=$result[0]->status;

                if($status=='success'){
                    $resultdata['status']=true;
                    $resultdata['orders']=json_decode(json_encode($xml->xpath('//orderDetail')),1);

                }
            }

            curl_close($curl);

            return array('status' => true, 'message' => '', 'result' => $resultdata);

        } else {

            return array('status' => false, 'message' => 'Kullanım süreniz sona erdi, premium hesaba geçiş yapabilirsiniz.', 'result' => array());

        }

    }


    public function getOrders($debug=false)
    {

        $orders=array();


        $order_list=$this->getFromMarket();





        /*   $this->load->model('entegrasyon/general');
           //'New'
           $new_order_list=$this->getN11Orders('New',$debug);
           return;
           $approved_list=$this->getN11Orders('Approved',$debug);
           $shipped_list=$this->getN11Orders('Shipped',$debug);


           $order_list=array_merge($new_order_list,$approved_list);
           $order_list=array_merge($shipped_list,$order_list);*/



        if(isset($order_list['result']['orders'][0])){

            $order_list=$order_list['result']['orders'][0];


            if(isset($order_list['order'][0])){

            foreach ($order_list['order'] as $order) {


                if(!$this->entegrasyon->checkOrderByMarketPlaceOrderId($order['id'])){

                    $orders[] = $this->getOrder($order);

                }


            }}else {
                $order=$order_list['order'];
                if(!$this->entegrasyon->checkOrderByMarketPlaceOrderId($order['id'])){

                    $orders[] = $this->getOrder($order);

                }

            }
        }

      //  print_r($orders);return ;

        return $orders;

    }


    public function getOrder($order)

    {


        $order= $this->getOrderById($order['id'])['result']['orders'][0];


        $customer = $order['billingAddress']['fullName'];
        $customer = explode(' ', $customer);
        $first_name = isset($customer[2]) ? $customer[0] . ' ' . $customer[1] : $customer[0];
        $lastname = end($customer);
        $address_data = array();
        $ilce=$order['shippingAddress']['district'];
        $vergidairesi=$order['billingAddress']['taxHouse']?$order['billingAddress']['taxHouse']:'';

      

        $vergiNo=isset($order['billingAddress']['taxId'])?$order['billingAddress']['taxId']:'';
        $tcID=$order['citizenshipId'];


        $vergiNo=$vergiNo?$vergiNo:$tcID;
        $adres=$order['shippingAddress']['address'] . ' ' . $order['shippingAddress']['city'] . ' ' . $order['shippingAddress']['district'];


        $address_data = array(
            'firstname' => $first_name,
            'lastname' => $lastname,
            'company' => '',
            'tax_office'=>$vergidairesi,
            'tax_id'=>$vergiNo,
            'address_1' =>$adres ,
            'city' => $order['shippingAddress']['city'],
            'postcode' => isset($order['shippingAddress']['postalCode']) ? $order['shippingAddress']['postalCode'] : '',
            'country_id' => 215,
            'town'=>$ilce,

            'zone_id' => $this->entegrasyon->findZoneId($order['billingAddress']['city'])

        );


        $order_data = array(
            'id' =>$order['id'],
            'firstname' => $first_name,
            'lastname' => $lastname,
            'tckimlikno' => $order['citizenshipId'],
            'customer_group_id' => $this->config->get('config_customer_group_id'),
            'email' => $order['buyer']['email'],
            'telephone' => isset($order['shippingAddress']['gsm']) ? $order['shippingAddress']['gsm'] : '',
            'fax' => '',
            'newsletter' => '',
            'password' => isset($order['shippingAddress']['gsm']) ? $order['shippingAddress']['gsm'] : '',
            'status' => 1,
            'approved' => 1,
            'safe' => 1,
            'address' => $address_data,
            'order_type' => 'n11',
            'plength' => '',
            'poption' => '',


        );


        $order_data['order_id'] = $order['id'];
        $order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
        $order_data['invoice_no'] = 0;
        $order_data['store_id'] = $this->config->get('config_store_id');
        $order_data['store_name'] = $this->config->get('config_name');
        $order_data['payment_company_id'] = $vergidairesi;
        $order_data['payment_tax_id'] = $vergiNo;
        if ($order_data['store_id']) {
            $order_data['store_url'] = $this->config->get('config_url');
        } else {
            if ($this->request->server['HTTPS']) {
                $order_data['store_url'] = HTTPS_SERVER;
            } else {
                $order_data['store_url'] = HTTP_SERVER;
            }
        }



        $order_data['order_date'] = date('Y-m-d H:i:s',strtotime(str_replace('/','-',$order['createDate'])));


        $order_data['comment'] = $this->config->get('n11_order_comment');

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

        $shipment_info=null;
        if (isset($order['itemList']['item']['productSellerCode'])) {

            $item = $order['itemList']['item']['shipmentInfo'];
            $shipment_info=$this->getShipmenInfo($item);

        } else {
            $item = $order['itemList']['item'][0]['shipmentInfo'];
            $shipment_info=$this->getShipmenInfo($item);
        }

        $order_data['shipping_info']=$shipment_info;




        $shipping_address = array(
            'shipping_firstname' => $first_name,
            'shipping_lastname' => $lastname,
            'shipping_company' => '',
            'shipping_address_1' => $order['shippingAddress']['address'] . ' ' . $order['shippingAddress']['city'] . ' ' . $order['shippingAddress']['district'],
            'shipping_address_2' => '',
            'shipping_city' => $order['shippingAddress']['district'],
            'shipping_town' => $order['shippingAddress']['district'],
            'shipping_postcode' => isset($order['shippingAddress']['postalCode']) ? $order['shippingAddress']['postalCode'] : '',
            'shipping_country_id' => 215,
            'shipping_zone_id' => $this->entegrasyon->findZoneId($order['shippingAddress']['city']),
            'shipping_method' => 'Kampanya No:'.$shipment_info['campaign_number'].'-'.'Kargo No:'.$shipment_info['shipping_code'].'-'.'Kargo Metodu'.$shipment_info['shipment_method'],
            'shipping_zone' => $order['shippingAddress']['city'],
            'shipping_country' => 'Türkiye',
            'shipping_address_format' => '{firstname} {lastname}
                {company}
                {address_1}
                {address_2}
                {postcode}, {city} - {zone} / {country}',
            'shipping_custom_field' =>$shipment_info,
            'shipping_code' => 'N11'

        );


        $shipment_method='N11';
        $shipment_method .= '- Platform Referans No:'.$order_data['order_id'];
        if($shipment_info['campaign_number'])$shipment_method.=' - Kampanya No:'.$shipment_info['campaign_number'];
        if($shipment_info['shipping_code'])$shipment_method.=' - Kargo No:'.$shipment_info['shipping_code'];
        if($shipment_info['shipment_method'])$shipment_method.=' - Kargo Metodu:'.$shipment_info['shipment_method'];
        $shipping_address['shipping_method']=$shipment_method;

        $vergi_bilgileri='';
        if($vergidairesi){

            $custom_payment_data=array('Vergi_Dairesi'=>$vergidairesi,'Vergi_No'=>$vergiNo);
            $vergi_bilgileri.='VD:'.$vergidairesi.'-'.'VN:'.$vergiNo;
        }else {
            $custom_payment_data=array('TC_Kimlik_No'=>$vergiNo);
            $vergi_bilgileri.='TC No:'.$tcID;
        }

        $payment_address = array(

            'payment_firstname' => $first_name,
            'payment_lastname' => $lastname,
            'payment_company' => '',
            'payment_address_1' => $order['billingAddress']['address'] . ' ' . $order['billingAddress']['city'] . ' ' . $order['billingAddress']['district'],
            'payment_address_2' => '',
            'payment_city' => $order['billingAddress']['city'],
            'payment_town' => $order['billingAddress']['district'],
            'payment_postcode' => '',
            'payment_country_id' => 215,
            'payment_country' => 'Türkiye',
            'payment_zone_id' => $this->entegrasyon->findZoneId($order['billingAddress']['city']),
            'payment_code' => 'N11',
            'payment_zone' => $order['billingAddress']['city'],
            'payment_address_format' => '{firstname} {lastname}
{company}
{address_1}
{address_2}
{postcode}, {city} - {zone} / {country}',
            'payment_custom_field'=>$custom_payment_data

        );





        $payment_method='N11 - '.$vergi_bilgileri;


        $payment_address['payment_method']=$payment_method;
        $order_data['payment_info']=$custom_payment_data;


        /* if($this->config->get('n11_setting_add_tc')){
             $payment_address['payment_custom_field'] = 'Vergi dairesi,:'.$vergidairesi.' - Vergi No:'.$vergiNo;
         }*/


        $order_data = array_merge($order_data, $shipping_address);
        $order_data = array_merge($order_data, $payment_address);

        $order_data['invoice_link']='';
        $order_data['affiliate_id'] = '';
        $order_data['commission'] = '';
        $order_data['marketing_id'] = '';
        $order_data['tracking'] = (string)'';
        $order_data['custom_field']=array('fatura_bilgileri'=>$custom_payment_data,'kargo_bilgileri'=>$shipment_info);



        $order_data['total'] = 0;

        $total = 0;
        $tax = 0;
        $subtotal = 0;



        if (isset($order['itemList']['item']['productSellerCode'])) {

            $product = $order['itemList']['item'];

            $totals = array();
            $tax += 0;
            $subtotal += (float)$order['itemList']['item']['sellerInvoiceAmount'];
            $total += (float)$order['itemList']['item']['sellerInvoiceAmount'];
            $order_data['totals'] = $totals;
            $order_data['products'][] = $this->getProductInfo($product);
            $order_data['total'] =  $order['billingTemplate']['sellerInvoiceAmount'];


        } else {


            foreach ($order['itemList']['item'] as $product) {



                $totals = array();


                $tax += 0;


                $subtotal += (float)$product['sellerInvoiceAmount'];


                $total += (float)$product['sellerInvoiceAmount'];


                $order_data['totals'] = $totals;

                $order_data['products'][] = $this->getProductInfo($product);

                $order_data['total'] = $order['billingTemplate']['sellerInvoiceAmount'];


            }
        }



        $order_data['order_status_id'] = $this->orderStatus;

        $totals = array();



        $totals[] = array(

            'code' => 'sub_total',
            'title' => 'Ara Toplam',
            'value' => $subtotal,
            'sort_order' => 1
        );


        if ($order['billingTemplate']['totalServiceItemOriginalPrice']){
            $totals[] = array(

                'code' => 'sub_total',
                'title' => 'Kargo Ücreti',
                'value' => $order['billingTemplate']['totalServiceItemOriginalPrice'],
                'sort_order' => 3
            );
        }



        /*if($order['billingTemplate']['totalSellerDiscount']){

                $totals[] = array(

                    'code' => 'sub_total',
                    'title' => 'Satıcı İndirimi',
                    'value' => $order['billingTemplate']['totalSellerDiscount'],
                    'sort_order' => 3
                );

            }

 */


        $totals[] = array(

            'code' => 'total',
            'title' => 'Toplam',
            'value' => $total + $order['billingTemplate']['totalServiceItemOriginalPrice'],
            'sort_order' => 9
        );


        $order_data['totals'] = $totals;



        // $order_id = $this->model_n11_order->addOrder($order_data);



        return $order_data;


    }


    private function getOrderAtrributes($attributes,$product_id)
    {


        $options=array();


        if(isset($attributes['attribute']['name'])){

//$option_id=$this->entegrasyon->getOptionIdByName($attributes['attribute']['name']);
            $option_value_id=$this->entegrasyon->getOptionValueIdByName($attributes['attribute']['value']);


            if($option_value_id){

                $option_info=$this->entegrasyon->getProductOptionInfoByProductIdAndOptionValueId($product_id,  $option_value_id);
                $options[]=array('product_option_id'=>$option_info['option_value_id'],'product_option_value_id'=>$option_info['product_option_value_id'],'name'=>$option_info['option_name'],'value'=>$option_info['value']);

            }

        }else {

            foreach ($attributes['attribute'] as $attribute) {

                //  $option_id=$this->entegrasyon->getOptionIdByName($attribute['name']);
                $option_value_id=$this->entegrasyon->getOptionValueIdByName($attribute['value']);


                if($option_value_id) {
                    $option_info = $this->entegrasyon->getProductOptionInfoByProductIdAndOptionValueId($product_id, $option_value_id);
                    $options[] = array('product_option_id' => $option_info['option_value_id'], 'product_option_value_id' => $option_info['product_option_value_id'], 'name' => $option_info['option_name'], 'value' => $option_info['value']);
                }
            }

        }
        return $options;


    }

    private $orderStatus;
    private function getProductInfo($product){

        $this->orderStatus=$product['status'];
        // $product_info = $this->entegrasyon->getProductByOrderModel($product['productSellerCode'],$product['productSellerCode'],$product['productName'],'n11');

        $model=isset($product['sellerStockCode'])?$product['sellerStockCode']:$product['productSellerCode'];

        $product_info = $this->entegrasyon->getProductByOrderModel($model,$model,$product['productName'],'n11');
        if($product_info['product']){
            $basemodel=$product_info['product']['model'];
        }else {
            $basemodel=$model;
        }

        if ($this->config->get('n11_setting_add_tax')){
            $kdv = $this->config->get('n11_setting_add_tax_val');
            $price=($product['price'] / ((100+$kdv)/100));
            $tax=(($product['price']-$price));
        }
        $product_data = array(

            'item_id' => $product['id'],
            'product_id'=>$product_info['status'] ?$product_info['product']['product_id']:0,
            'variant_id'=>$product_info['variant_id'],
            'status'=>$product['status'],
            'name' => $product['productName'],
            'model' => $basemodel,
            'barcode' =>$basemodel,
            'market_product_id'=>$product['productId'],
            'base_model'=>$product_info['product'] ?$product_info['product']['model']:0 ,
            'base_price'=>$product_info['product'] ?$product_info['product']['price']:0,
            'base_special'=>$product_info['product'] ? $product_info['product']['special']:0,
            'download' => '',
            'quantity' => $product['quantity'],
            'subtract' => '',
            'shipment_info'=>$this->getShipmenInfo($product['shipmentInfo']),
            'list_price' => (float)$product['price'],
            'price' => $product["dueAmount"]?(float)$product['dueAmount']:(float)$product['price'],
            //     'discount' => $product['totalMallDiscountPrice']? $product['totalMallDiscountPrice']:isset($product['totalSellerDiscount'])?$product['totalSellerDiscount']:"",
            'discount' => $product['totalMallDiscountPrice']? $product['totalMallDiscountPrice']:"",
            'kdv' =>isset($tax)?$tax:"",
            'total' => (float)$product['sellerInvoiceAmount'],
            'tax' => isset($tax)?$this->entegrasyon->priceFormat($tax ):"",
            'tax_range'=>$product['installmentChargeWithVAT'],
            'totaltax' => isset($tax)?$this->entegrasyon->priceFormat($tax * $product['quantity']):"",
            'reward' => ''
        );





        //   $options = isset($product['attributes']['attribute'])?$this->getOrderAtrributes($product['attributes'],$product_data['product_id']):array();

        $product_data['option']=array();

        return $product_data;

    }

    private function getShipmenInfo($item)
    {


        return array(

            'shipping_code'=>isset($item['campaignNumber']) ? $item['campaignNumber']:isset($item['shipmentCode'])?$item['shipmentCode']:'',
            'campaign_number'=>isset($item['campaignNumber']) ? $item['campaignNumber']:isset($item['shipmentCode'])?$item['shipmentCode']:'',
            'shipment_method'=>isset($item['shipmentCompany']['name'])?$item['shipmentCompany']['name']:''
        );




    }


    private function getN11Orders($status,$debug=false)
    {

        $debug=true;
        $filter_data = array(
            'status' => $status,
            'buyerName' => '',
            'orderNumber' => '',
            'productSellerCode' => '',
            'recipient' => '',
            'period' => array('startDate' => '', 'endDate' => date('d/m/Y'))

        );

        $pagingData = array(
            'currentPage' => 0,
            'pageSize' => 100);

        $post_data['request_data']=array('filter_data'=>$filter_data,'paging_data'=>$pagingData);
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('n11');
        $order_list=$this->entegrasyon->clientConnect($post_data,'get_orders','n11',$debug);



        return $order_list['result'];
    }

    public function updateStock($order)
    {

        $status = true;

        foreach ($order['products'] as $orderedproduct) {


            $model=$orderedproduct['model'];
            $product_info = $this->entegrasyon->getProductByOrderModel($model,'n11');
            $message = '';
            if ($product_info) {

                $product_info['quantity'] -= (int)$orderedproduct['quantity'];
                //  echo 'Şimdiki Stok'.$product_info['quantity'].'<br>';

                $this->entegrasyon->updateStock($product_info);

                $message = 'Seçeneksiz olan ürününüz bulundu ve stoğu güncellendi!';
                $status = true;

            }

        }
        return array('status' => $status, 'message' => $message);

    }


}