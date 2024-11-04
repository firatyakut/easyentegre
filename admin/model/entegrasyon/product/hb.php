<?php

class ModelEntegrasyonProductHb extends Model {

    public function sendProduct($product_data,$selected_attributes=array(),$debug=false)
    {

        $status=false;
        $message='';

        /*
        $variants= $this->getVariants($product_data['product_id'],$product_data['category_id']);
        if($variants) {
            if($required_specs) {
                foreach ($required_specs as $row => $required_spec) {

                    if (in_array($required_spec, $variants['selected_attributes'])) {
                        unset($required_specs[$row]);
                    }
                }
            }
        }

*/


        if(!$selected_attributes) {
            if (isset($product_setting['selected_attributes'])) {
                $selected_attributes = $product_data['attributes'];
            } else {
                $selected_attributes = array();
            }
        }

        // $product_data['images']=$this->getImages($product_data['product_id'],$product_data['main_image']);
        $defaults=$product_data['defaults'];
        $post_data['request_data']=$product_data;
        $send['result']['errors'][]='hata 1';
        $send['result']['errors'][]='hata 2';

        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('hb');
        $send=$this->entegrasyon->clientConnect($post_data,'add_product','hb',$debug);


        if($send['status']){

            $status=true;
            $message.='Ürün Hepsiburada Mağazanıza Başarıyla Gönderildi, Ürününüz Hepsiburada tarafından inceleme yapıldıktan sonra satışa açılacaktır';

            $data=array('commission'=>$defaults['commission'],'sale_status'=>0,'approval_status'=>0, 'status'=>$send['result']['product_status'],'request_id'=>$send['result']['trackingId'],'product_id'=>$product_data['product_id'],'price'=>$product_data['sale_price'],0);
            $this->entegrasyon->addMarketplaceProduct($product_data['product_id'],$data,'hb');

            if($send['result']['errors']){
                $errors='';
                foreach ($send['result']['errors'] as $error) {

                    $errors.=','.$error;
                }
                $message='Ürün Gönderildi Ancak üründe şu hatalar tespit edildi; '.$errors.' Hepsiburada panelinden hataları düzetiniz.';
            }
            return array('status'=>$status,'message'=>$message,'price'=>$product_data['sale_price'].' TL');


        } else {

            return array('status'=>$status,'message'=>$send['message']);

        }

    }

    public function getExtraData($product_data)
    {

        return $product_data;

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


    public function deleteProduct($product_id)
    {

        $this->load->model("entegrasyon/general");

        $this->entegrasyon->deleteMarketplaceProduct($product_id,'hb');

        return array('status' => true, 'message' => 'Ürün Entegrasyon Ayarlarından Silindi. Ürünü Trendyol mağazanızın paneline girerek tamamen silebilirsiniz.');


    }

    public function getFromMarkethb($page,$size)
    {

        $offset=$page*$size;
        $url = "https://listing-external.hepsiburada.com/listings/merchantid/".$this->config->get('hb_merchant_id')."?offset=".$offset."&limit=".$size;

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

        $resp = curl_exec($curl);
        curl_close($curl);

        $productdata=json_decode($resp,1);

        $return=array();
        if(isset($productdata['listings'])) {
            $return['Listings']['Listing'] = $productdata['listings'];
            $return['TotalCount'] = $productdata['totalCount'];
            $return['limit'] = $productdata['limit'];
            $return['offset'] = $productdata['offset'];
        }

        return array('status'=>true,'message'=>'','result'=>$return);


    }


    public function getProducts($data=array(),$debug=false)
    {

        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        $this->load->model('entegrasyon/general');

        $message='';
        $status=true;
        $total=0;
        $products=array();
        $post_data['request_data']=array('itemcount'=>$data['itemcount'],'page'=>$data['page']);
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('hb');


        $debug=false;
        $result=$this->getFromMarkethb($data['page'],$data['itemcount']);//$this->entegrasyon->clientConnect($post_data,'get_products','hb',$debug);



        if(isset($result['result']['Listings']['Listing'])){

            $total=$result['result']['TotalCount'];

            if(isset($result['result']['Listings']['Listing']['hepsiburadaSku'])){

                $item=$result['result']['Listings']['Listing'];

                $products[]=array(
                    'market_id'=>$item['hepsiburadaSku'],
                    'model'=>$item['merchantSku'],
                    'product_code'=>$item['hepsiburadaSku'],
                    'quantity'=>$item['availableStock'],
                    'stock_code'=>$item['MerchantSku'],
                    'name'=>$item['merchantSku'],
                    'barcode'=>$item['merchantSku'],
                    'list_price'=>$item['price'],
                    'sale_price'=>$item['price'],
                    'sale_status'=>$item['isSalable'],
                    'approval_status'=>$item['hepsiburadaSku']?1:0,
                    'custom_data'=>array()//$item

                );
            }else {
                foreach ($result['result']['Listings']['Listing'] as $item) {
                    if(isset($item['hepsiburadaSku'])){



                        $products[]=array(
                            'market_id'=>$item['hepsiburadaSku'],
                            'model'=>$item['merchantSku'],
                            'barcode'=>$item['merchantSku'],
                            'stock_code'=>$item['merchantSku'],
                            'list_price'=>$item['price'],
                            'quantity'=>$item['availableStock'],
                            'name'=>$item['merchantSku'],
                            'sale_price'=>$item['price'],
                            'sale_status'=>$item['isSalable'],
                            'approval_status'=>$item['hepsiburadaSku']?1:0,
                            'custom_data'=>array()//$item

                        );
                    }
                }

            }

        }else {


            $message='Hepsiburada Ürününüz Mevcut değildir';

        }



        return array('status'=>$status,'total'=>$total,'message'=>$message,'products'=>$products);
    }


    public function getProduct($product_ids,$debug=false)
    {


        // $product_id='9786055s06sd2200';

        $this->load->model('entegrasyon/general');



        $post_data['request_data'] = $product_ids;

        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('hb');
        $result = $this->entegrasyon->clientConnect($post_data, 'get_product', 'hb', false);


        if (isset($result['result']['listings'][0]['hepsiburadaSku'])) {
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


        $product=array();


        if (isset($result['result']['listings'][0]['hepsiburadaSku'])) {

            foreach ( $result['result']['listings'] as $item) {

                $product[] = array(
                    'market_id' => $item['hepsiburadaSku'],
                    'model' => $item['merchantSku'],
                    'product_code' => $item['hepsiburadaSku'],
                    'quantity' => $item['availableStock'],
                    'match_status'=>$match_status,
                    'message'=>$message,
                    'stock_code' => $item['merchantSku'],
                    'name' => $item['merchantSku'],
                    'barcode' => $item['merchantSku'],
                    'list_price' => $item['price'],
                    'sale_price' => $item['price'],
                    'sale_status' => $item['isSalable'],
                    'approval_status' => $item['hepsiburadaSku'] ? 1 : 0,
                    'custom_data' => $item

                );
            }


        }


        return $product;

    }



}
