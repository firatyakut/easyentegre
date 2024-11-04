<?php

class ControllerEntegrasyonEfatura extends Controller
{
    private $server = 'http://www.egitimsetleri.com/tr/pdfler/';


    public function invoice()
    {
        //$this->request->post['order_list']=array(69);
        if(!isset($this->request->post['order_list'])){

            echo json_encode(array('status'=>false,'message'=>'Lütfen önce seçim yapınız'));

            return;
        }

        $this->load->language('sale/order');

        $data['title'] = $this->language->get('text_invoice');

        if ($this->request->server['HTTPS']) {
            $data['base'] = HTTPS_SERVER;
        } else {
            $data['base'] = HTTP_SERVER;
        }
        //$this->load->model('entegrasyon/efatura');


        $this->load->model('entegrasyon/order');
        $this->load->model('entegrasyon/efatura');

        $this->load->model('setting/setting');

        $data['orders'] = array();

        $orders = array();


        $orders = $this->request->post['order_list'];


        // $orders = array(457,456,455,454,453,452,451,450,449,448,447,446,445);//$this->request->post['order_id'];


        $order_datas = array();
        foreach ($orders as $order_id) {
            $order_info = $this->model_entegrasyon_order->getOrder($order_id);

            if ($order_info) {

                $product_data = array();

                $products = $this->model_entegrasyon_order->getOrderedProducts($order_id);


                $total_price = 0;
                $total_tax = 0;
                $total_price_with_tax = 0;

                foreach ($products as $product) {


                    $product_data[] = array(
                        'name' => $product['name'],
                        'model' => $product['model'],
                        'quantity' => $product['quantity'],
                        'price' => $product['price'],
                        'kdv_oran' => $product['kdv_oran'],
                        'tax' => $product['kdv'],
                        'discount' => $product['discount'],
                        'total' => $product['price']*$product['quantity']
                    );

                    $total_price += $product['price'];
                    $total_tax += $product['kdv'];
                    $total_price_with_tax += $product['list_price'];
                }


                $payment_info = unserialize($order_info['payment_info']);


                $order_info['products'] = $product_data;
                $order_info['total_price'] = $total_price;
                $order_info['total_kdv'] = $total_tax;
                $order_info['payment_info'] = $payment_info;


                $order_datas[] = $order_info;


            }


        }




        $results = $this->entegrasyon->efatura_connect($order_datas, 'create', false, false);

        //$result=  $this->model_fatura_efatura->create($order_info);

        $return_data['status']=$results['status'];
        if ($results['status']) {
            foreach ($results['result'] as $order_id => $result) {
                if ($result['status']) {

                    $this->model_entegrasyon_efatura->addInvoice($order_id, $result['result']);
                    $return_data['result'][$order_id]=array('status'=>true);

                }else {

                    $return_data['result'][$order_id]=array('status'=>false,'message'=>$result['message']);

                }
            }
        }



        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($return_data));


    }

    public function delete_invoice()
    {

        $result_data=array();

if(!isset($this->request->post['order_list'])){

    echo json_encode(array('status'=>false,'message'=>'Lütfen önce seçim yapınız'));

    return;

}
        $orders = $this->request->post['order_list'];

        $uuid_datas = array();
        foreach ($orders as $order_id) {
            $query = $this->db->query("select * from " . DB_PREFIX . "es_invoice where order_id='" . $order_id . "'");

            if ($query->num_rows) {
                $invoice_info = $query->row;

                if($invoice_info['is_signed']){

                    $result_data[$order_id]=array('status'=>false,'message'=>'İmzalanmış fatura silinemez, faturayı iptal etmek için Gib eportaldan giriş yapınız');

                }else {
                    $uuid_datas[$order_id] = $invoice_info['uuid'];

                }
            }else {

                $result_data[$order_id]=array('status'=>false,'message'=>'Seçilen sipariş henüz faturalanmamış!');


            }


        }




        if($uuid_datas){
            $results = $this->entegrasyon->efatura_connect($uuid_datas, 'delete_invoice', true, false);

            if ($results['status']) {

                $this->load->model("entegrasyon/efatura");

                foreach ($results['result'] as $order_id => $result) {
                    if ($result['status']) {

                        if( $this->model_entegrasyon_efatura->deleteInvoice($order_id)){

                            $result_data[$order_id]=array('status'=>true,'message'=>'Fatura başarıyla silindi!');
                        }

                    }else {

                        $result_data[$order_id]=$result;

                    }
                }
            }


        }else {

          $results['status']=true;

        }








        $results['result']=$result_data;


        echo json_encode($results);

    }

    public function sync_invoice()
    {

        $debug = false;

        $updated=0;
        $added=0;
        $deleted=0;

        $start = date('d/m/Y', strtotime('-60 days'));
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
            } else if(count($customer) == 2) {
                $firstname = $customer[0];
                $lastname = $customer[1];
            }else {
                $firstname = $customer[0];
                $lastname = "";
            }


            $query = $this->db->query("select * from " . DB_PREFIX . "es_order  WHERE `first_name` LIKE '" . $firstname . "' AND `last_name` LIKE '" . $lastname . "' and DATE_FORMAT(date_added, '%d-%m-%Y') ='".$item['belgeTarihi']."' ");

            $is_signed=0;
            if($item['onayDurumu']=='Onaylandı'){

                $is_signed=1;
            }else if($item['onayDurumu']=='Onaylanmadı'){

                $is_signed=0;

            }

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


        echo json_encode(array('status'=>true,'added'=>$added,'updated'=>$updated,'deleted'=>$deleted));

        }else {

         echo    json_encode($results);

        }


    }


    public function send_invoice_mail()
    {

        //$order_id=$this->request->post['order_id'];
        $order_id=1134;
        $query_invoice= $this->db->query("select * from ".DB_PREFIX."es_invoice where order_id='".$order_id."'");
        $query_order= $this->db->query("select * from ".DB_PREFIX."es_order where order_id='".$order_id."'");




        if($query_invoice->num_rows){

echo 'selam';
            $order_info=$query_order->row;
            $invoice_info=$query_invoice->row;
            $folder = date("Y/m/d", strtotime($order_info['date_added']));
            $file_name = $invoice_info['uuid'] . '.pdf';
            $file_path = 'faturalar/' . $folder . '/' . $file_name;
           $pdf_file=  $this->download_pdf($file_name, $folder);

           echo $pdf_file;
           return;

            $status=false;

            echo $file_path;
            return;

            if (file_exists($file_path)) {

                $invoice_info = $query_invoice->row;
                $order_info = $query_order->row;
                $subject = 'Faturanız Hazır';
                $order_info['email']='dilegitim@gmail.com';
                $mail = new Mail();
                $mail->protocol = $this->config->get('config_mail_protocol');
                $mail->parameter = $this->config->get('config_mail_parameter');
                $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
                $mail->smtp_username = $this->config->get('config_mail_smtp_username');
                $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
                $mail->smtp_port = $this->config->get('config_mail_smtp_port');
                $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
                $mail->setTo($order_info['email']);
                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender(html_entity_decode('Efatura', ENT_QUOTES, 'UTF-8'));
                $mail->addAttachment($file_path);
                $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));

                $result = $mail->send();

                $status=true;
                echo 'Mail gönderdim';
            }

            echo json_encode(array('status'=>$status,'message'=>'mail gönder'));

        }

}

    public function download_pdf($file_name, $folder)
    {

        $url = $this->server . $file_name;

        $pdf_folder = 'faturalar/' . $folder . '/';



        if (!file_exists($pdf_folder)) {
            mkdir($pdf_folder, 0777, true);
        }
        echo $url;

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



    public function logout()
    {
        $debug = false;

        $result = $this->entegrasyon->efatura_connect(array(), 'logout', $debug, false);



        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($result));


    }



}
