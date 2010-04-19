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
{* this template used to build location block *}
{if $addBlock}
{include file="CRM/Contact/Form/Edit/$blockName.tpl"}
{else}
<div class="crm-submit-buttons">
    {$form.buttons.html}
</div>
<fieldset>
    <div id="help">
        {ts}Use this form to configure the location and optional contact information for the event. This information will be displayed on the Event Information page. It will also be included in online registration pages and confirmation emails if these features are enabled.{/ts}
    </div>
    {if $locEvents}
    	<table class="form-layout-compressed">
			<tr id="optionType">
				<td class="labels">
					{$form.location_option.label}
				</td>
				{foreach from=$form.location_option key=key item =item}
					{if $key|is_numeric}
						<td class="fields"><strong>{$item.html}</strong></td>
				    {/if}
                {/foreach} 
			 </tr>
			<tr id="existingLoc">
				<td class="labels">
					{$form.loc_event_id.label}
				</td>
				<td class="fields" colspan="2">
					{$form.loc_event_id.html|crmReplace:class:huge}
				</td>
			</tr>
			<tr>
				<td id="locUsedMsg" colspan="3">
				{assign var=locUsedMsgTxt value="<strong>Note:</strong> This location is used by multiple events. Modifying location information will change values for all events."}
				</td>
			</tr>
			
		</table>
    {/if}	

    

    <div id="newLocation">
	<fieldset><legend>Address</legend>
		{* Display the address block *}
		{include file="CRM/Contact/Form/Edit/Address.tpl"} 
	</fieldset>
	<table class="form-layout-compressed">
    {* Display the email block(s) *}  
    {include file="CRM/Contact/Form/Edit/Email.tpl"}

    {* Display the phone block(s) *}
    {include file="CRM/Contact/Form/Edit/Phone.tpl"} 
    </table>
	 <table class="form-layout-compressed">
	 <tr>
		<td colspan="2">{$form.is_show_location.label}</td>
		<td colspan="2">
			{$form.is_show_location.html}<br />
			<span class="description">{ts}Uncheck this box if you want to HIDE the event Address on Event Information and Registration pages as well as on email confirmations.{/ts}
		</td>
	</tr>
	</table>
</fieldset>
<div class="crm-submit-buttons">
    {$form.buttons.html}
</div>
    
{* Include Javascript to hide and display the appropriate blocks as directed by the php code *} 
{*include file="CRM/common/showHide.tpl"*}
{if $locEvents}
<script type="text/javascript">    
{literal}
var locUsedMsgTxt = {/literal}"{$locUsedMsgTxt}"{literal};
var locBlockURL   = {/literal}"{crmURL p='civicrm/ajax/locBlock' q='reset=1' h=0}"{literal};
var locBlockId    = {/literal}"{$form.loc_event_id.value.0}"{literal};

if ( {/literal}"{$locUsed}"{literal} ) {
   displayMessage( true );
}

cj(document).ready(function() {
  cj('#loc_event_id').change(function() {
    cj.ajax({
      url: locBlockURL, 
      type: 'POST',
      data: {'lbid': cj(this).val()},
      dataType: 'json',
      success: function(data) {
        var selectLocBlockId = cj('#loc_event_id').val();
        for(i in data) {
          if ( i == 'count_loc_used' ) {
            if ( ((selectLocBlockId == locBlockId) && data['count_loc_used'] > 1) || 
                 ((selectLocBlockId != locBlockId) && data['count_loc_used'] > 0) ) {
              displayMessage( true );
            } else {
              displayMessage( false );
            }
          } else {
            cj('#'+i).val(data[i]);
          }
        }
      }
    });
    return false;
  });
});

function displayMessage( set ) {
   cj(document).ready(function() {
     if ( set ) {
       cj('#locUsedMsg').html( locUsedMsgTxt ).addClass('status');
     } else {
       cj('#locUsedMsg').html( ' ' ).removeClass('status');
     }
   });
}

function showLocFields( ) {
   var createNew = document.getElementsByName("location_option")[0].checked;
   var useExisting = document.getElementsByName("location_option")[1].checked;
   if ( createNew ) {
     cj('#existingLoc').hide();
     displayMessage(false);
   } else if ( useExisting ) {
     cj('#existingLoc').show();
   }
}

showLocFields( );
{/literal}
</script>
{/if}

{* include common additional blocks tpl *}
{include file="CRM/common/additionalBlocks.tpl"}

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}

{/if} {* add block if end*}