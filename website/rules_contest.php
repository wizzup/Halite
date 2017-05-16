<html lang="en">
<head>
    <?php include 'includes/header.php'; ?>

    <title>Contest Rules</title>

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
                <h1>Contest Rules</h1>

                <h3>Schedule</h3>
                <p>The Halite contest officially began on November 10 and will end at 11:59 pm EST on February 12. After that, the rankings will be recomputed for about 2 days, and a winner will be announced.</p>
                <p>Submit early and often!</p>

                <h3>Account Ownership</h3>
                <p>Though collaboration is highly encouraged, teams are not technically allowed.</p>
                <p>Each participant may only have one halite account. Participants found to be in control of multiple accounts will be banned forever.</p>

                <h3>Bug Reports</h3>
                <p>If you find a bug that is exploitable, email us at <a href="mailto:halite@halite.io">halite@halite.io</a>, do not post it on the forums, and do not exploit it.</p>
                <p>Otherwise, feel free to let us know on <a href="http://forums.halite.io">the forums</a>.</p>

                <h3>Rankings</h3>
                <p>Rankings are based on the outcome of organized games where bots play against each other. A good analogy is the <a href="https://en.wikipedia.org/wiki/Elo_rating_system">Elo rating system</a> used for chess.</p>
                <p>More precisely, rankings are computed using a Bayesian algorithm variant of the <a href="https://en.wikipedia.org/wiki/Glicko_rating_system">Glicko system</a>, specifically using the <a href="https://www.microsoft.com/en-us/research/project/trueskill-ranking-system/">TrueSkill</a> Python library available <a href="https://github.com/sublee/trueskill">here</a>.</p>
                <h3>Prizes</h3>
                <p><a href="https://www.twosigma.com">Two Sigma</a> (the company that developed Halite) will waive first round interviews for all users ranked as Gold or Diamond (the top 1/16 of contestants). Just give us a shout at referrals@twosigma.com with the subject line "Halite."</b>
                <p>There's also pride! Bragging rights! Internet royalty! The results of the competition will be officially announced with a link to best players Github profiles and/or blogs (we hope for some great postmortems).</b>
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
