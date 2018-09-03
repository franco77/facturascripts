<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\PageOption;
use FacturaScripts\Core\Model\User;

/**
 * Base definition for the views used in ExtendedControllers
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
abstract class BaseView
{

    /**
     * Total count of read rows.
     *
     * @var int
     */
    public $count;

    /**
     * Cursor with data from the model display
     *
     * @var array
     */
    public $cursor;

    /**
     *
     * @var string
     */
    public $icon;

    /**
     * Contains the translator.
     *
     * @var Base\Translator
     */
    protected static $i18n;

    /**
     * Model to use in this view.
     *
     * @var ModelClass
     */
    public $model;

    /**
     *
     * @var string
     */
    private $name;

    /**
     * Stores the new code from the save() procedure, to use in loadData().
     *
     * @var string
     */
    public $newCode;

    /**
     * Stores the offset for the cursor
     *
     * @var int
     */
    public $offset;

    /**
     *
     * @var array
     */
    public $order;

    /**
     * Columns configuration
     *
     * @var PageOption
     */
    protected $pageOption;

    /**
     *
     * @var array
     */
    public $settings;

    /**
     *
     * @var string
     */
    public $template;

    /**
     * View title
     *
     * @var string
     */
    public $title;

    /**
     * Stores the where parameters for the cursor
     *
     * @var DataBaseWhere[]
     */
    protected $where;

    /**
     * Method to export the view data.
     */
    abstract public function export(&$exportManager);

    /**
     * Loads view data.
     */
    abstract public function loadData($code = false, $where = [], $order = [], $offset = 0, $limit = FS_ITEM_LIMIT);

    /**
     * Process request data needed.
     */
    abstract public function processRequest($request);

    /**
     * Construct and initialize the class
     *
     * @param string $name
     * @param string $title
     * @param string $modelName
     * @param string $icon
     */
    public function __construct(string $name, string $title, string $modelName, string $icon)
    {
        if (!isset(static::$i18n)) {
            static::$i18n = new Base\Translator();
        }

        $this->count = 0;
        $this->cursor = [];
        $this->icon = $icon;
        $this->model = class_exists($modelName) ? new $modelName() : null;
        $this->name = $name;
        $this->offset = 0;
        $this->order = [];
        $this->pageOption = new PageOption();
        $this->settings = [];
        $this->template = 'Master/BaseView.html.twig';
        $this->title = static::$i18n->trans($title);
        $this->where = [];
    }

    /**
     * Clears the model and set new code for the PK.
     */
    public function clear()
    {
        $this->model->clear();
        $this->model->{$this->model->primaryColumn()} = $this->model->newCode();
    }

    /**
     * Gets the column by the given field name
     *
     * @param string $fieldName
     *
     * @return ColumnItem
     */
    public function columnForField(string $fieldName)
    {
        $result = null;
        foreach ($this->pageOption->columns as $group) {
            foreach ($group->columns as $column) {
                if ($column->widget->fieldName === $fieldName) {
                    $result = $column;
                    break;
                }
            }
            if (!empty($result)) {
                break;
            }
        }

        return $result;
    }

    /**
     * Gets the column by the column name
     *
     * @param string $columnName
     *
     * @return ColumnItem
     */
    public function columnForName(string $columnName)
    {
        $result = null;
        foreach ($this->pageOption->columns as $group) {
            foreach ($group->columns as $key => $column) {
                if ($key === $columnName) {
                    $result = $column;
                    break;
                }
            }
            if (!empty($result)) {
                break;
            }
        }

        return $result;
    }

    /**
     * Establishes the column's edit state
     *
     * @param string $columnName
     * @param bool   $disabled
     */
    public function disableColumn($columnName, $disabled)
    {
        ;
    }

    public function getPagination()
    {
        $pages = [];
        $key1 = $key2 = 0;
        $current = 1;

        /// add all pages
        while ($key2 < $this->count) {
            $pages[$key1] = [
                'active' => ($key2 == $this->offset),
                'num' => $key1 + 1,
                'offset' => $key1 * FS_ITEM_LIMIT,
            ];
            if ($key2 == $this->offset) {
                $current = $key1;
            }
            $key1++;
            $key2 += FS_ITEM_LIMIT;
        }

        /// now descarting pages
        foreach (array_keys($pages) as $key2) {
            $middle = intval($key1 / 2);

            /**
             * We discard everything except the first page, the last one, the middle one,
             * the current one, the 5 previous and 5 following ones.
             */
            if (($key2 > 1 && $key2 < $current - 5 && $key2 != $middle) || ( $key2 > $current + 5 && $key2 < $key1 - 1 && $key2 != $middle)) {
                unset($pages[$key2]);
            }
        }

        return (count($pages) > 1) ? $pages : [];
    }

    /**
     * Returns the name.
     *
     * @return string
     */
    public function getViewName()
    {
        return $this->name;
    }

    /**
     * 
     * @param User|false $user
     */
    public function loadPageOptions($user)
    {
        
    }
}
