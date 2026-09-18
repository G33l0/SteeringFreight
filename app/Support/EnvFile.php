<?php

namespace App\Support;

use RuntimeException;

/**
 * Careful edits to the .env file from the console.
 *
 * Setting mail credentials by hand over SSH is where a deployment goes wrong:
 * a long shell one-liner drops characters in transit, an SMTP key with a `$`
 * in it lands in the shell's history, and a value written between double
 * quotes has `${...}` expanded out of it by the parser, producing a password
 * that is quietly not the one that was typed.
 *
 * So values are written single quoted, which the parser treats as literal, and
 * a value carrying a single quote of its own is refused rather than written
 * broken. The file is copied first, because there is only one of it and it
 * holds the key that decrypts every session.
 */
class EnvFile
{
    public function __construct(private readonly string $path) {}

    /**
     * Write each key, replacing the existing line or appending a new one, and
     * return the path of the backup taken first.
     *
     * @param  array<string, string>  $values
     */
    public function put(array $values): string
    {
        if (! is_file($this->path) || ! is_readable($this->path)) {
            throw new RuntimeException("No readable .env at {$this->path}.");
        }

        foreach ($values as $key => $value) {
            if (str_contains($value, "'")) {
                throw new RuntimeException("{$key} contains a single quote, which cannot be stored safely. Nothing was changed.");
            }
        }

        $backup = $this->path.'.backup.'.now()->format('YmdHis');

        if (! copy($this->path, $backup)) {
            throw new RuntimeException('Could not back up the .env file. Nothing was changed.');
        }

        $contents = (string) file_get_contents($this->path);

        foreach ($values as $key => $value) {
            $line = $key."='".$value."'";
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            $contents = preg_match($pattern, $contents) === 1
                ? preg_replace_callback($pattern, fn () => $line, $contents, 1)
                : rtrim($contents, "\n")."\n".$line."\n";
        }

        if (file_put_contents($this->path, $contents) === false) {
            throw new RuntimeException('Could not write the .env file.');
        }

        return $backup;
    }

    /**
     * The current value of a key, for showing what is already configured.
     */
    public function get(string $key): ?string
    {
        if (! is_file($this->path)) {
            return null;
        }

        $contents = (string) file_get_contents($this->path);

        if (preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $contents, $matches) !== 1) {
            return null;
        }

        return trim(trim(trim($matches[1]), '"'), "'") ?: null;
    }
}
