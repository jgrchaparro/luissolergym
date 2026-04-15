app.controller('ngClienteDetalleController', function ($scope, $http) {
    var $ctrl = this;

    $ctrl.clienteId = null;
    $ctrl.tiposPago = [];
    $ctrl.pago = {};
    $ctrl.error = null;
    $ctrl.guardando = false;

    $ctrl.init = function (clienteId) {
        $ctrl.clienteId = clienteId;
        $ctrl.cargarTiposPago();
    };

    $ctrl.cargarTiposPago = function () {
        var url = Routing.generate('cliente_tipos_pago');
        $http.get(url).then(function (response) {
            $ctrl.tiposPago = response.data;
        });
    };

    $ctrl.abrirModalPago = function () {
        $ctrl.pago = {};
        $ctrl.error = null;
        $('#modalAgregarPago').modal('show');
    };

    $ctrl.guardarPago = function () {
        if ($ctrl.guardando) return;
        $ctrl.error = null;
        $ctrl.guardando = true;

        var url = Routing.generate('cliente_registrar_pago');
        var params = {
            cliente_id: $ctrl.clienteId,
            tipo_pago_id: $ctrl.pago.tipo_pago_id,
            monto: $ctrl.pago.monto,
            observaciones: $ctrl.pago.observaciones || ''
        };

        $http.get(url, {params: params}).then(function (response) {
            $ctrl.guardando = false;
            if (response.data.success) {
                $('#modalAgregarPago').modal('hide');
                showToast('success', 'Pago registrado exitosamente');
                setTimeout(function () {
                    window.location.reload();
                }, 700);
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
});
