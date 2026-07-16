(function () {
    function digitoVerificadorRuc(ruc) {
        ruc = (ruc || '').replace(/\D/g, '');
        if (ruc.length !== 13) return false;
        var coef = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        var suma = 0;
        for (var i = 0; i < 9; i++) {
            var v = parseInt(ruc[i], 10) * coef[i];
            if (v >= 10) v -= 9;
            suma += v;
        }
        var dig = (10 - (suma % 10)) % 10;
        return parseInt(ruc[9], 10) === dig;
    }

    var rucInput = document.getElementById('ruc');
    var novenoInput = document.getElementById('noveno_digito');
    if (rucInput && novenoInput) {
        rucInput.addEventListener('input', function () {
            var r = this.value.replace(/\D/g, '');
            if (r.length >= 9) {
                novenoInput.value = r.charAt(8);
            }
            var fb = document.getElementById('ruc_feedback');
            if (fb) {
                if (r.length === 13 && digitoVerificadorRuc(r)) {
                    fb.textContent = 'RUC válido';
                    fb.className = 'form-text text-success';
                } else if (r.length === 13) {
                    fb.textContent = 'Dígito verificador incorrecto';
                    fb.className = 'form-text text-danger';
                } else {
                    fb.textContent = 'Ingrese 13 dígitos';
                    fb.className = 'form-text text-muted';
                }
            }
        });
    }

    document.querySelectorAll('form[data-confirm]').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!confirm(f.getAttribute('data-confirm'))) e.preventDefault();
        });
    });
})();
