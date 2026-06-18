<?php
require_once 'includes/db.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'inactive') {
            $error = "Su cuenta está desactivada. Contacte al administrador.";
        } else {
            $stmtUpdate = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmtUpdate->execute([$user['id']]);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['avatar'] = $user['avatar'];

            if ($user['role'] === 'cliente' || $user['role'] === 'conductor') {
                header("Location: portal/dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit;
        }
    } else {
        $error = "Usuario o contraseña inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CELR App — Iniciar Sesión</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
            overflow: hidden;
        }

        /* Fondo con imagen y efecto Ken Burns */
        .bg-scene {
            position: fixed;
            inset: 0;
            z-index: 0;
            background: url('login-bg.jpg') center center / cover no-repeat;
            animation: kenburns 20s ease-in-out infinite alternate;
            transform-origin: center center;
        }

        @keyframes kenburns {
            0%   { transform: scale(1)    translateX(0)     translateY(0); }
            25%  { transform: scale(1.08) translateX(-1%)   translateY(-1%); }
            50%  { transform: scale(1.05) translateX(1%)    translateY(0.5%); }
            75%  { transform: scale(1.1)  translateX(-0.5%) translateY(1%); }
            100% { transform: scale(1.06) translateX(0.5%)  translateY(-0.5%); }
        }

        /* Partículas flotantes */
        .particles {
            position: fixed;
            inset: 0;
            z-index: 1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255,255,255,0.6);
            border-radius: 50%;
            animation: float linear infinite;
        }

        @keyframes float {
            0%   { transform: translateY(100vh) translateX(0); opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 1; }
            100% { transform: translateY(-10vh) translateX(30px); opacity: 0; }
        }

        /* Overlay oscuro */
        .overlay {
            position: fixed;
            inset: 0;
            z-index: 2;
            background: linear-gradient(135deg, rgba(0,20,60,0.65) 0%, rgba(0,0,0,0.45) 100%);
        }

        /* Card del login */
        .login-card {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.2);
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0)    scale(1); }
        }

        /* Logo / ícono */
        .logo-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            font-size: 2rem;
            box-shadow: 0 8px 25px rgba(59,130,246,0.5);
            animation: pulse-glow 3s ease-in-out infinite;
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 8px 25px rgba(59,130,246,0.5); }
            50%       { box-shadow: 0 8px 40px rgba(59,130,246,0.85); }
        }

        .login-card h1 {
            color: #fff;
            font-size: 1.6rem;
            font-weight: 700;
            text-align: center;
            letter-spacing: -0.5px;
        }

        .login-card p.subtitle {
            color: rgba(255,255,255,0.65);
            font-size: 0.85rem;
            text-align: center;
            margin-bottom: 1.8rem;
        }

        label {
            display: block;
            color: rgba(255,255,255,0.85);
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            color: #fff;
            font-size: 0.95rem;
            margin-bottom: 1.2rem;
            transition: all 0.3s;
            outline: none;
        }

        input::placeholder { color: rgba(255,255,255,0.4); }

        input:focus {
            border-color: #3b82f6;
            background: rgba(59,130,246,0.15);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.25);
        }

        .btn-login {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(59,130,246,0.4);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59,130,246,0.6);
        }

        .btn-login:active { transform: translateY(0); }

        .error-msg {
            background: rgba(239,68,68,0.2);
            border: 1px solid rgba(239,68,68,0.5);
            color: #fca5a5;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1.2rem;
            text-align: center;
        }

        .footer-text {
            color: rgba(255,255,255,0.4);
            font-size: 0.75rem;
            text-align: center;
            margin-top: 1.5rem;
        }

    </style>
</head>
<body>

    <!-- Fondo animado -->
    <div class="bg-scene"></div>

    <!-- Partículas -->
    <div class="particles">
        <?php for ($i = 0; $i < 18; $i++):
            $left  = rand(0, 100);
            $delay = rand(0, 15);
            $dur   = rand(8, 20);
            $size  = rand(2, 5);
        ?>
        <div class="particle" style="left:<?= $left ?>%;animation-duration:<?= $dur ?>s;animation-delay:<?= $delay ?>s;width:<?= $size ?>px;height:<?= $size ?>px;"></div>
        <?php endfor; ?>
    </div>

    <!-- Overlay -->
    <div class="overlay"></div>

    <!-- Card -->
    <div class="login-card">
        <div class="logo-icon">🚛</div>
        <h1>CELR App</h1>
        <p class="subtitle">Sistema de Gestión de Flota</p>

        <?php if ($error): ?>
            <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="username">Usuario</label>
            <input type="text" id="username" name="username" placeholder="Ingrese su usuario" required autofocus>

            <label for="password">Contraseña</label>
            <div style="position:relative;">
                <input type="password" id="password" name="password" placeholder="••••••••••••" required style="padding-right:3rem;">
                <button type="button" onclick="const i=document.getElementById('password');i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'👁️':'🙈';" style="position:absolute;right:0.75rem;top:50%;transform:translateY(-60%);background:none;border:none;cursor:pointer;font-size:1.1rem;color:rgba(255,255,255,0.6);">👁️</button>
            </div>

            <button type="submit" class="btn-login">Entrar al Sistema</button>
        </form>

        <p class="footer-text">&copy; <?= date('Y') ?> CELR App &mdash; Todos los derechos reservados</p>
    </div>


</body>
</html>
