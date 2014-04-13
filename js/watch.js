function listSongs(){
  $(document).ready(function(){
    $.get("handler.php?action=listSongs",        
      function(data,status){
        if (status=="success"){
          alert(data);
          /*
          var songs= JSON.parse(data);
          //var users=data;         
          document.getElementById("songList").innerHTML="";
          for (var i=0;i<users.length;i++){
            var song=document.createElement("LI");
            song.innerHTML=(i+1).toString()+" "+songss[i].title;
            document.getElementById('songList').appendChild(song); 
          }
          */
        }else{
          document.getElementById("songList").innerHTML="Failed :(";
        }
      }
    );
  });
}
$(document).ready(function(){
  listSongs();
});