<?php

$title = "index";
require_once('header.php'); //bar at the top of the page
require_once("includes/functions.php"); //basic database operations

$partyId = -1;
$party = new Party($db);
$isOwner = 0;
if (isset($_GET['partyId'])) {
    $partyId = $_GET['partyId'];
    $party = new Party($db, $partyId);
    $isOwner = $party->isPartyOwner($user);
}
$partyData = [];
if (isset($_GET['partyId'])) {
    $partyData = $party->getPartyObject();
}
?>
<!-- beginning of youtube player and queuelist -->
<div class="row">
    <div class="col-md-4">
        <h2><?php echo $partyData->partyName; ?> <span
                class="text-muted small">by <?php echo $partyData->ownerUsername; ?></span></h2>
        <h4><span class="text-muted small">Playing:</span><br/><span id='currentTitle'></span></h4>
        <?php
        if ($isOwner) {
            ?>
            <!-- SWFObject to Verify Flash Version -->
            <script type='text/javascript' src='js/swfobject.js'></script>
            <script type="text/javascript">
                var isAdmin = 1;
            </script>

            <div id='youtubePlayerParent'>
                You need Flash player 8+ and JavaScript enabled to view this video.
            </div>

            <div class="btn-group btn-group-justified btn-group-lg" id="controls">
                <!--<div class="btn-group btn-group-lg">
                    <button type="button" id="thumbDown" class="btn btn-danger">
                        <span class="glyphicon glyphicon-thumbs-down"></span>
                    </button>
                </div>-->
                <div class="btn-group btn-group-lg">
                    <button type="button" id="playPause" class="btn btn-default">
                        <span class="glyphicon glyphicon-play"></span>
                    </button>
                </div>
                <!--<div class="btn-group btn-group-lg">
                    <button type="button" id="thumbUp" class="btn btn-success">
                        <span class="glyphicon glyphicon-thumbs-up"></span>
                    </button>
                </div>-->
            </div>
        <?php
        } else {
            ?>
            <img id='currentThumbnail' class='pull-left' src=""/>
            <p id="currentDescription"></p>

            <!--<div class="btn-group btn-group-justified btn-group-lg" id="controls">
                <div class="btn-group btn-group-lg">
                    <button type="button" id="thumbDown" class="btn btn-danger">
                        <span class="glyphicon glyphicon-thumbs-down"></span>
                    </button>
                </div>
                <div class="btn-group btn-group-lg">
                    <button type="button" id="thumbUp" class="btn btn-success">
                        <span class="glyphicon glyphicon-thumbs-up"></span>
                    </button>
                </div>
            </div>-->
        <?php
        }
        ?>
        <br/>

        <div class="row">
            <div class="col-lg-6">
                <div id="qrcode"></div>
                <br/>
                <script type="text/javascript">
                    new QRCode(document.getElementById("qrcode"), {
                            text: document.URL,
                            width: 150,
                            height: 150,
                            colorLight: "#ffffff"
                        }
                    );
                </script>
            </div>
            <div class="col-lg-6">
                <a class="btn btn-default btn-block" id="viewOnYoutube" target="_blank" href="">View on YouTube</a>
                <a class="btn btn-default btn-block" target="_blank"
                   href="https://docs.google.com/forms/d/1fy-vD3ovTfs4iekNbgE3viobHvvusD8ODunL_v2zks8/viewform?<?php if($user->logged){echo "entry.1934380623=".$user->username."&";} ?>entry.1987106882">Report
                    a Bug</a>
                <a href="https://play.google.com/store/apps/details?id=me.menext.menext">
                  <img alt="Get it on Google Play"
                       src="https://developer.android.com/images/brand/en_generic_rgb_wo_45.png" />
                </a>
                <a href="https://itunes.apple.com/us/app/menext/id934530773?mt=8&uo=4" target="itunes_store" style="display:inline-block;overflow:hidden;background:url(https://linkmaker.itunes.apple.com/htmlResources/assets/en_us//images/web/linkmaker/badge_appstore-lrg.png) no-repeat;width:135px;height:40px;@media only screen{background-image:url(https://linkmaker.itunes.apple.com/htmlResources/assets/en_us//images/web/linkmaker/badge_appstore-lrg.svg);}"></a>
            </div>
        </div>
    </div>

    <?php
    $writeParty = 0;
    $readParty = 0;
    if ($partyId >= 0) { // then $partyId must be set from above
        $writeParty = $party->canWriteParty($user);
        $readParty = $party->canReadParty($user);
    }
    if ($readParty) {
        ?>
        <div class="col-md-8">
            <h3>Song Queue</h3>
            <table class="table table-striped table-hover" id="queueTable">
                <thead>
                <tr id="queueHeader">
                    <th>#</th>
                    <th>Title</th>
                    <th>Submitted by:</th>
                    <th></th>
                    <th>Votes</th>
                </tr>
                </thead>
                <tbody id="queueList">
                </tbody>
            </table>

        </div>

        <!--
             end of youtube player and queuelist
             beginning of search content
        -->

    <?php
    } else {
        ?>
        <div class="col-md-8">
            <button type='submit' class='joinPartyButton btn btn-lg btn-primary' value="<?php echo $partyId; ?>">Join
            </button>
        </div>
    <?php
    }
    if ($writeParty) {
// makes sure user is a member of the party

        ?>

        <div class="col-md-8">
            <br/>

            <div class="row">
                <div class="col-md-6">
                    <h3>Add a Song</h3><br/>
                </div>
                <div class="col-md-6">
                    <form id="searchForm" class="pull-right" role="search" method="get">
                        <script type="text/javascript">
                            // Forces only the required div to be reloaded
                            $('#searchForm').submit(function (onSubmitClick) {
                                onSubmitClick.preventDefault(); //prevents default submit
                                searchYouTube(); //puts the search query through and writes out to #searchResults
                                return false; //prevents page reload
                            });
                        </script>
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchText" name="q" placeholder="Search">
                <span class="input-group-btn">
                    <button class="btn btn-primary search" type="submit"><span
                            class="glyphicon glyphicon-search"></span></button>
                </span>
                        </div>
                    </form>
                </div>
            </div>

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
    <?php
    }

    require_once('footer.php'); //bar at the top of the page
    ?>
