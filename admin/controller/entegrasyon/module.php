<?php
class ControllerEntegrasyonModule extends Controller {

    private $token_data;
    private $marketplaces=array();

    public function __construct($registry)
    {

        parent::__construct($registry);

        $this->load->model('entegrasyon/general');
        $this->token_data=$this->model_entegrasyon_general->getToken();
        $this->marketplaces = $this->model_entegrasyon_general->getActiveMarkets();

        if(!$this->config->get('mir_login')){

            $this->response->redirect($this->url->link('entegrasyon/setting/error','&error=no_user&'.$this->token_data['token_link'], true));

        }else if(!$this->marketplaces){

            $this->response->redirect($this->url->link('entegrasyon/setting/error','&error=no_api&'.$this->token_data['token_link'], true));

        }else if(!$this->config->get('module_entegrasyon_status')){
            $this->response->redirect($this->url->link('entegrasyon/setting/error','&error=no_module&'.$this->token_data['token_link'], true));

        }

    }

    public function index()
    {

        $this->document->setTitle('Modül Ayarları');

        $this->load->model('entegrasyon/general');
        $this->model_entegrasyon_general->loadPageRequired();

        $this->document->addStyle('view/stylesheet/entegrasyon/module.css');
        $this->document->addScript('https://app.shopside.io/_scripts/_common/_setup.js?v=1.2.5');

        $data['token_link'] = $this->token_data['token_link'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['modules'][]=array(
            'is_registered'=>$this->config->get('easymodule_efatura_status'),
            'code'    =>'efatura',
            'name'    =>'Earşiv Fatura Entegraysonu',
            'image'   =>HTTPS_CATALOG.'image/entegrasyon-logo/module-images/earsiv.png',


        );

        $data['modules'][]=array(
            'is_registered'=>$this->config->get('easymodule_hpsjet_status'),
            'code'    =>'hepsijet',
            'name'    =>'HepsiJet',
            'image'   =>'https://akillikobi.org.tr/media/uhflzeq3/hepsijet.png',


        );

        $data['modules'][]=array(
            'is_registered'=>$this->config->get('easymodule_buybox_status'),
            'code'    =>'buybox',
            'name'    =>'BuyBox Modülü',
            'image'   =>'https://res.cloudinary.com/crunchbase-production/image/upload/c_lpad,f_auto,q_auto:eco,dpr_1/v1495196508/kxh1oy14eza5uyystbzl.png',


        );

        $data['modules'][]=array(
            'is_registered'=>$this->config->get('easymodule_xml_status'),
            'code'    =>'xml',
            'name'    =>'Xml Modülü',
            'image'   =>'https://banner2.cleanpng.com/20181205/fu/kisspng-xml-scalable-vector-graphics-image-data-web-feed-jules-thuillier-ampquot-old-remote-app-brains-5c0881bd8bd6c5.2990553315440613735728.jpg',


        );

        $this->response->setOutput($this->load->view('entegrasyon/module/module_list', $data));
    }


    public function module_active()
    {

        $module_status=false;
        $code=$this->request->post['code'];
        $action=$this->request->post['action'];

        $message = (int)$action?'Aktif Edildi':'pasif yapıldı';
        $setting_data=array('easymodule_'.$code.'_status'=>(int)$action);


        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('easymodule_'.$code, $setting_data);

        echo json_encode(array('module_status'=>$action,'message'=>$message));

    }


    public function api_setting()
    {
        //$this->request->get['code']="efatura";
        $code =  $this->request->get['code'];
        $this->load->model('setting/setting');



        $this->load->model('entegrasyon/module/'.$code);

        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
          
            $result = $this->{"model_entegrasyon_module_".$code}->apiControl($this->request->post);

            if ($result['status']) {


                $this->model_setting_setting->editSetting($code,$this->request->post);

                echo json_encode(array('status' => true, 'message' => $result['message']));

            } else {

                echo json_encode(array('status' => false, 'message' => $result['message']));
            }

        } else {

            $data['api_info']=$this->model_setting_setting->getSetting($code);

            $data['token_link'] = $this->token_data['token_link'];
            $this->response->setOutput($this->load->view('entegrasyon/module/api/'.$code, $data));
        }
    }



}