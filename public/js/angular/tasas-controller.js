app.controller('ngTasasController', function ($scope, $http) {
    var $ctrl = this;

    $ctrl.tasas = [];
    $ctrl.formData = {};
    $ctrl.tasaCalculada = 0;
    $ctrl.loading = false;
    $ctrl.error = null;

    $ctrl.busqueda = '';
    $ctrl.total = 0;
    $ctrl.limit = 10;
    $ctrl.paginaActual = 1;
    $ctrl.totalPaginas = 1;
    $ctrl.paginas = [];

    $ctrl.listar = function () {
        $ctrl.loading = true;
        var skip = ($ctrl.paginaActual - 1) * $ctrl.limit;
        var url = Routing.generate('tasas_listar_json');

        $http.get(url, {
            params: {busqueda: $ctrl.busqueda, limit: $ctrl.limit, skip: skip}
        }).then(function (response) {
            $ctrl.tasas = response.data.data;
            $ctrl.total = response.data.total;
            $ctrl.totalPaginas = Math.ceil($ctrl.total / $ctrl.limit) || 1;
            $ctrl.calcularPaginas();
            $ctrl.loading = false;
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
        $ctrl.busqueda = '';
        $ctrl.paginaActual = 1;
        $ctrl.listar();
    };

    $ctrl.recalcular = function () {
        var vef = parseFloat($ctrl.formData.usd_vef);
        var cop = parseFloat($ctrl.formData.usd_cop);
        if (vef > 0 && cop > 0) {
            $ctrl.tasaCalculada = cop / vef;
        } else {
            $ctrl.tasaCalculada = 0;
        }
    };

    $ctrl.abrirModalAgregar = function () {
        $ctrl.formData = {};
        $ctrl.tasaCalculada = 0;
        $ctrl.error = null;
        $('#modalTasa').modal('show');
    };

    $ctrl.guardar = function () {
        $ctrl.error = null;
        var url = Routing.generate('tasas_guardar');
        var datos = angular.copy($ctrl.formData);

        $http.get(url, {params: datos}).then(function (response) {
            if (response.data.success) {
                $('#modalTasa').modal('hide');
                $ctrl.listar();
            } else {
                $ctrl.error = response.data.error;
            }
        }, function (response) {
            $ctrl.error = (response.data && response.data.error) ? response.data.error : 'Error al guardar la tasa';
        });
    };

    // Cargar al iniciar
    $ctrl.listar();
});
