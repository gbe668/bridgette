// Capacité optionnelle : les anciennes APK gardent les numéros en texte.
function androidPhoneHtml(value, email) {
    var text = String(value == null ? '' : value);
    var span = document.createElement('span');
    span.textContent = text;
    var number = text.replace(/[\s.()\-]/g, '');
    if (typeof Android === 'undefined' || typeof Android.dialPhone !== 'function'
            || !/^\+?[0-9]{3,15}$/.test(number)) return span.innerHTML;
    span.className = 'apk-phone';
    var select = document.createElement('button');
    select.type = 'button';
    select.className = 'mButton apk-phone-select';
    select.textContent = text;
    select.setAttribute('data-phone', number);
    select.setAttribute('data-email', String(email == null ? '' : email).trim());
    span.textContent = '';
    span.appendChild(select);
    return span.outerHTML;
}

$(document).ready(function () {
    $('#section_annuaire tr td:nth-child(2)').each(function () {
        this.innerHTML = androidPhoneHtml(this.textContent, this.getAttribute('data-email'));
    });
    $(document).on('click', '.apk-phone-select', function () {
        if (document.getElementById('apk-phone-confirm')) return;
        var source = this;
        var number = source.getAttribute('data-phone');
        var popup = document.createElement('dialog');
        popup.id = 'apk-phone-confirm';
        popup.setAttribute('aria-label', 'Confirmation d’appel');
        var mail = document.createElement('p');
        mail.textContent = 'Mail : ' + (source.getAttribute('data-email') || 'non renseigné');
        mail.setAttribute('style', 'overflow-wrap: anywhere;');
        popup.appendChild(mail);
        var message = document.createElement('p');
        message.textContent = 'Appeler le ' + source.textContent + ' ?';
        var cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'myButton';
        cancel.textContent = 'Annule';
        cancel.setAttribute('style', 'background: #ffcccc; color: #000;');
        var accept = document.createElement('button');
        accept.type = 'button';
        accept.className = 'myButton';
        accept.textContent = 'OK';
        accept.setAttribute('style', 'background: lightgreen; color: #000;');
        function close() {
            popup.close();
            popup.remove();
            source.focus();
        }
        cancel.onclick = close;
        popup.oncancel = function (event) { event.preventDefault(); close(); };
        accept.onclick = function () {
            close();
            try {
                Android.dialPhone(number);
            } catch (error) {
                alert('Impossible d’ouvrir le téléphone. Réessayez.');
            }
        };
        popup.appendChild(message);
        var buttons = document.createElement('div');
        buttons.setAttribute('style', 'display: flex; justify-content: center; gap: 24px;');
        buttons.appendChild(cancel);
        buttons.appendChild(accept);
        popup.appendChild(buttons);
        document.body.appendChild(popup);
        popup.showModal();
        cancel.focus();
    });
});
