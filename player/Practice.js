//JavaScript YouTube Player Implementation
var params = { allowScriptAccess: "always" };
var atts = { id: "myytplayer" };

//"Chromeless" Player
swfobject.embedSWF("http://www.youtube.com/apiplayer/?enablejsapi=1&version=3&playerapiid=ytplayer",
"ytapiplayer", "560", "315", "8", null, null, params, atts);


//Get Player Reference
function onYouTubePlayerReady(playerId)
{
    player = document.getElementById("myytplayer");
    player.loadVideoById('FT7MWDoW_rc', 15);
}

//Voting Variables
var ups = 0, downs = 0;

//Voting Functions
function downVideo()
{
    downs++;
}

function upVideo()
{
    ups++;
}

//Player Functions
function playpause() {
    //Make Sure Player Is Initialized
    if (player) {
        if (player.getPlayerState() == 1)//Playing
        {
            player.pauseVideo();
        }
        else if (player.getPlayerState() == 2)//Paused
        {
            player.playVideo();
        }
    }
    else
    {
        document.write("YOU FUCKED UP");
    }
}