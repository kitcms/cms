<?php
/*
 * ...
 *
 * @package   This file is part of the Kit.cms
 * @author    Anton Popov <a.popov@kit.team>
 * @copyright Kit.team <http://www.kit.team>
 * @link      Kit.cms <http://www.kitcms.ru>
 */

namespace Classes\Database;

class ORMWrapper extends \ORMWrapper
{
    public static function for_table($table_name, $connection_name = self::DEFAULT_CONNECTION) {
        self::_setup_db($connection_name);
        return new self($table_name, array(), $connection_name);
    }

    public function get_table_name()
    {
        return $this->_table_name;
    }

    public function find_array() {
        $rows = $this->_run();
        foreach ($rows as $index => $row) {
            foreach ($row as $field => $values) {
                if (is_array($values)) {
                    foreach ($values as $key => $value) {
                        if (!is_array($value) && $value = json_decode(trim($value, '"'), true)) {
                            $rows[$index][$field][$key] = $value;
                        }
                    }
                } elseif (!is_array($values) && $values = json_decode(trim($values, '"'), true)) {
                    $rows[$index][$field] = $values;
                }
            }
        }
        return $rows;
    }

    protected function _create_model_instance($orm)
    {
        if ($orm === false) {
            return false;
        }
        $model = new $this->_class_name();
        $model->setOrm($orm);
        if (method_exists($model, __FUNCTION__)) {
            call_user_func(array($model, __FUNCTION__), $orm);
        }
        return $model;
    }

    protected function _create_instance_from_row($row) {
        $instance = self::for_table($this->_table_name, $this->_connection_name);
        $instance->use_id_column($this->_instance_id_column);
        $instance->hydrate($row);
        return $instance;
    }

    public function hydrate($data=array())
    {
        foreach ($data as $field => $values) {
            if (is_array($values)) {
                foreach ($values as $key => $value) {
                    if (!is_array($value) && $value = json_decode(trim($value, '"'), true)) {
                        $data[$field][$key] = $value;
                    }
                }
            } elseif (!is_array($values) && $values = json_decode(trim($values, '"'), true)) {
                $data[$field] = $values;
            }
        }
        $this->_data = $data;
        return $this;
    }

    public function force_all_dirty()
    {
        $data = $this->_data;
        foreach ($data as $key => $value) {
            if (is_array($value) && $value = json_encode($value, JSON_UNESCAPED_UNICODE)) {
                $data[$key] = $value;
            }
        }
        $this->_dirty_fields = $data;
        return $this;
    }

    protected function _set_orm_property($key, $value = null, $expr = false)
    {
        if (!is_array($key)) {
            $key = array($key => $value);
        }
        foreach ($key as $field => $value) {
            $this->_data[$field] = $value;
            $this->_dirty_fields[$field] = $value;
            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    if (!is_array($val) && $val = json_decode(trim($val, '"'), true)) {
                        $value[$key] = $val;
                    }
                }
                $this->_data[$field] = $value;
                $this->_dirty_fields[$field] = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif (!is_array($value) && $value = json_decode(trim($value, '"'), true)) {
                $this->_data[$field] = $value;
            }
            if (false === $expr and isset($this->_expr_fields[$field])) {
                unset($this->_expr_fields[$field]);
            } else if (true === $expr) {
                $this->_expr_fields[$field] = true;
            }
        }
        return $this;
    }

    /*
     * Возможность вызова методов в обход filter()
     */
    public function __call($method, $arguments) {
        if (method_exists($this->_class_name, $method)) {
            return call_user_func_array(array($this->_class_name, $method), $arguments);
        } else {
            return parent::__call($method, $arguments);
        }
    }
}
