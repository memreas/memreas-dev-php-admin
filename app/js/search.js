/**
 * Copyright (C) 2015 memreas llc. - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 */
$(document).ready(function() {
 var user_id = $("input[name=user_id]").val();
     var throttledRequest = _.debounce(function(query, process)
    {

         if(typeof users !== 'undefined'){
               for (var i in users) {
                if(users[i].search(query) !== -1){
                   return process(users);
                }
            }
        }

        addLoading('.top-search', 'input', '');
        ajaxRequest('findtag', [{tag: "tag", value: query},  {tag: 'user_id', value: user_id} ]
                , function(data) {
                    var q = $('#search').val();
                    users = [];
                    map = {};
                    //var objs = jQuery.parseJSON(data);
                    var objs = data;
                    var username = '@';
                    switch (q.charAt(0))
                    {
                        case '@':
                            $.each(objs.search, function(i, obj) {
                            	username += obj.username;
                                map[username] = obj;
                                users.push(username);
                            });
                            break;
                        case '!':
                            $.each(objs.search, function(i, obj) {
                                map[obj.name] = obj;
                                users.push(obj.name);
                            });
                            break;
                        case '#':
                            $.each(objs.search, function(i, obj) {
                                map[obj.name] = obj;
                                users.push(obj.name);
                            });
                            break;

                    }
                   removeLoading('.top-search');
                   return process(users);
                }, 'undefined', true);
    }, 1000);


    $('#search').typeahead({
        source: function(query, process) {
            throttledRequest(query, process);
        },
        updater: function(item) {
            q = this.query;
            doSearchAjax();
            return item;
        },
                  highlighter: function(item) {
            return h(item);
        },
        minLength: 4
    });
});

var h = function(item) {

    switch (item.charAt(0)) {
        case '@':
            var photo = map[item].profile_photo;
            var name = map[item].username;
            photo = removeCdataCorrectLink(photo);
            break;
        case '!':
            var photo = map[item].event_photo;
            photo = removeCdataCorrectLink(photo);
            var name = map[item].name + "!";
            break;
        case '#':
            var photo = map[item].event_photo;
            var name = map[item].event_name;
            var comment =map[item].comment;
                //html = '<div class="bond"><img src="' + photo + '"><strong>' + name + '</strong><strong>' + map[item].event_name + '</strong></div>';
                if(comment.length > 25) {
                    comment = comment.substring(0,24)+"...";
                }
                photo = removeCdataCorrectLink(photo);
                var html ='<div class="swipebox_comment" style="float: left;"><div class="event_pro"><img src="' + photo + '"></div>'+ name +'<div class="event_textarea" >' + comment + '</div></div><div class="event_gallery_pro"><img src="' + map[item].commenter_photo + '"></div>';
                return html;
            break;
    }

    photo = removeCdataCorrectLink(photo);
    var html = '<div class="bond"><img src="' + photo + '"><strong>' + name + '</strong></div>';
    return html;



}

function personalSearchLi(target, item) {
    //Prevent yourself listing
    if (('@' + $("input[name=username]").val()) != item.username){
    		for (property in item) {

    		 /*this will create one string with all the Javascript 
    		  properties and values to avoid multiple alert boxes: */

    		  alertText += property + ':' + sampleObject[property]+'; ';
    		  console.log (alertText);

    		}
    	
        var photo = item.profile_photo;
        photo = removeCdataCorrectLink(photo);
        console.log(photo);
        var name = $.trim(item.username);
        var op = '<li id="search-'+name.replace('@', '')+'"><figure class="pro-pics"><img src="'
                + photo + '" alt=""></figure><div class="user-names">'
                + name + '</div>';
                if(typeof item.friend_request_sent  === 'undefined'){
                    op += '<a href="javascript:;" class="btn-friends black_btn_skin" id="'
                + name + '" title="user-' + name.replace('@', '') +'">add friend</a></li>';
                }
                
        $(target).append(op);
    }
}
function eventSearchLi(target, item) {
    console.log(item);
    var event_img = item.event_photo;
    event_img = removeCdataCorrectLink(event_img);
    var name = $.trim(item.name);

    var op = '<li>'
            + '<div class="event_img"><img src="'
            + event_img + '" alt=""></div><aside class="event_name">'
            + name + '</aside><div class="event_like"><span>'
            + item.like_count + '</span></div><div class="event_comment"><span>' + item.comment_count + '</span></div>'
            + ' <div class="event_members">';
    if (item.friends.length > 0) {
        $.each(item.friends, function(i, friend) {
            var friend_photo = removeCdataCorrectLink(friend.profile_photo);
            op += '<div class="event_gallery_pro"><img src="' + friend_photo + '" title="' + friend.username + '"></div>'
        });
    }



    op += '</div></li>';
    $(target).append(op);
}
function closeModals(modalid){
	$(".modal-backdrop").removeClass("in").addClass("out").fadeOut();
	$("#pop-"+modalid).remove();
}
function addFriend(name) {
    var user_id = $("input[name=user_id]").val();
    var personalMsg = $("#msg-"+name.replace("@", "")).val();
    
    var photo = map[name].profile_photo;
    var friend_id = map[name].user_id;
    var selFriends = [];
    selFriends[0] = {
        tag: 'friend',
        value: [
            {tag: 'friend_name', value: name.replace("@", "")},
            {tag: 'network_name', value: 'memreas'},
            {tag: 'profile_pic_url', value: ''}
        ]
    };
    ajaxRequest(
            'addfriend',
            [
                {tag: 'user_id', value: user_id},
                {tag: 'friend_id', value: friend_id},
            ],
            function(ret_xml) {
                // parse the returned xml.
                var status = getValueFromXMLTag(ret_xml, 'status');
                var message = getValueFromXMLTag(ret_xml, 'message');
                if (status.toLowerCase() == 'success') {
                	if($.trim(personalMsg) != ""){
                		
                	}
                	closeModals(name.replace("@", ""));
                    //jsuccess('your friends added successfully.');
                    jsuccess(message);
                    var link_object = "a[title='user-" + name.replace('@', '') + "']";
                    $(link_object).removeAttr('href')
                        .html("requested")
                        .attr("disabled", true)
                        .css({'font-size':'12px', 'font-style':'italic'})
                        .unbind('click');
                    //$(".popupContact li").each (function(){ $(this).removeClass ('setchoosed');});
                }
                else{
                	closeModals(name.replace("@", ""));
                	jerror(message);
                }
                    
            }
    );




}

function discoverSearchLi(target, item) {
    //var event_img = item.event_photo;
    //var name = $.trim(item.name);
    /*
     input- tag
     event- image,name
     commented person photo
     comment-text
     tags in ancor
    */
    var op = '<li>'
        op +=  '<div class="event_img"><img src="'
                + item.event_photo + '" alt=""><span class="event_name_box">'
                + item.event_name + '</span></div>';
        op +=  '<p>' + item.comment + '</p>';

    op += '</li>';
    $(target).append(op);
}


