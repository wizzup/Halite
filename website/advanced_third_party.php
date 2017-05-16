<html lang="en">
<head>
    <?php include 'includes/header.php'; ?>

    <title>Third Party Resources</title>

    <link href="lib/bootstrap.min.css" rel="stylesheet">
    <link href="style/general.css" rel="stylesheet">
    <link href="style/learn.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <?php include 'includes/navbar.php'; ?>
        <div class="row">
            <?php include 'includes/learn_sidebar.php'; ?>
            <div class="col-sm-9">
                <h1>Third Party Resources</h1>

                <p>Below is a list of useful and/or fun resources that were created by the Halite community. Especially popular resources will be <span class="glyphicon glyphicon-star" aria-hidden="true"></span>'ed.</p>

                <h3>Tutorials</h3>
                <ul>
                   <li><a href="http://forums.halite.io/t/ml-starter-bot-tutorial/616">Machine Learning Starter Bot by brianvanleeuwen</a> <span class="glyphicon glyphicon-star" aria-hidden="true"></span></li> 
                   <li><a href="http://forums.halite.io/t/running-your-halite-bot-from-an-ide/70">Running Your Halite Bot from an IDE by the Halite team</a></li> 
                   <li><a href="http://forums.halite.io/t/so-youve-improved-the-random-bot-now-what/482">So you've Improved the Random Bot. Now what? by nmalaguti</a> <span class="glyphicon glyphicon-star" aria-hidden="true"></span></li>
                   <li><a href="http://forums.halite.io/t/building-a-good-ml-bot/776">Building a Good Machine Learning Bot by KalraA</a></li>
                </ul>

                <h3>Strategy Writeups</h3>
                <ul>
                   <li><a href="http://forums.halite.io/t/early-mid-and-late-game/703">Early, Mid, and Late Game Strategy by nmalaguti</a> <span class="glyphicon glyphicon-star" aria-hidden="true"></span></li> 
                   <li><a href="http://forums.halite.io/t/how-your-starting-position-impacts-your-bots-performance/769">How Your Starting Position Impacts Your Bot's Performance by nmalaguti</a></li> 
                   <li><a href="http://forums.halite.io/t/perimeter-optimization-during-expansion/723">Perimeter Optimization by Sydriax</a></li> 
                </ul>

                <h3>Tools</h3>
                <ul>
                   <li><a href="http://forums.halite.io/t/cloudbots-compete-on-demand-against-some-of-my-bots/725">Cloudbots: locally test your bot against nmalaguti's</a></li> 
                   <li><a href="http://forums.halite.io/t/halite-swig-wrapper-for-the-game-engine/550">Environment: halite environment SWIG wrapper</a></li> 
                   <li><a href="http://forums.halite.io/t/unofficial-halite-engine-clone-reloader/582">Environment: multi-featured game environment clone</a></li> 
                   <li><a href="http://forums.halite.io/t/unofficial-match-manager-for-local-testing/505">Local tournament manager</a> <span class="glyphicon glyphicon-star" aria-hidden="true"></span></li> 
                   <li><a href="http://forums.halite.io/t/visualizer-with-more-graphs-aka-experimental-visualizer/771">Visualizer: web visualizer with aws s3 hosting, extra stats, and easy linking</a> <span class="glyphicon glyphicon-star" aria-hidden="true"></span></li> 
                   <li><a href="http://forums.halite.io/t/a-stand-alone-game-viewer/615">Visualizer: local 3D visualizer</a></li> 
                   <li><a href="http://forums.halite.io/t/auto-visualization-of-test-runs/422">Visualizer: local auto-reloading visualizer</a></li> 
                   <li><a href="https://github.com/erdman/halint">Halint: load in replays and analyse gameplay for obvious mistakes</a></li> 
                </ul>

                <h3>Mini Competitions</h3>
                <ul>
                <li><a href="http://forums.halite.io/t/introducing-unofficial-halite-single-player-mode/573">Single player expansion competition</a> <span class="glyphicon glyphicon-star" aria-hidden="true"></span></li> 
                </ul>

                <h3>Alternate Starter Packages</h3>
                <ul>
                   <li><a href="http://forums.halite.io/t/slightly-more-powerful-c-starter-package/767">C#: more powerful starter package</a></li> 
                   <li><a href="http://forums.halite.io/t/python3-much-alternative-starter-kit-asyncio-based-timeout-management-etc/611">Python: asyncio timeout-based starter package</a></li> 
                   <li><a href="http://forums.halite.io/t/distributed-evolutionary-algorithm-deap-starter/624">Python: Distributed Evolutionary Algorithm (DEAP) starter package</a></li> 
                   <li><a href="http://forums.halite.io/t/python3-numpy-focused-all-i-want-is-matrices-starter-kit/766">Python: numpy matrix oriented starter package</a></li> 
                </ul>

                <h3>Replay File Dumps</h3>
                <ul>
                   <li><a href="http://forums.halite.io/t/diamond-replay-dump/749">December 22, 1583 replays of diamond tier bots</a></li> 
                   <li><a href="http://forums.halite.io/t/2gb-hlt-files-to-train-on/569">December 1, 5GB of @erdman, @djma, and @daniel-shields</a></li> 
                </ul>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <script src="script/backend.js"></script>
    <script src="script/general.js"></script>
</body>
</html>
