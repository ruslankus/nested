<?php
/**
 * Created by PhpStorm.
 * User: ruslankus
 * Date: 27/04/15
 * Time: 23:43
 */

class DB {

    private $dbh;
    private $className = 'stdClass';

    /**
     *
     */
    public function __construct(){
        $dsn = 'mysql:host=localhost;dbname=nested';
        $this->dbh = new PDO($dsn,'sigma','12345');
    }


    public function query($sql,$param=array()){

        $sth = $this->dbh->prepare($sql);
        $sth->execute($param);
        return $sth->fetchAll(PDO::FETCH_CLASS,$this->className);
    }//query

    public function queryAsArray($sql,$param=array()){

        $sth = $this->dbh->prepare($sql);
        $sth->execute($param);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }//query


    public function execute($sql,$param=array()){

        $sth = $this->dbh->prepare($sql);
        return $sth->execute($param);
    }//execute


    public function lastInsertId(){
        return $this->dbh->lastInsertId();
    }


    public function showErrorInfo(){
        return $this->dbh->errorInfo();
    }//showErrorInfo




    public function setClassName($className){
        $this->className = $className;
    }

}//DB