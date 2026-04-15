app.controller('ngEntesIndex', function ($http, $uibModal, $timeout) {
    var $ctrl = this;

    $ctrl.documento = {};
    $ctrl.archivos = [];
    $ctrl.filtros = {};
    $ctrl.isLoading = false;
    $ctrl.totalItems = 0;
    $ctrl.currentPage = 1;

    $ctrl.numItems = 15;
    $ctrl.numItemList = [
        {name: 'Ver 15', value: 15},
        {name: 'Ver 25', value: 25},
        {name: 'Ver 50', value: 50},
        {name: 'Ver 100', value: 100}
    ];

    $ctrl.init = function () {

        $timeout(function () {
            $ctrl.filtros.nombre = '';
            $ctrl.filtros.fecha = angular.element('#fecha').val();
            $ctrl.getRecaudadores();

        }, 500);
    };

    //-----------------begin PAGINATOR-----------------//

    $ctrl.setPage = function (pageNo) {
        $ctrl.currentPage = pageNo;
    };
    $ctrl.pageChanged = function () {
        $ctrl.getRecaudadores();
    };
    $ctrl.setNumItems = function () {
        $ctrl.currentPage = 1;
        $ctrl.getRecaudadores();
    };

    //-----------------end   PAGINATOR-----------------//

    $ctrl.getRecaudadores = function () {
        var $url = Routing.generate('super_recaudadores_listarDT');

        $ctrl.isLoading = true;
        $ctrl.filtros.skip = $ctrl.currentPage;
        $ctrl.filtros.limit = $ctrl.numItems;
        $http.get(
            $url,
            {
                'params': $ctrl.filtros
            }
        ).then(function (response) {
            $ctrl.recaudadores = response.data.data;
            $ctrl.totalItems = response.data.count;
            $ctrl.numPages = 6;
            $ctrl.isLoading = false;
        });
    };

    $ctrl.detalleRecaudador = function (recaudador) {
        let $filtros = {};
        $filtros.codigo = recaudador.codigo;
        location.href = Routing.generate("recaudador_archivos_conciliacion", $filtros);
    };

    $ctrl.editarRecaudador = function (recaudador) {
        location.href = Routing.generate("recaudador_editar", {id: recaudador.id});
    };

    $ctrl.settingsRecaudador = function (recaudador) {
        location.href = Routing.generate("recaudador_settings", {id: recaudador.id});
    };
});