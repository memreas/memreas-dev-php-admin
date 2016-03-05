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
            if ($("#searchi-result ul").hasClass("mCustomScrollbar"))
                var target = '#search-result ul .mCSB_container';
            else target = '#search-result ul';
            var action = 'findtag';
            var param = [{tag: "tag", value: this.query}, {tag: 'user_id', value: user_id}];
            var q = this.query;

            $("#search-result").show();
            $(".notification-area").hide();
            switch (item.charAt(0)) {
                case '@':
                var page = 1;var totalPage = 1;
                    var reqhandler = function(data) {

                        $(".tabcontent-detail").hide();
                        $("#search-result").show();
                        $(target).empty();

                        var objs = jQuery.parseJSON(data);
                        $('#search-count').text(objs.count);
                        totalPage = objs.totalPage;
                        $.each(objs.search, function(i, obj) {
                            personalSearchLi(target, obj);
                        });
                        $('.btn-friends').click(function() {
                        	var mid = $(this).attr('id');
                        	var photo = map[mid].profile_photo;
                        	photo = removeCdataCorrectLink(photo);
                        	$(".modal-backdrop").removeClass("out").addClass("in");
                        	$(".modal-backdrop").fadeIn();
                        	var pophtml = '<div id="pop-'+mid.replace("@", "")+'" class="modal fade in" style="display: none;">';
		                        	pophtml += '<div class="modal-dialog">';
		                        		pophtml += '<div class="modal-content">';
		                        			pophtml += '<form class="form-horizontal" role="form">';
		                        				pophtml += '<div class="modal-header">';
		                        					pophtml += '<button class="close" data-dismiss="modal" type="button"><span aria-hidden="true">Ã—</span></button>';
		                        					pophtml += '<h4 id="myModalLabel" class="modal-title">Add '+mid.replace("@", "")+' as a friend?</h4>';
		                        				pophtml += '</div>';
		                        				pophtml += '<div class="modal-body">';
		                        					pophtml += '<div class="row-fluid">';
		                        							pophtml += '<div class="form-group">';
		                        								pophtml += '<div class="span3" for="inputLogin"><img src="'+photo+'"/></div>';
		                        								pophtml += '<div class="span9">';
		                        									pophtml += '<p style="padding-bottom:20px">'+mid.replace("@", "")+' will have to confirm that you are friends.</p>';
		                        									pophtml += '<p>add a persional message: </p>';
		                        									pophtml += '<textarea id="msg-'+mid.replace("@", "")+'" class="input-group-lg uniform" style="width:100%;height:100px;" rows="1" cols="1"></textarea>';
		                        								pophtml += '</div><div style="clear:both"></div>';
		                        							pophtml += '</div>';
		                        					pophtml += '</div>';
		                        				pophtml += '</div>';
		                        				pophtml += '<div class="modal-footer">';
		                        					pophtml += '<button class="btn btn-primary addfriendbutton" type="button">add friend</button>';
		                        					pophtml += '<button class="btn btn-default canclemodal" type="button">cancel</button>';
		                        				pophtml += '</div>';
		                        				
                        	pophtml += '</div>';
                        	$("body").append(pophtml);
                        	$("#pop-"+mid.replace("@", "")).fadeIn();
                        	$(".canclemodal").click(function(){
                        		closeModals(mid.replace("@", ""));
                        	});
                        	$(".close").click(function(){
                        		closeModals(mid.replace("@", ""));
                        	});
                        	$(".addfriendbutton").click(function(){
                        		addFriend(mid);
                        	});
                            //addFriend($(this).attr('id'));
                        });
                        $('#search-result ul').removeClass().addClass('personresults');
                        
                        //Debugging limited results
                        console.log("count of objects::" + objs.count);
                        ajaxScrollbarElement(".personresults");
                       // paginationlink();
                        $("#linkpaginations").pagination({
                            items: objs.count,
                            itemsOnPage: 5,
                            cssStyle: 'light-theme',
                            currentPage:parseInt($('.current').not('.prev, .next').text()) - 1,
                            onPageClick:function(){
                            	var pClicked = parseInt($('.current').not('.prev, .next').text());
                                var param = [{tag: "tag", value: q},
                                    {tag: "page", value: pClicked.toString()}
                                ];
                                var action = 'findtag';
                                ajaxRequest(action, param, reqhandler);
                            }
                        });

                    }
                    break;
                case '!':
                    var page = 1;var totalPage = 1;
                    var reqhandler = function(data) {
                        $(".tabcontent-detail").hide();
                        $("#search-result").show();
                        $(target).empty();
                        var objs = jQuery.parseJSON(data);
                        $('#search-count').text(objs.count);
                        totalPage = objs.totalPage;
                        $.each(objs.search, function(i, obj) {
                            eventSearchLi(target, obj);
                        });
                        $('#search-result ul').removeClass().addClass('memreas_results');
                        ajaxScrollbarElement(".memreas_results");
                        //paginationlink();
                        $("#linkpaginations").pagination({
                            items: objs.count,
                            itemsOnPage: 5,
                            cssStyle: 'light-theme',
                            currentPage:parseInt($('.current').not('.prev, .next').text()) - 1,
                            onPageClick:function(){
                            	var pClicked = parseInt($('.current').not('.prev, .next').text());
                                var param = [{tag: "tag", value: q},
                                    {tag: "page", value: pClicked.toString()}
                                ];
                                var action = 'findtag';
                                ajaxRequest(action, param, reqhandler);
                            }
                        });
                    }
                    break;
                case '#':
                    var page = 1;var totalPage = 1;
                    var reqhandler = function(data) {
                        $(".tabcontent-detail").hide();
                        $("#search-result").show();
                        $(target).empty();

                        var objs = jQuery.parseJSON(data);
                        $('#search-count').text(objs.count);
                        totalPage = objs.totalPage;
                        $.each(objs.search, function(i, obj) {
                            discoverSearchLi(target, obj);
                        });
                        $('#search-result ul').removeClass().addClass('discover_results');
                        ajaxScrollbarElement(".discover_results");
                        $("#linkpaginations").pagination({
                            items: objs.count,
                            itemsOnPage: 5,
                            cssStyle: 'light-theme',
                            currentPage:parseInt($('.current').not('.prev, .next').text()) - 1,
                            onPageClick:function(){
                            	var pClicked = parseInt($('.current').not('.prev, .next').text());
                                var param = [{tag: "tag", value: q},
                                    {tag: "page", value: pClicked.toString()}
                                ];
                                var action = 'findtag';
                                ajaxRequest(action, param, reqhandler);
                            }
                        });
                        //paginationlink();
                    }

                    break;
            }

            /*var paginationlink = function() {

                if(totalPage == 1){
                   $(".btn-event-n").hide();
                   $(".btn-event-p").hide();
                   return;
                }
                        if (page == totalPage) {
                            $(".btn-event-n").hide();
                            $(".btn-event-p").show();

                        } else if(page == 1) {
                            $(".btn-event-p").hide();
                             $(".btn-event-n").show();
                        } else {
                             $(".btn-event-n").show();
                              $(".btn-event-p").show();
                        }
            }

            var nextbtn=function() {

                            page = page + 1;
                            var param = [{tag: "tag", value: q},
                                {tag: "page", value: page.toString()}
                            ];
                            ajaxRequest(action, param, reqhandler);
                        };
            var prevbtn = function() {
                            page = page - 1;
                            var param = [{tag: "tag", value: q},
                                {tag: "page", value: page.toString()}
                            ];
                            ajaxRequest(action, param, reqhandler);

                        };*/

            ajaxRequest(action, param, reqhandler);

           //$( ".btn-event-n" ).unbind('click').bind( "click", nextbtn );
           // $(".btn-event-p").unbind('click').bind( "click",prevbtn);

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


