<?php
session_start();

// Καταστρέφουμε όλα τα δεδομένα της session
session_destroy();

// Επιστροφή στη σελίδα login
header('Location: login.php');
exit;
