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
<div class="view-content">
{if $action eq 1 or $action eq 2 or $action eq 8} {* add, update or delete *}              
    {include file="CRM/Member/Form/Membership.tpl"}
{elseif $action eq 4}
    {include file="CRM/Member/Form/MembershipView.tpl"}
{elseif $action eq 32768}  {* renew *}
    {include file="CRM/Member/Form/MembershipRenewal.tpl"}
{elseif $action eq 16} {* Browse memberships for a contact *}
    {if $permission EQ 'edit'}{capture assign=newURL}{crmURL p="civicrm/contact/view/membership" q="reset=1&action=add&cid=`$contactId`&context=membership"}{/capture}{/if}

    {if $action ne 1 and $action ne 2 and $permission EQ 'edit'}
        <div id="help">
            {ts 1=$displayName}Current and inactive memberships for %1 are listed below.{/ts}
            {if $permission EQ 'edit'}{ts 1=$newURL}Click <a href='%1'>New Membership</a> to record a new membership.{/ts}{/if}
	    {if $newCredit}	
            {capture assign=newCreditURL}{crmURL p="civicrm/contact/view/membership" q="reset=1&action=add&cid=`$contactId`&context=membership&mode=live"}{/capture}
            {ts 1=$newCreditURL}Click <a href='%1'>Submit Credit Card Membership</a> to process a Membership on behalf of the member using their credit card.{/ts}
            {/if}
        </div>

        <div class="action-link">
            <a accesskey="N" href="{$newURL}" class="button"><span>&raquo; {ts}New Membership{/ts}</span></a>
            {if $accessContribution and $newCredit}
                <a accesskey="N" href="{$newCreditURL}" class="button"><span>&raquo; {ts}Submit Credit Card Membership{/ts}</span></a><br /><br />
            {else}
                <br/ ><br/ >	
        {/if}
        </div>
    {/if}
    {if NOT ($activeMembers or $inActiveMembers) and $action ne 2 and $action ne 1 and $action ne 8 and $action ne 4 and $action ne 32768}
       	<div class="messages status">
           <dl>
	     <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
               <dd>
                 {ts}No memberships have been recorded for this contact.{/ts}
               </dd>
           </dl>
      </div>
    {/if}
    {include file="CRM/common/jsortable.tpl"}
    {if $activeMembers}
    <div id="memberships">
        <div><label>{ts}Active Memberships{/ts}</label></div>
        {strip}
        <table id="active_membership" class="display">
            <thead>
            <tr>
                <th>{ts}Membership{/ts}</th>
                <th>{ts}Start Date{/ts}</th>
                <th>{ts}End Date{/ts}</th>
                <th>{ts}Status{/ts}</th>
                <th>{ts}Source{/ts}</th>
                <th></th>
            </tr>
            </thead>
            {foreach from=$activeMembers item=activeMember}
            <tr id="mem_{$activeMember.id}" class="{cycle values="odd-row,even-row"} {$activeMember.class}">
                <td>
                    {$activeMember.membership_type}
                    {if $activeMember.owner_membership_id}<br />({ts}by relationship{/ts}){/if}
                </td>
                <td>{$activeMember.start_date|crmDate}</td>
                <td>{$activeMember.end_date|crmDate}</td>
                <td>{$activeMember.status}</td>
                <td>{$activeMember.source}</td>
                <td>
                    {$activeMember.action|replace:'xx':$activeMember.id}
                    {if $activeMember.owner_membership_id}
                        &nbsp;|&nbsp;<a href="{crmURL p='civicrm/membership/view' q="reset=1&id=`$activeMember.owner_membership_id`&action=view&context=membership&selectedChild=member"}" title="{ts}View Primary member record{/ts}">{ts}View Primary{/ts}</a>
                    {/if}
                </td>
            </tr>
            {/foreach}
        </table>
        {/strip}
    </div>
    {/if}

    {if $inActiveMembers}
        <div id="inactive-memberships">
        <p></p>
        <div class="label font-red">{ts}Pending and Inactive Memberships{/ts}</div>
        {strip}
        <table id="pending_membership" class="display">
           <thead>
            <tr>
                <th>{ts}Membership{/ts}</th>
                <th>{ts}Start Date{/ts}</th>
                <th>{ts}End Date{/ts}</th>
                <th>{ts}Status{/ts}</th>
                <th>{ts}Source{/ts}</th>
                <th></th>
            </tr>
            </thead>
            {foreach from=$inActiveMembers item=inActiveMember}
            <tr id="mem_{$inActiveMember.id}" class="{cycle values="odd-row,even-row"} {$inActiveMember.class}">
                <td>{$inActiveMember.membership_type}</td>
                <td>{$inActiveMember.start_date|crmDate}</td>
                <td>{$inActiveMember.end_date|crmDate}</td>
                <td>{$inActiveMember.status}</td>
                <td>{$inActiveMember.source}</td>
                <td>{$inActiveMember.action|replace:'xx':$inActiveMember.id}</td>
            </tr>
            {/foreach}
        </table>
        {/strip}
        </div>
    {/if}

    {if $membershipTypes}
    <div class="solid-border-bottom">&nbsp;</div>
    <div id="membership-types">
        <div><label>{ts}Membership Types{/ts}</label></div>
        <div class="help">
            {ts}The following Membership Types are associated with this organization. Click <strong>Members</strong> for a listing of all contacts who have memberships of that type. Click <strong>Edit</strong> to modify the settings for that type.{/ts}
        <div class="form-item">
            {strip}
            <table id="membership_type" class="display">
            <thead>
            <tr>
                <th>{ts}Name{/ts}</th>
                <th>{ts}Period{/ts}</th>
                <th>{ts}Fixed Start{/ts}</th>		
                <th>{ts}Minimum Fee{/ts}</th>
                <th>{ts}Duration{/ts}</th>            
                <th>{ts}Visibility{/ts}</th>
                <th></th>
            </tr>
            </thead>
            {foreach from=$membershipTypes item=membershipType}
            <tr class="{cycle values="odd-row,even-row"} {$membershipType.class}">
                <td>{$membershipType.name}</td>
            <td>{$membershipType.period_type}</td>
            <td>{$membershipType.fixed_period_start_day}</td>
                <td>{$membershipType.minimum_fee}</td>
                <td>{$membershipType.duration_unit}</td>	        
                <td>{$membershipType.visibility}</td>
                <td>{$membershipType.action|replace:xx:$membershipType.id}</td>
            </tr>
            {/foreach}
            </table>
            {/strip}

        </div>
    </div>
    {/if}
{/if} {* End of $action eq 16 - browse memberships. *}
</div>
