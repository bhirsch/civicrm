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
{* Import Wizard - Data Mapping table used by MapFields.tpl and Preview.tpl *}
 <div id="map-field">
    {strip}
    <table class="selector">
    {if $loadedMapping}
        <tr class="columnheader-dark"><th colspan="4">{ts 1=$savedName}Saved Field Mapping: %1{/ts}</td></tr>
    {/if}
        <tr class="columnheader">
	    {if $showColNames}	
	        {assign var="totalRowsDisplay" value=$rowDisplayCount+1}
	    {else}	
	        {assign var="totalRowsDisplay" value=$rowDisplayCount}
	    {/if}	
            {section name=rows loop=$totalRowsDisplay}
                { if $smarty.section.rows.iteration == 1 and $showColNames}
                  <th>{ts}Column Names{/ts}</th>
                {elseif $showColNames}
                  <th>{ts 1=$smarty.section.rows.iteration-1}Import Data (row %1){/ts}</th>
		{else}
		  <th>{ts 1=$smarty.section.rows.iteration}Import Data (row %1){/ts}</th>
                {/if}
            {/section}
            
            <th>{ts}Matching CiviCRM Field{/ts}</th>
        </tr>
        
        {*Loop on columns parsed from the import data rows*}
        {section name=cols loop=$columnCount}
            {assign var="i" value=$smarty.section.cols.index}
            <tr style="border: 1px solid #DDDDDD;">

                {if $showColNames}        
                    <td class="even-row labels">{$columnNames[$i]}</td>
                {/if}

                {section name=rows loop=$rowDisplayCount}
                    {assign var="j" value=$smarty.section.rows.index}
                    <td class="odd-row">{$dataValues[$j][$i]}</td>
                {/section}

                {* Display mapper <select> field for 'Map Fields', and mapper value for 'Preview' *}
                <td class="form-item even-row{if $wizard.currentStepName == 'Preview'} labels{/if}">
                    {if $wizard.currentStepName == 'Preview'}
            			{if $relatedContactDetails && $relatedContactDetails[$i] != ''}
                            {$mapper[$i]} - {$relatedContactDetails[$i]}
                            
                            {if $relatedContactLocType && $relatedContactLocType[$i] != ''}
	                            - {$relatedContactLocType[$i]}
                			{/if}

                            {if $relatedContactPhoneType && $relatedContactPhoneType[$i] != ''}
	                            - {$relatedContactPhoneType[$i]}
                			{/if}
                            
                            {* append IM Service Provider type for related contact *}
                            {if  $relatedContactImProvider && $relatedContactImProvider[$i] != ''}
                                - {$relatedContactImProvider[$i]}
                            {/if}
                                       
			            {else}                        
			                {if $locations[$i]}
                                {$locations[$i]} - 
                            {/if}

                            {if $phones[$i]}
                                {$phones[$i]} - 
                            {/if}
                            
                            {* append IM Service provider type for contact *}
                            {if $ims[$i]}
                                {$ims[$i]} - 
                            {/if}
                            {*else*}
                                {$mapper[$i]}
                            {*/if*}
                        {/if}
                    {else}
                        {$form.mapper[$i].html}
                    {/if}
                </td>

            </tr>
        {/section}
                
    </table>
	{/strip}

    {if $wizard.currentStepName != 'Preview'}
    <div>
    
    	{if $loadedMapping} 
        	<span>{$form.updateMapping.html} &nbsp;&nbsp; {$form.updateMapping.label}</span>
    	{/if}
    	<span>{$form.saveMapping.html} &nbsp;&nbsp; {$form.saveMapping.label}</span>
    	<div id="saveDetails" class="form-item">
    	      <dl>
    		   <dt>{$form.saveMappingName.label}</dt><dd>{$form.saveMappingName.html}</dd>
    		   <dt>{$form.saveMappingDesc.label}</dt><dd>{$form.saveMappingDesc.html}</dd>
    	      </dl>
    	</div>
    	<script type="text/javascript">
             {if $mappingDetailsError }
                show('saveDetails');    
             {else}
        	    hide('saveDetails');
             {/if}
    
    	     {literal}   
 	         function showSaveDetails(chkbox) {
        		 if (chkbox.checked) {
        			document.getElementById("saveDetails").style.display = "block";
        			document.getElementById("saveMappingName").disabled = false;
        			document.getElementById("saveMappingDesc").disabled = false;
        		 } else {
        			document.getElementById("saveDetails").style.display = "none";
        			document.getElementById("saveMappingName").disabled = true;
        			document.getElementById("saveMappingDesc").disabled = true;
        		 }
             }
            cj('select[id^="mapper"][id$="[0]"]').addClass('huge');
            {/literal}
	    {include file="CRM/common/highLightImport.tpl" relationship=true}	    
	</script>
    </div>
    {/if}
 </div>
