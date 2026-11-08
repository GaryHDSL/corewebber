<?php
declare(strict_types=1);
session_start();

/*
 * Core Webber — dynamic services portal
 * PHP 8.1+ / MySQL. Set database credentials in config.php (never commit secrets).
 */
$configFile = __DIR__ . '/config.php';
$config = file_exists($configFile) ? require $configFile : [];
$db = null;
try {
    if (!empty($config['db_host']) && !empty($config['db_name'])) {
        $db = new PDO(
            'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
            $config['db_user'] ?? '',
            $config['db_pass'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
} catch (Throwable $e) { error_log($e->getMessage()); }

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function logged_in(): bool { return !empty($_SESSION['user']); }
function go(string $to): never { header('Location: '.$to); exit; }
function notice(string $kind, string $message): void { $_SESSION['flash'] = [$kind, $message]; }
$services = [
 ['Road Construction','Road building, rehabilitation, paving, drainage and maintenance','road'],
 ['Housing & Building','Residential homes, commercial buildings, renovations and project delivery','home'],
 ['Civil Engineering','Site works, foundations, earthworks, concrete and infrastructure','engineering'],
 ['Mining Services','Mining support, equipment sourcing, site logistics and contractor coordination','mining'],
 ['Farming & Agriculture','Farm development, irrigation, equipment, inputs and agricultural projects','farming'],
 ['Logistics & Transport','Truck hire, freight coordination, fleet support and materials delivery','logistics'],
 ['Plant & Equipment','Construction and farming machinery sourcing, hire and maintenance','equipment'],
 ['Electrical & Solar','Solar lighting, electrical installations and energy solutions','solar'],
 ['Bricks & Building Materials','Bricks, cement, aggregates and construction material sourcing','materials'],
 ['Repairs & Maintenance','General repairs, maintenance and technical support','repairs'],
 ['Sourcing & Procurement','Supplier discovery, quotations and procurement coordination','sourcing'],
 ['Small Business Services','Cleaning, landscaping, fabrication and general support services','small-services']
];
$action = $_POST['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$db) { notice('error','The database is not connected yet. Import database.sql and configure config.php.'); go('?page=contact'); }
    if ($action === 'register') {
        $name = trim($_POST['name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? ''; $role = in_array($_POST['role'] ?? '', ['client','partner'], true) ? $_POST['role'] : 'client';
        if ($name === '' || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            notice('error','Enter your name, a valid email and a password of at least 8 characters.'); go('?page=register');
        }
        try {
            $q=$db->prepare('INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)');
            $q->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role]);
            $_SESSION['user']=['id'=>(int)$db->lastInsertId(),'name'=>$name,'email'=>$email,'role'=>$role];
            go('?page=dashboard');
        } catch (Throwable $e) { notice('error','That email may already be registered. Please sign in.'); go('?page=register'); }
    }
    if ($action === 'login') {
        $q=$db->prepare('SELECT id,name,email,password_hash,role FROM users WHERE email=? LIMIT 1'); $q->execute([strtolower(trim($_POST['email'] ?? ''))]); $u=$q->fetch();
        if ($u && password_verify($_POST['password'] ?? '',$u['password_hash'])) {
            session_regenerate_id(true); unset($u['password_hash']); $_SESSION['user']=$u; go('?page=dashboard');
        }
        notice('error','Email or password is incorrect.'); go('?page=login');
    }
    if ($action === 'request') {
        $q=$db->prepare('INSERT INTO service_requests(user_id,service,title,details,status) VALUES(?,?,?,?,?)');
        $q->execute([logged_in()?$_SESSION['user']['id']:null,trim($_POST['service']??''),trim($_POST['title']??''),trim($_POST['details']??''),'New']);
        notice('success','Your project enquiry has been received. Our team will contact you.'); go('?page=services');
    }
    if ($action === 'logout') { $_SESSION=[]; session_destroy(); go('?'); }
}
$page = $_GET['page'] ?? 'home';
$allowed = ['home','services','about','contact','login','register','dashboard','projects'];
if (!in_array($page,$allowed,true)) $page='home';
if (in_array($page,['dashboard','projects'],true) && !logged_in()) go('?page=login');
$user = $_SESSION['user'] ?? null;
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$requests=[];
if ($db && $user) {
    if ($user['role']==='admin') $requests=$db->query('SELECT r.*,u.name AS client_name FROM service_requests r LEFT JOIN users u ON u.id=r.user_id ORDER BY r.created_at DESC')->fetchAll();
    else { $q=$db->prepare('SELECT * FROM service_requests WHERE user_id=? ORDER BY created_at DESC'); $q->execute([$user['id']]); $requests=$q->fetchAll(); }
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Core Webber connects clients and partners with road construction, housing, civil engineering, mining, farming, logistics and essential services.">
<title><?= e(ucwords($page)) ?> | Core Webber</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--ink:#15251f;--green:#176b4b;--lime:#c6ed75;--sand:#f4f2eb;--muted:#64736c;--white:#fff;--line:#e4e9e2}*{box-sizing:border-box}body{margin:0;background:#fff;color:var(--ink);font-family:'DM Sans',sans-serif}a{color:inherit;text-decoration:none}.wrap{width:min(1160px,92%);margin:auto}.top{background:#10251e;color:#dce9df;padding:10px 0;font-size:13px}.top .wrap{display:flex;justify-content:space-between;gap:15px;flex-wrap:wrap}header{background:#fff;position:sticky;top:0;z-index:5;border-bottom:1px solid var(--line)}nav{min-height:78px;display:flex;align-items:center;justify-content:space-between;gap:20px}.brand{font:800 25px Manrope;letter-spacing:-1.3px}.brand b{color:var(--green)}.links{display:flex;align-items:center;gap:23px;font-size:13px;font-weight:700;text-transform:uppercase}.links a:hover{color:var(--green)}.btn{display:inline-block;background:var(--green);color:white;padding:13px 19px;border-radius:7px;font-weight:700;border:0;cursor:pointer}.btn.light{background:var(--lime);color:#183224}.btn.outline{background:transparent;border:1px solid #b8c9be;color:var(--ink)}.hero{background:linear-gradient(90deg,rgba(8,30,22,.91),rgba(8,30,22,.28)),url('https://images.unsplash.com/photo-1519501025264-65ba15a82390?auto=format&fit=crop&w=2000&q=85') center/cover;color:#fff;padding:112px 0 105px}.eyebrow{font-size:12px;letter-spacing:2px;text-transform:uppercase;color:var(--lime);font-weight:800}.hero h1{font:800 clamp(40px,6vw,72px)/1.04 Manrope;max-width:760px;margin:20px 0}.hero p{font-size:18px;line-height:1.7;color:#e1e9e3;max-width:630px}.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:28px}.section{padding:76px 0}.soft{background:var(--sand)}.section h2{font:800 clamp(30px,4vw,44px)/1.15 Manrope;margin:10px 0 15px}.lead{color:var(--muted);line-height:1.7;max-width:700px}.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:32px}.service{background:#fff;border:1px solid var(--line);border-radius:12px;padding:25px;min-height:190px;transition:.2s}.service:hover{transform:translateY(-4px);box-shadow:0 14px 35px #14271b12}.service .num{color:var(--green);font-weight:800;font-size:12px}.service h3{font:700 20px Manrope;margin:16px 0 9px}.service p{color:var(--muted);line-height:1.6;font-size:14px}.split{display:grid;grid-template-columns:1fr 1fr;gap:55px;align-items:center}.photo{min-height:390px;border-radius:16px;background:url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1200&q=85') center/cover}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:25px}.stat{padding:18px;background:#fff;border-radius:10px}.stat strong{display:block;font:800 27px Manrope;color:var(--green)}.stat span{font-size:12px;color:var(--muted)}.cta{background:#153d2c;color:#fff;padding:48px;border-radius:16px;display:flex;justify-content:space-between;align-items:center;gap:25px}.cta h2{margin:0}.formbox{max-width:650px;background:#fff;border:1px solid var(--line);border-radius:14px;padding:28px;margin:30px auto}.field{display:grid;gap:7px;margin:15px 0}.field label{font-weight:700;font-size:13px}.field input,.field select,.field textarea{width:100%;padding:13px;border:1px solid #ccd8d0;border-radius:7px;font:inherit}.field textarea{min-height:120px}.notice{padding:13px;border-radius:8px;background:#edf6e9;margin:15px 0}.notice.error{background:#fff0ec;color:#8a3020}.dashhead{background:#173b2c;color:white;padding:45px 0}.dashgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin:25px 0}.dashcard{padding:22px;border:1px solid var(--line);border-radius:12px;background:white}.dashcard strong{font:800 28px Manrope;display:block;color:var(--green)}table{width:100%;border-collapse:collapse;background:#fff}td,th{text-align:left;padding:14px;border-bottom:1px solid var(--line);font-size:13px}th{background:#f3f6f2}footer{background:#10251e;color:#dce9df;padding:50px 0;margin-top:65px}footer .foot{display:flex;justify-content:space-between;gap:25px;flex-wrap:wrap}.small{font-size:13px;color:#9eafa5}@media(max-width:800px){.links{gap:10px;flex-wrap:wrap;justify-content:flex-end;font-size:11px}.grid{grid-template-columns:repeat(2,1fr)}.split{grid-template-columns:1fr}.hero{padding:75px 0}.cta{display:block}.dashgrid{grid-template-columns:1fr 1fr}}@media(max-width:520px){nav{align-items:flex-start;padding:15px 0;flex-direction:column}.links{justify-content:flex-start}.grid{grid-template-columns:1fr}.stats{grid-template-columns:1fr}.dashgrid{grid-template-columns:1fr}.cta{padding:28px}}
</style></head><body>
<div class="top"><div class="wrap"><span>BUILDING PLACES. CONNECTING OPPORTUNITY.</span><span>Projects • Partners • Practical Services</span></div></div>
<header><nav class="wrap"><a class="brand" href="?">CORE <b>WEBBER</b></a><div class="links"><a href="?">Home</a><a href="?page=services">Services</a><a href="?page=about">About</a><a href="?page=contact">Contact</a><?php if($user):?><a href="?page=dashboard">Dashboard</a><form method="post" style="display:inline"><input type="hidden" name="action" value="logout"><button class="btn outline" style="padding:8px 12px">Logout</button></form><?php else:?><a href="?page=login">Sign in</a><a class="btn" href="?page=register">Create account</a><?php endif;?></div></nav></header>
<?php if($flash):?><div class="wrap"><div class="notice <?=e($flash[0])?>"><?=e($flash[1])?></div></div><?php endif;?>
<?php if($page==='home'):?>
<section class="hero"><div class="wrap"><div class="eyebrow">Infrastructure • Property • Agriculture • Mining</div><h1>Building better roads, homes and opportunities.</h1><p>One connected platform for construction, civil engineering, farming, mining, logistics, sourcing and the everyday services that keep projects moving.</p><div class="actions"><a class="btn light" href="?page=services">Explore our services</a><a class="btn outline" style="color:#fff;border-color:#fff" href="?page=register">Join as a partner</a></div></div></section>
<section class="section"><div class="wrap"><div class="eyebrow" style="color:var(--green)">What we do</div><h2>From the first plan to the final delivery.</h2><p class="lead">Find the right service, submit a project enquiry and connect with a growing network of clients and delivery partners.</p><div class="grid"><?php foreach(array_slice($services,0,6) as $i=>$s):?><a class="service" href="?page=services"><span class="num">0<?= $i+1 ?> / CORE SERVICE</span><h3><?=e($s[0])?></h3><p><?=e($s[1])?></p></a><?php endforeach;?></div><div style="margin-top:25px"><a class="btn" href="?page=services">View all services →</a></div></div></section>
<section class="section soft"><div class="wrap split"><div class="photo"></div><div><div class="eyebrow" style="color:var(--green)">A connected network</div><h2>One place for clients and trusted partners.</h2><p class="lead">Clients can submit project requests and track enquiries. Partners can create a profile, introduce their capabilities and connect with opportunities. Our team coordinates the next steps.</p><div class="stats"><div class="stat"><strong>01</strong><span>Submit a request</span></div><div class="stat"><strong>02</strong><span>Connect to a partner</span></div><div class="stat"><strong>03</strong><span>Move work forward</span></div></div></div></div></section>
<section class="section"><div class="wrap"><div class="cta"><div><div class="eyebrow">Start a project</div><h2>Have a project or service to offer?</h2></div><a class="btn light" href="?page=contact">Talk to Core Webber</a></div></div></section>
<?php elseif($page==='services'):?>
<section class="section"><div class="wrap"><div class="eyebrow" style="color:var(--green)">Our service network</div><h2>Practical services for every stage.</h2><p class="lead">Explore our main divisions and supporting services. Select a service below to send an enquiry.</p><div class="grid"><?php foreach($services as $i=>$s):?><div class="service"><span class="num">SERVICE <?=sprintf('%02d',$i+1)?></span><h3><?=e($s[0])?></h3><p><?=e($s[1])?></p><a href="?page=contact" style="color:var(--green);font-weight:800;font-size:13px">Request this service →</a></div><?php endforeach;?></div></div></section>
<?php elseif($page==='about'):?>
<section class="section"><div class="wrap split"><div><div class="eyebrow" style="color:var(--green)">About Core Webber</div><h2>Connecting skills, suppliers and projects.</h2><p class="lead">Core Webber is a multi-service project and business network designed to make it easier to find practical support across infrastructure, property, agriculture, mining, transport and day-to-day business services.</p><p class="lead">We bring clients and independent service partners together through clear enquiries, capability profiles and coordinated project communication.</p><a class="btn" href="?page=register">Join the network</a></div><div class="photo"></div></div></section>
<?php elseif($page==='contact'):?>
<section class="section"><div class="wrap"><div class="eyebrow" style="color:var(--green);text-align:center">Contact & enquiries</div><h2 style="text-align:center">Tell us what you need.</h2><p class="lead" style="margin:auto;text-align:center">Share your project details and the Core Webber team can follow up.</p><form class="formbox" method="post"><input type="hidden" name="action" value="request"><div class="field"><label>Service required</label><select name="service" required><?php foreach($services as $s):?><option><?=e($s[0])?></option><?php endforeach;?></select></div><div class="field"><label>Project or enquiry title</label><input name="title" required placeholder="e.g. Road rehabilitation project"></div><div class="field"><label>Tell us more</label><textarea name="details" required placeholder="Location, scope, timing and any requirements"></textarea></div><button class="btn">Submit enquiry</button></form></div></section>
<?php elseif($page==='login'||$page==='register'):?>
<section class="section soft"><div class="wrap"><form class="formbox" method="post"><h2><?= $page==='login'?'Welcome back':'Create your account' ?></h2><p class="lead"><?= $page==='login'?'Sign in to view your project dashboard.':'Register as a client or service partner.' ?></p><input type="hidden" name="action" value="<?= $page==='login'?'login':'register' ?>"><?php if($page==='register'):?><div class="field"><label>Full name / Business name</label><input name="name" required></div><div class="field"><label>Account type</label><select name="role"><option value="client">Client account</option><option value="partner">Partner account</option></select></div><?php endif;?><div class="field"><label>Email address</label><input type="email" name="email" required></div><div class="field"><label>Password (minimum 8 characters)</label><input type="password" name="password" required minlength="8"></div><button class="btn"><?= $page==='login'?'Sign in':'Create account' ?></button><p class="small"><?php if($page==='login'):?>New here? <a href="?page=register">Create an account</a><?php else:?>Already registered? <a href="?page=login">Sign in</a><?php endif;?></p></form></div></section>
<?php elseif($page==='dashboard'):?>
<section class="dashhead"><div class="wrap"><div class="eyebrow">Member portal</div><h1 style="font:800 38px Manrope">Hello, <?=e($user['name'])?></h1><p><?=e(ucfirst($user['role']))?> account · Manage your Core Webber activity</p></div></section><section class="section"><div class="wrap"><div class="dashgrid"><div class="dashcard"><strong><?=count($requests)?></strong><span>Project enquiries</span></div><div class="dashcard"><strong><?=e(ucfirst($user['role']))?></strong><span>Account type</span></div><div class="dashcard"><strong>Active</strong><span>Portal access</span></div></div><h2>Your enquiries</h2><?php if(!$requests):?><p class="lead">No enquiries yet. <a href="?page=contact" style="color:var(--green)">Submit your first project request.</a></p><?php else:?><div style="overflow:auto"><table><thead><tr><?php if($user['role']==='admin'):?><th>Client</th><?php endif;?><th>Service</th><th>Project</th><th>Status</th><th>Date</th></tr></thead><tbody><?php foreach($requests as $r):?><tr><?php if($user['role']==='admin'):?><td><?=e($r['client_name']??'Guest')?></td><?php endif;?><td><?=e($r['service'])?></td><td><?=e($r['title'])?></td><td><?=e($r['status'])?></td><td><?=e($r['created_at'])?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?><?php if($user['role']==='partner'):?><div class="service" style="margin-top:25px"><h3>Partner account</h3><p>Your partner registration is active. Contact the Core Webber team to complete your business capability profile and verification.</p></div><?php endif;?></div></section>
<?php endif;?>
<footer><div class="wrap foot"><div><a class="brand" style="color:white" href="?">CORE <b>WEBBER</b></a><p class="small">Building places. Connecting opportunity.</p></div><div><b>Explore</b><p class="small"><a href="?page=services">Services</a> · <a href="?page=about">About</a> · <a href="?page=contact">Contact</a></p></div><div><b>Member access</b><p class="small"><a href="?page=login">Client / Partner login</a></p></div></div></footer></body></html>