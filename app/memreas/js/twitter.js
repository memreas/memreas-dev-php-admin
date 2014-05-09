/////////////////////////////////
// Author: David Kang
// Copyright memreas llc 2013
/////////////////////////////////

var TWITTER_USER 		= 'FranzPolar';
var TWITTER_KEY 		= 'qIPCqlYRHip1chaY1tt0w';
var TWITTER_SECRETCODE 	= 'ofDFMhBUS12Di6HlB0Cb7rkiKc7X4zpv85m3tXaZ1wY';

var twitterToken = null;
var tw_friendsInfo = null;
var tw_friendCount = 0;
var tw_friendIndex = 0;


twitter_getFriendList = function() {
	$('#loadingpopup').show();
	twitter_authorization();
}

twitter_authorization = function() {
	$.oauthpopup({
		path: 'twitter',
		callback: function(){
			window.location.reload();
		}
	});
    
	return;

	var tKBase64 =  btoa(TWITTER_KEY + ":" + TWITTER_SECRETCODE);
	
	try {
		send = $.ajax({
			beforeSend: function (xhr, settings) {
				xhr.withCredentials = true;
				xhr.setRequestHeader('Authorization', 'Basic ' + tKBase64);
			},
			type: "POST",
			data: { "grant_type" : "client_credentials"},
			url: 'https://api.twitter.com/oauth2/token',
			//url: 'https://api.twitter.com/oauth/request_token',
			success: function(data) {
				twitterToken = data.access_token;
				twitter_getAllFriends();
			},
			fail: function(data) {
				console.log(data);
				$('#loadingpopup').hide();
			}
		});
	} catch (e) {
		$('#loadingpopup').hide();
	}
}
		
twitter_getAllFriends = function() {
	if (twitterToken != null)
	{
		$.ajax({
			beforeSend: function (xhr, settings) {
				xhr.withCredentials = true;
				xhr.setRequestHeader('Authorization', 'Bearer ' + twitterToken);
			},
			type: "GET",
			url: 'https://api.twitter.com/1.1/friends/ids.json?cursor=-1&screen_name=' + TWITTER_USER + '&count=5000',
			success: function(data) {
				tw_friendsInfo = [];
				tw_friendIndex = 0;
				tw_friendCount = data.ids.length;
				for(var i = 0; i < data.ids.length; i++) {
					var appourl = 'https://api.twitter.com/1.1/users/lookup.json?user_id=' + data.ids[i] + '&include_entities=true'; 
					$.ajax({
						beforeSend: function (xhr, settings) {
							xhr.withCredentials = true;
							xhr.setRequestHeader('Authorization', 'Bearer ' + twitterToken);
						},
						type: "GET",
						//data: { "q": 'from:' + TWITTER_USER, 'count': 3, 'result_type': 'recent'},
						//url: 'https://api.twitter.com/1.1/search/tweets.json',
						url: appourl,
						success: function(friend_data) {
							tw_friendsInfo[tw_friendIndex] = {
								'id': 		friend_data[0].id,
								'div_id':	'tw_' + tw_friendIndex,
								'name': 	friend_data[0].name,
								'photo': 	friend_data[0].profile_image_url_https,
								'selected':	false
							}
							
							tw_friendIndex++;
							if (tw_friendIndex >= tw_friendCount) {
								share_addFriends(tw_friendsInfo);
								$('#loadingpopup').hide();
							}
						}
					});
				}
			},
			fail: function(data) {
				console.log(data);
				$('#loadingpopup').hide();
			}
		});
	}
	else {
		$('#loadingpopup').hide();
	}
}