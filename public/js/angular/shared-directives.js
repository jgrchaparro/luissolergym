/**
 * Selector de Tipo de Mensualidad con monto resaltado y alineado a la derecha.
 * Reemplaza al <select> nativo (que no permite estilar cada opción).
 *
 * Uso:
 *   <mensualidad-picker items="$ctrl.tiposMensualidad"
 *                       model="$ctrl.formData.tipo_mensualidad_id"
 *                       disabled="$ctrl.guardando"></mensualidad-picker>
 */
app.directive('mensualidadPicker', function ($document) {
    return {
        restrict: 'E',
        scope: {
            items: '=',
            model: '=',
            disabled: '='
        },
        template:
            '<div style="position: relative;">' +
            '  <button type="button" class="form-control" ng-click="toggle()" ng-disabled="disabled"' +
            '          style="display: flex; align-items: center; text-align: left; cursor: pointer;">' +
            '    <span ng-if="!seleccionado()" class="text-muted" style="flex: 1;">-- Seleccione --</span>' +
            '    <span ng-if="seleccionado()" style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' +
            '      {{ seleccionado().codigo }} - {{ seleccionado().descripcion }}</span>' +
            '    <span ng-if="seleccionado()" class="label label-success"' +
            '          style="font-size: 12px; margin-left: 8px;">$ {{ seleccionado().monto | number:2 }}</span>' +
            '    <i class="fa fa-caret-down text-muted" style="margin-left: 8px;"></i>' +
            '  </button>' +
            '  <div class="list-group" ng-show="abierto"' +
            '       style="position: absolute; width: 100%; z-index: 1060; max-height: 240px; overflow-y: auto;' +
            '              margin-top: 2px; margin-bottom: 0; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">' +
            '    <a href class="list-group-item" ng-click="elegir(null)">' +
            '      <span class="text-muted">-- Seleccione --</span></a>' +
            '    <a href class="list-group-item" ng-repeat="t in items" ng-click="elegir(t)"' +
            '       ng-class="{active: t.id === model}" style="display: flex; align-items: center;">' +
            '      <span style="flex: 1;">{{ t.codigo }} - {{ t.descripcion }}</span>' +
            '      <span class="label" ng-class="t.id === model ? \'label-default\' : \'label-success\'"' +
            '            style="font-size: 13px;">$ {{ t.monto | number:2 }}</span>' +
            '    </a>' +
            '  </div>' +
            '</div>',
        link: function (scope, element) {
            scope.abierto = false;

            scope.seleccionado = function () {
                var items = scope.items || [];
                for (var i = 0; i < items.length; i++) {
                    if (items[i].id === scope.model) {
                        return items[i];
                    }
                }
                return null;
            };

            scope.toggle = function () {
                if (scope.disabled) return;
                scope.abierto = !scope.abierto;
            };

            scope.elegir = function (item) {
                scope.model = item ? item.id : '';
                scope.abierto = false;
            };

            // Cerrar al hacer clic fuera del componente.
            var onDocClick = function (event) {
                if (!element[0].contains(event.target) && scope.abierto) {
                    scope.$apply(function () {
                        scope.abierto = false;
                    });
                }
            };
            $document.on('click', onDocClick);
            scope.$on('$destroy', function () {
                $document.off('click', onDocClick);
            });
        }
    };
});
