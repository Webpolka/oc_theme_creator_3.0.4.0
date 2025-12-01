<?php

if ($argc < 2) {
    die("
    Usage: php ./theme-create.php [theme_name]
            theme_name - The name of the theme.
    ");
}

$name = $argv[1];

// Имя темы — оставляем как есть
$theme_name = $name;

// Приведение к нормальному виду
$Name = ucwords($name);
$ThemeName = str_replace('_', '', ucwords($theme_name, '_'));

// Директория с шаблонами
$templateDir = __DIR__ . '/files';

// Временная директория
$tmpDir = sys_get_temp_dir() . '/' . uniqid('theme_', true);
mkdir($tmpDir);

// Копируем файлы
recurseCopy($templateDir, $tmpDir);

// Заменяем плейсхолдеры
replacePlaceholders($tmpDir, $name, $Name, $theme_name, $ThemeName);

// Имя zip-файла в текущей директории
$zipFile = __DIR__ . '/' . $theme_name . '.ocmod.zip';

// Архивируем
archiveTheme($tmpDir, $zipFile);

// Чистим за собой
deleteDirectory($tmpDir);

echo "Theme created successfully: $zipFile\n";


// --------------------- FUNCTIONS ---------------------

function recurseCopy($src, $dst) {
    $dir = opendir($src);
    if (!is_dir($dst)) mkdir($dst);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            $filePathSrc = $src . '/' . $file;
            $filePathDst = $dst . '/' . str_replace('%name%', $GLOBALS['name'], $file);
            if (is_dir($filePathSrc)) {
                recurseCopy($filePathSrc, $filePathDst);
            } else {
                copy($filePathSrc, $filePathDst);
            }
        }
    }
    closedir($dir);
}

function replacePlaceholders($dir, $name, $Name, $theme_name, $ThemeName) {
    $fields = ['%name%', '%Name%', '%theme_name%', '%ThemeName%'];
    $values = [$name, $Name, $theme_name, $ThemeName];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $originalFile = $file->getPathname();
            $destFile = str_replace($fields, $values, $originalFile);

            $content = file_get_contents($originalFile);
            $content = str_replace($fields, $values, $content);

            file_put_contents($destFile, $content);

            if ($originalFile !== $destFile) {
                unlink($originalFile);
            }
        }
    }
}

function archiveTheme($src, $dst) {
    $src = realpath($src);
    $zip = new ZipArchive();
    $zip->open($dst, ZipArchive::CREATE);
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src), RecursiveIteratorIterator::LEAVES_ONLY);

    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($src) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }

    $zip->close();
}

function deleteDirectory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
    }

    return rmdir($dir);
}
