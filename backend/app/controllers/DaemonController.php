<?php 
class DaemonController extends ControllerBase
{
	public function initialize(){
		parent::initialize();
		$this->division_id = $this->Division_id;
		$this->view->division_id = $this->division_id;
		
	}
	public function indexAction(){	
		
	}
	public function mapAction($type  , $page = 1 , $eNum = 5){
		if($type == 'clear'){
			$this->session->remove('maplist');
			$this->session->remove('mappage');
			return ;
		}
		
		$map = array();
		$map['209'] = array( #inf中的分类,公司活动,'division_category_id'=>208,
						'title'=>null,
						'content'=>null,
						'division_id'=>1,//$this->division_id,//当前的部门
						'language'=>'cn',
						'source'=>'HXPM',
						'visibility'=>'Visible',
						'status'=>'Enabled',
						'share'=>'N',
						'author'=>'computer',
						'picker' =>array(
										'entranceUrl'=>'http://www.hx9999.com/cn/intro_227.html',
										'page'=>null,
										'orderby'=>'DESC',
										'list'=>'/<a href="(?P<pic_url>\S+?)"><img src="(?P<pic>\S+?)">\s*<\/a>\s+<h2><a href="(?P<url>\S+?)">/is',
										'content'=>'/<h1>(?P<title>.+?)<\/h1>\s+?<div class="content">(?P<content>.+?)\<div class="clear">/is',
									)
			
		);
		
		$map['210'] = array( #inf中的分类,公司活动,'division_category_id'=>208,(手机版)
				'title'=>null,
				'content'=>null,
				'division_id'=>1,//$this->division_id,//当前的部门
				'language'=>'cn',
				'source'=>'HXPM',
				'visibility'=>'Visible',
				'status'=>'Enabled',
				'share'=>'N',
				'author'=>'computer',
				'picker' =>array(
						'entranceUrl'=>'http://m.hx9999.com/about/activity',
						'page'=>null,
						'orderby'=>'DESC',
						'list'=>'/<div class="newsItem">\s+<a href="(?P<url>\S+?)">\s*<div class="thumb">\s*<img src="(?P<pic>\S+?)".+?>\s*<\/div>.+?<div>(?P<description>\S+?)\.\.\./is',
						'content'=>'/<h1 class="news_title">(?P<title>.+?)<\/h1>\s+?<div class="news_date">(?P<ctime>(\d|\:|\s|\-)+?)<\/div>.+?<div class="content">(?P<content>.+?<div class="clear"><\/div>)/is',
				)
					
		);
		
		
		
		if(isset($map[$type]['picker'])){
			
			$picker = $map[$type]['picker'];
			
			unset($map[$type]['picker']);
			$map[$type]['division_category_id'] = $type;
			
			$pickerBase = $picker['entranceUrl'] ; //总入口文件
			
			$base_url_arr = parse_url($pickerBase);
			$base_url_arr['dir'] = (isset($base_url_arr['path']) && $base_url_arr['path']!='/') ? dirname($base_url_arr['path']).'/' : '';
			
			/** 使用缓存 **/
			if($this->session->get('maplist')){
				$r = $this->session->get('maplist');
			}
			
			if(!isset($r['url']) || empty($r['url'])){
				$baseContent = $this->curl($pickerBase);
			}
				
			if(isset($picker['list']) && $picker['list']){
				if(!isset($r)){
					preg_match_all($picker['list'], $baseContent,$r);
				}
				
				if(isset($r['url']) && $r['url']){
					
					$this->session->set('maplist', $r);
					
					/** 列表数据 **/
					$bNum = ($page-1)*$eNum; //[0->10),[10,20)
			
					/** 执行完毕 **/
					if($bNum+$eNum>count($r['url'])){
						echo 'doComplete';
						exit();
					}
					if($picker['orderby'] == 'DESC'){
						krsort($r['url']);
					}
					$r['url'] = array_splice($r['url'], $bNum,$eNum);
					print_R($r['url']);
					/** 页面缓存 **/
					$doPages = $this->session->get('mappage') ? $this->session->get('mappage') : array();
					
					foreach($r['url'] as $k=>$v){
						$contengUrl = stripos($v,'http') ===0 ? $v : ($base_url_arr['scheme'].'://'
								.(substr($v,0,1)=='/' 
									? ($base_url_arr['host']) 
										: ($base_url_arr['host'].$base_url_arr['dir'])) . $v);
						
						if(in_array($contengUrl, $doPages)){
							continue;
						}
						$doPages[] = $contengUrl;
						$this->session->set('mappage', $doPages);
						
						
						$iExecTime=ini_get("max_execution_time");
						ini_set("max_execution_time",300);
						$content  = $this->curl($contengUrl);
						ini_set("max_execution_time",$iExecTime);
						
						preg_match($picker['content'], $content,$ri);
						
						if(isset($ri['content'])){
							
							/** 文章内容 **/
							$map[$type][$contengUrl] = $map[$type];
							$art = new Article;
							
							if(is_array($map[$type])){
								foreach($map[$type][$contengUrl] as $ki=>$vi){
									$art->{$ki} = $vi;
								}
								$art->title = $ri['title'];
								$art->content = $ri['content'];
								
								/** 时间 **/
								if(isset($ri['ctime']) && $ri['ctime']){
									$art->ctime = $ri['ctime'];
								}
								/** 描述 **/
								if(isset($r['description'][$k]) && $r['description'][$k]){
									echo $art->description = $r['description'][$k];
								}
							
							}
							if($art->save()){
								
								if(isset($r['pic'][$k])){
									
									$img = new Images;
									$img->article_id = $art->id;
									$img->url = $r['pic'][$k];
									
									if($img->save()){
										echo $img->id;
										echo '<br />';
									}else{
										print_r($img->getMessages());
									}
								}
							}else{
								print_r($art->getMessages());
							}
						}
					}	
					header('Location: /daemon/map/'.$type.'/'.($page+1).'/'.$eNum);
					exit();
				}
			}
		}
		return $map;
		
	}
	
	public function discountAction($type , $page = 1 , $eNum = 5){
		$map = array();
		$map['140'] = array( #inf中的分类,公司活动,'division_category_id'=>208,
				'title'=>null,
				'content'=>null,
				'division_id'=>1,//$this->division_id,//当前的部门
				'language'=>'cn',
				'source'=>'HXPM',
				'visibility'=>'Visible',
				'status'=>'Enabled',
				'share'=>'N',
				'picker' =>array(
						'entranceUrl'=>'http://www.hx9999.com/cn/intro_act.html',
						'page'=>null,
						'orderby'=>'DESC',
						//'list'=>'/<a href="(?P<pic_url>\S+?)" target="_blank"><img src="(?P<pic>\S+?)" alt=""></a>/is',//'list'=>'/<a href="(?P<pic_url>\S+?)"><img src="(?P<pic>\S+?)"><\/a>\s+<h2><a href="(?P<url>\S+?)">/is',
						'title'=>'/<div class="act_desc">\s+<p>(?P<title>.+?)<\/p>/is',
						'contentall'=>'/<div class="content introact_list">\s+<ul>(?P<content>.+?)\<\/ul>/is',
						'content'=>'/<li>(?P<content>.+?)\s+<div class="clear">/is',
				)
					
		);
		$map['141'] = array( #inf中的分类,公司活动,'division_category_id'=>208,
				'title'=>null,
				'content'=>null,
				'division_id'=>1,//$this->division_id,//当前的部门
				'language'=>'cn',
				'source'=>'HXPM',
				'visibility'=>'Visible',
				'status'=>'Enabled',
				'share'=>'N',
				'picker' =>array(
						'entranceUrl'=>'http://m.hx9999.com/about/companyact',
						'page'=>null,
						'orderby'=>'DESC',
						//'list'=>'/<a href="(?P<pic_url>\S+?)" target="_blank"><img src="(?P<pic>\S+?)" alt=""></a>/is',//'list'=>'/<a href="(?P<pic_url>\S+?)"><img src="(?P<pic>\S+?)"><\/a>\s+<h2><a href="(?P<url>\S+?)">/is',
						'title'=>'/<div class="act_desc">\s+<p>(?P<title>.+?)<\/p>/is',
						'content'=>'/<li>(?P<content>.+?)\s+<div class="clear">/is',
				)
					
		);
		if(isset($map[$type]['picker'])){
				
			$picker = $map[$type]['picker'];
				
			unset($map[$type]['picker']);
			$map[$type]['division_category_id'] = $type;
				
			$pickerBase = $picker['entranceUrl'] ; //总入口文件
				
			$base_url_arr = parse_url($pickerBase);
			$base_url_arr['dir'] = (isset($base_url_arr['path']) && $base_url_arr['path']!='/') ? dirname($base_url_arr['path']).'/' : '';
				
			$baseContent = $this->curl($pickerBase);
				
			preg_match_all($picker['title'], $baseContent, $ri);
			$contentAll = array();
			$content = array();
			if(isset($picker['contentall'])){
				preg_match($picker['contentall'], $baseContent, $contentAll);
				$baseContent = $contentAll['content'];
				preg_match_all($picker['content'], $baseContent, $content);
			}
			else{
				preg_match_all($picker['content'], $baseContent, $content);
			}
			if(isset($ri['title']) && isset($content['content'])){
	
				/** 文章内容 **/
				for($i = (sizeof($ri['title']) - 1), $size = 0; $i >= $size; $i--){
					$art = new Article;
					foreach ($map[$type] as $key => $val){
						$art->{$key} = $val;
					}
					$art->title = $ri['title'][$i];
					$art->content = $content['content'][$i];
					$art->division_category_id = $type;
					if($art->save()){
	
					}else{
						print_r($art->getMessages());
					}
				}
	
			}
		}
		return $map;
	
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
		curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_VERBOSE, 0);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_REFERER, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		if ($fields) {
			$fields_string = http_build_query($fields);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $fields_string);
		}
		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}
}