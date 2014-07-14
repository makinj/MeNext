<?php
  $title="index";
  require_once('header.php');//bar at the top of the page
  
  if(isset($_SESSION['logged'])){
?>
  <div class="partyCreateWrapper">
    <form id="createPartyForm">
      <div class="form-group" id="createPartyNameWrapper">
        <input type="text" id="createPartyName" class="form-control" name="name" placeholder="Party Name">
      </div>
      <button type="submit" class="button" id="submitPartyName"><img class="icon addPartyButton" src="images/plus.png" /></button>
      <input type="hidden" name="action" value='createParty'>
    </form>
  </div>
  <div class="joinedPartiesWrapper">
    <div class="joinedPartiesHeading">Joined Parties</div>
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Owner:</th>
        </tr>
      </thead>
      <tbody id="joinedList">
      </tbody>
    </table>
  </div>
  <div class="joinedPartiesWrapper">
    <div class="joinedPartiesHeading">Unjoined Parties</div>
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Owner:</th>
        </tr>
      </thead>
      <tbody id="unjoinedList">
      </tbody>
    </table>
  </div>
<?php
  } else {
?>

  <div class="welcomeText">
    Make an account to get started!
  </div>

<?php
  }
?>

<?php
  require_once('footer.php');//bar at the top of the page
?>
