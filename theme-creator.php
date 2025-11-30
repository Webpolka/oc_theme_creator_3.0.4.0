<?php

if ($argc < 2) {
    die("
    Usage: php ./theme-creator.php [name] 
            name     - The name of the theme.          
    ");
}

$name = $argv[1];
$author_name = "Weblegko";
$author_link = "https://weblegko.ru/";
$version = "1.0";

for ($i = 2; $i < $argc; $i++) {
    switch ($argv[$i]) {
        case '-a':
            $author_name = $argv[++$i];
            break;
        case '-u':
            $author_link = $argv[++$i];
            break;
        case '-v':
            $version = $argv[++$i];
            break;
    }
}

$theme_name = $name;
$Name = ucwords($name);
$ThemeName = str_replace('_', '', ucwords($theme_name, '_'));

$templateDir = __DIR__ . '/files';
$targetDir = __DIR__ . '/' . $theme_name;

// Создаём конечную папку
mkdir($targetDir, 0777, true);

// Копируем шаблон
recurseCopy($templateDir, $targetDir);

// Делаем подстановки
replacePlaceholders($targetDir, $name, $Name, $theme_name, $ThemeName, $version, $author_name, $author_link);

echo "Theme folder created: $targetDir\n";


// ----------------------- FUNCTIONS ---------------------------

function recurseCopy($src, $dst) {
    $dir = opendir($src);
    if (!is_dir($dst)) mkdir($dst, 0777, true);

    while (false !== ($file = readdir($dir))) {
        if ($file != '.' && $file != '..') {
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

function replacePlaceholders($dir, $name, $Name, $theme_name, $ThemeName, $version, $author_name, $author_link) {
    $fields = ['%name%', '%Name%', '%theme_name%', '%ThemeName%', '%version%', '%author_name%', '%author_link%'];
    $values = [$name, $Name, $theme_name, $ThemeName, $version, $author_name, $author_link];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir),
        RecursiveIteratorIterator::SELF_FIRST
    );

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
