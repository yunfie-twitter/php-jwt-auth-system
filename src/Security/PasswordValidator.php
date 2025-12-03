<?php

namespace App\Security;

class PasswordValidator {
    private $minLength;
    
    public function __construct($minLength = 8) {
        $this->minLength = $minLength;
    }
    
    /**
     * パスワードの強度を検証
     */
    public function validate($password) {
        $errors = [];
        
        // 最小文字数チェック
        if (strlen($password) < $this->minLength) {
            $errors[] = "パスワードは{$this->minLength}文字以上である必要があります";
        }
        
        // 大文字を含むかチェック
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "パスワードには少なくとも1つの大文字が必要です";
        }
        
        // 小文字を含むかチェック
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "パスワードには少なくとも1つの小文字が必要です";
        }
        
        // 数字を含むかチェック
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "パスワードには少なくとも1つの数字が必要です";
        }
        
        // 特殊文字を含むかチェック
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "パスワードには少なくとも1つの特殊文字が必要です";
        }
        
        // 一般的な弱いパスワードチェック
        $commonPasswords = [
            'password', 'Password1!', '12345678', 'qwerty', 'abc123',
            'password123', 'admin123', 'welcome1', 'letmein', 'monkey'
        ];
        
        if (in_array(strtolower($password), array_map('strtolower', $commonPasswords))) {
            $errors[] = "このパスワードは一般的すぎます。別のパスワードを選択してください";
        }
        
        // 連続文字チェック
        if (preg_match('/([a-zA-Z0-9])\1{2,}/', $password)) {
            $errors[] = "同じ文字を3回以上連続して使用できません";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength' => $this->calculateStrength($password)
        ];
    }
    
    /**
     * パスワード強度を計算（0-100）
     */
    private function calculateStrength($password) {
        $strength = 0;
        $length = strlen($password);
        
        // 長さによるスコア (最大40点)
        $strength += min(40, $length * 2);
        
        // 文字種類によるスコア
        if (preg_match('/[a-z]/', $password)) $strength += 10;
        if (preg_match('/[A-Z]/', $password)) $strength += 10;
        if (preg_match('/[0-9]/', $password)) $strength += 10;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $strength += 15;
        
        // 多様性ボーナス
        $uniqueChars = count(array_unique(str_split($password)));
        $strength += min(15, $uniqueChars);
        
        return min(100, $strength);
    }
    
    /**
     * パスワードのハッシュ化（ソルト付き）
     */
    public function hashPassword($password, &$salt = null) {
        // ランダムソルトを生成
        if ($salt === null) {
            $salt = bin2hex(random_bytes(32));
        }
        
        // ソルトを追加してハッシュ化
        $saltedPassword = $salt . $password;
        
        // PASSWORD_ARGON2IDを使用（最も安全）
        $hash = password_hash($saltedPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
        
        return $hash;
    }
    
    /**
     * パスワードの検証
     */
    public function verifyPassword($password, $hash, $salt) {
        $saltedPassword = $salt . $password;
        return password_verify($saltedPassword, $hash);
    }
}
