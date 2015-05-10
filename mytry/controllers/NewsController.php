<?php

/**
 * Class NewsController
 * @var NewModel obj
 */
class NewsController
{
    /**
     *@var NewsModel obj
     */
    public function actionAll(){

        $res = NestedModel::getAll();
        //var_dump($res);

        //$tree = $this->createTree($res);
        //var_dump($tree);
        $ulTree = $this->MyRenderTree($res);

        echo $ulTree;
        //$view = new View();
        //$view->arrRes = $res;
        //$view->display('nested/nested_all.php');


        //$res = NewsModel::findAllByColumn('title','tes');
        //var_export($res);die;


        //$res = NewsModel::findByPk(9999);


        //var_dump($article);die;

        //echo NewsModel::getTable();

        //$items = News::get_all();
        /*
        $view = new View();
        $view->items = $res;
        $view->title = 'test title';

        $view->display('news/all.php');
        */


    }


    public function tree(array $a){
        $out = '<ul>';
        foreach($a[0] as $item){
            var_dump($item);die;
            if(1 < count($item)){
                $out .= "<li>".$this->tree($item)."</li>";
            }else{
                //var_dump($item);die;
                $out .= "<li>".$item['title']."</li>";
            }
        }
        $out .= "</ul>";

        return $out;
    }


    function createTree_old($category, $left = 0, $right = null) {
        $tree = array();
        foreach ($category as $cat => $range) {
            if ($range['lft'] == $left + 1 && (is_null($right) || $range['rgt'] < $right)) {
                $tree[$cat] = $this->createTree($category, $range['lft'], $range['rgt']);
                $left = $range['rgt'];
            }

        }
        return $tree;
    }


    public function createTree($category, $left = 0, $right = null) {
        $tree = array();
        foreach ($category as $cat => $range) {
            if ($range['lft'] == $left + 1 && (is_null($right) || $range['rgt'] < $right)) {
                $tree[$cat]= array();
                $tree[$cat]['title']=$range['name'];
                $tree[$cat]['id'] = $range['id'];
                if($range['rgt']-$range['lft']>1){
                    $tree[$cat]['sub'] = $this->createTree($category, $range['lft'], $range['rgt']);
                }
                $left = $range['rgt'];
            }
        }
        return $tree;
    }


    public function createList($category, $left = 0, $right = null, $list = ""){
        $tree = array();
        $out = $list . "<ul>";

        foreach($category as $cat => $range){
            if($range['lft'] == $left + 1 && (is_null($right)) || $range['rgt'] < $right){
                $out .= '<li>';
                $out .= $range['name'];
                if($range['rgt'] - $range['lft'] > 1){
                    $out .= $this->createList($category,$range['lft'],$range['rgt']);
                }
                $out .= "</li>";
            }
        }
        return $out . "</ul>";
    }



    function MyRenderTree ( $tree = array(array('name'=>'','level'=>'', 'lft'=>'','rgt'=>'')) , $current=false){

        $current_depth = 0;
        $counter = 0;

        $link = "/index.php?ctr=News&act=All";

        $result = '<ul>';

        foreach($tree as $node){
            $node_depth = $node['level'];
            $node_name = $node['name'];
            $node_id = $node['id'];

            if($node_depth == $current_depth){
                if($counter > 0) $result .= '</li>';
            }
            elseif($node_depth > $current_depth){
                $result .= '<ul>';
                $current_depth = $current_depth + ($node_depth - $current_depth);
            }
            elseif($node_depth < $current_depth){
                $result .= str_repeat('</li></ul>',$current_depth - $node_depth).'</li>';
                $current_depth = $current_depth - ($current_depth - $node_depth);
            }
            $link .= "&id=".$node_id;
            $result .= '<li id="c'.$node_id.'"';
            $result .= $node_depth < 2 ?' class="open"':'';
            $result .= "><a href='{$link}'>".$node_name.'</a>';
            $link =  "/index.php?ctr=News&act=All";
            ++$counter;
        }
        $result .= str_repeat('</li></ul>',$node_depth).'</li>';

        $result .= '</ul>';

        return $result;
    }


    function flattenTree($tree, $parent_tree = array()) {
        $out = array();
        foreach ($tree as $key => $children) {
            $new_tree = $parent_tree;
            $new_tree[] = $key;
            if (count($children)) {
                $child_trees = $this->flattenTree($children, $new_tree);
                foreach ($child_trees as $tree) {
                    $out[] = $tree;
                }
            } else {
                $out[] = $new_tree;
            }
        }
        return $out;
    }


    /**
     *
     */
    public function actionOne(){
        $id = $_GET['id'];
        $item = News::get_one($id);

        $view = new View();
        $view->item = $item;
        $view->display('news/one.php');

    }
}