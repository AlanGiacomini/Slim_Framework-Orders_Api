<?php

namespace Alang\DesafioIpag\Utils;

class Validator
{
    // Validador de CPF
    private static function isValidCPF(string $cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) return false;

        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$t] != $d) return false;
        }

        return true;
    }

    // Helper para acessar campos aninhados
    private static function getNestedValue(array $data, string $path)
    {
        $segments = explode('.', $path);
        foreach ($segments as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return null;
            }
            $data = $data[$segment];
        }
        return $data;
    }


    /**
     * Valida um campo específico.
     * Regras permitidas: required, string, integer, numeric, json, datetime,
     * email, cpf, phone, order_number, min:#, max:#, in:item1,item2,itenN
     * 
     * @param null $value
     * @param string $ruleSet formato regra1|regra2|regraN
     * 
     * @return array
     */
    public static function validateField($value, string $ruleSet): array
    {

        $errors = [];
        $rules = explode('|', $ruleSet);

        $isRequired = in_array('required', $rules);

        // Se o valor não foi enviado e não é obrigatório, ignora
        if (is_null($value) && !$isRequired) {
            return [];
        } else {
            foreach ($rules as $rule) {
                //Aqui se o valor vier nullo, mas for required, gera erro
                if ($rule === 'required' && (is_null($value) || (is_string($value) && trim($value) === ''))) {
                    $errors[] = [
                        'code' => 'required_field',
                        'message' => 'Campo obrigatório.'
                    ];
                    break;
                } elseif (!is_null($value)) {
                    if ($rule === 'string' && !is_string($value)) {
                        $errors[] = [
                            'code' => 'invalid_string',
                            'message' => 'Deve ser uma string.'
                        ];
                    }

                    if ($rule === 'integer' && !filter_var($value, FILTER_VALIDATE_INT)) {
                        $errors[] = [
                            'code' => 'invalid_integer',
                            'message' => 'Deve ser um número inteiro.'
                        ];
                    }

                    if ($rule === 'numeric' && !is_numeric($value)) {
                        $errors[] = [
                            'code' => 'invalid_numeric',
                            'message' => 'Deve ser numérico:'.$value
                        ];
                    }

                    if ($rule === 'json' && (!is_string($value) || is_null(json_decode($value)))) {
                        $errors[] = [
                            'code' => 'invalid_json',
                            'message' => 'JSON inválido.'
                        ];
                    }

                    if ($rule === 'datetime') {
                        try {
                            $dt = new \DateTime($value);
                        } catch (\Exception $e) {
                            $errors[] = [
                                'code' => 'invalid_datetime',
                                'message' => 'Formato de data inválido (ISO 8601).'
                            ];
                        }
                    }

                    if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = [
                            'code' => 'invalid_email',
                            'message' => 'E-mail inválido.'
                        ];
                    }

                    if ($rule === 'cpf' && !self::isValidCPF($value)) {
                        $errors[] = [
                            'code' => 'invalid_cpf',
                            'message' => 'CPF inválido.'
                        ];
                    }

                    if ($rule === 'phone' && !preg_match('/^\d{10,11}$/', $value)) {
                        $errors[] = [
                            'code' => 'invalid_phone',
                            'message' => 'Telefone inválido.'
                        ];
                    }

                    if ($rule === 'order_number' && !preg_match('/^ORD-\w+$/', $value)) {
                        $errors[] = [
                            'code' => 'invalid_order_number',
                            'message' => 'Formato de order_number inválido.'
                        ];
                    }

                    if (str_starts_with($rule, 'min:')) {
                        $min = (float) substr($rule, 4);
                        if ($value < $min) {
                            $errors[] = [
                                'code' => 'value_below_min',
                                'message' => "Valor mínimo permitido: $min."
                            ];
                        }
                    }

                    if (str_starts_with($rule, 'max:')) {
                        $max = (float) substr($rule, 4);
                        if ($value > $max) {
                            $errors[] = [
                                'code' => 'value_above_max',
                                'message' => "Valor máximo permitido: $max."
                            ];
                        }
                    }

                    if (str_starts_with($rule, 'in:')) {
                        $allowed = explode(',', substr($rule, 3));
                        if (!in_array($value, $allowed)) {
                            $errors[] = [
                                'code' => 'value_not_allowed',
                                'message' => 'Valor não permitido.'
                            ];
                        }
                    }
                }
            }
        }
        return $errors;
    }

    /**
     * Validador de parâmetros permitidos
     * 
     * @param array $input Array de Parâmetros -> Valores que serão testados
     * @param array $allowedKeys Array de Parâmetros permitidos
     * 
     * @return array
     */
    public static function validateAllowedKeys(array $input, array $allowedKeys): array
    {
        $errors = [];

        foreach (array_keys($input) as $key) {
            if (!in_array($key, $allowedKeys)) {
                $errors[$key] = [[
                    'code' => 'invalid_key',
                    'message' => "Campo não permitido: $key"
                ]];
            }
        }

        return $errors;
    }

    public static function validateAllFields(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleSet) {
            // Trata campos com wildcard: ex. order.items.*.product_name
            if (str_contains($field, '.*.')) {
                [$prefix, $subfield] = explode('.*.', $field);

                foreach ($data[$prefix] ?? [] as $index => $item) {
                    $value = $item[$subfield] ?? null;
                    $fieldKey = "$prefix.$index.$subfield";
                    $errors[$fieldKey] = self::validateField($value, $ruleSet);
                }
            } else {
                // Garante que o campo seja validado mesmo se estiver ausente
                $value = self::getNestedValue($data, $field);
                $errors[$field] = self::validateField($value ?? null, $ruleSet);
            }
        }

        return array_filter($errors);
    }
}
