<?php

class ControllerEntegrasyonUpdate extends Controller
{

    private $debug=false;
    private $send=false;
    private $token_data;

    public function __construct($registry)
    {

        parent::__construct($registry);
        $this->load->model('entegrasyon/general');
        $this->token_data = $this->model_entegrasyon_general->getToken();
        $this->marketplaces = $this->model_entegrasyon_general->getActiveMarkets();

        if (!$this->config->get('mir_login')) {

            $this->response->redirect($this->url->link('entegrasyon/setting/error', '&error=no_user&' . $this->token_data['token_link'], true));

        } else if (!$this->marketplaces) {

            $this->response->redirect($this->url->link('entegrasyon/setting/error', '&error=no_api&' . $this->token_data['token_link'], true));

        } else if (!$this->config->get('module_entegrasyon_status')) {
            $this->response->redirect($this->url->link('entegrasyon/setting/error', '&error=no_module&' . $this->token_data['token_link'], true));

        }
    }


    public function bulk_actions()
    {

        //sale status değişimlerini sessionda tututoruz;
        $this->session->data['waiting_for_update'] = array();

        $this->load->model('entegrasyon/market_product');
        $this->load->model('entegrasyon/general');
        $this->load->model('entegrasyon/product');

        $code = $this->request->get['marketplace'];
        //$products = $this->input->get['product_list'];
        $list_type = $this->request->get['list_type'];
        if ($list_type == 'all_filter') {

            if (isset($this->request->get['filter_model'])) {
                $filter_model = $this->request->get['filter_model'];
            } else {
                $filter_model = '';
            }

            if (isset($this->request->get['filter_name'])) {
                $filter_name = $this->request->get['filter_name'];
            } else {
                $filter_name = '';
            }

            if (isset($this->request->get['filter_status'])) {
                $filter_status = $this->request->get['filter_status'];
            } else {
                $filter_status = '*';
            }

            if (isset($this->request->get['filter_marketplace'])) {
                $filter_marketplace = $this->request->get['filter_marketplace'];
            } else {
                $filter_marketplace = '';
            }

            if (isset($this->request->get['filter_marketplace_do'])) {
                $filter_marketplace_do = $this->request->get['filter_marketplace_do'];
            } else {
                $filter_marketplace_do = '';
            }

            if (isset($this->request->get['filter_stock_prefix'])) {
                $filter_stock_prefix = html_entity_decode($this->request->get['filter_stock_prefix']);
            } else {
                $filter_stock_prefix = "";
            }


            if (isset($this->request->get['filter_stock'])) {
                $filter_stock = $this->request->get['filter_stock'];
            } else {
                $filter_stock = 0;
            }


            if (isset($this->request->get['filter_category'])) {
                $filter_category = $this->request->get['filter_category'];
            } else {
                $filter_category = '';
            }

            if (isset($this->request->get['filter_manufacturer'])) {
                $filter_manufacturer = $this->request->get['filter_manufacturer'];
            } else {
                $filter_manufacturer = '';
            }


            $filter_data = array(
                'filter_category' => $filter_category,
                'filter_manufacturer' => $filter_manufacturer,
                'filter_marketplace' => $filter_marketplace,
                'filter_marketplace_do' => $filter_marketplace_do,
                'filter_name' => $filter_name,
                'filter_model' => $filter_model,
                'filter_status' => $filter_status,
                'filter_stock_prefix' => $filter_stock_prefix,
                'filter_stock' => $filter_stock,
                'sort' => "pd.name",
                'order' => "ASC",
                'code' => $code
            );

            // print_r($filter_data);return;

            $product_total = $this->model_entegrasyon_market_product->getMarketProductsTotal($filter_data);
            //  echo $product_total;return;

            $data['filter_marketplace'] = $filter_marketplace;
            $data['filter_marketplace_do'] = $filter_marketplace_do;
            $data['filter_model'] = $filter_model;
            $data['filter_name'] = $filter_name;
            $data['filter_manufacturer'] = $filter_manufacturer;
            $data['filter_category'] = $filter_category;
            $data['filter_stock_prefix'] = $filter_stock_prefix;
            $data['filter_stock'] = $filter_stock;
            $data['filter_status'] = $filter_status;


            if ($filter_marketplace_do == 1) {


                $data['total_active'] = $product_total;
                $data['total_passive'] = 0;
            } else if ($filter_marketplace_do == '*') {
                $filter_data['filter_marketplace_do'] = 1;
                $total_Active = $this->model_entegrasyon_market_product->getMarketProductsTotal($filter_data);;
                $data['total_active'] = $total_Active;
                $data['total_passive'] = $product_total - $total_Active;

            } else {

                //echo $product_total;
                $data['total_active'] = 0;
                $data['total_passive'] = $product_total;
            }


            $data['total'] = $product_total;
            $data['product_list'] = '';


        } else {


            $product_total = $this->db->query("select count(*) as total from " . DB_PREFIX . "es_market_product mp left join " . DB_PREFIX . "product p ON(p.product_id=mp.oc_product_id) where mp.code='" . $code . "' and mp.oc_product_id!=0")->row['total'];
            $data['total'] = $product_total;
            $product_passive = $this->db->query("select count(*) as total from " . DB_PREFIX . "es_market_product mp left join " . DB_PREFIX . "product p ON(p.product_id=mp.oc_product_id) where sale_status=0  and mp.code='" . $code . "' and mp.oc_product_id!=0")->row['total'];
            $product_active = $this->db->query("select count(*) as total from " . DB_PREFIX . "es_market_product mp left join " . DB_PREFIX . "product p ON(p.product_id=mp.oc_product_id) where sale_status=1 and mp.code='" . $code . "' and mp.oc_product_id!=0")->row['total'];

            $data['product_list'] = '';
            $data['total_passive'] = $product_passive;
            $data['total_active'] = $product_active;


        }


        $marketPlace = $this->model_entegrasyon_general->getMarketPlace($code);
        $data['marketplace'] = $marketPlace['name'];
        $data['list_type'] = $list_type;
        $data['code'] = $code;
        $data['delete_permission'] = false;
        $data['commission'] = '';
        $data['value'] = '';
        $data['token_link'] = $this->token_data['token_link'];

        $this->response->setOutput($this->load->view('entegrasyon/update/bulk', $data));

    }


    public function bulk_progress()
    {

        $code = $this->request->get['code'];
        $action = $this->request->get['action'];
        $success = $this->request->post['dt_success'];
        $fail = $this->request->post['dt_fail'];
        $list_type = $this->request->get['list_type'];
        $total_for_progress = $this->request->get['total_for_progress'];
        $this->debug = isset($this->request->get['debug'])?true:false;
        $this->send = isset($this->request->get['send'])?true:false;

     /*   if ($code == 'n11') {
            $limit = 1;
        } else { */
            $limit = 100;
       // }


        if (isset($this->request->get['page'])) {

            $page = $this->request->get['page'];

        } else {
           // $this->session->data['waiting_for_update']=array();
            $page = 0;
        }

        $product_list = array();
        if ($list_type == 'all_filter') {

            if (isset($this->request->get['filter_model'])) {
                $filter_model = $this->request->get['filter_model'];
            } else {
                $filter_model = '';
            }

            if (isset($this->request->get['filter_name'])) {
                $filter_name = $this->request->get['filter_name'];
            } else {
                $filter_name = '';
            }

            if (isset($this->request->get['filter_status'])) {
                $filter_status = $this->request->get['filter_status'];
            } else {
                $filter_status = '*';
            }

            if (isset($this->request->get['filter_marketplace'])) {
                $filter_marketplace = $this->request->get['filter_marketplace'];
            } else {
                $filter_marketplace = '';
            }

            if (isset($this->request->get['filter_marketplace_do'])) {
                $filter_marketplace_do = $this->request->get['filter_marketplace_do'];
            } else {
                $filter_marketplace_do = '';
            }

            if (isset($this->request->get['filter_stock_prefix'])) {
                $filter_stock_prefix = html_entity_decode($this->request->get['filter_stock_prefix']);
            } else {
                $filter_stock_prefix = "";
            }


            if (isset($this->request->get['filter_stock'])) {
                $filter_stock = $this->request->get['filter_stock'];
            } else {
                $filter_stock = 0;
            }


            if (isset($this->request->get['filter_category'])) {
                $filter_category = $this->request->get['filter_category'];
            } else {
                $filter_category = '';
            }

            if (isset($this->request->get['filter_manufacturer'])) {
                $filter_manufacturer = $this->request->get['filter_manufacturer'];
            } else {
                $filter_manufacturer = '';
            }


            $start = $limit * $page;


            $filter_data = array(
                'filter_category' => $filter_category,
                'filter_manufacturer' => $filter_manufacturer,
                'filter_marketplace' => $filter_marketplace,
                'filter_marketplace_do' => $filter_marketplace_do,
                'filter_name' => $filter_name,
                'filter_model' => $filter_model,
                'filter_status' => $filter_status,
                'filter_stock_prefix' => $filter_stock_prefix,
                'filter_stock' => $filter_stock,
                'sort' => "pd.name",
                'start' => $start,
                'limit' => $limit,
                'order' => "ASC",
                'code' => $code
            );

//            print_r($filter_data);return;


            $this->load->model('entegrasyon/market_product');

            $products = $this->model_entegrasyon_market_product->getMarketProducts($filter_data);


            foreach ($products as $item) {
                // $query =$this->udb->query("select marketplace_product_id from market_product where market_product_id='".$products[$i]."'");
                $product_list[] = $item;
            }


            $data['filter_marketplace'] = $filter_marketplace;
            $data['filter_marketplace_do'] = $filter_marketplace_do;
            $data['filter_model'] = $filter_model;
            $data['filter_name'] = $filter_name;
            $data['filter_manufacturer'] = $filter_manufacturer;
            $data['filter_category'] = $filter_category;
            $data['filter_stock_prefix'] = $filter_stock_prefix;
            $data['filter_stock'] = $filter_stock;
            $data['filter_status'] = $filter_status;
            // $json['product'] = implode('-',$product_list);
            $url_prefix = 'filter_marketplace=' . $filter_marketplace . '&filter_marketplace_do=' . $filter_marketplace_do . '&filter_model=' . $filter_model . '&filter_name=' . $filter_name . '&filter_manufacturer=' . $filter_manufacturer . '&filter_category=' . $filter_category . '&filter_stock_prefix=' . $filter_stock_prefix . '&filter_stock=' . $filter_stock . '&filter_status=' . $filter_status;

        } else {


            $start = $limit * $page;
            if ($action == 'open_for_sale') {
                $filter_data = array(
                    'filter_marketplace' => $code,
                    'filter_marketplace_do' => 0,
                    'sort' => "pd.name",
                    'limit' => $limit,
                    'order' => "ASC",
                    'start' => $start,
                    'code' => $code
                );


                $this->load->model('entegrasyon/market_product');

                $products = $this->model_entegrasyon_market_product->getMarketProducts($filter_data);

                foreach ($products as $item) {
                    // $query =$this->udb->query("select marketplace_product_id from market_product where market_product_id='".$products[$i]."'");
                    $product_list[] = $item;
                }

            } else if ($action == 'close_for_sale') {

                $filter_data = array(

                    'filter_marketplace' => $code,
                    'filter_marketplace_do' => 1,
                    'sort' => "pd.name",
                    'start' => $start,
                    'limit' => $limit,
                    'order' => "ASC",
                    'code' => $code
                );


                $this->load->model('entegrasyon/market_product');

                $products = $this->model_entegrasyon_market_product->getMarketProducts($filter_data);
                foreach ($products as $item) {
                    // $query =$this->udb->query("select marketplace_product_id from market_product where market_product_id='".$products[$i]."'");
                    $product_list[] = $item;
                }

            } else if ($action == 'update') {

                $filter_data = array(

                    'filter_marketplace' => $code,
                    'sort' => "pd.name",
                    'filter_marketplace_do' => 1,
                    'start' => $start,
                    'limit' => $limit,
                    'order' => "ASC",
                    'code' => $code
                );


                $this->load->model('entegrasyon/market_product');

                $products = $this->model_entegrasyon_market_product->getMarketProducts($filter_data);
                foreach ($products as $item) {
                    // $query =$this->udb->query("select marketplace_product_id from market_product where market_product_id='".$products[$i]."'");
                    $product_list[] = $item;
                }

            }

        }


        $page++;

        $cond = $total_for_progress <= (($page) * $limit) ? $total_for_progress : (($page) * $limit);


        if ($list_type == 'all_filter') {


            $url = 'index.php?route=entegrasyon/update/bulk_progress&code=' . $code . '&page=' . $page . '&list_type=' . $list_type . '&action=' . $action . '&' . $this->token_data['token_link'] . '&total_for_progress=' . $total_for_progress;
            $url = $url . '&' . $url_prefix;

        } else {
            $url = 'index.php?route=entegrasyon/update/bulk_progress&code=' . $code . '&page=' . $page . '&list_type=' . $list_type . '&action=' . $action . '&' . $this->token_data['token_link'] . '&total_for_progress=' . $total_for_progress;

        }


        //$this->load->model('entegrasyon/product/marketplace/' . $code);



        $result = $this->{$action}($product_list, $code);
       

        $json['success'] = $success + $result['success'];;

        if (!$result['status']) {
            $json['fail'] = $fail + count($product_list);

        } else {
            $json['fail'] = $fail + $result['fail'];
        }

        if ($result['message']) {
            $json['message'] = $result['message'];
        }

        $json['success_messages'] = $result['success_messages'];
        $json['warning_messages'] = $result['warning_messages'];


        $json['total_for_progress'] = $total_for_progress;


        $json['current'] = $cond;

        if ($cond < $total_for_progress) {

            $json['status'] = true;
            $json['next'] = $url;

        } else {

            if ($this->session->data['waiting_for_update']) {


                foreach ($this->session->data['waiting_for_update'] as $index => $item) {

                    //print_r($item);
                    $this->db->query("update " . DB_PREFIX . "es_market_product SET sale_status='" . $item . "' where oc_product_id='" . $index . "'");
                    unset($this->session->data['waiting_for_update'][$index]);

                }
            }

            $json['status'] = false;
            unset($this->session->data['update_visa_'.$code]);
            //$json['message']='Tamamlandı';
        }


        echo json_encode($json);


    }


    public function close_for_sale($product_list, $code)
    {

        return $this->update($product_list, $code, 0, 'Satışa Kapatıldı');

    }


    public function open_for_sale($product_list, $code)
    {

        return $this->update($product_list, $code, 2, 'Satışa Açıldı');

    }

    public function update($product_list, $code, $mode = 1, $action_type = 'Güncellendi')
    {
        $this->load->model("entegrasyon/general");

        //print_r($product_list);return;

        $updatable_list = $this->get_updatable_list($product_list, $code, $mode);





        if($this->debug){

            echo'<hr><h1>Güncelleme Listesi</h1><pre>';
            print_r($updatable_list);return;
            echo'</pre>';
        }






        $post_data['request_data'] = $updatable_list;
        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace($code);


        if($this->send){
            return;
        }

        $this->debug=false;
        $result = $this->entegrasyon->clientConnect($post_data, 'update_fast', $code, $this->debug, false);


        $primary_index = 'model';
        if ($code == 'ty') {
            $primary_index = 'barcode';
        } else if ($code == 'cs' || $code == 'n11') {
            $primary_index = 'stock_code';
        }

        $success = 0;
        $fail = 0;
        $error_messages = array();
        $success_messages = array();
        $warning_messages = array();


        if ($result['status']) {


            foreach ($updatable_list as $item) {

                if (key_exists($item[$primary_index], $result['result'])) {
                    //Başarısız
                    $error_messages[] = $item['name'] . ':' . $result['result'][$item[$primary_index]];
                    $error = $this->entegrasyon->getError($item['product_id'], $code);
                    if ($error) {
                        $this->entegrasyon->updateError($item['product_id'], $code, 2, $result['result'][$item[$primary_index]]);
                    } else {
                        $this->entegrasyon->addError($item['product_id'], $code, 2, $result['result'][$item[$primary_index]]);
                    }
                    //$json['status'] = false;
                    //$json['message'] = $item['name'] . ' - ' . $result['message'];

                    $fail++;

                } else {
                    //Başarılı



                    $total_status = 0;
                    if ($item['is_variant']) {

                        if ($this->config->get('easy_setting_critical_stock') && $mode) {
                            $total_status = $this->db->query("select count(*) as total from " . DB_PREFIX . "product_option_value where quantity >= '" . (int)$this->config->get('easy_setting_critical_stock') . "' and product_id='".$item['product_id']."'")->row['total'] ? 1 : 0;
                        }

                    }

                    if ($item['manuel_closed'] && $mode != 2) {
                        $total_status = 0;
                    }




                    if ($item['action']) {

                        $warning_messages[] = $item['name'] . ':' . $item['action'];

                    } else {

                        $success_messages[] = $item['name'] . ':' . 'Başarıyla ' . $action_type;

                    }

                    $marketplace_data = $this->entegrasyon->getMarketPlaceProductForMarket($item['product_id'], $code);

                    if ($item['quantity'] && ($item['list_price']) && $marketplace_data['approval_status']) {

                        $marketplace_data['sale_status'] = 1;

                    } else {

                        $marketplace_data['sale_status'] = 0;
                        // $marketplace_data['price'] = isset($item['sale_price']) ? $item['sale_price'] : $item['list_price'];
                    }

                    if (!$marketplace_data['sale_status']) {
                        $marketplace_data['sale_status'] = $total_status;
                    }
                    if($item['quantity'] <= 0){
                        $marketplace_data['sale_status']=0;
                    }

                    //güncelleme sırasında en az 1 açık varyant varsa ürünü açık olarak görüntüle.
                    if ($item['quantity'] <=0 && $item['is_variant'] && $mode && $marketplace_data['sale_status']==0 && $marketplace_data['approval_status']) {

                        $marketplace_data['sale_status'] = $this->db->query("select count(*) as total from " . DB_PREFIX . "product_option_value where quantity > '" . (int)$this->config->get('easy_setting_critical_stock') . "' and product_id='".$item['product_id']."'")->row['total'] ? 1 : 0;


                    }


                    $marketplace_data['price'] = isset($item['sale_price']) ? $item['sale_price'] : $item['list_price'];

                    $this->entegrasyon->addMarketplaceProduct($item['product_id'], $marketplace_data, $code,false,true);

                    if (!array_key_exists($item['product_id'], $this->session->data['waiting_for_update'])) {
                        $this->session->data['waiting_for_update'][$item['product_id']] = $marketplace_data['sale_status'];

                    }
                    $success++;
                }

            }
        }else {
            $error_messages[]=$result['message'];
        }
        if($result['message']){
            $messages[]=$result['message'];
            $result['message']=$messages;
        }

        return array('status' => $result['status'], 'message' => $result['message'] ? $result['message'] : $error_messages, 'success_messages' => $success_messages, 'success' => $success, 'warning_messages' => $warning_messages, 'fail' => $fail);


    }


    public function get_updatable_list($product_list, $code, $mode)
    {

        $manuel_closed = false;
        if ($this->config->get('easy_setting_customer_group')) {
            $customer_group_id = $this->config->get('easy_setting_customer_group');
        } else {
            $customer_group_id = $this->config->get('config_customer_group_id');
        }

        $update_list = array();




        foreach ($product_list as $item) {


            $product_query = $this->query("select * from " . DB_PREFIX . "product where product_id='" . $item['oc_product_id'] . "'");

            if ($product_query->num_rows) {
                $product_info = $product_query->row;
                $category_setting = $this->entegrasyon->getMarketPlaceCategory($product_info['product_id'], $code);
                $manufacturer_setting = $this->entegrasyon->getMarketPlaceManufacturer($product_info['manufacturer_id'], $code);
                $product_setting = $this->entegrasyon->getSettingData($code, 'product', $product_info['product_id']);
                $product_data['defaults'] = $this->entegrasyon->getDefaults($category_setting, $manufacturer_setting, $product_setting, $code);

                $customer_sale_price=false;
                $customer_list_price=false;

                $barcode = $item['barcode'];
                $stock_code = $item['barcode'];
                $model = $item['model'];
                $list_price = $product_info['price'];
                $sale_price = 0;
                if ($this->config->get($code . '_setting_product_special')) {

                    $query = $this->query("select * from " . DB_PREFIX . "product_special where product_id='" . $item['oc_product_id'] . "' and customer_group_id='" . $customer_group_id . "'");

                    if ($query->num_rows) {

                        $sale_price = $query->row['price'];

                    }

                }


                if (isset($product_setting[$code.'_product_sale_price'])) {

                    $customer_sale_price=$product_setting[$code . '_product_sale_price'];
                }
                if (isset($product_setting[$code.'_product_list_price'])) {

                    $customer_list_price=$product_setting[$code . '_product_list_price'];
                }

                if($customer_sale_price && !$customer_list_price ){

                    $customer_list_price=$customer_sale_price;
                    $customer_sale_price=null;
                }


                if ($this->config->get($code . '_setting_model_prefix')) {

                    if (count(explode($this->config->get($code . '_setting_model_prefix'), $barcode)) > 1) {
                        $barcode = explode($this->config->get($code . '_setting_model_prefix'), $barcode)[1];
                    }
                    if (count(explode($this->config->get($code . '_setting_model_prefix'), $model)) > 1) {
                        $model = explode($this->config->get($code . '_setting_model_prefix'), $model)[1];
                    }
                    if (count(explode($this->config->get($code . '_setting_model_prefix'), $stock_code)) > 1) {
                        $stock_code = explode($this->config->get($code . '_setting_model_prefix'), $stock_code)[1];
                    }
                }


                $is_varianter = $this->query("select count(*) as total from " . DB_PREFIX . "es_product_variant where product_id='" . $item['oc_product_id'] . "'")->row['total'];

                if ($is_varianter) {

                    $query = $this->query("select * from " . DB_PREFIX . "es_product_variant where product_id='" . $item['oc_product_id'] . "' and  (barcode='" . $barcode . "' or model='" . $model . "')");

                    if ($query->num_rows) {

                        $action = array();

                        $variant_infos = explode('|', $query->row['variant_info']);
                        //Varyant tek seçenekten oluşuyorsa
                        if (count($variant_infos) == 1) {
                            $variant_title = '';
                            $variant = explode('+-', $variant_infos[0]);
                            $variant_title = $variant[0] . ':' . $variant[2];

                            $product_option_id = $variant[3];
                            $query = $this->query("select quantity,price from " . DB_PREFIX . "product_option_value where product_id='" . $item['oc_product_id'] . "' and option_value_id='" . $product_option_id . "' ");

                            if ($query->num_rows) {

                                $quantity = ($query->row['quantity'] <= 0 || $product_info['quantity'] <= 0 )  ? 0 :$query->row['quantity'];

                                if ($this->config->get('easy_setting_critical_stock') && $quantity > 0) {
                                    $quantity = $query->row['quantity'] <= $this->config->get('easy_setting_critical_stock') ? 0 : $query->row['quantity'];
                                    if ($query->row['quantity'] <= $this->config->get('easy_setting_critical_stock')) {
                                        $action[] = 'Kritik stok sebebiyle stok sıfırlandı, kritik stok:' . $this->config->get('easy_setting_critical_stock');
                                    }

                                }


                                $marketplace_data = $this->entegrasyon->getMarketPlaceProductForMarket($item['oc_product_id'], $code);

                                if ($mode == 1) {
                                    if ($marketplace_data['approval_status'] && !$marketplace_data['sale_status'] && $quantity > 0) {
                                        $quantity = 0;
                                        $manuel_closed = true;
                                        $action[] = 'Ürün kullanıcı tarafından satışa kapatılmış, tekrar satışa açılana kadar kapalı kalacaktır.';

                                    }else {
                                        $manuel_closed=false;
                                    }

                                }

                                if (!$marketplace_data['approval_status'] && !$marketplace_data['sale_status'] && $mode == 2) {
                                    $action[] = 'Ürün henüz pazaryeri tarafından onaylanmadığı için satışa açılamamıştır';

                                }



                                $product = array(
                                    'product_id' => $item['oc_product_id'],
                                    'name' => $item['name'] . ' (' . $variant_title . ')',
                                    'quantity' => $mode ? $quantity : 0,
                                    'manuel_closed' => $manuel_closed,
                                    'marketplace_id' => $item['marketplace_product_id'],
                                    'maximum_order' => $product_data['defaults']['maximum_order'],
                                    'shipping_time' => $product_data['defaults']['shipping_time'],
                                    'barcode' => $item['barcode'],
                                    'model' => $item['model'],
                                    'action' => implode(',', $action),
                                    'stock_code' => $item['stock_code'],
                                    'is_variant' => true,
                                    'price' =>   $list_price + $query->row['price'],
                                    'list_price' => $customer_list_price ? $customer_list_price : $this->entegrasyon->calculatePrice($list_price + $query->row['price'], $product_data['defaults'], $product_info['tax_class_id'], $code, $product_info),

                                );
                                if ($sale_price || ($customer_sale_price && $customer_list_price) ) {
                                    $product['sale_price'] = $customer_sale_price ? $customer_sale_price : $this->entegrasyon->calculatePrice($sale_price + $query->row['price'], $product_data['defaults'], $product_info['tax_class_id'], $code, $product_info);
                                }

                                $update_list[] = $product;

                            } else {


                            }

//Çok Seçenekten oluşuyorsa
                        } else {
                            $variant_title = '';

                            $action = array();
                            $quantity = 99999;
                            $oprice = 0;



                            foreach ($variant_infos as $variant_info) {


                                $variant = explode('+-', $variant_info);
                                $product_option_id = $variant[3];
                                $variant_title .= '-' . $variant[0] . ':' . $variant[2];

                                $query = $this->query("select quantity,price from " . DB_PREFIX . "product_option_value where product_id='" . $item['oc_product_id'] . "' and option_value_id='" . $product_option_id . "' ");
                                if (!isset($query->row['quantity'])) {
                                    //Ürüne ait böyle bir varyant yok
                                    $quantity = 0;
                                    $oprice = 0;

                                } else {
                                    if ($query->row['quantity'] < $quantity) {
                                        $quantity = $query->row['quantity'];
                                    }
                                    if ($query->row['price']) {
                                        $oprice += $query->row['price'];
                                    }
                                }
                            }

                            $quantity = ($quantity <= 0 || $product_info['quantity'] <= 0 )  ? 0 : $quantity;

                            if ($this->config->get('easy_setting_critical_stock') && $quantity > 0) {
                                $quantity = $quantity <= $this->config->get('easy_setting_critical_stock') ? 0 : $quantity;
                                if ($quantity <= $this->config->get('easy_setting_critical_stock')) {
                                    $action[] = 'Kritik stok sebebiyle stok sıfırlandı, kritik stok:' . $this->config->get('easy_setting_critical_stock');
                                }
                            }

                            $marketplace_data = $this->entegrasyon->getMarketPlaceProductForMarket($item['oc_product_id'], $code);
                            if ($mode == 1) {
                                if ($marketplace_data['approval_status'] && !$marketplace_data['sale_status'] && $quantity > 0) {
                                    $quantity = 0;
                                    $manuel_closed = true;
                                    $action[] = 'Ürün kullanıcı tarafından satışa kapatılmış, tekrar satışa açılana kadar kapalı kalacaktır.';

                                }else {
                                    $manuel_closed=false;
                                }

                            }
                            if (!$marketplace_data['approval_status'] && !$marketplace_data['sale_status'] && $mode == 2) {
                                $action[] = 'Ürün henüz pazaryeri tarafından onaylanmadığı için satışa açılamamıştır';

                            }

                            $product = array(
                                'product_id' => $item['oc_product_id'],
                                'quantity' => $mode ? $quantity : 0,
                                'name' => $item['name'] . ' (' . $variant_title . ')',
                                'manuel_closed' => $manuel_closed,
                                'marketplace_id' => $item['marketplace_product_id'],
                                'maximum_order' => $product_data['defaults']['maximum_order'],
                                'shipping_time' => $product_data['defaults']['shipping_time'],
                                'barcode' => $item['barcode'],
                                'model' => $item['model'],
                                'is_variant' => true,
                                'action' => implode(',', $action),
                                'stock_code' => $item['stock_code'],
                                'list_price' => $customer_list_price ? $customer_list_price : $this->entegrasyon->calculatePrice($list_price + $query->row['price'] + $oprice, $product_data['defaults'], $product_info['tax_class_id'], $code, $product_info),
                            );

                            if ($sale_price || ($customer_sale_price && $customer_list_price) ) {

                                $product['sale_price'] =$customer_sale_price ? $customer_sale_price : $this->entegrasyon->calculatePrice($sale_price + $oprice, $product_data['defaults'], $product_info['tax_class_id'], $code, $product_info);
                            }

                            $update_list[] = $product;


                        }


                    } else {
                        //ürün varyantı opencartttan silinmiş, bu durumda ana ürün verileri ile ürünü güncelliyoruz.


                        $action = array();
                        $quantity = $product_query->row['quantity'];
                        $quantity = ($quantity <= 0 || $product_info['quantity'] <= 0 )  ? 0 : $quantity;

                        if ($this->config->get('easy_setting_critical_stock') && $quantity > 0) {
                            $quantity = $quantity <= $this->config->get('easy_setting_critical_stock') ? 0 : $quantity;
                            if ($quantity <= $this->config->get('easy_setting_critical_stock')) {
                                $action[] = 'Kritik stok sebebiyle stok sıfırlandı, kritik stok:' . $this->config->get('easy_setting_critical_stock');
                            }
                        }

                        $marketplace_data = $this->entegrasyon->getMarketPlaceProductForMarket($item['oc_product_id'], $code);
                        if ($mode == 1) {
                            if ($marketplace_data['approval_status'] && !$marketplace_data['sale_status'] && $quantity > 0) {
                                $quantity = 0;
                                $manuel_closed = true;
                                $action[] = 'Ürün kullanıcı tarafından satışa kapatılmış, tekrar satışa açılana kadar kapalı kalacaktır.';

                            }else {
                                $manuel_closed=false;
                            }

                        }
                        if (!$marketplace_data['approval_status'] && !$marketplace_data['sale_status'] && $mode == 2) {
                            $action[] = 'Ürün henüz pazaryeri tarafından onaylanmadığı için satışa açılamamıştır';

                        }

                        $product = array(
                            'product_id' => $item['oc_product_id'],
                            'name' => $item['name'],
                            'quantity' => $mode ? $quantity : 0,
                            'manuel_closed' => $manuel_closed,
                            'marketplace_id' => $item['marketplace_product_id'],
                            'maximum_order' => $product_data['defaults']['maximum_order'],
                            'shipping_time' => $product_data['defaults']['shipping_time'],
                            'barcode' => $item['barcode'],
                            'action' => implode(',', $action),
                            'model' => $item['model'],
                            'stock_code' => $item['stock_code'],
                            'is_variant' => true,
                            'list_price' => $customer_list_price ? $customer_list_price: $this->entegrasyon->calculatePrice($product_info['price'], $product_data['defaults'], $product_info['tax_class_id'], $code, $product_info),
                        );
                        if ($sale_price || ($customer_sale_price && $customer_list_price) ) {
                            $product['sale_price'] = $customer_sale_price ? $customer_sale_price :$this->entegrasyon->calculatePrice($sale_price, $product_data['defaults'], $product_info['tax_class_id'], $code, $product_info);
                        }

                        if ($code == 'n11') {
                            $custome_date = unserialize($item['custom_data']);
                            if (isset($custome_date['stockItems']['stockItem']['id'])) {
                                $product['stock_id'] = $custome_date['stockItems']['stockItem']['id'];
                            } else {
                                $variants = $this->entegrasyon->getOptionsforOthers($item['oc_product_id'], $code, $this->entegrasyon->getPoductVariants($item['oc_product_id']));

                                $total_sale_status=false;
                                foreach ($variants as $variant) {

                                    if ($mode && $this->config->get('easy_setting_critical_stock') && $variant['quantity'] > 0) {
                                        $variant['quantity'] = $variant['quantity'] <= $this->config->get('easy_setting_critical_stock') ? 0 : $variant['quantity'];

                                        if($variant['quantity']){
                                            $total_sale_status=true;
                                        }

                                    }

                                    if(!$mode){

                                        $variant['quantity']=0;
                                    }

                                    if ($mode == 1) {
                                        if ($marketplace_data['approval_status'] && !$marketplace_data['sale_status'] && $variant['quantity'] > 0) {
                                            $variant['quantity'] = 0;
                                            $manuel_closed = true;

                                        }else {
                                            $manuel_closed=false;
                                        }

                                    }

                                    if($mode==1){

                                        $product['sale_status']=0;
                                    }

                                    $product['variant'][]=$variant;
                                }


                            }
                        }
                        $update_list[] = $product;

                    }


                } else {

                    $action = array();
                    $quantity = $product_info['quantity'] <= 0 ? 0 : $product_info['quantity'];
                    if ($this->config->get('easy_setting_critical_stock') && $product_info['quantity'] > 0) {
                        $quantity = $product_info['quantity'] <= $this->config->get('easy_setting_critical_stock') ? 0 : $product_info['quantity'];
                        if ($product_info['quantity'] <= $this->config->get('easy_setting_critical_stock')) {
                            $action[] = 'Kritik stok sebebiyle stok sıfırlandı, kritik stok:' . $this->config->get('easy_setting_critical_stock');
                        }

                    }
                    $marketplace_data = $this->entegrasyon->getMarketPlaceProductForMarket($item['oc_product_id'], $code);

                    if ($mode == 1) {

                        if ($marketplace_data['approval_status'] && !$marketplace_data['sale_status'] && $quantity > 0) {
                            $quantity = 0;
                            $manuel_closed = true;
                            $action[] = 'Ürün kullanıcı tarafından satışa kapatılmış, ürün satışa açılana kadar güncellenmeyecektir.';

                        }else {
                            $manuel_closed=false;
                        }

                    }
                    if (!$marketplace_data['approval_status'] && !$marketplace_data['sale_status'] && $mode == 2) {
                        $action[] = 'Ürün henüz pazaryeri tarafından onaylanmadığı için satışa açılamamıştır';
                    }

                    $product = array(
                        'product_id' => $item['oc_product_id'],
                        'quantity' => $mode ? $quantity : 0,
                        'name' => $item['name'],
                        'manuel_closed' => $manuel_closed,
                        'marketplace_id' => $item['marketplace_product_id'],
                        'maximum_order' => $product_data['defaults']['maximum_order'],
                        'shipping_time' => $product_data['defaults']['shipping_time'],
                        'barcode' => $item['barcode'],
                        'model' => $item['model'],
                        'action' => implode(',', $action),
                        'is_variant' => false,
                        'stock_code' => $item['stock_code'],
                        'list_price' => $customer_list_price ? $customer_list_price : $this->entegrasyon->calculatePrice($product_info['price'], $product_data['defaults'], $product_info['tax_class_id'], $code, $product_info),
                    );
                    if ($sale_price || ($customer_sale_price && $customer_list_price) ) {
                        $product['sale_price'] = $customer_sale_price ? $customer_sale_price : $this->entegrasyon->calculatePrice($sale_price, $product_data['defaults'], $product_info['tax_class_id'], $code, $product_info);
                    }
                    if ($code == 'n11') {

                        $custome_date = unserialize($item['custom_data']);
                        if (isset($custome_date['stockItems']['stockItem']['id'])) {
                            $product['stock_id'] = $custome_date['stockItems']['stockItem']['id'];
                        }


                    }
                    $update_list[] = $product;
                }


            } else {
                // echo 'eklenmedi';

            }
        }


        return $update_list;


    }


    private function query($sql)
    {
        try {

            return $this->db->query($sql);
        } catch (Exception $exception) {

            echo $exception->getMessage();
        }
    }


}

