<?php

class ModelEntegrasyonMarketProduct extends Model
{
    public function getMarketProductsTotal($data)
    {
        $sql1 = "SELECT count(*) as total FROM " . DB_PREFIX . "es_market_product mp   left join " . DB_PREFIX . "product p ON(p.product_id=mp.oc_product_id)";
        $sql2 = " where mp.`code` = '" . $data['code'] . "' and mp.oc_product_id !=0 ";


        if ($data['filter_category']) {

            $sql1 .= " left join " . DB_PREFIX . "product_to_category p2c ON(p2c.product_id=mp.oc_product_id) ";
            $sql2 .= " AND p2c.category_id = '" . $data['filter_category'] . "' ";

        }

        if (!empty($data['filter_name'])) {

            $sql1 .= " left join " . DB_PREFIX . "product_description pd ON(pd.product_id=mp.oc_product_id) ";
            $sql2 .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%' and pd.language_id='".$this->config->get('config_language_id')."'";
        }
        if (!empty($data['filter_oc_product_id'])) {

            $sql2 .= " AND mp.oc_product_id LIKE '%" . $this->db->escape($data['filter_oc_product_id']) . "%'";
        }

        if(!empty($data['filter_manufacturer'])){

            $sql2 .= " AND p.manufacturer_id  = '".$data['filter_manufacturer']."' ";

        }


        if (!empty($data['filter_model'])) {
            $sql2 .= " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
        }


        if (isset($data['filter_match']) && $data['filter_match'] !== '*') {

            if ($data['filter_match'] == 1) {
                $sql2 .= " AND mp.product_id  != 0 ";

            } else {
                $sql2 .= " AND mp.product_id = 0";

            }

        }

        if (!empty($data['filter_barcode'])) {
            $sql2 .= " AND mp.barcode LIKE '" . $this->db->escape($data['filter_barcode']) . "%'";
        }
        if (!empty($data['filter_marketplace_product_id'])) {
            $sql2 .= " AND mp.marketplace_product_id LIKE '" . $this->db->escape($data['filter_marketplace_product_id']) . "%'";
        }



        if (isset($data['filter_marketplace_do'])) {

            if ($data['filter_marketplace'] != '*') {

                if($data['filter_marketplace_do']==1){
                    $sql2.=" and sale_status=1";
                }else if($data['filter_marketplace_do']==2){
                    $sql2.=" and sale_status=0 and approval_status=1";
                }else if($data['filter_marketplace_do']==3){

                    $sql2.="and sale_status=0 and approval_status=0";
                }

            }
        }




        if (!empty($data['filter_stock_prefix'])) {


            if ($data['filter_stock_prefix'] != '*') {
                if ($data['filter_stock'] !== false) {

                    $sql2 .= " AND p.quantity " . $data['filter_stock_prefix'] . " " . $data['filter_stock'] . " ";
                }
            }
        }


        $sql = $sql1 . $sql2;


        try {
            $query = $this->db->query($sql);
            return $query->row['total'];
        } catch (Exception $exception) {

            echo $exception->getMessage();
        }

    }

    public function getMarketProducts($data)
    {

        $sql1 = "SELECT *, mp.model as model,mp.barcode FROM " . DB_PREFIX . "es_market_product mp left join " . DB_PREFIX . "product p ON(p.product_id=mp.oc_product_id)  ";

        $sql2 = " where mp.`code` = '" . $data['code'] . "' and mp.oc_product_id != 0";


        if(isset($data['filter_category'])) {
            if (!empty($data['filter_category'])) {

                $sql1 .= " left join " . DB_PREFIX . "product_to_category p2c ON(p2c.product_id=mp.oc_product_id) ";
                $sql2 .= " AND p2c.category_id = '" . $data['filter_category'] . "'";
            }
        }


        if (!empty($data['filter_name']) ) {
            $sql1 .= " left join " . DB_PREFIX . "product_description pd ON(pd.product_id=mp.oc_product_id) ";
            $sql2 .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%' and pd.language_id='".$this->config->get('config_language_id')."'";
        }

        if(!empty($data['filter_manufacturer'])){

            $sql2 .= " AND p.manufacturer_id  = '".$data['filter_manufacturer']."' ";

        }



        if (!empty($data['filter_product_id'])) {

            $sql2.= " AND mp.product_id LIKE '%" . $this->db->escape($data['filter_product_id']) . "%'";

        }


        if (!empty($data['filter_model'])) {
            $sql2.= " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
        }


        if (isset($data['filter_match']) && $data['filter_match'] !== '*') {

            if ($data['filter_match'] == 1) {
                $sql2.= " AND mp.product_id  NOT LIKE '0'";

            } else {
                $sql2.= " AND mp.product_id LIKE '0'";

            }

        }


        if (isset($data['filter_marketplace_do'])) {

            if ($data['filter_marketplace'] != '*') {

                if($data['filter_marketplace_do']==1){
                    $sql2.=" and sale_status=1";
                }else if($data['filter_marketplace_do']==2){
                    $sql2.=" and sale_status=0 and approval_status=1";
                }else if($data['filter_marketplace_do']==3){

                    $sql2.=" and sale_status=0 and approval_status=0";
                }

            }
        }



        if (!empty($data['filter_barcode'])) {
            $sql2.= " AND mp.barcode LIKE '" . $this->db->escape($data['filter_barcode']) . "%'";
        }
        if (!empty($data['filter_marketplace_product_id'])) {
            $sql2.= " AND mp.marketplace_product_id LIKE '" . $this->db->escape($data['filter_marketplace_product_id']) . "%'";
        }

        if (!empty($data['filter_stock_prefix'])) {


            if ($data['filter_stock_prefix'] != '*') {
                if ($data['filter_stock'] !== false) {

                    $sql2.= " AND p.quantity " . $data['filter_stock_prefix'] . " " . $data['filter_stock'] . " ";

                }
            }
        }


        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }


            $sql2.= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $sql = $sql1 . $sql2;



        try{
            $query = $this->db->query($sql);
            return $query->rows;
        }catch (Exception $exception){

            echo $exception->getMessage();
        }



    }


    public function getTotalUnApprovedProducts($data)
    {

        $keyword = 'approval_status";i:0';
        $sql = "select count(*) as total from " . DB_PREFIX . "product_to_marketplace p2m left join " . DB_PREFIX . "product p ON(p.product_id=p2m.product_id) where p2m." . $data['code'] . " like '%" . $keyword . "%' ";


        if (!empty($data['filter_model'])) {
            $sql .= " AND p.model LIKE '" . $this->udb->escape($data['filter_model']) . "%'";
        }


        try {
            $query = $this->udb->query($sql);
            return $query->row['total'];

        } catch (Exception $exception) {

            echo $exception->getMessage();
        }


    }

    public function is_product_exists($stock_code)
    {
        $query = $this->udb->query("select product_id from product where stock_code='" . $this->udb->escape($stock_code) . "' ");
        if ($query->num_rows) {
            return true;
        }
        $query = $this->udb->query("select product_id from product_variant where stock_code='" . $this->udb->escape($stock_code) . "' ");

        if ($query->num_rows) {
            return true;
        }

        return false;

    }

    public function getMarketPlaceProductForMarket($product_id, $code)
    {

        $sql = "select * from product_to_marketplace where product_id='" . $product_id . "'";
        $query = $this->udb->query($sql);

        if ($query->num_rows) {
            return $query->row[$code] ? json_decode($query->row[$code], 1) : array();

        } else {
            return array();
        }
    }

    public function getUnApprovedProducts($data)
    {

        $keyword = 'approval_status";i:0';
        $sql = "select * from " . DB_PREFIX . "product_to_marketplace p2m left join " . DB_PREFIX . "product p ON(p.product_id=p2m.product_id) left join " . DB_PREFIX . "product_description pd ON(p.product_id=pd.product_id)  where p2m." . $data['code'] . " like '%" . $keyword . "%' ";

        if (!empty($data['filter_model'])) {
            $sql .= " AND p.model LIKE '" . $this->udb->escape($data['filter_model']) . "%'";
        }


        $sql .= " ORDER BY p2m.date_added DESC";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
        }

        //  $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];

        try {
            $query = $this->udb->query($sql);
            return $query->rows;
        } catch (Exception $exception) {

            echo $exception->getMessage();
        }


    }

    public function getPassiveProducts($code)
    {

        $sql = "select p.product_id from product p left join product_to_marketplace p2m ON(p.product_id=p2m.product_id) where p2m." . $code . "='' ";


        $query = $this->udb->query($sql);


        return $query->rows;

    }

    public function getCloseMarketPlaceProducts($code)
    {


        $status = 'sale_status";i:0';
        $alternatif_status = 'sale_status";b:0';

        $sql = " select * from product_to_marketplace WHERE " . $code . " LIKE '%" . $status . "%'  or  " . $code . " LIKE '%" . $alternatif_status . "%'    ";


        //  $sql = "select * from product_to_marketplace where $code != '' ";

        $query = $this->udb->query($sql);


        //foreach ($query->rows as $row){
        //   if (!unserialize($row[$code])['sale_status']){
        //  $products[] = $row;
        //  }

        // }


        return $query->rows;
    }

    public function getMarketPlaceProductsForMatch($product_id, $code, $product_model)
    {

        $query = $this->udb->query("select * from product_to_marketplace where product_id='" . $product_id . "'");
        if ($query->num_rows) {
            $row = $query->row;

            $variable = $row['' . $code . ''];

            if ($variable) {
                $settings = unserialize($variable);

                $temp = isset($settings['product_match']) ? $settings['product_match'] : 0;
                $settings['product_match'] = $product_model;

                if (!$product_model) {

                    unset($settings['product_match']);
                }


                if ($settings) {


                    $this->udb->query("update product_to_marketplace SET $code='" . $this->udb->escape(serialize($settings)) . "', date_modified=NOW() where product_id='" . $product_id . "' ");

                } else {

                    $this->udb->query("update product_to_marketplace SET $code='', date_modified=NOW() where product_id_id='" . $product_id . "' ");
                }


            } else {

                $insert_data = array('product_match' => $product_model);
                $this->udb->query("update product_to_marketplace SET $code='" . $this->udb->escape(serialize($insert_data)) . "',date_modified=NOW() where product_id ='" . $product_id . "' ");

            }
        } else {
            //Yeni Match_Id Oluştur;
            $insert_data = array('product_match' => $product_model);
            $this->udb->query("insert into product_to_marketplace SET $code='" . $this->udb->escape(serialize($insert_data)) . "', product_id='" . $product_id . "', date_modified=NOW() ");
        }

    }

    public function getTotalProductByMarketPlace($marketplace)
    {

        $query = $this->udb->query("SELECT COUNT(product_id) as total FROM `product_to_marketplace` WHERE `$marketplace` !=''");

        return $query->row['total'];

    }

    public function is_product_exists_in_product_to_marketplace($code, $product)
    {

        if (!$product['barcode']) return false;
        try {
            $query = $this->udb->query("select product_id from product_to_marketplace where  $code LIKE '%" . $product['barcode'] . "%' ");

            if ($query->num_rows) {
                return true;
            } else {


                return 0;
            }


        } catch (Exception $exception) {

            echo $exception->getMessage();

        }
    }

    public function getMarketPlaceUrl($code, $market_id, $cs_link = false)
    {


        if ($code == 'gg') {

            return 'http://urun.gittigidiyor.com/' . $market_id;
        } else if ($code == 'n11') {

            return 'http://urun.n11.com/xxx-P' . $market_id;
        } else if ($code == 'ty') {

            return 'https://www.trendyol.com/p/p-p-p-' . $market_id;
        } else if ($code == 'hb') {

            return 'https://www.hepsiburada.com/product-p-' . $market_id;

        } else if ($code == 'eptt') {

            return 'https://www.epttavm.com/item/' . $market_id . '_p';

        } else if ($code == 'cs') {

            return $cs_link;

        } else {

            return false;
        }


    }

}
