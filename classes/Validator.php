<?php

class Validator
{
    public static function sanitizeInput(string $input): string
    {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }

    public static function validateNumeric($value, ?float $min = null, ?float $max = null): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $numValue = (float)$value;

        if ($min !== null && $numValue < $min) {
            return false;
        }

        if ($max !== null && $numValue > $max) {
            return false;
        }

        return true;
    }

    public static function validateSaleAmount($amount): bool
    {
        if (!self::validateNumeric($amount, 0.01, 99999999.99)) {
            return false;
        }

        $decimalPart = explode('.', (string)$amount);
        if (isset($decimalPart[1]) && strlen($decimalPart[1]) > 2) {
            return false;
        }

        return true;
    }

    public static function validateUserId(int $userId): bool
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('User validation error: ' . $e->getMessage());
            return false;
        }
    }

    public static function validateEmail(string $email): bool
    {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validateUsername(string $username): bool
    {
        $length = strlen($username);
        if ($length < 3 || $length > 100) {
            return false;
        }

        return preg_match('/^[a-zA-Z0-9_-]+$/', $username) === 1;
    }

    public static function validatePositiveInteger($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
    }

    public static function sanitizeAndValidateString(string $input, int $minLength = 1, int $maxLength = 255)
    {
        $sanitized = self::sanitizeInput($input);
        $length = strlen($sanitized);

        if ($length < $minLength || $length > $maxLength) {
            return false;
        }

        return $sanitized;
    }
}
