<?php
declare(strict_types=1);

define('UPLOAD_DIR', 'file/');
define('JSON_FILE', UPLOAD_DIR . 'all.json');
define('ALLOWED_EXTENSIONS', ['html', 'txt']);

session_start();

$errors = [];
$uploadedFiles = [];

function loadUploadedFiles(): array
{
    if (!file_exists(JSON_FILE)) {
        return [];
    }

    $jsonContent = file_get_contents(JSON_FILE);
    $files = json_decode($jsonContent ?: '[]', true);

    if (!is_array($files)) {
        return [];
    }

    usort($files, static function (array $a, array $b): int {
        return ($b['t'] ?? 0) <=> ($a['t'] ?? 0);
    });

    return $files;
}

function determineFileType(string $content): string
{
    if (strpos($content, '<title>作业详情</title>') !== false) {
        return 'homework';
    }

    if (strpos($content, '<title>查看详情</title>') !== false) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($content);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $subNavDivs = $xpath->query("//div[contains(@class, 'subNav')]");

        foreach ($subNavDivs as $div) {
            if (strpos($div->nodeValue, '考试详情') !== false) {
                return 'exam';
            }
        }
    }

    return '';
}

function generateRandomKey(?int $length = null): string
{
    $length = $length ?? random_int(14, 24);
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $maxIndex = strlen($characters) - 1;
    $key = '';

    for ($i = 0; $i < $length; $i++) {
        $key .= $characters[random_int(0, $maxIndex)];
    }

    return $key;
}

function redirectWithSuccess(string $message): void
{
    $_SESSION['success'] = $message;
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

function handleUpload(): void
{
    global $errors;

    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $errors[] = '请输入试卷或作业名称。';
        return;
    }

    if (strpos($name, '><') !== false) {
        $errors[] = '名称包含不允许的字符。';
        return;
    }

    $fileContent = '';
    $isPastedContent = false;
    $pastedHtml = trim($_POST['pasted_html'] ?? '');

    if ($pastedHtml !== '') {
        $isPastedContent = true;
        $fileContent = $pastedHtml;
    } else {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = '请选择要上传的文件，或直接粘贴 HTML 内容。';
            return;
        }

        $file = $_FILES['file'];
        $fileExtension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ALLOWED_EXTENSIONS, true)) {
            $errors[] = '仅支持上传 .html 或 .txt 文件。';
            return;
        }

        $fileContent = file_get_contents((string) $file['tmp_name']);
        if ($fileContent === false) {
            $errors[] = '无法读取上传文件内容。';
            return;
        }
    }

    $manualType = trim($_POST['manual_type'] ?? '');
    if ($manualType !== '' && in_array($manualType, ['exam', 'homework'], true)) {
        $type = $manualType;
    } else {
        $type = determineFileType($fileContent);
        if ($type === '') {
            $errors[] = '未能自动识别文件类型，请手动选择考试或作业。';
            return;
        }
    }

    $randomKey = generateRandomKey();
    $targetDir = UPLOAD_DIR . ($type === 'homework' ? 'homework/' : 'exam/');
    $targetPath = $targetDir . $randomKey . '.html';

    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        $errors[] = '上传目录创建失败。';
        return;
    }

    $saveSuccess = $isPastedContent
        ? file_put_contents($targetPath, $fileContent) !== false
        : move_uploaded_file((string) $_FILES['file']['tmp_name'], $targetPath);

    if (!$saveSuccess) {
        $errors[] = '文件保存失败。';
        return;
    }

    $jsonData = loadUploadedFiles();
    $jsonData[] = [
        't' => time(),
        'type' => $type,
        'name' => $name,
        'background' => 'none',
        'key' => $randomKey,
    ];

    $jsonString = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($jsonString === false) {
        $errors[] = '数据编码失败：' . json_last_error_msg();
        @unlink($targetPath);
        return;
    }

    if (file_put_contents(JSON_FILE, $jsonString) === false) {
        $errors[] = '上传记录保存失败。';
        @unlink($targetPath);
        return;
    }

    redirectWithSuccess('上传成功');
}

function handleDelete(): void
{
    global $errors;

    $deleteKey = trim($_POST['key'] ?? '');
    if ($deleteKey === '') {
        $errors[] = '删除参数无效。';
        return;
    }

    $jsonData = loadUploadedFiles();

    foreach ($jsonData as $index => $item) {
        if (($item['key'] ?? '') !== $deleteKey) {
            continue;
        }

        $filePath = UPLOAD_DIR
            . (($item['type'] ?? '') === 'homework' ? 'homework/' : 'exam/')
            . $deleteKey
            . '.html';

        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        array_splice($jsonData, $index, 1);

        $saved = file_put_contents(
            JSON_FILE,
            (string) json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        if ($saved === false) {
            $errors[] = '删除后记录保存失败。';
            return;
        }

        redirectWithSuccess('文件已删除');
    }

    $errors[] = '未找到要删除的文件。';
}

$uploadedFiles = loadUploadedFiles();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit'])) {
        handleUpload();
    } elseif (isset($_POST['delete'])) {
        handleDelete();
    }
}

$successMsg = '';
if (isset($_SESSION['success'])) {
    $successMsg = (string) $_SESSION['success'];
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>上传中心</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --panel: #ffffff;
            --line: #e5e7eb;
            --line-dark: #d6dbe4;
            --text: #111827;
            --muted: #6b7280;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --success-bg: #ecfdf3;
            --success-text: #166534;
            --error-bg: #fef2f2;
            --error-text: #b91c1c;
            --danger-bg: #fff1f2;
            --danger-text: #be123c;
            --shadow: 0 14px 40px rgba(17, 24, 39, 0.06);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font: 14px/1.6 "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif;
        }

        .page {
            max-width: 1040px;
            margin: 0 auto;
            padding: 32px 16px 48px;
        }

        .topbar {
            margin-bottom: 20px;
        }

        .topbar h1 {
            margin: 0 0 6px;
            font-size: 28px;
            font-weight: 700;
        }

        .topbar p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
            gap: 20px;
            align-items: start;
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: var(--shadow);
        }

        .card-body {
            padding: 24px;
        }

        .card h2 {
            margin: 0 0 6px;
            font-size: 20px;
        }

        .card-intro {
            margin: 0 0 20px;
            color: var(--muted);
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .summary-item {
            padding: 14px 16px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fafbfc;
        }

        .summary-item strong {
            display: block;
            font-size: 20px;
            margin-bottom: 4px;
        }

        .summary-item span {
            color: var(--muted);
            font-size: 13px;
        }

        .alert {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid transparent;
        }

        .alert-success {
            background: var(--success-bg);
            color: var(--success-text);
            border-color: #bbf7d0;
        }

        .alert-error {
            background: var(--error-bg);
            color: var(--error-text);
            border-color: #fecaca;
        }

        .field {
            margin-bottom: 16px;
        }

        .field label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .field input[type="text"],
        .field input[type="file"],
        .field textarea,
        .field select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--line-dark);
            border-radius: 12px;
            background: #fff;
            color: var(--text);
            font: inherit;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .field input[type="text"]:focus,
        .field input[type="file"]:focus,
        .field textarea:focus,
        .field select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .field textarea {
            min-height: 220px;
            resize: vertical;
            font-family: Consolas, "Courier New", monospace;
        }

        .hint {
            margin-top: 6px;
            color: var(--muted);
            font-size: 12px;
        }

        .split {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .subpanel {
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fafbfc;
        }

        .subpanel h3 {
            margin: 0 0 4px;
            font-size: 16px;
        }

        .subpanel p {
            margin: 0 0 14px;
            color: var(--muted);
            font-size: 13px;
        }

        .submit-btn,
        .delete-btn {
            border: 0;
            border-radius: 12px;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        .submit-btn {
            width: 100%;
            padding: 13px 16px;
            background: var(--primary);
            color: #fff;
        }

        .submit-btn:hover {
            background: var(--primary-hover);
        }

        .side-note {
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fafbfc;
            color: var(--muted);
        }

        .side-note strong {
            display: block;
            margin-bottom: 6px;
            color: var(--text);
        }

        .file-list {
            display: grid;
            gap: 12px;
        }

        .file-item {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fafbfc;
        }

        .file-tag {
            display: inline-block;
            margin-bottom: 8px;
            padding: 4px 8px;
            border-radius: 999px;
            background: #e8f0ff;
            color: var(--primary);
            font-size: 12px;
            font-weight: 600;
        }

        .file-name {
            margin-bottom: 6px;
            font-size: 15px;
            font-weight: 600;
            word-break: break-word;
        }

        .file-meta {
            color: var(--muted);
            font-size: 12px;
        }

        .delete-btn {
            padding: 10px 14px;
            background: var(--danger-bg);
            color: var(--danger-text);
            border: 1px solid #fecdd3;
            white-space: nowrap;
        }

        .empty {
            padding: 20px;
            border: 1px dashed var(--line-dark);
            border-radius: 12px;
            text-align: center;
            color: var(--muted);
            background: #fafbfc;
        }

        @media (max-width: 900px) {
            .layout,
            .split,
            .summary {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .page {
                padding: 20px 12px 36px;
            }

            .card-body {
                padding: 18px;
            }

            .file-item {
                flex-direction: column;
            }

            .delete-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="topbar">
            <h1>上传中心</h1>
            <p>支持上传文件或直接粘贴 HTML 内容。</p>
        </div>

        <div class="summary">
            <div class="summary-item">
                <strong><?php echo count($uploadedFiles); ?></strong>
                <span>当前记录</span>
            </div>
            <div class="summary-item">
                <strong>2</strong>
                <span>上传方式</span>
            </div>
            <div class="summary-item">
                <strong>.html / .txt</strong>
                <span>支持格式</span>
            </div>
        </div>

        <div class="layout">
            <section class="card">
                <div class="card-body">
                    <h2>提交内容</h2>
                    <p class="card-intro">文件上传和粘贴内容二选一即可，手动选择的文件类型会优先生效。</p>

                    <?php if ($successMsg !== ''): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <?php foreach ($errors as $error): ?>
                                <div><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <div class="field">
                            <label for="name">试卷 / 作业名称</label>
                            <input type="text" id="name" name="name" required placeholder="例如：高数期中试卷">
                        </div>

                        <div class="field">
                            <label for="manual_type">文件类型</label>
                            <select id="manual_type" name="manual_type" required>
                                <option value="">请选择类型</option>
                                <option value="exam">考试</option>
                                <option value="homework">作业</option>
                            </select>
                            <div class="hint">如果系统识别不到类型，这里的选择会直接作为上传结果。</div>
                        </div>

                        <div class="split">
                            <div class="subpanel">
                                <h3>上传文件</h3>
                                <p>支持 `.html` 和 `.txt`。</p>
                                <div class="field" style="margin-bottom:0;">
                                    <label for="file">选择文件</label>
                                    <input type="file" id="file" name="file" accept=".html,.txt,text/plain,text/html">
                                    <div class="hint">没选文件的话，可以直接使用右侧粘贴内容。</div>
                                </div>
                            </div>

                            <div class="subpanel">
                                <h3>粘贴 HTML</h3>
                                <p>适合直接粘贴页面源码或导出的文本。</p>
                                <div class="field" style="margin-bottom:0;">
                                    <label for="pasted_html">HTML 内容</label>
                                    <textarea id="pasted_html" name="pasted_html" placeholder="把 HTML 内容粘贴到这里"></textarea>
                                    <div class="hint">如果文件和文本都填写，会优先使用粘贴的内容。</div>
                                </div>
                            </div>
                        </div>

                        <button class="submit-btn" type="submit" name="submit">确认上传</button>
                    </form>
                </div>
            </section>

            <aside class="card">
                <div class="card-body">
                    <h2>已上传文件</h2>
                    <p class="card-intro">最新记录排在最前面。</p>

                    <div class="side-note">
                        <strong>当前设置</strong>
                        已移除指定链接检测，保留上传、分类、删除功能。
                    </div>

                    <div class="file-list">
                        <?php if (!empty($uploadedFiles)): ?>
                            <?php foreach ($uploadedFiles as $file): ?>
                                <div class="file-item">
                                    <div>
                                        <span class="file-tag"><?php echo ($file['type'] ?? '') === 'homework' ? '作业' : '考试'; ?></span>
                                        <div class="file-name"><?php echo htmlspecialchars((string) ($file['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="file-meta">
                                            上传时间：<?php echo date('Y-m-d H:i', (int) ($file['t'] ?? time())); ?><br>
                                            访问 Key：<?php echo htmlspecialchars((string) ($file['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </div>

                                    <form method="post">
                                        <input type="hidden" name="key" value="<?php echo htmlspecialchars((string) ($file['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        <button class="delete-btn" type="submit" name="delete" onclick="return confirm('确定要删除这个文件吗？');">删除</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty">还没有上传记录。</div>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</body>
</html>