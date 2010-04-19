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
{if ! $trigger}
  {assign var=trigger value=trigger}
{/if}

{literal}
<script type="text/javascript">
    var obj = new Date();
    var currentYear = obj.getFullYear();
{/literal}
{if $offset}
    var startYear = currentYear - {$offset};
    var endYear   = currentYear + {$offset};
{else}
    var startYear = {$startDate};
    var endYear   = {$endDate};
{/if}

{literal}
    Calendar.setup(
      {
{/literal}
{if !$doTime}
         dateField   : "{$dateVar}[d]",
         monthField  : "{$dateVar}[M]",
         yearField   : "{$dateVar}[Y]",
{elseif $doTime}
         dateField   : "{$dateVar}[d]",
         monthField  : "{$dateVar}[M]",
         yearField   : "{$dateVar}[Y]",
       {if $ampm}
         hourField   : "{$dateVar}[{$config->datetimeformatHourVar}]",
         minuteField : "{$dateVar}[i]",
         ampmField   : "{$dateVar}[A]",       
         showsTime   : true,
         timeFormat  : 12,
        {else}
         hourField   : "{$dateVar}[H]",
         minuteField : "{$dateVar}[i]",
         ampmField   : false,
         showsTime   : true,
         timeFormat  : 24,
        {/if}
{/if}
         range       : [startYear, endYear],
         button      : "{$trigger}"
{literal}
      }
    );
</script>
{/literal}


