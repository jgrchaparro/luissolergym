app.controller('ngPagoController', function ($scope, $http, $timeout) {
    var $ctrl = this;

    // ===== Listado de pagos =====
    $ctrl.items = [];
    $ctrl.loading = false;
    $ctrl.totales = {USD: 0, VEF: 0, COP: 0};
    $ctrl.miembrosRelacionados = [];

    $ctrl.total = 0;
    $ctrl.limit = 10;
    $ctrl.paginaActual = 1;
    $ctrl.totalPaginas = 1;
    $ctrl.paginas = [];

    // ===== Filtros =====
    function hoyISO() {
        var d = new Date();
        var mm = ('0' + (d.getMonth() + 1)).slice(-2);
        var dd = ('0' + d.getDate()).slice(-2);
        return d.getFullYear() + '-' + mm + '-' + dd;
    }

    $ctrl.filtros = {desde: hoyISO(), hasta: hoyISO(), busqueda: ''};

    // ===== Registro de pago =====
    $ctrl.tiposPago = [];
    $ctrl.tasa = {usdVef: 0, usdCop: 0, montoUsdDia: 0};
    $ctrl.modo = 'miembro'; // 'miembro' | 'dia'
    $ctrl.pago = {};
    $ctrl.miembroBusqueda = '';
    $ctrl.miembrosEncontrados = [];
    $ctrl.miembroSeleccionado = null;
    $ctrl.buscandoMiembros = false;
    $ctrl.guardando = false;
    $ctrl.error = null;

    $ctrl.monedaSimbolo = function (moneda) {
        if (moneda === 'VEF' || moneda === 'VES') return 'Bs';
        if (moneda === 'COP') return 'COP';
        return '$';
    };

    $ctrl.listar = function () {
        $ctrl.loading = true;
        var skip = ($ctrl.paginaActual - 1) * $ctrl.limit;

        $http.get(Routing.generate('pago_listar_json'), {
            params: {
                desde: $ctrl.filtros.desde,
                hasta: $ctrl.filtros.hasta,
                busqueda: $ctrl.filtros.busqueda,
                limit: $ctrl.limit,
                skip: skip
            }
        }).then(function (response) {
            $ctrl.items = response.data.data;
            $ctrl.total = response.data.total;
            $ctrl.totales = response.data.totales || {USD: 0, VEF: 0, COP: 0};
            $ctrl.totalPaginas = Math.ceil($ctrl.total / $ctrl.limit) || 1;
            $ctrl.calcularPaginas();
            $ctrl.loading = false;

            // Si no hay pagos para el filtro pero se buscó un miembro, mostrar los
            // miembros coincidentes con su estado de solvencia.
            var q = ($ctrl.filtros.busqueda || '').trim();
            if ($ctrl.items.length === 0 && q.length >= 2) {
                $ctrl.buscarMiembrosRelacionados(q);
            } else {
                $ctrl.miembrosRelacionados = [];
            }
        }, function () {
            $ctrl.loading = false;
        });
    };

    $ctrl.buscarMiembrosRelacionados = function (q) {
        $http.get(Routing.generate('pago_buscar_miembros'), {params: {busqueda: q}})
            .then(function (response) {
                $ctrl.miembrosRelacionados = response.data;
            }, function () {
                $ctrl.miembrosRelacionados = [];
            });
    };

    $ctrl.calcularPaginas = function () {
        var paginas = [];
        var inicio = Math.max(1, $ctrl.paginaActual - 2);
        var fin = Math.min($ctrl.totalPaginas, $ctrl.paginaActual + 2);
        for (var i = inicio; i <= fin; i++) {
            paginas.push(i);
        }
        $ctrl.paginas = paginas;
    };

    $ctrl.irPagina = function (pagina) {
        if (pagina < 1 || pagina > $ctrl.totalPaginas) return;
        $ctrl.paginaActual = pagina;
        $ctrl.listar();
    };

    $ctrl.buscar = function () {
        $ctrl.paginaActual = 1;
        $ctrl.listar();
    };

    $ctrl.limpiarFiltro = function () {
        $ctrl.filtros = {desde: hoyISO(), hasta: hoyISO(), busqueda: ''};
        $ctrl.paginaActual = 1;
        $ctrl.listar();
    };

    // ===== Tipos de pago y tasa (reutiliza endpoints existentes) =====
    $ctrl.cargarTiposPago = function () {
        $http.get(Routing.generate('cliente_tipos_pago')).then(function (response) {
            $ctrl.tiposPago = response.data;
        });
    };

    $ctrl.cargarTasa = function () {
        $http.get(Routing.generate('pago_tasa_actual')).then(function (response) {
            $ctrl.tasa = response.data;
        });
    };

    $ctrl.getTipoPagoSeleccionado = function () {
        for (var i = 0; i < $ctrl.tiposPago.length; i++) {
            if ($ctrl.tiposPago[i].id === $ctrl.pago.tipo_pago_id) {
                return $ctrl.tiposPago[i];
            }
        }
        return null;
    };

    $ctrl.monedaActual = function () {
        var tp = $ctrl.getTipoPagoSeleccionado();
        return tp ? tp.moneda : 'USD';
    };

    // Base en USD según el modo: mensualidad del miembro o costo del día.
    $ctrl.baseUsd = function () {
        if ($ctrl.modo === 'dia') {
            return $ctrl.tasa.montoUsdDia || 0;
        }
        return ($ctrl.miembroSeleccionado && $ctrl.miembroSeleccionado.montoSugerido != null)
            ? $ctrl.miembroSeleccionado.montoSugerido
            : null;
    };

    $ctrl.convertirDesdeUsd = function (usd, moneda) {
        if (usd == null) return null;
        if (moneda === 'VEF') return usd * ($ctrl.tasa.usdVef || 0);
        if (moneda === 'COP') return usd * ($ctrl.tasa.usdCop || 0);
        return usd;
    };

    // Equivalente del costo del día en una moneda (para el panel informativo).
    $ctrl.costoDiaEn = function (moneda) {
        return $ctrl.convertirDesdeUsd($ctrl.tasa.montoUsdDia || 0, moneda);
    };

    // Autocompleta el monto convirtiendo la base USD a la moneda del tipo de pago.
    $ctrl.recalcularMonto = function () {
        var usd = $ctrl.baseUsd();
        if (usd == null) return;
        var monto = $ctrl.convertirDesdeUsd(usd, $ctrl.monedaActual());
        $ctrl.pago.monto = Math.round(monto * 100) / 100;
    };

    $ctrl.setModo = function (modo) {
        $ctrl.modo = modo;
        $ctrl.error = null;
        if (modo === 'dia') {
            $ctrl.miembroSeleccionado = null;
            $ctrl.miembrosEncontrados = [];
            $ctrl.miembroBusqueda = '';
        }
        $ctrl.recalcularMonto();
    };

    // ===== Registro de miembro (mismo formulario que la vista de Miembros) =====
    $ctrl.nacionalidades = ['V', 'E', 'P', 'J', 'G', 'M', 'C'];
    $ctrl.tiposMensualidad = [];
    $ctrl.miembro = {};
    $ctrl.guardandoMiembro = false;
    $ctrl.errorMiembro = null;

    $ctrl.cargarTiposMensualidad = function () {
        $http.get(Routing.generate('cliente_tipos_mensualidad')).then(function (response) {
            $ctrl.tiposMensualidad = response.data;
        });
    };

    $ctrl.abrirModalMiembro = function () {
        $ctrl.miembro = {nacionalidad: 'V'};
        $ctrl.errorMiembro = null;
        $('#modalMiembro').modal('show');
    };

    $ctrl.guardarMiembro = function () {
        if ($ctrl.guardandoMiembro) return;
        $ctrl.errorMiembro = null;
        $ctrl.guardandoMiembro = true;

        var datos = angular.copy($ctrl.miembro);
        datos.cedula = (datos.nacionalidad || 'V') + (datos.cedulaNumero || '');
        delete datos.nacionalidad;
        delete datos.cedulaNumero;

        $http.get(Routing.generate('cliente_guardar'), {params: datos}).then(function (response) {
            $ctrl.guardandoMiembro = false;
            if (response.data.success) {
                $('#modalMiembro').modal('hide');
                showToast('success', 'Miembro registrado exitosamente');
            } else {
                $ctrl.errorMiembro = response.data.error;
            }
        }, function () {
            $ctrl.guardandoMiembro = false;
            $ctrl.errorMiembro = 'Error al guardar el miembro';
        });
    };

    // ===== Modal de registro =====
    $ctrl.abrirModalPago = function () {
        $ctrl.modo = 'miembro';
        $ctrl.pago = {};
        $ctrl.error = null;
        $ctrl.miembroBusqueda = '';
        $ctrl.miembrosEncontrados = [];
        $ctrl.miembroSeleccionado = null;
        $('#modalRegistrarPago').modal('show');
    };

    var debounce = null;
    $ctrl.buscarMiembros = function () {
        $ctrl.miembroSeleccionado = null;
        if (debounce) $timeout.cancel(debounce);

        var q = ($ctrl.miembroBusqueda || '').trim();
        if (q.length < 2) {
            $ctrl.miembrosEncontrados = [];
            return;
        }

        $ctrl.buscandoMiembros = true;
        debounce = $timeout(function () {
            return $http.get(Routing.generate('pago_buscar_miembros'), {params: {busqueda: q}})
                .then(function (response) {
                    $ctrl.miembrosEncontrados = response.data;
                    $ctrl.buscandoMiembros = false;
                }, function () {
                    $ctrl.buscandoMiembros = false;
                });
        }, 300);
    };

    $ctrl.seleccionarMiembro = function (miembro) {
        $ctrl.miembroSeleccionado = miembro;
        $ctrl.miembrosEncontrados = [];
        $ctrl.miembroBusqueda = miembro.nombre + ' (' + miembro.cedula + ')';
        $ctrl.recalcularMonto();
    };

    $ctrl.cambiarMiembro = function () {
        $ctrl.miembroSeleccionado = null;
        $ctrl.miembroBusqueda = '';
        $ctrl.miembrosEncontrados = [];
    };

    $ctrl.guardarPago = function () {
        if ($ctrl.guardando) return;

        var url, params;
        if ($ctrl.modo === 'dia') {
            url = Routing.generate('pago_registrar_dia');
            params = {
                tipo_pago_id: $ctrl.pago.tipo_pago_id,
                nombre: $ctrl.pago.nombre || '',
                observaciones: $ctrl.pago.observaciones || ''
            };
        } else {
            if (!$ctrl.miembroSeleccionado) {
                $ctrl.error = 'Debe seleccionar un miembro';
                return;
            }
            url = Routing.generate('cliente_registrar_pago');
            params = {
                cliente_id: $ctrl.miembroSeleccionado.id,
                tipo_pago_id: $ctrl.pago.tipo_pago_id,
                monto: $ctrl.pago.monto,
                observaciones: $ctrl.pago.observaciones || ''
            };
        }

        $ctrl.error = null;
        $ctrl.guardando = true;

        $http.get(url, {params: params}).then(function (response) {
            $ctrl.guardando = false;
            if (response.data.success) {
                $('#modalRegistrarPago').modal('hide');
                showToast('success', 'Pago registrado exitosamente');
                $ctrl.listar();
            } else {
                $ctrl.error = response.data.error;
            }
        }, function (response) {
            $ctrl.guardando = false;
            $ctrl.error = (response && response.data && response.data.error)
                ? response.data.error
                : 'Error al registrar el pago';
        });
    };

    // Cargar al iniciar
    $ctrl.cargarTiposPago();
    $ctrl.cargarTasa();
    $ctrl.cargarTiposMensualidad();
    $ctrl.listar();
});

app.directive('soloNumeros', function () {
    return {
        require: 'ngModel',
        link: function (scope, element, attrs, ngModel) {
            element.on('input', function () {
                var limpio = this.value.replace(/[^0-9]/g, '');
                if (this.value !== limpio) {
                    this.value = limpio;
                    ngModel.$setViewValue(limpio);
                    ngModel.$render();
                }
            });
        }
    };
});

/**
 * Selector de rango de fechas (daterangepicker) integrado con Angular.
 * Enlaza el rango seleccionado a "desde"/"hasta" (formato YYYY-MM-DD) y
 * dispara "on-apply" al aplicar un nuevo rango.
 */
app.directive('rangoFechas', function () {
    return {
        restrict: 'A',
        scope: {
            desde: '=',
            hasta: '=',
            onApply: '&'
        },
        link: function (scope, element) {
            var base = (typeof datePickerOptions !== 'undefined') ? datePickerOptions : {};

            var opts = angular.extend({}, base, {
                autoApply: true,
                startDate: moment(),
                endDate: moment(),
                opens: 'right'
            });
            opts.locale = angular.extend({format: 'DD/MM/YYYY'}, base.locale || {});

            element.daterangepicker(opts);

            element.on('apply.daterangepicker', function (ev, picker) {
                scope.$apply(function () {
                    scope.desde = picker.startDate.format('YYYY-MM-DD');
                    scope.hasta = picker.endDate.format('YYYY-MM-DD');
                    scope.onApply();
                });
            });

            // Mantiene el widget en sincronía si el rango cambia desde fuera (ej. Limpiar).
            scope.$watchGroup(['desde', 'hasta'], function (vals) {
                var dp = element.data('daterangepicker');
                if (!dp || !vals[0] || !vals[1]) return;
                var s = moment(vals[0], 'YYYY-MM-DD');
                var e = moment(vals[1], 'YYYY-MM-DD');
                if (!s.isSame(dp.startDate, 'day') || !e.isSame(dp.endDate, 'day')) {
                    dp.setStartDate(s);
                    dp.setEndDate(e);
                }
            });

            scope.$on('$destroy', function () {
                var dp = element.data('daterangepicker');
                if (dp) dp.remove();
            });
        }
    };
});
