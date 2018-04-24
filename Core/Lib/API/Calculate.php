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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\API;

/**
 * TestAPI to test API functionality
 *
 * @author Rafael San José Tovar (http://www.x-netdigital.com) <rsanjoseo@gmail.com>
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Calculate extends Base\APIResourceClass
{

    public function processResource()
    {
        $data = [
            'name' => 'Perform simple arithmetic operations',
            'subname' => 'Indicate operator and two or more operands',
            'options' => 'Available operators:',
            'sum' => 'sum',
            'subtraction' => 'subtraction',
            'multiplication' => 'multiplication',
            'division' => 'division',
            'sample' => 'facturascripts.com/api/3/calculates/subtraction/10/2/1',
            'result' => 'Proccess 10-2-1 and return 7',
        ];

        $this->returnResult($data);
    }

    public function processResourceParam(array $param)
    {
        $operator = array_shift($param);
        $result = (float) array_shift($param);
        foreach ($param as $_value) {
            $value = (float) $_value;
            switch ($operator) {
                case 'sum':
                    $result += (float) $value;
                    break;

                case 'subtraction':
                    $result -= (float) $value;
                    break;

                case 'multiplication':
                    $result *= (float) $value;
                    break;

                case 'division':
                    if ($value == 0) {
                        $this->setError('No se puede dividir entre 0');
                        return false;
                    }
                    $result /= (float) $value;
                    break;

                default:
                    $this->setError("Bad Operator" . $operator);
                    return false;
            }
        }

        $this->returnResult(array($operator => $result));
        return true;
    }
}