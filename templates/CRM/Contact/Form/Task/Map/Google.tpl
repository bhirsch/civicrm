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
{if $showDirectly}
  {assign var=height value="350px"}
  {assign var=width  value="425px"}
{else}	
  {assign var=height value="600px"}
  {assign var=width  value="100%"}
{/if}
{literal}
<script src="http://maps.google.com/maps?file=api&v=2&key={/literal}{$mapKey}{literal}" type="text/javascript"></script>
<script type="text/javascript">
    function initMap() {
	//<![CDATA[
	var map     = new GMap2(document.getElementById("google_map"));
	var span    = new GSize({/literal}{$span.lng},{$span.lat}{literal});
	var center  = new GLatLng({/literal}{$center.lat},{$center.lng}{literal});
	map.setUIToDefault();
	map.setCenter(new GLatLng( 0, 0 ), 0 );
	var bounds = new GLatLngBounds( );
	GEvent.addListener(map, 'resize', function() { map.setCenter(bounds.getCenter()); map.checkResize(); });
	
	// Creates a marker whose info window displays the given number
	function createMarker(point, data) {
	    var marker = new GMarker(point);
	    GEvent.addListener(marker, "click", function() {
		marker.openInfoWindowHtml(data);
	    });
	    return marker;
	}
	
	{/literal}
	{foreach from=$locations item=location}
	    {if $location.url and ! $profileGID}
		{literal}
		var data = "{/literal}<a href='{$location.url}'>{$location.displayName}</a><br />{if !$skipLocationType}{$location.location_type}<br />{/if}{$location.address}<br /><br />Get Directions FROM:&nbsp;<input type=hidden id=to value='{$location.displayAddress}'><input type=text id=from size=20>&nbsp;<a href=\"javascript:gpopUp();\">&raquo; Go</a>";
	    {else}
		{capture assign="profileURL"}{crmURL p='civicrm/profile/view' q="reset=1&id=`$location.contactID`&gid=$profileGID"}{/capture}
		{literal}
		var data = "{/literal}<a href='{$profileURL}'>{$location.displayName}</a><br />{if !$skipLocationType}{$location.location_type}<br />{/if}{$location.address}<br /><br />Get Directions FROM:&nbsp;<input type=hidden id=to value='{$location.displayAddress}'><input type=text id=from size=20>&nbsp;<a href=\"javascript:gpopUp();\">&raquo; Go</a>";
	    {/if}
	    {literal}
	    var address = "{/literal}{$location.address}{literal}";
	    {/literal}
	    {if $location.lat}
		var point  = new GLatLng({$location.lat},{$location.lng});
		var marker = createMarker(point, data);
		map.addOverlay(marker);
		bounds.extend(point);
	    {/if}
	{/foreach}
	map.setZoom(map.getBoundsZoomLevel(bounds));
	map.setCenter(bounds.getCenter());
	{literal}	
	//]]>  
    }

    function gpopUp() {
	var from   = document.getElementById('from').value;
	var to     = document.getElementById('to').value;	
	var URL    = "http://maps.google.com/maps?saddr=" + from + "&daddr=" + to;
	day = new Date();
	id  = day.getTime();
	eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=780,height=640,left = 202,top = 100');");
    }

    if (window.addEventListener) {
        window.addEventListener("load", initMap, false);
    } else if (window.attachEvent) {
        document.attachEvent("onreadystatechange", initMap);
    }
</script>
{/literal}
<div id="google_map" style="width: {$width}; height: {$height}"></div>
