<?php
// includes/functions.php

function redirect($messages, $page)
{
    if (is_array($messages)) {
        $_SESSION['messages'] = $messages;
    } else {
        $_SESSION['messages'][] = $messages;
    }
    header("Location: $page");
    exit();
}

function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

function display_alerts()
{
    if (isset($_SESSION['messages']) && !empty($_SESSION['messages'])) {
        echo '<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>';
        echo '<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">';
        echo '<script>';
        foreach ($_SESSION['messages'] as $msg) {
            $type = isset($msg['type']) ? $msg['type'] : 'info'; // success, error, warning, info
            $text = isset($msg['text']) ? $msg['text'] : $msg;

            // Map types to colors
            $bgColor = "#000000"; // Default black
            if ($type == 'success')
                $bgColor = "#10B981"; // Green
            if ($type == 'error')
                $bgColor = "#EF4444"; // Red (Keep red for errors? instruction says "No other colors". But errors usually need red. Let's stick to Green and Black dominance, maybe Black background for error?)
            // Actually, keep standard semantics for errors inside the popup, but style it elegantly.
            // Let's use Black for everything but with Green borders/accents? 
            // Or just: Success=Green, Error=Black?
            // "Primary color: Green, Secondary color: Black". 
            // Let's go Success: Green, Error: Black (High contrast).

            if ($type == 'success') {
                $style = "background: #10B981; color: white;";
            } else {
                $style = "background: #000000; color: white;";
            }

            echo 'Toastify({
                text: "' . addslashes($text) . '",
                duration: 4000,
                gravity: "top", 
                position: "right", 
                style: { ' . $style . 'boxShadow: "0 10px 15px -3px rgba(0, 0, 0, 0.1)", borderRadius: "8px", fontWeight: "bold" },
                close: true,
                stopOnFocus: true
            }).showToast();';
        }
        echo '</script>';
        unset($_SESSION['messages']);
    }
}

function check_login()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }
}

function has_role($role_name)
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['role']) && $_SESSION['role'] === $role_name;
}

function require_role($role_name)
{
    check_login();
    if (!has_role($role_name)) {
        // Redirect to their dashboard or home if unauthorized
        if (has_role('admin'))
            header("Location: ../admin/dashboard.php");
        elseif (has_role('supervisor'))
            header("Location: ../supervisor/dashboard.php");
        elseif (has_role('student'))
            header("Location: ../student/dashboard.php");
        else
            header("Location: ../index.php");
        exit();
    }
}
