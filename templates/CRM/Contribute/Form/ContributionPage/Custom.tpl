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
{* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
{include file="CRM/common/WizardHeader.tpl"}
<div id="help">
    <p>{ts}You may want to collect information from contributors beyond what is required to make a contribution. For example, you may want to inquire about volunteer availability and skills. Add any number of fields to your contribution form by selecting CiviCRM Profiles (collections of fields) to include at the beginning of the page, and/or at the bottom.{/ts}</p>
    {capture assign=crmURL}{crmURL p='civicrm/admin/uf/group' q="reset=1&action=browse"}{/capture}
{if $noProfile} 
<div class="form-item">
    <fieldset><legend>{ts}Include Profiles{/ts}</legend>
<div class="status message">
  <dl> 
  <dd>{ts 1=$crmURL 2=Profile}No Profile has been configured / enabled for your site. You need to first configure <a href="%1"><strong>&raquo; %2</a>(s).{/ts} {docURL page="Profiles Admin"}</dd>
  </dl>
</div>
{else}
    <p>{ts 1=$crmURL}You can use existing CiviCRM Profiles on your page - OR create profile(s) specifically for use in Online Contribution pages. Go to <a href='%1'>Administer CiviCRM Profiles</a> if you need to review, modify or create profiles (you can come back at any time to select or update the Profile(s) used for this page).{/ts}</p>
</div>
 
<div class="form-item">
    <fieldset><legend>{ts}Include Profiles{/ts}</legend>
    <dl>
    <dt>{$form.custom_pre_id.label}</dt><dd>{$form.custom_pre_id.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Include additional fields in this online contribution page by configuring and selecting a CiviCRM Profile to be included at the top of the page (immediately after the introductory message).{/ts}{help id="contrib-profile"}</dd>
    <dt>{$form.custom_post_id.label}</dt><dd>{$form.custom_post_id.html}</dd>
    <dt>&nbsp;</dt><dd class="description">{ts}Include additional fields in this online contribution page by configuring and selecting a CiviCRM Profile to be included at the bottom of the page.{/ts}</dd>
    </dl>
{/if}
    <div id="crm-submit-buttons">
        <dl><dt></dt><dd>{$form.buttons.html}</dd></dl>  
    </div>
    </fieldset>
</div>

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}
