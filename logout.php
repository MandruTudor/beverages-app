<?php
require_once __DIR__ . '/includes/auth.php';
logout_user();
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="UTF-8"><title>Ieșire...</title></head>
<body>
<script>
    localStorage.removeItem('jwt_token');
    window.location.replace('/beverages-app/login.php');
</script>
</body>
</html>
