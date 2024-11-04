<?php

class ControllerEntegrasyonProductUpdate extends Controller
{

    private $reg = '';

    public function __construct($registry)
    {

        parent::__construct($registry);

        $this->reg = $registry;
    }


    public function index()
    {
        $page = 1;
        $limit = 100;
        $min_quantity = (int)$this->config->get('easy_setting_critical_stock');
        $start = ($page - 1) * $limit;

        $this->load->model("entegrasyon/general");

        $market_places = $this->model_entegrasyon_general->getMarketPlaces();

        $update_packages = array();

        foreach ($market_places as $market_place) {
            if ($market_place['status']) {

                $sql = "select count(*) as total from " . DB_PREFIX . "product p left join " . DB_PREFIX . "es_market_product mp on(p.product_id=mp.oc_product_id) where (mp.sale_status=1 and mp.approval_status=1) and (select count(*) as total from " . DB_PREFIX . "product_option where product_id=p.product_id) = 0 and  p.quantity <= $min_quantity and mp.code='" . $market_place['code'] . "'";
                echo $sql;

                $query_total = $this->db->query($sql);
                $query = $this->db->query("select mp.sale_price,mp.list_price,mp.marketplace_product_id,mp.barcode,mp.model,p.quantity,(select count(*) as total from " . DB_PREFIX . "product_option where product_id=p.product_id) as total_option from " . DB_PREFIX . "product p left join " . DB_PREFIX . "es_market_product mp on(p.product_id=mp.oc_product_id) where (mp.sale_status=1 and mp.approval_status=1) and  (select count(*) as total from " . DB_PREFIX . "product_option where product_id=p.product_id) = 0 and mp.code='" . $market_place['code'] . "' and  p.quantity <= $min_quantity limit $start,$limit  ");


                foreach ($query->rows as $row) {


                    $update_packages[$market_place['code']][] = $row;


                }

            }

        }

        print_r($update_packages);


    }


}