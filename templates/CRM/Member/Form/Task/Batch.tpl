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
<div class="batch-update form-item">
<fieldset>
<div id="help">
    {ts}Update field values for each member as needed. Click <strong>Update Memberships</strong> below to save all your changes. To set a field to the same value for ALL rows, enter that value for the first member and then click the <strong>Copy icon</strong> (next to the column title).{/ts}
</div>
    <legend>{$profileTitle}</legend>
         <table>
	  <thead class="sticky">
            <tr class="columnheader">
             {foreach from=$readOnlyFields item=fTitle key=fName}
	        <th>{$fTitle}</th>
	     {/foreach}

             {foreach from=$fields item=field key=fieldName}
                {if strpos( $field.name, '_date' ) !== false ||
                    (substr( $field.name, 0, 7 ) == 'custom_' && $field.data_type == 'Date')}
                  <th><img  src="{$config->resourceBase}i/copy.png" alt="{ts 1=$field.title}Click to copy %1 from row one to all rows.{/ts}" onclick="copyValuesDate('{$field.name}')" class="action-icon" title="{ts}Click here to copy the value in row one to ALL rows.{/ts}" />{$field.title}</th>
                {else}
                  <th><img  src="{$config->resourceBase}i/copy.png" alt="{ts 1=$field.title}Click to copy %1 from row one to all rows.{/ts}" onclick="copyValues('{$field.name}')" class="action-icon" title="{ts}Click here to copy the value in row one to ALL rows.{/ts}" />{$field.title}</th>
                 {/if}
             {/foreach}
            </tr>
          </thead>
            {foreach from=$componentIds item=cid}
             <tr class="{cycle values="odd-row,even-row"}">

	      {foreach from=$readOnlyFields item=fTitle key=fName}
	         <td>{$contactDetails.$cid.$fName}</td>
	      {/foreach}

              {foreach from=$fields item=field key=fieldName}
                {assign var=n value=$field.name}
                {if ( $fields.$n.data_type eq 'Date') }
                   <td class="compressed">{include file="CRM/common/jcalendar.tpl" elementName=$n elementIndex=$cid batchUpdate=1}</td>
                {else}
                	<td class="compressed">{$form.field.$cid.$n.html}</td> 
                {/if}
              {/foreach}
             </tr>
            {/foreach}
           </tr>
         </table>
        <dl>
            <dt></dt><dd>{if $fields}{$form._qf_Batch_refresh.html}{/if} &nbsp; {$form.buttons.html}</dd>
        </dl>
</fieldset>
</div>

{*include batch copy js js file*}
{include file="CRM/common/batchCopy.tpl"}
