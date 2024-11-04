<?php

class ModelEntegrasyonProductPz extends Model
{


    public function sendProduct($product_data, $selected_attributes = array(), $debug = false)
    {

        $status = false;
        $message = '';


        if (!$selected_attributes) {
            if (isset($product_setting['selected_attributes'])) {
                $selected_attributes = $product_data['attributes'];
            } else {
                $selected_attributes = array();
            }
        }

        $product_data['attributes'] = $this->getAttributes($selected_attributes);

        //$product_data['images']=$this->getImages($product_data['product_id'],$product_data['main_image']);

        $defaults = $product_data['defaults'];
        $debug=true;
        $post_data['request_data'] = $product_data;
        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('pz');

     

        $send = $this->entegrasyon->clientConnect($post_data, 'add_product', 'pz', $debug);

        return;

        if ($send['status']) {

            $status = true;
            $message .= $send['message'];

            if (isset($send['result']['result']['batchRequestId'])) {
                $data = array('commission' => $defaults['commission'], 'message' => $message, 'sale_status' => 0, 'approval_status' => 0, 'product_id' => $product_data['product_id'], 'request_id' => $send['result']['result']['batchRequestId'], 'price' => $product_data['sale_price'], 0);

                $this->entegrasyon->addMarketplaceProduct($product_data['product_id'], $data, 'pz');
                //   $this->entegrasyon->addMarketProductAfterAddProduct($product_data['product_id'], array('barcode'), 'ty');


                return array('status' => $status, 'message' => $message, 'price' => $product_data['sale_price'] . ' TL');

            } else {
                return array('status' => false, 'message' => $send['message']);
            }


        } else {

            return array('status' => $status, 'message' => $send['message']);

        }


    }

    public function getExtraData($product_data)
    {

        return $product_data;

    }

    public function deleteProduct($product_id)
    {

        $this->load->model("entegrasyon/general");

        $this->entegrasyon->deleteMarketplaceProduct($product_id, 'pz');

        return array('status' => true, 'message' => 'Ürün Entegrasyon Ayarlarından Silindi. Ürünü Trendyol mağazanızın paneline girerek tamamen silebilirsiniz.');


    }


    public function reset_stock($product_info)
    {

        $product_info['quantipz'] = 0;
        $product_info['sale_price'] = 0;
        $product_info['list_price'] = 0;
        $product_info['price'] = 0;
        $post_data['request_data'] = $product_info;
        $this->load->model('entegrasyon/general');

        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('pz');
        $result = $this->entegrasyon->clientConnect($post_data, 'update_basic', 'pz');


        if ($result['status']) {
            $this->deleteProduct($product_info['product_id']);
            return array('status' => true);

        } else {
            $this->deleteProduct($product_info['product_id']);
            return array('status' => true, 'message' => $result['message']);

        }


    }

    private function getAttributes($selected_attributes)
    {


        $attributes = array();


        foreach ($selected_attributes as $selected_attribute) {


            if ($selected_attribute['value']) {

                if ($selected_attribute['name'] == 47) {
                    $attributes[] = array(
                        'attributeId' => $selected_attribute['name'],
                        'customAttributeValue' => $selected_attribute['value']
                    );

                } else {

                    $val = explode('|', htmlspecialchars_decode($selected_attribute['value']));


                    $attributes[] = array(
                        'attributeId' => $val[0],
                        'attributeValueId' => $val[1]
                    );
                }

            }

        }

        return $attributes;

    }


    public function getImages($product_id, $main_image)
    {


        $catalog_url = $this->config->get('config_secure') ? HTTPS_CATALOG : HTTP_CATALOG;
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



    public function getProducts($data = array(), $onlyApproved = false, $debug = false)
    {



        error_reporting(E_ALL);
        ini_set('display_errors', 0);

        $this->load->model('entegrasyon/general');
        $message = '';
        $status = false;
        $total = 0;
        $products = array();
        $products_uniq = array();
         $post_data['request_data']=array('itemcount'=>$data['itemcount'],'page'=>($data['page']+1),'approved'=>$onlyApproved);
         $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('pz');
        $result = $this->entegrasyon->clientConnect($post_data,'get_products','pz',$debug);






        if ($debug) {
            print_r($result);
        }

        $pages = 0;
        if ($result['status']) {
            $total = 0;
            $pages = 0;
            if (count($result['result']['data']) > 0) {
                foreach ($result['result']['data'] as $item) {

                    //   if(!in_array($item['productMainId'],$products_uniq)) {

                    $market_id = isset($item['productContentId']) ? $item['productContentId'] : false;
                    $product = array(
                        'market_id' => $market_id,
                        'product_code' => $market_id,
                        'model' => $item['code'],
                        'stock_code' => isset($item['code']) ? $item['code'] : '',
                        'name' => $item['name'],
                        'list_price' => isset($item['listPrice']) ? $item['listPrice'] : 0,
                        'barcode' => $item['code'],
                        'sale_price' => isset($item['salePrice']) ? $item['salePrice'] : 0,
                        'sale_status' => 1,
                        'quantity' => $item['stockCount'],
                        'approval_status' => 1,
                        'custom_data' => $item

                    );





                    $products[] = $product;

                    //  $products_uniq[] = $item['productMainId'];

                    //  }
                }
            }

        } else {

            $message = $result['message'];
        }


        return array('status' => $result['status'], 'total' => $total, 'pages' => $pages, 'message' => $message, 'products' => $products);
    }


    public function create_category($category_id)
    {

        //$query = $this->db->query("select * from " . DB_PREFIX . "category_ty where category_id='" . $category_id . "'");

        $category=json_decode(file_get_contents('https://www.opencart.gen.tr/index.php?route=api/search/get_category_info&code=pz&category_id='.$category_id),1);


        if (is_string($category['name'])) {
            $subcategories = explode(' > ', $category['name']);

            $subcategories = array_map('trim', $subcategories);
            $subcategories = array_filter($subcategories);
        } else {
            $subcategories = (array)$category;
        }

        $full_categories = $subcategories;



        $cat_name = array_pop($subcategories);



        $parent_name = $parent_lvl2_name = $parent_lvl3_name = false;

        if (count($subcategories)) {
            $parent_name = array_pop($subcategories);
        }
        if (count($subcategories)) {
            $parent_lvl2_name = array_pop($subcategories);
        }

        if (count($subcategories)) {
            $parent_lvl3_name = array_pop($subcategories);
        }

        // 2 parents levels detection, then 1, then 0
        if (!emppz($parent_lvl3_name)) {
            $query = $this->db->query("SELECT cd.name, c.category_id FROM " . DB_PREFIX . "category_description cd LEFT JOIN " . DB_PREFIX . "category c ON cd.category_id = c.category_id LEFT JOIN " . DB_PREFIX . "category_description pcd ON pcd.category_id = c.parent_id LEFT JOIN " . DB_PREFIX . "category pc ON pc.category_id = pcd.category_id LEFT JOIN " . DB_PREFIX . "category_description ppcd ON ppcd.category_id = pc.parent_id LEFT JOIN " . DB_PREFIX . "category ppc ON ppc.category_id = ppcd.category_id LEFT JOIN " . DB_PREFIX . "category_description pppcd ON pppcd.category_id = ppc.parent_id WHERE cd.name = '" . $this->db->escape(trim($this->request->clean($cat_name))) . "' AND pcd.name = '" . $this->db->escape(trim($this->request->clean($parent_name))) . "' AND ppcd.name = '" . $this->db->escape(trim($this->request->clean($parent_lvl2_name))) . "' AND pppcd.name = '" . $this->db->escape(trim($this->request->clean($parent_lvl3_name))) . "' GROUP BY cd.category_id")->rows;
        } else if (!emppz($parent_lvl2_name)) {
            $query = $this->db->query("SELECT cd.name, c.category_id FROM " . DB_PREFIX . "category_description cd LEFT JOIN " . DB_PREFIX . "category c ON cd.category_id = c.category_id LEFT JOIN " . DB_PREFIX . "category_description pcd ON pcd.category_id = c.parent_id LEFT JOIN " . DB_PREFIX . "category pc ON pc.category_id = pcd.category_id LEFT JOIN " . DB_PREFIX . "category_description ppcd ON ppcd.category_id = pc.parent_id WHERE cd.name = '" . $this->db->escape(trim($this->request->clean($cat_name))) . "' AND pcd.name = '" . $this->db->escape(trim($this->request->clean($parent_name))) . "' AND ppcd.name = '" . $this->db->escape(trim($this->request->clean($parent_lvl2_name))) . "' GROUP BY cd.category_id")->rows;
        } else if (!emppz($parent_name)) {
            $query = $this->db->query("SELECT cd.name, c.category_id FROM " . DB_PREFIX . "category_description cd LEFT JOIN " . DB_PREFIX . "category c ON cd.category_id = c.category_id LEFT JOIN " . DB_PREFIX . "category_description pcd ON pcd.category_id = c.parent_id WHERE cd.name = '" . $this->db->escape(trim($this->request->clean($cat_name))) . "' AND pcd.name = '" . $this->db->escape(trim($this->request->clean($parent_name))) . "' GROUP BY cd.category_id")->rows;
        } else {
            $query = $this->db->query("SELECT name, category_id FROM " . DB_PREFIX . "category_description WHERE name = '" . $this->db->escape(trim($this->request->clean($cat_name))) . "' GROUP BY category_id")->rows;
        }

        if (count($query) === 1) {

            $values[] = $query[0]['category_id'];


        } else if (true) {
            // category does not exists, create it ?
            $this->load->model('catalog/category');

            $parent_id = 0;



            foreach ($full_categories as $cat_name) {
                $cat_name_ml = array();

                // xml fix

                /*
    if (isset($full_categoriesFr)) {
      $cat_name_ml['fr-fr'] = trim(array_shift($full_categoriesFr));
    }*/

                $cat_exists = $this->db->query("SELECT cd.category_id FROM " . DB_PREFIX . "category_description cd LEFT JOIN " . DB_PREFIX . "category c ON cd.category_id = c.category_id WHERE name = '" . $this->db->escape(trim($this->request->clean($cat_name))) . "' AND c.parent_id = '" . (int)$parent_id . "'")->row;

                if (emppz($cat_exists['category_id'])) {
                    $cat_data = array(
                        'parent_id' => $parent_id,
                        'column' => 3,
                        'top' => 1,
                        'sort_order' => 0,
                        'category_store' => isset($config['columns']['product_store']) ? $config['columns']['product_store'] : array(0),
                        'status' => 1,
                        'keyword' => $this->urlify($cat_name),
                    );


                    $cat_data['category_description'][1] = array(
                        'name' =>  trim($cat_name),
                        'description' => '',
                        'meta_title' =>  trim($cat_name),
                        'meta_description' => '',
                        'meta_keyword' => '',
                        'seo_h1' => '',
                        'seo_keyword' => $this->urlify($cat_name),
                    );


                    $parent_id = $this->model_catalog_category->addCategory($this->request->clean($cat_data));

                } else {
                    $parent_id = $cat_exists['category_id'];
                }
            }

            // last id is assigned category

            $values[] = $parent_id;

        }


        $res = array_unique($values);

        return $res;

    }

    private function urlify($value, $lang = null)
    {
        if (!emppz($lang)) {
            include_once(DIR_SYSTEM . 'library/gkd_urlify.php');
            $value = URLify::downcode($value, $lang);
        }

        $value = str_replace(array('\'', '`', '‘', '’', '|', '%7C', "\n"), '-', $value);
        $value = str_replace(array('"', '“', '”', '&', '&amp;', '+', '?', '!', '/', '%', '#', ',', ':', '&gt;', '&lt;', ';', '<', '>', '(', ')', '™', '®', '©', '&copy;', '&reg;', '&trade;'), '', $value);

        $value = trim(mb_ereg_replace('--+', '-', str_replace(' ', '-', mb_strtolower($value))), '-');

        return $value;
    }

    public
    function getProduct($product_id, $debug = false)
    {


        // $product_id='9786055s06sd2200';

        $this->load->model('entegrasyon/general');


        $post_data['request_data'] = array('itemcount' => 1, 'page' => 1, 'barcode' => $product_id, 'approved' => true);

        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('pz');

        $result = $this->entegrasyon->clientConnect($post_data, 'get_product', 'pz', $debug);


        if (isset($result['result']['content'][0])) {
            $match_status = true;
            $message = 'Ürün Bulundu';
        } else {
            $match_status = false;
            $message = 'Ürün Bulunamadı';
            $product = array(
                'match_status' => $match_status,
                'message' => $message
            );
            return $product;
        }


        //$product=array();

        if (!$result['status']) {

            return $result;
        }


        $product = array();

        if (!$result['status']) return $result;


        if (isset($result['result']['content'][1])) {
            $products = array();
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
                    'sale_status' => $market_id && $item['quantipz'] ? 1 : 0,
                    'quantipz' => $item['quantipz'],
                    'approval_status' => $item['approved'],
                    'custom_data' => $item

                );

            }
            return $products;
        } else if ($result['result']['content'][0]) {


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
                'sale_status' => $market_id && $item['quantipz'] ? 1 : 0,
                'quantipz' => $item['quantipz'],
                'approval_status' => $item['approved'],
                'custom_data' => $item

            );

            return $product;
        }


    }


    public
    function getMarketPlaceProduct($product_id, $category_id, $manufacturer_id, $debug = false)
    {


        // $product_id='8681123687181';

        $this->load->model('entegrasyon/general');


        $post_data['request_data'] = array('itemcount' => 1, 'page' => 1, 'barcode' => $product_id, 'approved' => true);

        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('pz');


        $result = $this->entegrasyon->clientConnect($post_data, 'get_product', 'pz', $debug);




        if ($debug) {
            print_r($result);
            return;

        }


        $language_id = $this->config->get('config_language_id');


        if (!$result['status']) {

            return $result;
        }

        if (!$result['status']) return $result;


        $product = $result['result']['content'][0];
        $product_title = $product['title'];

        $product_description = array($language_id => array(
            'name' => $product_title,
            'description' => $product['description'],
            'meta_title' => $product_title,
            'meta_description' => $product_title,
            'meta_keyword' => $product_title,
            'tag' => array()//implode(',', explode(' ', $product_title)),

        ));


        $images = array();


        foreach ($product['images'] as $key => $image) {
            $ext = pathinfo($image['url'], PATHINFO_EXTENSION);
            if (!$ext) {
                $img_url = $this->findImg($image['url']);

            } else {
                $img_url = $image['url'];
            }
            $img_url = str_replace(' ', '%20', $img_url);

            // $img_url = str_replace('https', 'http', $img_url);

            //echo $img_url;
            //return;


            $check_image = $this->checkUrl($img_url);


            if (!$check_image) {

                $url_arr = explode('.' . $ext, $img_url);
                $img_url = $url_arr[0] . '_org.' . $ext;

            }


            if (strpos(HTTPS_CATALOG, $img_url) === 0) {

                $image_arr = explode(HTTPS_CATALOG, $img_url);
                $images[] = array(

                    'image' => $image_arr[1],
                    'sort_order' => 0
                );;


            } else {

                if ($this->checkUrl($img_url)) {

                    $images[] = array(

                        'image' => $this->entegrasyon->getImage($img_url, $product_title . '_' . $product['productMainId'] . '_' . $key),
                        'sort_order' => 0
                    );;

                }

            }


        }


        //  $stockData = $this->getOptionsFromN11($product['stockItems']['stockItem']);


        if ($product['listPrice'] > $product['salePrice']) {

            $price = $product['listPrice'];
            $product_special = array(0 => array(
                'customer_group_id' => $this->config->get('config_customer_group_id'),
                'prioripz' => 0,
                'date_start' => '',
                'date_end' => '',
                'price' => $product['salePrice']

            ));
        } else {

            $price = $product['salePrice'];

            $product_special = array();
        }

        $categories = $this->create_category($product['pimCategoryId']);

        if (!$manufacturer_id) {

            $manufacturer_id = $this->addManufacturer($product['brand']);

        }


        $product_data = array(
            'model' => isset($product['productMainId']) ? $product['productMainId'] : $this->entegrasyon->createSEOKeyword($product_title),
            'sku' => '',
            'upc' => '',
            'ean' => '',
            'jan' => '',
            'isbn' => '',
            'mpn' => '',
            'location' => '',
            'quantipz' => $product['quantipz'],
            'minimum' => 1,
            'keyword' => $this->entegrasyon->createSEOKeyword($product_title) . '_' . $product_id . ".html",
            'subtract' => 1,
            'image' => $images[0]['image'],
            'product_image' => $images,
            'product_category' => $category_id ? array($category_id) : $categories,
            'product_special' => $product_special,
            'stock_status_id' => 2,
            'date_available' => '',
            'manufacturer_id' => $manufacturer_id ? $manufacturer_id : "",
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

        if ($this->config->get('pz_setting_barkod_place')) {
            $barcode_place = $this->config->get('pz_setting_barkod_place');
            $product_data[$barcode_place] = $product['barcode'];

        } else {

            $product_data['ean'] = $product['barcode'];
        }


        //print_r($product);
        // return;


        $url = isset($product['productContentId']) ? $this->entegrasyon->getMarketPlaceUrl('pz', $product['productContentId']) : '';
        //productCode
        $marketplace_product_data = array('sale_status' => $product['approved'], 'approval_status' => $product['approved'], 'commission' => 0, 'product_id' => isset($product['productCode']) ? $product['productCode'] : '', 'price' => $price, 'url' => $url);
        $marketplace_product_data['pz_category_id'] = $product['pimCategoryId'] . '|' . $product['categoryName'];
        $marketplace_product_data['product_id'] = $product_id;

        return array('status' => true, 'product_data' => $product_data, 'marketplace_product_data' => $marketplace_product_data);


    }

    private
    function addManufacturer($brand)
    {

        $query = $this->db->query("select * from " . DB_PREFIX . "manufacturer where name='" . $brand . "'");

        if (!$query->num_rows) {
            $this->db->query("insert into  " . DB_PREFIX . "manufacturer SET name='" . $brand . "', meta_title='" . $brand . " Türkiye Yetkil Satış' ");

            return $this->db->getLastId();

        } else {
            return $query->row['manufacturer_id'];
        }


    }

    private
    function findImg($str)
    {

        preg_match_all('/(http|https):\/\/[^ ]+(\.gif|\.jpg|\.jpeg|\.png)/', $str, $out);

        return $out[0][0];

    }


    public
    function checkUrl($url)
    {
        //$url='https://cdn.dsmcdn.com//ty5/product/media/images/20200622/12/3284861/61173429/1/1_org.jpg';

        $status = true;
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0);
        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($handle);
        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($httpCode != 200) {
            /* Handle 404 here. */
            $status = false;

        }

        curl_close($handle);


        return $status;
    }


    public
    function imageControl($image)
    {


        $bosluk = strpos($image, ' ');

        if ($bosluk) {
            if (is_file(DIR_IMAGE . $image)) {

                $degisti = $this->imageTemizle($image);


                $produduct_image = $this->db->query("select * from " . DB_PREFIX . "product WHERE image='" . $image . "' ");

                if ($produduct_image->num_rows) {

                    $product_id = $produduct_image->row['product_id'];

                    $this->db->query("UPDATE " . DB_PREFIX . "product SET image='" . $degisti . "' WHERE product_id=" . (int)$product_id . " ");

                    rename(DIR_IMAGE . $image, DIR_IMAGE . $degisti);

                    return $degisti;
                } else {

                    $produduct_image = $this->db->query("select * from " . DB_PREFIX . "product_image WHERE image='" . $image . "' ");


                    if ($produduct_image->num_rows)

                        $image_id = $produduct_image->row['product_image_id'];

                    $this->db->query("UPDATE " . DB_PREFIX . "product_image SET image='" . $degisti . "' WHERE product_id='" . (int)$image_id . "' ");

                    rename(DIR_IMAGE . $image, DIR_IMAGE . $degisti);

                    return $degisti;

                }
            }
        } else {

            return $image;

        }


    }


    private
    function searchName($name, $attributes)
    {

        foreach ($attributes as $attribute) {

            if ($this->entegrasyon->replaceSpace($attribute['name']) == $this->entegrasyon->replaceSpace($name)) {

                return $attribute['values'];

            }


        }

    }


    private
    function searchValue($value, $valuesArray)
    {


        foreach ($valuesArray as $item) {
            $val1 = $this->entegrasyon->replaceSpace($item['name']);
            $findMe = $this->entegrasyon->replaceSpace($value);


            $pos = stripos($val1, $findMe);

            //  echo $val1.'-'.$findMe.'<br>';


            if ($pos !== false) {

                return $item['id'];

            }

        }

    }


}