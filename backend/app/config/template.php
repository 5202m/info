<?php 
return array(
		'preview'=>array(
			'category'=>'http://inf.hx9999.com/category/html/:template_id/:category_id.html',
			'list'=>'http://inf.hx9999.com/list/html/:template_id/:category_id.html',
			'detail'=>'http://inf.hx9999.com/detail/html/:template_id/:category_id/:article_id.html'
		),
		
		'purge'=>array(
				'category'=>'http://inf.hx9999.com/category/purge/:template_id/:parent_id.html',
				'list'=>'http://inf.hx9999.com/list/purge/:template_id/:parent_id.html',
				'detail'=>'http://inf.hx9999.com/detail/purge/:template_id/:parent_id.html'
		),
		
		'sample'=>array(
					/**
					 * url: ajax动态调用模板,优先级1
					 * content:编辑内容,优先级2
					 * image:显示的图片
					 */				
					array('url'=>'','name'=>'分类','content'=>'','path'=>__DIR__ . '/../../public/template/category.html','image'=>'/img/gallery/photo10.jpg'),
					array('url'=>'','name'=>'列表','content'=>'','path'=>__DIR__ . '/../../public/template/list.html','image'=>'/img/gallery/photo10.jpg'),
					array('url'=>'','name'=>'内容','content'=>'','path'=>__DIR__ . '/../../public/template/detail.html','image'=>'/img/gallery/photo10.jpg'),
					
				),
		
	);
?>