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
{* this template is used for web-based subscriptions to mailing list type groups  *}
<div class="form-item">
<fieldset>
{if $single}
    <div id="help">
        {ts}Enter your email address and click <strong>Subscribe</strong>. You will receive a confirmation request via email shortly. Your subscription will be activated after you respond to that email.{/ts}
    </div>
{else}
    <div id="help">
        {ts}Enter your email address and check the box next to each mailing list you want to join. Then click the <strong>Subscribe</strong> button. You will receive a confirmation request via email for each selected list. Activate your subscription to each list by responding to the corresponding confirmation email.{/ts}
    </div>
{/if}

<table class="form-layout-compressed">
    <tr><td style="width: 10%;">{$form.email.label}</td><td>{$form.email.html}</td></tr>
    <tr><td colspan="2">
        <div class="spacer"></div>

        {if ! $single} {* Show all public mailing list groups. Page was loaded w/o a specific group param (gid=N not in query string). *}
            <table summary="{ts}Group Listings.{/ts}" class="selector" style="width: auto;">
            {counter start=0 skip=1 print=false}
            {foreach from=$rows item=row}
            <tr id='rowid{$row.id}' class="{cycle values="odd-row,even-row"}">
                {assign var=cbName value=$row.checkbox}
                <td>{$form.$cbName.html}</td>
                <td><strong>{$row.title}</strong></td>
                <td>&nbsp;&nbsp;{$row.description}&nbsp;</td>
            </tr>
            {/foreach}  
            </table>
        {/if}
        </td>
    </tr>
    {include file='CRM/common/ReCAPTCHA.tpl'}
    <tr><td>&nbsp;</td><td>{$form.buttons.html}</td></tr>
</table>
</fieldset>
</div>
