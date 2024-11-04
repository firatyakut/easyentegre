<?php
class ModelEntegrasyonCategoryPz extends Model {


    public function renderAttributes($result)
    {
        $attributes = array();
        $required=array();
        $pz_attributes=$result['result'];
        $variants=array();

        if($pz_attributes['data']) {
            $ix = 0;





            foreach ($pz_attributes['data']['attributes'] as $attr) {
                $attributeValues = array();
                foreach ($attr['attributeValues'] as $attributeValue) {
                    $attributeValues[] = array(
                        'id' => $attr['id'] . '|' . $attributeValue['id'],
                        'name' => $attributeValue['value']
                    );
                }
//if($attr['required']) {
                $attributes[] = array(
                    'id'=> $attr['id'],
                    'name' => $attr['name'],
                    'values' => $attributeValues,
                    'varianter' => $attr['isVariantable'],
                    'type' => '',
                    'required' => $attr['isRequired']
                );
//}
                if ($attr['required']) {
                    $required++;
                }

                if ($attr['isRequired']) {
                    $required[] = $attr['name'];
                }


                if($attr['isVariantable']) {
                    $values=array();
                    foreach ($attr['attributeValues'] as $atr_val) {


                        $values[] = array(
                            'value_id' => $atr_val['id'],
                            'name' => $atr_val['value'],
                            'order_number' => 1
                        );
                    }


                    $variants[] = array(
                        'name' => $attr['name'],

                        'id' => $attr['id'],
                        'values' => $values
                    );
                }


            }
        }

        $attributes['required_attributes']=$required;
        $attributes['variants']=$variants;


        return $attributes;

    }



    /*
        public function getCategoryOptions($category_id)
        {
            $options = array();
            $surl = 'https://api.trendyol.com/sapigw/product-categories/'.$category_id.'/attributes';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$surl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            $attributes = json_decode(curl_exec($ch), true);
            curl_close($ch);
            if(isset($attributes['categoryAttributes'])){


                foreach ($attributes['categoryAttributes'] as $attr) {
                    if($attr['varianter']) {
                        $values=array();
                    foreach ($attr['attributeValues'] as $atr_val) {


                        $values[] = array(
                            'value_id' => $atr_val['id'],
                            'name' => $atr_val['name'],
                            'order_number' => 1
                        );
                    }



                    $options[] = array(
                        'name' => $attr['attribute']['name'],

                        'id' => $attr['attribute']['id'],
                        'values' => $values
                    );
                }
            }

                return $options;
            }else {

                return array();
            }


        }
    */



}