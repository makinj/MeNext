function register(){
  $.post("handler.php", $("#register").serialize(),
    function(data){
      var result= JSON.parse(data);
      if(result['status']=='success'){
        window.location.href = "/";
      }else{
        $("#register_problem").html('<div class="alert alert-danger" role="alert">' + result['errors'][0] + '</div>');
      }
    }
  );
  return false;
}

function login(){
  $.post("handler.php", $("#login").serialize(),
    function(data){
      var result= JSON.parse(data);
      if(result['status']=='success'){
        window.location.href = "/";
      }else{
        $("#login_problem").html('<div class="alert alert-danger" role="alert">' + result['errors'][0] + '</div>');
      }
    }
  );
  return false;
}

function searchYouTube(){//searches youtube to get a list of
  if($("#searchText").val()!=""){
    $.get("https://www.googleapis.com/youtube/v3/search"+
      "?part=snippet"+//don't question this part
      "&order=relevance"+//sort by relevance
      "&type=video"+//only show videos(not channels or playlists)
      "&maxResults=25"+//up to 25 results
      "&q="+$("#searchText").val()+//query with search term
      "&key="+YT_API_KEY,//Client API key

      function(data,status){
        if (status=="success"){
          listSearchResults(data);
        }else{
          $("#searchResults").html("Failed :(");
        }
        $('button.addVideo').click(function(){
          $(this).attr('disabled',true);
          submitVideo($(this).val());
          $(this).attr('class',"btn btn-default btn-lg");
          $(this).html("<span class='glyphicon glyphicon-play'></span>");
          listQueue();
        });
    });
  }else{
    $("#searchResults").html("Enter Search term");

  }
  //event.preventDefault();
  return 0;
}

function listSearchResults(data){
  $("#searchResults").html("");

  var videos= data.items;
  for (var i=0;i<videos.length;i++){
    $('#searchResults').append("<tr>"+
      "<td>"+(i+1).toString()+"</td>"+
      "<td><button class='addVideo btn btn-default btn-lg' value='"+videos[i].id.videoId+"'><span class='glyphicon glyphicon-plus'></span></button></td>"+
      "<td><img src='"+videos[i].snippet.thumbnails.default.url+"'/></td>"+
      "<td>"+videos[i].snippet.title+"</td>"+
      "<td>"+videos[i].snippet.description+"</td>"+
      "</tr>");
  }
}

function createParty(){
  $.post("handler.php", $("#createPartyForm").serialize(),
    function(data){
      var result= JSON.parse(data);
      if(result['status']=='success'){
        window.location.href = "/party.php?partyId="+result['partyId'];
      }else{
        $("#problem").html(result['errors'][0]);
        listParties();
      }
    }
  );
  return false;
}

function joinParty(passedId) {
  $.post("handler.php", { 'action': 'joinParty', 'partyId': passedId },
    function (data) {
      var result = JSON.parse(data);
      if (result['status'] == 'success') {
        window.location.href = "/party.php?partyId="+passedId;
      }
      else {
        $("#problem").html(result['errors'][0]);
      }
    }
  );
}

function listQueue(){
  console.log("updating q");
  $(document).ready(function(){
    loadCurrentVideo();
    $.get("handler.php?action=listVideos&partyId="+partyId,
      function(data,status){
        var result= JSON.parse(data);
        if(result['status']=='success'){
          var videos = result['videos'];
          $("#queueList").html("");
          for (var i=0;i<videos.length;i++){
            upClass = "songUpvote btn btn-success";
            downClass = "songDownvote btn btn-danger";
            if(videos[i].userRating>0){
              upClass = "songUnvote btn btn-warning"
            }
            if(videos[i].userRating<0){
              downClass = "songUnvote btn btn-warning"
            }
            var queueRow="<tr><td>"+(i+1).toString()+"</td><td>"+videos[i].title+"</td><td>"+videos[i].username+"</td>"+
                "<td><div class='btn-group btn-group-sm'>"+
                    "<button class='"+upClass+"' value='"+videos[i].submissionId+"'>" +
                        "<span class='glyphicon glyphicon-thumbs-up'>" +
                    "</button>" +
                    "<button class='"+downClass+"' value='"+videos[i].submissionId+"'>" +
                        "<span class='glyphicon glyphicon-thumbs-down'>" +
                    "</button>";
            if((typeof isAdmin != 'undefined' && isAdmin==1) || userId == videos[i].submitterId){
              queueRow=queueRow+"<button class='removeVideo btn btn-default' value='"+videos[i].submissionId+"'>" +
                  "<span class='glyphicon glyphicon-remove'>" +
                "</button>";
            }
            queueRow=queueRow+"</div></td></tr>";
            $('#queueList').append(queueRow);
          }
          $('button.removeVideo').click(function(){
            $(this).attr('disabled',true);
            removeVideo($(this).val());
            listQueue();
            if($(this).val()==window.currentVideo.submissionId){
              if(typeof window.player != 'undefined'){
                window.player.stopVideo();
                window.player.clearVideo();
              }
              loadCurrentVideo();
            }
          });
          $('button.songUpvote').click(function(){
            $(this).attr('disabled',true);
            upVote($(this).val());
            listQueue();
          });
          $('button.songDownvote').click(function(){
            $(this).attr('disabled',true);
            downVote($(this).val());
            listQueue();
          });
          $('button.songUnvote').click(function(){
            $(this).attr('disabled',true);
            unVote($(this).val());
            listQueue();
          });
        }else{
          $("#problem").html(result['errors'][0]);
        }
    });
  });
}

function loadCurrentVideo(){
  $(document).ready(function(){
    $.get("handler.php?action=getCurrentVideo&partyId="+partyId,
      function(data,status){
        var result= JSON.parse(data);
        if(result['status']=='success'){
          var video = result['video'];
          if(video){
            if(typeof window.currentVideo == 'undefined' || window.currentVideo.submissionId != video.submissionId){
              if(typeof window.player != 'undefined' && typeof isAdmin != 'undefined' && isAdmin==1){
                $("#youtubePlayerParent").show();
                window.currentVideo=video;
                window.player.loadVideoById(video.youtubeId, 0);
              }else  if(typeof isAdmin == 'undefined' || isAdmin==0){
                window.currentVideo=video;
              }
              $('#currentThumbnail').attr("src", video.thumbnail);
              $('#viewOnYoutube').attr("href", "https://www.youtube.com/watch?v="+video.youtubeId);
              $('#currentTitle').html(video.title);

              $('#currentDescription').html(video.description);
            }
          }else{
            if(typeof window.player != 'undefined'){
              state = window.player.getPlayerState()
              if(state!=-1 && state!=0 && state!=5){
                //window.player.stopVideo();
                //window.player.clearVideo();
              }
            }
          }
        }else if(result['errors']){
          $("#problem").html(result['errors'][0]);
        }
      });
    });
}

function markVideoWatched(){
  $.post("handler.php", {'action':'markVideoWatched', 'submissionId':window.currentVideo.submissionId},
    function(data){
      var result= JSON.parse(data);
      if(result['status']!='success' && result['errors']){
        $("#problem").html(result['errors'][0]);
      }
    }
  );
}

function removeVideo(submissionId){
  $.post("handler.php", {'action':'removeVideo', 'submissionId':submissionId},
    function(data){
      var result= JSON.parse(data);
      if(result['status']!='success'){
        $("#problem").html(result['errors'][0]);
      }
    }
  );
}

function upVote(submissionId){
  $.post("handler.php", {'action':'vote', 'direction':1, 'submissionId':submissionId},
    function(data){
      var result= JSON.parse(data);
      if(result['status']!='success'){
        $("#problem").html(result['errors'][0]);
      }
    }
  );
}

function downVote(submissionId){
  $.post("handler.php", {'action':'vote', 'direction':-1, 'submissionId':submissionId},
    function(data){
      var result= JSON.parse(data);
      if(result['status']!='success'){
        $("#problem").html(result['errors'][0]);
      }
    }
  );
}

function unVote(submissionId){
  $.post("handler.php", {'action':'vote', 'direction':0, 'submissionId':submissionId},
    function(data){
      var result= JSON.parse(data);
      if(result['status']!='success'){
        $("#problem").html(result['errors'][0]);
      }
    }
  );
}

function getSubmissionId() {
  $(document).ready(function () {
    var submissionId = null;
    $.get("handler.php?action=getCurrentVideo&partyId=" + partyId,
      function (data, status) {
        var result = JSON.parse(data);
        if (result['status'] == 'success') {
          var video = result['video'];
          if (video) {
            submissionId = video.submissionId;
          }
        } else {
          $("#problem").html(result['errors'][0]);
        }
      }
    );
    return submissionId;
  });
}

function submitVideo(youtubeId){
  $.post("handler.php", {'action':'addVideo', 'partyId':partyId, 'youtubeId':youtubeId},
    function(data){
      var result= JSON.parse(data);
      if(result['status']!='success'){
        $("#problem").html(result['errors'][0]);
      }
    }
  );
}

function listParties(){
  if($("#joinedList").length >0){
    $.get("handler.php?action=listJoinedParties",
      function(data,status){
        var result= JSON.parse(data);
        if(result['status']=='success'){
          var parties= result['parties'];
          $("#joinedList").html("");
          for (var i=0;i<parties.length;i++){
            var row="<tr><td>"+parties[i].partyId+"</td><td><a href='/party.php?partyId="+parties[i].partyId+"'>"+parties[i].name+"</a></td><td>"+parties[i].username+"</td>";
            row=row+"</tr>"
            $('#joinedList').append(row);
          }
        }else{
          $("#problem").html(result['errors'][0]);
        }
      }
    );
  }

  if($("#unjoinedList").length >0){
    $.get("handler.php?action=listUnjoinedParties",
      function(data,status){
        var result= JSON.parse(data);
        if(result['status']=='success'){
          var parties= result['parties'];
          $("#unjoinedList").html("");
          for (var i=0;i<parties.length;i++){
            var row = "<tr><td>" + parties[i].partyId + "</td><td><a href='/party.php?partyId=" + parties[i].partyId + "'>" + parties[i].name + "</a></td><td>" + parties[i].username + "</td><td><button type='submit' class='btn btn-default btn-sm joinPartyButton' value=" + parties[i].partyId + ">Join</button></td>";
            row=row+"</tr>"
            $('#unjoinedList').append(row);
          }
          $('.joinPartyButton').click(function(){
            joinParty($(this).attr("value"));
          });
        }else{
          $("#problem").html(result['errors'][0]);
        }
      }
    );
  }
}

/**@license
This function uses Google Suggest for jQuery plugin (licensed under GPLv3) by Haochi Chen ( http://ihaochi.com )
 */
$.fn.googleSuggest = function(opts){
  opts = $.extend({service: 'web', secure: false}, opts);

  var services = {
    youtube: { client: 'youtube', ds: 'yt' },
    books: { client: 'books', ds: 'bo' },
    products: { client: 'products-cc', ds: 'sh' },
    news: { client: 'news-cc', ds: 'n' },
    images: { client: 'img', ds: 'i' },
    web: { client: 'psy', ds: '' },
    recipes: { client: 'psy', ds: 'r' }
  }, service = services[opts.service];

  opts.source = function(request, response){
    $.ajax({
      url: 'http'+(opts.secure?'s':'')+'://clients1.google.com/complete/search',
      dataType: 'jsonp',
      data: {
        q: request.term,
        nolabels: 't',
        client: service.client,
        ds: service.ds
      },
      success: function(data) {
        response($.map(data[1], function(item){
          return { value: $("<span>").html(item[0]).text() };
        }));
      }
    });
  };
  opts.delay = 50;
  opts.autoFocus = true;
  return this.each(function(){
    $(this).autocomplete(opts);
  });
}

function onYouTubePlayerReady(playerId){
  window.player = document.getElementById("youtubePlayer");
  window.player.addEventListener("onStateChange", "playerStateHandler");
  //$("#youtubePlayerParent").hide();
}

function playPause() {
  //Make Sure Player Is Initialized
  if (window.player) {
    if (window.player.getPlayerState() == 1){//Playing
      window.player.pauseVideo();
    }else if (window.player.getPlayerState() == 0||window.player.getPlayerState() == 2){//Paused
      window.player.playVideo();
    }
  }
}

function playerStateHandler(state){
  if (state==-1) {//unstarted
    loadCurrentVideo();
  }else if (state==0){//ended
    markVideoWatched();
    listQueue();
    loadCurrentVideo();
  }else if (state==1){//playing
    $("#playPause").html('<span class="glyphicon glyphicon-pause">');
  }else if(state==2){//paused
    $("#playPause").html('<span class="glyphicon glyphicon-play">');
  }else if(state==3){//buffering
  }else if(state==5){//video cued
  }
}

function setupYouTube(){
  var params = { allowScriptAccess: "always" , allowFullscreen: "true"};
  var atts = { id: "youtubePlayer" };
  //"Chromeless" Player
  //swfobject.embedSWF("http://www.youtube.com/apiplayer/?enablejsapi=1&version=3&playerapiid=youtubePlayerParent",

  swfobject.embedSWF("http://www.youtube.com/v/00000000000?version=3&enablejsapi=1&iv_load_policy=3&autohide=1&showinfo=0",
  "youtubePlayerParent", "100%", "250", "8", null, null, params, atts);

  //add "&modestbranding=1&autohide=1&showinfo=0&controls=0" to remove youtube bars and controls

  /* -- youtube flash object url addition explanations --
     "&iv_load_policy=3" disables annotations
     "&modestbranding=1" makes youtube logo smaller
     "&autohide=1" makes the top and bottom bars go away faster
     "&showinfo=0" removes the top bar
     "&controls=0" removes the bottom bar
  */

}

function fullScreenChangeHandler() {
  var elem = document.getElementById("youtubePlayer");
  if (elem && // if there is a youtube window.player and the page is in fullscreen mode
      (document.fullScreen == false) ||
      (document.msFullscreenEnabled == true) ||
      (document.mozFullScreen == false) ||
      (document.webkitIsFullScreen == false)
  ) {
    // IE 11 checks
    if (document.msFullscreenElement != null) {
      elem.style.width = screen.width + "px";
      elem.style.height = screen.height + "px";
    }
    else {
      elem.style.width = "100%";
      elem.style.height = "645px";
    }

    // removes listeners
    document.removeEventListener("fullscreenchange", fullScreenChangeHandler, false);
    //document.removeEventListener("MSFullscreenChange", fullScreenChangeHandler, false);
    document.removeEventListener("mozfullscreenchange", fullScreenChangeHandler, false);
    document.removeEventListener("webkitfullscreenchange", fullScreenChangeHandler, false);
  }
}

function fullScreen() {
  var elem = document.getElementById("youtubePlayer");
  var fullScreenPossible = false;
  var fullScreenSupported = true;
  var ieEventListenerExists;

  // Disables external fullscreen button for operating systems on which firefox doesn't work

  if( window.mozInnerScreenX != null &&
      (
        navigator.appVersion.indexOf("Linux") != -1 ||
        navigator.appVersion.indexOf("X11") != -1 ||
        (navigator.appVersion.indexOf("Windows") != -1 && navigator.appVersion.indexOf("Windows") > 5) // Windows 8
      )
  ) {
    fullScreenSupported = false;
    var fullScreenButton = document.getElementById("fullScreen");
    fullScreenButton.className += " disabled";
  }

  if (fullScreenSupported) {
    if (elem.requestFullscreen) {
      elem.requestFullscreen();
      fullScreenPossible = true;
    }
    else if (elem.msRequestFullscreen) {
      elem.msRequestFullscreen();
      fullScreenPossible = true;
    }
    else if (elem.mozRequestFullScreen) {
      elem.mozRequestFullScreen();
      fullScreenPossible = true;
    }
    else if (elem.webkitRequestFullscreen) {
      elem.webkitRequestFullscreen();
      fullScreenPossible = true;
    }

    if (fullScreenPossible) {
      // makes the youtube window.player the screen's size
      elem.style.width = screen.width + "px";
      elem.style.height = screen.height + "px";

      document.addEventListener("fullscreenchange", fullScreenChangeHandler, false);
      if (ieEventListenerExists != true) {
        document.addEventListener("MSFullscreenChange", fullScreenChangeHandler, false);
        ieEventListenerExists = true;
      }
      document.addEventListener("mozfullscreenchange", fullScreenChangeHandler, false);
      document.addEventListener("webkitfullscreenchange", fullScreenChangeHandler, false);
    }
  }

  else {
    // adds notification that browser/OS combo is not supported
    document.getElementById("disabledFullScreen").style.display = "block";
    $("#disabledFullScreen").children().click(function () { $("#disabledFullScreen").hide(); })
  }
  /* external fullscreen is still partially broken in older versions
     of IE and Safari but should now work in firefox, chrome, IE 11, and Safari 7+.
     because of this leave the normal youtube controls on for now
  */
}

function submitContentToggle() {
  if ($('.submitContent').is(':visible')) {
    $('.submitContent').hide();
    $('.container').css('margin-left', 'auto');
    $('.container').css('width', '100%');
    $('#submitContentToggle').html('<img class="icon" src="images/search.png" />');
  }
  else {
    $('.submitContent').show();
    $('.container').css('margin-left', 0);
    $('.container').css('width', '50%');
    $('#submitContentToggle').html('<img class="icon" src="images/close.png" />');
  }
}

function QRCodeToggle() {
  if ($('#qrcode').is(':visible')) {
    $('#qrcode').hide();
  }
  else {
    $('#qrcode').show();
  }
}

$(document).ready(function(){
  var mql = window.matchMedia("screen and (max-width: 992px)"); //mobile if wql.matches evaluates to true

  $('#thumbUp').click(function(){
    upVote(window.currentVideo.submissionId);
  });
  $('#thumbDown').click(function(){
    downVote(window.currentVideo.submissionId);
  });
  if ($("#youtubePlayerParent").length > 0){
    setupYouTube();
    $("#playPause").click(playPause);
    $("#fullScreen").click(fullScreen);
  }
  var listQueueTimer=window.setInterval(listQueue, 5000);
  $("#searchText").googleSuggest({ service: "youtube" });
  $('#register').submit(register);
  $('#login').submit(login);
  $('#createPartyForm').submit(createParty);
  $('#submitContentToggle').click(submitContentToggle);
  $('#qrcodetoggle').click(QRCodeToggle);

  if ($("#queueList").length > 0){
    listQueue();
  }

  if ($("#unjoinedList").length > 0){
    listParties();
  }
});

(function($) {
    $.fn.toggleDisabled = function(){
        return this.each(function(){
            this.disabled = !this.disabled;
        });
    };
})(jQuery);