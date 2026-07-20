<?php
// Choix du chien à inscrire : champ caché s'il n'y en a qu'un, liste déroulante sinon
$dogs = $dogs ?? [];
?>
<?php if (count($dogs) === 1): ?>
    <input type="hidden" name="dog_id" value="<?= (int) $dogs[0]['id_dog'] ?>">
<?php elseif (count($dogs) > 1): ?>
    <div class="input-wrapper input-wrapper-select" style="margin-bottom:12px;">
        <i class="bx bx-dog bx-remove-padding input-icon"></i>
        <select name="dog_id" aria-label="Chien à inscrire" required>
            <?php foreach ($dogs as $d): ?>
                <option value="<?= (int) $d['id_dog'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
<?php endif; ?>
