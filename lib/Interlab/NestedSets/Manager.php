<?php

/**
 *
 * @author 06.01.2013 - 01.06.2013 by Inter <pmrtorrents@gmail.com>
 * Support this class please visit: http://simaru.org/
 *
 * Thanks:
 *      http://www.getinfo.ru/article610.html
 *      http://doc.prototypes.ru/database/trees/nestedsets/theory/edit/
 *      http://propelorm.org/behaviors/nested-set.html
 *      http://www.nikolaposa.in.rs/webfolio/php-klase/nested-set-db-table
 *      https://github.com/blt04/doctrine2-nestedset
 *
 * rgt < last rgt => increment the hierarchy level (move one level down)
 * lft - last lft > 2 => decrement the hierarchy level (move one level up)
 * (rgt - lft - 1) / 2 = number of children nodes
 *
 * Rules:
 *      1. Левый ключ ВСЕГДА меньше правого
 *      2. Наименьший левый ключ ВСЕГДА равен 1
 *      3. Наибольший правый ключ ВСЕГДА равен двойному числу узлов
 *      4. Разница между правым и левым ключом ВСЕГДА нечетное число
 *      5. Если уровень узла нечетное число то тогда левый ключ ВСЕГДА нечетное число, то же самое и для четных чисел
 *      6. Ключи ВСЕГДА уникальны, вне зависимости от того правый он или левый
 *
 */

namespace Interlab\NestedSets;

use PDO;
use Exception;

class Manager
{
    protected $db_table = 'categories';
    protected $id_column = 'id';
    protected $left_column = 'lft';
    protected $right_column = 'rgt';
    protected $level = 'depth';

    private $db;

    # public function __construct(){}

    /**
     * DB connect
     * @throws Exception
     */
    public function setDb(
        $db_type = 'mysql',
        $db_host = 'localhost',
        $db_port = 3306,
        $db_name = 'categories',
        $db_user = 'root',
        $db_password = '',
        $charset = 'UTF8'
    ) {
        try {
            if ('mysql' === $db_type)
                $this->db = new PDO($db_type . ':host=' . $db_host . ';dbname=' . $db_name, $db_user, $db_password,
                    array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $charset)
                );
            elseif ('sqlite' === $db_type)
                $this->db = new PDO($db_type . ':' . $db_host);
            elseif ('pgsql' === $db_type)
                $this->db = new PDO($db_type . ':host=' . $db_host . ';port=' . $db_port . ';dbname=' . $db_name, $db_user, $db_password);
            else
                throw new Exception('Your db type not support in setDb() method. Use setDbAdapter() method.');
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            # $this->db->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
        } catch (PDOException $e) {
            die('Houston, we have a problem.');
        }
    }

    public function setDbAdapter(PDO $dbAdapter)
    {
        $this->db = $dbAdapter;
    }

    /**
     * @return PDO object
     */
    public function getDbAdapter()
    {
        return $this->db;
    }

    /**
     * Проверка: узел в дереве?
     * @return boolean
     * @throws Exception
     */
    public function inTree($node, $parent)
    {
        if (!is_array($node))
            $node = $this->getNode($node);
        elseif(!is_array($parent))
            $parent = $this->getNode($parent);

        if (empty($node) or empty($parent))
            throw new Exception('Node Not Found!');

        return $node['left_key'] > $parent['left_key'] && $node['right_key'] < $parent['right_key'];
    }

    /**
     * Проверка: узел $node родитель по отношению к узлу $node2?
     * @return boolean
     * @throws Exception
     */
    public function isParent($node, $node2)
    {
        if (!is_array($node))
            $node = $this->getNode($node);
        elseif(!is_array($node2))
            $node2 = $this->getNode($node2);

        if (empty($node) or empty($node2))
            throw new Exception('Node Not Found!');

        return $node['left_key'] < $node2['left_key'] && $node['right_key'] > $node2['right_key'];
    }

    /**
     * @return boolean
     * @throws Exception
     */
    public function issetNode($id_node)
    {
        if (!is_numeric($id_node))
            throw new Exception('ID NODE Only INTEGER TYPE!');

        $id_node = (int) $id_node;
        $node = $this->getTree('node.' . $this->id_column . ' = ' . $id_node);

        return isset($node[$id_node]);
    }

    /**
     * Возвращает узел по ID
     * @return array
     * @throws Exception
     */
    public function getNode($id_node)
    {
        if (!is_numeric($id_node))
            throw new Exception('ID NODE Only INTEGER TYPE!');

        $id_node = (int) $id_node;
        $node = $this->getTree('node.' . $this->id_column . ' = ' . $id_node);

        if (isset($node[$id_node]))
            return $node[$id_node];
        else
            return array();
    }

    /**
     * Возвращает ВСЕ узлы
     * @return array
     * @throws Exception
     */
    public function getTree($where = '', $having = '')
    {
        $sql = '
            SELECT node.*, (COUNT(parent.' . $this->id_column . ') - 1) AS ' . $this->level . '
            FROM ' . $this->db_table . ' AS node, ' . $this->db_table . ' AS parent
            WHERE node.' . $this->left_column . ' BETWEEN parent.' . $this->left_column . ' AND parent.' . $this->right_column . (empty($where) ? '' : '
                AND ' . $where) . '
            GROUP BY node.' . $this->id_column . (empty($having) ? '' : '
            HAVING ' . $having) . '
            ORDER BY node.' . $this->left_column;

        try {
            $q = $this->db->query($sql);
            $tree = array();
            while ($row = $q->fetch(PDO::FETCH_ASSOC))
                $tree[$row[$this->id_column]] = $row;
            $q->closeCursor();
        } catch (Exception $e) {
            throw $e;
        }

        if (empty($tree) or !is_array($tree))
            $tree = array();

        return $tree;
    }

    /**
     * Возвращает ВСЮ ветку по ID указанного узла
     * @return array
     * @throws Exception
     */
    public function getBranch($node)
    {
        if (!is_array($node))
            $node = $this->getNode($node);

        if (empty($node))
            throw new Exception('Node Not Found!');

        $where = 'node.' . $this->left_column . ' < ' . $node[$this->right_column] . '
                  AND node.' . $this->right_column . ' > ' . $node[$this->left_column];

        return $this->getTree($where);
    }

    /**
     * Возвращает родителя
     * @return array
     * @throws Exception
     */
    public function getParent($node)
    {
        if (!is_array($node))
            $node = $this->getNode($node);

        if (empty($node))
            throw new Exception('Node Not Found!');

        if (0 == $node[$this->level])
            return array();

        $where = 'node.' . $this->left_column . ' < ' . $node[$this->left_column] . '
                  AND node.' . $this->right_column . ' > ' . $node[$this->right_column] . '
                  AND parent.' . $this->level . ' = ' . ($node[$this->level] - 1);

        return $this->getTree($where);
    }

    /**
     * Возвращает предыдущий узел по отношению к $node
     * @return array
     */
    public function getPrevSibling($node)
    {
        if (!is_array($node))
            $node = $this->getNode($node);

        $where = 'node.' . $this->right_column . ' = ' . $node[$this->left_column] . ' - 1';

        return $this->getTree($where);
    }

    /**
     * Возвращает детей БЕЗ родителя
     * @return array
     * @throws Exception
     */
    public function getChildren($node)
    {
        if (!is_array($node))
            $node = $this->getNode($node);

        if (empty($node))
            throw new Exception('Node Not Found!');

        $where = 'node.' . $this->left_column . ' > ' . $node[$this->left_column] . '
                  AND node.' . $this->right_column . ' < ' . $node[$this->right_column];

        return $this->getTree($where);
    }

    /**
     * Возвращает количество всех дочерних узлов
     * @return integer
     * @throws Exception
     */
    public function getCountChildren($node)
    {
        if (!is_array($node))
            $node = $this->getNode($node);

        if (empty($node))
            throw new Exception('Node Not Found!');

        return ($node[$this->right_column] - $node[$this->left_column] - 1) / 2;
    }

    /**
     * Создать первый узел или корневой за последним корневым узлом
     * @return boolean
     * @throws Exception
     */
    public function createRoot(array $params)
    {
        $tree = $this->getTree();
        if (!empty($tree)) {
            # Get Last Parent Info
            $last_parent = $this->getLastParent();
            $this->insertAsNextSiblingOf($last_parent, $params);

            return true;
        }

        try {
            $this->db->beginTransaction();

            $params = $this->_safeParams($params);

            $params[$this->left_column] = 1;
            $params[$this->right_column] = 2;

            $sql = '
                INSERT INTO ' . $this->db_table . ' (' . implode(', ', array_keys($params)) . ')
                VALUES (' . implode(', ', array_fill(0, count($params), '?')) . ')';

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getLastParent()
    {
        $sql = '
            SELECT node.' . $this->id_column . ', (COUNT(parent.' . $this->id_column . ') - 1) AS ' . $this->level . '
            FROM ' . $this->db_table . ' AS node, ' . $this->db_table . ' AS parent
            WHERE node.' . $this->left_column . ' BETWEEN parent.' . $this->left_column . ' AND parent.' . $this->right_column . '
            GROUP BY node.' . $this->id_column . '
            HAVING ' . $this->level . ' = 0
            ORDER BY node.' . $this->left_column . ' DESC
            LIMIT 1';

        try {
            $q = $this->db->query($sql);
            $row = $q->fetch(PDO::FETCH_ASSOC);
            $q->closeCursor();
        } catch (Exception $e) {
            throw $e;
        }

        return $row[$this->id_column];
    }

    /**
     * Добавляем категорию ПЕРЕД выбранным узлом
     * @return bool
     * @throws Exception
     */
    public function insertAsPrevSiblingOf($id_node, array $params)
    {
        $node = $this->getNode($id_node);

        if (empty($node))
            throw new Exception('Node Not Found!');

        $params = $this->_safeParams($params);

        try {
            $this->db->beginTransaction();

            $sql = '
                UPDATE ' . $this->db_table . '
                    SET ' . $this->left_column . ' =
                            CASE WHEN ' . $this->left_column . ' >= ' . $node[$this->left_column] . '
                                THEN ' . $this->left_column . ' + 2
                                ELSE ' . $this->left_column . '
                            END,
                        ' . $this->right_column . ' = ' . $this->right_column . ' + 2
                WHERE ' . $this->left_column . ' >= ' . $node[$this->left_column] . '
                    OR ' . $this->right_column . ' >= ' . $node[$this->right_column];

            $q = $this->db->prepare($sql);
            $q->execute();

            $params[$this->left_column] = $node[$this->left_column];
            $params[$this->right_column] = $node[$this->left_column] + 1;

            $sql = '
                INSERT INTO ' . $this->db_table . ' (' . implode(', ', array_keys($params)) . ')
                VALUES (' . implode(', ', array_fill(0, count($params), '?')) . ')';

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Добавляем категорию ПОСЛЕ выбранного узла
     * @return boolean
     * @throws Exception
     */
    public function insertAsNextSiblingOf($id_node, array $params)
    {
        $node = $this->getNode($id_node);

        if (empty($node))
            throw new Exception('Node Not Found!');

        $params = $this->_safeParams($params);

        try {
            $this->db->beginTransaction();

            $sql = '
                UPDATE ' . $this->db_table . '
                    SET ' . $this->left_column . ' =
                            CASE WHEN ' . $this->left_column . ' > ' . $node[$this->right_column] . '
                                THEN ' . $this->left_column . ' + 2
                                ELSE ' . $this->left_column . '
                            END,
                        ' . $this->right_column . ' = ' . $this->right_column . ' + 2
                WHERE ' . $this->right_column . ' > ' . $node[$this->right_column];

            $q = $this->db->prepare($sql);
            $q->execute();

            $params[$this->left_column] = $node[$this->right_column] + 1;
            $params[$this->right_column] = $node[$this->right_column] + 2;

            $sql = '
                INSERT INTO ' . $this->db_table . ' (' . implode(', ', array_keys($params)) . ')
                VALUES (' . implode(', ', array_fill(0, count($params), '?')) . ')';

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Добавляем дочернюю категорию в конец
     * @return type
     */
    public function addChild($id_node, array $params)
    {
        return $this->insertAsLastChildOf($id_node, $params);
    }

    /**
     * Добавляем дочернюю категорию в начало
     * @return boolean
     * @throws Exception
     */
    public function insertAsFirstChildOf($id_node, array $params)
    {
        $node = $this->getNode($id_node);

        if (empty($node))
            throw new Exception('Node Not Found!');

        try {
            $this->db->beginTransaction();

            $sql = '
                UPDATE ' . $this->db_table . '
                    SET ' . $this->left_column . ' =
                            CASE WHEN ' . $this->left_column . ' > ' . $node[$this->left_column] . '
                                THEN ' . $this->left_column . ' + 2
                                ELSE ' . $this->left_column . '
                            END,
                        ' . $this->right_column . ' = ' . $this->right_column . ' + 2
                WHERE ' . $this->left_column . ' > ' . $node[$this->left_column] . '
                    OR ' . $this->right_column . ' >= ' . $node[$this->right_column];

            $q = $this->db->prepare($sql);
            $q->execute();

            $params[$this->left_column] = $node[$this->left_column] + 1;
            $params[$this->right_column] = $node[$this->left_column] + 2;

            $sql = '
                INSERT INTO ' . $this->db_table . ' (' . implode(', ', array_keys($params)) . ')
                VALUES (' . implode(', ', array_fill(0, count($params), '?')) . ')';

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Добавляем дочернюю категорию в конец
     * @return boolean
     * @throws Exception
     */
    public function insertAsLastChildOf($id_node, array $params)
    {
        $node = $this->getNode($id_node);

        if (empty($node))
            throw new Exception('Node Not Found!');

        try {
            $this->db->beginTransaction();

            $sql = '
                UPDATE ' . $this->db_table . '
                    SET ' . $this->left_column . ' =
                            CASE WHEN ' . $this->left_column . ' > ' . $node[$this->right_column] . '
                                THEN ' . $this->left_column . ' + 2
                                ELSE ' . $this->left_column . '
                            END,
                        ' . $this->right_column . ' = ' . $this->right_column . ' + 2
                WHERE ' . $this->right_column . ' >= ' . $node[$this->right_column];

            $q = $this->db->prepare($sql);
            $q->execute();

            $params[$this->left_column] = $node[$this->right_column];
            $params[$this->right_column] = $node[$this->right_column] + 1;

            $sql = '
                INSERT INTO ' . $this->db_table . ' (' . implode(', ', array_keys($params)) . ')
                VALUES (' . implode(', ', array_fill(0, count($params), '?')) . ')';

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Перемещает узел $id_node ПЕРЕД выбранным узлом $id_parent
     * @return boolean
     * @throws Exception
     */
    public function moveToPrevSiblingOf($id_node, $id_parent, array $params = array())
    {
        $id_node = (int) $id_node;
        $id_parent = (int) $id_parent;

        if ($id_node == $id_parent)
            return false;

        $node = $this->getNode($id_node);
        $parent = $this->getNode($id_parent);

        if (empty($node) or empty($parent))
            throw new Exception('Node Not Found!');

        if ($this->isParent($node, $parent)) {
            //throw new Exception('Node Is Parent!');
            $this->moveToNextSiblingOf($id_parent, $id_node);
            $this->updateNode($node, $params);

            return true;
        }

        if ($parent['left_key'] - 1 == $node['right_key']) {
            # throw new Exception('Node on Position!');
            $this->updateNode($node, $params);

            return true;
        }

        $params = $this->_safeParams($params);
        $count_nodes = $this->getCountChildren($node) + 1;

        try {
            $this->db->beginTransaction();

            # Расширить новое место
            $sql = '
                UPDATE ' . $this->db_table . '
                    SET ' . $this->left_column . ' =
                            CASE WHEN ' . $this->left_column . ' >= ' . $parent[$this->left_column] . '
                                THEN ' . $this->left_column . ' + (2 * ' . $count_nodes . ')
                                ELSE ' . $this->left_column . '
                            END,
                        ' . $this->right_column . ' = ' . $this->right_column . ' + (2 * ' . $count_nodes . ')
                WHERE ' . $this->left_column . ' >= ' . $parent[$this->left_column] . '
                    OR ' . $this->right_column . ' >= ' . $parent[$this->right_column];

            $q = $this->db->prepare($sql);
            $q->execute();

            # В БД ещё нет изменений
            if ($this->inTree($node, $parent) || $parent[$this->right_column] < $node[$this->left_column]) {
                $node[$this->left_column] = $node[$this->left_column] + (2 * $count_nodes);
                $node[$this->right_column] = $node[$this->right_column] + (2 * $count_nodes);
            }

            $difference = $parent[$this->left_column] - $node[$this->left_column];

            # Перенести на новое место
            $sql = 'UPDATE ' . $this->db_table . '
                    SET ' . ($params ? implode(' = ?, ', array_keys($params)) . ' = ?,' : '') . '
                        ' . $this->left_column . ' = ' . $this->left_column . ' + ' . $difference . ',
                        ' . $this->right_column . ' = ' . $this->right_column . ' + ' . $difference . '
                    WHERE ' . $this->left_column . ' >= ' . $node[$this->left_column] . '
                        AND ' . $this->right_column . ' <= ' . $node[$this->right_column];

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->_clean($node);

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Перемещает узел $id_node ПОСЛЕ выбранного узла $id_parent
     * @return boolean
     * @throws Exception
     */
    public function moveToNextSiblingOf($id_node, $id_parent, array $params = array())
    {
        $id_node = (int) $id_node;
        $id_parent = (int) $id_parent;

        if ($id_node == $id_parent)
            return false;

        $node = $this->getNode($id_node);
        $parent = $this->getNode($id_parent);

        if (empty($node) or empty($parent))
            throw new Exception('Node Not Found!');

        if ($this->isParent($node, $parent)) {
            //throw new Exception('Node Is Parent!');
            $this->moveToPrevSiblingOf($id_parent, $id_node);
            $this->updateNode($node, $params);

            return true;
        }

        # Уже на позиции, обновляем только параметры без ключей
        if ($parent['right_key'] + 1 == $node['left_key']) {
            # throw new Exception('Node on Position!');
            $this->updateNode($node, $params);

            return true;
        }

        $params = $this->_safeParams($params);
        $count_nodes = count($this->getChildren($node)) + 1;

        try {
            $this->db->beginTransaction();

            # Расширить новое место
            $sql = '
                UPDATE ' . $this->db_table . '
                    SET ' . $this->left_column . ' =
                            CASE WHEN ' . $this->left_column . ' > ' . $parent[$this->right_column] . '
                                THEN ' . $this->left_column . ' + (2 * ' . $count_nodes . ')
                                ELSE ' . $this->left_column . '
                            END,
                        ' . $this->right_column . ' = ' . $this->right_column . ' + (2 * ' . $count_nodes . ')
                WHERE ' . $this->right_column . ' > ' . $parent[$this->right_column];

            $q = $this->db->prepare($sql);
            $q->execute();

            # В бд ещё нет изменений
            if ($parent[$this->right_column] < $node[$this->right_column]) {
                $node[$this->left_column] = $node[$this->left_column] + (2 * $count_nodes);
                $node[$this->right_column] = $node[$this->right_column] + (2 * $count_nodes);
            }

            $difference = $parent[$this->right_column] - $node[$this->left_column] + 1;

            # Перенести на новое место
            $sql = '
                UPDATE ' . $this->db_table . '
                SET ' . ($params ? implode(' = ?, ', array_keys($params)) . ' = ?,' : '') . '
                    ' . $this->left_column . ' = ' . $this->left_column . ' + ' . $difference . ',
                    ' . $this->right_column . ' = ' . $this->right_column . ' + ' . $difference . '
                WHERE ' . $this->left_column . ' >= ' . $node[$this->left_column] . '
                    AND ' . $this->right_column . ' <= ' . $node[$this->right_column];

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->_clean($node);

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Перемещает узел $id_node в НАЧАЛО дочерних узлов выбранного узла $id_parent
     * @return boolean
     * @throws Exception
     */
    public function moveToFirstChildOf($id_node, $id_parent, array $params = array())
    {
        $id_node = (int) $id_node;
        $id_parent = (int) $id_parent;

        if ($id_node == $id_parent)
            return false;

        $node = $this->getNode($id_node);
        $parent = $this->getNode($id_parent);

        if (empty($node) or empty($parent))
            throw new Exception('Node Not Found!');

        if ($this->isParent($node, $parent)) {
            # throw new Exception('Node Is Parent!');
            $this->moveToNextSiblingOf($id_node, $id_parent);
            $this->moveToFirstChildOf($id_node, $id_parent, $params);

            return true;
        }

        if ($parent['left_key'] + 1 == $node['left_key']) {
            # throw new Exception('Node on Position!');
            $this->updateNode($node, $params);

            return true;
        }

        $params = $this->_safeParams($params);
        $count_nodes = $this->getCountChildren($node) + 1;

        try {
            $this->db->beginTransaction();

            # Расширить новое место
            $sql = '
                UPDATE ' . $this->db_table . '
                    SET ' . $this->left_column . ' =
                            CASE WHEN ' . $this->left_column . ' > ' . $parent[$this->left_column] . '
                                THEN ' . $this->left_column . ' + (2 * ' . $count_nodes . ')
                                ELSE ' . $this->left_column . '
                            END,
                        ' . $this->right_column . ' = ' . $this->right_column . ' + (2 * ' . $count_nodes . ')
                WHERE ' . $this->left_column . ' > ' . $parent[$this->left_column] . '
                    OR ' . $this->right_column . ' >= ' . $parent[$this->right_column];

            $q = $this->db->prepare($sql);
            $q->execute();

            # В бд ещё нет изменений
            if ($parent[$this->left_column] < $node[$this->left_column]) {
                $node[$this->left_column] = $node[$this->left_column] + (2 * $count_nodes);
                $node[$this->right_column] = $node[$this->right_column] + (2 * $count_nodes);
            }

            $difference = $parent[$this->left_column] - $node[$this->left_column] + 1;

            # Перенести на новое место
            $sql = 'UPDATE ' . $this->db_table . '
                    SET ' . ($params ? implode(' = ?, ', array_keys($params)) . ' = ?,' : '') . '
                        ' . $this->left_column . ' = ' . $this->left_column . ' + ' . $difference . ',
                        ' . $this->right_column . ' = ' . $this->right_column . ' + ' . $difference . '
                    WHERE ' . $this->left_column . ' >= ' . $node[$this->left_column] . '
                        AND ' . $this->right_column . ' <= ' . $node[$this->right_column];

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->_clean($node);

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Перемещает узел $id_node в КОНЕЦ дочерних узлов выбранного узла $id_parent
     * @return boolean
     * @throws Exception
     */
    public function moveToLastChildOf($id_node, $id_parent, array $params = array())
    {
        $id_node = (int) $id_node;
        $id_parent = (int) $id_parent;

        if ($id_node == $id_parent)
            return false;

        $node = $this->getNode($id_node);
        $parent = $this->getNode($id_parent);

        if (empty($node) or empty($parent))
            throw new Exception('Node Not Found!');

        if ($this->isParent($node, $parent)) {
            # throw new Exception('Node Is Parent!');
            $this->moveToNextSiblingOf($id_node, $id_parent);
            $this->moveToLastChildOf($id_node, $id_parent, $params);

            return true;
        }

        $params = $this->_safeParams($params);
        $count_nodes = $this->getCountChildren($node) + 1;

    try {
            $this->db->beginTransaction();

            $sql = '
                UPDATE ' . $this->db_table . '
                    SET ' . $this->left_column . ' =
                            CASE WHEN ' . $this->left_column . ' > ' . $parent[$this->right_column] . '
                                THEN ' . $this->left_column . ' + (2 * ' . $count_nodes . ')
                                ELSE ' . $this->left_column . '
                            END,
                        ' . $this->right_column . ' = ' . $this->right_column . ' + (2 * ' . $count_nodes . ')
                WHERE ' . $this->right_column . ' >= ' . $parent[$this->right_column];

            $q = $this->db->prepare($sql);
            $q->execute();

            # В бд ещё нет изменений
            if ($parent[$this->right_column] < $node[$this->left_column]) {
                $node[$this->left_column] = $node[$this->left_column] + (2 * $count_nodes);
                $node[$this->right_column] = $node[$this->right_column] + (2 * $count_nodes);
            }

            $difference = $parent[$this->right_column] - $node[$this->left_column];

            # Перенести на новое место
            $sql = 'UPDATE ' . $this->db_table . '
                    SET ' . ($params ? implode(' = ?, ', array_keys($params)) . ' = ?,' : '') . '
                        ' . $this->left_column . ' = ' . $this->left_column . ' + ' . $difference . ',
                        ' . $this->right_column . ' = ' . $this->right_column . ' + ' . $difference . '
                    WHERE ' . $this->left_column . ' >= ' . $node[$this->left_column] . '
                        AND ' . $this->right_column . ' <= ' . $node[$this->right_column];

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->_clean($node);

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Простое обновление информации
     * Simple update information for node
     * @return boolean
     * @throws Exception
     */
    public function updateNode($node, array $params)
    {
        if (!is_array($node))
            $node = $this->getNode($node);

        if (empty($node))
            throw new Exception('Node Not Found!');

        $params = $this->_safeParams($params);

        if (empty($params))
            return false;

        try {
            $this->db->beginTransaction();

            $sql = '
                UPDATE ' . $this->db_table . '
                SET ' . implode(' = ?, ', array_keys($params)) . ' = ?' . '
                WHERE ' . $this->left_column . ' = ' . $node[$this->left_column] . '
                    AND ' . $this->right_column . ' = ' . $node[$this->right_column];

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Удаляет узел и его потомков(опционально)
     * @return boolean
     * @throws Exception
     */
    public function deleteNode($id_node, $full = false)
    {
        $node = $this->getNode($id_node);
        if (empty($node))
            return false;

        # Удаляем только выбранный узел
        if (!$full) {
            try {
                $this->db->beginTransaction();

                $sql = '
                    DELETE FROM ' . $this->db_table . '
                    WHERE ' . $this->id_column . ' = ' . $node[$this->id_column];

                $q = $this->db->prepare($sql);
                $q->execute();

                $sql = '
                    UPDATE ' . $this->db_table . '
                        SET ' . $this->left_column . ' =
                                CASE WHEN ' . $this->left_column . ' > ' . $node[$this->left_column] . ' AND ' . $this->right_column . ' < ' . $node[$this->right_column] . '
                                        THEN ' . $this->left_column . ' - 1
                                     WHEN ' . $this->left_column . ' > ' . $node[$this->left_column] . '
                                        THEN ' . $this->left_column . ' - 2
                                     ELSE ' . $this->left_column . '
                                END,
                            ' . $this->right_column . ' =
                                CASE WHEN ' . $this->right_column . ' < ' . $node[$this->right_column] . '
                                         THEN ' . $this->right_column . ' - 1
                                     WHEN ' . $this->right_column . ' > ' . $node[$this->right_column] . '
                                        THEN ' . $this->right_column . ' - 2
                                     ELSE ' . $this->right_column . '
                                END
                    WHERE ' . $this->left_column . ' > ' . $node[$this->left_column] . '
                        OR ' . $this->right_column . ' > ' . $node[$this->right_column];

                $q = $this->db->prepare($sql);
                $q->execute();

                $this->db->commit();

                return true;
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        }
        # Удаляем ВСЮ ветку
        else {
            try {
                $this->db->beginTransaction();

                $sql = '
                    DELETE FROM ' . $this->db_table . '
                    WHERE ' . $this->left_column . ' >= ' . $node[$this->left_column] . '
                        AND ' . $this->right_column . ' <= ' . $node[$this->right_column];

                $q = $this->db->prepare($sql);
                $q->execute();

                $this->_clean($node);

                $this->db->commit();

                return true;
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        }

        return true;
    }

    /**
     * @return array
     */
    protected function _safeParams(array $params)
    {
        if (empty($params))
            return $params;

        foreach (array($this->id_column, $this->left_column, $this->right_column) as $key) {
            if (isset($params[$key]))
                unset($params[$key]);
        }

        return $params;
    }

    /**
     * "Замести следы"
     * Сжать расстояние соседей узла
     * @return boolean
     * @throws Exception
     */
    protected function _clean($node)
    {
        if (empty($node))
            throw new Exception('Value should not be empty!');

        if (!is_array($node))
            $node = $this->getNode($node);

        $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . $this->left_column . ' =
                    CASE WHEN ' . $this->left_column . ' > ' . $node[$this->left_column] . '
                        THEN ' . $this->left_column . ' - (' . $node[$this->right_column] . ' - ' . $node[$this->left_column] . ' + 1)
                        ELSE ' . $this->left_column . '
                    END,
                    ' . $this->right_column . ' = ' . $this->right_column . ' - (' . $node[$this->right_column] . ' - ' . $node[$this->left_column] . ' + 1)
            WHERE ' . $this->right_column . ' > ' . $node[$this->right_column];

        $q = $this->db->prepare($sql);
        $q->execute();

        return true;
    }

    public function __set($index, $value)
    {
        if (empty($index) or empty($value))
            throw new Exception('Пусто, блеать!');
        elseif (!in_array($index, array('db_table', 'id_column', 'left_column',
                                        'right_column', 'level')))
            throw new Exception('Переменная не зарезервирована для замены!');

        $this->$index = $value;
    }

    public function __destruct()
    {
        $this->db = null;
    }
}
