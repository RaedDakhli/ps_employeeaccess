{if isset($product) && $product->id}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Assign Employee'}
        </div>
        <div class="form-group">
            <label class="control-label">{l s='Select Employee'}</label>
            <select name="employee_id" id="employee_id" class="form-control select2">
                <option value="" selected>{l s='Select Employee'}</option>
                {foreach from=$employees item=employee}
                    {if ($selected_employee) && $selected_employee->id_employee == $employee.id_employee}
                        <option value="{$employee.id_employee}" selected>{$employee.firstname} {$employee.lastname}</option>
                    {else}
                        <option value="{$employee.id_employee}">{$employee.firstname} {$employee.lastname}</option>
                    {/if}
                {/foreach}
            </select>
        </div>
    </div>
{/if}