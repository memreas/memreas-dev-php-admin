/*
* Server side
*/
/*Preload for server media*/
jQuery.fetch_server_media = function (user_id){
    $(".user-resources").remove();
    $("#tab-content #tab1").append ('<div class="user-resources" data-click="false" data-swipe="true" data-ratio="800/325" data-max-width="100%"  data-allow-full-screen="true"  data-nav="thumbs"></div>');
    $(".user-resources, .scrollClass .mCSB_container, .sync .mCSB_container").html('');
    $("#loadingpopup").show();
    var _request_url = '/index/ApiServerSide';
    var _request_content = '<xml>' +
                                '<listallmedia>' +
                                    '<event_id></event_id>' +
                                    '<user_id>' + user_id + '</user_id>' +
                                    '<device_id></device_id>' +
                                    '<limit>200</limit>' +
                                    '<page>1</page>' +
                                    '</listallmedia>' +
                                    '</xml>';
    var _action = 'listallmedia';
    var data = new Object();
    data.ws_action = _action;
    data.type = "jsonp";
    data.json = _request_content;
    var json_data = JSON.stringify(data);
    var _video_extensions = "mp4 wmv mov";
    $.ajax( {
      type:'post',
      url: _request_url,
      dataType: 'jsonp',
      data: 'json=' + json_data,
      success: function(json){
          json = $.xml2json(json, true);
          if (json.listallmediaresponse[0].medias[0].status[0].text == "Success") {
              var data = json.listallmediaresponse[0].medias[0].media;
              console.log(data);
              $(".user-resources, .scrollClass .mCSB_container, .sync-content .scrollClass").html('');
              for (var json_key in data){
                 var _media_url = data[json_key].main_media_url[0].text;
                 var _media_extension = _media_url.substr(_media_url.length - 3);
                 _media_extension = _media_extension.toLowerCase();
                //Build video thumbnail
                var _found = _video_extensions.indexOf (_media_extension);
                if (_found > -1){
                        $.post('/index/buildvideocache', {video_url:_media_url, thumbnail:data[json_key].event_media_video_thum[0].text, media_id:data[json_key].media_id[0].text}, function(response_data){
                            response_data = JSON.parse (response_data);
                            $(".user-resources").append('<a data-video="true" href="/memreas/js/jwplayer/jwplayer_cache/' + response_data.video_link + '"><img src="' + response_data.thumbnail + '"/></a>');
                            $(".scrollClass .mCSB_container, .sync-content .scrollClass").append ('<li><a class="swipebox" id="' + response_data.media_id + '" onclick="return imageChoosed(this.id);" href="' + response_data.thumbnail + '"><img src="' + response_data.thumbnail + '"/></a></li>');
                        });
                }
                else {
                    $(".user-resources").append('<img src="' + _media_url + '"/>');
                    $(".scrollClass .mCSB_container, .sync-content .scrollClass").append ('<li><a class="image-sync swipebox" id="' + data[json_key].media_id[0].text + '" onclick="return imageChoosed(this.id);" href="' + _media_url + '"><img src="' + _media_url + '"/></a></li>');
                }
              }
              setTimeout(function(){ 
                  $(".user-resources").fotorama({width: '100%', height: '500px'}); 
                  $("#loadingpopup").hide(); 
                  $("ul.scrollClass").mCustomScrollbar({
                        scrollButtons:{
                            enable:true
                        }
                    });
              }, 1000);
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