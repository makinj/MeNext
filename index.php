<?php
  $title="index";
  require_once('header.php');//bar at the top of the page
?>
    <div class="panel panel-default">
      <form id="createPartyForm">
        <div class="form-group">
          <input type="text" id="createPartyName" class="form-control" name="name" placeholder="Party Name">
        </div>
        <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-plus"></span></button>
        <input type="hidden" name="action" value='createParty'>
      </form>
    </div>
<<<<<<< HEAD
=======

<?php
  }
?>
>>>>>>> 0332500f9fd25d2862ed33f524b994e14a9a7279
    <div class="panel panel-default">
      <div class="panel-heading">Joined Parties</div>
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Creator:</th>
          </tr>
        </thead>
        <tbody id="joinedList">
        </tbody>
      </table>
    </div>
<<<<<<< HEAD
    <div class="panel panel-default">
      <div class="panel-heading">Unjoined Parties</div>
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Creator:</th>
          </tr>
        </thead>
        <tbody id="unjoinedList">
        </tbody>
      </table>
    </div>

=======
  </div>
  <!-- end of youtube player and queuelist
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
        <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-search"></span></button>
      </form>

      <div class="panel panel-default" id="searchTableHeader">
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

    <!--THIS IS THE OLD METHOD
      <iframe src="submit.php" class="submitContent"></iframe>
      -->
    <button type="button" id="submitContentToggle" class="btn btn-default btn-lg"><span class="glyphicon glyphicon-search"></span></button>

  <?php
    }
  ?>

>>>>>>> 0332500f9fd25d2862ed33f524b994e14a9a7279
<?php
  require_once('footer.php');//bar at the top of the page
?>
