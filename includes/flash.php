<?php
/** Queue a one-time flash message shown on the next page render. */
function flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}
