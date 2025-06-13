# PHP Web Terminal v1.0.0

A simple yet powerful PHP-based web terminal for executing commands on Linux and Windows servers directly from your browser. It includes features for file management, command execution, and system information display.

## Disclaimer

**FOR ETHICAL USE ONLY.**

This tool is provided for educational and legitimate system administration purposes only. The user is solely responsible for any actions performed using this tool. The author is not responsible or liable for any damage, misuse, or illegal activity caused by this tool. Use at your own risk and ensure you have proper authorization before using it on any system.

## Features

* **Cross-Platform Compatibility:** Works on both Linux and Windows servers.
* **Password Protection:**  MD5 hashed password.
* **Command Execution:**
    * Execute standard shell commands.
    * Flexible command execution backend (tries `proc_open`, `shell_exec`, `passthru`, `system`, `exec`).
* **Directory Navigation:**
    * `cd <directory>`: Change current working directory. Supports relative paths, absolute paths, and `~` for home directory.
* **File Management:**
    * **File Upload:** Upload files directly to the server's current working directory (or a fallback `uploads/` directory if CWD is not writable).
    * **File Download:**
        * `download <filename>`: Download files from the server.
    * **File Editor:**
        * `edit <filename>`: Open a simple text editor in a modal to view and modify file contents.
* **Custom Shell/Binary Execution:**
    * Option to specify a custom shell or binary (e.g., `./mytool`) to prefix commands.
* **System Information Display:**
    * Shows server OS details (`uname -a` / `ver`).
    * PHP version.
    * Disabled PHP functions.
    * Safe mode status.
    * Server and Client IP addresses.
    * Current User and Group.
    * Current Working Directory (CWD).
    * Date and Time.
* **User Interface:**
    * Retro terminal theme.
    * Command history (navigate with Up/Down arrow keys).
    * `clear` command to clear the terminal output.
    * Logout functionality.
* **Error Logging:** Logs PHP errors to `term_error_log` in the same directory as the script.

## Setup and Installation

1.  **Deploy:** Place the `term.php` file on a web server with PHP enabled.
2.  **Set Password:**
    * The default password is `myp@ssw0rd`.
    * To change the password, you **MUST** edit the `term.php` file.
    * Locate the following line:
        ```php
        $default_password_hash = '2ebba5cd75576c408240e57110e7b4ff'; // MD5 for "myp@ssw0rd"
        ```
    * Replace the MD5 hash `'2ebba5cd75576c408240e57110e7b4ff'` with the MD5 hash of your new desired password. You can generate an MD5 hash using various online tools or command-line utilities (e.g., `echo -n "yournewpassword" | md5sum`).

## Usage

1.  **Access:** Open the `term.php` file in your web browser (e.g., `http://yourserver.com/path/to/term.php`).
2.  **Login:** You will be prompted for a password. Enter the password you configured (default is `myp@ssw0rd`).
3.  **Terminal Interface:**
    * **Command Input:** Type commands into the input field at the bottom of the terminal and press Enter.
    * **Output:** Command output will be displayed in the terminal area.
    * **Current Directory:** The header displays the current working directory (CWD).
    * **System Info:** The header also displays various system and server information.

### Special Commands

* `cd <directory>`: Change the current working directory.
    * Example: `cd /var/www`
    * Example: `cd ../logs`
    * Example: `cd ~` (navigates to user's home directory, if defined)
* `edit <filename>`: Opens the specified file in a modal editor. (Since we can't use nano and vim)
    * Example: `edit config.txt`
    * You can modify the content and click "Save".
* `download <filename>`: Initiates a download for the specified file.
    * Example: `download backup.zip`
* `clear`: Clears the terminal output screen.
* **Command History:** Use the Up and Down arrow keys in the command input field to navigate through previously executed commands.

### File Upload

* Use the "Choose File" button in the footer to select a file from your local machine.
* Click the "Upload" button to upload it to the server's current working directory.
* A message will indicate the success or failure of the upload.

### Custom Shell

* The "Custom Shell" input field (e.g., placeholder `(e.g. ./Pwn)`) allows you to specify a path to a binary or script that should be prepended to your commands.
* For example, if you enter `/usr/local/bin/custom_script` in the "Custom Shell" field and then type `my_arg` in the command input, the executed command will be `/usr/local/bin/custom_script my_arg`.
* Leave this field empty to use the default system shell.

### Logout

* Click the "Logout" button in the header to end your session.

## Security Considerations

* **Strong Password:** Ensure you change the default password to a strong, unique password.
* **Server Security:** This tool provides direct shell access. Secure the server environment where this script is hosted.
* **HTTPS:** Always access this tool over HTTPS to encrypt communication.
* **File Permissions:** Be mindful of file permissions on the server. The script's ability to read/write files and execute commands is governed by the permissions of the web server user.
* **Disable Functions:** The tool attempts to use various PHP functions for command execution. If some are disabled via `disable_functions` in `php.ini`, it will try alternatives. The list of disabled functions is displayed in the header.
* **This version of the script has been intentionally modified to allow path traversal for the edit and download commands. This means you can access files outside of the current working directory using relative paths (e.g., edit ../../file.php) or absolute paths (e.g., download /etc/passwd).

Warning: This feature removes critical security safeguards. Exercise extreme caution, as it grants access to any file on the server that the web server's user account has permission to read or write.

## Version

v1.0.1

