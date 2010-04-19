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
    {if $action eq 0}
        <p>{ts}This is the first step in creating a new online Contribution Page. You can create one or more different Contribution Pages for different purposes, audiences, campaigns, etc. Each page can have it's own introductory message, pre-configured contribution amounts, custom data collection fields, etc.{/ts}</p>
        <p>{ts}In this step, you will configure the page title, contribution type (donation, campaign contribution, etc.), goal amount, and introductory message. You will be able to go back and modify all aspects of this page at any time after completing the setup wizard.{/ts}</p>
    {else}
        {ts}Use this form to edit the page title, contribution type (e.g. donation, campaign contribution, etc.), goal amount, introduction, and status (active/inactive) for this online contribution page.{/ts}
    {/if}
</div>
 
<div class="form-item">
    <fieldset><legend>{ts}Title and Settings{/ts}</legend>
	<table class="form-layout-compressed">

	<tr><td class="label">{$form.title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='title' id=$id}{/if}</td><td>{$form.title.html}<br/>
            <span class="description">{ts}This title will be displayed at the top of the page.<br />Please use only alphanumeric, spaces, hyphens and dashes for Title.{/ts}</td>
	</tr>
	<tr><td class="label">{$form.contribution_type_id.label}</td><td>{$form.contribution_type_id.html}<br />	
            <span class="description">{ts}Select the corresponding contribution type for contributions made using this page (e.g. donation, membership fee, etc.). You can add or modify available types using the <strong>Contribution Type</strong> option from the CiviCRM Administrator Control Panel.{/ts}</span></td>
	</tr>
	<tr><td>&nbsp;</td><td>{$form.is_organization.html} {$form.is_organization.label}</td></tr>
	<tr id="for_org_option">
        <td>&nbsp;</td>
        <td>
            <table class="form-layout-compressed">
            <tr id="for_org_text">
                <td class="label">{$form.for_organization.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='for_organization' id=$id}{/if}</td><td>{$form.for_organization.html}<br />
                    <span class="description">{ts}Text displayed next to the checkbox on the contribution form.{/ts}</span>
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>{$form.is_for_organization.html}<br />
                    <span class="description">{ts}Check 'Required' to force ALL users to contribute/signup on behalf of an organization.{/ts}</span>
                </td>
            </tr>
            </table>
        </td>
    </tr>
	<tr><td class ="label">{$form.intro_text.label}</td><td>{$form.intro_text.html}<br />
	    <span class="description">{ts}Enter content for the introductory message. This will be displayed below the page title. You may include HTML formatting tags. You can also include images, as long as they are already uploaded to a server - reference them using complete URLs.{/ts}</span></td>
	</tr>
	<tr><td class ="label">{$form.footer_text.label}</td><td>{$form.footer_text.html}<br />
	    <span class="description">{ts}If you want content displayed at the bottom of the contribution page, enter it here. You may include HTML formatting tags. You can also include images, as long as they are already uploaded to a server - reference them using complete URLs.{/ts}</span></td>
	</tr>
	<tr><td class ="label">{$form.goal_amount.label}</td><td>{$form.goal_amount.html}<br />
	    <span class="description">{ts}Enter an optional goal amount for this contribution page (e.g. for this 'campaign'). If you enable a contribution widget for this page, the widget will track progress against this goal. Otherwise, the goal will display as 'no limit'.{/ts}</span></td>
	</tr>
	<tr>
	    <td class ="label">{$form.start_date.label}</td>
	    <td>
	        {include file="CRM/common/jcalendar.tpl" elementName=start_date}
	    </td>    
    </tr>
	<tr>
	    <td class ="label">{$form.end_date.label}</td>
	    <td>
	        {include file="CRM/common/jcalendar.tpl" elementName=end_date}
	    </td>    
    </tr>
	<tr><td>&nbsp;</td><td>{$form.honor_block_is_active.html}{$form.honor_block_is_active.label}<br />
	    <span class="description">{ts}If you want to allow contributors to specify a person whom they are honoring with their gift, check this box. An optional Honoree section will be included in the form. Honoree information is automatically saved and linked with the contribution record.{/ts}</span></td>
	</tr>
</table>
<table class="form-layout-compressed" id="honor">
    	<tr><td class="label">{$form.honor_block_title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='honor_block_title' id=$id}{/if}</td><td>{$form.honor_block_title.html}<br />
	    <span class="description">{ts}Title for the Honoree section (e.g. &quot;Honoree Information&quot;).{/ts}</span></td>
	</tr>
	<tr>
    	    <td class="label">{$form.honor_block_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='honor_block_text' id=$id}{/if}</td><td>{$form.honor_block_text.html}<br />
    	    <span class="description">{ts}Optional explanatory text for the Honoree section (displayed above the Honoree fields).{/ts}</span></td>
	</tr>
</table>
<table class="form-layout-compressed">
		<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>{$form.is_active.html} {$form.is_active.label}<br />
	{if $id}
    		<span class="description">
        	{if $config->userFramework EQ 'Drupal'}
            	{ts}When your page is active, you can link people to the page by copying and pasting the following URL:{/ts}<br />
            	<strong>{crmURL a=true p='civicrm/contribute/transact' q="reset=1&id=`$id`"}</strong></dd>
        	{elseif $config->userFramework EQ 'Joomla'}
            	{ts 1=$title}When your page is active, create front-end links to the contribution page using the Menu Manager. Select <strong>CiviCRM &raquo; Contribution Pages</strong> and select <strong>%1</strong> for the contribution page.{/ts}
        	{/if}
		</span>
    	{/if}
	</td>
	</tr>    	
 	
    	{if $action ne 4}
        <tr id="crm-submit-buttons">
            <td></td><td>{$form.buttons.html}</td></tr>  
        
   	 {else}
        <tr id="crm-done-button">
            <td></td><td>{$form.done.html}</td></tr>  
	 {/if}

	</table>
    </fieldset>
</div>

{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="is_organization"
    trigger_value       = 1
    target_element_id   ="for_org_text" 
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}

{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="is_organization"
    trigger_value       = 1
    target_element_id   ="for_org_option" 
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}
<script type="text/javascript">
 showHonor();
 {literal}
     function showHonor() {
        var checkbox = document.getElementsByName("honor_block_is_active");
        if (checkbox[0].checked) {
            document.getElementById("honor").style.display = "block";
        } else {
            document.getElementById("honor").style.display = "none";
        }  
     } 
 {/literal} 
</script>

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}

