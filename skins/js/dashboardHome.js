var chartVentasCompras = null;
var chartDistribucion = null;
var periodoActual = 'anio'; // Sincronizado con el botón activo por defecto en el HTML

function formatMoney(value) {
    return parseFloat(value).toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function calcularFechasPorPeriodo(periodo) {
    var fechaInicio, fechaFin;
    var hoy = new Date();
    
    switch(periodo) {
        case 'hoy':
            fechaInicio = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate());
            fechaFin = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate());
            break;
        case 'semana':
            // Lunes de esta semana
            var lunes = new Date(hoy);
            var diaSemana = lunes.getDay();
            var diff = lunes.getDate() - diaSemana + (diaSemana === 0 ? -6 : 1); // Ajuste para que lunes sea 1
            lunes.setDate(diff);
            fechaInicio = new Date(lunes.getFullYear(), lunes.getMonth(), lunes.getDate());
            // Domingo de esta semana
            var domingo = new Date(lunes);
            domingo.setDate(lunes.getDate() + 6);
            fechaFin = new Date(domingo.getFullYear(), domingo.getMonth(), domingo.getDate());
            break;
        case 'mes':
            fechaInicio = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            fechaFin = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0); // Último día del mes
            break;
        case 'trimestre':
            var mes = hoy.getMonth();
            var trimestre = Math.floor(mes / 3);
            fechaInicio = new Date(hoy.getFullYear(), trimestre * 3, 1);
            fechaFin = new Date(hoy.getFullYear(), (trimestre + 1) * 3, 0); // Último día del trimestre
            break;
        case 'anio':
            fechaInicio = new Date(hoy.getFullYear(), 0, 1);
            fechaFin = new Date(hoy.getFullYear(), 11, 31);
            break;
        case 'personalizado':
            return null;
        default:
            return null;
    }
    
    // Formatear fechas como YYYY-MM-DD para los inputs
    function formatearFecha(fecha) {
        var año = fecha.getFullYear();
        var mes = String(fecha.getMonth() + 1).padStart(2, '0');
        var dia = String(fecha.getDate()).padStart(2, '0');
        return año + '-' + mes + '-' + dia;
    }
    
    return {
        inicio: formatearFecha(fechaInicio),
        fin: formatearFecha(fechaFin)
    };
}

function cambiarPeriodo(periodo, btn) {
    periodoActual = periodo;
    if (btn) {
        document.querySelectorAll('.btn-periodo').forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');
    }
    
    // Actualizar fechas según el período seleccionado
    if (periodo !== 'personalizado') {
        var fechas = calcularFechasPorPeriodo(periodo);
        if (fechas) {
            document.getElementById('fechaInicio').value = fechas.inicio;
            document.getElementById('fechaFin').value = fechas.fin;
        }
    }
    
    actualizarDashboard();
}

function cambiarTipoGrafico(tipo, btn) {
    document.querySelectorAll('.chart-type-btn').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    if (chartVentasCompras) {
        chartVentasCompras.config.type = tipo;
        chartVentasCompras.update();
    }
}

function actualizarDashboard() {
    var loading = document.getElementById('dashboard-loading');
    loading.style.display = 'inline';

    var formData = new FormData();
    formData.append('getDashboardData', '1');
    formData.append('periodo', periodoActual);
    formData.append('fechaInicio', document.getElementById('fechaInicio').value);
    formData.append('fechaFin', document.getElementById('fechaFin').value);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            // Actualizar Ventas
            document.getElementById('ventas-total').textContent = '$' + formatMoney(data.ventas.total);
            document.getElementById('ventas-cantidad').textContent = data.ventas.cantidad;
            document.getElementById('ventas-iva').textContent = formatMoney(data.ventas.iva);
            document.getElementById('ventas-periodo').textContent = data.periodo.inicio + ' - ' + data.periodo.fin;

            // Actualizar Compras
            document.getElementById('compras-total').textContent = '$' + formatMoney(data.compras.total);
            document.getElementById('compras-cantidad').textContent = data.compras.cantidad;
            document.getElementById('compras-iva').textContent = formatMoney(data.compras.iva);
            document.getElementById('compras-periodo').textContent = data.periodo.inicio + ' - ' + data.periodo.fin;

            // Actualizar Balance
            var balance = data.ventas.total - data.compras.total;
            var balanceCard = document.getElementById('card-balance');
            var balanceIndicador = document.getElementById('balance-indicador');
            if (balance >= 0) {
                document.getElementById('balance-total').textContent = '+$' + formatMoney(balance);
                document.getElementById('balance-tipo').textContent = 'Ganancia';
                balanceCard.classList.remove('negativo');
                balanceIndicador.innerHTML = '<i class="fa fa-arrow-up"></i> Positivo';
            } else {
                document.getElementById('balance-total').textContent = '-$' + formatMoney(Math.abs(balance));
                document.getElementById('balance-tipo').textContent = 'Pérdida';
                balanceCard.classList.add('negativo');
                balanceIndicador.innerHTML = '<i class="fa fa-arrow-down"></i> Negativo';
            }

            // Actualizar Clientes
            document.getElementById('clientes-cantidad').textContent = data.clientesTotal || data.clientesNuevos || 0;
            document.getElementById('clientes-periodo').textContent = 'Total Registrados';

            // Actualizar Gráfico Principal
            actualizarGraficoPrincipal(data.grafico);
            // Actualizar Gráfico de Distribución
            actualizarGraficoDistribucion(data.ventas.total, data.compras.total);

            // Actualizar Tablas
            actualizarTablaProductos(data.topProductos);
            actualizarTablaClientes(data.topClientes);

            // Actualizar Documentos Autorizados
            if (data.documentos) {
                actualizarDocumentosAutorizados(data.documentos, data.periodo);
            }
        }
        loading.style.display = 'none';
    })
    .catch(function(error) {
        console.error('Error:', error);
        loading.style.display = 'none';
    });
}

function actualizarGraficoPrincipal(datosGrafico) {
    var ctx = document.getElementById('chartVentasCompras').getContext('2d');
    if (chartVentasCompras) {
        chartVentasCompras.data.labels = datosGrafico.labels;
        chartVentasCompras.data.datasets[0].data = datosGrafico.ventas;
        chartVentasCompras.data.datasets[1].data = datosGrafico.compras;
        chartVentasCompras.update();
    } else {
        chartVentasCompras = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: datosGrafico.labels,
                datasets: [
                    { label: 'Ventas', data: datosGrafico.ventas, backgroundColor: 'rgba(30, 136, 229, 0.8)', borderColor: 'rgba(30, 136, 229, 1)', borderWidth: 2, borderRadius: 6, tension: 0.4 },
                    { label: 'Compras', data: datosGrafico.compras, backgroundColor: 'rgba(229, 57, 53, 0.8)', borderColor: 'rgba(229, 57, 53, 1)', borderWidth: 2, borderRadius: 6, tension: 0.4 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, padding: 15, font: { size: 12, weight: '600' } } },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + formatMoney(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { callback: function(value) { return '$' + value.toLocaleString('es-ES'); } }
                    }
                }
            }
        });
    }
}

function actualizarGraficoDistribucion(ventas, compras) {
    var ctx = document.getElementById('chartDistribucion').getContext('2d');
    if (chartDistribucion) {
        chartDistribucion.data.datasets[0].data = [ventas, compras];
        chartDistribucion.update();
    } else {
        chartDistribucion = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Ventas', 'Compras'],
                datasets: [{
                    data: [ventas, compras],
                    backgroundColor: ['rgba(30, 136, 229, 0.9)', 'rgba(229, 57, 53, 0.9)'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                rotation: -90,
                circumference: 360,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15, usePointStyle: true },
                        reverse: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                var porcentaje = ((context.raw / total) * 100).toFixed(1);
                                return context.label + ': $' + formatMoney(context.raw) + ' (' + porcentaje + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
}

function actualizarTablaProductos(productos) {
    var tbody = document.getElementById('tbody-productos');
    if (!productos || productos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px; color: #999;"><i class="fa fa-info-circle"></i> Sin datos</td></tr>';
        return;
    }
    var html = '';
    productos.forEach(function(prod, index) {
        var rankClass = index < 3 ? 'rank-' + (index + 1) : 'rank-other';
        var nombreCorto = prod.nombre ? prod.nombre.substring(0, 20) : 'N/A';
        var nombreLargo = prod.nombre_largo || prod.nombre || '';
        html += '<tr>' +
            '<td><span class="rank-badge ' + rankClass + '">' + (index + 1) + '</span></td>' +
            '<td title="' + nombreLargo.replace(/"/g, '&quot;') + '" style="cursor: help;">' + nombreCorto + '</td>' +
            '<td>' + (prod.cantidad || 0) + '</td>' +
            '<td>$' + formatMoney(prod.total || 0) + '</td>' +
        '</tr>';
    });
    tbody.innerHTML = html;
}

function actualizarTablaClientes(clientes) {
    var tbody = document.getElementById('tbody-clientes');
    if (!clientes || clientes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px; color: #999;"><i class="fa fa-info-circle"></i> Sin datos</td></tr>';
        return;
    }
    var html = '';
    clientes.forEach(function(cli, index) {
        var rankClass = index < 3 ? 'rank-' + (index + 1) : 'rank-other';
        html += '<tr>' +
            '<td><span class="rank-badge ' + rankClass + '">' + (index + 1) + '</span></td>' +
            '<td>' + (cli.nombre ? cli.nombre.substring(0, 25) : 'N/A') + '</td>' +
            '<td>' + (cli.facturas || 0) + '</td>' +
            '<td>$' + formatMoney(cli.total || 0) + '</td>' +
        '</tr>';
    });
    tbody.innerHTML = html;
}

function actualizarDocumentosAutorizados(docs, periodo) {
    document.getElementById('doc-facturas').textContent = (docs.facturas || 0).toLocaleString('es-ES');
    document.getElementById('doc-retenciones').textContent = (docs.retenciones || 0).toLocaleString('es-ES');
    document.getElementById('doc-notas-credito').textContent = (docs.notasCredito || 0).toLocaleString('es-ES');
    document.getElementById('doc-liquidaciones').textContent = (docs.liquidaciones || 0).toLocaleString('es-ES');
    document.getElementById('doc-guias').textContent = (docs.guias || 0).toLocaleString('es-ES');
    // Calcular y actualizar total
    var total = (docs.facturas || 0) + (docs.retenciones || 0) + (docs.notasCredito || 0) + (docs.liquidaciones || 0) + (docs.guias || 0);
    document.getElementById('doc-total').textContent = total.toLocaleString('es-ES');
    // Actualizar label de período
    var labelPeriodo = document.getElementById('docs-periodo-label');
    if (labelPeriodo && periodo) {
        labelPeriodo.textContent = periodo.inicio + ' al ' + periodo.fin;
    }
}

// Cargar datos al iniciar
document.addEventListener('DOMContentLoaded', function() {
    // Establecer fechas iniciales según el período por defecto (año)
    var fechas = calcularFechasPorPeriodo(periodoActual);
    if (fechas) {
        document.getElementById('fechaInicio').value = fechas.inicio;
        document.getElementById('fechaFin').value = fechas.fin;
    }
    actualizarDashboard();
});
