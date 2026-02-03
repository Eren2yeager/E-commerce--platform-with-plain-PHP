<?php
// Display toast notification if message exists in session
function showToast() {
    if (isset($_SESSION['toast'])) {
        $toast = $_SESSION['toast'];
        $type = $toast['type'] ?? 'success'; // success, error, warning, info
        $message = htmlspecialchars($toast['message']);
        
        echo "
        <div class='toast toast-{$type}' id='toast'>
            <span class='toast-message'>{$message}</span>
            <button class='toast-close' onclick='closeToast()'>×</button>
        </div>
        ";
        
        // Clear the toast message after displaying
        unset($_SESSION['toast']);
    }
}

// Set toast message
function setToast($message, $type = 'success') {
    $_SESSION['toast'] = [
        'message' => $message,
        'type' => $type
    ];
}
?>