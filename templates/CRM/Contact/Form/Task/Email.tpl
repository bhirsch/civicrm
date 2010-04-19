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
<div class="form-item">
<dl>{$form.buttons.html}</dl>
<fieldset>
{if $suppressedEmails > 0}
    <div class="status">
        <p>{ts count=$suppressedEmails plural='Email will NOT be sent to %count contacts - (no email address on file, or communication preferences specify DO NOT EMAIL, or contact is deceased).'}Email will NOT be sent to %count contact - (no email address on file, or communication preferences specify DO NOT EMAIL, or contact is deceased).{/ts}</p>
    </div>
{/if}
<table class="form-layout-compressed">
<tr>
    <td class="label">{$form.fromEmailAddress.label}</td><td>{$form.fromEmailAddress.html} {help id ="id-from_email" file="CRM/Contact/Form/Task/Email.hlp"}</td>
</tr>
<tr>
    <td class="label">{if $single eq false}{ts}Recipient(s){/ts}{else}{$form.to.label}{/if}</td>
    <td>{$form.to.html}{if $noEmails eq true}&nbsp;&nbsp;{$form.emailAddress.html}{/if}
    <div class="spacer"></div>
    <span class="bold"><a href="#" id="addcc">{ts}Add CC{/ts}</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="#" id="addbcc">{ts}Add BCC{/ts}</a></span></td>
</tr>
<tr id="cc" {if ! $form.cc_id.value}style="display:none;"{/if}><td class="label">{$form.cc_id.label}</td><td>{$form.cc_id.html}</td></tr>
<tr id="bcc" {if ! $form.bcc_id.value}style="display:none;"{/if}><td class="label">{$form.bcc_id.label}</td><td>{$form.bcc_id.html}</td></tr>
<tr>
    <td class="label">{$form.subject.label}</td>
    <td>
        {$form.subject.html|crmReplace:class:huge}&nbsp;
        <a href="#" onClick="return showToken('Subject', 3);">{$form.token3.label}</a>
	    {help id="id-token-subject" file="CRM/Contact/Form/Task/Email.hlp"}
        <div id='tokenSubject' style="display:none">
	      <input style="border:1px solid #999999;" type="text" id="filter3" size="20" name="filter3" onkeyup="filter(this, 3)"/><br />
	      <span class="description">{ts}Begin typing to filter list of tokens{/ts}</span><br/>
	      {$form.token3.html}
        </div>
    </td>
</tr>
</table>

{include file="CRM/Contact/Form/Task/EmailCommon.tpl"}

<div class="spacer"> </div>

<dl>
{if $single eq false}
    <dt></dt><dd>{include file="CRM/Contact/Form/Task.tpl"}</dd>
{/if}
{if $suppressedEmails > 0}
    <dt></dt><dd>{ts count=$suppressedEmails plural='Email will NOT be sent to %count contacts.'}Email will NOT be sent to %count contact.{/ts}</dd>
{/if}
</dl>
</fieldset>
<dl>{$form.buttons.html}</dl>
</div>
<script type="text/javascript">
var toContact = ccContact = bccContact = '';

{if $toContact}
    toContact  = {$toContact};
{/if}

{if $ccContact}
    ccContact  = {$ccContact};
{/if}

{if $bccContact}
    bccContact = {$bccContact};
{/if}

{literal}
cj('#addcc').toggle( function() { cj(this).text('Remove CC');
                                  cj('tr#cc').show().find('ul').find('input').focus();
                   },function() { cj(this).text('Add CC');cj('#cc_id').val('');
                                  cj('tr#cc ul li:not(:last)').remove();cj('#cc').hide();
});
cj('#addbcc').toggle( function() { cj(this).text('Remove BCC');
                                   cj('tr#bcc').show().find('ul').find('input').focus();
                    },function() { cj(this).text('Add BCC');cj('#bcc_id').val('');
                                   cj('tr#bcc ul li:not(:last)').remove();cj('#bcc').hide();
});

eval( 'tokenClass = { tokenList: "token-input-list-facebook", token: "token-input-token-facebook", tokenDelete: "token-input-delete-token-facebook", selectedToken: "token-input-selected-token-facebook", highlightedToken: "token-input-highlighted-token-facebook", dropdown: "token-input-dropdown-facebook", dropdownItem: "token-input-dropdown-item-facebook", dropdownItem2: "token-input-dropdown-item2-facebook", selectedDropdownItem: "token-input-selected-dropdown-item-facebook", inputToken: "token-input-input-token-facebook" } ');

var hintText = "{/literal}{ts}Type in a partial or complete name or email address of an existing contact.{/ts}{literal}";
var sourceDataUrl = "{/literal}{crmURL p='civicrm/ajax/checkemail' h=0 }{literal}";
var toDataUrl     = "{/literal}{crmURL p='civicrm/ajax/checkemail' q='id=1' h=0 }{literal}";

cj( "#to"     ).tokenInput( toDataUrl, { prePopulate: toContact, classes: tokenClass, hintText: hintText });
cj( "#cc_id"  ).tokenInput( sourceDataUrl, { prePopulate: ccContact, classes: tokenClass, hintText: hintText });
cj( "#bcc_id" ).tokenInput( sourceDataUrl, { prePopulate: bccContact, classes: tokenClass, hintText: hintText });
cj( 'ul.token-input-list-facebook, div.token-input-dropdown-facebook' ).css( 'width', '450px' );
</script>
{/literal}
{include file="CRM/common/formNavigate.tpl"}
