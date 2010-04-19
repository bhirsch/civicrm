{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing CiviCRM Menu *}
<div class="form-item">
<div class="crm-submit-buttons">
    {$form.buttons.html}
</div>
<fieldset><legend>{if $action eq 1}{ts}New Menu{/ts}{elseif $action eq 2}{ts}Edit Menu{/ts}{else}{ts}Delete Menu{/ts}{/if}</legend>
<table class="form-layout-compressed">
    <tr><td class="label">{$form.label.label}</td><td>{$form.label.html}</td></tr>
    <tr><td class="label">{$form.url.label}</td><td>{$form.url.html} {help id="id-menu_url" file="CRM/Admin/Form/Navigation.hlp"}</td></tr>
    <tr><td class="label">{$form.parent_id.label}</td><td>{$form.parent_id.html} {help id="id-parent" file="CRM/Admin/Form/Navigation.hlp"}</td></tr>
    <tr><td class="label">{$form.has_separator.label}</td><td>{$form.has_separator.html} {help id="id-has_separator" file="CRM/Admin/Form/Navigation.hlp"}</td></tr>
    <tr><td class="label">{$form.permission.label}<br />{help id="id-menu_permission" file="CRM/Admin/Form/Navigation.hlp"}</td><td>{$form.permission.html}</td></tr>
    <tr><td class="label">&nbsp;</td><td>{$form.permission_operator.html}&nbsp;{$form.permission_operator.label} {help id="id-permission_operator" file="CRM/Admin/Form/Navigation.hlp"}</td></tr>
    <tr><td class="label">{$form.is_active.label}</td><td>{$form.is_active.html}</td></tr>
</table>   
</fieldset>
<div class="crm-submit-buttons">
    {$form.buttons.html}
</div>
</div>