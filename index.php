<?php
$title = (isset($GLOBALS['logged']) ? "Dashboard" : "Home");
require_once('header.php'); //bar at the top of the page
require_once('includes/constants.php');
require_once('includes/functions.php');
if(isset($GLOBALS['logged'])){
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
                <tbody id="joinedList">
                    <?php
                        $joined = listJoinedParties($db)['parties'];
                        for ($i=0; $i < count($joined); $i++) {
                            echo "<tr><td>".$joined[$i]->partyId."</td><td><a href='/party.php?partyId=".$joined[$i]->partyId."'>".$joined[$i]->name."</a></td><td>".$joined[$i]->username."</td></tr>";
                        }
                    ?>
                </tbody>
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
                <tbody id="unjoinedList">
                    <?php
                        $unjoined = listUnjoinedParties($db)['parties'];
                        for ($i=0; $i < count($unjoined); $i++) {
                            echo "<tr><td>".$unjoined[$i]->partyId."</td><td><a href='/party.php?partyId=".$unjoined[$i]->partyId."'>".$unjoined[$i]->name."</a></td><td>".$unjoined[$i]->username."</td><td><button type='submit' class='btn btn-default btn-sm joinPartyButton' value=".$unjoined[$i]->partyId.">Join</button></td></tr>";
                        }
                    ?>
                </tbody>
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
