<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\Utils;

/**
 * ApiKey model to manage the connection tokens through the api
 * that will be generated to synchronize different applications.
 *
 * @author Joe Nilson           <joenilson at gmail.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class ApiKey extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * API key.
     *
     * @var string
     */
    public $apikey;

    /**
     * Date of registration.
     *
     * @var string
     */
    public $creationdate;

    /**
     * Description.
     *
     * @var string
     */
    public $description;

    /**
     * Enabled/Disabled.
     *
     * @var bool
     */
    public $enabled;

    /**
     * Primary key. Id autoincremental
     *
     * @var int
     */
    public $id;

    /**
     * Nick of the user.
     *
     * @var string
     */
    public $nick;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->apikey = Utils::randomString(20);
        $this->enabled = true;
        $this->creationdate = date('d-m-Y');
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'id';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'api_keys';
    }
}
