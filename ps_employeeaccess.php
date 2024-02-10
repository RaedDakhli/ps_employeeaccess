<?php
require_once _PS_MODULE_DIR_ . 'ps_employeeaccess/classes/EmployeeAccess.php';
require_once _PS_MODULE_DIR_ . 'ps_employeeaccess/classes/Logger.php';
class Ps_EmployeeAccess extends Module
{
    public function __construct()
    {
        $this->name = 'ps_employeeaccess';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'PrestaShop';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Employee Access');
        $this->description = $this->l('Grant access to products based on employees.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function install()
    {
        // Install SQL schema
        include dirname(__FILE__) . '/sql/install.php';

        if (
            parent::install() &&
            $this->registerHook('actionProductUpdate') &&
            $this->registerHook('displayAdminProductsExtra') &&
            $this->registerHook('actionProductGridQueryBuilderModifier') &&
            $this->registerHook('displayBackOfficeHeader') &&
            Configuration::updateValue('PS_EMPLOYEE_PROFILE_ID', 5)
        ) {
            return true;
        }

        return false;
    }

    public function uninstall()
    {
        // Uninstall SQL schema
        include(dirname(__FILE__) . '/sql/uninstall.php');

        if (
            !parent::uninstall() ||
            !Configuration::deleteByName('PS_EMPLOYEE_PROFILE_ID')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Retrieves module configuration page content.
     *
     * @return string Content of the configuration page.
     */
    public function getContent()
    {
        $output = '';

        // Update the value of PS_EMPLOYEE_PROFILE_ID
        $newProfileId = (int) Tools::getValue('PS_EMPLOYEE_PROFILE_ID');
        Configuration::updateValue('PS_EMPLOYEE_PROFILE_ID', $newProfileId);

        // Clear the cache
        $this->_clearCache('*');

        // Display confirmation message
        $confirmationMessage = $this->trans('The settings have been updated.', [], 'Admin.Notifications.Success');
        $output .= $this->displayConfirmation($confirmationMessage);

        // Log the update action
        $logMessage = sprintf(
            'PS_EMPLOYEE_PROFILE_ID updated to %d by Employee %d',
            $newProfileId,
            (int)Context::getContext()->employee->id
        );
        Logger::log($logMessage);

        // Render the configuration form
        return $output . $this->renderForm();
    }

    /**
     * Render the configuration form.
     *
     * @return string HTML content of the form
     */
    public function renderForm()
    {
        // Retrieve employee profiles
        $employees = Profile::getProfiles($this->context->language->id); // Récupérer la liste des employés pour le profil 5

        // Define form fields
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Employee Profile'),
                        'name' => 'PS_EMPLOYEE_PROFILE_ID',
                        'options' => array(
                            'query' => $employees,
                            'id' => 'id_profile',
                            'name' => 'name',
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ),
            ),
        );

        // Initialize HelperForm
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submit' . $this->name;
        // Set default field value
        $helper->fields_value['PS_EMPLOYEE_PROFILE_ID'] = Configuration::get('PS_EMPLOYEE_PROFILE_ID', true);

        // Generate the form HTML
        return $helper->generateForm(array($fields_form));
    }

    /**
     * Add necessary JavaScript and CSS files to the back office header.
     */
    public function hookDisplayBackOfficeHeader()
    {
        // Check if the current controller is AdminProducts
        if (Tools::getValue('controller') == 'AdminProducts') {
            // Add the JavaScript file for employee selection
            $this->context->controller->addJS($this->_path . 'views/js/employee_selection.js');
            /// Add the Select2 CSS file
            $this->context->controller->addCSS($this->_path . 'views/css/select2.min.css');
            // Add the Select2 JavaScript file
            $this->context->controller->addJS($this->_path . 'views/js/select2.min.js');
        }
    }

    /**
     * Display additional content in the admin products page.
     *
     * @param array $params The hook parameters.
     * @return string|null The HTML content to display.
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        // Extract the product ID from the request parameters
        $productId = (int)Tools::getValue('id_product');

        // Check if a valid product ID is provided
        if ($productId > 0) {

            // Load the product object based on the ID
            $product = new Product($productId);

            // Retrieve employees based on the configured employee profile ID
            $employees = Employee::getEmployeesByProfile((Configuration::get('PS_EMPLOYEE_PROFILE_ID', true)));

            // Retrieve the selected employee for the product
            $selected_employee = EmployeeAccess::getByProductId($productId);

            // Assign variables to Smarty for template rendering
            $this->context->smarty->assign(array(
                'selected_employee' => $selected_employee,
                'employees' => $employees,
                'product' => $product,
            ));

            // Render the template and return the HTML content
            return $this->display(__FILE__, 'views/templates/admin/employee_attach.tpl');
        }
        // If no valid product ID is provided, return null
        return null;
    }

    /**
     * Hook triggered when a product is updated.
     *
     * @param array $params Parameters passed to the hook.
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionProductUpdate($params)
    {
        // Extract employee ID from the submitted form
        $id_employee = (int)Tools::getValue('employee_id');
        // Extract product ID from the parameters
        $id_product = (int)$params['id_product'];

        // Check if the product already has an employee assigned
        $existingEmployeeAccess = EmployeeAccess::getByProductId($id_product);

        // Log message initialization
        $logMessage = '';

        if ($existingEmployeeAccess) {
            // Product already has an employee assigned, update the employee ID
            $existingEmployeeAccess->id_employee = $id_employee;
            $existingEmployeeAccess->date_upd = date('Y-m-d H:i:s');
            $existingEmployeeAccess->save();

            // Log message for updating employee assignment
            $logMessage = sprintf(
                'Employee assignment updated for product ID: %d. to Employee ID: %d by Employee %d',
                $id_product,
                $id_employee,
                (int)Context::getContext()->employee->id
            );
        } else {
            // Product doesn't have an employee assigned yet, create a new entry
            $employeeAccess = new EmployeeAccess();
            $employeeAccess->id_employee = $id_employee;
            $employeeAccess->id_product = $id_product;
            $employeeAccess->date_add = date('Y-m-d H:i:s');
            $employeeAccess->save();

            // Log message for creating new employee assignment
            $logMessage = sprintf(
                'New employee assignment created for product ID: %d. Employee ID: %d by Employee %d',
                $id_product,
                $id_employee,
                (int)Context::getContext()->employee->id
            );
        }
        // Log the action
        Logger::log($logMessage);
    }

    /**
     * Modifies the product grid query builder to filter products based on employee access.
     *
     * @param array $params Parameters passed to the hook.
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionProductGridQueryBuilderModifier(array $params)
    {
        /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $params['search_query_builder'];

        // Retrieve employee ID
        $employeeId = (int)Context::getContext()->employee->id;

        // Retrieve profile ID
        $profileId = (int)Context::getContext()->employee->id_profile;

        // Retrieve product IDs associated with the employee
        $productIds = EmployeeAccess::getProductsSqlByEmployee($employeeId);


        // If no products are associated with the employee, or if the profile is not PS_EMPLOYEE_PROFILE_ID, do nothing
        if (empty($productIds) && $profileId != Configuration::get('PS_EMPLOYEE_PROFILE_ID', true)) {
            return;
        }

        // Extract product IDs from the array
        $productIdsArray = array_column($productIds, 'id_product');

        // Create a comma-separated string of product IDs
        $productIdsString = implode(',', $productIdsArray);

        // Construct the new SQL query to filter products
        $whereClause = 'p.id_product IN (' . $productIdsString . ')';
        $queryBuilder->andWhere($whereClause);

        $countQueryBuilder = $params['count_query_builder'];

        // So the pagination and the number of customers
        // retrieved will be right.
        $countQueryBuilder->andWhere($whereClause);

        // Log the action
        $logMessage = sprintf(
            'Products grid query builder modified to show products %s for Employee ID: %d',
            $productIdsString,
            $employeeId

        );
        Logger::log($logMessage);
    }
}
