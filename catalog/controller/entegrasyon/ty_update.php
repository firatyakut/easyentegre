<?php

class ControllerEntegrasyonTyUpdate extends Controller
{

    private $reg = '';

    public function __construct($registry)
    {

        parent::__construct($registry);

        $this->reg = $registry;
    }


    public function index()
    {


        if (isset($this->request->get['mode'])) {
            $mode = true;
        } else {
            $mode = false;
        }

        $debug = false;
        if (isset($this->request->get['debug'])) {

            $debug = true;
        }

        $this->load->model('entegrasyon/general');

        if (!$this->model_entegrasyon_general->checkPermission()) {

            echo json_encode(array('status' => false, 'message' => 'Gerçek Mağaza bilgileri kullanıldığı için Demo versiyonda Sipariş Kontrolüne izin verilmemektedir.'));
            return;

        }


        $this->load->model('entegrasyon/order/ty');
        $orders = $this->model_entegrasyon_order_ty->getOrders(false,'update');





        foreach ($orders as $order) {


            $this->checkStatues($order);

        }

       // print_r($res);
        return;

    }

    private function checkStatues($order)
    {

        // print_r($order);return;
        $statuses=array('ReadyToShip'=>2,'Delivered'=>5,'Cancelled'=>7,'Shipped'=>3,'Delivered'=>5);


        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order where email='" . $this->db->escape($order['customerEmail']) . "'");

        $order_info=$query->row;



        if($order_info){
            $order_id=$order_info['order_id'];
            $status = $order['status'];
            if($status=='Shipped'){

                $tracking_number=$order['cargoSenderNumber'];
                $tracking_link=$order['cargoTrackingLink'];
                if($order['cargoProviderName']=='Sürat Kargo Marketplace'){
                    $kargo='surat';
                    $name='Sürat Kargo';
                }else if($order['cargoProviderName']=='Yurtiçi Kargo Marketplace'){
                    $kargo='yurtici';
                    $name='Yurtiçi Kargo';

                }

                try {
                    $query=$this->db->query("select * from `oc_order_shipping` where order_id='".$order_id."'");
                    if($query->num_rows){

                        $this->db->query("update `oc_order_shipping` SET status='" . $status . "', code='".$kargo."',name='".$name."', status_message='" . $this->db->escape($status) . "',tracking_number='".$tracking_number."',tracking_url='".$tracking_link."', date_modified=NOW() where order_id='" . $order_id . "' ");

                    }else {

                        $this->db->query("insert into `oc_order_shipping` SET order_id='".$order_id."',status='" . $status . "', code='".$kargo."',name='".$name."', status_message='" . $this->db->escape($status) . "',tracking_number='".$tracking_number."',tracking_url='".$tracking_link."', date_added=NOW() ");

                    }
                }catch (Exception $exception){
                    echo $exception->getMessage();
                }


            }




            //$other=$order_info['order_status_id']==18 &&  $statuses[$status] ?18:2;
//echo $order['shipmentAddress']['firstName'].':'.$statuses[$status];

            $order_status_id=isset($statuses[$status])?$statuses[$status]:2;
          /*  if($order_info['order_status_id'] ==18 && $statuses[$status]==3){
            $this->db->query("update " . DB_PREFIX . "order_history SET order_status_id='" . $order_status_id . "', notify=0, comment='', date_added=NOW() where order_id='" . $order_id . "'");
            $this->db->query("update " . DB_PREFIX . "order SET order_status_id='" . $order_status_id . "' where order_id='" . $order_id . "'");
            }*/

            if(($order_info['order_status_id'] == 18) && $status !='Cancelled'){
           
            }else {

                $this->db->query("update " . DB_PREFIX . "order_history SET order_status_id='" . $order_status_id . "', notify=0, comment='', date_added=NOW() where order_id='" . $order_id . "'");
                $this->db->query("update " . DB_PREFIX . "order SET order_status_id='" . $order_status_id . "' where order_id='" . $order_id . "'");

            }

        }


    }

}