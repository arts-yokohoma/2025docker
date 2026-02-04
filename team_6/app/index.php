<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db/connect.php';
if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

// 1. Real-time Status Logic (10:00 - 22:00)
date_default_timezone_set('Asia/Tokyo');
$currentHour = (int)date('H');
$isOpen = ($currentHour >= 10 && $currentHour < 22);

$menuItems = $db->query("SELECT * FROM menu_items ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🍕 ピザマッハ | PIZZA MASH</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    :root {
        --bg-color: #fffafa; /* Very light Sakura white */
        --card-bg: #ffffff;
        --accent-red: #e63946; 
        --sakura-pink: #fff0f0; 
        --status-blue: #3498db; 
        --status-red: #d63031;
        --text-dark: #2d3436;
        --shadow-soft: 0 8px 30px rgba(0,0,0,0.05);
    }

    body { 
        margin: 0; 
        font-family: 'Helvetica Neue', 'Arial', 'Hiragino Kaku Gothic ProN', sans-serif;
        background-color: var(--bg-color); 
        color: var(--text-dark); 
    }

    /* --- MODERN JAPANESE THEME HEADER --- */
    .header-wrapper {
        background: var(--sakura-pink); 
        padding: 15px 4%;
        display: flex; justify-content: space-between; align-items: center;
        position: sticky; top: 0; z-index: 1000;
        border-bottom: 3px solid var(--accent-red);
        box-shadow: 0 4px 15px rgba(230, 57, 70, 0.1);
    }
    
    .logo-section { display: flex; align-items: center; gap: 15px; }
    .logo-left { width: 50px; height: 50px; border-radius: 50%; background: var(--accent-red); padding: 5px; }
    .brand-name h1 { margin: 0; font-size: 1.6rem; color: var(--accent-red); font-weight: 900; }
    .brand-name p { margin: 0; font-size: 0.7rem; color: #636e72; font-weight: bold; }

    /* --- Status Buttons (Color Logic Restored) --- */
    .status-container { display: flex; gap: 10px; }
    .status-btn {
        padding: 8px 18px; border-radius: 50px; font-size: 0.85rem;
        font-weight: 800; display: flex; align-items: center; gap: 8px; color: #2d3436;
        background: #fff;
        border: 2px solid #fab1a0;
        box-shadow: 0 3px 0px #fab1a0;
    }
    
    /* Icon color logic based on $isOpen */
    .icon-clock-status { 
        font-size: 1.1rem;
        color: <?php echo $isOpen ? 'var(--status-blue)' : 'var(--status-red)'; ?>;
        text-shadow: 0 0 5px <?php echo $isOpen ? 'rgba(52,152,219,0.3)' : 'rgba(214,48,49,0.3)'; ?>;
    }

    .status-cash { background: var(--accent-red); color: white; border: none; box-shadow: 0 3px 0px #c0392b; }

    /* --- Navigation --- */
    .nav-links { display: flex; gap: 5px; }
    .nav-item { 
        color: var(--text-dark); text-decoration: none; padding: 10px 15px; border-radius: 12px;
        font-size: 0.95rem; font-weight: 900; transition: 0.3s; display: flex; align-items: center; gap: 6px;
    }
    .nav-item:hover { background: #ffebeb; color: var(--accent-red); }

  /* 2. New Styles for Video System */
    .hero-wrapper {
        background: rgba(245, 242, 148, 1);
        padding-top: 5px;
    }

   .video-topic-nav {
        display: flex; justify-content: center; gap: 10px;
        padding: 10px; background: #fff;
    }
    .topic-btn {
        background: #f8f9fa; border: 1px solid #ddd; padding: 6px 12px;
        border-radius: 20px; font-size: 0.8rem; cursor: pointer; font-weight: bold;
    }
    .topic-btn.active {
        border-color: #e63946; color: #e63946; background: #fff0f0;
    }
   .video-viewport {
        width: 100%; height: 250px; /* The "Cut" size */
        background: #000; position: relative; overflow: hidden;
    }
    #promo-video {
        width: 100%; height: 100%;
        object-fit: cover; /* This makes the video fill the small height by zooming in */
        transition: opacity 0.3s;
    }
    #video-loader-bar {
        position: absolute; bottom: 0; left: 0; height: 4px;
        background: #e63946; width: 0%; z-index: 20;
        transition: width 0.1s linear;
    }


    /* Video Zoom Button */
.video-viewport { position: relative; }
.video-fullscreen-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(0,0,0,0.5);
    color: white;
    border: none;
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
    z-index: 30;
    transition: 0.3s;
}
.video-fullscreen-btn:hover { background: var(--accent-red); transform: scale(1.1); }

/* Image Lightbox (Modal) */
.image-modal {
    display: none;
    position: fixed;
    z-index: 2000;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.9);
    align-items: center;
    justify-content: center;
    cursor: zoom-out;
}
.modal-content {
    max-width: 90% ;
    max-height: 80vh;
    border-radius: 20px;
    border: 5px solid white;
    box-shadow: 0 0 30px rgba(0,0,0,0.5);
}
.close-modal {
    position: absolute;
    top: 20px; right: 30px;
    color: white; font-size: 40px; font-weight: bold; cursor: pointer;
}

/* Hover effect for menu images */
.menu-img { cursor: zoom-in; transition: transform 0.3s; }
.menu-img:hover { filter: brightness(0.9); }

    .menu-container { display: flex; flex-wrap: wrap; gap: 30px; padding: 40px 5%; }
    .menu-grid { flex: 1; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; }
    .menu-item { background: var(--card-bg); border-radius: 25px; padding: 25px; box-shadow: var(--shadow-soft); transition: 0.3s; border: 1px solid #fdf0f0; }
    .menu-item:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(230,57,70,0.1); }
    .menu-img { width: 100%; border-radius: 20px; height: 190px; object-fit: cover; }

    .size-btn { flex: 1; padding: 12px; border: 2px solid #eee; border-radius: 12px; background: white; cursor: pointer; font-weight: bold; }
    .size-btn.active { background: var(--accent-red); color: white; border-color: var(--accent-red); }

    .add-cart-btn { flex: 1; background: #2d3436; color: white; border: none; padding: 16px; border-radius: 15px; font-weight: bold; cursor: pointer; transition: 0.3s; }
    .add-cart-btn:hover { background: var(--accent-red); }

    .cart-box { flex: 0 0 350px; background: white; padding: 30px; border-radius: 30px; box-shadow: var(--shadow-soft); height: fit-content; position: sticky; top: 110px; border: 2px solid var(--sakura-pink); }
    #basket-badge-inner { background: var(--accent-red); color: white; font-size: 0.8rem; padding: 2px 10px; border-radius: 50px; display: none; margin-left: 8px; }
    
    #checkout-btn { width: 100%; background: var(--accent-red); color: white; border: none; padding: 20px; border-radius: 18px; font-weight: 900; cursor: pointer; transition: 0.3s; margin-top: 20px; font-size: 1.1rem; }
    #checkout-btn:hover { background: #c0392b; transform: scale(1.02); }
    #cart-items button:hover {transform: scale(1.2);transition: 0.2s;}

    @media (max-width: 1000px) { .header-wrapper { flex-direction: column; gap: 15px; text-align: center; } .cart-box { position: static; } }
</style>
</head>
<body>

<header class="header-wrapper">
  <div class="logo-section">
    <img src="assets/images/logo.png" alt="Logo" class="logo-left">
    <div class="brand-name">
      <h1>ピザマッハ</h1>
      <p>PIZZA MACH - 職人仕込みの味</p>
    </div>
  </div>

  <div class="status-container">
    <div class="status-btn">
        <i class="fas fa-clock icon-clock-status"></i>営業時間 10:00 - 22:00
    </div>
    <div class="status-btn status-cash">
        <i class="fas fa-yen-sign" style="color:#ffeaa7"></i>   現金のみ
    </div>
     <div class="status-btn delivery">
        <i class="fas fa-bolt" style="color:#27ae60"></i> 
    30分以内にお届け
</div>
  </div>

  <nav class="nav-links">
    <a href="index.php" class="nav-item"><i class="fas fa-home"></i> ホーム</a>
    <a href="#menu-start" class="nav-item"><i class="fas fa-utensils"></i> メニュー</a>
    <a href="order_status.php" class="nav-item"><i class="fas fa-shipping-fast"></i> 注文状況</a>
    <a href="contact.php" class="nav-item"><i class="fas fa-phone-alt"></i> お問い合わせ</a>
  </nav>
</header>

<section class="hero-wrapper">
    <div class="video-topic-nav">
        <button class="topic-btn" data-index="1">
            <i class="fas fa-map-marker-alt"></i> 店舗詳細
        </button>
        <button class="topic-btn active" data-index="0">
            <i class="fas fa-list-ol"></i> ご注文の流れ
        </button>
        <button class="topic-btn" data-index="2">
            <i class="fas fa-star"></i> 新着メニュー
        </button>
    </div>

<div class="video-viewport" id="video-container">
    <video id="promo-video" autoplay muted playsinline>
        <source id="video-source" src="assets/videos/v1.mov" type="video/mp4">
    </video>
    <button class="video-fullscreen-btn" onclick="toggleFullscreen()">
        <i class="fas fa-expand"></i>
    </button>
    <div id="video-loader-bar"></div>
</div>
</section>

<div id="menu-start"></div>

<main class="menu-container">
  <section class="menu-grid">
    <?php foreach ($menuItems as $item): ?>
      <div class="menu-item">
        <img src="assets/images/<?php echo htmlspecialchars($item['image']); ?>" 
     class="menu-img" 
     onclick="openImage(this.src)">
        <h3 style="margin: 15px 0 10px 0; font-size: 1.4rem;"><?php echo htmlspecialchars($item['name']); ?></h3>
        
        <?php if ($item['is_sold_out']): ?>
          <p style="color: #e74c3c; font-weight: bold; text-align: center; background: #fee; padding: 15px; border-radius: 15px;">本日分は終了しました</p>
        <?php else: ?>
          <div class="size-selector" style="display: flex; gap: 8px; margin-bottom: 20px;">
            <button class="size-btn active" data-size="S" data-price="<?php echo $item['price_s']; ?>" onclick="changeSize(this)">S ¥<?php echo number_format($item['price_s']); ?></button>
            <button class="size-btn" data-size="M" data-price="<?php echo $item['price_m']; ?>" onclick="changeSize(this)">M ¥<?php echo number_format($item['price_m']); ?></button>
            <button class="size-btn" data-size="L" data-price="<?php echo $item['price_l']; ?>" onclick="changeSize(this)">L ¥<?php echo number_format($item['price_l']); ?></button>
          </div>
          <div class="qty-row" style="display: flex; gap: 10px;">
            <div style="display: flex; align-items: center; border: 2px solid #eee; border-radius: 12px; overflow: hidden; background: #fff;">
                <button onclick="updateQtyDisplay(this, -1)" style="padding: 10px 15px; border: none; cursor: pointer; font-weight:bold;">-</button>
                <input type="text" class="pizza-qty-display" value="1" readonly style="width: 35px; text-align: center; border: none; font-weight: bold; font-size: 1.1rem;">
                <button onclick="updateQtyDisplay(this, 1)" style="padding: 10px 15px; border: none; cursor: pointer; font-weight:bold;">+</button>
            </div>
            <button class="add-cart-btn" data-id="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>" onclick="addToCart(this)">追加</button>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </section>

  <aside class="cart-box">
    <h2 style="margin: 0 0 20px 0; font-size: 1.3rem; border-bottom: 3px solid var(--accent-red); padding-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
        <span>🛒 ご注文内容</span>
        <span id="basket-badge-inner">0</span>
    </h2>
    <ul id="cart-items" style="list-style: none; padding: 0;">
        <p style="color: #aaa; text-align: center; padding: 30px 0;">カートは空です</p>
    </ul>
    <div style="display: flex; justify-content: space-between; font-size: 1.4rem; font-weight: 900; margin-top: 25px; border-top: 2px dashed #eee; pt: 20px; padding-top: 15px;">
        <span>合計金額</span>
        <span style="color: var(--accent-red);">¥<span id="cart-total">0</span></span>
    </div>
    <button id="checkout-btn" onclick="goCheckout()">注文手続きへ進む <i class="fas fa-chevron-right"></i></button>
  </aside>
</main>

<script>
let cart = [];
const video = document.getElementById('promo-video');

window.addEventListener('scroll', () => {
    const menuPos = document.getElementById('menu-start').getBoundingClientRect().top;
    if (menuPos < 100) video.pause(); else video.play();
});

function changeSize(btn) {
    btn.parentElement.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

function updateQtyDisplay(btn, delta) {
    const input = btn.parentElement.querySelector('.pizza-qty-display');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    input.value = val;
}

function addToCart(btn) {
    const parent = btn.closest('.menu-item');
    const activeSizeBtn = parent.querySelector('.size-btn.active');
    const qty = parseInt(parent.querySelector('.pizza-qty-display').value);
    
    const item = {
        id: btn.dataset.id,
        name: btn.dataset.name,
        size: activeSizeBtn.dataset.size,
        price: parseInt(activeSizeBtn.dataset.price),
        qty: qty
    };

    cart.push(item);
    renderCart();
    updateBadge();

    btn.innerText = "OK!";
    btn.style.background = "#2ecc71";
    setTimeout(() => { 
        btn.innerText = "追加"; 
        btn.style.background = "#2d3436";
    }, 800);
}

function renderCart() {
    const list = document.getElementById('cart-items');
    const totalDisp = document.getElementById('cart-total');
    list.innerHTML = '';
    let total = 0;

    if(cart.length === 0) {
        list.innerHTML = '<p style="color: #aaa; text-align: center; padding: 30px 0;">まだ何も入っていません</p>';
    }

    // We use the index 'i' to know exactly which item to remove
    cart.forEach((item, i) => {
        total += (item.price * item.qty);
        const li = document.createElement('li');
        li.style.cssText = "display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-bottom:1px solid #f9f9f9; font-size:0.95rem; font-weight:bold;";
        
        li.innerHTML = `
            <div style="display:flex; align-items:center; gap:10px;">
                <button onclick="removeFromCart(${i})" style="background:none; border:none; color:#e63946; cursor:pointer; padding:5px;">
                    <i class="fas fa-minus-circle"></i>
                </button>
                <span>${item.name} (${item.size}×${item.qty})</span>
            </div>
            <span>¥${(item.price * item.qty).toLocaleString()}</span>
        `;
        list.appendChild(li);
    });
    totalDisp.innerText = total.toLocaleString();
}

function removeFromCart(index) {
    // Remove 1 item at the specific index
    cart.splice(index, 1);
    
    // Refresh the UI
    renderCart();
    updateBadge();
}

function updateBadge() {
    const badgeInner = document.getElementById('basket-badge-inner');
    if (cart.length > 0) {
        badgeInner.style.display = 'inline-block';
        badgeInner.innerText = cart.length;
    } else {
        badgeInner.style.display = 'none';
    }
}

function goCheckout() {
    if (cart.length === 0) { alert("カートが空です"); return; }
    const form = document.createElement('form');
    form.method = 'POST'; form.action = 'order_confirm.php';
    const input = document.createElement('input');
    input.type = 'hidden'; input.name = 'cart_data';
    input.value = JSON.stringify(cart);
    form.appendChild(input); document.body.appendChild(form);
    form.submit();
}



// 3. Script for Automatic & Manual Switching
// Make sure these filenames match your actual assets
const videoPlaylist = [
    'assets/videos/v1.mov',
    'assets/videos/v2.mp4',
];

let currentIdx = 0;
const player = document.getElementById('promo-video');
const source = document.getElementById('video-source');
const btns = document.querySelectorAll('.topic-btn');
const loader = document.getElementById('video-loader-bar');

function changeToVideo(index) {
    // Ensure index is always valid (Looping back to 0 if at the end)
    currentIdx = (index + videoPlaylist.length) % videoPlaylist.length;
    
    // UI Update for buttons
    btns.forEach((b, i) => b.classList.toggle('active', i === currentIdx));

    // Change source and load
    source.src = videoPlaylist[currentIdx];
    player.load(); 

    // Attempt to play
    player.play().catch(error => {
        console.warn("Autoplay blocked or file missing at index: " + currentIdx);
        // If play fails, we wait 1 second and try the next one
        setTimeout(playNext, 1000); 
    });
}

function playNext() {
    changeToVideo(currentIdx + 1);
}

// SUCCESS: When video finishes playing naturally
player.addEventListener('ended', () => {
    console.log("Finished. Moving to next.");
    playNext();
});

// ERROR: When file is NOT FOUND or cannot load
player.addEventListener('error', (e) => {
    console.error("Video Error: File likely missing. Skipping...");
    playNext();
});

// Update the progress bar
player.addEventListener('timeupdate', () => {
    if (player.duration) {
        const progress = (player.currentTime / player.duration) * 100;
        loader.style.width = progress + '%';
    }
});

// Manual Clicks
btns.forEach((btn, i) => {
    btn.onclick = () => changeToVideo(i);
});

// Start the first video on page load
window.onload = () => {
    changeToVideo(0);
};



// --- Feature 1: Video Fullscreen ---
function toggleFullscreen() {
    const video = document.getElementById('promo-video');
    
    if (!document.fullscreenElement) {
        if (video.requestFullscreen) {
            video.requestFullscreen();
        } else if (video.webkitRequestFullscreen) { /* Safari */
            video.webkitRequestFullscreen();
        } else if (video.msRequestFullscreen) { /* IE11 */
            video.msRequestFullscreen();
        }
    } else {
        document.exitFullscreen();
    }
}

// --- Feature 2: Image Lightbox ---
function openImage(src) {
    const modal = document.getElementById('imgModal');
    const modalImg = document.getElementById('fullImage');
    modal.style.display = "flex";
    modalImg.src = src;
    // Stop body scrolling while looking at image
    document.body.style.overflow = "hidden";
}

function closeImage() {
    document.getElementById('imgModal').style.display = "none";
    document.body.style.overflow = "auto";
}
</script>

<div id="imgModal" class="image-modal" onclick="closeImage()">
    <span class="close-modal">&times;</span>
    <img class="modal-content" id="fullImage">
</div>
</body>
</html>