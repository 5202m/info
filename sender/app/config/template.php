<?php 
return array(
		'preview'=>array(
			'category'=>':url/category/html/:template_id/:category_id.html',
			'list'=>':url/list/html/:template_id/:category_id.html',
			'detail'=>':url/detail/html/:template_id/:category_id/:article_id.html',
			'video'=>':url/video/play/:template_id/:category_id/:article_id.html'
		),
		
		'purge'=>array(
				'category'=>':url/category/purge/:template_id/:parent_id.html',
				'list'=>':url/list/purge/:template_id/:parent_id.html',
				'detail'=>':url/detail/purge/:template_id/:parent_id.html',
				'video'=>':url/video/purge/:template_id/:parent_id.html'
		),
		
		'sample'=>array(
					/**
					 * url: ajax动态调用模板,优先级1
					 * content:编辑内容,优先级2
					 * image:显示的图片
					 */				
					array('url'=>'','name'=>'SMS','content'=>'','path'=>__DIR__ . '/../../public/template/sms.html','image'=>'/img/icon/u14.png'),
					array('url'=>'','name'=>'Email','content'=>'','path'=>__DIR__ . '/../../public/template/email.html','image'=>'/img/icon/u12.png'),
//					array('url'=>'','name'=>'内容','content'=>'','path'=>__DIR__ . '/../../public/template/detail.html','image'=>'/img/icon/u16.png'),
					
				),
		'node'=>array('127.0.0.1','192.168.4.1'),
		
	);
?>