<?php
/*
 * ...
 *
 * @package   This file is part of the Kit.cms
 * @author    Anton Popov <a.popov@kit.team>
 * @copyright Kit.team <http://www.kit.team>
 * @link      Kit.cms <http://www.kitcms.ru>
 */

namespace Classes\Model;

use Classes\Database\Model;

class Section extends Model
{
    protected $_table = 'Section';

    protected $fields = array(
        array(
            'field' => 'site',
            'type' => 'int(10) unsigned',
            'key' => 'mul',
            'default' => '0',
            'comment' => 'Идентификатор сайта'
        ),
        array(
            'field' => 'parent',
            'type' => 'int(10) unsigned',
            'key' => 'mul',
            'default' => '0',
            'comment' => 'Идентификатор родительского объекта'
        ),
        array(
            'field' => 'template',
            'type' => 'int(10) unsigned',
            'null' => 'yes',
            'key' => 'mul',
            'comment' => 'Идентификатор макета дизайна'
        ),
        array(
            'field' => 'path',
            'type' => 'varchar(255)',
            'comment' => 'Материализованный путь'
        ),
        array(
            'field' => 'type',
            'type' => 'tinyint(1)',
            'null' => 'yes',
            'comment' => 'Тип раздела'
        ),
        array(
            'field' => 'model',
            'type' => 'varchar(255)',
            'null' => 'yes',
            'comment' => 'Модель данных',
        ),
        array(
            'field' => 'pattern',
            'type' => 'varchar(255)',
            'null' => 'yes',
            'comment' => 'Паттерн для разбора параметров'
        ),
        array(
            'field' => 'title',
            'type' => 'varchar(255)',
            'comment' => 'Название раздела'
        ),
        array(
            'field' => 'description',
            'type' => 'text',
            'null' => 'yes',
            'comment' => 'Описание'
        ),
        array(
            'field' => 'meta',
            'type' => 'longtext',
            'null' => 'yes',
            'comment' => 'Метаинформация'
        )
    );

    public function site()
    {
        return $this->hasOne(__NAMESPACE__ . NS .'Site', 'id', 'site');
    }

    public function parent()
    {
        return $this->hasOne(__CLASS__, 'id', 'parent');
    }

    public function parents()
    {
        $paths = explode('/', $this->path);
        array_pop($paths);
        $where = array(array_shift($paths));
        foreach ($paths as $path) {
            array_push($where, end($where) .'/'. $path);
        }
        return $this->hasMany(__CLASS__, 'site', 'site')->whereIn('path', $where);
    }

    public function children()
    {
        return $this->hasMany(__CLASS__, 'parent', 'id');
    }

    public function childrens()
    {
        return $this->hasMany(__CLASS__, 'site', 'site')->whereNotIn('id', (array) $this->id)->whereLike('path', $this->path .'%');
    }

    public function save()
    {
        if ($this->isDirty('keyword')) {
            // Проверка на корректность
            if (!preg_match("/^[[:alnum:]-.]+$/iu", $this->keyword)) {
                return false;
            }
        }

        $this->set('path', $this->keyword);
        if ($parent = $this->parent()->findOne()) {
            // Если раздел имеет расширение, то нельзя добавлять подразделы
            if ($parent->extension) {
                return false;
            }
            if ($parent->type) {
                // Если родителький элемент
                $parent->path = $parent->keyword;
                if ($parent->parent && ($parental = $parent->parent()->findOne())) {
                    $parent->path = $parental->path .'/'. $parent->path;
                }
            }
            $this->set(array('path' => $parent->path .'/'. $this->keyword, 'site' => $parent->site));
        }
        // Проверка на уникальность
        if ($this->isDirty('path')) {
            $instance = $this->hasOne(__CLASS__, 'site', 'site')->where('path', $this->get('path'));
            if (false === $this->isNew()) {
                $instance->whereNotIn('id', (array) $this->id);
            }
            if ($instance->findOne()) {
                return false;
            }
        }
        // Массивы зависимых объектов
        $dependences = array();
        if ($this->isDirty('site') || $this->isDirty('parent') || $this->isDirty('path')) {
            // Для раздела, имеющего подразделы, нельзя задавать расширение
            if (($childrens = $this->children()->findMany()) && strrpos($this->keyword, '.')) {
                return false;
            }
            array_push($dependences, $childrens);
        }
        if (parent::save()) {
            foreach ((array) $dependences as $dependence) {
                foreach ((array) $dependence as $depend) {
                    $depend->set('path', null)->save();
                }
            }
            return true;
        }
        return false;
    }

    public function delete()
    {
        // Массивы зависимых объектов
        $dependences = array();
        array_push($dependences, $this->children()->findMany());
        if (parent::delete()) {
            // Удаление зависимых объектов
            foreach ((array) $dependences as $dependence) {
                foreach ((array) $dependence as $depend) {
                    $depend->delete();
                }
            }
        }
        return false;
    }

    protected function _create_model_instance($orm) {
        $data = $this->asArray();
        if (false === $this->isDirty('keyword')) {
            $parts = explode('/', $this->get('path'));
            $keyword = array_pop($parts);
            $extension = false;
            if (false !== $pos = strrpos($keyword, '.')) {
                $extension = substr($keyword, $pos + 1);
                //$keyword = substr($keyword, 0, $pos);
            }
            $data = array_merge($data, array('keyword' => $keyword, 'extension' => $extension));
        }
        // Если главная страница удаляем информацию о материализованном пути
        if ($this->get('type')) {
            unset($data['path']);
        }
        $this->orm->hydrate($data);
    }
}
