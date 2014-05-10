/**
* Upload - Sync - Download function
*/
jQuery.addfile = function(){
    var _source_action = "/index/s3upload";
    var _source_element = ".sync-content .upload";
    var _new_element = jQuery.randomElement();
    jQuery(_source_element).append ('<div class="row-choose-file" id="' + _new_element + '"></div>');
    jQuery("#" + _new_element).append('<iframe src="' + _source_action + '" width="550" height="120"></iframe>');
    jQuery("#" + _new_element).append('<div class="addmore" style="position: absolute; right: 100px; top: 10px;"><a href="javascript:addfile();" class="btn-addmore-file"><img src="/memreas/img/cloudadd.png" width="30" border="none"/></a> <a href="javascript:removeRow(\'' + _new_element + '\');"><img src="/memreas/img/cloudremove.png" width="30" border="none"/></a></div>');
    return false;
}

//Generate random id element
jQuery.randomElement = function(){
    var random_number = Math.floor((Math.random()*10000)+1);
    var _element = "row-file-" + random_number;
    if (jQuery("#" + _element).length > 0)
        _final_element = jQuery.randomElement();
    _final_element = _element;
    return _final_element;
}
function addfile(){ jQuery.addfile(); }
function removeRow(element_id){ jQuery("#" + element_id).remove(); }
function success_addmedia(response){
    response = $.xml2json(response, true);
    response = response.addmediaeventresponse[0].status[0].text;
    if (response == "Success")
        {
            //alert ("Media added success");
        }
    else alert ("error while adding media");
}
function error_addmedia(){}

/*function for sync tab image */
function imageChoosed(media_id){    
    if (jQuery("a#" + media_id + " img").hasClass ('setchoosed')){        
        jQuery("a#" + media_id + " img").removeClass ('setchoosed');
    }
    else jQuery("a#" + media_id + " img").addClass ('setchoosed');
    return false;
}

function deleteFiles(){
    var confirm_box = confirm ("Are you sure want to delete them?");
    if (confirm_box){
         $("#loadingpopup").show();
        $(".sync-content img").each(function(){
           if ($(this).hasClass("setchoosed")){
               var media_id = $(this).parent("a").attr ("id");               
               var xml_data = new Array();
               xml_data[0] = new Array();
               xml_data[0]['tag'] = 'mediaid';               
               xml_data[0]['value'] = media_id.trim();                              
               ajaxRequest ('deletephoto', xml_data, success_deletephoto, error_deletephoto); 
           }           
        });                                                                                    
        $.fetch_server_media($("input[name=user_id]").val());
    }
}
function success_deletephoto(ret_xml){     
     //alert ("Successfull delete photo");     
}
function error_deletephoto(){}

jQuery (function(){
    jQuery ("#btn-upload").click (function(){
        jQuery(".edit-area").fadeOut(500);
        jQuery(".upload-container").delay(500).fadeIn(500);
    });
    jQuery (".back-edit").click (function(){
        jQuery(".upload-container").fadeOut(500);
        jQuery(".edit-area").delay(500).fadeIn(500);    
    });
    
});