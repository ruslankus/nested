<?php

error_reporting(-1);
header('Content-type: text/html; charset=utf-8');
/*cd C:\Users\Inter\Desktop
php php-cs-fixer.phar fix C:\apache\localhost\www\ns\vendor\Interlab\NestedSets\Manager.php*/

function h($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8', false);
}

function p($str)
{
    echo '<pre>', print_r($str, true), '</pre>';
}

function i($key, array $search)
{
    return array_key_exists($key, $search) ? $search[$key] : null;
}

set_exception_handler(function ($exception) {
    p($exception);
});

/*require_once '/../SplClassLoader.php';
$classLoader = new SplClassLoader('Interlab\NestedSets', '/../vendor/');
$classLoader->register();*/

require_once '/../lib/Interlab/NestedSets/Manager.php';

# test: child class
class Nesty extends Interlab\NestedSets\Manager
{
    # private $db = null;
}

$ns_class = new Nesty();

# DB config
$config = require_once 'config.php';
# $config = $config['mysql'];
$config = $config['sqlite'];
# $config = $config['postgresql'];

$ns_class->db_table = $config['db_prefix'] . $config['db_table'];
$ns_class->id_column = $config['id_column'];
$ns_class->left_column = $config['left_column'];
$ns_class->right_column = $config['right_column'];

# DB connect
$ns_class->setDb(
    $config['db_type'], $config['db_host'], i('db_port', $config),
    i('db_name', $config), i('db_user', $config), i('db_password', $config)
);

# Get Site Url
$site_url = strpos($_SERVER['REQUEST_URI'], '?');
$site_url = $site_url ? substr($_SERVER['REQUEST_URI'], 0, $site_url) : $_SERVER['REQUEST_URI']; 
$site_url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['SERVER_NAME'] . $site_url;

/* try {
    $db = new \PDO('mysql:host=localhost;dbname=smf', 'smf', 'smf2',
        array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8')
    );
    $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    throw new \Exception('Houston, we have a problem. ' . $e);
}
$ns_class->setDbAdapter($db); */

$ary = array(
    'createRoot',
    'deleteNode',
    'insertAsPrevSiblingOf',
    'insertAsNextSiblingOf',
    'insertAsFirstChildOf',
    'insertAsLastChildOf',
    'addChild',
    'moveToPrevSiblingOf',
    'moveToNextSiblingOf',
    'moveToFirstChildOf',
    'moveToLastChildOf',
);

if (isset($_REQUEST['def']) and in_array($_REQUEST['def'], $ary)) {
    switch ($_REQUEST['def']) {

        case 'createRoot':
            if (!empty($_REQUEST['name'])) {
                if (call_user_func ([$ns_class, $_REQUEST['def']], ['name' => $_REQUEST['name']]))
                    $flashdata = [$_REQUEST['def'], 'success', 'Категория успешно создана!'];
            }  else {
                $flashdata = [$_REQUEST['def'], 'error', 'Отсутствует название категории!'];
            }
            break;

        case 'deleteNode':
            if (call_user_func ([$ns_class, $_REQUEST['def']], $_REQUEST['cat'])) {
                $flashdata = [$_REQUEST['def'], 'warning', 'Категория успешно удалена!'];
            } else {
                $flashdata = [$_REQUEST['def'], 'error', 'Возникла ошибка: категория не была удалена, либо не существует.'];
            }
            break;

        case 'insertAsPrevSiblingOf':
        case 'insertAsNextSiblingOf':
            if (call_user_func_array ([$ns_class, $_REQUEST['def']], [$_REQUEST['cat'], ['name' => $_REQUEST['name']]])) {
                //var_dump($ns_class->$_REQUEST['def']($_REQUEST['cat'], ['name' => $_REQUEST['name']]));
                $flashdata = [$_REQUEST['def'], 'success', 'Категория успешно создана!'];
            } else {
                $flashdata = [$_REQUEST['def'], 'error', 'Ошибка! Unknown Error Type.'];
            }
            break;
            
        case 'addChild':
        case 'insertAsFirstChildOf':
        case 'insertAsLastChildOf':
            if (call_user_func_array ([$ns_class, $_REQUEST['def']], [$_REQUEST['cat'], ['name' => $_REQUEST['name']]])) {
                $flashdata = [$_REQUEST['def'], 'success', 'Подкатегория успешно создана!'];
            } else {
                $flashdata = [$_REQUEST['def'], 'error', 'Ошибка! Unknown Error Type.'];
            }
            break;

        case 'moveToPrevSiblingOf':
        case 'moveToNextSiblingOf':
        case 'moveToFirstChildOf':
        case 'moveToLastChildOf':
            if (isset($_GET['node'], $_GET['parent']) and $ns_class->issetNode($_GET['node']) and $ns_class->issetNode($_GET['parent'])) {
                if (call_user_func_array ([$ns_class, $_REQUEST['def']], [$_REQUEST['node'], $_REQUEST['parent']])) {
                    $flashdata = [$_REQUEST['def'], 'success', 'Узел успешно перемещён!'];
                } else {
                    $flashdata = [$_REQUEST['def'], 'error', 'Ошибка! Unknown Error Type. #' . __LINE__];
                }
            }
            break;

        default:
            $flashdata = [$_REQUEST['def'], 'error', 'Ошибка! Unknown Error Type.'];
            break;
    }
}

$tree = $ns_class->getTree();

include_once __DIR__ . '/template/header.tpl';

include_once __DIR__ . '/template/content.tpl';

include_once __DIR__ . '/template/footer.tpl';
