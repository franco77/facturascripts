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

use Exception;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Lib\Widget\ColumnItem;
use FacturaScripts\Core\Lib\Widget\WidgetAutocomplete;
use FacturaScripts\Core\Lib\Widget\WidgetSelect;
use FacturaScripts\Core\Model\Base\ModelClass;

/**
 * Description of GridView
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class GridView extends EditView
{

    const GRIDVIEW_TEMPLATE = 'Master/GridView.html.twig';

    /**
     * Detail view
     *
     * @var BaseView
     */
    public $detailView;

    /**
     *
     * @var ModelClass
     */
    public $detailModel;

    /**
     * Template for edit master data
     *
     * @var string
     */
    public $editTemplate = self::EDITVIEW_TEMPLATE;

    /**
     * Grid data configuration and data
     *
     * @var array
     */
    private $gridData;

    /**
     * GridView constructor and initialization.
     * Master/Detail params:
     *   ['name' = 'viewName', 'model' => 'modelName']
     *
     * @param array   $master
     * @param array   $detail
     * @param string  $title
     * @param string  $icon
     */
    public function __construct($master, $detail, $title, $icon)
    {
        parent::__construct($master['name'], $title, $master['model'], $icon);

        // Create detail view
        $this->detailView = new EditView($detail['name'], $title, $detail['model'], $icon);
        $this->detailModel = $this->detailView->model;

        // custom template
        $this->template = self::GRIDVIEW_TEMPLATE;
    }

    /**
     * Returns detail column configuration
     *
     * @param string $key
     *
     * @return ColumnItem[]
     */
    public function getDetailColumns($key = '')
    {
        if (!array_key_exists($key, $this->detailView->columns)) {
            if ($key == 'master') {
                return [];
            }
            $key = array_keys($this->detailView->columns)[0];
        }

        return $this->detailView->columns[$key]->columns;
    }

    /**
     * Returns JSON into string with Grid view data
     *
     * @return string
     */
    public function getGridData(): string
    {
        return json_encode($this->gridData);
    }

    /**
     * Load the data in the model property, according to the code specified.
     *
     * @param string          $code
     * @param DataBaseWhere[] $where
     * @param array           $order
     * @param int             $offset
     * @param int             $limit
     */
    public function loadData($code = '', $where = array(), $order = array(), $offset = 0, $limit = FS_ITEM_LIMIT)
    {
        parent::loadData($code, $where, $order, $offset, $limit);

        if ($this->count == 0) {
            $this->template = self::EDITVIEW_TEMPLATE;
        } else {
            if ($this->newCode !== null) {
                $code = $this->newCode;
            }

            $where = [new DataBaseWhere($this->model->primaryColumn(), $code)];
            $orderby = [$this->detailView->model->primaryColumn() => 'ASC'];
            $this->loadGridData($where, $orderby);
        }
    }

    /**
     * Load detail data and set grid configuration
     *
     * @param DataBaseWhere[] $where
     * @param array           $order
     */
    public function loadGridData($where = array(), $order = array())
    {
        // load columns configuration
        $this->gridData = $this->getGridColumns();

        // load detail model data
        $this->gridData['rows'] = [];
        $this->detailView->count = $this->detailView->model->count($where);
        if ($this->detailView->count > 0) {
            foreach ($this->detailView->model->all($where, $order, 0, 0) as $line) {
                $this->gridData['rows'][] = (array) $line;
            }
        }
    }

    /**
     *
     * @param array $lines
     * @return array
     */
    public function processFormLines(&$lines): array
    {
        $result = [];
        $primaryKey = $this->detailView->model->primaryColumn();
        foreach ($lines as $data) {
            if (!isset($data[$primaryKey])) {
                foreach ($this->getDetailColumns('detail') as $col) {
                    if (!isset($data[$col->widget->fieldname])) {
                        // TODO: maybe the widget can have a default value method instead of null
                        $data[$col->widget->fieldname] = null;
                    }
                }
            }
            $result[] = $data;
        }

        return $result;
    }

    public function saveData($data): array
    {
        $result = [
            'error' => false,
            'message' => '',
            'url' => ''
        ];

        try {
            // load master document data and test it's ok
            if (!$this->loadDocumentDataFromArray('code', $data['document'])) {
                throw new Exception(self::$i18n->trans('parent-document-test-error'));
            }

            // load detail document data (old)
            $primaryKey = $this->model->primaryColumn();
            $primaryKeyValue = $this->model->primaryColumnValue();
            $linesOld = $this->detailView->model->all([new DataBaseWhere($primaryKey, $primaryKeyValue)]);

            // start transaction
            $dataBase = new DataBase();
            $dataBase->beginTransaction();

            // delete old lines not used
            if (!$this->deleteLinesOld($linesOld, $data['lines'])) {
                throw new Exception(self::$i18n->trans('lines-delete-error'));
            }

            // Proccess detail document data (new)
            $this->model->initTotals(); // Master Model must implement GridModelInterface
            foreach ($data['lines'] as $newLine) {
                $this->detailView->model->loadFromData($newLine);
                if (empty($this->detailView->model->primaryColumnValue())) {
                    $this->detailView->model->{$primaryKey} = $primaryKeyValue;
                }
                if (!$this->detailView->model->save()) {
                    throw new Exception(self::$i18n->trans('lines-save-error'));
                }
                $this->model->accumulateAmounts($newLine);
            }

            // save master document
            if (!$this->model->save()) {
                throw new Exception(self::$i18n->trans('parent-document-save-error'));
            }

            // confirm save data into database
            $dataBase->commit();

            // URL for refresh data
            $result['url'] = $this->model->url('edit') . '&action=save-ok';
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        } finally {
            if ($dataBase->inTransaction()) {
                $dataBase->rollback();
            }
            return $result;
        }
    }

    protected function assets()
    {
        AssetManager::add('css', FS_ROUTE . '/node_modules/handsontable/dist/handsontable.full.min.css');
        AssetManager::add('js', FS_ROUTE . '/node_modules/handsontable/dist/handsontable.full.min.js');
        AssetManager::add('js', FS_ROUTE . '/Dinamic/Assets/JS/GridView.js');
    }

    /**
     * Removes from the database the non-existent detail
     *
     * @param array $linesOld
     * @param array $linesNew
     *
     * @return bool
     */
    private function deleteLinesOld(&$linesOld, &$linesNew): bool
    {
        if (!empty($linesOld)) {
            $model = $this->detailView->model;
            $fieldPK = $model->primaryColumn();
            $oldIDs = array_column($linesOld, $fieldPK);
            $newIDs = array_column($linesNew, $fieldPK);
            $deletedIDs = array_diff($oldIDs, $newIDs);

            foreach ($deletedIDs as $idKey) {
                $model->loadFromCode($idKey);
                if (!$model->delete()) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Configure autocomplete column with data to Grid component
     *
     * @param WidgetAutocomplete $widget
     *
     * @return array
     */
    private function getAutocompleteSource($widget): array
    {
        $url = $this->model->url('edit');
        $datasource = $widget->getDataSource();

        return [
            'url' => $url,
            'source' => $datasource['source'],
            'field' => $datasource['fieldcode'],
            'title' => $datasource['fieldtitle']
        ];
    }

    /**
     * Return grid columns configuration
     * from pages_options of columns
     *
     * @return array
     */
    private function getGridColumns(): array
    {
        $data = [
            'headers' => [],
            'columns' => [],
            'hidden' => [],
            'colwidths' => []
        ];

        foreach ($this->getDetailColumns('detail') as $col) {
            $item = $this->getItemForColumn($col);
            if ($col->hidden()) {
                $data['hidden'][] = $item;
            } else {
                $data['columns'][] = $item;
                $data['colwidths'][] = $col->htmlWidth();
                $data['headers'][] = self::$i18n->trans($col->title);
            }
        }

        return $data;
    }

    /**
     * Return grid column configuration
     *
     * @param ColumnItem $column
     *
     * @return array
     */
    private function getItemForColumn($column): array
    {
        $item = [
            'data' => $column->widget->fieldname,
            'type' => $column->widget->getType()
        ];
        switch ($item['type']) {
            case 'autocomplete':
                $item['visibleRows'] = 5;
                $item['allowInvalid'] = true;
                $item['trimDropdown'] = false;
                $item['strict'] = $column->widget->strict;
                $item['data-source'] = $this->getAutocompleteSource($column->widget);
                break;

            case 'select':
                $item['editor'] = 'select';
                $item['selectOptions'] = $this->getSelectSource($column->widget);
                break;

            case 'number':
            case 'money':
                $item['type'] = 'numeric';
                $item['numericFormat'] = DivisaTools::gridMoneyFormat();
                break;
        }

        return $item;
    }

    /**
     * Return array of values to select
     *
     * @param WidgetSelect $widget
     */
    private function getSelectSource($widget): array
    {
        $result = [];
        if (!$widget->required) {
            $result[] = '';
        }

        foreach ($widget->values as $value) {
            $result[] = $value['title'];
        }
        return $result;
    }

    /**
     * Load data of master document and set data from array
     *
     * @param string $field
     * @param array  $data
     *
     * @return bool
     */
    private function loadDocumentDataFromArray($field, &$data): bool
    {
        if ($this->model->loadFromCode($data[$field])) {    // old data
            $this->model->loadFromData($data, ['action', 'activetab', 'code']);  // new data (the web form may be not have all the fields)
            return $this->model->test();
        }
        return false;
    }
}
