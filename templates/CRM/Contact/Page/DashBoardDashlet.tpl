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
{include file="CRM/common/dashboard.tpl"}
{include file="CRM/common/openFlashChart.tpl"}
<a href="javascript:addDashlet( );" class="button show-add" style="margin-left: 6px;"><span>&raquo; {ts}Configure Your Dashboard{/ts}</span></a>
<a style="display:none;" href="{crmURL p="civicrm/dashboard" q="reset=1"}" class="button show-done" style="margin-left: 6px;"><span>&raquo; {ts}Done{/ts}</span></a>
<a style="float:right;" href="{crmURL p="civicrm/dashboard" q="reset=1&resetCache=1"}" class="button show-refresh" style="margin-left: 6px;"><span>&raquo; {ts}Refresh Dashboard Data{/ts}</span></a>
<div class="spacer"></div>

{* Welcome message appears when there are no active dashlets for the current user. *}
<div id="empty-message" class='hiddenElement'>
    <br />
    <div class="status" style="padding: 1em;">
        <div class="font-size12pt bold">{ts}Welcome to your Home Dashboard{/ts}</div>
        <div class="display-block">
            {ts}Your dashboard provides a one-screen view of the data that's most important to you. Graphical or tabular data is pulled from the reports you select,
            and is displayed in 'dashlets' (sections of the dashboard).{/ts} {help id="id-dash_welcome" file="CRM/Contact/Page/Dashboard.hlp"}
        </div>
    </div>
</div>

<div id="configure-dashlet" class='hiddenElement'></div>
<div id="civicrm-dashboard">
  <!-- You can put anything you like here.  jQuery.dashboard() will remove it. -->
  {ts}Javascript must be enabled in your browser in order to use the dashboard features.{/ts}
</div>
<div class="clear"></div>

{literal}
<script type="text/javascript">
  function addDashlet(  ) {
      var dataURL = {/literal}"{crmURL p='civicrm/dashlet' q='reset=1&snippet=1' h=0 }"{literal};

      cj.ajax({
         url: dataURL,
         success: function( content ) {
             cj("#civicrm-dashboard").hide( );
             cj('.show-add').hide( );
             cj('.show-refresh').hide( );
             cj('.show-done').show( );
             cj("#empty-message").hide( );
             cj("#configure-dashlet").show( ).html( content );
         }
      });
  }
        
</script>
{/literal}
