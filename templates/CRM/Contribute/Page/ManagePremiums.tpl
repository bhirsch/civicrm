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
{if $action eq 1 or $action eq 2 or $action eq 8 or $action eq 1024}
   {include file="CRM/Contribute/Form/ManagePremiums.tpl"}
{else}
    

{if $action ne 2}
{if $action ne 1 or $action ne 8}
<div id="help">
{capture assign=contribURL}{crmURL p='civicrm/admin/contribute' q="reset=1"}{/capture}
<p>{ts}CiviContribute allows you to configure any number of <strong>Premiums</strong> which can be offered to contributors as incentives / thank-you gifts. Premiums may be tangible items (i.e. a coffee mug or t-shirt), or they may be a membership or subscription with a pre-determined duration.{/ts}</p>
<p>{ts 1=$contribURL}Use this section to enter and update all premiums that you want to offer on any of your Online Contribution pages. Then you can assign one or more premiums to a specific Contribution page from <a href='%1'>Configure Online Contribution Pages</a> <strong>&raquo; Configure &raquo; Premiums</strong>.{/ts}</p>
</div>

{/if}
{if $rows}
<div id="ltype">
<p></p>
    {strip}
	{* handle enable/disable actions*}
 	{include file="CRM/common/enableDisable.tpl"}
	{include file="CRM/common/jsortable.tpl"}
        <table id="options" class="display">
          <thead>
           <tr>
            <th id="sortable">{ts}Name{/ts}</th>
            <th>{ts}SKU{/ts}</th>
            <th>{ts}Market Value{/ts}</th>
            <th>{ts}Min Contribution{/ts}</th>
            <th>{ts}Active?{/ts}</th>
            <th></th>
           </tr>
          </thead>
        {foreach from=$rows item=row}
	      <tr id="row_{$row.id}"class="{cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">        
	        <td>{$row.name}</td>	
	        <td>{$row.sku}</td>
                <td>{$row.price }</td>
	        <td>{$row.min_contribution}</td>
	        <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
	        <td id={$row.id}>{$row.action|replace:'xx':$row.id}</td>
          </tr>
        {/foreach}
        </table>
    {/strip}
    {if $action ne 1 and $action ne 2}
	    <div class="action-link">
    	<a href="{crmURL q="action=add&reset=1"}" id="newManagePremium" class="button"><span>&raquo; {ts}New Premium{/ts}</span></a>
        </div>
    {/if}
</div>
{else}
    {if $action ne 1 and $action ne 2}
    <div class="messages status">
    <dl>
        <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/></dt>
        {capture assign=crmURL}{crmURL p='civicrm/admin/contribute/managePremiums' q="action=add&reset=1"}{/capture}
        <dd>{ts 1=$crmURL}No premium products have been created for your site. You can <a href='%1'>add one</a>.{/ts}</dd>
        </dl>
    </div>  
    {/if}	  
{/if}
{/if}
{/if}