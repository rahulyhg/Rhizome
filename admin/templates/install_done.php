<?php $app->render('header.php', array('path' => $path, 'app' => $app)); // load ui ?>
<div id="additional-info">
        <div class="row">
            <div class="large-12 columns">
                <h2 class="color-white headings text-center">Cinema Open Data Base - Administration</h2>
            </div>
        </div>
    </div>

    <div id="why">
        <div class="row">
            <div class="large-12 columns">
                <h2 class="text-center color-pink headings">Installation 2/2</h2>
            </div>
            <div class="row">
                <div class="large-12 columns">
                    <h5 class="color-black" style="text-align:center; line-height: 27px;">
                        Cliquer sur le bouton suivant pour vous connecter
                    </h5>            
                </div>
            </div>
        </div>
    </div>

    <div id="features">
        <div class="row">
            <div class="large-6 large-centered columns">
                <center>
                    <a href="<?= $app->urlFor('login'); ?>"><button  class="blue-btn">Aller sur la page de connexion</button></a>
                </center>
                
            </div>
        </div>
    </div>

    
    <footer>
        <div class="row text-center">
            <div class="large-12 columns">
                <a href="" >Supporté par le Catalogue Ouvert du Cinéma</a>
            </div>
        </div>
    </footer>
<?php $app->render('footer.php', array('path' => $path, 'app' => $app)); // load ui ?>