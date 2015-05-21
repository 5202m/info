<?php 
return array(
		'preview'=>array(
			'category'=>'http://inf.hx9999.com/category/html/:template_id/:category_id.html',
			'list'=>'http://inf.hx9999.com/list/html/:template_id/:category_id.html',
			'detail'=>'http://inf.hx9999.com/detail/html/:template_id/:category_id/:article_id.html'
		),
		'template_list'=>array(
					/**
					 * url: ajax动态调用模板,优先级1
					 * content:编辑内容,优先级2
					 * image:显示的图片
					 */				
					array('url'=>'','content'=>'模板内容1','image'=>'/img/gallery/photo10.jpg'),
					array('url'=>'','content'=>'模板内容2','image'=>'/img/gallery/photo10.jpg'),
					array('url'=>'','content'=>'模板内容3','image'=>'/img/gallery/photo10.jpg'),
					array('url'=>'','content'=>'模板内容3','image'=>'/img/gallery/photo10.jpg'),
				),
		
	);
?>