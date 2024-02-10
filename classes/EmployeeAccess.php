<?php

class EmployeeAccess extends ObjectModel
{
    public $id_employee_access;
    public $id_employee;
    public $id_product;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'employee_access',
        'primary' => 'id_employee_access',
        'fields' => array(
            'id_employee' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_product' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    /**
     * Retrieves an entry from the employee_access table based on the provided product ID.
     *
     * @param int $id_product The ID of the product.
     * @return EmployeeAccess|bool An EmployeeAccess object if the entry is found, otherwise false.
     */
    public static function getByProductId($id_product)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'employee_access` WHERE `id_product` = ' . (int) $id_product;

        $result = Db::getInstance()->getRow($sql);

        if ($result) {
            $employee = new EmployeeAccess();
            $employee->hydrate($result);
            return $employee;
        }
        return false;
    }

    /**
     * Retrieves product IDs associated with the given employee ID.
     *
     * @param int $id_employee The ID of the employee.
     * @return array|null An array of product IDs associated with the employee, or null if no products are found.
     */
    public static function getProductsSqlByEmployee($id_employee)
    {
        $sql = 'SELECT id_product FROM ' . _DB_PREFIX_ . 'employee_access WHERE id_employee = ' . (int)$id_employee;
        return Db::getInstance()->executeS($sql);
    }
}
