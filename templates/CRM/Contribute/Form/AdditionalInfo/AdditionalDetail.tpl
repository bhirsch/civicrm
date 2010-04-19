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
{* this template is used for adding/editing Additional Detail *} 
{if $isOnline}{assign var=valueStyle value=" class='view-value'"}{else}{assign var=valueStyle value=""}{/if}
<div id="id-additionalDetail" class="section-shown">
  <fieldset>
    <table class="form-layout-compressed">
        <tr><td class="label" style="vertical-align:top;">{$form.note.label}</td><td>{$form.note.html}</td></tr>
        <tr><td class="label">{$form.non_deductible_amount.label}</td><td{$valueStyle}>{$form.non_deductible_amount.html|crmMoney:$currency}<br />
            <span class="description">{ts}Non-deductible portion of this contribution.{/ts}</span></td></tr>
        <tr><td class="label">{$form.fee_amount.label}</td><td{$valueStyle}>{$form.fee_amount.html|crmMoney:$currency}<br />
            <span class="description">{ts}Processing fee for this transaction (if applicable).{/ts}</span></td></tr>
        <tr><td class="label">{$form.net_amount.label}</td><td{$valueStyle}>{$form.net_amount.html|crmMoney:$currency}<br />
            <span class="description">{ts}Net value of the contribution (Total Amount minus Fee).{/ts}</span></td></tr>
        <tr><td class="label">{$form.invoice_id.label}</td><td{$valueStyle}>{$form.invoice_id.html}<br />
            <span class="description">{ts}Unique internal reference ID for this contribution.{/ts}</span></td></tr>
        <tr><td class="label">{$form.thankyou_date.label}</td><td>{include file="CRM/common/jcalendar.tpl" elementName=thankyou_date}<br />
            <span class="description">{ts}Date that a thank-you message was sent to the contributor.{/ts}</span></td></tr>
    </table>
  </fieldset>
</div>     
