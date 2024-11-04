<?php

class ModelEntegrasyonQuestionHb extends Model
{

    public function getQuestions()
    {


        $questions_data = array();
        $post_data['request_data'] = '';

        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('hb');

        $questions = $this->entegrasyon->clientConnect($post_data, 'get_questions', 'hb', false);


        if ($questions['status']) {


            if ($questions['result']['totalItemCount']) {

                foreach ($questions['result']['data'] as $question) {
                    //if ($question['status'] == 'WaitingforAnswer') {

                    $query = $this->db->query("select count(*) as total from `" . DB_PREFIX . "es_product_question` where `question_id` ='" . $question['id'] . "'  ");

                    if (!$query->row['total']) {
                        $rejected = false;
                        if (isset($question['conversations']['rejectReason'])) {
                            $rejected = true;
                        }

                        $questions_data[] = array(
                            'rejected' => $rejected,
                            'id' => $question['issueNumber'],
                            'product' => $question['product']['name'],
                            'image' => $question['product']['imageUrl'],
                            'stock_code' => $question['product']['stockCode'],
                            'new_message' => false,
                            'user' => isset($question['userName'])?$question['userName']:"Müşteri",
                            'text' => $question['lastContent'],
                            'created_date' => date('Y-m-d H:i:s', strtotime($question['createdAt']))

                        );


                    }
                }
                //    }


            }

            return array('status' => true, 'message' => '', 'result' => $questions_data);


        } else {

            return array('status' => false, 'message' => $questions['message'], 'result' => array());


        }

    }

}
