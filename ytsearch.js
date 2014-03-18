function searchVideo(){
  /*
  Joshua Makinen
  Calls AJAX to retreive search result
  */
  var search_term=document.getElementById("search").value;
  if (search_term.length==0){
    document.getElementById("list").innerHTML="<h5>Enter video to search</h5>";
    return false;
  }else{
    var xmlhttp=new XMLHttpRequest();
    xmlhttp.onreadystatechange=function(){
      if (xmlhttp.readyState==4 && xmlhttp.status==200){
        document.getElementById("list").innerHTML=xmlhttp.responseText;
      }else{
        document.getElementById("list").innerHTML="Loading...";
      }
    }
    xmlhttp.open("GET","includes/ytfunctions.php?action=search&search="+search_term,true);
    xmlhttp.send();
  }
  return false;
}