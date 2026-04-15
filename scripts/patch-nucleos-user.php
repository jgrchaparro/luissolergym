<?php

$file = __DIR__ . '/../vendor/nucleos/user-bundle/src/Resources/config/doctrine.php';

if (!file_exists($file)) {
    echo "Archivo doctrine.php no encontrado.\n";
    exit(1);
}

$content = file_get_contents($file);

// Reemplazar las referencias de ORM por ODM
$content = str_replace(
    "use Doctrine\ORM\Tools\ResolveTargetEntityListener;",
    "use Doctrine\ODM\MongoDB\Tools\ResolveTargetDocumentListener;",
    $content
);

$content = str_replace(
    "Doctrine\ORM\Tools\ResolveTargetEntityListener::class",
    "Doctrine\ODM\MongoDB\Tools\ResolveTargetDocumentListener::class",
    $content
);

$content = str_replace(
    "use Doctrine\ORM\Events;",
    "use Doctrine\ODM\MongoDB\Events;",
    $content
);

file_put_contents($file, $content);

echo "✅ Parche aplicado correctamente a nucleos-user-bundle\n";