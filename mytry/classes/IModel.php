<?php
/**
 * Created by PhpStorm.
 * User: ruslankus
 * Date: 03/05/15
 * Time: 15:13
 */

interface IModel {

    public static function get_all();

    public static function get_one($id);
}