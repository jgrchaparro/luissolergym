<?php

namespace App\Util;

class ProgressBar
{
    /**
     * Muestra una barra de progreso inline sin salto de línea
     *
     * @param string $label Etiqueta descriptiva (máx 40 caracteres recomendado)
     * @param int $total Total de elementos a procesar
     * @param int $actual Elementos procesados hasta el momento
     * @param int $anchoNumero Ancho para formatear números (opcional, se calcula automático)
     * @param int $barWidth Ancho de la barra de progreso (default: 20)
     */
    public static function mostrar(
        string $label,
        int $total,
        int $actual,
        int $anchoNumero = 0,
        int $barWidth = 20
    ): void {
        // Calcular ancho automático si no se proporciona
        if ($anchoNumero === 0) {
            $anchoNumero = strlen(number_format($total, 0, ',', '.'));
        }

        $porcentaje = $total > 0 ? round(($actual / $total) * 100, 1) : 0;

        $totalStr = str_pad(number_format($total, 0, ',', '.'), $anchoNumero, ' ', STR_PAD_LEFT);
        $actualStr = str_pad(number_format($actual, 0, ',', '.'), $anchoNumero, ' ', STR_PAD_LEFT);
        $porcStr = str_pad(number_format($porcentaje, 1), 5, ' ', STR_PAD_LEFT);

        // Construir barra de progreso
        $completed = (int)($porcentaje / 100 * $barWidth);
        $barra = '[' . str_repeat('█', $completed) . str_repeat('░', $barWidth - $completed) . ']';

        $progreso = sprintf(
            "%s/%s (%s%%) %s",
            $actualStr,
            $totalStr,
            $porcStr,
            $barra
        );

        // Limpiar línea anterior y mostrar progreso
        echo "\r" . str_repeat(' ', 120) . "\r" . $progreso;

        // Flush del buffer para mostrar inmediatamente
        flush();
    }

    /**
     * Limpia la línea de progreso
     */
    public static function limpiar(): void
    {
        echo "\r" . str_repeat(' ', 120) . "\r";
        flush();
    }

    /**
     * Completa la barra de progreso y hace salto de línea
     */
    public static function finalizar(): void
    {
        echo PHP_EOL;
        flush();
    }
}
