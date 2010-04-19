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
{capture assign=newPageURL}{crmURL q='action=add&reset=1'}{/capture}
<div id="help">
    {ts}CiviContribute allows you to create and maintain any number of Online Contribution Pages. You can create different pages for different programs or campaigns - and customize text, amounts, types of information collected from contributors, etc.{/ts} {help id="id-intro"}
</div>

{include file="CRM/Contribute/Form/SearchContribution.tpl"}  
{if NOT ($action eq 1 or $action eq 2) }
    <table class="form-layout-compressed">
    <tr>
        <td><a href="{$newPageURL}" class="button"><span>&raquo; {ts}New Contribution Page{/ts}</span></a></td>
        <td style="vertical-align: top"><a href="{crmURL p="civicrm/admin/pcp" q="reset=1"}">&raquo; {ts}Manage Personal Campaign Pages{/ts}</a></td>
    </tr>
    </table>
{/if}

{if $rows}
    <div id="configure_contribution_page">
        {strip}
        
        {include file="CRM/common/pager.tpl" location="top"}
        {include file="CRM/common/pagerAToZ.tpl"}
        {* handle enable/disable actions *}
        {include file="CRM/common/enableDisable.tpl"}
        {include file="CRM/common/jsortable.tpl"}
        <table id="options" class="display">
          <thead>
          <tr>
            <th id="sortable">{ts}Title{/ts}</th>
            <th>{ts}ID{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th></th>
          </tr>
          </thead>
          {foreach from=$rows item=row}
        <tr id="row_{$row.id}" class="{cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
            <td>
               <strong>{$row.title}</strong>
            </td>
            <td>{$row.id}</td>
            <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </table>
        
        {/strip}
    </div>
{else}
    {if $isSearch eq 1}
    <div class="status messages">
        <dl>
            <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/></dt>
            {capture assign=browseURL}{crmURL p='civicrm/contribute/manage' q="reset=1"}{/capture}
            <dd>
                {ts}No available Contribution Pages match your search criteria. Suggestions:{/ts}
                <div class="spacer"></div>
                <ul>
                <li>{ts}Check your spelling.{/ts}</li>
                <li>{ts}Try a different spelling or use fewer letters.{/ts}</li>
                <li>{ts}Make sure you have enough privileges in the access control system.{/ts}</li>
                </ul>
                {ts 1=$browseURL}Or you can <a href='%1'>browse all available Contribution Pages</a>.{/ts}
            </dd>
        </dl>
    </div>
    {else}
    <div class="messages status">
        <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /> &nbsp;
        {ts 1=$newPageURL}No contribution pages have been created yet. Click <a accesskey="N" href='%1'>here</a> to create a new contribution page using the step-by-step wizard.{/ts}
    </div>
    {/if}
{/if}