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
    static public function getSubIds($parent_id,&$ids){
    	empty($ids) && $ids[]=$parent_id;
    	foreach(Category::find("parent_id = '{$parent_id}'") as $item){
    		if($item){
    			$ids[]=$item->id;
    			Category::getSubIds($item->id,$ids);
    		}
    	}
    	
    }
}

