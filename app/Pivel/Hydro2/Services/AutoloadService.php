<?php

namespace Pivel\Hydro2\Services;

class AutoloadService
{
    /**
     * @var string[]
     */
    protected array $base_dirs;

    /**
     * @return AutoloadService
     */
    public function __construct(?string $base_dir=null)
    {
        $this->base_dirs[] = rtrim($base_dir??$_SERVER["DOCUMENT_ROOT"], DIRECTORY_SEPARATOR);
        
        // Manually require Utilities.php because we don't have a working autoloader yet
    }

    public function AddDir(string $base_dir)
    {
        $this->base_dirs[] = rtrim($base_dir, DIRECTORY_SEPARATOR);
    }

    public function Register() : bool
    {
        return spl_autoload_register(array($this, 'LoadClass'));
    }

    public function LoadClass(string $class)
    {
        $class_parts = explode("\\", $class);

        if (count($class_parts) < 3) {
            // Hydro2 package namespaces must have at least [vendor]\[package]\[class], (2x '\' or 3x parts minimum).
            return false;
        }

        foreach ($this->base_dirs as $base_dir) {
            $file = $base_dir . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $class) . '.php';

            // if the mapped file exists, require it
            if ($this->RequireFile($file)) {
                // yes, we're done
                return $file;
            }
        }

        // no matching file.
        return false;
    }

    protected function RequireFile($file)
    {
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
        return false;
    }
}