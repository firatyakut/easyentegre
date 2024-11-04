<?php

class ModelEntegrasyonEfatura extends Model
{

    public function addInvoice($order_id, $data)
    {


        $this->db->query("INSERT INTO " . DB_PREFIX . "es_invoice SET order_id='" . $order_id . "',uuid='" . $data['faturaUuid'] . "', document_id='" . $data['belgeNumarasi'] . "', date_added=NOW()");

        return $this->db->getLastId();

    }
    public function deleteInvoice($order_id)
    {


        $this->db->query("DELETE FROM " . DB_PREFIX . "es_invoice WHERE  order_id='" . $order_id . "'");

        return 1;

    }

    public function getKdvRange($kdv, $total_price, $price)
    {

        $oran = $kdv * 100 / $price;
        if ($oran < 19 && $oran > 17) {
            return 18;
        } else if ($oran < 9 && $oran > 7) {
            return 8;
        } else {
            return 0;
        }

    }

}