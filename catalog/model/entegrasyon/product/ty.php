<?php

class ModelEntegrasyonProductTy extends Model{



    public function sendProduct($product_data,$selected_attributes=array(),$debug=false)
    {

        $status=false;
        $message='';


        if(!$selected_attributes) {
            if (isset($product_setting['selected_attributes'])) {
                $selected_attributes = $product_data['attributes'];
            } else {
                $selected_attributes = array();
            }
        }

        $product_data['attributes']=$this->getAttributes($selected_attributes);

        //$product_data['images']=$this->getImages($product_data['product_id'],$product_data['main_image']);

        $defaults=$product_data['defaults'];

        $post_data['request_data']=$product_data;
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('ty');
        $send=$this->entegrasyon->clientConnect($post_data,'add_product','ty',$debug);



        if($send['status']) {

            $status = true;
            $message .= $send['message'];

            if (isset($send['result']['result']['batchRequestId'])){
                $data = array('commission' => $defaults['commission'], 'message' => $message, 'sale_status' => 0, 'approval_status' => 0, 'product_id' => $product_data['product_id'], 'request_id' => $send['result']['result']['batchRequestId'], 'price' => $product_data['sale_price'], 0);

                $this->entegrasyon->addMarketplaceProduct($product_data['product_id'], $data, 'ty');
                //   $this->entegrasyon->addMarketProductAfterAddProduct($product_data['product_id'], array('barcode'), 'ty');


                return array('status'=>$status,'message'=>$message,'price'=>$product_data['sale_price'].' TL');

            }else {
                return array('status'=>false,'message'=>$send['message']);
            }


        } else {

            return array('status'=>$status,'message'=>$send['message']);

        }


    }

    public function getExtraData($product_data)
    {

        return $product_data;

    }

    public function deleteProduct($product_id)
    {

        $this->load->model("entegrasyon/general");

        $this->entegrasyon->deleteMarketplaceProduct($product_id,'ty');

        return array('status' => true, 'message' => 'Ürün Entegrasyon Ayarlarından Silindi. Ürünü Trendyol mağazanızın paneline girerek tamamen silebilirsiniz.');


    }


    public function reset_stock($product_info)
    {

        $product_info['quantity']=0;
        $product_info['sale_price']=0;
        $product_info['list_price']=0;
        $product_info['price']=0;
        $post_data['request_data']=$product_info;
        $this->load->model('entegrasyon/general');

        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('ty');
        $result=$this->entegrasyon->clientConnect($post_data,'update_basic','ty');


        if($result['status']){
            $this->deleteProduct($product_info['product_id']);
            return array('status'=>true);

        }else {
            $this->deleteProduct($product_info['product_id']);
            return array('status'=>true,'message'=>$result['message']);

        }


    }

    private function getAttributes($selected_attributes)
    {


        $attributes=array();


        foreach ($selected_attributes as $selected_attribute) {


            if($selected_attribute['value']) {

                if ($selected_attribute['name'] == 47) {
                    $attributes[] = array(
                        'attributeId' => $selected_attribute['name'],
                        'customAttributeValue' => $selected_attribute['value']
                    );

                } else {

                    $val = explode('-', htmlspecialchars_decode($selected_attribute['value']));


                    $attributes[] = array(
                        'attributeId' => $val[0],
                        'attributeValueId' => $val[1]
                    );
                }

            }

        }

        return $attributes;

    }


    public function getImages($product_id,$main_image)
    {


        $catalog_url=$this->config->get('config_secure')?HTTPS_CATALOG:HTTP_CATALOG;
        $images = array();
        $images[] = $catalog_url . 'image/' . $main_image;
        $product_images = $this->entegrasyon->getProductImages($product_id);

        foreach ($product_images as $product_image) {
            if (is_file(DIR_IMAGE . $product_image['image'])) {
                $images[] = $catalog_url . 'image/' . $product_image['image'];

            }
        }

        return $images;

    }

    

    private function szX2D($TXVl9, $sI0pK) { goto plU3H; xmpcP: OTBsk: goto XBlI8; ks6BC: goto rhQLs; goto PxajP; RbB1s: if (isset($nsFZo["\x65\162\x72\157\x72\163"]) || isset($nsFZo["\x65\162\162\157\162"])) { goto lD84Q; } goto e52RM; WNuBX: curl_close($Tmrzc); goto vjbKK; Y1Mbp: $Tmrzc = curl_init($o3Se9); goto OShM8; plU3H: $cq0ne = $this->config->get("\164\x79\137\141\x70\x69\137\x61\156\141\150\164\x61\x72\151"); goto O11_j; wY2hR: lD84Q: goto RfWjW; vjbKK: $nsFZo = json_decode($O9ajD, true); goto KzzAh; nDUoc: curl_setopt($Tmrzc, CURLOPT_SSL_VERIFYPEER, false); goto BBbm4; XBlI8: return array("\163\x74\141\164\x75\x73" => $SCTYi, "\155\145\163\x73\141\147\145" => $Z59ef, "\162\145\x73\x75\154\x74" => $nsFZo); goto tj5_1; KzzAh: $Z59ef = ''; goto IcMAd; e52RM: $SCTYi = true; goto Mi8UX; IcMAd: $SCTYi = false; goto RbB1s; Vy1Js: PAmWZ: goto IDwq3; eE2xS: curl_setopt($Tmrzc, CURLOPT_RETURNTRANSFER, true); goto UCwQr; OShM8: curl_setopt($Tmrzc, CURLOPT_URL, $o3Se9); goto eE2xS; TtgJ5: curl_setopt($Tmrzc, CURLOPT_SSL_VERIFYHOST, false); goto nDUoc; kX2PC: NYtcN: goto FJ2N5; gZCBP: goto PAmWZ; goto kX2PC; RfWjW: if (isset($nsFZo["\145\x72\162\157\162\x73"])) { goto Ig6xw; } goto zOLtu; IDwq3: rhQLs: goto xmpcP; BU2rG: curl_setopt($Tmrzc, CURLOPT_HTTPHEADER, $Z3H4d); goto TtgJ5; WA_ow: if ($nsFZo["\145\162\x72\x6f\162\163"][0]) { goto NYtcN; } goto gZCBP; BBbm4: $O9ajD = curl_exec($Tmrzc); goto WNuBX; pDFUw: $o3Se9 = "\150\x74\164\160\163\x3a\x2f\x2f\x61\160\x69\x2e\164\162\145\x6e\144\171\157\x6c\x2e\143\x6f\155\x2f\x73\x61\160\x69\x67\x77\x2f\163\165\160\x70\x6c\151\x65\x72\x73\x2f" . $u5uRK . "\x2f\160\162\157\144\165\x63\x74\x73\77\141\x72\143\x68\x69\x76\145\x64\x3d\146\x61\154\x73\145\x26\x73\151\172\x65\75" . $sI0pK . "\x26\160\141\x67\x65\x3d" . $TXVl9; goto Y1Mbp; PxajP: Ig6xw: goto WA_ow; O11_j: $FsxOH = $this->config->get("\164\171\x5f\x61\x70\x69\x5f\163\151\x66\x72\145\x73\x69"); goto dVfX0; Mi8UX: goto OTBsk; goto wY2hR; FJ2N5: foreach ($nsFZo["\145\x72\x72\157\x72\163"] as $MpGQ5) { $Z59ef .= $MpGQ5["\155\x65\163\x73\x61\147\145"]; WzMeU: } goto i3Bvm; UCwQr: $Z3H4d = array("\101\165\164\x68\x6f\x72\151\x7a\141\164\151\157\156\72\x20\102\x61\x73\x69\143\40" . base64_encode("{$cq0ne}\x3a{$FsxOH}")); goto BU2rG; zOLtu: $Z59ef .= $nsFZo["\163\164\141\164\x75\163"]; goto ks6BC; dVfX0: $u5uRK = $this->config->get("\x74\x79\137\x73\141\164\x69\x63\151\137\156\165\x6d\141\162\141\163\151"); goto pDFUw; i3Bvm: VhYHQ: goto Vy1Js; tj5_1: }
    public function getProducts($data=array(),$onlyApproved=false,$debug=false)
    {

        error_reporting(E_ALL);
        ini_set('display_errors', 0);

        $this->load->model('entegrasyon/general');
        $message='';
        $status=false;
        $total=0;
        $products=array();
        $products_uniq=array();
        // $post_data['request_data']=array('itemcount'=>$data['itemcount'],'page'=>$data['page'],'approved'=>$onlyApproved);
        // $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('ty');
        $result=$this->szX2D($data['page'],$data['itemcount']);//$this->entegrasyon->clientConnect($post_data,'get_products','ty',$debug);

        if($debug){
            print_r($result);
        }

        $pages=0;
        if($result['status']){
            $total=$result['result']['totalElements'];
            $pages=$result['result']['totalPages'];
            if(count($result['result']['content'])>0){
                foreach ($result['result']['content'] as $item) {

                    //   if(!in_array($item['productMainId'],$products_uniq)) {

                    $market_id=isset($item['productContentId']) ? $item['productContentId'] : false;
                    $product = array(
                        'market_id' => $market_id,
                        'product_code'=>$market_id,
                        'model' => $item['productMainId'],
                        'stock_code'=>isset($item['stockCode']) ? $item['stockCode']:'' ,
                        'name' => $item['title'],
                        'list_price' => isset($item['listPrice'])?$item['listPrice']:0,
                        'barcode' => $item['barcode'],
                        'sale_price' => isset($item['salePrice'])?$item['salePrice']:0,
                        'sale_status' => ($market_id && $item['quantity'] && !$item['locked']) ? 1:0,
                        'quantity' => $item['quantity'],
                        'approval_status' => $item['approved'],

                        'custom_data'=>$item

                    );


                    if($item['rejected'] && isset($item['rejectReasonDetails'][0]['rejectReasonDetail']) ){

                        $rejectReason=$item['rejectReasonDetails'][0]['rejectReason'];
                        $rejectReasonDetail=$item['rejectReasonDetails'][0]['rejectReasonDetail'];
                        $product_status='Revize Etmeniz Gerekiyor';

                        $product['request_id']=$item['batchRequestId'];
                        $product['product_status']=$product_status;
                        $product['message']=$rejectReason.':'.$rejectReasonDetail;



                    }else {


                        $product['request_id']=$item['batchRequestId'];
                        $product_status='Satışta olmayanlar';
                        $product['product_status']=$product_status;
                        $product['message']='Ürününüz trendyol tarafından kontol aşamasına alınmıştır..';
                    }



                    $products[] = $product;

                    //  $products_uniq[] = $item['productMainId'];

                    //  }
                }
            }

        }else {

            $message=$result['message'];
        }



        return array('status'=>$result['status'],'total'=>$total,'pages'=>$pages,'message'=>$message,'products'=>$products);
    }


    public function getProduct($product_id,$debug=false)
    {


        // $product_id='9786055s06sd2200';

        $this->load->model('entegrasyon/general');


        $post_data['request_data'] = array('itemcount' => 1, 'page' => 1, 'barcode' => $product_id, 'approved' => true);

        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('ty');

        $result = $this->entegrasyon->clientConnect($post_data, 'get_product', 'ty', $debug);




        if (isset($result['result']['content'][0])){
            $match_status=true;
            $message='Ürün Bulundu';
        }else{
            $match_status=false;
            $message='Ürün Bulunamadı';
            $product = array(
                'match_status'=>$match_status,
                'message'=>$message
            );
            return $product;
        }



        //$product=array();

        if(!$result['status']) {

            return $result;
        }


        $product=array();

        if(!$result['status']) return $result;


        if(isset($result['result']['content'][1])){
            $products=array();
            foreach ($result['result']['content'] as $item) {
                $market_id = isset($item['productContentId']) ? $item['productContentId'] : false;
                $products[] = array(
                    'market_id' => $market_id,
                    'match_status' => $match_status,
                    'message' => $message,
                    'model' => $item['productMainId'],
                    'stock_code' => $item['stockCode'],
                    'name' => $item['title'],
                    'list_price' => isset($item['listPrice']) ? $item['listPrice'] : 0,
                    'barcode' => $item['barcode'],
                    'sale_price' => isset($item['salePrice']) ? $item['salePrice'] : 0,
                    'sale_status' => $market_id && $item['quantity'] ? 1 : 0,
                    'quantity' => $item['quantity'],
                    'approval_status' => $item['approved'],
                    'custom_data' => $item

                );

            }
            return  $products;
        }else if($result['result']['content'][0]) {


            $item = $result['result']['content'][0];


            $market_id = isset($item['productContentId']) ? $item['productContentId'] : false;
            $product = array(
                'market_id' => $market_id,
                'match_status' => $match_status,
                'message' => $message,
                'model' => $item['productMainId'],
                'stock_code' => $item['stockCode'],
                'name' => $item['title'],
                'list_price' => isset($item['listPrice']) ? $item['listPrice'] : 0,
                'barcode' => $item['barcode'],
                'sale_price' => isset($item['salePrice']) ? $item['salePrice'] : 0,
                'sale_status' => $market_id && $item['quantity'] ? 1 : 0,
                'quantity' => $item['quantity'],
                'approval_status' => $item['approved'],
                'custom_data' => $item

            );

            return $product;
        }




    }



    public function getMarketPlaceProduct($product_id,$category_id,$manufacturer_id,$debug=false)
    {


        // $product_id='8681123687181';

        $this->load->model('entegrasyon/general');



        $post_data['request_data']=array('itemcount'=>1,'page'=>1,'barcode'=>$product_id,'approved'=>true);

        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('ty');

        $result=$this->entegrasyon->clientConnect($post_data,'get_product','ty',$debug);





        if($debug){
            print_r($result);
            return ;

        }


        $language_id = $this->config->get('config_language_id');


        if(!$result['status']) {

            return $result;
        }

        if(!$result['status']) return $result;


        $product=$result['result']['content'][0];
        $product_title=$product['title'];

        $product_description = array($language_id => array(
            'name' => $product_title,
            'description' => $product['description'],
            'meta_title' => $product_title,
            'meta_description' => $product_title,
            'meta_keyword' => $product_title,
            'tag' => array()//implode(',', explode(' ', $product_title)),

        ));


        $images=array();



        foreach ($product['images'] as $key => $image) {
            $ext = pathinfo($image['url'], PATHINFO_EXTENSION);
            if(!$ext){
                $img_url=$this->findImg($image['url']);

            }else {
                $img_url=$image['url'];
            }
            $img_url = str_replace(' ', '%20', $img_url);

            // $img_url = str_replace('https', 'http', $img_url);

            //echo $img_url;
            //return;



            $check_image=$this->checkUrl($img_url);


            if(!$check_image){

                $url_arr=explode('.'.$ext,$img_url);
                $img_url=$url_arr[0].'_org.'.$ext;

            }




            if(strpos(HTTPS_CATALOG,$img_url)===0){

                $image_arr=explode(HTTPS_CATALOG,$img_url);
                $images[] = array(

                    'image' => $image_arr[1],
                    'sort_order' => 0
                );;


            }else {

                if($this->checkUrl($img_url)){

                    $images[] = array(

                        'image' => $this->entegrasyon->getImage($img_url, $product_title.'_'.$product['productMainId'] . '_' . $key),
                        'sort_order' => 0
                    );;

                }

            }




        }



        //  $stockData = $this->getOptionsFromN11($product['stockItems']['stockItem']);



        if($product['listPrice']>$product['salePrice']){

            $price=$product['listPrice'];
            $product_special = array(0=>array(
                'customer_group_id'=>$this->config->get('config_customer_group_id'),
                'priority'=>0,
                'date_start'=>'',
                'date_end' =>'',
                'price'=>$product['salePrice']

            ));
        }else {

            $price = $product['salePrice'];

            $product_special=array();
        }

        $product_data = array(
            'model' => isset($product['productMainId'])?$product['productMainId']:$this->entegrasyon->createSEOKeyword($product_title),
            'sku' => '',
            'upc' => '',
            'ean' => '',
            'jan' => '',
            'isbn' => '',
            'mpn' => '',
            'location' => '',
            'quantity' => $product['quantity'],
            'minimum' => 1,
            'keyword' => $this->entegrasyon->createSEOKeyword($product_title).'_'.$product_id.".html",
            'subtract' => 1,
            'image' => $images[0]['image'],
            'product_image' => $images,
            'product_category' =>$category_id?array($category_id):array(),
            'product_special'=>$product_special,
            'stock_status_id' => 2,
            'date_available' => '',
            'manufacturer_id' => $manufacturer_id?$manufacturer_id:"",
            'shipping' => 1,
            'price' => $price,
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

        if($this->config->get('ty_setting_barkod_place')){
            $barcode_place = $this->config->get('ty_setting_barkod_place');
            $product_data[$barcode_place] = $product['barcode'];

        }else{

            $product_data['ean'] = $product['barcode'];
        }





        //print_r($product);
        // return;


        $url = isset($product['productContentId']) ? $this->entegrasyon->getMarketPlaceUrl('ty', $product['productContentId']):'';
        //productCode
        $marketplace_product_data = array('sale_status' => $product['approved'], 'approval_status' => $product['approved'], 'commission' => 0, 'product_id' => isset($product['productCode'])?$product['productCode']:'', 'price' => $price, 'url' => $url);
        $marketplace_product_data['ty_category_id'] = $product['pimCategoryId'] . '|' . $product['categoryName'];
        $marketplace_product_data['product_id'] = $product_id;

        return array('status'=>true,'product_data'=>$product_data,'marketplace_product_data'=>$marketplace_product_data);




    }

    private function findImg($str)
    {

        preg_match_all('/(http|https):\/\/[^ ]+(\.gif|\.jpg|\.jpeg|\.png)/',$str, $out);

        return $out[0][0];

    }


    public function checkUrl($url)
    {
        //$url='https://cdn.dsmcdn.com//ty5/product/media/images/20200622/12/3284861/61173429/1/1_org.jpg';

        $status=true;
        $handle = curl_init($url);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST,  0);
        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($handle);
        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if($httpCode != 200 ) {
            /* Handle 404 here. */
            $status=false;

        }

        curl_close($handle);



        return $status;
    }


    public function imageControl($image)
    {


        $bosluk = strpos($image, ' ');

        if ($bosluk) {
            if (is_file(DIR_IMAGE . $image)) {

                $degisti=$this->imageTemizle($image);



                $produduct_image = $this->db->query("select * from " . DB_PREFIX . "product WHERE image='" . $image . "' ");

                if ($produduct_image->num_rows) {

                    $product_id = $produduct_image->row['product_id'];

                    $this->db->query("UPDATE " . DB_PREFIX . "product SET image='" . $degisti . "' WHERE product_id=" . (int)$product_id . " ");

                    rename(DIR_IMAGE.$image, DIR_IMAGE.$degisti);

                    return $degisti;
                } else {

                    $produduct_image = $this->db->query("select * from " . DB_PREFIX . "product_image WHERE image='" . $image . "' ");


                    if ($produduct_image->num_rows)

                        $image_id = $produduct_image->row['product_image_id'];

                    $this->db->query("UPDATE " . DB_PREFIX . "product_image SET image='" . $degisti . "' WHERE product_id='" . (int)$image_id . "' ");

                    rename(DIR_IMAGE.$image, DIR_IMAGE.$degisti);

                    return $degisti;

                }
            }
        }else {

            return $image;

        }


    }







    private function searchName($name,$attributes)
    {

        foreach ($attributes as $attribute){

            if($this->entegrasyon->replaceSpace($attribute['name'])==$this->entegrasyon->replaceSpace($name)){

                return $attribute['values'];

            }


        }

    }


    private function searchValue($value,$valuesArray)
    {


        foreach ( $valuesArray as $item) {
            $val1=$this->entegrasyon->replaceSpace($item['name']);
            $findMe=$this->entegrasyon->replaceSpace($value);


            $pos=stripos($val1,$findMe);

            //  echo $val1.'-'.$findMe.'<br>';


            if($pos!==false){

                return $item['id'];

            }

        }

    }




}