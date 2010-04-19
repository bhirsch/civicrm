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
{if $preview_type eq 'group'}
    {capture assign=infoMessage}{ts}Preview of the custom data group (fieldset) as it will be displayed within an edit form.{/ts}{/capture}
    {capture name=legend}
        {foreach from=$groupTree item=fieldName}
          {$fieldName.title}
        {/foreach}
    {/capture}
{else}
    {capture assign=infoMessage}{ts}Preview of this field as it will be displayed in an edit form.{/ts}{/capture}
{/if}
{include file="CRM/common/info.tpl"}
<div class="form-item">
{strip}

{foreach from=$groupTree item=cd_edit key=group_id}
    <p></p>
    <fieldset>{if $preview_type eq 'group'}<legend>{$smarty.capture.legend}</legend>{/if}
    {if $cd_edit.help_pre}<div class="messages help">{$cd_edit.help_pre}</div><br />{/if}
    <dl class="html-adjust">
    {foreach from=$cd_edit.fields item=element key=field_id}
      {if $element.is_view eq 0}{* fix for CRM-2699 *}
        {if $element.help_pre}
            <dt>&nbsp;</dt><dd class="description">{$element.help_pre}</dd>
        {/if}
	{if $element.options_per_line }
        {*assign var="element_name" value=$element.custom_group_id|cat:_|cat:$field_id|cat:_|cat:$element.name*}
        {assign var="element_name" value=$element.element_name}      
        <dt>{$form.$element_name.label}</dt>
        <dd>
            {assign var="count" value="1"}
                <table class="form-layout-compressed">
                 <tr>
                   {* sort by fails for option per line. Added a variable to iterate through the element array*}
                   {assign var="index" value="1"}
                   {foreach name=outer key=key item=item from=$form.$element_name}
                        {if $index < 10}
                            {assign var="index" value=`$index+1`}
                        {else}
                          <td class="labels font-light">{$form.$element_name.$key.html}</td>
                              {if $count == $element.options_per_line}
                                {assign var="count" value="1"}
                           </tr>
                           <tr>
                            {else}
                                {assign var="count" value=`$count+1`}
                            {/if}
                         {/if}
                    {/foreach}  
		    {if $element.html_type eq 'Radio'}
	    	    <td>&nbsp;&nbsp;(&nbsp;<a href="#" title="unselect" onclick="unselectRadio('{$element_name}', '{$form.formName}'); return false;" >{ts}unselect{/ts}</a>&nbsp;)</td> 
	            {/if}
                </tr>                  
            </table>
        </dd>
        {if $element.help_post}
            <dt>&nbsp;</dt><dd class="description">{$element.help_post}</dd>
        {/if}
	{else}
        {assign var="name" value=`$element.name`} 
        {*assign var="element_name" value=$group_id|cat:_|cat:$field_id|cat:_|cat:$element.name*}
        {assign var="element_name" value=$element.element_name}  
        <dt>{$form.$element_name.label}</dt>
	    <dd>
	    {if $element.data_type neq 'Date'}
            {$form.$element_name.html}&nbsp;
        {elseif $element.skip_calendar NEQ true }
            {include file="CRM/common/jcalendar.tpl" elementName=$element_name}
        {/if}
	    {if $element.html_type eq 'Radio'}
	    	&nbsp;(&nbsp;<a href="#" title="unselect" onclick="unselectRadio('{$element_name}', '{$form.formName}'); return false;" >{ts}unselect{/ts}</a>&nbsp;)
	    {elseif $element.html_type eq 'Autocomplete-Select'}
	        {include file="CRM/Custom/Form/AutoComplete.tpl"}
        {/if}
        </dd>
        {if $element.help_post}
            <dt>&nbsp;</dt><dd class="description">{$element.help_post}</dd>
        {/if}
	{/if}
     {/if}
    {/foreach}
    </dl>
    {if $cd_edit.help_post}<br /><div class="messages help">{$cd_edit.help_post}</div>{/if}
    </fieldset>
{/foreach}
{/strip}

<dl>
  <dt></dt><dd>{$form.buttons.html}</dd>
</dl>
</div>

