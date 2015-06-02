<?php

class Category extends \Phalcon\Mvc\Model
{
    public function initialize(){
        Category::skipAttributes(array('ctime','mtime'));
    }
    
    static public function selectCategoryId($modelsManager, $id){
    	$divisionCategoryId = array();
    	if($id){
	    	$phql = "SELECT category.id FROM category where category.parent_id={$id}";
			$categoryId = $modelsManager->executeQuery($phql);
			//echo '<pre>';
			foreach ($categoryId as $category){
				$divisionCategoryId[] = $category->id;
			}
    	}
    	return $divisionCategoryId;
    }
}

