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
<h2>{$title}</h2>                                
<div class="messages status">
    <dl>
    {if $is_active}
        <dt><img src="{$config->resourceBase}i/traffic_green.gif" alt="{ts}status{/ts}"/></dt>
        <dd><p><a href="{crmURL p='civicrm/contribute/transact' q="reset=1&id=`$id`"}">&raquo; {ts}Go to this LIVE Online Contribution page{/ts}</a></p>
        {if $config->userFramework EQ 'Drupal'}
            <p>{ts}Create links to this contribution page by copying and pasting the following URL into any web page.{/ts}:<br />
            <a href="{crmURL p='civicrm/contribute/transact' q="reset=1&id=`$id`"}">{crmURL a=true p='civicrm/contribute/transact' q="reset=1&id=`$id`"}</a>
        {elseif $config->userFramework EQ 'Joomla'}
            {ts 1=$id}Create front-end links to this contribution page using the Menu Manager. Select <strong>Online Contribution</strong> and enter <strong>%1</strong> for the Contribution id.{/ts}
        {/if}
        </dd>
    {else}
        <dt><img src="{$config->resourceBase}i/traffic_red.gif" alt="{ts}status{/ts}"/></dt>
        <dd><p>{ts}This page is currently <strong>inactive</strong> (not accessible to visitors).{/ts}</p>
        {capture assign=crmURL}{crmURL q="reset=1&action=update&id=`$id`&subPage=Settings"}{/capture}
        <p>{ts 1=$crmURL}When you are ready to make this page live, click <a href='%1'>Title and Settings</a> and update the <strong>Active?</strong> checkbox.{/ts}</p></dd>
    {/if}
    </dl>
</div>

<div id="help">
    {capture assign=docLink}{docURL page="CiviContribute Admin" text="CiviContribute Administration Documentation"}{/capture}
    {ts 1=$docLink}Use the links below to update features and content for this Online Contribution Page, as well as to run through the contribution process in <strong>test mode</strong>. Refer to the %1 for more information.{/ts}
</div>
<table class="report"> 
<tr>
    <td class="nowrap"><a href="{crmURL q="reset=1&action=update&id=`$id`&subPage=Settings"}" id="idTitleAndSettings">&raquo; {ts}Title and Settings{/ts}</a></td>
    <td>{ts}Set page title and describe your cause or campaign. Select contribution type (donation, campaign contribution, etc.), and set optional fund-raising goal and campaign start and end dates. Enable honoree features and allow individuals to contribute on behalf of an organization. Enable or disable this page.{/ts}</td>
</tr>
<tr>
    <td class="nowrap"><a href="{crmURL q="reset=1&action=update&id=`$id`&subPage=Amount"}" id="idContributionAmounts">&raquo; {ts}Contribution Amounts{/ts}</a></td>
    <td>
        {ts}Select the payment processor to be used for this contribution page.{/ts}
        {ts}Configure contribution amount options and labels, minimum and maximum amounts.{/ts}
        {ts}Enable pledges OR recurring contributions (recurring contributions are not supported for all payment processors).{/ts}
        {ts}Give contributors the option to 'pay later' (e.g. mail in a check, call in a credit card, etc.).{/ts}
    </td>
</tr>
{if $CiviMember}
<tr>
    <td class="nowrap"><a href="{crmURL q="reset=1&action=update&id=`$id`&subPage=Membership"}" id="idMembershipSettings">&raquo; {ts}Membership Settings{/ts}</a></td>
    <td>{ts}Configure membership sign-up and renewal options.{/ts}</td>
</tr>
{/if}
<tr>
    <td class="nowrap"><a href="{crmURL q="reset=1&action=update&id=`$id`&subPage=Custom"}" id="idCustomPageElements">&raquo; {ts}Include Profiles{/ts}</a></td>
    <td>{ts}Collect additional information from contributors by selecting CiviCRM Profile(s) to include in this contribution page.{/ts}</td>
</tr>
<tr>
    <td class="nowrap"><a href="{crmURL q="reset=1&action=update&id=`$id`&subPage=ThankYou"}" id="idThank-youandReceipting">&raquo; {ts}Thank-you and Receipting{/ts}</a></td>
    <td>{ts}Edit thank-you page contents and receipting features.{/ts}</td>
</tr>
<tr>
    <td class="nowrap"><a href="{crmURL q="reset=1&action=update&id=`$id`&subPage=Friend"}" id="idFriend">&raquo; {ts}Tell a Friend{/ts}</a></td>
    <td>{ts}Make it easy for contributors to spread the word to friends and colleagues.{/ts}</td>
</tr>
{capture assign=pcpAdminURL}{crmURL p="civicrm/admin/pcp" q="reset=1"}{/capture}
<tr>
    <td class="nowrap"><a href="{crmURL q="reset=1&action=update&id=`$id`&subPage=PCP"}" id="idPcp">&raquo; {ts}Personal Campaign Pages{/ts}</a></td>
    <td>{ts 1=$pcpAdminURL}Allow constituents to create their own personal fundraising pages and drive traffic to this contribution page. (<a href="%1">Or you can view and manage existing Personal Campaign Pages.</a>){/ts}</td>
</tr>
<tr>
    <td class="nowrap"><a href="{crmURL q="reset=1&action=update&id=`$id`&subPage=Widget"}" id="idWidget">&raquo; {ts}Contribution Widget{/ts}</a></td>
    <td>{ts}Create a contribution widget which you and your supporters can embed in websites and blogs.{/ts}</td>
</tr>
<tr>
    <td class="nowrap"><a href="{crmURL q="reset=1&action=update&id=`$id`&subPage=Premium"}" id="idPremiums">&raquo; {ts}Premiums{/ts}</a></td>
    <td>{ts}Enable a Premiums section (incentives / thank-you gifts) for this page, and configure premiums offered to contributors.{/ts}</td>
</tr>
<tr>
    <td class="nowrap"><a href="{crmURL p='civicrm/contribute/transact' q="reset=1&action=preview&id=`$id`"}" id="idTest-drive">&raquo; {ts}Test-drive{/ts}</a></td>
    <td>{ts}Test-drive the entire contribution process - including custom fields, confirmation, thank-you page, and receipting. Transactions will be directed to your payment processor's test server. <strong>No live financial transactions will be submitted. However, a contact record will be created or updated and a test contribution record will be saved to the database. Use obvious test contact names so you can review and delete these records as needed. Test contributions are not visible on the Contributions tab, but can be viewed by searching for 'Test Contributions' in the CiviContribute search form.</strong>{/ts}</td>
</tr>
{if $is_active}
<tr>
    <td class="nowrap"><a href="{crmURL p='civicrm/contribute/transact' q="reset=1&id=`$id`"}" id="idLive">&raquo; {ts}Live Contribution Page{/ts}</a></td>
    <td>{ts}Review your customized <strong>LIVE</strong> online contribution page here.{/ts}
        {if $config->userFramework EQ 'Drupal'}
            {ts}Use the following URL in links and buttons on any website to send visitors to this live page{/ts}:<br />
            <strong>{crmURL a=true p='civicrm/contribute/transact' q="reset=1&id=`$id`"}</strong>
        {elseif $config->userFramework EQ 'Joomla'}
            {ts 1=$id}When your page is active, create front-end links to the contribution page using the Menu Manager. Select <strong>Online Contribution</strong> and enter <strong>%1</strong> for the Contribution id.{/ts}
        {/if}
    </td>
</tr>
{/if}

</table>
