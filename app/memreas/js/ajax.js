/////////////////////////////////
// Author: David Kang
// Copyright memreas llc 2013
/////////////////////////////////

//var base_url = 'http://memreasdev.elasticbeanstalk.com/eventapp_zend2.1/webservices/index.php?';
var wsurl = 'http://memreas-dev-php-frontend.localhost/index/sampleAjax';
var xml_str = "";

//////////////////////////////////
// Input xml and fetch output xml
//////////////////////////////////
ajaxRequest = function (action, params, success_func, error_func) {
    var data = "";
    var result = "";
	var xml_input = getXMLStringFromParamArray(action, params);
    
    var data = new Object();
    data.ws_action = action;
    data.type = "jsonp";
    data.json = xml_input;
    
    var json_data = JSON.stringify(data);
   	
   	$('#loadingpopup').show();
	$.ajax( {
	  	type:'post', 
	  	url: wsurl,
	  	dataType: 'jsonp',
	  	data: 'json=' + json_data,
	  	success: function(ret_xml) {            
			if (typeof success_func != "undefined")
				success_func(ret_xml);
		   	$('#loadingpopup').hide();
	  	},
	  	error: function (jqXHR, textStatus, errorThrown) {
       		alert(jqXHR.responseText);
       		alert(jqXHR.status);
			if (typeof error_func != "undefined")
				error_func();
		   	$('#loadingpopup').hide();
	  	}
	});
	
	return false;
}

getXMLStringFromParamArray = function(action, params) {
	var i = 0;
	var action_tag = "";
	
	xml_str = "<xml>";

	switch (action) {
		case "checkusername":
			action_tag = "checkusername";
			break;
			
		case "addevent":
			action_tag = "addevent";
			break;
			
		case "addcomments":
			action_tag = "addcomment";
			break;
			
		case "creategroup":
			action_tag = "creategroup";
			break;
			
        /* Action coded by Tran Tuan - For develop only DELETE THIS COMMENT WHEN GO LIVE */                
        case "addmediaevent":
            action_tag = "addmediaevent";
            break;
            
		case "deletephoto":	
            action_tag = "deletephoto";
            break;
            
        case "listallmedia":
        	action_tag = "listallmedia";
            
		default:
			break;
	}
	
	xml_str += "<" + action_tag + ">";
	
	getSubXMLStringFromParamArray(params);
	
	xml_str += "</" + action_tag + ">";
	
	xml_str += "</xml>";
	//console.log(xml_str);
	
	return xml_str;
}

getSubXMLStringFromParamArray = function(params) {
	if (typeof params.length == "undefined")
		return;

	for (var i = 0; i < params.length; i++) {
		xml_str += "<" + params[i]['tag'] + ">";
		var val = params[i]['value'];
		if (typeof val != "string" && typeof val.length != "undefined") {
			getSubXMLStringFromParamArray(val);
		}
		else
			xml_str += val;
		xml_str += "</" + params[i]['tag'] + ">";
	}
}
/*)
$(function(){
    $("#main-tab ul a").click(function(){        
        var _maintab_content_element = $(this).attr ("title");        
        if ($("#" + _maintab_content_element).html() == ""){
            var _ajax_content_url = $(this).attr ("href");
            $.ajax({
               url: _ajax_content_url,
               type: "GET" ,
               dataType: 'html',
               success: function(data){                   
                  $("#main-tab ul a").removeClass ('active');
                  $(this).addClass ('active');
                  var _content = $(data).find(".main-content").html();                  
                  $("#" + _maintab_content_element).html (_content);                 
               }
            });
        }
         $(".tabcontent-detail").hide();
         $("#" + _maintab_content_element).fadeIn(500);
         return false;
    });
});

*/
$(function(){
    $("#main-tab ul a").click(function(){
        $("#main-tab ul a").removeClass ("active");
        $(this).addClass ("active");
        var _active_tab = $(this).attr ("title");
        $(".tabcontent-detail").hide();
        $("#" + _active_tab).fadeIn();
    });
    $(".clear-upload").click (function(){
        $("ul.image_upload_box").html("");
        $("ul.image_upload_box").html('<li class="first-upload"></li>');        
    });
});