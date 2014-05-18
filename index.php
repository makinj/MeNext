<?php
  $title="index";
  require_once('header.php');//bar at the top of the page
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
