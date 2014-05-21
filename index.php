<?php
  $title="index";
  require_once('header.php');//bar at the top of the page
  if(session_id() == '') {
    session_start();
  }
  if(isset($_SESSION['admin'])&&$_SESSION['admin']==True){
    
    echo "<!--SWFObject to Verify Flash Version-->
        <script type='text/javascript' src='js/swfobject.js'></script>
        <div id='youtubePlayer'>
            You need Flash player 8+ and JavaScript enabled to view this video.
        </div>";
  }
?>
  <div class="panel panel-default">
    <div class="panel-heading">Song Queue</div>
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Title</th>
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
