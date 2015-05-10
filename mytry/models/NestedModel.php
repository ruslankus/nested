<?php
/**
 * Created by PhpStorm.
 * User: ruslankus
 * Date: 10/05/15
 * Time: 13:11
 */

class NestedModel {

    private static $_table = 'ns_tree';

    public static function getAll(){
        $sql = "
          SELECT * FROM ".static::$_table ."
          ORDER BY lft";

        $db = new DB();
        $res = $db->queryAsArray($sql);

        return $res;
    }

}