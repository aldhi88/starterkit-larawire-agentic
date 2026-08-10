<?php

namespace Altekno\StarterKit\Installation;

class StarterInstallState
{
    public const PATH = 'config/starter-installed.php';

    public static function status(): ?string
    {
        $path = base_path(self::PATH);

        if (! is_file($path)) {
            return null;
        }

        $state = require $path;

        return is_array($state) && is_string($state['status'] ?? null)
            ? $state['status']
            : null;
    }

    public static function runtimeEnabled(): bool
    {
        return in_array(self::status(), ['installing', 'installed'], true);
    }

    public static function write(string $status): void
    {
        $contents = "<?php\n\nreturn [\n    'status' => '".$status."',\n];\n";

        if (file_put_contents(base_path(self::PATH), $contents, LOCK_EX) === false) {
            throw new \RuntimeException('Tidak dapat menulis status instalasi starterkit.');
        }
    }
}
