function listvideos(){
  $(document).ready(function(){
    $.get("handler.php?action=listVideos",        
      function(data,status){
        if (status=="success"){
          
          var videos= JSON.parse(data);
          //var users=data;         
          document.getElementById("videoList").innerHTML="";
          for (var i=0;i<videos.length;i++){
            var video=document.createElement("LI");
            video.innerHTML=(i+1).toString()+" "+videos[i].title;
            document.getElementById('videoList').appendChild(video); 
          }
          
        }else{
          document.getElementById("videoList").innerHTML="Failed :(";
        }
      }
    );
  });
}
$(document).ready(function(){
  listvideos();
});