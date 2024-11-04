<?php

class ControllerEntegrasyonEfatura extends Controller
{
    private $server = 'https://www.easyentegre.com/tr/pdfler/';

    public function index()
    {

        echo 'yes';

    }

    public function create_pdf()
    {
        $success=0;
        $failed=0;

        $debug = false;

        $query = $this->db->query("select * from " . DB_PREFIX . "es_invoice i left join ".DB_PREFIX."es_order o on(i.order_id=o.order_id) where  i.is_signed=1 and i.email_send=0 limit 0,1");


        //print_r($query->rows);return
        $uuid = array();
        if ($query->num_rows) {

            foreach ($query->rows as $row) {


                $uuid[] = array('uuid'=>$row['uuid'],'email'=>$row['email'],'customer'=>$row['first_name'].' '.$row['last_name']);

            }




            // if (is_file(DIR_ROOT . 'pdfler/' . $query->row['uuid'] . '.pdf')) {
            $results = $this->entegrasyon->efatura_connect(array('uuid' => $uuid), 'getpdf', $debug, false);



            if ($results['status']) {
                foreach ($results['result'] as $uuid => $result) {
                    //print_r($result);
                    $this->db->query("update " . DB_PREFIX . "es_invoice SET pdf_path='".$result['pdf_file']."', email_send='".$result['is_mail_send']."', invoice_data='" . $this->db->escape($result['data']) . "' where uuid='" . $uuid . "' ");
                    $success++;
                }


            }else {
                $failed++;

            }


            //}
        }


        echo json_encode(array('success'=>$success,'fail'=>$failed));
        //echo 'başarılı:'.$success.'-Başarısız:'.$failed;

    }

    public function sync_invoice()
    {

        $debug = false;

        $updated=0;
        $added=0;
        $deleted=0;

        $start = date('d/m/Y', strtotime('-30 days'));
        $end = date('d/m/Y');
        $results = $this->entegrasyon->efatura_connect(array('start' => $start, 'end' => $end), 'getInvoices', $debug, false);
        
        if($results['status']){
            foreach ($results['result']['data'] as $item) {

                $firstname = '';
                $lastname = '';
                $customer = explode(' ', $item['aliciUnvanAdSoyad']);

                if (count($customer) == 3) {
                    $firstname = $customer[0] . ' ' . $customer[1];
                    $lastname = $customer[2];

                    $altfirstname = $customer[0];
                    $altlastname =   $customer[1].' '.$customer[2];


                } else if(count($customer) == 2) {
                    $firstname = $customer[0];
                    $lastname = $customer[1];
                }else {
                    $firstname = $customer[0];
                    $lastname = "";
                }





                $query = $this->db->query("select * from " . DB_PREFIX . "es_order  WHERE (`first_name` LIKE '" . $firstname . "' AND `last_name` LIKE '" . $lastname . "') or (`first_name` LIKE '" . $altfirstname . "' AND `last_name` LIKE '" . $altlastname . "') and DATE_FORMAT(date_added, '%d-%m-%Y') ='".$item['belgeTarihi']."' ");
                //ECHO "select * from " . DB_PREFIX . "es_order  WHERE `first_name` LIKE '" . $firstname . "' AND `last_name` LIKE '" . $lastname . "' and DATE_FORMAT(date_added, '%d-%m-%Y') ='".$item['belgeTarihi']."' ";
                //  print_r($query->row);

                $is_signed=0;
                if($item['onayDurumu']=='Onaylandı'){

                    $is_signed=1;
                }else if($item['onayDurumu']=='Onaylanmadı'){

                    $is_signed=0;

                }


                $query_inv=$this->db->query("select * from ".DB_PREFIX."es_invoice where uuid='".$item['ettn']."'");
                if($query_inv->num_rows){

                    $this->db->query("update ".DB_PREFIX."es_invoice  SET is_signed=".$is_signed." where uuid='".$item['ettn']."' ");
                    $updated++;

                } else {

                    if($query->num_rows){

                        $order_info=$query->row;

                        $query_invoice = $this->db->query("select * from ".DB_PREFIX."es_invoice where order_id='".$order_info['order_id']."'");

                        if($query_invoice->num_rows){


                            if($item['onayDurumu']=='Silindi'){

                                $this->db->query("DELETE FROM " . DB_PREFIX . "es_invoice where order_id='".$order_info['order_id']."'");

                                $deleted++;
                            }else {

                                $this->db->query("UPDATE " . DB_PREFIX . "es_invoice SET is_signed='" . $is_signed . "'   where order_id='".$order_info['order_id']."'");

                                $updated++;

                            }

                            //UPDATE
                        }else {
                            //ADD

                            $this->db->query("INSERT INTO " . DB_PREFIX . "es_invoice SET order_id='" . $order_info['order_id'] . "',uuid='" . $item['ettn'] . "',is_signed='" . $is_signed . "' , document_id='" . $item['belgeNumarasi'] . "', date_added='" . $item['belgeTarihi'] . "'");

                            $added++;

                        }

                    }

                }

            }


            echo json_encode(array('status'=>true,'added'=>$added,'updated'=>$updated,'deleted'=>$deleted));

        }else {

            echo    json_encode($results);

        }


    }



    public function check_invoice()
    {
        $query1 = $this->db->query("select * from " . DB_PREFIX . "es_invoice where  email_send =1");

        foreach ($query1->rows as $row) {

            $query = $this->db->query("select * from " . DB_PREFIX . "es_order where order_id='" . $row['order_id'] . "'");

            $folder = date("Y/m/d", strtotime($query->row['date_added']));
            $pdf_folder = 'faturalar/' . $folder . '/';

            $file_name = $row['uuid'] . '.pdf';
            $file_path = $pdf_folder . $file_name;

            if (file_exists($file_path)) {
                echo $file_path . ':Dosya var<br>';

            } else {

                $this->db->query("update " . DB_PREFIX . "es_invoice SET email_send=0,pdf_path='', invoice_data='' where invoice_id='" . $row['invoice_id'] . "' ");
                echo $file_path . ':Dosya yok<br>';

            }

        }


    }


    public function send_invoice()
    {


        $query1 = $this->db->query("select * from " . DB_PREFIX . "es_invoice where  email_send =0  ");

        if ($query1->num_rows) {

            foreach ($query1->rows as $row) {
                $query = $this->db->query("select * from " . DB_PREFIX . "es_order where order_id='" . $row['order_id'] . "'");

                if ($query->row) {
                    $folder = date("Y/m/d", strtotime($query->row['date_added']));
                    $file_name = $row['uuid'] . '.pdf';
                    $file_path = $this->download_pdf($file_name, $folder);


                    if (file_exists('faturalar/' . $folder . '/' . $file_name)) {

                        $this->entegrasyon->efatura_connect(array('file' => $file_name), 'deletepdf', false, false);
                        $this->db->query("UPDATE " . DB_PREFIX . "es_invoice SET pdf_path='" . $file_path . "' where order_id = '" . $row['order_id'] . "' ");

                        $this->send_email($query->row, $file_path);

                    } else {
                        echo 'yok: faturalar/' . $folder . $file_name . '<br>';
                    }

                } else {

                    $this->db->query("delete from " . DB_PREFIX . "es_invoice where order_id='" . $row['order_id'] . "'");

                }


            }

        }

    }

    public function download_pdf($file_name, $folder)
    {

        $url = $this->server . $file_name;

        $pdf_folder = 'faturalar/' . $folder . '/';


        if (!file_exists($pdf_folder)) {
            mkdir($pdf_folder, 0777, true);
        }


        if (!file_exists($pdf_folder . $file_name)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $pdf_content = curl_exec($ch);
            if (curl_error($ch)) {
                return false;
            }

            if ($pdf_content == false) {
                $pdf_content = file_get_contents($url);
            }
            file_put_contents($pdf_folder . $file_name, $pdf_content);

            return $pdf_folder . $file_name;

        } else {

            echo 'var:' . $pdf_folder . $file_name . '<br>';
            return $pdf_folder . $file_name;
        }

    }

    public function delete_twice_invoice()
    {
        $query = $this->db->query("DELETE t1 FROM " . DB_PREFIX . "es_invoice t1 INNER JOIN " . DB_PREFIX . "es_invoice t2 WHERE t1.invoice_id < t2.invoice_id AND t1.order_id = t2.order_id ");

    }


}