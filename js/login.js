$(document).ready(function(){
  $('#register').submit(function(){
    $.post("handler.php", $("#register").serialize(),
      function(data){
        if(data=="alreadyExists"){
          
        }
      });
    return false;
  });
});