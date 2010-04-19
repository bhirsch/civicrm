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
{if $action eq 1 or $action eq 2 or $action eq 4}
    {include file="CRM/Price/Form/Set.tpl"}
{elseif $action eq 1024}
    {include file="CRM/Price/Form/Preview.tpl"}
{elseif $action eq 8 and !$usedBy}
    {include file="CRM/Price/Form/DeleteSet.tpl"}
{else}
    <div id="help">
        {ts}Price sets allow you to set up multiple event registration options with associated fees (e.g. pre-conference workshops, additional meals, etc.). Configure Price Sets for events which need more than a single set of fee levels.{/ts}
    </div>

    {if $usedBy}
    <div class='spacer'></div>
    <div id="price_set_used_by" class="messages status">
      <dl>
      <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>      
      <dd>
        {if $action eq 8}
            {ts 1=$usedPriceSetTitle}Unable to delete the '%1' price set - it is currently in use by one or more active events or contribution pages or contributions.{/ts}
        {/if}<br />
        
	{if $usedBy.civicrm_event or $usedBy.civicrm_contribution_page} 
            {include file="CRM/Price/Page/table.tpl"} 
        {/if}
	
      </dd>
      </dl>
    </div>
    {/if}

    {if $rows}
    <div id="price_set">
    <p></p>
        {strip}
	{* handle enable/disable actions*}
 	{include file="CRM/common/enableDisable.tpl"}
	{include file="CRM/common/jsortable.tpl"}
        <table id="price_set" class="display">
        <thead>
        <tr>
            <th id="sortable">{ts}Set Title{/ts}</th>
            <th id="nosort">{ts}Used For{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th></th>
        </tr>
        </thead>
        {foreach from=$rows item=row}
	<tr id="row_{$row.id}"class="{cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
            <td>{$row.title}</td>
	    <td>{$row.extends}</td>
	    <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </table>
        
        {if NOT ($action eq 1 or $action eq 2) }
        <p></p>
        <div class="action-link">
        <a href="{crmURL p='civicrm/admin/price' q="action=add&reset=1"}" id="newPriceSet" class="button"><span>&raquo;  {ts}New Set of Price Fields{/ts}</span></a>
        </div>
        {/if}

        {/strip}
    </div>
    {else}
       {if $action ne 1} {* When we are adding an item, we should not display this message *}
       <div class="messages status">
       <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/> &nbsp;
         {capture assign=crmURL}{crmURL p='civicrm/admin/price' q='action=add&reset=1'}{/capture}
         {ts 1=$crmURL}No price sets have been created yet. You can <a href='%1'>add one</a>.{/ts}
       </div>
       {/if}
    {/if}
{/if}
