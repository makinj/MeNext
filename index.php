<?php
$title = "Home";
require_once('includes/constants.php');
require_once('includes/functions.php');
require_once('header.php'); //bar at the top of the page

if($user->logged){
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
                    <th></th>
                </tr>
                </thead>
                <tbody id="joinedList">
                    <?php
                        $joined = $user->listJoinedParties();
                        for ($i=0; $i < count($joined); $i++) {
                            echo "<tr><td>".$joined[$i]->partyId."</td><td><a href='/party.php?partyId=".$joined[$i]->partyId."'>".$joined[$i]->name."</a></td><td>".$joined[$i]->username."</td><td>";
                            if ($joined[$i]->isOwner){
                                echo "<button type='submit' class='btn btn-default btn-sm deletePartyButton' value=".$joined[$i]->partyId.">Delete</button>";
                            }
                            echo "</td></tr>";
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
                        $unjoined = $user->listUnjoinedParties();
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
    <p>Have you ever been to a social gathering and wanted to share cool music with the group without taking over the music entirely? With MeNext, this is possible! MeNext enables users to submit their own suggestions to a playlist at a party. Other party-goers can then vote to decide what gets played and when.</p>
    <p>
        <a href="https://play.google.com/store/apps/details?id=me.menext.menext">
          <img alt="Get it on Google Play"
               src="https://developer.android.com/images/brand/en_generic_rgb_wo_45.png" />
    </p>
    <p>
        </a>
        <a href="https://itunes.apple.com/us/app/menext/id934530773?mt=8&uo=4" target="itunes_store" style="display:inline-block;overflow:hidden;background:url(https://linkmaker.itunes.apple.com/htmlResources/assets/en_us//images/web/linkmaker/badge_appstore-lrg.png) no-repeat;width:135px;height:40px;@media only screen{background-image:url(https://linkmaker.itunes.apple.com/htmlResources/assets/en_us//images/web/linkmaker/badge_appstore-lrg.svg);}"></a>
    </p>
</div>

<?php
}

require_once('footer.php'); //bar at the top of the page
?>
