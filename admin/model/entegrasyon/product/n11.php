<?php

class ModelEntegrasyonProductN11 extends Model{


    public function sendProduct($product_data,$selected_attributes=array(),$debug=false)
    {

        $status=false;
        $message='';

        /*
                    if (isset($product_setting['selected_attributes'])) {
                        $selected_attributes = $product_data['product_setting']['selected_attributes'];
                    } else {
                        $selected_attributes = array();
                    }*/


        if(!$selected_attributes){

            $selected_attributes =  $product_data['attributes'];
        }


        //$product_images=$this->getImages($product_data['product_id'],$product_data['main_image']);


        if(!$product_data['defaults']['shipping_template']){
            $message.='En az bir teslimat şablonu seçmelisiniz.';
            return array('status'=>$status,'message'=>$message);
        }



        /* $this->load->model('entegrasyon/category');
         /*$category_attiribute =  $this->model_entegrasyon_category->getAttributesFromDb($product_data['category_id'], 'n11');

         if (!$category_attiribute){
             $product_data['manufacturer'] = '';

         }*/


        // print_r($product_data);return;

        $post_data['request_data']=$product_data;
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('n11');




        $send=$this->entegrasyon->clientConnect($post_data,'add_product','n11',$debug);



        if ($send['status']) {
            $n11_id=$send['result']['product']['id'];
            $status=true;
            $message.=$product_data['title'].' N11 Mağazanızda Başarıyla Listelendi.';
            $price=$product_data['sale_price'];
            $url=$this->entegrasyon->getMarketPlaceUrl('n11',$n11_id);
            $data=array('stock_id'=>$send['result']['product']['stockItems']['stockItem']['id'],'sale_status'=>$send['result']['product']['saleStatus'],'approval_status'=>1,'commission'=>$product_data['defaults']['commission'],'product_id'=>$n11_id,'price'=>$price,'url'=>$url);
            $this->entegrasyon->addMarketplaceProduct($product_data['product_id'],$data,'n11');
            return array('status'=>$status,'sale_status'=>$send['result']['product']['saleStatus'],'message'=>$message,'price'=>$price.' TL','url'=>$url);
            // $sonuc = $this->model_n11_product->addN11Product($product_id, $saveProduct->product);
            // $id = $saveProduct->product->id;
            //

        }else {

            return array('status'=>$status,'message'=>$send['message']);

        }

        //echo $n11_category_name;

    }


    public function getExtraData($product_data)
    {

        return $product_data;

    }

    public function deleteProduct($product_id)
    {
        $this->load->model("entegrasyon/general");

        $product_info = $this->entegrasyon->getProduct($product_id);
        $post_data['request_data']=$this->config->get('n11_setting_model_prefix').$product_info['model'];
        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('gg');
        $deleteProduct=$this->entegrasyon->clientConnect($post_data,'delete_product','n11');

        if ($deleteProduct['status']) {

            $this->entegrasyon->deleteMarketplaceProduct($product_id,'n11');
            return array('status' => true, 'message' => 'Ürün n11 mağazasından silindi.');

        } else  if($deleteProduct['message']=='ürün bulunamadı'){
            $this->entegrasyon->deleteMarketplaceProduct($product_id,'n11');
            return array('status' => true, 'message' => 'Ürün n11 mağazasından silindi.');

        }else {

            return array('status' => false, 'type'=>0, 'message' => $deleteProduct['message']);
        }

        // return array('status' => false, 'message' => $deleteProduct->result->errorMessage);
        //  $this->entegrasyon->deleteMarketplaceProduct($product_id,'n11');


    }



    private function getImages($product_id,$main_image){


        $product_images = $this->entegrasyon->getProductImages($product_id);
        $images['image'] = array();
        $images['image'][] = array(
            'url' => HTTPS_CATALOG . 'image/' . $main_image,
            'order' => 1);
        $sort = 2;
        foreach ($product_images as $product_image) {
            if (is_file(DIR_IMAGE . $product_image['image'])) {
                $images['image'][] = array(
                    'url' => HTTPS_CATALOG . 'image/' . $product_image['image'],
                    'order' => $sort
                );
                $sort++;
            }
        }

        return $images;
    }

    public function getProductTest($productSellerCode)
    {

        $this->load->model('entegrasyon/general');

        $post_data['request_data']=$productSellerCode;
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('n11');
        $result=$this->entegrasyon->clientConnect($post_data,'get_product','n11');
        // print_r($result);
    }

    private function vfz40($zwvm1, $I3bls) { goto ZZUA8; HrN1H: return array("\x73\164\x61\x74\x75\163" => $k4sJo, "\x6d\x65\163\163\141\147\145" => '', "\162\x65\163\165\x6c\x74" => $UFqiL); goto BhM_0; vHjHX: $UFqiL["\x73\x74\x61\x74\x75\x73"] = true; goto pgOgm; V5dLV: $SaJT6 = $P2HbE->xpath("\x2f\57\x72\145\x73\165\x6c\164"); goto NvRDF; ZTxMo: MH7b9: goto HrN1H; tgF4N: $UGFyr = curl_init(); goto GVI3n; bjTts: $UFqiL["\x70\141\x67\151\156\x67\104\141\x74\141"] = json_decode(json_encode($P2HbE->xpath("\x2f\x2f\160\x61\x67\x69\156\147\104\141\x74\141")), 1)[0]; goto t0x4e; qF96R: $e4Ee3 = curl_exec($UGFyr); goto STXyd; ZBMVJ: $P2HbE = simplexml_load_string($e4Ee3); goto V5dLV; NvRDF: if (!isset($SaJT6[0]->status)) { goto MH7b9; } goto ooW2A; STXyd: curl_close($UGFyr); goto ZBMVJ; GVI3n: curl_setopt_array($UGFyr, array(CURLOPT_URL => "\150\x74\164\160\x73\x3a\57\57\141\160\x69\x2e\x6e\61\61\x2e\143\157\x6d\x2f\x77\x73\x2f\x50\162\x6f\x64\165\143\164\x53\145\162\166\151\x63\x65\x2e\x77\163\x64\x6c", CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => '', CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "\x50\117\x53\124", CURLOPT_POSTFIELDS => "\74\163\x6f\141\160\145\x6e\166\72\x45\x6e\166\x65\x6c\157\x70\x65\x20\170\x6d\154\x6e\x73\72\163\x6f\141\160\x65\156\166\x3d\42\x68\x74\164\x70\x3a\57\x2f\x73\x63\150\145\155\141\163\x2e\x78\x6d\154\x73\157\x61\x70\56\x6f\x72\147\x2f\x73\x6f\x61\x70\x2f\145\x6e\166\145\x6c\157\x70\x65\x2f\42\x20\x78\x6d\154\156\x73\72\x73\x63\x68\x3d\x22\150\164\x74\160\72\57\x2f\x77\x77\167\x2e\156\61\61\56\143\157\155\x2f\167\163\57\163\143\150\145\x6d\141\163\x22\x3e\xd\xa\x20\x20\x20\x3c\x73\x6f\141\x70\145\x6e\x76\72\x48\x65\x61\x64\x65\x72\x2f\76\xd\12\x20\x20\x20\74\163\x6f\141\x70\145\156\166\x3a\x42\x6f\x64\171\x3e\15\xa\x20\x20\x20\40\40\40\x3c\163\143\x68\x3a\107\x65\x74\x50\x72\157\x64\x75\143\x74\114\x69\x73\164\122\145\x71\165\x65\163\164\x3e\15\12\40\40\40\x20\40\x20\40\x20\40\74\x61\165\164\x68\x3e\15\xa\40\x20\40\x20\x20\40\40\x20\x20\x20\40\x9\74\141\160\160\113\x65\x79\x3e" . $this->config->get("\156\x31\x31\x5f\x61\x70\x69\x5f\x6b\145\x79") . "\74\x2f\141\160\x70\x4b\145\x79\x3e\xd\xa\x20\40\x20\x20\x9\x9\74\x61\160\x70\x53\145\143\162\145\164\76" . $this->config->get("\x6e\x31\x31\x5f\x61\160\x69\x5f\x73\145\x63\x72\145\x74") . "\x3c\x2f\141\x70\160\x53\145\143\x72\145\164\x3e\xd\xa\x20\x20\40\40\x20\40\x20\x20\x20\x3c\57\x61\x75\164\150\76\xd\12\x20\40\40\x20\40\40\x20\x20\x20\74\x70\x61\x67\151\156\x67\104\141\164\x61\76\xd\xa\x20\40\x20\x20\40\40\40\x20\x20\x20\40\x20\74\143\x75\162\162\145\x6e\x74\x50\141\x67\145\76" . $zwvm1 . "\74\x2f\x63\x75\162\x72\x65\x6e\164\x50\x61\x67\145\x3e\15\12\40\x20\x20\x20\40\x20\x20\x20\x20\x20\40\40\74\160\141\x67\145\x53\151\x7a\x65\x3e" . $I3bls . "\x3c\57\x70\141\x67\145\x53\151\x7a\x65\x3e\xd\xa\40\40\x20\x20\x20\40\x20\x20\x20\x3c\57\160\141\x67\151\156\147\104\x61\x74\141\76\xd\xa\40\40\40\x20\40\x20\74\x2f\163\x63\x68\x3a\x47\145\164\120\162\157\144\x75\x63\x74\114\151\x73\x74\122\x65\161\x75\145\163\164\x3e\xd\12\40\x20\x20\74\57\x73\157\141\160\145\156\166\72\102\x6f\144\x79\x3e\xd\12\x3c\x2f\163\x6f\141\160\x65\x6e\x76\72\105\156\166\145\x6c\157\x70\145\76", CURLOPT_HTTPHEADER => array("\123\117\x41\x50\x41\x63\x74\151\157\x6e\x3a\x20\43\x50\117\x53\x54", "\x43\x6f\156\164\145\x6e\164\55\x54\171\x70\145\x3a\x20\164\145\x78\164\57\170\155\154", "\103\x6f\x6f\153\151\x65\x3a\40\145\x32\x66\71\x61\x66\146\143\65\x33\x32\146\63\x36\x63\66\141\x65\x63\61\143\x38\142\x64\x34\63\x33\145\62\x61\65\x39\75\x63\x30\146\67\x61\70\64\x37\x38\142\62\142\71\141\x32\x62\66\x65\x38\x39\63\64\x32\145\66\x37\64\63\62\x62\x37\x66"))); goto qF96R; pgOgm: $UFqiL["\160\162\x6f\144\165\x63\164\163"]["\160\162\x6f\144\x75\x63\x74"] = json_decode(json_encode($P2HbE->xpath("\x2f\x2f\x70\x72\x6f\144\165\143\164")), 1); goto bjTts; t0x4e: VwKhV: goto ZTxMo; ooW2A: $k4sJo = $SaJT6[0]->status; goto OmyWz; ZZUA8: $UFqiL = array(); goto tgF4N; OmyWz: if (!($k4sJo == "\x73\165\143\x63\x65\x73\x73")) { goto VwKhV; } goto vHjHX; BhM_0: }

    public function getProducts($data=array(),$debug=false)
    {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        error_reporting(E_ALL);

        $message='';
        $status=true;
        $total=0;
        $products=array();
        $this->load->model('entegrasyon/general');


        $post_data['request_data']=$data;
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('n11');



        $result=$this->vfz40($data['page'],$data['itemcount']);//$this->entegrasyon->clientConnect($post_data,'get_products','n11',$debug);
        // $result=$this->entegrasyon->clientConnect($post_data,'get_products','n11',$debug);

        //print_r($result);

        if($result['status']){

            $total=$result['result']['pagingData']['totalCount'];
            $stock_code='';
            if($result['result']['pagingData']['totalCount']>1){

                foreach ($result['result']['products']['product'] as $item) {

                    $stock_id=0;
                    $quantity=0;
                    if(isset($item['stockItems']['stockItem']['quantity'])){

                        $quantity=$item['stockItems']['stockItem']['quantity'];
                        $stock_id=$item['stockItems']['stockItem']['id'];

                    }else {

                        if(isset($item['stockItems']['stockItem'])){

                            foreach ($item['stockItems']['stockItem'] as $stockItem) {
                                $stock_id=$stockItem['id'];
                                $quantity+=$stockItem['quantity'];

                            } }
                    }

                    $saleStatus=0;
                    if($item['saleStatus'] == 2 && $item['approvalStatus']==1){
                        $saleStatus=1 ;}

                    $products[]=array(
                        'market_id'=>$item['id'],
                        'product_code'=>$item['id'],
                        'name' =>$item['title'],
                        'stock_id'=>$stock_id,
                        'model'=>$item['productSellerCode'],
                        'stock_code'=>$item['productSellerCode'],
                        'barcode'=>$item['productSellerCode'],
                        'list_price'=>$item['price'],
                        'quantity'=>$quantity,
                        'sale_price'=>$item['displayPrice'],
                        'sale_status'=>$saleStatus,
                        'approval_status'=>$item['approvalStatus']==1 ? 1:0,
                        'custom_data'=>$item

                    );

                    /*     if($item['title']=='Kadın Gümüş Beyaz Taşlı Bayan Yüzük'){
                             print_r($item);
                         }*/



                }

            } else {

                $status=false;
                $message='N11 Mağazanızda Ürün Bulunamadı!';

            }

        }else {

            $message=$result['message'];

        }


        //print_r($products); return;


        return array('status'=>$status,'total'=>$total,'message'=>$message,'products'=>$products);


    }


    public function getProduct_lastversion($product_id,$debug=false)
    {


        // $product_id='11d0asdasd419051545';

        $this->load->model('entegrasyon/general');

        $post_data['request_data']=array('product_id'=>$product_id);
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('n11');
        $result=$this->entegrasyon->clientConnect($post_data,'get_product','n11',$debug);




        $product=array();

        if(!$result['status']) {

            return $result;

        }



        if(!$result['status']) return $result;

        $item=$result['result']['product'];


        $stock_id=0;
        $quantity=0;
        if(isset($item['stockItems']['stockItem']['quantity'])){

            $quantity=$item['stockItems']['stockItem']['quantity'];
            $stock_id=$item['stockItems']['stockItem']['id'];

        }else {

            if(isset($item['stockItems']['stockItem'])){



                $stock_id=$item['stockItems']['stockItem']['id'];
                $quantity+=$item['stockItems']['stockItem']['quantity'];

            }
        }



        $market_id=isset($item['productContentId']) ? $item['productContentId'] : false;
        $product = array(
            'market_id'=>$item['id'],
            'name' =>$item['title'],
            'stock_id'=>$stock_id,
            'model'=>$item['productSellerCode'],
            'stock_code'=>$item['productSellerCode'],
            'barcode'=>$item['productSellerCode'],
            'list_price'=>$item['price'],
            'quantity'=>$quantity,
            'sale_price'=>$item['displayPrice'],
            'sale_status'=>$item['saleStatus'],
            'approval_status'=>$item['approvalStatus']

        );


        return $product;

    }
    public function getProduct($product_id,$debug=false)
    {


        // $product_id='11d0asdasd419051545';

        $this->load->model('entegrasyon/general');

        $post_data['request_data']=array('sellerCode'=>$product_id);
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('n11');
        // $post_data['request_data']['sellerCode'][0]="283985-YG-8446LBR";
        //     $post_data['request_data']['sellerCode'][1]="539757-HN-6151LBR";

        $result=$this->entegrasyon->clientConnect($post_data,'get_product','n11',$debug);




        $product=array();

        if(!$result['status']) {

            return $result;

        }



        if(!$result['status']) return $result;





        if(($result['result']['content'][1])){

            $product =array();
            foreach ($result['result']['content'] as $item) {
                $stock_id=0;
                $quantity=0;
                if(isset($item['stockItems']['stockItem']['quantity'])){

                    $quantity=$item['stockItems']['stockItem']['quantity'];
                    $stock_id=$item['stockItems']['stockItem']['id'];

                }else {

                    if(isset($item['stockItems']['stockItem'])){



                        $stock_id=$item['stockItems']['stockItem']['id'];
                        $quantity+=$item['stockItems']['stockItem']['quantity'];

                    }
                }
                $market_id=isset($item['productContentId']) ? $item['productContentId'] : false;

                if (isset($item['id'])){
                    $product[] = array(
                        'market_id'=>$item['id'],
                        'name' =>$item['title'],
                        'stock_id'=>$stock_id,
                        'model'=>$item['productSellerCode'],
                        'stock_code'=>$item['productSellerCode'],
                        'barcode'=>$item['productSellerCode'],
                        'list_price'=>$item['price'],
                        'quantity'=>$quantity,
                        'sale_price'=>$item['displayPrice'],
                        'sale_status'=>$item['saleStatus'],
                        'approval_status'=>$item['approvalStatus'],
                        'custom_data'=>$item

                    );
                }




            }

        }else{


            $item=$result['result']['product'];


            $stock_id=0;
            $quantity=0;
            if(isset($item['stockItems']['stockItem']['quantity'])){

                $quantity=$item['stockItems']['stockItem']['quantity'];
                $stock_id=$item['stockItems']['stockItem']['id'];

            }else {

                if(isset($item['stockItems']['stockItem'])){



                    $stock_id=$item['stockItems']['stockItem']['id'];
                    $quantity+=$item['stockItems']['stockItem']['quantity'];

                }
            }



            $market_id=isset($item['productContentId']) ? $item['productContentId'] : false;
            $product = array(
                'market_id'=>$item['id'],
                'name' =>$item['title'],
                'stock_id'=>$stock_id,
                'model'=>$item['productSellerCode'],
                'stock_code'=>$item['productSellerCode'],
                'barcode'=>$item['productSellerCode'],
                'list_price'=>$item['price'],
                'quantity'=>$quantity,
                'sale_price'=>$item['displayPrice'],
                'sale_status'=>$item['saleStatus'],
                'approval_status'=>$item['approvalStatus']

            );
        }




        return $product;

    }


    public function getMarketPlaceProduct($product_id,$category_id,$manufacturer_id,$debug=false)
    {

        $this->load->model('entegrasyon/general');

        $post_data['request_data']=array('product_id'=>$product_id);
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('n11');
        $result=$this->entegrasyon->clientConnect($post_data,'get_product','n11',$debug);





        if($debug){

            print_r($result);
            return ;

        }

        if(!$result['status']){

            return $result;
        }

        $language_id = $this->config->get('config_language_id');

        if(!$result['status']) {

            return $result;
        }


        $product=$result['result']['product'];
        $product_description = array($language_id => array(
            'name' => $product['title'],
            'description' => $product['description'],
            'meta_title' => $product['title'],
            'meta_description' => $product['title'],
            'meta_keyword' => $product['title'],
            'tag' =>array()// implode(',', explode(' ', $product['title'])),

        ));


        if (isset($product['images']['image']['url'])) {

            $image = $product['images']['image'];
            $images[] = array(

                'image' => $this->entegrasyon->getImage($image['url'], $product['title']),
                'sort_order' => 0
            );
        } else {
            foreach ($product['images']['image']as $key => $image) {
                $images[] = array(

                    'image' => $this->entegrasyon->getImage($image['url'], $product['title'] . '_' . $key),
                    'sort_order' => 0
                );
            }
        }


        $stockData = $this->getOptionsFromN11($product['stockItems']['stockItem']);


        $price = $product['displayPrice'];





        $product_price=$this->getProductPrice($product);



        //$special=$special_price?($special_price[0]['price']==$price?array():$special_price):array();

        $product_data = array(
            'model' => $product['productSellerCode'],
            'sku' => '',
            'upc' => '',
            'ean' => '',
            'jan' => '',
            'isbn' => '',
            'mpn' => $stockData['gtin'],
            'location' => '',
            'quantity' => $stockData['quantity'],
            'minimum' => 1,
            'keyword' => $this->entegrasyon->createSEOKeyword($product['title']).".html",
            'subtract' => 1,
            'image' => $images[0]['image'],
            'product_image' => $images,
            'product_category' =>$category_id?array($category_id):array(),
            'product_special'=>array(),
            'stock_status_id' => 2,
            'date_available' => '',
            'manufacturer_id' => $manufacturer_id?$manufacturer_id:"",
            'shipping' => 1,
            'price' => $product_price['list_price'],
            'points' => '',
            'length' => '',
            'weight' => '',
            'width' => '',
            'height' => '',
            'weight_class_id' => 1,
            'length_class_id' => 1,
            'height_class_id' => 1,
            'status' => 1,
            'tax_class_id' => '',
            'sort_order' => '',
            'product_description' => $product_description,
            'product_store' => array(0)
        );

        if($product_price['list_price']>$product_price['sale_price']){

            $product_data['product_special'] = array(0=>array(
                'customer_group_id'=>$this->config->get('config_customer_group_id'),
                'priority'=>0,
                'date_start'=>'',
                'date_end' =>'',
                'price'=>$product_price['sale_price']

            ));
        }


        if($this->config->get('n11_setting_barkod_place')){
            $barcode_place = $this->config->get('n11_setting_barkod_place');
            $product_data[$barcode_place] = $product['productSellerCode'];

        }else{
            $product_data['ean'] = $product['productSellerCode'];

        }


        $url=$this->entegrasyon->getMarketPlaceUrl('n11',$product['id']);

        $marketplace_product_data=array('stock_id'=>$stockData['stock_id'],'sale_status'=>$product['saleStatus'],'approval_status'=>$product['approvalStatus'],'commission'=>0,'product_id'=>$product['id'],'price'=>$price,'url'=>$url);

        $marketplace_product_data['n11_category_id']=$product['category']['id'].'|'.$product['category']['fullName'];
        $marketplace_product_data['product_id']=$product_id;
        return array('status'=>true,'product_data'=>$product_data,'marketplace_product_data'=>$marketplace_product_data);


    }

    public function getOptionsFromN11($n11Options)
    {

        $data = array();

        $quantity = 0;
        $gtin = 0;
        $stock_id=0;



        if (isset($n11Options['quantity'])) {

            $quantity = $n11Options['quantity'];
            $stock_id= $n11Options['id'];
            $gtin = isset($n11Options['gtin']) ? $n11Options['gtin'] : 0;

        } else {

            // if(!$stock_id)$stock_id= $n11Options['id'];

            foreach ($n11Options as $n11Option) {

                $quantity += $n11Option['quantity'] ? $n11Option['quantity'] : 0;
                $gtin = isset($n11Options['gtin']) ? $n11Options['gtin'] : 0;

            }

        }

        $data['quantity'] = $quantity;
        $data['gtin'] = $gtin;
        $data['stock_id'] = $stock_id;

        return $data;

    }


    public function getProductPrice($product)
    {
        if ($product['currencyType'] == 2){

            $n11Options=$product['stockItems']['stockItem'];

            if (isset($n11Options['currencyAmount'])) {

                $list_price = $this->currency->format($n11Options['currencyAmount'], "USD","",false);
                $sale_price = $this->currency->format($n11Options['displayPrice'], "USD","",false);

            } else {

                // if(!$stock_id)$stock_id= $n11Options['id'];

                // foreach ($n11Options as $n11Option) {
                $list_price = $this->currency->format($n11Options[0]['currencyAmount'], "USD","",false);
                $sale_price = $this->currency->format($n11Options[0]['displayPrice'], "USD","",false);



                // }

            }

        }else{
            $n11Options=$product['stockItems']['stockItem'];

            if (isset($n11Options['currencyAmount'])) {

                $list_price = $n11Options['currencyAmount'];
                $sale_price = $n11Options['displayPrice'];

            } else {

                // if(!$stock_id)$stock_id= $n11Options['id'];

                // foreach ($n11Options as $n11Option) {

                $list_price = $n11Options[0]['currencyAmount'];
                $sale_price = $n11Options[0]['displayPrice'];

                // }

            }
        }







        return array('list_price'=>$list_price,'sale_price'=>$sale_price);



        // return $product_special;


    }

    // }




}