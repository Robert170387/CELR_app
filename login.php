<?php
require_once 'includes/config_security.php';
session_start();
require_once 'includes/functions.php';
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user) {
        $now = date('Y-m-d H:i:s');
        if (!empty($user['locked_until']) && $user['locked_until'] > $now) {
            $minutes = ceil((strtotime($user['locked_until']) - time()) / 60);
            $error = "Cuenta bloqueada. Intente en $minutes minutos.";
        } else {
            if (password_verify($password, $user['password_hash'])) {
                if (!isset($user['status']) || $user['status'] === 'inactive' || !$user['active']) {
                    $error = "Su cuenta esta desactivada. Contacte al administrador.";
                } else {
                    $pdo->prepare("UPDATE users SET failed_attempts=0,locked_until=NULL,last_login=NOW() WHERE id=?")->execute([$user['id']]);
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['role']      = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
                    $_SESSION['avatar']    = $user['avatar'] ?? '';
                    header("Location: " . (in_array($user['role'],['cliente','conductor']) ? 'portal/dashboard.php' : 'index.php'));
                    exit;
                }
            } else {
                $attempts = ($user['failed_attempts'] ?? 0) + 1;
                if ($attempts >= 5) {
                    $locked = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    $pdo->prepare("UPDATE users SET failed_attempts=?,locked_until=? WHERE id=?")->execute([$attempts,$locked,$user['id']]);
                    $error = "Cuenta bloqueada 15 min por demasiados intentos fallidos.";
                } else {
                    $pdo->prepare("UPDATE users SET failed_attempts=? WHERE id=?")->execute([$attempts,$user['id']]);
                    $error = "Usuario o contrasena invalidos. Intentos: $attempts / 5.";
                }
            }
        }
    } else {
        $error = "Usuario o contrasena invalidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CELR App - Iniciar Sesion</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{min-height:100vh;display:flex;font-family:'Segoe UI',sans-serif;overflow:hidden;}

.bg-scene{position:fixed;inset:0;z-index:0;background:url('login-bg.jpg') center center/cover no-repeat;animation:kenburns 20s ease-in-out infinite alternate;}
@keyframes kenburns{0%{transform:scale(1) translateX(0)}25%{transform:scale(1.08) translateX(-1%)}50%{transform:scale(1.05) translateX(1%)}100%{transform:scale(1.06) translateX(0.5%)}}

.overlay{position:fixed;inset:0;z-index:1;background:linear-gradient(to right, rgba(0,10,40,0.25) 0%, rgba(0,10,40,0.75) 60%, rgba(0,10,40,0.92) 100%);}

.particles{position:fixed;inset:0;z-index:2;pointer-events:none;}
.particle{position:absolute;background:rgba(255,255,255,0.5);border-radius:50%;animation:floatP linear infinite;}
@keyframes floatP{0%{transform:translateY(100vh);opacity:0}10%{opacity:1}90%{opacity:1}100%{transform:translateY(-10vh) translateX(30px);opacity:0}}

/* Layout split */
.layout{position:relative;z-index:10;display:flex;width:100%;min-height:100vh;}

/* Left branding panel */
.brand-panel{flex:1;display:flex;flex-direction:column;justify-content:flex-end;padding:4rem;color:#fff;}
.brand-panel .tagline{font-size:2.8rem;font-weight:800;line-height:1.15;letter-spacing:-1px;text-shadow:0 2px 20px rgba(0,0,0,0.5);}
.brand-panel .tagline span{color:#60a5fa;}
.brand-panel .desc{margin-top:1rem;font-size:1rem;color:rgba(255,255,255,0.6);max-width:380px;line-height:1.6;}
.brand-panel .badges{display:flex;gap:0.75rem;margin-top:2rem;flex-wrap:wrap;}
.badge{background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);border-radius:8px;padding:0.4rem 0.9rem;font-size:0.75rem;font-weight:600;color:rgba(255,255,255,0.8);backdrop-filter:blur(8px);}

/* Right form panel */
.form-panel{width:420px;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem;background:rgba(5,15,40,0.55);backdrop-filter:blur(24px);border-left:1px solid rgba(255,255,255,0.08);}

.login-card{width:100%;max-width:360px;animation:slideUp 0.8s cubic-bezier(0.16,1,0.3,1) both;}
@keyframes slideUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}

.logo-icon{width:68px;height:68px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;font-size:2rem;box-shadow:0 8px 25px rgba(59,130,246,0.5);animation:pulse-glow 3s ease-in-out infinite;}
@keyframes pulse-glow{0%,100%{box-shadow:0 8px 25px rgba(59,130,246,0.5)}50%{box-shadow:0 8px 40px rgba(59,130,246,0.85)}}

.login-card h1{color:#fff;font-size:1.6rem;font-weight:700;text-align:center;letter-spacing:-0.5px;}
.login-card .subtitle{color:rgba(255,255,255,0.55);font-size:0.82rem;text-align:center;margin-bottom:2rem;}

label{display:block;color:rgba(255,255,255,0.8);font-size:0.78rem;font-weight:600;margin-bottom:0.4rem;letter-spacing:0.5px;text-transform:uppercase;}

input[type="text"],input[type="password"]{width:100%;padding:0.75rem 1rem;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);border-radius:10px;color:#fff;font-size:0.95rem;margin-bottom:1.2rem;transition:all 0.3s;outline:none;}
input::placeholder{color:rgba(255,255,255,0.35);}
input:focus{border-color:#3b82f6;background:rgba(59,130,246,0.12);box-shadow:0 0 0 3px rgba(59,130,246,0.2);}

.btn-login{width:100%;padding:0.85rem;background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff;font-size:1rem;font-weight:700;border:none;border-radius:10px;cursor:pointer;letter-spacing:0.5px;transition:all 0.3s;box-shadow:0 4px 15px rgba(59,130,246,0.4);margin-top:0.25rem;}
.btn-login:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(59,130,246,0.6);}
.btn-login:active{transform:translateY(0);}

.error-msg{background:rgba(239,68,68,0.18);border:1px solid rgba(239,68,68,0.4);color:#fca5a5;padding:0.75rem 1rem;border-radius:10px;font-size:0.83rem;margin-bottom:1.2rem;text-align:center;}
.footer-text{color:rgba(255,255,255,0.3);font-size:0.72rem;text-align:center;margin-top:1.8rem;}

@media(max-width:768px){
.brand-panel{display:none;}
.form-panel{width:100%;border-left:none;background:rgba(5,15,40,0.7);}
}
</style>
</head>
<body>
<div class="bg-scene"></div>
<div class="overlay"></div>
<div class="particles">
<?php for($i=0;$i<18;$i++): $l=rand(0,100);$d=rand(0,15);$dur=rand(8,20);$s=rand(2,5); ?>
<div class="particle" style="left:<?=$l?>%;animation-duration:<?=$dur?>s;animation-delay:<?=$d?>s;width:<?=$s?>px;height:<?=$s?>px;"></div>
<?php endfor; ?>
</div>

<div class="layout">
    <!-- Panel izquierdo -->
    <div class="brand-panel">
        <div>
            <div class="tagline">Gestiona tu flota<br>con <span>inteligencia.</span></div>
            <p class="desc">Control total de viajes, gastos, liquidaciones y mantenimiento en una sola plataforma.</p>
            <div class="badges">
                <span class="badge">Registro de Viajes</span>
                <span class="badge">Liquidaciones</span>
                <span class="badge">Gastos</span>
                <span class="badge">Mantenimiento</span>
                <span class="badge">RNDC</span>
            </div>
        </div>
    </div>

    <!-- Panel derecho con formulario -->
    <div class="form-panel">
        <div class="login-card">
            <div class="logo-icon">&#x1F69A;</div>
            <h1>CELR App</h1>
            <p class="subtitle">Sistema de Gestion de Flota</p>

            <?php if ($error): ?>
            <div class="error-msg">&#9888; <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" placeholder="Ingrese su usuario" required autofocus>
                <label for="password">Contrasena</label>
                <div style="position:relative;">
                    <input type="password" id="password" name="password" placeholder="············" required style="padding-right:3rem;">
                    <button type="button" onclick="const i=document.getElementById('password');i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'👁️':'🙈';" style="position:absolute;right:0.75rem;top:50%;transform:translateY(-60%);background:none;border:none;cursor:pointer;font-size:1.1rem;color:rgba(255,255,255,0.6);">👁️</button>
                </div>
                <button type="submit" class="btn-login">Entrar al Sistema</button>
            </form>
            <p class="footer-text">&copy; <?= date('Y') ?> CELR App &mdash; Todos los derechos reservados</p>
        </div>
    </div>
</div>
</body>
</html>
