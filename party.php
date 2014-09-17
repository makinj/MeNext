<?php
$title = "index";
require_once('header.php'); //bar at the top of the page
require_once("includes/functions.php"); //basic database operations
if (session_id() == '') {
    session_start();
}

$db = connectDb(); //connect to mysql

$partyId = -1;
$isOwner = 0;
if (isset($_GET['partyId'])) {
    $partyId = $_GET['partyId'];
    $isOwner = isPartyOwner($db, $partyId);
}
if ($isOwner){
?>
<!-- beginning of youtube player and queuelist -->
<div class="row">
<div class="col-md-4">
    <h2>Party Name <span class="text-muted small">by author</span></h2>
    <!-- SWFObject to Verify Flash Version -->
    <script type='text/javascript' src='js/swfobject.js'></script>
    <script type="text/javascript">
        var isAdmin = 1;
    </script>

    <div id='youtubePlayerParent'>
        You need Flash player 8+ and JavaScript enabled to view this video.
    </div>

    <!--<div id="disabledFullScreen">HTML5 fullscreen and firefox don't mix well with your operating system. We
        recommend
        Google Chrome.
        <button type="button" id="closeAlert" class="btn"><span class="glyphicon glyphicon-remove"></span></button>
    </div>-->

    <div class="btn-group btn-group-justified btn-group-lg" style="width:100%;" id="controls">
        <div class="btn-group btn-group-lg">
            <button type="button" id="thumbDown" class="btn btn-danger">
                <span class="glyphicon glyphicon-thumbs-down"></span>
            </button>
        </div>
        <div class="btn-group btn-group-lg">
            <button type="button" id="playPause" class="btn btn-default">
                <span class="glyphicon glyphicon-play"></span>
            </button>
        </div>
        <div class="btn-group btn-group-lg">
            <button type="button" id="thumbUp" class="btn btn-success">
                <span class="glyphicon glyphicon-thumbs-up"></span>
            </button>
        </div>
    </div>
    <br/>

    <div class="row">
        <div class="col-lg-6">
            <div id="qrcode"></div>
            <br />
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
            <button class="btn btn-default btn-block">Report a Bug</button>
        </div>
    </div>
</div>

<?php

}
$writeParty = 0;
$readParty = 0;
if ($partyId >= 0) { // then $partyId must be set from above
    $writeParty = canWriteParty($db, $partyId);
    $readParty = canReadParty($db, $partyId);
}
if ($readParty){
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
        <button type='submit' class='joinPartyButton btn btn-lg btn-primary' value="<?php echo $partyId; ?>">Join</button>
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
