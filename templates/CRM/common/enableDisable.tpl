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
{* handle common enable/disable actions *}
<div id="enableDisableStatusMsg" class="success-status" style="display:none;"></div>
{literal}
<script type="text/javascript">
function modifyLinkAttributes( recordID, op, recordBAO ) {
    //we changed record from enable to disable
    if ( op == 'enable-disable' ) {
        var fieldID     = "#row_"+ recordID + " a." + "disable-action";
        var operation   = "disable-enable";
        var htmlContent = {/literal}'{ts}Enable{/ts}'{literal};
        var newClass    = 'action-item enable-action';
        var newTitle    = {/literal}'{ts}Enable{/ts}'{literal};
        var newText     = {/literal}' {ts}No{/ts} '{literal};
    } else if ( op == 'disable-enable' ) {
        var fieldID     = "#row_"+ recordID + " a." + "enable-action";
        var operation   = "enable-disable";
        var htmlContent = {/literal}'{ts}Disable{/ts}'{literal};
        var newClass    = 'action-item disable-action';
        var newTitle    = {/literal}'{ts}Disable{/ts}'{literal};
        var newText     = {/literal}' {ts}Yes{/ts} '{literal};
    }

    //change html
    cj( fieldID ).html( htmlContent ); 	

    //change title
    cj( fieldID ).attr( 'title', newTitle );

    //need to update js - change op from js to new allow operation. 
    //set updated js
    var newAction = 'enableDisable( ' + recordID + ',"' + recordBAO + '","' + operation + '" );';
    cj( fieldID ).attr("onClick", newAction );
    
    //set the updated status
    var fieldStatus = "#row_"+ recordID + "_status";
    cj( fieldStatus ).text( newText );

    //finally change class to enable-action.
    cj( fieldID ).attr( 'class', newClass );
}

function modifySelectorRow( recordID, op ) {
    var elementID = "#row_" + recordID;
    if ( op == "disable-enable" ) {
        cj( elementID ).removeClass("disabled");
    } else if ( op == "enable-disable" )  {
        //we are disabling record.
        cj( elementID ).addClass("disabled");
    }
}

function hideEnableDisableStatusMsg( ) {
    cj( '#enableDisableStatusMsg' ).hide( );
}

cj( '#enableDisableStatusMsg' ).hide( );
function enableDisable( recordID, recordBAO, op ) {
    	if ( op == 'enable-disable' ) {
       	   var st = {/literal}'{ts}Disable Record{/ts}'{literal};
    	} else if ( op == 'disable-enable' ) {
       	   var st = {/literal}'{ts}Enable Record{/ts}'{literal};
    	}

	cj("#enableDisableStatusMsg").show( );
	cj("#enableDisableStatusMsg").dialog({
		title: st,
		modal: true,
		bgiframe: true,
		position: "right",
		overlay: { 
			opacity: 0.5, 
			background: "black" 
		},

        	beforeclose: function(event, ui) {
            	        cj(this).dialog("destroy");
        	},

		open:function() {
       		        var postUrl = {/literal}"{crmURL p='civicrm/ajax/statusmsg' h=0 }"{literal};
		        cj.post( postUrl, { recordID: recordID, recordBAO: recordBAO, op: op  }, function( statusMessage ) {
			        if ( statusMessage.status ) {
 			            cj( '#enableDisableStatusMsg' ).show( ).html( statusMessage.status );
       	     		        }
				if ( statusMessage.show == "noButton" ) {
   				    cj('#enableDisableStatusMsg').dialog('option', 'position', "centre");
				    cj('#enableDisableStatusMsg').data("width.dialog", 630);
				    cj.extend( cj.ui.dialog.prototype, {
			               	      'removebutton': function(buttonName) {
				                      var buttons = this.element.dialog('option', 'buttons');
						      delete buttons[buttonName];
						      this.element.dialog('option', 'buttons', buttons);
        				      }
				    });
				    cj('#enableDisableStatusMsg').dialog('removebutton', 'Cancel'); 
				    cj('#enableDisableStatusMsg').dialog('removebutton', 'OK'); 
       			    }  
	       	        }, 'json' );
		},
	
		buttons: { 
			"OK": function() { 	    
			        saveEnableDisable( recordID, recordBAO, op );
			        cj(this).dialog("close"); 
			        cj(this).dialog("destroy");
			},

			"Cancel": function() { 
				cj(this).dialog("close"); 
				cj(this).dialog("destroy"); 
			} 
		} 
	});
}

//check is server properly processed post.
var responseFromServer = false; 

function noServerResponse( ) {
    if ( !responseFromServer ) { 
        var serverError =  '{/literal}{ts}There is no response from server therefore selected record is not updated.{/ts}{literal}'  + '&nbsp;&nbsp;<a href="javascript:hideEnableDisableStatusMsg();"><img title="{/literal}{ts}close{/ts}{literal}" src="' +resourceBase+'i/close.png"/></a>';
        cj( '#enableDisableStatusMsg' ).show( ).html( serverError ); 
    }
}

function saveEnableDisable( recordID, recordBAO, op ) {
    cj( '#enableDisableStatusMsg' ).hide( );
    var postUrl = {/literal}"{crmURL p='civicrm/ajax/ed' h=0 }"{literal};

    //post request and get response
    cj.post( postUrl, { recordID: recordID, recordBAO: recordBAO, op:op  }, function( html ){
        responseFromServer = true;      
       
        //this is custom status set when record update success.
        if ( html.status == 'record-updated-success' ) {
           
            //change row class and show/hide action links.
            modifySelectorRow( recordID, op );

            //modify action link html        
            modifyLinkAttributes( recordID, op, recordBAO ); 
        } 

            //cj( '#enableDisableStatusMsg' ).show( ).html( successMsg );
        }, 'json' );

        //if no response from server give message to user.
        setTimeout( "noServerResponse( )", 1500 ); 
    }
    </script>
    {/literal}
