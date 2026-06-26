app.controller('ngVencidosController', function ($scope, $http) {
    var $ctrl = this;

    $ctrl.clientes = [];
    $ctrl.loading = false;

    $ctrl.busqueda = '';
    $ctrl.total = 0;
    $ctrl.limit = 10;
    $ctrl.paginaActual = 1;
    $ctrl.totalPaginas = 1;
    $ctrl.paginas = [];

    $ctrl.listar = function () {
        $ctrl.loading = true;
        var skip = ($ctrl.paginaActual - 1) * $ctrl.limit;
        var url = Routing.generate('cliente_listar_json');

        $http.get(url, {
            params: {
                busqueda: $ctrl.busqueda,
                estado: 'vencido',
                orden: 'venc_desc',
                limit: $ctrl.limit,
                skip: skip
            }
        }).then(function (response) {
            $ctrl.clientes = response.data.data;
            $ctrl.total = response.data.total;
            $ctrl.totalPaginas = Math.ceil($ctrl.total / $ctrl.limit) || 1;
            $ctrl.calcularPaginas();
            $ctrl.loading = false;
        }, function () {
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

    $ctrl.verDetalle = function (id) {
        location.href = Routing.generate('cliente_detalle', {id: id});
    };

    $ctrl.listar();
});
