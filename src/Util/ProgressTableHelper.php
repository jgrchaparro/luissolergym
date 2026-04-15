<?php

namespace App\Util;

/**
 * Helper reutilizable para mostrar tablas de progreso de procesamiento de archivos
 * con formato consistente y alineación perfecta
 *
 * USO:
 * $table = new ProgressTableHelper();
 * $table->mostrarEncabezado();
 *
 * foreach ($archivos as $archivo) {
 *     $resultado = $table->procesarConProgreso($archivo, function($line) {
 *         // Tu lógica de procesamiento
 *     });
 * }
 *
 * $table->mostrarPie($resultados);
 */
class ProgressTableHelper
{
    // Anchos de columnas (configurables)
    private int $anchoNombre;
    private int $anchoLineas;
    private int $anchoTiempo;
    private int $anchoMemoria;
    private int $anchoStatus;
    private int $anchoTotal;

    /**
     * @param int $anchoNombre Ancho para nombre de archivo
     * @param int $anchoLineas Ancho para número de líneas
     * @param int $anchoTiempo Ancho para tiempo
     * @param int $anchoMemoria Ancho para memoria
     * @param int $anchoStatus Ancho para status
     */
    public function __construct(
        int $anchoNombre = 40,
        int $anchoLineas = 12,
        int $anchoTiempo = 10,
        int $anchoMemoria = 10,
        int $anchoStatus = 12
    )
    {
        $this->anchoNombre = $anchoNombre;
        $this->anchoLineas = $anchoLineas;
        $this->anchoTiempo = $anchoTiempo;
        $this->anchoMemoria = $anchoMemoria;
        $this->anchoStatus = $anchoStatus;

        // Calcular ancho total (incluye separadores: 4 "│" = 12 caracteres)
        $this->anchoTotal = $anchoNombre + $anchoLineas + $anchoTiempo + $anchoMemoria + $anchoStatus + 12;
    }

    /**
     * Muestra encabezado de la tabla
     */
    public function mostrarEncabezado(): void
    {
        echo PHP_EOL;
        echo str_repeat('═', $this->anchoTotal) . PHP_EOL;

        $header = sprintf(
            "%-{$this->anchoNombre}s │ %{$this->anchoLineas}s │ %{$this->anchoTiempo}s │ %{$this->anchoMemoria}s │ %{$this->anchoStatus}s",
            "ARCHIVO",
            "LÍNEAS",
            "TIEMPO",
            "MEMORIA",
            "STATUS"
        );

        echo $header . PHP_EOL;
        echo str_repeat('─', $this->anchoTotal) . PHP_EOL;
    }

    /**
     * Procesa un archivo mostrando progreso inline y devuelve métricas
     *
     * @param string $filePath Ruta completa del archivo
     * @param callable $procesadorLinea Función que procesa cada línea: function($line, $numLinea): bool
     * @param int $intervaloProgreso Cada cuántas líneas actualizar progreso
     * @return array Métricas del procesamiento
     */
    public function procesarConProgreso(
        string $filePath,
        callable $procesadorLinea,
        int $intervaloProgreso = 1000
    ): array
    {
        $fileName = basename($filePath);
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            $this->mostrarResumenArchivo($fileName, 0, 0, 0, 'ERROR');
            return [
                'archivo' => $fileName,
                'lineas' => 0,
                'tiempo' => 0,
                'memoria' => 0,
                'status' => 'ERROR'
            ];
        }

        // Métricas
        $tiempoInicio = microtime(true);
        $memoriaInicio = memory_get_usage();

        // Contar líneas
        $lineasTotales = $this->contarLineas($filePath);
        $lineasProcesadas = 0;
        $lineasExitosas = 0;

        // Preparar progreso
        $anchoNumero = strlen(number_format($lineasTotales, 0, ',', '.'));
        $nombreCorto = $this->truncarNombre($fileName, $this->anchoNombre);

        // Procesar archivo
        while (($line = fgets($handle)) !== false) {
            $lineasProcesadas++;

            // Actualizar progreso
            if ($lineasProcesadas % $intervaloProgreso == 0 || $lineasProcesadas == $lineasTotales) {
                $this->mostrarProgresoInline(
                    $nombreCorto,
                    $lineasTotales,
                    $lineasProcesadas,
                    $anchoNumero
                );
            }

            // Procesar línea
            $resultado = call_user_func($procesadorLinea, $line, $lineasProcesadas);
            if ($resultado) {
                $lineasExitosas++;
            }
        }

        fclose($handle);

        // Métricas finales
        $tiempoTotal = microtime(true) - $tiempoInicio;
        $memoriaUsada = memory_get_usage() - $memoriaInicio;

        // Limpiar línea de progreso
        echo "\r" . str_repeat(' ', $this->anchoTotal + 20) . "\r";

        // Mostrar resumen
        $this->mostrarResumenArchivo(
            $fileName,
            $lineasProcesadas,
            $tiempoTotal,
            $memoriaUsada,
            'OK'
        );

        return [
            'archivo' => $fileName,
            'lineas' => $lineasProcesadas,
            'lineasExitosas' => $lineasExitosas,
            'tiempo' => $tiempoTotal,
            'memoria' => $memoriaUsada,
            'status' => 'OK'
        ];
    }

    /**
     * Muestra progreso inline mientras se procesa
     */
    public function mostrarProgresoInline(
        string $nombreCorto,
        int $total,
        int $actual,
        int $anchoNumero
    ): void
    {
        $porcentaje = round(($actual / $total) * 100, 1);

        $totalStr = str_pad(number_format($total, 0, ',', '.'), $anchoNumero, ' ', STR_PAD_LEFT);
        $actualStr = str_pad(number_format($actual, 0, ',', '.'), $anchoNumero, ' ', STR_PAD_LEFT);
        $porcStr = str_pad(number_format($porcentaje, 1), 5, ' ', STR_PAD_LEFT);

        // Barra de progreso
        $barWidth = 20;
        $completed = (int)($porcentaje / 100 * $barWidth);
        $barra = '[' . str_repeat('█', $completed) . str_repeat('░', $barWidth - $completed) . ']';

        $progreso = sprintf(
            "%-{$this->anchoNombre}s │ %s/%s (%s%%) %s",
            $nombreCorto,
            $actualStr,
            $totalStr,
            $porcStr,
            $barra
        );

        echo "\r" . str_repeat(' ', $this->anchoTotal + 40) . "\r" . $progreso;
    }

    /**
     * Muestra resumen del archivo en UNA LÍNEA
     */
    public function mostrarResumenArchivo(
        string $fileName,
        int $lineas,
        float $tiempo,
        int $memoria,
        string $status
    ): void
    {
        $nombreCorto = $this->truncarNombre($fileName, $this->anchoNombre);
        $lineasStr = str_pad(number_format($lineas, 0, ',', '.'), $this->anchoLineas, ' ', STR_PAD_LEFT);
        $tiempoStr = str_pad($this->formatTiempo($tiempo), $this->anchoTiempo, ' ', STR_PAD_LEFT);
        $memoriaStr = str_pad($this->formatBytes($memoria), $this->anchoMemoria, ' ', STR_PAD_LEFT);

        $statusIcons = [
            'OK' => '✓',
            'ERROR' => '✗',
            'PARCIAL' => '⚠',
            'SKIP' => '○'
        ];
        $statusIcon = $statusIcons[$status] ?? '•';
        $statusStr = str_pad($statusIcon . ' ' . $status, $this->anchoStatus, ' ', STR_PAD_LEFT);

        $resumen = sprintf(
            "%-{$this->anchoNombre}s │ %s │ %s │ %s │ %s",
            $nombreCorto,
            $lineasStr,
            $tiempoStr,
            $memoriaStr,
            $statusStr
        );

        echo $resumen . PHP_EOL;
    }

    /**
     * Muestra pie de tabla con totales
     *
     * @param array $resultados Array de resultados con keys: archivo, lineas, tiempo, memoria, status
     */
    public function mostrarPie(array $resultados): void
    {
        echo str_repeat('─', $this->anchoTotal) . PHP_EOL;

        if (!empty($resultados)) {
            $totalLineas = array_sum(array_column($resultados, 'lineas'));
            $totalTiempo = array_sum(array_column($resultados, 'tiempo'));
            $maxMemoria = max(array_column($resultados, 'memoria'));

            $totalResumen = sprintf(
                "%-{$this->anchoNombre}s │ %s │ %s │ %s │ %s",
                "TOTAL (" . count($resultados) . " archivos)",
                str_pad(number_format($totalLineas, 0, ',', '.'), $this->anchoLineas, ' ', STR_PAD_LEFT),
                str_pad($this->formatTiempo($totalTiempo), $this->anchoTiempo, ' ', STR_PAD_LEFT),
                str_pad($this->formatBytes($maxMemoria), $this->anchoMemoria, ' ', STR_PAD_LEFT),
                str_pad('', $this->anchoStatus, ' ', STR_PAD_LEFT)
            );

            echo $totalResumen . PHP_EOL;
        }

        echo str_repeat('═', $this->anchoTotal) . PHP_EOL;
        echo PHP_EOL;
    }

    /**
     * Muestra separador entre secciones
     */
    public function mostrarSeparador(string $texto = ''): void
    {
        if ($texto) {
            $padding = floor(($this->anchoTotal - strlen($texto) - 2) / 2);
            echo str_repeat('─', (int)$padding) . ' ' . $texto . ' ' . str_repeat('─', (int)$padding) . PHP_EOL;
        } else {
            echo str_repeat('─', $this->anchoTotal) . PHP_EOL;
        }
    }

    // ========================================================================
    // FUNCIONES DE FORMATEO
    // ========================================================================

    /**
     * Trunca nombre de archivo si es muy largo
     */
    public function truncarNombre(string $nombre, int $maxLength): string
    {
        if (strlen($nombre) <= $maxLength) {
            return $nombre;
        }

        $inicio = substr($nombre, 0, 18);
        $fin = substr($nombre, -($maxLength - 21));
        return $inicio . '...' . $fin;
    }

    /**
     * Formatea tiempo en formato legible
     */
    public function formatTiempo(float $segundos): string
    {
        if ($segundos < 60) {
            return number_format($segundos, 1) . 's';
        } elseif ($segundos < 3600) {
            $minutos = floor($segundos / 60);
            $segs = $segundos % 60;
            return sprintf('%dm %02ds', $minutos, $segs);
        } else {
            $horas = floor($segundos / 3600);
            $minutos = floor(($segundos % 3600) / 60);
            return sprintf('%dh %02dm', $horas, $minutos);
        }
    }

    /**
     * Formatea bytes a formato legible
     */
    public function formatBytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Cuenta líneas de un archivo eficientemente
     */
    public function contarLineas(string $filepath): int
    {
        // Intentar comando del sistema (más rápido)
        if (DIRECTORY_SEPARATOR === '/' && function_exists('shell_exec')) {
            $output = shell_exec("wc -l < " . escapeshellarg($filepath));
            if ($output !== null) {
                return (int)trim($output);
            }
        }

        // Fallback manual
        $count = 0;
        $handle = fopen($filepath, 'r');
        if ($handle) {
            while (!feof($handle)) {
                fgets($handle);
                $count++;
            }
            fclose($handle);
        }
        return $count;
    }
}
