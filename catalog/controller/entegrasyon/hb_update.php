<?php

class ControllerEntegrasyonHbUpdate extends Controller
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


        $this->load->model('entegrasyon/order/hb');
        $res = $this->model_entegrasyon_order_hb->getOrders(false,'update');
         print_r($res);
        return;


    }
}