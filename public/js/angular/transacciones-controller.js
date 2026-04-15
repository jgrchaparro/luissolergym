app.controller('ngModalMovimientoController', function ($scope, $rootScope, $http, $uibModalInstance) {
    var $ctrl = this;

    $ctrl.error = null;

    $ctrl.formData = {
        fecha_operacion: moment() //moment(new Date()).format("DD-MM-YYYY")
    };

    $ctrl.datePickerOptions = {
        autoApply: true,
        singleDatePicker: true,
        locale: {
            format: 'DD-MM-YYYY'
        }
    };

    $ctrl.registrarMovimiento = function ($event) {
        $event.preventDefault();

        $url = Routing.generate('movimiento_bancario_registrar');

        const fechaOper = moment($ctrl.formData.fecha_operacion, "DD-MMMM-YYYY");
        $fechaOperacion = fechaOper.format('YYYY-MM-DD');

        const fechaRecaudacion = moment(angular.element('#filtros_fecha').val(), "DD-MMMM-YYYY");
        $fechaRecaudacion = fechaRecaudacion.format('YYYY-MM-DD');

        $ctrl.formData.fecha_recaudacion = $fechaRecaudacion;
        $ctrl.formData.fecha_operacion = $fechaOperacion;
        $ctrl.formData.codigo_recaudador = angular.element('#codigo_recaudador').val();

        $http.get(
            $url,
            {
                params: $ctrl.formData
            }
        ).then(function (response) {
            if (response.data.success) {
                $ctrl.cancel();
                $rootScope.$emit('onMovimientoRegistrado');
            } else {
                $ctrl.error = response.data.error;
            }
        });
    };

    $ctrl.cancel = function () {
        $uibModalInstance.close(false);
    };
});

app.controller('ngTransaccionesController', function ($scope, $rootScope, $http, $uibModal) {
    var $ctrl = this;

    $ctrl.movimientos = [];
    $ctrl.total = 0;

    $ctrl.init = function (fecha) {
        if (fecha === '') {
            fecha = moment().format("DD-MM-YYYY");
        }

        $ctrl.fecha_recaudacion = fecha;

        $ctrl.listarMovimientos();
    };

    $rootScope.$on('onMovimientoRegistrado', function () {
        $ctrl.listarMovimientos();
    });

    $ctrl.addMovimiento = function () {
        $ctrl.modalMovimiento = $uibModal.open({
            controller: 'ngModalMovimientoController as $ctrl',
            templateUrl: 'mdlMovimiento.html',
            scope: $scope
        });
    };

    $ctrl.listarMovimientos = function () {
        $ctrl.filters = {
            fecha_recaudacion: $ctrl.fecha_recaudacion,
            codigo_recaudador: angular.element('#codigo_recaudador').val()
        }

        $url = Routing.generate('movimiento_bancario_listar');

        $http.get(
            $url,
            {
                params: $ctrl.filters
            }
        ).then(function (response) {
            $ctrl.movimientos = response.data.movimientos;
            $ctrl.total = response.data.total;
        });
    };

    $ctrl.deleteMovimientoBancario = function (movimiento) {
        $url = Routing.generate('movimiento_bancario_eliminar');

        $ctrl.filters = {
            idMovimiento: movimiento.id
        }

        $ctrl.isLoading = true;
        $http.get(
            $url,
            {
                params: $ctrl.filters
            }
        ).then(function (response) {
            $ctrl.listarMovimientos()
            $ctrl.isLoading = false;
        });
    }
});