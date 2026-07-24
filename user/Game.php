<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['apex_best'])) {
    $_SESSION['apex_best'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $score = max(0, (int)($body['score'] ?? 0));
    $coins = max(0, (int)($body['coins'] ?? 0));

    if ($score > (int)$_SESSION['apex_best']) {
        $_SESSION['apex_best'] = $score;
    }

    echo json_encode([
        'ok' => true,
        'best' => (int)$_SESSION['apex_best'],
        'coins' => $coins
    ]);
    exit;
}

$serverBest = (int)$_SESSION['apex_best'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no,viewport-fit=cover">
<meta name="theme-color" content="#050914">
<title>Apex Rush 3D</title>
<style>
:root{
  --cyan:#55ecff;
  --blue:#4d78ff;
  --pink:#ff4fc8;
  --lime:#adff76;
  --red:#ff5369;
  --yellow:#ffd86a;
  --glass:rgba(7,12,26,.68);
  --line:rgba(255,255,255,.13);
}
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent;user-select:none}
html,body{margin:0;width:100%;height:100%;overflow:hidden;background:#050914;color:#fff;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;touch-action:none}
button{font:inherit}
#app,#stage{position:fixed;inset:0;overflow:hidden}
#stage canvas{display:block;width:100%;height:100%;touch-action:none}
#vignette{
  position:fixed;inset:0;pointer-events:none;z-index:3;
  background:
    radial-gradient(circle at center,transparent 42%,rgba(0,0,0,.18) 75%,rgba(0,0,0,.56) 115%),
    linear-gradient(180deg,rgba(0,0,0,.12),transparent 22%,transparent 75%,rgba(0,0,0,.25));
}
#speedLines{position:fixed;inset:0;z-index:2;pointer-events:none;opacity:0;transition:opacity .2s ease}
#speedLines.active{opacity:.68}
.speed-line{
  position:absolute;left:50%;top:50%;height:2px;width:22vw;transform-origin:left center;
  background:linear-gradient(90deg,rgba(255,255,255,0),rgba(85,236,255,.7));
  filter:blur(.4px);opacity:.35
}
#hud{position:fixed;inset:0;z-index:5;pointer-events:none;padding:calc(12px + env(safe-area-inset-top)) 12px calc(12px + env(safe-area-inset-bottom))}
.hud-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
.glass{
  border:1px solid var(--line);background:var(--glass);backdrop-filter:blur(16px) saturate(130%);
  box-shadow:0 16px 45px rgba(0,0,0,.25),inset 0 1px rgba(255,255,255,.06)
}
.stat-card{min-width:108px;border-radius:18px;padding:10px 13px}
.label{display:block;color:rgba(255,255,255,.55);font-size:9px;font-weight:900;letter-spacing:.14em;text-transform:uppercase;margin-bottom:3px}
.value{font-weight:950;font-size:20px;line-height:1;letter-spacing:-.03em}
#pauseBtn,#qualityBtn{
  pointer-events:auto;border:1px solid var(--line);color:#fff;background:var(--glass);backdrop-filter:blur(14px);
  width:44px;height:44px;border-radius:15px;font-weight:900;box-shadow:0 12px 28px rgba(0,0,0,.22)
}
#pauseBtn:active,#qualityBtn:active{transform:scale(.94)}
.right-tools{display:flex;gap:8px}
.hud-bottom{position:absolute;left:12px;right:12px;bottom:calc(110px + env(safe-area-inset-bottom));display:grid;grid-template-columns:1fr auto;align-items:end;gap:10px}
.meters{display:grid;gap:8px;max-width:280px}
.meter-row{display:grid;grid-template-columns:44px 1fr 40px;align-items:center;gap:8px;font-size:10px;font-weight:900;letter-spacing:.08em}
.track{height:9px;border-radius:999px;background:rgba(255,255,255,.1);overflow:hidden;border:1px solid rgba(255,255,255,.08)}
.fill{height:100%;width:100%;border-radius:inherit;transition:width .12s linear}
#healthFill{background:linear-gradient(90deg,var(--red),#ff9f73)}
#nitroFill{background:linear-gradient(90deg,var(--blue),var(--cyan))}
.speedBox{text-align:right}
#speedValue{font-size:clamp(42px,12vw,70px);font-weight:1000;line-height:.8;letter-spacing:-.08em;text-shadow:0 0 28px rgba(85,236,255,.2)}
#speedUnit{display:block;margin-top:8px;color:rgba(255,255,255,.5);font-size:9px;font-weight:900;letter-spacing:.2em}
#combo{
  position:fixed;z-index:6;left:50%;top:22%;transform:translate(-50%,-50%) scale(.8);
  opacity:0;text-align:center;pointer-events:none;transition:opacity .15s,transform .15s
}
#combo.show{opacity:1;transform:translate(-50%,-50%) scale(1)}
#combo strong{font-size:34px;font-weight:1000;color:var(--yellow);text-shadow:0 0 25px rgba(255,216,106,.55)}
#combo span{display:block;font-size:10px;letter-spacing:.22em;font-weight:900}
#toast{
  position:fixed;z-index:8;top:calc(76px + env(safe-area-inset-top));left:50%;transform:translateX(-50%) translateY(-8px);
  opacity:0;pointer-events:none;padding:9px 14px;border-radius:14px;border:1px solid var(--line);
  background:rgba(7,12,26,.76);backdrop-filter:blur(14px);font-size:12px;font-weight:800;transition:.2s
}
#toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
#controls{
  position:fixed;z-index:7;left:0;right:0;bottom:calc(14px + env(safe-area-inset-bottom));
  display:flex;justify-content:space-between;align-items:end;padding:0 14px;pointer-events:none
}
.control-group{display:flex;gap:10px;pointer-events:none}
.control{
  pointer-events:auto;border:1px solid rgba(255,255,255,.16);color:#fff;background:rgba(6,11,24,.58);
  backdrop-filter:blur(15px);box-shadow:0 14px 35px rgba(0,0,0,.28),inset 0 1px rgba(255,255,255,.07);
  width:min(20vw,78px);height:min(18vw,68px);min-width:62px;min-height:58px;border-radius:22px;
  font-size:25px;font-weight:950;display:grid;place-items:center;transition:transform .1s,background .1s
}
.control.wide{width:min(24vw,96px);font-size:11px;letter-spacing:.1em}
.control.nitro{background:linear-gradient(160deg,rgba(77,120,255,.76),rgba(85,236,255,.35));box-shadow:0 12px 35px rgba(77,120,255,.25)}
.control.brake{background:linear-gradient(160deg,rgba(255,83,105,.65),rgba(255,132,94,.28))}
.control.pressed,.control:active{transform:scale(.92);background:rgba(85,236,255,.22)}
.overlay{
  position:fixed;inset:0;z-index:20;display:grid;place-items:center;padding:20px;
  background:linear-gradient(180deg,rgba(3,7,15,.28),rgba(3,7,15,.82));transition:opacity .25s,visibility .25s
}
.overlay.hidden{opacity:0;visibility:hidden;pointer-events:none}
.panel{
  width:min(94vw,470px);padding:24px;border-radius:30px;border:1px solid rgba(255,255,255,.15);
  background:linear-gradient(165deg,rgba(17,28,56,.93),rgba(5,9,20,.92));
  backdrop-filter:blur(22px) saturate(140%);box-shadow:0 35px 100px rgba(0,0,0,.5),inset 0 1px rgba(255,255,255,.08)
}
.brand{display:flex;align-items:center;gap:14px;margin-bottom:18px}
.logo{
  width:64px;height:64px;border-radius:21px;display:grid;place-items:center;font-size:28px;font-weight:1000;
  background:conic-gradient(from 220deg,var(--cyan),var(--blue),var(--pink),var(--cyan));
  box-shadow:0 0 42px rgba(85,236,255,.22);position:relative
}
.logo:after{content:"";position:absolute;inset:5px;border-radius:17px;background:#081021;z-index:0}
.logo span{position:relative;z-index:1}
h1{font-size:clamp(30px,10vw,48px);line-height:.9;letter-spacing:-.055em;margin:0;font-weight:1000}
.tag{margin-top:7px;color:rgba(255,255,255,.55);font-size:10px;font-weight:900;letter-spacing:.18em}
.hero-copy{color:rgba(255,255,255,.68);font-size:13px;line-height:1.55;margin:0 0 16px}
.feature-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:14px 0 18px}
.feature{border:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.035);border-radius:17px;padding:11px 8px;text-align:center}
.feature b{display:block;font-size:16px;margin-bottom:4px}
.feature span{font-size:9px;color:rgba(255,255,255,.58);font-weight:800;letter-spacing:.05em}
.menu-stats{display:flex;gap:8px;margin:0 0 15px}
.menu-stat{flex:1;border-radius:16px;padding:10px 12px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08)}
.menu-stat small{display:block;color:rgba(255,255,255,.5);font-size:9px;font-weight:900;letter-spacing:.1em}
.menu-stat strong{font-size:20px}
.primary{
  width:100%;height:58px;border:0;border-radius:19px;color:#03101a;font-size:15px;font-weight:1000;letter-spacing:.08em;
  background:linear-gradient(135deg,var(--cyan),#9fffd8 60%,var(--yellow));box-shadow:0 15px 38px rgba(85,236,255,.2)
}
.primary:active{transform:scale(.98)}
.secondary-row{display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-top:9px}
.secondary{
  height:46px;border-radius:16px;border:1px solid var(--line);background:rgba(255,255,255,.05);color:#fff;font-size:11px;font-weight:900
}
#gameOverTitle{color:#fff}
.result-score{font-size:58px;font-weight:1000;letter-spacing:-.08em;line-height:.85;margin:15px 0 4px}
.result-caption{color:rgba(255,255,255,.5);font-size:10px;font-weight:900;letter-spacing:.16em;margin-bottom:16px}
.pause-panel{text-align:center}
.pause-panel h2{font-size:40px;margin:0 0 8px}
.pause-panel p{color:rgba(255,255,255,.58);font-size:13px}
@media (max-height:650px){
  .panel{padding:18px;border-radius:24px}
  .brand{margin-bottom:10px}.logo{width:52px;height:52px}
  .feature-grid{margin:10px 0}.hero-copy{margin-bottom:10px}
  .hud-bottom{bottom:88px}.control{min-width:56px;min-height:50px}
}
</style>
</head>
<body>
<div id="app">
  <div id="stage"></div>
  <div id="speedLines"></div>
  <div id="vignette"></div>

  <div id="hud">
    <div class="hud-top">
      <div class="stat-card glass">
        <span class="label">Score</span>
        <span class="value" id="scoreText">0</span>
      </div>
      <div class="right-tools">
        <button id="qualityBtn" aria-label="Graphics quality">HQ</button>
        <button id="pauseBtn" aria-label="Pause">Ⅱ</button>
      </div>
      <div class="stat-card glass" style="text-align:right">
        <span class="label">Distance</span>
        <span class="value"><span id="distanceText">0.0</span><small style="font-size:10px"> KM</small></span>
      </div>
    </div>

    <div class="hud-bottom">
      <div class="meters">
        <div class="meter-row">
          <span>HEALTH</span>
          <div class="track"><div class="fill" id="healthFill"></div></div>
          <span id="healthText">100</span>
        </div>
        <div class="meter-row">
          <span>NITRO</span>
          <div class="track"><div class="fill" id="nitroFill"></div></div>
          <span id="nitroText">100</span>
        </div>
      </div>
      <div class="speedBox">
        <span id="speedValue">0</span>
        <span id="speedUnit">KM/H</span>
      </div>
    </div>
  </div>

  <div id="combo"><strong id="comboValue">2X</strong><span>NEAR MISS COMBO</span></div>
  <div id="toast">Perfect dodge +250</div>

  <div id="controls">
    <div class="control-group">
      <button class="control" id="leftBtn" aria-label="Steer left">◀</button>
      <button class="control" id="rightBtn" aria-label="Steer right">▶</button>
    </div>
    <div class="control-group">
      <button class="control wide brake" id="brakeBtn">BRAKE</button>
      <button class="control wide nitro" id="nitroBtn">NITRO</button>
    </div>
  </div>

  <section class="overlay" id="menuOverlay">
    <div class="panel">
      <div class="brand">
        <div class="logo"><span>AR</span></div>
        <div>
          <h1>APEX<br>RUSH 3D</h1>
          <div class="tag">ULTRA MOBILE ROAD SURVIVAL</div>
        </div>
      </div>
      <p class="hero-copy">
        Race through dynamic traffic, collect energy, trigger nitro and survive an increasingly dangerous highway.
      </p>
      <div class="feature-grid">
        <div class="feature"><b>◈</b><span>DYNAMIC TRAFFIC</span></div>
        <div class="feature"><b>⚡</b><span>NITRO BOOST</span></div>
        <div class="feature"><b>✦</b><span>COMBO SYSTEM</span></div>
      </div>
      <div class="menu-stats">
        <div class="menu-stat"><small>BEST SCORE</small><strong id="menuBest"><?= $serverBest ?></strong></div>
        <div class="menu-stat"><small>TOTAL COINS</small><strong id="menuCoins">0</strong></div>
      </div>
      <button class="primary" id="startBtn">START ENGINE</button>
      <div class="secondary-row">
        <button class="secondary" id="howBtn">HOW TO PLAY</button>
        <button class="secondary" id="soundBtn">SOUND: ON</button>
      </div>
    </div>
  </section>

  <section class="overlay hidden" id="pauseOverlay">
    <div class="panel pause-panel">
      <h2>PAUSED</h2>
      <p>Take a breath. The highway is waiting.</p>
      <button class="primary" id="resumeBtn">RESUME</button>
    </div>
  </section>

  <section class="overlay hidden" id="gameOverOverlay">
    <div class="panel">
      <div class="tag">RUN COMPLETE</div>
      <h1 id="gameOverTitle">CAR WRECKED</h1>
      <div class="result-score" id="finalScore">0</div>
      <div class="result-caption">FINAL SCORE</div>
      <div class="menu-stats">
        <div class="menu-stat"><small>DISTANCE</small><strong id="finalDistance">0.0 KM</strong></div>
        <div class="menu-stat"><small>COINS</small><strong id="finalCoins">0</strong></div>
        <div class="menu-stat"><small>BEST</small><strong id="finalBest">0</strong></div>
      </div>
      <button class="primary" id="restartBtn">RACE AGAIN</button>
      <div class="secondary-row">
        <button class="secondary" id="homeBtn">MAIN MENU</button>
        <button class="secondary" id="shareBtn">COPY SCORE</button>
      </div>
    </div>
  </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/three@0.152.2/build/three.min.js"></script>
<script>
(() => {
'use strict';
if (!window.THREE) {
  const stage = document.getElementById('stage');
  stage.innerHTML = '<div style="position:fixed;inset:0;display:grid;place-items:center;padding:24px;background:#050914;color:white;text-align:center;font-family:system-ui"><div><h2>3D Engine could not load</h2><p>Please connect to the internet and reload the game once.</p></div></div>';
  return;
}

const $ = id => document.getElementById(id);
const ui = {
  stage:$('stage'), hud:$('hud'), menu:$('menuOverlay'), pause:$('pauseOverlay'), gameOver:$('gameOverOverlay'),
  score:$('scoreText'), distance:$('distanceText'), speed:$('speedValue'), healthFill:$('healthFill'),
  healthText:$('healthText'), nitroFill:$('nitroFill'), nitroText:$('nitroText'), combo:$('combo'),
  comboValue:$('comboValue'), toast:$('toast'), best:$('menuBest'), coins:$('menuCoins'),
  finalScore:$('finalScore'), finalDistance:$('finalDistance'), finalCoins:$('finalCoins'), finalBest:$('finalBest'),
  start:$('startBtn'), restart:$('restartBtn'), resume:$('resumeBtn'), home:$('homeBtn'),
  left:$('leftBtn'), right:$('rightBtn'), brake:$('brakeBtn'), nitro:$('nitroBtn'),
  pauseBtn:$('pauseBtn'), quality:$('qualityBtn'), sound:$('soundBtn'), how:$('howBtn'),
  share:$('shareBtn'), speedLines:$('speedLines')
};

const state = {
  mode:'menu',
  score:0,
  distance:0,
  coins:0,
  totalCoins:Number(localStorage.getItem('apexTotalCoins') || 0),
  best:Math.max(Number(localStorage.getItem('apexBest') || 0), Number(ui.best.textContent || 0)),
  health:100,
  nitro:100,
  speed:0,
  targetSpeed:38,
  steer:0,
  targetSteer:0,
  brake:false,
  nitroOn:false,
  combo:1,
  comboTimer:0,
  elapsed:0,
  quality:localStorage.getItem('apexQuality') || 'high',
  sound:localStorage.getItem('apexSound') !== 'off',
  lastHit:0,
  shake:0,
  worldTime:0,
  roadPhase:0
};

ui.best.textContent = state.best;
ui.coins.textContent = state.totalCoins;
ui.sound.textContent = state.sound ? 'SOUND: ON' : 'SOUND: OFF';
ui.quality.textContent = state.quality === 'high' ? 'HQ' : 'LQ';

let scene, camera, renderer, clock, player, carBody, wheels = [];
let roadSegments = [], scenery = [], traffic = [], pickups = [], particles = [];
let sun, hemi, ambient, roadTexture;
let audioCtx = null, engineOsc = null, engineGain = null;
let keyState = {};
let isPointerSteering = false;
let swipeStart = null;
const ROAD_WIDTH = 14;
const SEGMENT_LENGTH = 42;
const ROAD_COUNT = 11;
const TRAFFIC_COUNT = 10;
const PICKUP_COUNT = 12;
const LANES = [-4.2, 0, 4.2];

init();

function init(){
  createSpeedLines();
  setupThree();
  buildWorld();
  bindEvents();
  updateHUD();
  clock = new THREE.Clock();
  animate();
}

function setupThree(){
  scene = new THREE.Scene();
  scene.background = new THREE.Color(0x08111f);
  scene.fog = new THREE.FogExp2(0x091426, 0.0105);

  camera = new THREE.PerspectiveCamera(62, innerWidth / innerHeight, 0.1, 800);
  camera.position.set(0, 4.8, 10.8);

  renderer = new THREE.WebGLRenderer({
    antialias: state.quality === 'high',
    powerPreference: 'high-performance'
  });
  renderer.setPixelRatio(Math.min(devicePixelRatio, state.quality === 'high' ? 1.75 : 1.15));
  renderer.setSize(innerWidth, innerHeight);
  renderer.shadowMap.enabled = state.quality === 'high';
  renderer.shadowMap.type = THREE.PCFSoftShadowMap;
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.05;
  ui.stage.appendChild(renderer.domElement);

  hemi = new THREE.HemisphereLight(0x9ddcff, 0x171321, 1.45);
  scene.add(hemi);

  ambient = new THREE.AmbientLight(0x5d70a0, .75);
  scene.add(ambient);

  sun = new THREE.DirectionalLight(0xffe4c7, 2.4);
  sun.position.set(-18, 28, 12);
  sun.castShadow = state.quality === 'high';
  sun.shadow.mapSize.set(1024,1024);
  sun.shadow.camera.left = -30;
  sun.shadow.camera.right = 30;
  sun.shadow.camera.top = 35;
  sun.shadow.camera.bottom = -20;
  sun.shadow.camera.near = 1;
  sun.shadow.camera.far = 90;
  scene.add(sun);
}

function buildWorld(){
  roadTexture = makeRoadTexture();
  buildRoad();
  buildPlayer();
  buildTraffic();
  buildPickups();
  buildScenery();
  buildSkyObjects();
}

function makeRoadTexture(){
  const c = document.createElement('canvas');
  c.width = 512; c.height = 1024;
  const x = c.getContext('2d');

  const g = x.createLinearGradient(0,0,512,0);
  g.addColorStop(0,'#1b2130');
  g.addColorStop(.5,'#242b3b');
  g.addColorStop(1,'#171d2b');
  x.fillStyle = g;
  x.fillRect(0,0,c.width,c.height);

  for(let i=0;i<5000;i++){
    const v = 28 + Math.random()*24;
    x.fillStyle = `rgba(${v},${v+4},${v+9},${Math.random()*.22})`;
    x.fillRect(Math.random()*512,Math.random()*1024,1+Math.random()*2,1+Math.random()*4);
  }

  x.fillStyle='#f2f5ff';
  x.fillRect(18,0,9,1024);
  x.fillRect(485,0,9,1024);

  x.fillStyle='#f7f7f7';
  for(let y=-120;y<1100;y+=170){
    x.fillRect(167,y,7,88);
    x.fillRect(338,y,7,88);
  }

  const t = new THREE.CanvasTexture(c);
  t.wrapS = t.wrapT = THREE.RepeatWrapping;
  t.repeat.set(1,1);
  t.anisotropy = renderer.capabilities.getMaxAnisotropy();
  t.colorSpace = THREE.SRGBColorSpace;
  return t;
}

function buildRoad(){
  const roadMat = new THREE.MeshStandardMaterial({map:roadTexture,roughness:.92,metalness:.05});
  const sideMat = new THREE.MeshStandardMaterial({color:0x182027,roughness:1});
  const railMat = new THREE.MeshStandardMaterial({color:0xa7bacb,metalness:.8,roughness:.3});

  for(let i=0;i<ROAD_COUNT;i++){
    const group = new THREE.Group();
    group.position.z = -i * SEGMENT_LENGTH;

    const road = new THREE.Mesh(new THREE.PlaneGeometry(ROAD_WIDTH,SEGMENT_LENGTH),roadMat);
    road.rotation.x = -Math.PI/2;
    road.receiveShadow = true;
    group.add(road);

    const leftGround = new THREE.Mesh(new THREE.PlaneGeometry(45,SEGMENT_LENGTH),sideMat);
    leftGround.rotation.x = -Math.PI/2;
    leftGround.position.x = -(ROAD_WIDTH/2 + 22.5);
    leftGround.position.y = -.03;
    leftGround.receiveShadow = true;
    group.add(leftGround);

    const rightGround = leftGround.clone();
    rightGround.position.x *= -1;
    group.add(rightGround);

    [-1,1].forEach(side=>{
      const shoulder = new THREE.Mesh(
        new THREE.BoxGeometry(.45,.12,SEGMENT_LENGTH),
        new THREE.MeshStandardMaterial({color:side<0?0x4b7cff:0xff4fbd,emissive:side<0?0x183080:0x6d174c,emissiveIntensity:.65})
      );
      shoulder.position.set(side*(ROAD_WIDTH/2+.15),.06,0);
      shoulder.castShadow = false;
      group.add(shoulder);

      const rail = new THREE.Mesh(new THREE.BoxGeometry(.16,.22,SEGMENT_LENGTH),railMat);
      rail.position.set(side*(ROAD_WIDTH/2+1.25),.72,0);
      group.add(rail);

      for(let z=-SEGMENT_LENGTH/2+3;z<SEGMENT_LENGTH/2;z+=6){
        const post = new THREE.Mesh(new THREE.BoxGeometry(.13,1.35,.13),railMat);
        post.position.set(side*(ROAD_WIDTH/2+1.25),.28,z);
        group.add(post);
      }
    });

    scene.add(group);
    roadSegments.push(group);
  }
}

function createCar(color=0x29c9ff, playerCar=false){
  const group = new THREE.Group();
  const paint = new THREE.MeshPhysicalMaterial({
    color,metalness:.72,roughness:.2,clearcoat:1,clearcoatRoughness:.12
  });
  const dark = new THREE.MeshStandardMaterial({color:0x070a0f,metalness:.5,roughness:.25});
  const glass = new THREE.MeshPhysicalMaterial({color:0x17344b,metalness:.15,roughness:.05,transmission:.15,transparent:true,opacity:.85});
  const lightFront = new THREE.MeshStandardMaterial({color:0xe8ffff,emissive:0x8eefff,emissiveIntensity:4});
  const lightRear = new THREE.MeshStandardMaterial({color:0xff2b43,emissive:0xff112d,emissiveIntensity:3});

  const base = new THREE.Mesh(new THREE.BoxGeometry(2.05,.5,4.3),paint);
  base.position.y=.62;
  base.castShadow=true;
  group.add(base);

  const nose = new THREE.Mesh(new THREE.BoxGeometry(1.8,.34,1.25),paint);
  nose.position.set(0,.88,-1.55);
  nose.rotation.x=-.05;
  nose.castShadow=true;
  group.add(nose);

  const cabin = new THREE.Mesh(new THREE.BoxGeometry(1.55,.72,1.8),glass);
  cabin.position.set(0,1.16,.05);
  cabin.rotation.x=-.04;
  cabin.castShadow=true;
  group.add(cabin);

  const rear = new THREE.Mesh(new THREE.BoxGeometry(1.82,.28,.85),paint);
  rear.position.set(0,.87,1.58);
  group.add(rear);

  const wheelGeo = new THREE.CylinderGeometry(.38,.38,.32,18);
  for(const sx of [-1,1]){
    for(const sz of [-1.34,1.35]){
      const w = new THREE.Mesh(wheelGeo,dark);
      w.rotation.z=Math.PI/2;
      w.position.set(sx*1.03,.48,sz);
      w.castShadow=true;
      group.add(w);
      if(playerCar) wheels.push(w);
    }
  }

  for(const sx of [-.62,.62]){
    const f = new THREE.Mesh(new THREE.BoxGeometry(.38,.16,.08),lightFront);
    f.position.set(sx,.76,-2.18);
    group.add(f);
    const r = new THREE.Mesh(new THREE.BoxGeometry(.42,.14,.08),lightRear);
    r.position.set(sx,.78,2.17);
    group.add(r);
  }

  const spoiler = new THREE.Mesh(new THREE.BoxGeometry(1.65,.1,.35),dark);
  spoiler.position.set(0,1.13,1.85);
  group.add(spoiler);

  if(playerCar){
    const glow = new THREE.PointLight(0x30dfff,3.2,9,2);
    glow.position.set(0,.35,1.7);
    group.add(glow);

    const under = new THREE.Mesh(
      new THREE.PlaneGeometry(2.5,4.6),
      new THREE.MeshBasicMaterial({color:0x28c9ff,transparent:true,opacity:.13,blending:THREE.AdditiveBlending,depthWrite:false})
    );
    under.rotation.x=-Math.PI/2;
    under.position.y=.08;
    group.add(under);
  }
  return group;
}

function buildPlayer(){
  player = createCar(0x2ad7ff,true);
  player.position.set(0,.02,0);
  scene.add(player);
  carBody = player.children[0];
}

function buildTraffic(){
  const colors=[0xff4a65,0xffb33f,0x62dd75,0x8b68ff,0xe8edf5,0x2f5eaa,0xf05ec8];
  for(let i=0;i<TRAFFIC_COUNT;i++){
    const car=createCar(colors[i%colors.length],false);
    const lane=LANES[i%LANES.length];
    car.position.set(lane,.02,-75-i*24-Math.random()*20);
    car.userData={speedOffset:-5+Math.random()*12,hit:false,near:false,laneIndex:i%3};
    car.scale.setScalar(.92+Math.random()*.12);
    scene.add(car);
    traffic.push(car);
  }
}

function pickupMesh(type){
  const group=new THREE.Group();
  let geo,mat;
  if(type==='coin'){
    geo=new THREE.CylinderGeometry(.48,.48,.12,24);
    mat=new THREE.MeshStandardMaterial({color:0xffd86a,emissive:0x8e5c00,emissiveIntensity:1.7,metalness:.8,roughness:.2});
    const m=new THREE.Mesh(geo,mat);m.rotation.z=Math.PI/2;group.add(m);
    const ring=new THREE.Mesh(new THREE.TorusGeometry(.31,.06,8,22),new THREE.MeshBasicMaterial({color:0xffffc8}));
    ring.rotation.y=Math.PI/2;group.add(ring);
  }else if(type==='nitro'){
    geo=new THREE.OctahedronGeometry(.55,0);
    mat=new THREE.MeshStandardMaterial({color:0x55ecff,emissive:0x167ea1,emissiveIntensity:2,metalness:.4,roughness:.18});
    group.add(new THREE.Mesh(geo,mat));
  }else{
    geo=new THREE.BoxGeometry(.8,.8,.8);
    mat=new THREE.MeshStandardMaterial({color:0xff6278,emissive:0x8c1228,emissiveIntensity:1.5});
    group.add(new THREE.Mesh(geo,mat));
    const crossMat=new THREE.MeshBasicMaterial({color:0xffffff});
    const a=new THREE.Mesh(new THREE.BoxGeometry(.5,.16,.05),crossMat);
    const b=new THREE.Mesh(new THREE.BoxGeometry(.16,.5,.05),crossMat);
    a.position.z=.41;b.position.z=.41;group.add(a,b);
  }
  const glow=new THREE.PointLight(type==='coin'?0xffc34a:type==='nitro'?0x47ddff:0xff4b61,1.7,5,2);
  group.add(glow);
  return group;
}

function buildPickups(){
  for(let i=0;i<PICKUP_COUNT;i++){
    const type=i%7===5?'repair':i%4===3?'nitro':'coin';
    const p=pickupMesh(type);
    p.position.set(LANES[i%3],1.05,-55-i*19-Math.random()*16);
    p.userData={type,collected:false};
    scene.add(p);
    pickups.push(p);
  }
}

function buildScenery(){
  const treeTrunkMat=new THREE.MeshStandardMaterial({color:0x5c3a24,roughness:1});
  const leafMats=[
    new THREE.MeshStandardMaterial({color:0x164f3a,roughness:1}),
    new THREE.MeshStandardMaterial({color:0x254a62,roughness:1})
  ];
  const buildingMats=[
    new THREE.MeshStandardMaterial({color:0x263245,roughness:.8}),
    new THREE.MeshStandardMaterial({color:0x31273f,roughness:.8}),
    new THREE.MeshStandardMaterial({color:0x1f3840,roughness:.8})
  ];

  for(let i=0;i<60;i++){
    const side=i%2?-1:1;
    const group=new THREE.Group();
    const z=-20-i*10;
    const far=8.5+Math.random()*16;
    group.position.set(side*far,0,z);

    if(i%3===0){
      const h=4+Math.random()*12;
      const b=new THREE.Mesh(new THREE.BoxGeometry(3+Math.random()*3,h,3+Math.random()*4),buildingMats[i%buildingMats.length]);
      b.position.y=h/2;
      b.castShadow=state.quality==='high';
      b.receiveShadow=true;
      group.add(b);

      const windows=new THREE.MeshBasicMaterial({color:i%2?0x70e8ff:0xff6bcc});
      for(let y=1.5;y<h-1;y+=1.7){
        const w=new THREE.Mesh(new THREE.PlaneGeometry(1.5,.42),windows);
        w.position.set(-side*(1.55+Math.random()*.6),y,0);
        w.rotation.y=side>0?-Math.PI/2:Math.PI/2;
        group.add(w);
      }
    }else{
      const trunk=new THREE.Mesh(new THREE.CylinderGeometry(.18,.26,2,7),treeTrunkMat);
      trunk.position.y=1;
      const crown=new THREE.Mesh(new THREE.ConeGeometry(1.4+Math.random()*.5,3.2+Math.random(),8),leafMats[i%2]);
      crown.position.y=3.1;
      trunk.castShadow=crown.castShadow=state.quality==='high';
      group.add(trunk,crown);
    }
    scene.add(group);
    scenery.push(group);
  }
}

function buildSkyObjects(){
  const moon=new THREE.Mesh(
    new THREE.SphereGeometry(5.2,24,24),
    new THREE.MeshBasicMaterial({color:0xf7e8d3})
  );
  moon.position.set(-48,45,-180);
  scene.add(moon);

  const mountainMat=new THREE.MeshStandardMaterial({color:0x19233a,roughness:1});
  for(let i=0;i<16;i++){
    const m=new THREE.Mesh(new THREE.ConeGeometry(15+Math.random()*18,28+Math.random()*25,5),mountainMat);
    m.position.set(-110+i*15,-2,-240-Math.random()*45);
    m.rotation.y=Math.random()*Math.PI;
    scene.add(m);
  }

  const starGeo=new THREE.BufferGeometry();
  const positions=[];
  for(let i=0;i<500;i++){
    positions.push((Math.random()-.5)*500,25+Math.random()*150,-50-Math.random()*500);
  }
  starGeo.setAttribute('position',new THREE.Float32BufferAttribute(positions,3));
  const stars=new THREE.Points(starGeo,new THREE.PointsMaterial({color:0xbfdcff,size:.55,sizeAttenuation:true}));
  scene.add(stars);
}

function resetGame(){
  state.mode='playing';
  state.score=0; state.distance=0; state.coins=0; state.health=100; state.nitro=100;
  state.speed=28; state.targetSpeed=38; state.steer=0; state.targetSteer=0;
  state.brake=false; state.nitroOn=false; state.combo=1; state.comboTimer=0; state.elapsed=0; state.shake=0;

  player.position.set(0,.02,0);
  player.rotation.set(0,0,0);

  traffic.forEach((car,i)=>{
    car.position.set(LANES[i%3],.02,-70-i*26-Math.random()*16);
    car.userData.hit=false;car.userData.near=false;
  });
  pickups.forEach((p,i)=>{
    p.position.set(LANES[i%3],1.05,-45-i*20-Math.random()*15);
    p.visible=true;p.userData.collected=false;
  });

  ui.menu.classList.add('hidden');
  ui.gameOver.classList.add('hidden');
  ui.pause.classList.add('hidden');
  startAudio();
  showToast('Engine online');
  updateHUD();
}

function gameOver(){
  if(state.mode!=='playing')return;
  state.mode='gameover';
  state.nitroOn=false;
  explode(player.position.x,.8,0,0xff5f75,55);
  hitSound(true);

  state.totalCoins += state.coins;
  state.best=Math.max(state.best,Math.floor(state.score));
  localStorage.setItem('apexBest',String(state.best));
  localStorage.setItem('apexTotalCoins',String(state.totalCoins));

  ui.finalScore.textContent=Math.floor(state.score).toLocaleString();
  ui.finalDistance.textContent=state.distance.toFixed(2)+' KM';
  ui.finalCoins.textContent=state.coins;
  ui.finalBest.textContent=state.best.toLocaleString();
  ui.best.textContent=state.best.toLocaleString();
  ui.coins.textContent=state.totalCoins.toLocaleString();

  fetch('?api=score',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({score:Math.floor(state.score),coins:state.coins})
  }).catch(()=>{});

  setTimeout(()=>ui.gameOver.classList.remove('hidden'),520);
}

function togglePause(){
  if(state.mode==='playing'){
    state.mode='paused';
    ui.pause.classList.remove('hidden');
    if(audioCtx)audioCtx.suspend();
  }else if(state.mode==='paused'){
    state.mode='playing';
    ui.pause.classList.add('hidden');
    if(audioCtx)audioCtx.resume();
  }
}

function update(dt){
  state.worldTime+=dt;
  updateParticles(dt);

  if(state.mode!=='playing'){
    updateAmbient(dt);
    return;
  }

  state.elapsed+=dt;
  const keyboardSteer=(keyState['ArrowLeft']||keyState['a']?-1:0)+(keyState['ArrowRight']||keyState['d']?1:0);
  if(keyboardSteer!==0)state.targetSteer=keyboardSteer;

  const braking=state.brake||keyState['ArrowDown']||keyState['s'];
  const nitroRequested=(state.nitroOn||keyState[' ']||keyState['Shift'])&&state.nitro>0&&!braking;

  state.targetSpeed=Math.min(72,38+state.elapsed*.34);
  if(braking)state.targetSpeed=20;
  if(nitroRequested){
    state.targetSpeed=Math.min(96,70+state.elapsed*.2);
    state.nitro=Math.max(0,state.nitro-31*dt);
    ui.speedLines.classList.add('active');
  }else{
    state.nitro=Math.min(100,state.nitro+7.2*dt);
    ui.speedLines.classList.remove('active');
  }

  state.speed=THREE.MathUtils.lerp(state.speed,state.targetSpeed,1-Math.pow(.004,dt));
  state.steer=THREE.MathUtils.lerp(state.steer,state.targetSteer,1-Math.pow(.0008,dt));
  if(!keyboardSteer&&!isPointerSteering)state.targetSteer=THREE.MathUtils.lerp(state.targetSteer,0,1-Math.pow(.02,dt));

  player.position.x+=state.steer*(4.3+state.speed*.035)*dt;
  player.position.x=THREE.MathUtils.clamp(player.position.x,-5.35,5.35);
  player.rotation.z=THREE.MathUtils.lerp(player.rotation.z,-state.steer*.16,1-Math.pow(.002,dt));
  player.rotation.y=THREE.MathUtils.lerp(player.rotation.y,-state.steer*.08,1-Math.pow(.005,dt));
  carBody.position.y=.62+Math.sin(state.elapsed*15)*.012;

  wheels.forEach(w=>w.rotation.x-=state.speed*dt*1.9);

  const worldMove=state.speed*dt;
  state.distance+=state.speed*dt/1000;
  state.score+=state.speed*dt*(1.15+state.combo*.16);

  moveRoad(worldMove);
  updateTraffic(worldMove,dt);
  updatePickups(worldMove,dt);
  updateScenery(worldMove);
  updateCamera(dt,nitroRequested);
  updateCombo(dt);
  updateAmbient(dt);
  updateHUD();

  if(state.health<=0)gameOver();
}

function moveRoad(move){
  roadSegments.forEach(seg=>{
    seg.position.z+=move;
    if(seg.position.z>SEGMENT_LENGTH){
      let min=Math.min(...roadSegments.map(s=>s.position.z));
      seg.position.z=min-SEGMENT_LENGTH;
    }
  });
  roadTexture.offset.y-=move/SEGMENT_LENGTH;
}

function updateTraffic(move,dt){
  traffic.forEach((car,index)=>{
    car.position.z+=move-car.userData.speedOffset*dt;
    car.children.forEach(c=>{
      if(c.geometry&&c.geometry.type==='CylinderGeometry')c.rotation.x-=state.speed*dt;
    });

    if(car.position.z>18){
      const minZ=Math.min(...traffic.map(t=>t.position.z));
      car.position.z=minZ-28-Math.random()*34;
      car.position.x=LANES[Math.floor(Math.random()*LANES.length)];
      car.userData.hit=false;car.userData.near=false;
    }

    const dz=Math.abs(car.position.z-player.position.z);
    const dx=Math.abs(car.position.x-player.position.x);

    if(dz<2.8&&dx<1.55&&!car.userData.hit&&performance.now()-state.lastHit>850){
      car.userData.hit=true;
      state.lastHit=performance.now();
      state.health=Math.max(0,state.health-34);
      state.speed*=.56;
      state.combo=1;
      state.comboTimer=0;
      state.shake=1;
      explode((car.position.x+player.position.x)/2,.9,.1,0xff4861,30);
      hitSound(false);
      showToast('Heavy impact -34 health');
      car.position.x=THREE.MathUtils.clamp(car.position.x+(car.position.x>player.position.x?2.3:-2.3),-5.2,5.2);
    }else if(car.position.z>1.9&&car.position.z<3.8&&dx<2.6&&dx>1.55&&!car.userData.near){
      car.userData.near=true;
      state.combo=Math.min(8,state.combo+1);
      state.comboTimer=3.2;
      const bonus=150*state.combo;
      state.score+=bonus;
      comboSound();
      showToast(`Near miss +${bonus}`);
    }
  });
}

function updatePickups(move,dt){
  pickups.forEach(p=>{
    p.position.z+=move;
    p.rotation.y+=dt*2.8;
    p.position.y=1.05+Math.sin(state.worldTime*4+p.position.z)*.12;

    if(p.position.z>16){
      const minZ=Math.min(...pickups.map(o=>o.position.z));
      p.position.z=minZ-18-Math.random()*22;
      p.position.x=LANES[Math.floor(Math.random()*LANES.length)];
      p.visible=true;p.userData.collected=false;
      const roll=Math.random();
      p.userData.type=roll<.68?'coin':roll<.88?'nitro':'repair';
    }

    if(!p.userData.collected&&p.visible&&Math.abs(p.position.z)<2.2&&Math.abs(p.position.x-player.position.x)<1.4){
      p.userData.collected=true;p.visible=false;
      if(p.userData.type==='coin'){
        state.coins++;state.score+=220*state.combo;pickupSound(760);
        showToast(`Coin collected +${220*state.combo}`);
      }else if(p.userData.type==='nitro'){
        state.nitro=Math.min(100,state.nitro+42);state.score+=160;pickupSound(950);
        showToast('Nitro cell +42');
      }else{
        state.health=Math.min(100,state.health+28);state.score+=120;pickupSound(620);
        showToast('Repair kit +28 health');
      }
      explode(p.position.x,1,p.position.z,p.userData.type==='coin'?0xffd86a:p.userData.type==='nitro'?0x55ecff:0xff6078,18);
    }
  });
}

function updateScenery(move){
  scenery.forEach(s=>{
    s.position.z+=move;
    if(s.position.z>28){
      const min=Math.min(...scenery.map(o=>o.position.z));
      s.position.z=min-10-Math.random()*7;
      const side=Math.random()<.5?-1:1;
      s.position.x=side*(9+Math.random()*18);
    }
  });
}

function updateCamera(dt,nitro){
  const lookX=player.position.x*.32;
  const shake=state.shake;
  state.shake=Math.max(0,state.shake-dt*2.3);

  const targetX=player.position.x*.55-state.steer*.45+(Math.random()-.5)*shake*.35;
  const targetY=4.8+(nitro?.2:0)+(Math.random()-.5)*shake*.22;
  const targetZ=10.7+(nitro?1.1:0);

  camera.position.x=THREE.MathUtils.lerp(camera.position.x,targetX,1-Math.pow(.006,dt));
  camera.position.y=THREE.MathUtils.lerp(camera.position.y,targetY,1-Math.pow(.01,dt));
  camera.position.z=THREE.MathUtils.lerp(camera.position.z,targetZ,1-Math.pow(.01,dt));
  camera.lookAt(lookX,1,-9);

  camera.fov=THREE.MathUtils.lerp(camera.fov,nitro?70:62,1-Math.pow(.008,dt));
  camera.updateProjectionMatrix();
}

function updateCombo(dt){
  if(state.comboTimer>0){
    state.comboTimer-=dt;
    ui.combo.classList.add('show');
    ui.comboValue.textContent=state.combo+'X';
  }else{
    state.combo=1;
    ui.combo.classList.remove('show');
  }
}

function updateAmbient(){
  const cycle=(state.worldTime*.012)%1;
  const dusk=Math.sin(cycle*Math.PI*2)*.5+.5;
  const bg=new THREE.Color().lerpColors(new THREE.Color(0x07111e),new THREE.Color(0x142749),dusk*.42);
  scene.background.copy(bg);
  scene.fog.color.copy(bg);
  hemi.intensity=.9+dusk*.7;
  sun.intensity=1.6+dusk*1.3;
  sun.position.x=Math.sin(cycle*Math.PI*2)*30;
}

function explode(x,y,z,color,count){
  const mat=new THREE.MeshBasicMaterial({color,transparent:true,opacity:1,blending:THREE.AdditiveBlending});
  for(let i=0;i<count;i++){
    const size=.05+Math.random()*.12;
    const mesh=new THREE.Mesh(new THREE.BoxGeometry(size,size,size),mat.clone());
    mesh.position.set(x,y,z);
    mesh.userData={
      velocity:new THREE.Vector3((Math.random()-.5)*7,Math.random()*6,(Math.random()-.5)*7),
      life:.45+Math.random()*.7
    };
    scene.add(mesh);particles.push(mesh);
  }
}

function updateParticles(dt){
  for(let i=particles.length-1;i>=0;i--){
    const p=particles[i];
    p.userData.life-=dt;
    p.userData.velocity.y-=7.8*dt;
    p.position.addScaledVector(p.userData.velocity,dt);
    p.material.opacity=Math.max(0,p.userData.life*1.5);
    p.rotation.x+=dt*4;p.rotation.y+=dt*5;
    if(p.userData.life<=0){
      scene.remove(p);p.geometry.dispose();p.material.dispose();particles.splice(i,1);
    }
  }
}

function render(){
  renderer.render(scene,camera);
}

function animate(){
  requestAnimationFrame(animate);
  const dt=Math.min(.033,clock.getDelta()||.016);
  update(dt);
  render();
  updateEngineSound();
}

function updateHUD(){
  ui.score.textContent=Math.floor(state.score).toLocaleString();
  ui.distance.textContent=state.distance.toFixed(2);
  ui.speed.textContent=Math.round(state.speed*3.6);
  ui.healthFill.style.width=state.health+'%';
  ui.healthText.textContent=Math.round(state.health);
  ui.nitroFill.style.width=state.nitro+'%';
  ui.nitroText.textContent=Math.round(state.nitro);
}

function showToast(text){
  ui.toast.textContent=text;
  ui.toast.classList.add('show');
  clearTimeout(showToast.timer);
  showToast.timer=setTimeout(()=>ui.toast.classList.remove('show'),1250);
}

function createSpeedLines(){
  for(let i=0;i<18;i++){
    const l=document.createElement('i');
    l.className='speed-line';
    const angle=Math.random()*360;
    const radius=10+Math.random()*35;
    l.style.transform=`rotate(${angle}deg) translateX(${radius}vw)`;
    l.style.width=(10+Math.random()*24)+'vw';
    l.style.opacity=.15+Math.random()*.35;
    ui.speedLines.appendChild(l);
  }
}

function startAudio(){
  if(!state.sound)return;
  if(!audioCtx)audioCtx=new (window.AudioContext||window.webkitAudioContext)();
  if(audioCtx.state==='suspended')audioCtx.resume();
  if(!engineOsc){
    engineOsc=audioCtx.createOscillator();
    engineGain=audioCtx.createGain();
    const filter=audioCtx.createBiquadFilter();
    filter.type='lowpass';filter.frequency.value=380;
    engineOsc.type='sawtooth';engineOsc.frequency.value=55;
    engineGain.gain.value=.025;
    engineOsc.connect(filter).connect(engineGain).connect(audioCtx.destination);
    engineOsc.start();
  }
}

function updateEngineSound(){
  if(!audioCtx||!engineOsc||!engineGain)return;
  const active=state.sound&&state.mode==='playing';
  engineGain.gain.setTargetAtTime(active ? .018 + .00022 * state.speed : 0, audioCtx.currentTime, .08);
  engineOsc.frequency.setTargetAtTime(45+state.speed*2.1,audioCtx.currentTime,.07);
}

function tone(freq,duration=.12,type='sine',gain=.08,slide=0){
  if(!state.sound)return;
  startAudio();
  const o=audioCtx.createOscillator(),g=audioCtx.createGain();
  o.type=type;o.frequency.setValueAtTime(freq,audioCtx.currentTime);
  if(slide)o.frequency.exponentialRampToValueAtTime(Math.max(20,freq+slide),audioCtx.currentTime+duration);
  g.gain.setValueAtTime(gain,audioCtx.currentTime);
  g.gain.exponentialRampToValueAtTime(.0001,audioCtx.currentTime+duration);
  o.connect(g).connect(audioCtx.destination);o.start();o.stop(audioCtx.currentTime+duration);
}

function pickupSound(freq){tone(freq,.13,'triangle',.09,260)}
function comboSound(){tone(520+state.combo*55,.12,'sine',.07,180)}
function hitSound(fatal){
  tone(fatal ? 70 : 95, fatal ? .7 : .35, 'sawtooth', .17, fatal ? -45 : -35);
  tone(fatal?180:240,.18,'square',.07,-120);
}

function bindEvents(){
function bindHold(el,onStart,onEnd){
  const start=e=>{e.preventDefault();el.classList.add('pressed');onStart();};
  const end=e=>{if(e)e.preventDefault();el.classList.remove('pressed');onEnd();};
  el.addEventListener('pointerdown',start);
  ['pointerup','pointercancel','pointerleave'].forEach(ev=>el.addEventListener(ev,end));
}

bindHold(ui.left,()=>{isPointerSteering=true;state.targetSteer=-1},()=>{isPointerSteering=false;state.targetSteer=0});
bindHold(ui.right,()=>{isPointerSteering=true;state.targetSteer=1},()=>{isPointerSteering=false;state.targetSteer=0});
bindHold(ui.brake,()=>state.brake=true,()=>state.brake=false);
bindHold(ui.nitro,()=>state.nitroOn=true,()=>state.nitroOn=false);

ui.start.addEventListener('click',resetGame);
ui.restart.addEventListener('click',resetGame);
ui.resume.addEventListener('click',togglePause);
ui.pauseBtn.addEventListener('click',togglePause);
ui.home.addEventListener('click',()=>{
  ui.gameOver.classList.add('hidden');
  ui.menu.classList.remove('hidden');
  state.mode='menu';
});
ui.sound.addEventListener('click',()=>{
  state.sound=!state.sound;
  localStorage.setItem('apexSound',state.sound?'on':'off');
  ui.sound.textContent=state.sound?'SOUND: ON':'SOUND: OFF';
  if(state.sound)startAudio();
});
ui.quality.addEventListener('click',()=>{
  state.quality=state.quality==='high'?'low':'high';
  localStorage.setItem('apexQuality',state.quality);
  ui.quality.textContent=state.quality==='high'?'HQ':'LQ';
  renderer.setPixelRatio(Math.min(devicePixelRatio,state.quality==='high'?1.75:1.05));
  renderer.shadowMap.enabled=state.quality==='high';
  showToast(state.quality==='high'?'High graphics enabled':'Performance mode enabled');
});
ui.how.addEventListener('click',()=>showToast('Steer, brake, collect items and hold NITRO'));
ui.share.addEventListener('click',async()=>{
  const text=`I scored ${Math.floor(state.score).toLocaleString()} in Apex Rush 3D!`;
  try{await navigator.clipboard.writeText(text);showToast('Score copied');}
  catch(e){showToast(text);}
});

window.addEventListener('keydown',e=>{
  keyState[e.key]=true;
  if(e.key==='Escape')togglePause();
  if((e.key==='Enter'||e.key===' ')&&state.mode==='menu')resetGame();
});
window.addEventListener('keyup',e=>keyState[e.key]=false);

renderer.domElement.addEventListener('pointerdown',e=>swipeStart=e.clientX);
renderer.domElement.addEventListener('pointermove',e=>{
  if(swipeStart===null||state.mode!=='playing')return;
  const dx=e.clientX-swipeStart;
  state.targetSteer=THREE.MathUtils.clamp(dx/70,-1,1);
});
renderer.domElement.addEventListener('pointerup',()=>{swipeStart=null;if(!isPointerSteering)state.targetSteer=0});
renderer.domElement.addEventListener('pointercancel',()=>{swipeStart=null;if(!isPointerSteering)state.targetSteer=0});

window.addEventListener('resize',()=>{
  camera.aspect=innerWidth/innerHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(innerWidth,innerHeight);
  renderer.setPixelRatio(Math.min(devicePixelRatio,state.quality==='high'?1.75:1.05));
});

document.addEventListener('visibilitychange',()=>{
  if(document.hidden&&state.mode==='playing')togglePause();
});
}
})();
</script>
</body>
</html>
