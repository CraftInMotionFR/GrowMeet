<?php

$old = $old ?? [];
$breeds = $breeds ?? [];
// Mode modification si un chien est fourni
$dog = $dog ?? null;
$isEdit = $dog !== null;
$formAction = $isEdit ? '/dogs/' . (int) $dog['id_dog'] . '/update' : '/dogs';
?>

<section class="auth-section">
    <div class="auth-container auth-container-single">
        <div class="auth-card auth-card-wide">
            <a href="/dogs" class="back-link">
                <i class="bx bx-arrow-left-stroke bx-remove-padding"></i>
                Retour à mes chiens
            </a>
            <div class="auth-header">
                <h1><?= $isEdit ? 'Modifier ' . htmlspecialchars($dog['name']) : 'Ajouter un chien' ?></h1>
                <p><?= $isEdit ? 'Mettez à jour les informations de votre chien' : 'Remplissez les informations de votre chien' ?></p>
            </div>
            <div class="dog-form-subheader">
                <h4>Informations du chien</h4>
                <p>Ces informations nous permettent de vous proposer les cours adaptés</p>
            </div>
            <?php if (!empty($errors)): ?>
                <div class="form-errors">
                    <?php foreach ($errors as $error): ?>
                        <p class="form-error"><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form class="auth-form" method="POST" action="<?= $formAction ?>" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="dog-photo">Photo</label>
                    <div class="dog-photo-picker">
                        <div class="dog-photo-preview">
                            <?php if ($isEdit && !empty($dog['image'])): ?>
                                <img src="/images/<?= htmlspecialchars($dog['image']) ?>" alt="<?= htmlspecialchars($dog['name']) ?>"
                                     style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <i class="bx bx-image bx-remove-padding"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label for="dog-photo" class="cto-green btn-file">
                                <i class="bx bx-camera bx-remove-padding"></i> <?= $isEdit ? 'Changer la photo' : 'Choisir une photo' ?>
                            </label>
                            <input type="file" id="dog-photo" name="dog_photo" accept="image/jpeg,image/png" hidden>
                            <p class="dog-photo-hint">JPG ou PNG, max 5MB</p>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="dog-name">Nom du chien *</label>
                        <div class="input-wrapper">
                            <i class="bx bx-heart bx-remove-padding input-icon"></i>
                            <input type="text" id="dog-name" name="dog_name"
                                   placeholder="Max, Luna, Rex..."
                                   value="<?= htmlspecialchars($old['dog_name'] ?? '') ?>"
                                   required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="dog-breed">Race *</label>
                        <div class="input-wrapper">
                            <i class="bx bx-search bx-remove-padding input-icon"></i>
                            <input type="text" id="dog-breed" name="dog_breed" list="breed-suggestions"
                                   placeholder="Golden Retriever, Labrador..."
                                   value="<?= htmlspecialchars($old['dog_breed'] ?? '') ?>">
                            <datalist id="breed-suggestions">
                                <?php foreach ($breeds as $breed): ?>
                                    <option value="<?= htmlspecialchars($breed) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="dog-gender">Sexe *</label>
                        <div class="input-wrapper input-wrapper-select">
                            <i class="bx bx-transgender bx-remove-padding input-icon"></i>
                            <select id="dog-gender" name="gender" required>
                                <option value="" disabled <?= empty($old['gender']) ? 'selected' : '' ?>>Sélectionnez le sexe</option>
                                <?php foreach (['male' => 'Mâle', 'female' => 'Femelle', 'unknown' => 'Inconnu'] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= ($old['gender'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="dog-birth-date">Date de naissance *</label>
                        <div class="input-wrapper">
                            <i class="bx bx-calendar-alt bx-remove-padding input-icon"></i>
                            <input type="date" id="dog-birth-date" name="birth_date"
                                   max="<?= date('Y-m-d') ?>"
                                   value="<?= htmlspecialchars($old['birth_date'] ?? '') ?>"
                                   required>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="cto-orange"><?= $isEdit ? 'Enregistrer les modifications' : 'Ajouter le chien' ?></button>
                    <a href="/dogs"><button type="button" class="cto-green">Annuler</button></a>
                </div>
            </form>
            <?php if ($isEdit): ?>
                <form method="POST" action="/dogs/<?= (int) $dog['id_dog'] ?>/delete"
                      onsubmit="return confirm('Supprimer ce chien ? Ses inscriptions aux cours seront aussi annulées.');">
                    <button type="submit" class="cto-white btn-full">
                        <i class="bx bx-trash bx-remove-padding"></i> Supprimer ce chien
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
