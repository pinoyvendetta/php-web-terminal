PHP Web Terminal v1.2.2

A simple yet powerful PHP-based web terminal for executing commands on Linux and Windows servers directly from your browser. It includes features for file management, real-time command execution, and system information display.

![image](https://media3.giphy.com/media/v1.Y2lkPTc5MGI3NjExdm5qY2l1dmU5OTM3bmVrMjVlbGRzeHozZ2U2emtqZGxoaDJ5dmdlMCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9cw/M26KpCq0rGcKFryk45/giphy.gif)

## Disclaimer

FOR ETHICAL USE ONLY.

This tool is provided for educational and legitimate system administration purposes only. The user is solely responsible for any actions performed using this tool. The author is not responsible or liable for any damage, misuse, or illegal activity caused by this tool. Use at your own risk and ensure you have proper authorization before using it on any system.

## Features

    Cross-Platform Compatibility: Works on both Linux and Windows servers.

    Password Protection: Simple MD5 hashed password for access.

    Real-time Command Execution:

        Live Output Streaming: Execute long-running commands (e.g., ping, nmap, running scripts) and see the output in real-time, just like a native terminal. Prevents browser timeouts.

        Abort Command: A dedicated "Abort Task" button appears during command execution, allowing you to terminate the running process at any time (similar to CTRL+C).

    Directory Navigation:

        cd <directory>: Change current working directory. Supports relative paths, absolute paths, and ~ for home directory.

    File Management:

        File Upload: Upload files directly to the server's current working directory.

        File Download: download <filename> to download files from the server.

        File Editor: edit <filename> to open a simple text editor in a modal to view and modify file contents.

    Custom Shell/Binary Execution:

        Option to specify a custom shell or binary (e.g., ./mytool) to prefix commands.

    System Information Display:

        Shows server OS details (uname -a / ver).

        PHP version and safe_mode status.

        List of disabled PHP functions.

        Server and Client IP addresses.

        Current User.

        Current Working Directory (CWD).

    User Interface:

        Retro terminal theme.

        Live Clock: Header displays the current server date and time, updating every second.

        Clean two-column header layout.

        Command history (navigate with Up/Down arrow keys).

        clear command to clear the terminal output.

        Logout functionality.

    Error Logging: Logs PHP errors to term_error_log in the same directory as the script.

## Setup and Installation

    Deploy: Place the term.php file on a web server with PHP enabled. proc_open and related functions are required for the streaming and abort features.

    Set Password:

        The default password is myp@ssw0rd.

        To change the password, you MUST edit the term.php file.

        Locate the following line:

        $default_password_hash = '2ebba5cd75576c408240e57110e7b4ff'; // MD5 for "myp@ssw0rd"

        Replace the MD5 hash with the MD5 hash of your new desired password. You can generate an MD5 hash using various online tools or command-line utilities (e.g., echo -n "yournewpassword" | md5sum).

## Usage

    Access: Open the term.php file in your web browser (e.g., http://yourserver.com/path/to/term.php).

    Login: You will be prompted for a password. Enter the password you configured.

    Terminal Interface:

        Command Input: Type commands into the input field at the bottom of the terminal and press Enter.

        Output: For long-running commands, output will be streamed live to the terminal.

        Abort Task: While a command is running, an "Abort Task" button will appear. Click it to stop the current process.

## Special Commands

    cd <directory>: Change the current working directory.

    edit <filename>: Opens the specified file in a modal editor.

    download <filename>: Initiates a download for the specified file.

    clear: Clears the terminal output screen.

    Command History: Use the Up and Down arrow keys to navigate through previously executed commands.

## File Upload

    Use the "Choose File" button in the footer to select a file.

    Click "Upload" to send it to the server's current working directory.

## Custom Shell

    The "Custom Shell" input field allows you to specify a path to a binary or script (e.g., /bin/bash) that should be used to execute your commands. Leave this empty to use the default system shell.

## Security Considerations

    Strong Password: Always change the default password to something strong and unique.

    Server Security: This tool provides direct shell access. Secure the server environment where this script is hosted and restrict access to it.

    HTTPS: Always access this tool over HTTPS to encrypt communication.

    File Permissions: The script's capabilities are governed by the permissions of the web server's user (www-data, apache, etc.). Be mindful of these permissions.

    This script allows path traversal for edit and download commands. This means you can access files outside of the current working directory using relative paths (e.g., edit ../../file.php) or absolute paths (e.g., download /etc/passwd). Exercise extreme caution with this feature.

Version

v1.2.2