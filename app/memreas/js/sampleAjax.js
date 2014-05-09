/////////////////////////////////
// Author: John Meah
// Copyright memreas llc 2013
/////////////////////////////////

//var base_url = 'http://memreasdev.elasticbeanstalk.com/eventapp_zend2.1/webservices/index.php?';
var wsurl = 'http://memreas-dev-php-frontend.localhost/index/sampleAjax';
//////////////////////////////////
// Input xml and fetch output xml
//////////////////////////////////
jQuery.sampleAjax = function () {

alert("Inside jQuery.sampleAjax");

	var ws_action = $("#ws_action").val();
	var xml_input = $("#xml_form_input").val();
	var xml_output = "";
    //var json_sampleAjax = JSON.stringify(xml_input, null, '\t');
    var data = "";
    var result = "";
    
    //if () {}
    var data = new Object();
    data.ws_action = ws_action;
    data.type = "jsonp";
    data.json = xml_input;
    var json_data = JSON.stringify(data);
   
	$.ajax( {
	  type:'post', 
	  url: wsurl,
	  dataType: 'jsonp',
	  data: 'json=' + json_data,
	  success: function(json){
	  	$("#xml_form_output").val(JSON.stringify(json, null, '\t'));
	  	if (json.Status == "Success") {
	  		//do more processing here if you need to...
	  		alert("Success reached?");
	  	}
	  	return true;
	  },
	  error: function (jqXHR, textStatus, errorThrown) {
       	alert(jqXHR.responseText);
       	alert(jqXHR.status);
    	//alert(textStatus);
       	//alert(errorThrown);
	  }
	});
	return false;
}




