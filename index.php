<?php
  $title="index";
  require_once('header.php');//bar at the top of the page
  if(session_id() == '') {
    session_start();
  }
  if(isset($_SESSION['admin'])&&$_SESSION['admin']==True){
?>

    <!--SWFObject to Verify Flash Version-->
    <script type='text/javascript' src='js/swfobject.js'></script>
    <div id='youtubePlayer'>
      You need Flash player 8+ and JavaScript enabled to view this video.
    </div>

  </br>
    <div class="btn-group">
      <button type="button" id="thumbDown" class="btn btn-default btn-lg"><span class="glyphicon glyphicon-thumbs-down"></span></button>
      <button type="button" id="playPause" class="btn btn-default btn-lg"><span class="glyphicon glyphicon-play"></span></button>
      <button type="button" id="thumbUp" class="btn btn-default btn-lg"><span class="glyphicon glyphicon-thumbs-up"></span></button>
    </div>

<?php
  
  }
?>
  <div class="panel panel-default">
    <div class="panel-heading">Song Queue</div>
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Title</th>
          <th>Submitted by:</th>
        </tr>
      </thead>
      <tbody id="queueList">
      </tbody>
      
    </table>
  </div>

  <a href='reset.php'>reset</a>
<?php
  require_once('footer.php');//bar at the top of the page
?>
