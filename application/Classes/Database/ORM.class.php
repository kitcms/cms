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

class ORM extends \ORM
{
    public function set($key, $value = null) {
        return $this->_set_orm_property($key, $value);
    }

    public function set_expr($key, $value = null) {
        return $this->_set_orm_property($key, $value, true);
    }

    public function set_class_name($class_name) {
        $this->_class_name = $class_name;
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

    public function force_all_dirty() {
        $data = $this->_data;
        foreach ($data as $key => $value) {
            if (is_array($value) && $value = json_encode($value, JSON_UNESCAPED_UNICODE)) {
                $data[$key] = $value;
            }
        }
        $this->_dirty_fields = $data;
        return $this;
    }
}
