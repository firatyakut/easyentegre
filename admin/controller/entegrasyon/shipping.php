<?php
class ControllerEntegrasyonShipping extends Controller {

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

        $this->document->setTitle('Kargo Entegrasyon Ayarları');

        $this->load->model('entegrasyon/general');
        $this->model_entegrasyon_general->loadPageRequired();

        $this->document->addStyle('view/stylesheet/entegrasyon/shipping.css');
        $this->document->addScript('https://app.shopside.io/_scripts/_common/_setup.js?v=1.2.5');

        $data['token_link'] = $this->token_data['token_link'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['shippings'][]=array(
            'is_registered'=>$this->config->get('srt_status'),
            'code'    =>'srt',
            'name'    =>'Sürat Kargo',
            'image'   =>'https://www.hotelasistan.com/yonetim/firmalogolari/170008898.jpg',


        );

        $data['shippings'][]=array(
            'is_registered'=>$this->config->get('yrt_status'),
            'code'    =>'yrt',
            'name'    =>'Yurtiçi Kargo',
            'image'   =>'https://www.kobi-efor.com.tr/images/haberler/yurtici_kargo_turkiyenin_en_degerli_ilk_100_markasi_arasinda_h3003.jpg',


        );

        $this->response->setOutput($this->load->view('entegrasyon/shipping/shipping', $data));
    }


    public function api_setting()
    {

        $code =  $this->request->get['code'];
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {

            $post_data = array('api_info' => $this->request->post, 'request_data' => array());
            $result = $this->easyshipping->clientConnect($post_data, 'check_api', 'srt', false);


            if ($result['status']) {
                $this->request->post[$code.'_status']=true;
              $this->model_setting_setting->editSetting($code,$this->request->post);

                echo json_encode(array('status' => true, 'message' => $result['message']));

            } else {

                echo json_encode(array('status' => false, 'message' => $result['message']));
            }

        } else {

                $data['api_info']=$this->model_setting_setting->getSetting('srt');
            $data['token_link'] = $this->token_data['token_link'];
            $this->response->setOutput($this->load->view('entegrasyon/shipping/api/.'.$code, $data));
        }
    }

    

}