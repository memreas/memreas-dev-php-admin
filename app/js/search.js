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

      //  addLoading('.top-search', 'input', '');
        ajaxRequest('findtag', [{tag: "tag", value: query},  {tag: 'user_id', value: user_id} ]
                , function(data) {
                    var q = $('#search').val();
                    users = [];
                    map = {};
                    var objs = jQuery.parseJSON(data);

                    switch (q.charAt(0))
                    {
                        case '@':
                            $.each(objs.search, function(i, obj) {
                                map[obj.username] = obj;
                                users.push(obj.username);
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
            doAdminAjax();
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
            photo = removeCdataCorrectLink(photo);
            var name = map[item].username;
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
        var photo = item.profile_photo;
        photo = removeCdataCorrectLink(photo);
        var name = $.trim(item.username);
        var op = '<li><figure class="pro-pics"><img src="'
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

function addFriend(name) {
    var user_id = $("input[name=user_id]").val();
    var photo = map[name].profile_photo;
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
            'addfriendtoevent',
            [
                {tag: 'user_id', value: user_id},
                {tag: 'event_id', value: ''},
                {tag: 'friends', value: selFriends}
            ],
            function(ret_xml) {
                // parse the returned xml.
                var status = getValueFromXMLTag(ret_xml, 'status');
                var message = getValueFromXMLTag(ret_xml, 'message');
                if (status.toLowerCase() == 'success') {

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
                else
                    jerror(message);
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



