// Toggle visibilité mot de passe
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bx-eye', 'bx-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bx-eye-slash', 'bx-eye');
    }
}

const PASSWORD_CRITERIA = {
    length: { regEx: /.{8,}/, label: '8 caractères' },
    special: { regEx: /[#@!?.$%&*]+/, label: '1 caractère spécial parmi #@!?.$%&*' },
    uppercase: { regEx: /[A-Z]+/, label: '1 majuscule' },
    numeric: { regEx: /[0-9]+/, label: '1 chiffre' },
};

// Affiche une checklist de critères de robustesse sous un champ mot de passe et la met à jour en direct
function initPasswordStrength(inputId) {
    const pwd = document.getElementById(inputId);
    if (!pwd) return;

    const criterias = PASSWORD_CRITERIA;

    const div = document.createElement('div');
    div.classList.add('form-criteria');

    const p = document.createElement('p');
    p.append(document.createTextNode('Votre mot de passe doit contenir au moins :'));
    div.append(p);

    const ul = document.createElement('ul');
    for (const [id, data] of Object.entries(criterias)) {
        const li = document.createElement('li');
        li.id = `${inputId}-criteria-${id}`;
        li.append(document.createTextNode(data.label));
        ul.append(li);
    }
    div.append(ul);
    const wrapper = pwd.closest('.input-wrapper') ?? pwd.parentNode;
    wrapper.insertAdjacentElement('afterend', div);

    pwd.addEventListener('keyup', () => {
        for (const [id, data] of Object.entries(criterias)) {
            const li = document.getElementById(`${inputId}-criteria-${id}`);
            const valid = data.regEx.test(pwd.value);
            li.classList.toggle('success', valid);
            li.classList.toggle('error', !valid);
        }
    });
}

// Active la checklist sur les champs mot de passe marqués data-password-strength
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[data-password-strength]').forEach(el => initPasswordStrength(el.id));
});

function setFieldError(input, message) {
    const group = input.closest('.form-group') ?? input.parentNode;
    group.classList.add('error');
    group.querySelector('.field-error')?.remove();

    const msg = document.createElement('p');
    msg.classList.add('form-error', 'field-error');
    msg.append(document.createTextNode(message));

    const wrapper = input.closest('.input-wrapper');
    (wrapper ?? input).insertAdjacentElement('afterend', msg);
}

function clearFieldErrors(form) {
    form.querySelectorAll('.form-group.error').forEach((group) => {
        group.classList.remove('error');
        group.querySelector('.field-error')?.remove();
    });
}

function initFormValidation(form) {
    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
    if (!submitBtn) return;

    submitBtn.addEventListener('click', (event) => {
        event.preventDefault();
        clearFieldErrors(form);
        let hasError = false;

        form.querySelectorAll('input[required], select[required], textarea[required]').forEach((input) => {
            if (input.type === 'radio') {
                if (!form.querySelector(`input[name="${input.name}"]:checked`)) {
                    hasError = true;
                    setFieldError(input, 'Champ obligatoire.');
                }
                return;
            }
            if (input.value.trim().length === 0) {
                hasError = true;
                setFieldError(input, 'Champ obligatoire.');
            }
        });

        form.querySelectorAll('input[type="email"]').forEach((input) => {
            if (input.value.trim().length > 0 && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
                hasError = true;
                setFieldError(input, 'Adresse e-mail invalide.');
            }
        });

        form.querySelectorAll('input[data-password-strength]').forEach((input) => {
            if (input.value.length === 0) return;
            const failed = Object.values(PASSWORD_CRITERIA).filter((c) => !c.regEx.test(input.value));
            if (failed.length > 0) {
                hasError = true;
                setFieldError(input, 'Mot de passe invalide : ' + failed.map((c) => c.label).join(', ') + '.');
            }
        });

        form.querySelectorAll('input[data-confirm-of]').forEach((input) => {
            const original = document.getElementById(input.dataset.confirmOf);
            if (original && input.value.length > 0 && input.value !== original.value) {
                hasError = true;
                setFieldError(input, 'Les mots de passe ne correspondent pas.');
            }
        });

        if (!hasError) {
            form.submit();
        }
    });
}

// Active la validation sur les formulaires marqués data-validate
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-validate]').forEach(initFormValidation);
});


// Barre de progression (lit data-width et applique la largeur; utilisé sur le dashboard (progress-bar-fill))
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.progress-bar-fill[data-width]').forEach(el => {
        el.style.width = el.dataset.width + '%';
    });
});