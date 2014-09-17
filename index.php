<?php
$title = (isset($_SESSION['logged']) ? "Dashboard" : "Home");
require_once('header.php'); //bar at the top of the page
require_once('includes/constants.php');
if(isset($_SESSION['logged'])){
?>
<div class="row">
    <div class="col-md-8">
        <h3>Party Dashboard</h3>
    </div>
    <div class="col-md-4">
        <div class="partyCreateWrapper">
            <form id="createPartyForm">
                <div class="input-group form-group" id="createPartyNameWrapper">
                    <input type="text" id="createPartyName" class="form-control" name="name" placeholder="Party Name">
                    <span class="input-group-btn">
                        <button type="submit" class="btn btn-primary" id="submitPartyName">
                            Create
                        </button>
                    </span>
                </div>
                <input type="hidden" name="action" value='createParty'>
            </form>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="joinedPartiesWrapper">
            <div class="joinedPartiesHeading"><h4>Joined Parties</h4></div>
            <table class="table table-hover table-striped">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Owner:</th>
                </tr>
                </thead>
                <tbody id="joinedList"></tbody>
            </table>
        </div>
        <hr/>
        <div class="joinedPartiesWrapper">
            <h4>Unjoined Parties</h4>
            <table class="table table-hover table-striped" id="unjoinedPartiesTable">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Owner:</th>
                    <th></th>
                </tr>
                </thead>
                <tbody id="unjoinedList"></tbody>
            </table>
        </div>
    </div>
</div>

<?php
} else {
?>

<div class="jumbotron">
    <h1>MeNext</h1>
    <h2>A Music Request Service with a Hint of Democracy</h2>
    <p>Description to be included here</p>
</div>

<?php
}

require_once('footer.php'); //bar at the top of the page
?>
