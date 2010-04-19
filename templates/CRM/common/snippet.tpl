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
{if $smarty.get.snippet eq 4}
    {if $isForm}
        {include file="CRM/Form/default.tpl"}
    {else}
        {include file=$tplFile}
    {/if}
{else}
    {if $smarty.get.snippet eq 2}
    {include file="CRM/common/print.tpl"}
    {else}
    <div id="crm-container-snippet" bgColor="white">

    {* Check for Status message for the page (stored in session->getStatus). Status is cleared on retrieval. *}
    {if $session->getStatus(false)}
    <div class="messages status">
      <dl>
      <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
      <dd>{$session->getStatus(true)}</dd>
      </dl>
    </div>
    {/if}

    <!-- .tpl file invoked: {$tplFile}. Call via form.tpl if we have a form in the page. -->
    {if $isForm}
        {include file="CRM/Form/default.tpl"}
    {else}
        {include file=$tplFile}
    {/if}

    {include file="CRM/common/action.tpl" isSnippet = true}
    </div> {* end crm-container-snippet div *}
    {/if}
{/if}