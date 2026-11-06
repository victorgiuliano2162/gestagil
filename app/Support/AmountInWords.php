<?php

namespace App\Support;

class AmountInWords
{
    private static array $unidades = [
        0 => '',
        1 => 'um',
        2 => 'dois',
        3 => 'três',
        4 => 'quatro',
        5 => 'cinco',
        6 => 'seis',
        7 => 'sete',
        8 => 'oito',
        9 => 'nove',
        10 => 'dez',
        11 => 'onze',
        12 => 'doze',
        13 => 'treze',
        14 => 'quatorze',
        15 => 'quinze',
        16 => 'dezesseis',
        17 => 'dezessete',
        18 => 'dezoito',
        19 => 'dezenove',
    ];

    private static array $dezenas = [
        2 => 'vinte',
        3 => 'trinta',
        4 => 'quarenta',
        5 => 'cinquenta',
        6 => 'sessenta',
        7 => 'setenta',
        8 => 'oitenta',
        9 => 'noventa',
    ];

    private static array $centenas = [
        1 => 'cento',
        2 => 'duzentos',
        3 => 'trezentos',
        4 => 'quatrocentos',
        5 => 'quinhentos',
        6 => 'seiscentos',
        7 => 'setecentos',
        8 => 'oitocentos',
        9 => 'novecentos',
    ];

    /**
     * Converte um valor em centavos para texto por extenso em reais.
     * Exemplo: 25050 -> "duzentos e cinquenta reais e cinquenta centavos"
     */
    public static function format(int $cents): string
    {
        if ($cents === 0) {
            return 'zero reais';
        }

        $reais = intdiv($cents, 100);
        $restoCentavos = $cents % 100;

        $partes = [];

        if ($reais > 0) {
            $textoReais = self::converterMilhares($reais);
            $moeda = $reais === 1 ? 'real' : 'reais';
            $partes[] = "{$textoReais} {$moeda}";
        }

        if ($restoCentavos > 0) {
            $textoCentavos = self::converterAte999($restoCentavos);
            $moedaCentavo = $restoCentavos === 1 ? 'centavo' : 'centavos';
            $partes[] = "{$textoCentavos} {$moedaCentavo}";
        }

        return implode(' e ', $partes);
    }

    private static function converterMilhares(int $num): string
    {
        if ($num === 1000) {
            return 'mil';
        }

        $milhoes = intdiv($num, 1_000_000);
        $restoMilhoes = $num % 1_000_000;

        $milhares = intdiv($restoMilhoes, 1_000);
        $restoMilhares = $restoMilhoes % 1_000;

        $resultado = [];

        if ($milhoes > 0) {
            $strMilhoes = self::converterAte999($milhoes);
            $sufixo = $milhoes === 1 ? 'milhão' : 'milhões';
            $resultado[] = "{$strMilhoes} {$sufixo}";
        }

        if ($milhares > 0) {
            if ($milhares === 1) {
                $resultado[] = 'mil';
            } else {
                $resultado[] = self::converterAte999($milhares) . ' mil';
            }
        }

        if ($restoMilhares > 0) {
            $resultado[] = self::converterAte999($restoMilhares);
        }

        return implode(' e ', $resultado);
    }

    private static function converterAte999(int $num): string
    {
        if ($num === 100) {
            return 'cem';
        }

        if ($num < 20) {
            return self::$unidades[$num];
        }

        if ($num < 100) {
            $d = intdiv($num, 10);
            $u = $num % 10;
            return $u > 0 ? self::$dezenas[$d] . ' e ' . self::$unidades[$u] : self::$dezenas[$d];
        }

        $c = intdiv($num, 100);
        $resto = $num % 100;

        if ($resto === 0) {
            return self::$centenas[$c];
        }

        return self::$centenas[$c] . ' e ' . self::converterAte999($resto);
    }
}
