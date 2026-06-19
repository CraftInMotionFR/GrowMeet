<?php ?>

<section id="hero-banner">
    <div class="container">
        <div id="hero-text">
            <h1>Éduquez votre chien avec passion et expertise</h1>
            <p>Rejoignez notre club canin pour des cours d'éducation adaptés à tous les âges. Des coachs
                qualifiés accompagnent votre compagnon vers l'excellence.</p>
            <div class="flex">
                <a href="/register"><button class="cto-orange">Commencer maintenant</button></a>
                <a href="/courses"><button class="cto-green">Découvrir les cours</button></a>
            </div>
        </div>
        <div id="hero-img">
            <img src="/images/Dog_Hero_Banner.png" alt="Chien éducation GrowMeet">
        </div>
    </div>
</section>
<section>
    <div class="container">
        <div>
            <h2>Pourquoi choisir GrowMeet ?</h2>
            <p>Une plateforme complète pour le bien-être et l'éducation de votre chien</p>
        </div>
        <div class="grid-container">
            <div class="card">
                <div class="card-icon">
                    <i class="bx bx-calendar-alt bx-remove-padding icon-orange"></i>
                </div>
                <h3>Réservation en ligne</h3>
                <p>Inscrivez-vous facilement aux cours disponibles</p>
            </div>
            <div class="card">
                <div class="card-icon">
                    <i class="bx bx-group bx-remove-padding icon-orange"></i>
                </div>
                <h3>Coachs qualifiés</h3>
                <p>Des instructeurs expérimentés et passionnés</p>
            </div>
            <div class="card">
                <div class="card-icon">
                    <i class="bx bx-medal-alt bx-remove-padding icon-orange"></i>
                </div>
                <h3>Programmes adaptés</h3>
                <p>Cours adaptés à tous les âges et niveaux</p>
            </div>
        </div>
    </div>
</section>
<section class="secondary-bg">
    <div class="container txt-center">
        <div>
            <h2>Nos cours disponibles</h2>
            <p>Des programmes adaptés à chaque étape de la vie de votre chien</p>
        </div>
        <div class="grid-container">
            <?php
            $cours = [
                'École du chiot', 'Éducation 6-12 mois', 'Éducation 1-2 ans',
                'Éducation +2 ans', 'Socialisation', 'Parcours sportifs', 'Dressage avancé'
            ];
            foreach ($cours as $nom): ?>
                <div class="s-card">
                    <div class="check-icon">
                        <i class="bx bx-check bx-remove-padding icon-white"></i>
                    </div>
                    <span><?= htmlspecialchars($nom) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="/courses"><button class="cto-orange">Voir tous les cours</button></a>
    </div>
</section>
<section>
    <div class="container">
        <h2>Notre club en images</h2>
        <div class="gallery flex">
            <img src="/images/Dog_gallery_1.png" alt="Golden retriever">
            <img src="/images/Dog_gallery_2.png" alt="Chien en cours">
            <img src="/images/Dog_gallery_3.png" alt="Séance éducation">
            <img src="/images/Dog_gallery_4.png" alt="Club canin">
        </div>
    </div>
</section>
<section class="third-bg">
    <div class="container txt-center">
        <h2>Prêt à commencer l'aventure ?</h2>
        <p>Rejoignez notre communauté de passionnés et offrez à votre chien la meilleure éducation possible</p>
        <a href="/register"><button class="cto-white">S'inscrire gratuitement</button></a>
    </div>
</section>
