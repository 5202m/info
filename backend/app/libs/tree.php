<?php

class Tree
{
    public function __construct () {
        
    }
    public function _tree($arr,$parent_id=0,$level = 0){
        static $tree = array();
        foreach($arr as $v){
            if($v['parent_id'] == $parent_id){
                $v['level'] = $level;
                $tree[] = $v;
                $this->_tree($arr,$v['id'],$level+1);
            }
        }
        return $tree;
    }
}

