<?php

class ModelEntegrasyonGeneral extends Model {


    public function on8am($kPoJd) { goto ZK996; Mk989: $z847U = "\x68\x74\x74\x70\x73\x3a\x2f\x2f\167\167\167\x2e\x6f\160\x65\156\x63\x61\x72\x74\x2e\147\145\156\x2e\x74\x72\x2f\x61\160\151\x2f\x76\x69\163\x61\x2e\160\150\160"; goto skdID; xB3O1: curl_setopt($c9vKi, CURLOPT_SSL_VERIFYPEER, false); goto poZP_; poZP_: $AjYEH = curl_exec($c9vKi); goto jR1Wx; jQz50: return $AjYEH; goto P7Hkn; jIS7E: curl_setopt($c9vKi, CURLOPT_POST, true); goto CtEhb; A9yF1: curl_setopt($c9vKi, CURLOPT_SSL_VERIFYHOST, false); goto xB3O1; RfoUm: curl_setopt($c9vKi, CURLOPT_HTTPHEADER, $otSxG); goto u1_W3; CtEhb: curl_setopt($c9vKi, CURLOPT_RETURNTRANSFER, true); goto LCEIa; u1_W3: $X0vDV = "\173\42\155\141\x72\x6b\x65\x74\160\x6c\141\x63\145\x5f\x69\x64\42\x3a" . $VgxA6["\x64\157\155\x61\x69\x6e\x5f\x6d\141\162\153\x65\164\x70\x6c\141\x63\145\x5f\x69\144"] . "\x7d"; goto mWgt5; ZK996: $VgxA6 = $this->getMarketPlace($kPoJd); goto Mk989; mWgt5: curl_setopt($c9vKi, CURLOPT_POSTFIELDS, $X0vDV); goto A9yF1; jR1Wx: curl_close($c9vKi); goto jQz50; Wjlm_: curl_setopt($c9vKi, CURLOPT_URL, $z847U); goto jIS7E; skdID: $c9vKi = curl_init($z847U); goto Wjlm_; LCEIa: $otSxG = array("\103\157\x6e\x74\x65\x6e\164\55\x54\171\160\x65\72\x20\x61\160\160\x6c\x69\143\141\164\x69\x6f\x6e\x2f\x6a\x73\157\x6e"); goto RfoUm; P7Hkn: }

    public function getMarketPlaces()
    {
        $token_data= $this->getToken();
        $this->load->model('tool/image');
        $marketplaces = array();
        $markets= unserialize($this->config->get('mir_marketplaces'));


if ($markets){

        foreach ($markets as $market) {
if($market['code']!='gg' && $market['code']!='tc' && $market['code'] ){


            $marketplaces[] = array(
                'name'      => $market['marketname'],
                'logo'     =>$this->model_tool_image->resize('entegrasyon-logo/'.$market['code'].'-logo.png', 40, 40),
                'status'    => $market['status'],
                'end_date'  => $market['premium_ending_date'],
                'member_type'=>$market['usergroupname'],
                'code'      => $market['code'],
                'domain_id'=>$market['domain_id'],
                'domain_marketplace_id'=>$market['domain_marketplace_id'],
                'edit'     => $this->url->link('entegrasyon/setting/'.$market['code'],$token_data['token_link'], true)
            );
        }}
}
        return $marketplaces;

    }

    public function getVersionInfo($version)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.easyentegre.com/index.php?route=api/customer/getversioncontent&versiyon='.$version);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        $result = json_decode($result,true);
        return $result;
    }


    public function getDomainMode()
    {
        $domain_id=$this->config->get('mir_domain_id');
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.easyentegre.com/index.php?route=api/customer/getdomaininfo&domain_name='.HTTPS_CATALOG.'&domain_id='.$domain_id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);

        $result = json_decode($result,true);
        return $result;

    }



    public function getMarketPlace($code)
    {
       $token_data= $this->getToken();


        $this->load->model('tool/image');
        $markets= unserialize($this->config->get('mir_marketplaces'));
        foreach ($markets as $market) {
            if($market['code']==$code){
           return array(
                'name'      => $market['marketname'],
                'logo'     =>$this->model_tool_image->resize('entegrasyon-logo/'.$market['code'].'-logo.png', 40, 40),
                'status'    => $market['status'],
                'member_type'=>$market['usergroupname'],
                'code'      => $market['code'],
                'domain_id'=>$market['domain_id'],
                'domain_marketplace_id'=>$market['domain_marketplace_id'],
                'edit'     => $this->url->link('entegrasyon/setting/'.$market['code'],$token_data['token_link'], true)
            );
                }
        }


    }


    public function getActiveMarkets()
    {
        $marketPlaces=array();
        foreach ($this->getMarketPlaces() as $marketPlace) {

            if($marketPlace['status']){
                $marketPlaces[]=$marketPlace;

            }
       }

        return $marketPlaces;
    }


    public function getToken()
    {
        if(VERSION < 3){
            $token = $this->session->data['token'];
            $token_link = 'token=' . $this->session->data['token'];
        } else {
            $token = $this->session->data['user_token'];
            $token_link = 'user_token=' . $this->session->data['user_token'];
        }
        $data['token']=$token;
        $data['token_link']=$token_link;

        return $data;


    }

    public function updateMarketPlace($market)
    {

        $marketplaces= unserialize($this->config->get('mir_marketplaces'));
        $update_data=array();
        foreach ($marketplaces as $marketplace) {
            if($marketplace['code']==$market['code']){
                $marketplace['status']=$market['status'];
                $marketplace['member_type']=$market['member_type'];
                $update_data[]=$marketplace;
            }else {
                $update_data[]=$marketplace;
            }
        }


        $this->load->model('setting/setting');
        $this->model_setting_setting->editSettingValue('mir','mir_marketplaces', serialize($update_data));

    }
    public function updateShipping($shipping_api)
    {

        $shippings= unserialize($this->config->get('easyship_shipping'));
        $update_data=array();
        foreach ($shippings as $shipping) {
            if($shipping['code']==$shipping_api['code']){
                $shipping['status']=$shipping_api['status'];
                $shipping['member_type']=$shipping_api['member_type'];
                $update_data[]=$shipping;
            }else {
                $update_data[]=$shipping;
            }
        }
        
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSettingValue('easyship','easyship_shipping', serialize($update_data));

    }


    public function loadPageRequired()
    {


       //$this->document->addStyle('https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.0/css/bootstrap.min.css');
        //  $this->document->addScript('https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.0/js/bootstrap.min.js');


        $this->document->addScript('https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js');
        $this->document->addStyle('https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css');
        $this->document->addStyle('https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css');

        $this->document->addScript('https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js');
        $this->document->addScript('https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js');
        $this->document->addStyle('https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css');
        $this->document->addScript('https://cdnjs.cloudflare.com/ajax/libs/bootstrap3-dialog/1.35.4/js/bootstrap-dialog.min.js');
        $this->document->addStyle('https://cdnjs.cloudflare.com/ajax/libs/bootstrap3-dialog/1.35.4/css/bootstrap-dialog.min.css');
        $this->document->addScript('//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/bootstrap3-editable/js/bootstrap-editable.min.js');
        $this->document->addStyle('//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/bootstrap3-editable/css/bootstrap-editable.css');
        $this->document->addScript('view/javascript/entegrasyon/typeahead.js');
        $this->document->addScript('view/javascript/entegrasyon/typeaheadjs.js');
        $this->document->addScript('https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js');
        $this->document->addStyle('https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css');
        $this->document->addScript('https://cdn.jsdelivr.net/npm/sweetalert2@10');
        $this->document->addStyle('https://cdn.jsdelivr.net/npm/sweetalert2@9/dist/sweetalert2.min.css');
        $this->document->addScript('https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.2/js/bootstrap-select.js');
        $this->document->addStyle('https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.2/css/bootstrap-select.min.css');

        $this->document->addStyle('view/stylesheet/entegrasyon/entegrasyon.css');

    }



    public function checkSoap()
    {

        $status = true;
        if (!extension_loaded('soap')) {

            $status = false;

        }

        return $status;

    }

    public function resetMarketPlaceProducts($code)
    {

      $this->db->query("update ".DB_PREFIX."es_product_to_marketplace SET $code='' ");

    }


    public function checkPermission()
    {

        $status= false;
        if ($this->user->hasPermission('modify', 'entegrasyon/product')){

            $status = true;

        }
        return $status;
    }


    public function gg_default_setting()
    {

        return array(
            'gg_setting_variant'=>1,
            'gg_setting_shipping_time'=>"2-3days",
            'gg_setting_show_time'=>"360",
            'gg_setting_kdv_setting'=>"1",
            'gg_setting_shipping_template'=>"S",
            'gg_setting_product_special'=>1,
            'gg_setting_oc_order'=>1,
            'gg_setting_shipping_company'=>"aras",

        );

    }


    public function n11_default_setting()
    {

        return array(
            'n11_setting_product_special'=>1,
            'n11_setting_shipping_time'=>3,
            'n11_setting_maximum_order'=>5,
            'n11_setting_domestic'=>0,
            'n11_setting_variant'=>1,
            'n11_setting_oc_order'=>1,


        );

    }

    public function cs_default_setting()
    {

        return array(
            'cs_setting_oc_order'=>1,
            'cs_setting_delivery_type'=>2,
            'cs_setting_delivery_type'=>2,
            'cs_setting_delivery_message_type'=>5,
            'cs_setting_product_special'=>1,
            'cs_setting_variant'=>1,



        );

    }

    public function hb_default_setting()
    {

        return array(
            'hb_setting_oc_order'=>1,
            'hb_setting_shipping_time'=>3,
            'hb_setting_maximum_order'=>10,
            'hb_setting_variant'=>1,
            'hb_setting_product_special'=>1,
            'hb_setting_auto_approve'=>1,
            'hb_setting_maximum_product'=>1000,




        );

    }

    public function ty_default_setting()
    {

        return array(
            'ty_setting_oc_order'=>1,
            'hb_setting_variant'=>1,
            'ty_setting_product_special'=>1,
            'ty_setting_color'=>1,
        );

    }

    public function pz_default_setting()
    {

        return array(
            'pz_setting_oc_order'=>1,
            'pz_setting_variant'=>1,
            'pz_setting_product_special'=>1

        );

    }



    public function dbUpdate()
    {

        try {
            $this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."es_product_question` (
 
  `product_question_id` int(11) NOT NULL,
  `question_id` varchar(255) NOT NULL,
  `user` varchar(50) CHARACTER SET utf8 NOT NULL,
  `product` varchar(255) CHARACTER SET utf8 NOT NULL,
  `code` varchar(10) CHARACTER SET utf8 NOT NULL,
  `is_rejected` int(1) NOT NULL,
  `answered` int(1) NOT NULL,
  `message` text CHARACTER SET utf8 NOT NULL,
  `date_added` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        }catch(Exception $exception){

            echo $exception->getMessage();
        }




        if($this->db->countAffected()){

            $this->load->model('setting/setting');
            $this->load->model('user/user_group');

            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'entegrasyon/product_question');
            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'entegrasyon/product_question');


            $this->db->query("ALTER TABLE   `".DB_PREFIX."es_product_question`
  ADD PRIMARY KEY (`product_question_id`);");



            $this->db->query("ALTER TABLE `".DB_PREFIX."es_product_question`
  MODIFY `product_question_id` int(11) NOT NULL AUTO_INCREMENT;");
        }else {

           // echo 'do Notting';
        }




    }




}