/*
Joshua Makinen
Calls AJAX to retreive search result
*/
$(document).ready(function(){
  $("#search").keyup(function(){
    if($(this).val()!=""){
      $.get("https://www.googleapis.com/youtube/v3/search"+
        "?part=snippet"+//don't question this part
        "&order=relevance"+//sort by relevance
        "&type=video"+//only show videos(not channels or playlists)
        "&maxResults=25"+//up to 25 results
        "&q="+$(this).val()+//query with search term
        "&key="+API_KEY,//Client API key
        
        function(data,status){
          if (status=="success"){
            document.getElementById("searchResults").innerHTML="";
            var videos= data.items;
            for (var i=0;i<videos.length;i++){
              var link=document.createElement("LI");
              link.innerHTML=(i+1).toString()+"<button class='addSong' value='"+videos[i].id.videoId+"'>Add</button><a href='https://www.youtube.com/watch?v="+videos[i].id.videoId+"' target='_blank'>"+"<img src='"+videos[i].snippet.thumbnails.default.url+"'/>"+videos[i].snippet.title+"</a>";
              document.getElementById('searchResults').appendChild(link); 
            }
          }else{
            document.getElementById("searchResults").innerHTML="Failed :(";
          }
          $('button.addSong').click(function(){
            $.post("handler.php", {'action':'addSong', 'youtubeId':$(this).val()},
              function(data){
                
              }
            );
          });
      });
    }else{
      document.getElementById("searchResults").innerHTML="";
    }
  });
});