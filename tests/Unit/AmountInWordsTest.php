<?php

use App\Support\AmountInWords;

test('formats cents into brazilian real amount in words correctly', function () {
    expect(AmountInWords::format(0))->toBe('zero reais')
        ->and(AmountInWords::format(1))->toBe('um centavo')
        ->and(AmountInWords::format(10))->toBe('dez centavos')
        ->and(AmountInWords::format(100))->toBe('um real')
        ->and(AmountInWords::format(101))->toBe('um real e um centavo')
        ->and(AmountInWords::format(200))->toBe('dois reais')
        ->and(AmountInWords::format(10000))->toBe('cem reais')
        ->and(AmountInWords::format(25050))->toBe('duzentos e cinquenta reais e cinquenta centavos')
        ->and(AmountInWords::format(100000))->toBe('mil reais')
        ->and(AmountInWords::format(154230))->toBe('mil e quinhentos e quarenta e dois reais e trinta centavos');
});
