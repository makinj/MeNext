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

<?php
  require_once('footer.php');//bar at the top of the page
?>
