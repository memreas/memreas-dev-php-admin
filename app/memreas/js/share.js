/////////////////////////////////
// Author: David Kang
// Copyright memreas llc 2013
/////////////////////////////////

// MACROS for tab indices.
var SHAREPAGE_TAB_MEMREAS 	= 1;		// tab index of memreas details on share page
var SHAREPAGE_TAB_MEDIA 	= 2;		// tab index of media on share page
var SHAREPAGE_TAB_FRIENDS 	= 3;		// tab index of friends on share page

// google map object
var location_map = null;
// geocoder object to get the location address from langtitude and longtitude
var geocoder = null;

var user_id   = "4b2c6d4c-42a7-11e3-85d4-22000a8a1935";				// signed user id
var event_id  = "";				// created event id
var media_ids = [];				// array of selected media id
var device_id = "";				// current device id

var media_page_index  = 1;		// page index number for media
var media_limit_count = 200;	// limit count of media

var friendList = null;

// initialize the share page objects.
share_initObjects = function() {
	// Initially get the user id.
	//user_id = $('#user_id')[0].getAttribute('val');

	// event function when click "media" tab
	$('#tabs li:nth-child(2) a').on('click', function() {
		share_getAllMedia();
	});

	// event function when click "Friends" tab
	$('#tabs li:nth-child(3) a').on('click', function() {
		share_changeSocialType();
	});
	
	$("#cmb_socialtype").change(function(e) {
		share_changeSocialType();
    });
    
    ar_initAudio();
}

// initialize the akordeon.
share_initAkordeon = function() {
	$('#ckb_canpost')[0].checked = false;
	$('#ckb_canadd')[0].checked = false;

    $('#buttons').akordeon();
    	$('#button-less').akordeon({ buttons: false, toggle: true, itemsOrder: [2, 0, 1] });
	$('#buttons2').akordeon();
       	$('#button-less').akordeon({ buttons: false, toggle: true, itemsOrder: [2, 0, 1] });
	$('#buttons3').akordeon();
       	$('#button-less').akordeon({ buttons: false, toggle: true, itemsOrder: [2, 0, 1] });
	$('#buttons4').akordeon();
       	$('#button-less').akordeon({ buttons: false, toggle: true, itemsOrder: [2, 0, 1] });
	$('#buttons5').akordeon();
    	$('#button-less').akordeon({ buttons: false, toggle: true, itemsOrder: [2, 0, 1] });
}

// change the friend list by social type.
share_changeSocialType = function() {
	var socialType = $("#cmb_socialtype option:selected").val();
	
	switch (socialType) {
		case "facebook":
			if (fb_friendsInfo == null) {
				facebook_getFriendList();
			}
			else
				share_addFriends(fb_friendsInfo);
			break;
			
		case "twitter":
			if (tw_friendsInfo == null) {
				twitter_getFriendList();
			}
			else
				share_addFriends(tw_friendsInfo);
			break;
	}
}

// initialize and customize the scroll bar.
share_customScrollbar = function () {
	$("ul.scrollClass").mCustomScrollbar({
		scrollButtons:{
			enable:true
		}
	});
		
	$("#tab-content div.hideCls").hide(); // Initially hide all content
	$("#tabs li:first").attr("id","current"); // Activate first tab
	$("#tab-content div:first").fadeIn(); // Show first tab content*/
	
	$('#tabs a').click(function(e) {
		
		e.preventDefault();        
		$("#tab-content div.hideCls").hide(); //Hide all content
		$("#tabs li").attr("id",""); //Reset id's
		$(this).parent().attr("id","current"); // Activate this
		$('#' + $(this).attr('title')).fadeIn(); // Show content for current tab
	});
		
	//ajax demo fn
	$("a[rel='load-content']").click(function(e){
		
		e.preventDefault();
		var $this=$(this),
			url=$this.attr("href");
		$this.addClass("loading");
		$.get(url,function(data){
			$this.removeClass("loading");
			$("ul.scrollClass .mCSB_container").html(data); //load new content inside .mCSB_container
			$("ul.scrollClass").mCustomScrollbar("update"); //update scrollbar according to newly loaded content
			$("ul.scrollClass").mCustomScrollbar("scrollTo","top",{scrollInertia:200}); //scroll to top
		});
	});
	$("a[rel='append-content']").click(function(e){
		e.preventDefault();
		var $this=$(this),
			url=$this.attr("href");
		$this.addClass("loading");
		$.get(url,function(data){
			$this.removeClass("loading");
			$("ul.scrollClass .mCSB_container").append(data); //append new content inside .mCSB_container
			$("ul.scrollClass").mCustomScrollbar("update"); //update scrollbar according to newly appended content
			$("ul.scrollClass").mCustomScrollbar("scrollTo","h2:last",{scrollInertia:2500,scrollEasing:"easeInOutQuad"}); //scroll to appended content
		});
	});
}

// popup the window with google map when focus the location text field.
// div_id: identifier of div for google map.
share_showGoogleMap = function(div_id) {
	popup('dlg_locationmap');
	share_initGoogleMap(div_id);
}

// close the popup window with google map.
// save_address: variable indicating if the address is saved or not.
share_closeGoogleMap = function(save_address) {
	if (save_address == true) {
		var addr = $('#txt_locationmap_address').val();
		if (addr != "")
			$('#txt_location').val(addr);
		else
			setDefaultValue('txt_location');
	}
	
	disablePopup('dlg_locationmap');
}

// initialize the google map.
share_initGoogleMap = function(div_id) {
	if (location_map == null || typeof location_map == "undefined") {
         var lat = 44.88623409320778,
             lng = -87.86480712897173,
             latlng = new google.maps.LatLng(lat, lng),
             image = 'http://www.google.com/intl/en_us/mapfiles/ms/micons/blue-dot.png';

		 // create the google map object.
         var mapOptions = {
             center: new google.maps.LatLng(lat, lng),
             zoom: 13,
             mapTypeId: google.maps.MapTypeId.ROADMAP,
             panControl: true,
             panControlOptions: {
                 position: google.maps.ControlPosition.TOP_RIGHT
             },
             zoomControl: true,
             zoomControlOptions: {
                 style: google.maps.ZoomControlStyle.LARGE,
                 position: google.maps.ControlPosition.TOP_left
             }
         },
         location_map = new google.maps.Map(document.getElementById(div_id), mapOptions),
		 marker = new google.maps.Marker({
			 position: latlng,
			 map: location_map,
			 icon: image
		 });

		 // set the search text field as auto-complete.
         var input = document.getElementById('txt_locationmap_address');
         var autocomplete = new google.maps.places.Autocomplete(input, {
             types: ["geocode"]
         });

         autocomplete.bindTo('bounds', location_map);
         var infowindow = new google.maps.InfoWindow();

         google.maps.event.addListener(autocomplete, 'place_changed', function (event) {
             infowindow.close();
             
             var place = autocomplete.getPlace();
             if (typeof place.geometry == "undefined")
             	return;
             	
             if (place.geometry.viewport) {
                 location_map.fitBounds(place.geometry.viewport);
             } else {
                 location_map.setCenter(place.geometry.location);
                 location_map.setZoom(17);
             }

             moveMarker(place.name, place.geometry.location);
         });

         function moveMarker(placeName, latlng) {
             marker.setIcon(image);
             marker.setPosition(latlng);
             infowindow.setContent(placeName);
         }
	}
	
	if (isElementEmpty('txt_location')) {
		// get the current location.
		var err_msg = "Error while getting location. Device GPS/location may be disabled."
		var geoPositionOptions = $.extend({
			enableHighAccuracy: true,
			timeout: 10000,
			maximumAge: 10000
		}, { timeout: 10000 } );
		
		if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition ( 
				function(position) {
					var latlng = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
					if (geocoder == null)
						geocoder = new google.maps.Geocoder();
					geocoder.geocode({'latLng': latlng}, function(results, status) {
						if (status == google.maps.GeocoderStatus.OK) {
							if (results[1]) {
								$('#txt_locationmap_address').val(results[1].formatted_address);
							}
						} else {
						}
					});
					if (location_map)
						location_map.setCenter(new google.maps.LatLng(position.coords.latitude, position.coords.longitude));
				}, 
				function(error) {
					alert(err_msg);
				},
				geoPositionOptions
			);	
		} else {
			alert(err_msg);
		}
	}
}

// add the new event by request to the server.
share_addEvent = function() {
	var name = getElementValue('txt_name');
	if (name == "") {
		alert("You have to input the name.");
		return;
	}

	var date 				= getElementValue('dtp_date');
	var location 			= getElementValue('txt_location');
	var date_from 			= getElementValue('dtp_from');
	var date_to 			= getElementValue('dtp_to');
	var date_selfdestruct 	= getElementValue('dtp_selfdestruct');

	var ckb_canpost 	 	= getCheckBoxValue('ckb_canpost');
	var ckb_canadd 		 	= getCheckBoxValue('ckb_canadd');
	var ckb_public 		 	= getCheckBoxValue('ckb_public');
 	var ckb_viewable 	 	= getCheckBoxValue('ckb_viewable');
	var ckb_selfdestruct 	= getCheckBoxValue('ckb_selfdestruct');
	
	// send the request.
	ajaxRequest(
		'addevent',
		[
			{ tag: 'user_id', 					value: user_id },
			{ tag: 'event_name', 				value: name },
			{ tag: 'event_date', 				value: formatDateToDMY(date) },
			{ tag: 'event_location', 			value: location },
			{ tag: 'event_from', 				value: formatDateToDMY(date_from) },
			{ tag: 'event_to', 					value: formatDateToDMY(date_to) },
			{ tag: 'is_friend_can_add_friend', 	value: ckb_canadd },
			{ tag: 'is_friend_can_post_media', 	value: ckb_canpost },
			{ tag: 'event_self_destruct', 		value: formatDateToDMY(date_selfdestruct) },
			{ tag: 'is_public', 				value: ckb_public }
		],
		function(ret_xml) {
			// parse the returned xml.
			var status   = getValueFromXMLTag(ret_xml, 'status');
			var message  = getValueFromXMLTag(ret_xml, 'message');
			
			event_id = getValueFromXMLTag(ret_xml, 'event_id');
			
			if (status.toLowerCase() == 'success') {
				alert(event_id + ' was registered successfully.');
				share_gotoPage(SHAREPAGE_TAB_MEDIA);
			}
			else {
				alert(message);
			}
		}
	);
}

// clear all fields on details page when click "cancel" button.
share_clearMemreas = function() {
	var i = 0;
	var text_ids 	 = ['txt_name', 'txt_location', 'dtp_date', 'dtp_from', 'dtp_to', 'dtp_selfdestruct'];
	var checkbox_ids = ['ckb_canpost', 'ckb_canadd', 'ckb_public', 'ckb_viewable', 'ckb_selfdestruct'];

	clearTextField(text_ids);
	clearCheckBox(checkbox_ids);
}

// click event function for "public (anyone can add or post)" checkbox.
share_clickCkbPublic = function() {
	if (!getCheckBoxValue('ckb_public')) {
		setCheckBoxValue('ckb_canpost', true);
		setCheckBoxValue('ckb_canadd', true);
	}
}

// go to the other page (1: memreas details, 2: media, 3: friends)
share_gotoPage = function(tab_no) {
	$('#tabs li:nth-child(' + tab_no + ') a').click();
}

// add the comment to Media when click "next" button on the Media Page.
share_addComment = function() {
	ar_stop();
	//ar_saveAudio();

	share_uploadMedias(function() {
		var comments 	= getElementValue('txt_comment');
		var media_id	= "";
		
		if (media_ids.length > 0)
			media_id = media_ids[0];
		
		audio_media_id	= "";
	
		// send the request.
		ajaxRequest(
			'addcomments',
			[
				{ tag: 'event_id', 				value: event_id },
				{ tag: 'media_id', 				value: media_id },
				{ tag: 'user_id', 				value: user_id },
				{ tag: 'comments', 				value: comments },
				{ tag: 'audio_media_id', 		value: audio_media_id }
			],
			function(ret_xml) {
				// parse the returned xml.
				var status   = getValueFromXMLTag(ret_xml, 'status');
				var message  = getValueFromXMLTag(ret_xml, 'message');
				
				if (status.toLowerCase() == 'success') {
					alert('comments was added successfully.');
					share_gotoPage(SHAREPAGE_TAB_FRIENDS);
				}
				else {
					alert(message);
				}
			}
		);
	});
}

share_uploadMedias = function(success) {
	var count = 0;
	var selMediaList = $("#share_medialist .mCSB_container li a .setchoosed");
	
	for (i = 0; i < selMediaList.length; i++) {
		var img_url 	 = selMediaList[i].src;
		var img_filename = img_url.substr(img_url.lastIndexOf("/") + 1)
	
		ajaxRequest(
			'addmediaevent',
			[
				{ tag: 's3url', 				value: img_url },
				{ tag: 'is_server_image', 		value: 0 },
				{ tag: 'content_type', 			value: 'image/png' },
				{ tag: 's3file_name', 			value: img_filename },
				{ tag: 'device_id', 			value: device_id },
				{ tag: 'event_id', 				value: event_id },
				{ tag: 'media_id', 				value: '' },
				{ tag: 'user_id', 				value: user_id },
				{ tag: 'is_profile_pic', 		value: 0 },
				{ tag: 'location', 				value: '' }
			],
			function(ret_xml) {
				// parse the returned xml.
				var status  = getValueFromXMLTag(ret_xml, 'status');
				var message = getValueFromXMLTag(ret_xml, 'message');
				var id  	= getValueFromXMLTag(ret_xml, 'media_id');
				
				if (status.toLowerCase() == 'success') {
					media_ids[count++] = id;
					if (count == selMediaList.length) {
						if (typeof success != "undefined")
							success();
					}
				}
				else {
					console.log(message);
				}
			}
		);
	}
}

// clear all fields on Media page when click "cancel" button.
share_clearMedia = function() {
	clearTextField('txt_comment');
	
	var mediaList = $("#share_medialist .mCSB_container li a img");
	
	for (i = 0; i < mediaList.length; i++) {
		$(mediaList[i]).removeClass ('setchoosed');
	}
}

// get all media from user id and event id.
share_getAllMedia = function() {
	var _video_extensions = "mp4 wmv mov";

	// send the request.
	ajaxRequest(
		'listallmedia',
		[
			{ tag: 'event_id', 				value: event_id },
			{ tag: 'user_id', 				value: user_id },
			{ tag: 'device_id', 			value: device_id },
			{ tag: 'limit', 				value: media_limit_count },
			{ tag: 'page', 					value: media_page_index }
		],
		function(json) {
			json = $.xml2json(json, true);
			if (json.listallmediaresponse[0].medias[0].status[0].text == "Success") {
			  	var data = json.listallmediaresponse[0].medias[0].media;
			  	var mediaList = $("#share_medialist .mCSB_container");
			  	mediaList.empty();
			  	for (var json_key in data) {
				 	var _media_url = data[json_key].main_media_url[0].text;
				 	var _media_extension = _media_url.substr(_media_url.length - 3);
				 	_media_extension = _media_extension.toLowerCase();
					//Build video thumbnail
					var _found = _video_extensions.indexOf (_media_extension);
					if (_found > -1) {
						$.post('/index/buildvideocache', {video_url:_media_url, thumbnail:data[json_key].event_media_video_thum[0].text, media_id:data[json_key].media_id[0].text}, function(response_data){
							response_data = JSON.parse(response_data);
							mediaList.append ('<li><a class="swipebox" id="' + response_data.media_id + '" onclick="return imageChoosed(this.id);" href="' + response_data.thumbnail + '"><img src="' + response_data.thumbnail + '"/></a></li>');
						});
					}
					else {
						mediaList.append ('<li><a class="image-sync swipebox" id="' + data[json_key].media_id[0].text + '" onclick="return imageChoosed(this.id);" href="' + _media_url + '"><img src="' + _media_url + '"/></a></li>');
					}
			  	}
			  	
			  	//setTimeout(function(){ $(".user-resources").fotorama(); $(".preload-server").hide(); }, 1000);
			  	
			  	ar_start();
			}
		}
	);
}

// clear the friend list.
share_clearFriendsList = function() {
	if (friendList == null)
		friendList = $('#share_friendslist .mCSB_container');
	
	friendList.empty();
}

// add friends to the list from social friends information.
share_addFriends = function(info) {
	if (friendList == null)
		friendList = $('#share_friendslist .mCSB_container');

	friendList.empty();
	
	var i = 0, el;

	for (i = 0; i < info.length; i++) {
		el = '';
		el += '<li>';
		el += '<figure class="pro-pics2" id="' + info[i].div_id + '" onclick="javascript:share_clickFriends(this.id);"><img src="/memreas/img/profile-pic.jpg" alt="" ' + (info[i].selected ? 'class="setchoosed"' : '') + '></figure>';
		el += '<aside class="pro-pic_names2" id="a' + info[i].div_id + '" onclick="javascript:share_clickFriends(this.id.substr(1));">' + info[i].name + '</aside>';
		el += '</li>';
							
		friendList.append(el);
	}

	var imgList = $('#share_friendslist .mCSB_container li img');

	for (i = 0; i < imgList.length; i++) {
		$(imgList[i]).prop('src', info[i].photo);
	}
}

share_clickFriends = function(id) {
	var type = id.substr(0, 2);
	var idx = parseInt(id.substr(3));
	
	if (type == "fb") {
		fb_friendsInfo[idx].selected = !fb_friendsInfo[idx].selected;
		if (fb_friendsInfo[idx].selected) {
			$('#' + id + ' img').addClass('setchoosed');
		}
		else {
			$('#' + id + ' img').removeClass('setchoosed');
		}
	}
	else if (type == "tw") {
		tw_friendsInfo[idx].selected = !tw_friendsInfo[idx].selected;
		if (tw_friendsInfo[idx].selected) {
			$('#' + id + ' img').addClass('setchoosed');
		}
		else {
			$('#' + id + ' img').removeClass('setchoosed');
		}
	}
}

// make the group with selected friends and e-mail list.
share_makeGroup = function() {
	var emailList 	= splitByDelimeters(getElementValue('txt_emaillist'), [',', ';']);
	var groupName	= getElementValue('txt_groupname');
	var selFriends  = [];
	var i = 0, count = 0;
	
	// get all information of selected friends (facebook and twitter).
	if (fb_friendsInfo) {
		for (i = 0; i < fb_friendsInfo.length; i++) {
			if (fb_friendsInfo[i].selected) {
				selFriends[count++] = {
					tag: 'friend',
					value: [
								{ tag: 'friend_name', 		value: fb_friendsInfo[i].name },
								{ tag: 'network_name', 		value: 'facebook' },
								{ tag: 'profile_pic_url', 	value: fb_friendsInfo[i].photo }
							]
				};
			}
		}
	}
	
	if (tw_friendsInfo) {
		for (i = 0; i < tw_friendsInfo.length; i++) {
			if (tw_friendsInfo[i].selected) {
				selFriends[count++] = {
					tag: 'friend',
					value: [
								{ tag: 'friend_name', 		value: tw_friendsInfo[i].name },
								{ tag: 'network_name', 		value: 'twitter' },
								{ tag: 'profile_pic_url', 	value: tw_friendsInfo[i].photo }
							]
				};
			}
		}
	}

	// send the request.
	ajaxRequest(
		'creategroup',
		[
			{ tag: 'group_name', 	value: groupName },
			{ tag: 'user_id', 		value: user_id },
			{ tag: 'friends', 		value: selFriends }
		],
		function(ret_xml) {
			// parse the returned xml.
			var status   = getValueFromXMLTag(ret_xml, 'status');
			var message  = getValueFromXMLTag(ret_xml, 'message');
			
			if (status.toLowerCase() == 'success') {
				alert('group was created successfully.');
			}
			else {
				alert(message);
			}
		}
	);
}

// clear all fields on Friends page when click "cancel" button.
share_clearFriends = function() {
	clearTextField(['txt_emaillist', 'txt_groupname']);
	clearCheckBox('ckb_makegroup');
}
