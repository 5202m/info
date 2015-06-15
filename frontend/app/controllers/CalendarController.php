<?php
use Phalcon\Mvc\View;
class CalendarController extends \Phalcon\Mvc\Controller {
    
    public function indexAction($date = ''){
        $datas = $this->generator($date);
        if($datas){
            $this->view->setVar('datas',$datas);
        }else{
            $this->response->setStatusCode(404, 'Template Not Found');
            echo 'Template Not Found';
            return;
        }
        
    }
    public function gwfxAction($date = ''){
        $this->view->disableLevel(array(
            View::LEVEL_MAIN_LAYOUT => false
        ));
        if($date != ''){
            $date = str_replace('.html', '', $date);
        }
        $datas = $this->generator($date);
        if($datas){       
            $this->view->setVar('datas',$datas);
        }else{
            $this->response->setStatusCode(404, 'Template Not Found');
            echo 'Template Not Found';
            return;
        }
    }
    public function appAction($date = ''){
        $this->view->disableLevel(array(
            View::LEVEL_MAIN_LAYOUT => false
        ));
        if($date != ''){
            $date = str_replace('.html', '', $date);
        }
        $datas = $this->app_generator($date);
        if($datas){       
            $this->view->setVar('datas',$datas);
        }else{
            $this->response->setStatusCode(404, 'Template Not Found');
            echo 'Template Not Found';
            return;
        }
    }
    private function generator($day = '') {
        if($day == ''){
            $url = "http://www.gwfx.com/zh/calendar/index.html";
        }else{
            $url = "http://www.gwfx.com/zh/calendar/".$day.".html";
        }
        
        $html = $this->curl($url);
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $xml = $xpath->query('/html/body/div[@class="greyBg"]/div[@id="wrap"]/div[@class="w960"]/div[@class="cen-conbox clearfix"]/div[@class="innerContent fr"]');
//foreach ($xml as $result_object){
        //echo $result_object->childNodes->item(0)->nodeValue;
//      print_r($result_object);
//}

        $xhtml = $dom->saveHTML($xml->item(0));
        
        $new_xhtml = str_replace('http://www.gwfx.com/zh/calendar','/calendar/gwfx',$xhtml);
        
        return($new_xhtml);
    }
    
    private function app_generator($day = '') {
        if($day == ''){
            $url = "http://m.gwfx.com/zh/calender/index.html";
        }else{
            $url = "http://m.gwfx.com/zh/calender/".$day.".html";
        }
        
        $html = $this->curl($url);
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $xml_1 = $xpath->query('/html/body/div[@class="date_1"]');
        $xml_2 = $xpath->query('/html/body/section[@class="Calendar"]');
        $xml_3 = $xpath->query('/html/body/section[@class="Thing"]');
//        print_r($xml);
//foreach ($xml as $result_object){
//        echo $result_object->childNodes->item(0)->nodeValue;
//      print_r($result_object);
//}
        $xhtml_1 = $dom->saveHTML($xml_1->item(0));
        $xhtml_2 = $dom->saveHTML($xml_2->item(0));
        $xhtml_3 = $dom->saveHTML($xml_3->item(0));
        $xhtml_4 = $dom->saveHTML($xml_3->item(1));
        $xhtml = $xhtml_1.$xhtml_2.$xhtml_3.$xhtml_4;
        $new_xhtml = str_replace('http://m.gwfx.com/zh/calender','/calendar/app',$xhtml);
        return($new_xhtml);
    }

    private function curl($url, $fields = array(), $auth = false) {
        $url_arr = parse_url($url);
        $curl = curl_init($url);
        $headers = array(
            'Accept: text/plain, */*; q=0.01',
            'Accept-Encoding: gzip, deflate',
            'Accept-Language: zh-CN,zh;q=0.8,en;q=0.6,vi;q=0.4,zh-TW;q=0.2',
            'Connection: keep-alive',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
        );
        $headers[] = 'Host: ' . $url_arr['host'];
        $headers[] = 'Origin: https://' . $url_arr['host'];
        $headers[] = 'X-Requested-With: XMLHttpRequest';

//    	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_REFERER, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//    	$jar = $this->mPayCookieFile();
//    	curl_setopt($curl, CURLOPT_COOKIEFILE, $jar);
//    	curl_setopt($curl, CURLOPT_COOKIEJAR, $jar);
//    	if($auth){
//    		curl_setopt($curl, CURLOPT_USERPWD, "$auth");
//    		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
//    	}

        if ($fields) {
            $fields_string = http_build_query($fields);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $fields_string);
        }
        $response = curl_exec($curl);
        curl_close($curl);
//    	$this->referer = $url;
        return $response;
    }

}
