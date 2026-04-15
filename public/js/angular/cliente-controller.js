app.controller('ngClienteController', function ($scope, $http) {
    var $ctrl = this;

    $ctrl.clientes = [];
    $ctrl.formData = {};
    $ctrl.clienteEliminar = {};
    $ctrl.isEdit = false;
    $ctrl.loading = false;
    $ctrl.error = null;
    $ctrl.nacionalidades = ['V', 'E', 'P', 'J', 'G', 'M', 'C'];
    $ctrl.tiposMensualidad = [];

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
            params: {busqueda: $ctrl.busqueda, limit: $ctrl.limit, skip: skip}
        }).then(function (response) {
            $ctrl.clientes = response.data.data;
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

    $ctrl.cargarTiposMensualidad = function () {
        var url = Routing.generate('cliente_tipos_mensualidad');
        $http.get(url).then(function (response) {
            $ctrl.tiposMensualidad = response.data;
        });
    };

    $ctrl.abrirModalAgregar = function () {
        $ctrl.formData = {nacionalidad: 'V'};
        $ctrl.isEdit = false;
        $ctrl.error = null;
        $('#modalCliente').modal('show');
    };

    $ctrl.abrirModalEditar = function (id) {
        $ctrl.error = null;
        var url = Routing.generate('cliente_obtener');

        $http.get(url, {params: {id: id}}).then(function (response) {
            if (response.data.success) {
                $ctrl.formData = angular.copy(response.data.cliente);
                if ($ctrl.formData.cedula) {
                    var letra = $ctrl.formData.cedula.charAt(0).toUpperCase();
                    if ($ctrl.nacionalidades.indexOf(letra) !== -1) {
                        $ctrl.formData.nacionalidad = letra;
                        $ctrl.formData.cedulaNumero = $ctrl.formData.cedula.substring(1);
                    } else {
                        $ctrl.formData.nacionalidad = 'V';
                        $ctrl.formData.cedulaNumero = $ctrl.formData.cedula;
                    }
                }
                $ctrl.isEdit = true;
                $('#modalCliente').modal('show');
            }
        });
    };

    $ctrl.guardar = function () {
        $ctrl.error = null;
        var url = Routing.generate('cliente_guardar');
        var datos = angular.copy($ctrl.formData);
        datos.cedula = datos.nacionalidad + datos.cedulaNumero;
        delete datos.nacionalidad;
        delete datos.cedulaNumero;

        $http.get(url, {params: datos}).then(function (response) {
            if (response.data.success) {
                $('#modalCliente').modal('hide');
                showToast('success', $ctrl.isEdit ? 'Cliente actualizado exitosamente' : 'Cliente registrado exitosamente');
                $ctrl.listar();
            } else {
                $ctrl.error = response.data.error;
            }
        }, function () {
            $ctrl.error = 'Error al guardar el cliente';
        });
    };

    $ctrl.verDetalle = function (id) {
        location.href = Routing.generate('cliente_detalle', {id: id});
    };

    $ctrl.confirmarEliminar = function (cliente) {
        $ctrl.clienteEliminar = cliente;
        $('#modalEliminar').modal('show');
    };

    $ctrl.eliminar = function () {
        var url = Routing.generate('cliente_eliminar');

        $http.get(url, {params: {id: $ctrl.clienteEliminar.id}}).then(function (response) {
            if (response.data.success) {
                $('#modalEliminar').modal('hide');
                showToast('success', 'Cliente eliminado exitosamente');
                $ctrl.listar();
            }
        });
    };

    // Cargar al iniciar
    $ctrl.listar();
    $ctrl.cargarTiposMensualidad();
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
