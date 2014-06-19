<?php
  $title="index";
  require_once('header.php');//bar at the top of the page
  if(session_id() == '') {
    session_start();
  }
  if(isset($_SESSION['admin'])&&$_SESSION['admin']==True){
?>
  <!-- beginning of youtube player and queuelist -->
  <div class="mainPageContent">
    <!--SWFObject to Verify Flash Version-->
    <script type='text/javascript' src='js/swfobject.js'></script>
    <script type="text/javascript">
      var isAdmin=1;
    </script>

    <div id='youtubePlayerParent'>
      You need Flash player 8+ and JavaScript enabled to view this video.
    </div>

    <div id="disabledFullScreen">HTML5 fullscreen and firefox don't mix well with your operating system. We recommend Google Chrome.
      <button type="button" id="closeAlert" class="btn btn-"><span class="glyphicon glyphicon-remove"></span></button>
    </div>
    <div class="btn-group">
      <button type="button" id="thumbDown" class="btn btn-default btn-lg"><span class="glyphicon glyphicon-thumbs-down"></span></button>
      <button type="button" id="playPause" class="btn btn-default btn-lg"><span class="glyphicon glyphicon-play"></span></button>
      <button type="button" id="thumbUp" class="btn btn-default btn-lg"><span class="glyphicon glyphicon-thumbs-up"></span></button>
      <button type="button" id="fullScreen" class="btn btn-default btn-lg"><span class="glyphicon glyphicon-fullscreen"></span></button>
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
            <?php
              if(isset($_SESSION['admin'])&&$_SESSION['admin']==True){
                echo"<th>Remove</th>";
              }
            ?>
          </tr>
        </thead>
        <tbody id="queueList">
        </tbody>
      
      </table>
    </div>
  </div>
  <!-- end of youtube player and queuelist
       beginning of search content
  -->

  <?php
    if(isset($_SESSION['logged'])){
  ?>

    <iframe src="submit.php" class="submitContent"></iframe>
    <button type="button" id="submitContentToggle" class="btn btn-default btn-lg"><span class="glyphicon glyphicon-search"></span></button>

  <?php
    }
  ?>

<?php
  require_once('footer.php');//bar at the top of the page
?>
