/*
*@Define global function here
*@ Tran Tuan
*/
var GLOBAL_ENV = 'development'; //development or live
var CURRENT_URL = document.URL;
if (CURRENT_URL.indexOf('localhost') < 0 && GLOBAL_ENV == 'development')
    GLOBAL_ENV = 'live'; //Force set if URL is not localhost

var s3_bucket = 'memreasdev';

/*
*@ Function Scrollbar Secure
*@ Surely scrollbar element loaded and scroll loaded also
*/
function ajaxScrollbarElement(element_object){
    var jelement_object = $(element_object);
    jelement_object.mCustomScrollbar('update');
    if (!jelement_object.hasClass ('mCustomScrollbar'))
        jelement_object.mCustomScrollbar({ scrollButtons:{ enable:true }});
    if (!jelement_object.find ('.mCSB_scrollTools').is(':visible')){
        jelement_object.mCustomScrollbar('update');
        setTimeout (function(){ ajaxScrollbarElement(element_object); }, 1000);
    }
}
function getMediaThumbnail(element_object, default_value){
    jelement_object = $(element_object);
    var media_response = new Array('media_url_98x78', 'media_url_79x80', 'media_url_448x306', 'event_media_video_thum', 'main_media_url');
    var total_media_response = media_response.length;
    var found_link = '';
    for (i = 0;i < total_media_response;i++){
        found_link = jelement_object.filter (media_response[i]).html();
        found_link = found_link.replace("<!--[CDATA[", "").replace("]]-->", "");
        if (found_link != '') break;
    }
    media_type = jelement_object.filter('type').html();
    if (found_link == '' || found_link.indexOf ('undefined') >= 0 || (media_type == 'video' && (i == total_media_response - 1))) found_link = default_value;
    return found_link;
}

//Check if image already exist
function image_exist(image_url, default_value){

}

//Notify functions
function jsuccess (str_msg){
    jSuccess(
    str_msg,
    {
      autoHide : true, // added in v2.0
      clickOverlay : false, // added in v2.0
      MinWidth : 250,
      TimeShown : 3000,
      ShowTimeEffect : 200,
      HideTimeEffect : 200,
      LongTrip :20,
      HorizontalPosition : 'center',
      VerticalPosition : 'top',
      ShowOverlay : true,
      ColorOverlay : '#FFF',
      OpacityOverlay : 0.3,
      onClosed : function(){},
      onCompleted : function(){}
    });
}
function jerror (str_msg){
    jError(
    str_msg,
    {
      autoHide : true, // added in v2.0
      clickOverlay : false, // added in v2.0
      MinWidth : 250,
      TimeShown : 3000,
      ShowTimeEffect : 200,
      HideTimeEffect : 200,
      LongTrip :20,
      HorizontalPosition : 'center',
      VerticalPosition : 'top',
      ShowOverlay : true,
      ColorOverlay : '#FFF',
      OpacityOverlay : 0.3,
      onClosed : function(){},
      onCompleted : function(){}
    });
}
function logout(){
    var user_logged = $("input[name=user_id]").val();
    var params = [{tag: 'user_id', value: user_logged}];
    ajaxRequest('logout', params, function(response){
        window.location.href = "/";
    });
}

//Remove item from an array
function removeItem(array, item){
    for(var i in array){
        if(array[i]==item){
            array.splice(i,1);
            break;
            }
    }
}