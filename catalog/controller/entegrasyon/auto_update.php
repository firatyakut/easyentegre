<?php

class ControllerEntegrasyonAutoUpdate extends Controller
{

    private $reg = '';

    public function __construct($registry)
    {

        parent::__construct($registry);

        $this->reg = $registry;
    }

    public function update()
    {

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $limit = 20;
        $start = ($page - 1) * $limit;


        $total = $this->db->query("SELECT * FROM `oc_es_market_product` WHERE `code` LIKE 'ty' and sale_status=1")->num_rows;

        $query = $this->db->query("SELECT * FROM `oc_es_market_product` WHERE `code` LIKE 'ty' and sale_status=1 limit $start,$limit ");
        $total_page = round($total / $limit);


        $i = 1;

        $update_list = array();
        foreach ($query->rows as $item) {

            $barcode = $item['barcode'];

            if (mb_substr($barcode, -5) == '-2249' || mb_substr($barcode, -2) == '-1') {

                $orginal_barcode = str_replace('-2249', '', $barcode);


                $selling_price = $this->get_from_trendyol($orginal_barcode);


                if ($selling_price) {

                    if ($this->calculate_price($selling_price) > $selling_price) {
                        $update_list['items'][] = array(
                            "barcode" => $barcode,
                            // "orjinal" =>$selling_price,
                            "salePrice" => $this->calculate_price($selling_price),
                            "listPrice" => $this->calculate_price($selling_price),
                            "quantity" => 3,

                        );
                    }

                    // echo "barcode:" . $barcode . " - benim fiyat=" . $item['sale_price'] . "- Trendyol fiyat=" . $selling_price . '<br>';


                } else {

                    $update_list['items'][] = array(
                        "barcode" => $barcode,
                        "quantity" => 0,
                        "salePrice" => 0,
                        "listPrice" => 0
                    );

                }


            }


            $i++;


        }


        if ($update_list['items']) {

            $this->update_on_tredyol($update_list);

        }

        echo $page;

        if ($page < $total_page) {
            $page++;
            echo '<meta http-equiv="refresh" content="1;url=https://www.easytekno.com.tr/index.php?route=entegrasyon/auto_update/update&page=' . $page . '" />';
        } else {
            echo 'Tüm ürünler güncellendi';
        }


    }

    public function update_cron()
    {

        $query = $this->udb->query("select * from " . DB_PREFIX . "auto_update where auto_update_id=1");


        $page = $query->row['current_page'];


        $limit = 20;
        $start = ($page - 1) * $limit;


        $total = $this->db->query("SELECT * FROM `oc_es_market_product` WHERE `code` LIKE 'ty' ")->num_rows;

        $query = $this->db->query("SELECT * FROM `oc_es_market_product` WHERE `code` LIKE 'ty'  limit $start,$limit ");
        $total_page = round($total / $limit);


        $i = 1;

        $update_list = array();
        foreach ($query->rows as $item) {

            $orginal_barcode = false;


            $barcode = $item['barcode'];

            if (mb_substr($barcode, -5) == '-2249') {

                $orginal_barcode = str_replace('-2249', '', $barcode);

            } else if (mb_substr($barcode, -2) == '-1') {
                $orginal_barcode = str_replace('-1', '', $barcode);

            }

            if ($orginal_barcode) {


                $selling_price = $this->get_from_trendyol($orginal_barcode);


                //if ($this->calculate_price($selling_price) > $item['sale_price']) {

                    $update_list['items'][] = array(
                        "barcode" => $barcode,
                        // "orjinal" =>$selling_price,
                        "salePrice" => $this->calculate_price($selling_price),
                        "listPrice" => $this->calculate_price($selling_price),
                        "quantity" => 2,

                    );
                    // echo "barcode:" . $barcode . " - benim fiyat=" . $item['sale_price'] . "- Trendyol fiyat=" . $selling_price . '<br>';
                //}

            } else {

                $update_list['items'][] = array(
                    "barcode" => $barcode,
                    "quantity" => 0,
                    "salePrice" => 0,
                    "listPrice" => 0
                );

            }

            $i++;
        }





        //print_r($update_list['items']);

        $status = 0;
        if ($update_list['items']) {

            $result = $this->update_on_tredyol($update_list);

            print_r($result);

            if (isset($result['batchRequestId'])) {
                $status = 1;

            }
        }
        $page++;

        if ($page >= $total_page) {

            $page = 1;
        }

        $this->db->query("update " . DB_PREFIX . "auto_update SET current_page='" . $page . "', status='" . $status . "',total_page='" . $total_page . "',date_modified=NOW() where auto_update_id=1");

    }

    public
    function update_test()
    {

        //$query = $this->db->query("select * from ".DB_PREFIX."auto_update where auto_update_id=1");

        $page = 1;

        // $page = $query->row['current_page'];

        $product_id=8531;

        $limit = 20;
        $start = ($page - 1) * $limit;


        $total = $this->db->query("SELECT * FROM `oc_es_market_product` WHERE `code` LIKE 'ty' and sale_status=1")->num_rows;

        $query = $this->db->query("SELECT * FROM `oc_es_market_product` WHERE `code` LIKE 'ty' and sale_status=1 and market_product_id='".$product_id."' limit $start,$limit ");
        $total_page = round($total / $limit);


        $i = 1;

        $update_list = array();
        foreach ($query->rows as $item) {

            $barcode = $item['barcode'];
            $orginal_barcode = false;
            if (mb_substr($barcode, -5) == '-2249') {

                $orginal_barcode = str_replace('-2249', '', $barcode);

            } else if (mb_substr($barcode, -2) == '-1') {

                $orginal_barcode = str_replace('-1', '', $barcode);


            }

            if ($orginal_barcode) {


                $selling_price = $this->get_from_trendyol($orginal_barcode);


                if ($selling_price) {

echo $orginal_barcode;
return;

                    $update_list['items'][] = array(
                        "barcode" => $barcode,
                        // "orjinal" =>$selling_price,
                        "salePrice" => $this->calculate_price($selling_price),
                        "listPrice" => $this->calculate_price($selling_price),
                        "quantity" => 3,

                    );
                    // echo "barcode:" . $barcode . " - benim fiyat=" . $item['sale_price'] . "- Trendyol fiyat=" . $selling_price . '<br>';


                } else {

                    $update_list['items'][] = array(
                        "barcode" => $barcode,
                        "quantity" => 0,
                        "salePrice" => 0,
                        "listPrice" => 0
                    );

                }


            }


            $i++;


        }



        print_r($update_list);return

            $status = 0;
        if ($update_list['items']) {

            $result = $this->update_on_tredyol($update_list);

            if (isset($result['batchRequestId'])) {
                $status = 1;

            }
        }
        $page++;

        if ($page >= $total_page) {

            $page = 1;
        }

        $this->db->query("update " . DB_PREFIX . "auto_update SET current_page='" . $page . "', status='" . $status . "',total_page='" . $total_page . "',date_modified=NOW() where auto_update_id=1");

    }

    private function calculate_price($ty_price)
    {

        $price = $ty_price;

        if ($ty_price > 0 && $ty_price < 100) {

            $price = $ty_price * 4;
        } else if ($ty_price > 100 && $ty_price < 300) {
            $price = $ty_price * 3;
        } else if ($ty_price > 300 && $ty_price < 500) {
            $price = $ty_price * 2.5;
        } else if ($ty_price > 500 && $ty_price < 1000) {
            $price = $ty_price * 2.2;
        } else if ($ty_price > 1000 && $ty_price < 2000) {
            $price = $ty_price * 2;
        } else if ($ty_price > 2000 && $ty_price < 3000) {
            $price = $ty_price * 1.7;
        } else if ($ty_price > 3000 && $ty_price < 5000) {
            $price = $ty_price * 1.7;

        } else if ($ty_price > 5000) {
            $price = $ty_price * 1.5;
        }

        return $price;

    }

    private
    function get_from_trendyol($barcode)
    {

        $url = "https://public.trendyol.com/discovery-web-searchgw-service/v2/api/infinite-scroll/sr?q=" . $barcode;


        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        $datam = curl_exec($curl);
        $content = json_decode($datam, 1);


        curl_close($curl);

        if (!isset($content['error'])) {

            return isset($content['result']['products'][0]['price']['discountedPrice']) ? $content['result']['products'][0]['price']['discountedPrice'] : 0;

        } else return 0;

    }

    private
    function update_on_tredyol($update_list)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.trendyol.com/sapigw/suppliers/112836/products/price-and-inventory',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($update_list),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic M21jRklJbmh4d0tBbHg4ZnkwUk06a1hGY3R4dFBGZXZ6VWF2RVFUMnA=',
                'Content-Type: application/json',
                'Cookie: __cfruid=8b5d2adb208204a4811c0f78b79f05d976a1a90d-1685299400; _cfuvid=II_34tNJCaQvXfH1yMxntUGAhL7ob.ud.M3.9I8DdTI-1685299400267-0-604800000; __cflb=02DiuEkjxji3pxUywYoR8hKcjQvCpYR5XkY58QCPvGugx'
            ),
        ));

        $response = json_decode(curl_exec($curl), 1);

        curl_close($curl);

        sleep(10);

        if (isset($response['batchRequestId'])) {


            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.trendyol.com/sapigw/suppliers/112836/products/batch-requests/' . $response['batchRequestId'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Basic M21jRklJbmh4d0tBbHg4ZnkwUk06a1hGY3R4dFBGZXZ6VWF2RVFUMnA=',
                    'Cookie: __cfruid=8b5d2adb208204a4811c0f78b79f05d976a1a90d-1685299400; _cfuvid=II_34tNJCaQvXfH1yMxntUGAhL7ob.ud.M3.9I8DdTI-1685299400267-0-604800000; __cflb=02DiuEkjxji3pxUywYoR8hKcjQvCpYR5XkY58QCPvGugx'
                ),
            ));

            $response = json_decode(curl_exec($curl), 1);

            curl_close($curl);

            foreach ($response['items'] as $item) {

            }

            return $response;


        }


    }


}