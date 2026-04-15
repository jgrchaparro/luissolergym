app.controller('ngTipoMensualidadController', function ($scope, $http) {
    var $ctrl = this;

    $ctrl.items = [];
    $ctrl.formData = {};
    $ctrl.itemEliminar = {};
    $ctrl.isEdit = false;
    $ctrl.loading = false;
    $ctrl.error = null;

    $ctrl.busqueda = '';
    $ctrl.total = 0;
    $ctrl.limit = 10;
    $ctrl.paginaActual = 1;
    $ctrl.totalPaginas = 1;
    $ctrl.paginas = [];

    $ctrl.opcionesDescripcion = [
        'Full',
        'Personalizado VIP',
        'Personalizado 1',
        'Personalizado 2',
        'Todos los Aparatos',
        'Solo Maquinas',
        'Otro'
    ];

    $ctrl.checksSeleccionados = {};

    $ctrl.listar = function () {
        $ctrl.loading = true;
        var skip = ($ctrl.paginaActual - 1) * $ctrl.limit;
        var url = Routing.generate('tipo_mensualidad_listar_json');

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

    $ctrl.resetChecks = function () {
        $ctrl.checksSeleccionados = {};
        $ctrl.opcionesDescripcion.forEach(function (op) {
            $ctrl.checksSeleccionados[op] = false;
        });
    };

    $ctrl.cargarChecksDesdeDescripcion = function (descripcion) {
        $ctrl.resetChecks();
        if (descripcion) {
            var partes = descripcion.split(',');
            partes.forEach(function (parte) {
                var trimmed = parte.trim();
                if ($ctrl.checksSeleccionados.hasOwnProperty(trimmed)) {
                    $ctrl.checksSeleccionados[trimmed] = true;
                }
            });
        }
    };

    $ctrl.construirDescripcion = function () {
        var seleccionados = [];
        $ctrl.opcionesDescripcion.forEach(function (op) {
            if ($ctrl.checksSeleccionados[op]) {
                seleccionados.push(op);
            }
        });
        return seleccionados.join(',');
    };

    $ctrl.abrirModalAgregar = function () {
        $ctrl.formData = {};
        $ctrl.isEdit = false;
        $ctrl.error = null;
        $ctrl.resetChecks();
        $('#modalTipoMensualidad').modal('show');
    };

    $ctrl.abrirModalEditar = function (id) {
        $ctrl.error = null;
        var url = Routing.generate('tipo_mensualidad_obtener');

        $http.get(url, {params: {id: id}}).then(function (response) {
            if (response.data.success) {
                $ctrl.formData = angular.copy(response.data.tipo);
                $ctrl.isEdit = true;
                $ctrl.cargarChecksDesdeDescripcion($ctrl.formData.descripcion);
                $('#modalTipoMensualidad').modal('show');
            }
        });
    };

    $ctrl.guardar = function () {
        $ctrl.error = null;
        var url = Routing.generate('tipo_mensualidad_guardar');
        var datos = angular.copy($ctrl.formData);
        datos.descripcion = $ctrl.construirDescripcion();

        $http.get(url, {params: datos}).then(function (response) {
            if (response.data.success) {
                $('#modalTipoMensualidad').modal('hide');
                showToast('success', $ctrl.isEdit ? 'Tipo de mensualidad actualizado exitosamente' : 'Tipo de mensualidad registrado exitosamente');
                $ctrl.listar();
            } else {
                $ctrl.error = response.data.error;
            }
        }, function () {
            $ctrl.error = 'Error al guardar';
        });
    };

    $ctrl.confirmarEliminar = function (item) {
        $ctrl.itemEliminar = item;
        $('#modalEliminarTipo').modal('show');
    };

    $ctrl.eliminar = function () {
        var url = Routing.generate('tipo_mensualidad_eliminar');

        $http.get(url, {params: {id: $ctrl.itemEliminar.id}}).then(function (response) {
            if (response.data.success) {
                $('#modalEliminarTipo').modal('hide');
                showToast('success', 'Tipo de mensualidad eliminado exitosamente');
                $ctrl.listar();
            }
        });
    };

    $ctrl.listar();
});
