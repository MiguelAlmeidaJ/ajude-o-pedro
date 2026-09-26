<?php
declare(strict_types=1);

function campaign_image_upload(
    string $field,
    string $campaignSlug,
    string $slot,
    ?string $currentPath = null
): ?string {
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return $currentPath;
    }

    $file = $_FILES[$field];
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error === UPLOAD_ERR_NO_FILE) {
        return $currentPath;
    }

    if ($error !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'A imagem ultrapassa o limite permitido pelo servidor.',
            UPLOAD_ERR_FORM_SIZE => 'A imagem ultrapassa o limite permitido pelo formulário.',
            UPLOAD_ERR_PARTIAL => 'O upload da imagem foi interrompido. Tente novamente.',
            UPLOAD_ERR_NO_TMP_DIR => 'O servidor está sem diretório temporário para upload.',
            UPLOAD_ERR_CANT_WRITE => 'O servidor não conseguiu salvar a imagem.',
            UPLOAD_ERR_EXTENSION => 'O upload da imagem foi bloqueado pelo servidor.',
        ];
        throw new RuntimeException($messages[$error] ?? 'Não foi possível enviar a imagem.');
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $size = (int) ($file['size'] ?? 0);

    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('O arquivo enviado não é um upload válido.');
    }

    if ($size <= 0 || $size > 8 * 1024 * 1024) {
        throw new RuntimeException('Cada imagem deve ter no máximo 8 MB.');
    }

    $info = @getimagesize($tmp);
    if (!$info || empty($info['mime'])) {
        throw new RuntimeException('O arquivo enviado não é uma imagem válida.');
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    $mime = strtolower((string) $info['mime']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Use imagens JPG, PNG ou WebP.');
    }

    $slug = slugify($campaignSlug);
    $slot = slugify($slot);
    $relativeDir = 'uploads/campaigns/' . $slug;
    $absoluteDir = dirname(__DIR__) . '/' . $relativeDir;

    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
        throw new RuntimeException('Não foi possível criar a pasta para as imagens da campanha.');
    }

    $filename = $slot . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $destination = $absoluteDir . '/' . $filename;

    if (!move_uploaded_file($tmp, $destination)) {
        throw new RuntimeException('Não foi possível salvar a imagem enviada.');
    }

    $newPath = $relativeDir . '/' . $filename;

    if (
        $currentPath &&
        str_starts_with($currentPath, 'uploads/campaigns/') &&
        $currentPath !== $newPath
    ) {
        $oldAbsolute = dirname(__DIR__) . '/' . ltrim($currentPath, '/');
        if (is_file($oldAbsolute)) {
            @unlink($oldAbsolute);
        }
    }

    return $newPath;
}
