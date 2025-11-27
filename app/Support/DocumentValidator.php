<?php

namespace App\Support;

class DocumentValidator
{
    /**
     * Valida CPF ou CNPJ automaticamente baseado no tamanho
     * 
     * @param string $document CPF ou CNPJ (com ou sem formatação)
     * @return bool
     */
    public static function validateCPFCNPJ(string $document): bool
    {
        $cleanDocument = preg_replace('/\D/', '', $document);
        
        if (empty($cleanDocument)) {
            return false;
        }
        
        // Se length <= 11, tratar como CPF, senão como CNPJ
        if (strlen($cleanDocument) <= 11) {
            return self::validateCPF($cleanDocument);
        } else {
            return self::validateCNPJ($cleanDocument);
        }
    }
    
    /**
     * Valida CPF
     * 
     * @param string $cpf CPF (com ou sem formatação)
     * @return bool
     */
    public static function validateCPF(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        
        if (strlen($cpf) !== 11) {
            return false;
        }
        
        // Verificar se todos os dígitos são iguais
        if (preg_match('/^(\d)\1+$/', $cpf)) {
            return false;
        }
        
        // Validar primeiro dígito verificador
        $sum = 0;
        for ($i = 1; $i <= 9; $i++) {
            $sum += (int) substr($cpf, $i - 1, 1) * (11 - $i);
        }
        
        $remainder = ($sum * 10) % 11;
        if ($remainder === 10 || $remainder === 11) {
            $remainder = 0;
        }
        
        if ($remainder !== (int) substr($cpf, 9, 1)) {
            return false;
        }
        
        // Validar segundo dígito verificador
        $sum = 0;
        for ($i = 1; $i <= 10; $i++) {
            $sum += (int) substr($cpf, $i - 1, 1) * (12 - $i);
        }
        
        $remainder = ($sum * 10) % 11;
        if ($remainder === 10 || $remainder === 11) {
            $remainder = 0;
        }
        
        if ($remainder !== (int) substr($cpf, 10, 1)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida CNPJ
     * 
     * @param string $cnpj CNPJ (com ou sem formatação)
     * @return bool
     */
    public static function validateCNPJ(string $cnpj): bool
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        
        if (strlen($cnpj) !== 14) {
            return false;
        }
        
        // Verificar se todos os dígitos são iguais
        if (preg_match('/^(\d)\1+$/', $cnpj)) {
            return false;
        }
        
        $length = strlen($cnpj) - 2;
        $numbers = substr($cnpj, 0, $length);
        $digits = substr($cnpj, $length);
        $sum = 0;
        $pos = $length - 7;
        
        // Validar primeiro dígito verificador
        for ($i = $length; $i >= 1; $i--) {
            $sum += (int) substr($numbers, $length - $i, 1) * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }
        
        $result = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);
        if ($result !== (int) substr($digits, 0, 1)) {
            return false;
        }
        
        // Validar segundo dígito verificador
        $length = $length + 1;
        $numbers = substr($cnpj, 0, $length);
        $sum = 0;
        $pos = $length - 7;
        
        for ($i = $length; $i >= 1; $i--) {
            $sum += (int) substr($numbers, $length - $i, 1) * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }
        
        $result = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);
        if ($result !== (int) substr($digits, 1, 1)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Formata CPF ou CNPJ
     * 
     * @param string $document CPF ou CNPJ (com ou sem formatação)
     * @return string Documento formatado
     */
    public static function formatCPFCNPJ(string $document): string
    {
        $value = preg_replace('/\D/', '', $document);
        
        if (empty($value)) {
            return '';
        }
        
        // Se length <= 11, tratar como CPF, senão como CNPJ
        if (strlen($value) <= 11) {
            // Limitar a 11 dígitos para CPF
            $value = substr($value, 0, 11);
            
            // Formatar como CPF: 000.000.000-00
            if (strlen($value) > 9) {
                return substr($value, 0, 3) . '.' . substr($value, 3, 3) . '.' . substr($value, 6, 3) . '-' . substr($value, 9, 2);
            } elseif (strlen($value) > 6) {
                return substr($value, 0, 3) . '.' . substr($value, 3, 3) . '.' . substr($value, 6);
            } elseif (strlen($value) > 3) {
                return substr($value, 0, 3) . '.' . substr($value, 3);
            } else {
                return $value;
            }
        } else {
            // Limitar a 14 dígitos para CNPJ
            $value = substr($value, 0, 14);
            
            // Formatar como CNPJ: 00.000.000/0000-00
            if (strlen($value) > 12) {
                return substr($value, 0, 2) . '.' . substr($value, 2, 3) . '.' . substr($value, 5, 3) . '/' . substr($value, 8, 4) . '-' . substr($value, 12, 2);
            } elseif (strlen($value) > 8) {
                return substr($value, 0, 2) . '.' . substr($value, 2, 3) . '.' . substr($value, 5, 3) . '/' . substr($value, 8);
            } elseif (strlen($value) > 5) {
                return substr($value, 0, 2) . '.' . substr($value, 2, 3) . '.' . substr($value, 5);
            } elseif (strlen($value) > 2) {
                return substr($value, 0, 2) . '.' . substr($value, 2);
            } else {
                return $value;
            }
        }
    }
    
    /**
     * Remove formatação de CPF/CNPJ
     * 
     * @param string $document CPF ou CNPJ formatado
     * @return string Documento sem formatação
     */
    public static function cleanCPFCNPJ(string $document): string
    {
        return preg_replace('/\D/', '', $document);
    }
}

