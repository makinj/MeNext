<?php
  $title="index";
  require_once('header.php');//bar at the top of the page
  require_once("includes/functions.php");//basic database operations
  if(session_id() == '') {
    session_start();
  }

  $db = connectDb();//connect to mysql

  $partyId = -1;
  $isOwner = 0;
  if(isset($_GET['partyId'])){
    $partyId = $_GET['partyId'];
    $isOwner = isPartyOwner($db, $partyId);
  }
  if($isOwner){
?>
 <!-- beginning of youtube player and queuelist -->
  <div class="mainPageContent">
    <!-- SWFObject to Verify Flash Version -->
    <script type='text/javascript' src='js/swfobject.js'></script>
    <script type="text/javascript">
      var isAdmin=1;
    </script>

    <div id='youtubePlayerParent'>
      You need Flash player 8+ and JavaScript enabled to view this video.
    </div>

    <div id="disabledFullScreen">HTML5 fullscreen and firefox don't mix well with your operating system. We recommend Google Chrome.
      <button type="button" id="closeAlert" class="btn"><span class="glyphicon glyphicon-remove"></span></button>
    </div>
    
    <div class="row clear" id="controls">
      <button type="button" id="thumbDown" class="button col col-3 tablet-col-9 mobile-col-1-2"><span class="glyphicon glyphicon-thumbs-down"></span></button>
      <button type="button" id="playPause" class="button col col-3 tablet-col-9 mobile-col-1-2"><span class="glyphicon glyphicon-play"></span></button>
      <button type="button" id="thumbUp" class="button col col-3 tablet-col-9 mobile-col-1-2"><span class="glyphicon glyphicon-thumbs-up"></span></button>
      <button type="button" id="fullScreen" class="button col col-3 tablet-col-9 mobile-col-1-2"><span class="glyphicon glyphicon-fullscreen"></span></button>
    </div>

<?php

  }
?>
    <div>
      <div>Song Queue</div>
      <table class="table no-border">
        <thead>
          <tr id="queueHeader">
            <th>#</th>
            <th>Title</th>
            <th>Submitted by:</th>
            <th></th>
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
  <!-- 
       end of youtube player and queuelist
       beginning of search content
  -->

  <?php
    if(isset($_SESSION['logged'])){
  ?>

    <div class="submitContent">
      <div class="spacer"></div>
      <form id="searchForm" class="navbar-form navbar-left" role="search" method="get">
        <script type="text/javascript">
          // Forces only the required div to be reloaded
          $('#searchForm').submit(function(onSubmitClick){
            onSubmitClick.preventDefault(); //prevents default submit
            searchYouTube(); //puts the search query through and writes out to #searchResults
            return false; //prevents page reload
          });
        </script>
        <div class="form-group">
          <input type="text" id="searchText" name="q" class="form-control" placeholder="Search">
        </div>
        <button type="submit" class="button search"><span class="glyphicon glyphicon-search"></span></button>
      </form>

      <div id="searchTableHeader">
        <table class="table">
          <thead>
            <tr>
              <th></th>
              <th></th>
              <th></th>
              <th>Title</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody id="searchResults">
          </tbody>
        </table>
      </div>
    </div>
    <div id="submitContentToggleContainer">
      <button type="button" id="submitContentToggle" class="button"></button>
    </div>
  <?php
    }
  ?>

<?php
  require_once('footer.php');//bar at the top of the page
?>
