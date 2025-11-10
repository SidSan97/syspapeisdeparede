<?php

if (! function_exists('left_zero')) {

    /**
     * Formata string colocando zeros à esquerda.
     *
     * @param  string  $value
     * @param  int  $qty
     * @return string
     */
    function left_zero($value, $digits = 11)
    {
        return \Illuminate\Support\Str::of($value)->padLeft($digits, '0');
    }
}

if (! function_exists('cpf_db')) {

    /**
     * Formata campo CNPJ para salvar no banco de dados.
     *
     * @param  string  $value
     * @return string
     */
    function cpf_db($value = null)
    {
        if (! $value) {
            return null;
        }

        return left_zero(str_replace(['.', '-'], '', $value), 11);
    }
}

if (! function_exists('cpf_view')) {

    /**
     * Formata campo CNPJ para exibir nas view.
     *
     * @param  string  $value
     * @return string
     */
    function cpf_view($value)
    {
        return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", '$1.$2.$3-$4', $value);
    }
}

if (! function_exists('cnpj_db')) {

    /**
     * Formata campo CNPJ para salvar no banco de dados.
     *
     * @param  string  $value
     * @return string
     */
    function cnpj_db($value = null)
    {
        if (! $value) {
            return null;
        }

        return left_zero(str_replace(['.', '-', '/'], '', $value), 14);
    }
}

if (! function_exists('cnpj_view')) {

    /**
     * Formata campo CNPJ para exibir nas view.
     *
     * @param  string  $value
     * @return string
     */
    function cnpj_view($value)
    {
        return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", '$1.$2.$3/$4-$5', $value);
    }
}

if (! function_exists('phone_db')) {

    /**
     * Formata campo de telefone para salvar no banco de dados.
     *
     * @param  string  $value
     * @return string
     */
    function phone_db($value)
    {
        return str_replace(['(', ')', '-', ' '], [''], $value);
    }
}

if (! function_exists('phone_view')) {

    /**
     * Formata campo de telefone para exibir nas views.
     *
     * @param  string  $value
     * @return string
     */
    function phone_view($value)
    {
        if (strlen($value) == 10) {
            return preg_replace("/(\d{2})(\d{4})/", '($1) $2-$3', $value);
        }

        return preg_replace("/(\d{2})(\d{1})(\d{4})/", '($1) $2 $3-$4', $value);
    }
}
if (! function_exists('zip_code_db')) {

    /**
     * Formata campo de telefone para salvar no banco de dados.
     *
     * @param  string  $value
     * @return string
     */
    function zip_code_db($value)
    {
        return str_replace(['(', ')', '-', ' ', '.'], [''], $value);
    }
}

if (! function_exists('zip_code_view')) {

    /**
     * Formata campo de telefone para exibir nas views.
     *
     * @param  string  $value
     * @return string
     */
    function zip_code_view($value)
    {
        return preg_replace("/(\d{5})(\d{3})/", '$1-$2', $value);
    }
}

if (! function_exists('money_db')) {

    function money_db($money)
    {
        $isNegative = strpos($money, '-') !== false;
        $dotPos = strrpos($money, '.');
        $commaPos = strrpos($money, ',');
        $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos : ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);

        if (! $sep) {
            $value = floatval(preg_replace('/[^0-9]/', '', $money));

            return $isNegative ? -$value : $value;
        }

        $value = floatval(
            preg_replace('/[^0-9]/', '', substr($money, 0, $sep)).'.'.
                preg_replace('/[^0-9]/', '', substr($money, $sep + 1, strlen($money)))
        );

        return $isNegative ? -$value : $value;
    }
}

if (! function_exists('money_decimal_db')) {

    function money_decimal_db($number, $dec = 2, $trim = false)
    {
        if ($trim) {
            $parts = explode('.', (round($number, $dec) * 1));
            $dec = isset($parts[1]) ? strlen($parts[1]) : 0;
        }
        $formatted = number_format($number, $dec);

        return $formatted;
    }
}

if (! function_exists('money_view')) {

    function money_view($value)
    {
        return is_numeric($value) ? number_format($value, 2, ',', '.') : '0,00';
    }
}

if (! function_exists('money_in_full')) {

    function money_in_full($value, $uppercase = 0)
    {
        if (strpos($value, ',') > 0) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }
        $singular = ['centavo', 'real', 'mil', 'milhão', 'bilhão', 'trilhão', 'quatrilhão'];
        $plural = ['centavos', 'reais', 'mil', 'milhões', 'bilhões', 'trilhões', 'quatrilhões'];

        $c = ['', 'cem', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];
        $d = ['', 'dez', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
        $d10 = ['dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezesete', 'dezoito', 'dezenove'];
        $u = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];

        $z = 0;

        $value = number_format($value, 2, '.', '.');
        $integer = explode('.', $value);
        $cont = count($integer);
        for ($i = 0; $i < $cont; $i++) {
            for ($ii = strlen($integer[$i]); $ii < 3; $ii++) {
                $integer[$i] = '0'.$integer[$i];
            }
        }

        $fim = $cont - ($integer[$cont - 1] > 0 ? 1 : 2);
        $rt = '';
        for ($i = 0; $i < $cont; $i++) {
            $value = $integer[$i];
            $rc = (($value > 100) && ($value < 200)) ? 'cento' : $c[$value[0]];
            $rd = ($value[1] < 2) ? '' : $d[$value[1]];
            $ru = ($value > 0) ? (($value[1] == 1) ? $d10[$value[2]] : $u[$value[2]]) : '';

            $r = $rc.(($rc && ($rd || $ru)) ? ' e ' : '').$rd.(($rd &&
                $ru) ? ' e ' : '').$ru;
            $t = $cont - 1 - $i;
            $r .= $r ? ' '.($value > 1 ? $plural[$t] : $singular[$t]) : '';
            if (
                $value == '000'
            ) {
                $z++;
            } elseif ($z > 0) {
                $z--;
            }
            if (($t == 1) && ($z > 0) && ($integer[0] > 0)) {
                $r .= (($z > 1) ? ' de ' : '').$plural[$t];
            }
            if ($r) {
                $rt = $rt.((($i > 0) && ($i <= $fim) &&
                    ($integer[0] > 0) && ($z < 1)) ? (($i < $fim) ? ', ' : ' e ') : ' ').$r;
            }
        }

        if (! $uppercase) {
            return trim($rt ? $rt : 'zero');
        } elseif ($uppercase == '2') {
            return trim(strtoupper($rt) ? strtoupper(strtoupper($rt)) : 'Zero');
        } else {
            return trim(ucwords($rt) ? ucwords($rt) : 'Zero');
        }
    }
}

if (! function_exists('document_number_db')) {

    /**
     * Formata campo CNPJ para salvar no banco de dados.
     *
     * @param  string  $value
     * @return string
     */
    function document_number_db($value)
    {
        $document = preg_replace("/\D/", '', $value);
        $lenght = $document > 15 ? 11 : 14;

        return $value ? left_zero($document, $lenght) : null;
    }
}

if (! function_exists('document_number_view')) {

    /**
     * Formata campo CNPJ para exibir nas view.
     *
     * @param  string  $value
     * @return string
     */
    function document_number_view($value)
    {
        $document = preg_replace("/\D/", '', $value);

        if (strlen($document) == 11) {
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", '$1.$2.$3-$4', $document);
        }

        return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", '$1.$2.$3/$4-$5', $document);
    }
}

if (! function_exists('valid_phone')) {

    /**
     * validador do campo telefone.
     *
     * @param  string  $number
     * @return string
     */
    function valid_phone($number)
    {
        return preg_match('/^(?:(?:\+|00)?(55)\s?)?(?:\(?([1-9][0-9])\)?\s?)?(?:((?:9\d|[2-9])\d{3})\-?(\d{4}))$/', $number);
    }
}

if (! function_exists('document_db')) {

    function document_db($value = null)
    {
        $document = preg_replace("/\D/", '', $value);
        $lenght = strlen($document) <= 11 ?: 14;

        return $value ? left_zero($document, $lenght) : null;
    }
}

if (! function_exists('document_view')) {

    function document_view($value)
    {
        $document = preg_replace("/\D/", '', $value);

        if (strlen($document) == 11) {
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", '$1.$2.$3-$4', $document);
        }

        return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", '$1.$2.$3/$4-$5', $document);
    }
}
