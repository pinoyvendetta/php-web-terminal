# PHP Web Terminal Shell

![image](https://media3.giphy.com/media/v1.Y2lkPTc5MGI3NjExdm5qY2l1dmU5OTM3bmVrMjVlbGRzeHozZ2U2emtqZGxoaDJ5dmdlMCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9cw/M26KpCq0rGcKFryk45/giphy.gif)

This is a simple, secure, and cross-platform web-based terminal written in PHP. It provides remote command execution capabilities, file management features (upload, download, edit), and real-time output streaming, accessible directly through your web browser.

## Disclaimer

> **FOR ETHICAL USE ONLY.**
> This tool is provided for educational and legitimate system administration purposes only. The user is solely responsible for any actions performed using this tool. The author is not responsible or liable for any damage, misuse, or illegal activity caused by this tool. Use at your own risk and ensure you have proper authorization before using it on any system.

## Features

-   **Password Authentication**: Secures access to the terminal with an MD5-hashed password.
-   **Cross-Platform Compatibility**: Designed to work seamlessly on both Windows and Linux servers.
-   **Robust Command Execution**: Utilizes a chain of fallback functions (`shell_exec`, `proc_open`, `popen`, `passthru`, `system`, `exec`) to ensure commands can be executed in various server environments, even when some functions are disabled.
-   **Real-time Command Streaming**: Executes commands and streams output back to the browser in real-time for long-running processes, preventing timeouts.
-   **Command Abort**: Allows termination of currently running commands.
-   **Directory Navigation**: Supports `cd` command to change the current working directory.
-   **File Editor**: Built-in modal editor to view and modify files directly from the browser.
-   **File Upload**: Facilitates easy uploading of files to the current working directory on the server.
-   **File Download**: Enables downloading of files from the server.
-   **Command History**: Client-side history navigation using arrow keys.
-   **Custom Shell Execution**: Option to specify a custom shell or interpreter for commands (e.g., `/bin/bash`, `python`, `./Pwnkit`).
-   **System Information Display**: Shows crucial server details like Uname, PHP Version, Server IP, Client IP, current user, CWD, and disabled PHP functions.
-   **PHP Version Compatibility**: Compatible with PHP 5.3+, PHP 7.x, and PHP 8.x.
-   **User-Friendly Interface**: Clean, matrix-style terminal UI.
![Toolkit GIF](https://raw.githubusercontent.com/pinoyvendetta/php-web-terminal/refs/heads/main/img/main1.png)
## Compatibility

-   **PHP Versions**: 5.3+, 7.x, 8.x
-   **Operating Systems**: Windows, Linux

## Installation & Setup

1.  **Download**: Download the `term.php` file.
2.  **Upload**: Upload the `term.php` file to your web server in a directory accessible via HTTP (e.g., your public HTML directory or a subdirectory).
3.  **Password**: The default password is `myp@ssw0rd`. It is **highly recommended** to change this. To do so, modify the `$default_password_hash` variable in the `term.php` file. You can generate a new MD5 hash for your desired password using an online MD5 generator or `echo md5('your_new_password');` in a PHP script.

    ```php
    // --- MD5 Password ---
    $default_password_hash = '2ebba5cd75576c408240e57110e7b4ff'; // MD5 for "myp@ssw0rd"
    // Change '2ebba5cd75576c408240e57110e7b4ff' to the MD5 hash of your new password.
    ```

4.  **Permissions**: Ensure the directory where `term.php` resides, and any directories you wish to interact with, have appropriate read/write permissions for the web server user.

## Usage

1.  **Access the Terminal**: Open your web browser and navigate to the URL where you uploaded `term.php` (e.g., `http://your-domain.com/term.php`).
2.  **Login**: Enter the password (`myp@ssw0rd` by default, or your custom password) to log in.
![Toolkit GIF](https://raw.githubusercontent.com/pinoyvendetta/php-web-terminal/refs/heads/main/img/login.png)
3.  **Execute Commands**:
    -   Type your command in the input field at the bottom.
    -   Press `Enter` to execute.
4.  **Special Commands**:
    -   `cd <directory_path>`: Change the current working directory. Examples: `cd ..`, `cd /var/www`, `cd C:\Users\Public`
    -   `edit <file_path>`: Opens a modal editor for the specified file. If the file doesn't exist, it will be created upon saving.
    -   `download <file_path>`: Initiates a download for the specified file.
    -   `clear`: Clears the terminal output (client-side only).
5.  **Command History**: Use the `Up` and `Down` arrow keys to navigate through previously entered commands.
6.  **Custom Shell**: Use the "Custom Shell" input field to specify a different interpreter (e.g., `/bin/bash`, `python`, `php`) to run commands with. Leave empty to use the default shell.
7.  **Abort Task**: Click the **Abort Task** button to terminate a long-running streamed command.
8.  **File Upload**:
    -   Use the "**Choose File**" button at the bottom.
    -   Select the file from your local system.
    -   Click the "**Upload**" button to upload the file to the current working directory.

## Security Considerations

-   **Strong Password**: Always change the default password immediately to a strong, unique one.
-   **Limited Access**: Consider restricting access to `term.php` to specific IP addresses using web server configurations (e.g., Apache's `.htaccess` or Nginx configuration) or firewall rules.
-   **Dedicated Environment**: If possible, host this script in a sandboxed or dedicated environment.
-   **Monitor Logs**: Regularly check server access logs and `term_error_log` (generated in the same directory as `term.php`) for suspicious activity.
-   **Disabled Functions**: The header displays **Disabled Functions** from `php.ini`. Be aware of which functions are enabled/disabled on your server, as some may pose security risks if misused.

## License

This project is open-source and available under the [MIT License](https://opensource.org/licenses/MIT).