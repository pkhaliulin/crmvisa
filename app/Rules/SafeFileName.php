<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeFileName implements ValidationRule
{
    private array $dangerousExtensions = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps',
        'pht', 'phar', 'shtml', 'shtm', 'htaccess', 'htpasswd',
        'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'bat', 'cmd',
        'com', 'exe', 'dll', 'msi', 'scr', 'js', 'jsp', 'asp',
        'aspx', 'war', 'jar', 'svg',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof \Illuminate\Http\UploadedFile) {
            return;
        }

        $name = strtolower($value->getClientOriginalName());

        // Проверка двойных расширений: file.php.jpg
        $parts = explode('.', $name);
        if (count($parts) > 2) {
            foreach (array_slice($parts, 1, -1) as $ext) {
                if (in_array($ext, $this->dangerousExtensions)) {
                    $fail('Имя файла содержит недопустимое расширение.');
                    return;
                }
            }
        }

        // Проверка основного расширения
        $extension = $value->getClientOriginalExtension();
        if (in_array(strtolower($extension), $this->dangerousExtensions)) {
            $fail('Формат файла не поддерживается.');
        }
    }
}
