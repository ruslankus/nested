<?php
/**
 * Created by PhpStorm.
 * User: ruslankus
 * Date: 01/05/15
 * Time: 16:19
 */

abstract class AbstractModel {

    static protected $table;

    protected $data = array();

    public function __set($key,$value){
        $this->data[$key] = $value;
    }//set


    public function __get($key){
        return $this->data[$key];
    }//get


    public function __isset($key){
        return isset($this->data[$key]);
    }

    public static function getTable(){
        return static::$table;
    }//getTable


    public static function findAll(){
        $class = get_called_class();
        $sql = "SELECT * FROM ".static::$table;

        $db = new DB();
        $db->setClassName($class);
        return $res = $db->query($sql);
    }//findAll

    public static function findByPk($id){
        $class = get_called_class();
        $sql  = "SELECT * FROM ".static::$table;
        $sql .= " WHERE id = :id";
        $params[':id'] = $id;

        $db = new DB();
        $db->setClassName($class);
        $res = $db->query($sql,$params);
        if(empty($res)){
            throw new ModelException("No data for this id");
        }
        return array_shift($res);
    }//findByPk


    public static function findAllByColumn($column,$value){

        $db = new DB();
        $db->setClassName(get_called_class());
        $sql  = " SELECT * FROM " . static::$table;
        $sql .= " WHERE `{$column}` LIKE :value ";
        $params[':value'] = "%{$value}%";
        //var_dump($sql);die;
        $res = $db->query($sql,$params);
        return !empty($res)? $res : false;
    }//findAllByColumn

    public function insert()
    {
        $cols = array_keys($this->data);

        $param = array();
        foreach($cols as $col){
            $param[":{$col}"] = $this->data[$col];
        }

        $sql = "INSERT INTO " . static::$table;
        $sql .= " (". implode(', ',$cols)  .") ";
        $sql .= "VALUES ";
        $sql .= "(". implode(', ',array_keys($param) ). ") ";

        $db = new DB();
         if($db->execute($sql,$param)){
             $this->id = $db->lastInsertId();
             return true;
         }else{
             return false;
         }

    }//insert


    public function update(){
        $allKeys = array();
        $param = array();
        foreach($this->data as $key => $value){
            $param[":{$key}"] = $value;
            if($key == 'id'){continue;}
            $allKeys[] = "{$key} = :{$key}";

        }

        $sql = "
            UPDATE ".static::$table ."
            SET ".implode(', ',$allKeys). "
            WHERE id=:id ";

        $db = new DB();
        $res = $db->execute($sql,$param);
        if($res){
            return true;
        }else{
            var_dump($sql);
            var_dump($db->showErrorInfo());
            die;
        }
    }


    public function save(){
        if(!isset($this->id)){
            $this->update();
        }else{
            $this->insert();
        }
    }//save

}//end class