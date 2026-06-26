app.controller('ngTipoPagoController', function ($scope, $http) {
    var $ctrl = this;

    $ctrl.items = [];
    $ctrl.formData = {};
    $ctrl.itemEliminar = {};
    $ctrl.isEdit = false;
    $ctrl.loading = false;
    $ctrl.guardando = false;
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
        var url = Routing.generate('tipo_pago_listar_json');

        $http.get(url, {
            params: {busqueda: $ctrl.busqueda, limit: $ctrl.limit, skip: skip}
        }).then(function (response) {
            $ctrl.items = response.data.data;
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

    $ctrl.abrirModalAgregar = function () {
        $ctrl.formData = {moneda: 'USD'};
        $ctrl.isEdit = false;
        $ctrl.error = null;
        $('#modalTipoPago').modal('show');
    };

    $ctrl.abrirModalEditar = function (id) {
        $ctrl.error = null;
        var url = Routing.generate('tipo_pago_obtener');

        $http.get(url, {params: {id: id}}).then(function (response) {
            if (response.data.success) {
                $ctrl.formData = angular.copy(response.data.tipo);
                $ctrl.isEdit = true;
                $('#modalTipoPago').modal('show');
            } else {
                showToast('error', response.data.error || 'No se pudo cargar el tipo de pago');
            }
        });
    };

    $ctrl.guardar = function () {
        if ($ctrl.guardando) return;
        $ctrl.error = null;
        $ctrl.guardando = true;
        var url = Routing.generate('tipo_pago_guardar');

        $http.get(url, {params: $ctrl.formData}).then(function (response) {
            if (response.data.success) {
                $('#modalTipoPago').modal('hide');
                showToast('success', $ctrl.isEdit ? 'Tipo de pago actualizado exitosamente' : 'Tipo de pago registrado exitosamente');
                $ctrl.listar();
            } else {
                $ctrl.error = response.data.error;
            }
            $ctrl.guardando = false;
        }, function (response) {
            $ctrl.error = (response && response.data && response.data.error) ? response.data.error : 'Error al guardar';
            $ctrl.guardando = false;
        });
    };

    $ctrl.confirmarEliminar = function (item) {
        $ctrl.itemEliminar = item;
        $('#modalEliminarTipoPago').modal('show');
    };

    $ctrl.eliminar = function () {
        var url = Routing.generate('tipo_pago_eliminar');

        $http.get(url, {params: {id: $ctrl.itemEliminar.id}}).then(function (response) {
            if (response.data.success) {
                $('#modalEliminarTipoPago').modal('hide');
                showToast('success', 'Tipo de pago eliminado exitosamente');
                $ctrl.listar();
            } else {
                showToast('error', response.data.error || 'No se pudo eliminar');
            }
        });
    };

    $ctrl.listar();
});
