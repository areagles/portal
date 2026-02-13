// js/layout.js - (Royal Engine V7.0 - Ultimate Golden Dynamic)

// 1. حقن التصميم الملكي الديناميكي (CSS Injection)
const royalStyle = `
<style>
    /* --- تعريفات الألوان والأنيميشن --- */
    :root { 
        --bg-body: #050505; 
        --bg-card: #121212; 
        --gold: #d4af37; 
        --gold-light: #f1c40f; 
        --gold-dark: #b8860b; 
        --danger: #ff4757;
        --text-main: #ffffff; 
        --text-muted: #a0a0a0; 
        --glass: rgba(20, 20, 20, 0.95);
    }

    /* تأثير جريان الذهب */
    @keyframes goldFlow {
        0% { background-position: 0% 50%; }
        100% { background-position: 200% 50%; }
    }

    /* تأثير النبض الذهبي */
    @keyframes pulseGold {
        0% { box-shadow: 0 0 0 0 rgba(212, 175, 55, 0.4); }
        70% { box-shadow: 0 0 0 15px rgba(212, 175, 55, 0); }
        100% { box-shadow: 0 0 0 0 rgba(212, 175, 55, 0); }
    }

    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Cairo', sans-serif; -webkit-tap-highlight-color: transparent; }
    body { background-color: var(--bg-body); color: var(--text-main); padding-bottom: 90px; }
    a { text-decoration: none; color: inherit; }

    /* --- الهيدر (Header) - نظيف --- */
    .header { 
        padding: 15px 20px; 
        display: flex; justify-content: space-between; align-items: center; 
        background: var(--glass); 
        backdrop-filter: blur(15px); 
        position: sticky; top: 0; z-index: 100; 
        border-bottom: 1px solid #222; 
        box-shadow: 0 4px 20px rgba(0,0,0,0.8); 
    }
    .user-info h2 { font-size: 1rem; font-weight: 800; margin: 0; 
        background: linear-gradient(45deg, var(--gold), #fff, var(--gold)); 
        -webkit-background-clip: text; -webkit-text-fill-color: transparent; 
        background-size: 200% auto; animation: goldFlow 3s linear infinite; 
    }
    .user-info p { font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; }
    .user-avatar { width: 42px; height: 42px; border-radius: 50%; border: 2px solid var(--gold); padding: 2px; object-fit: cover; }

    /* --- البار السفلي الملكي (The Royal Bar) --- */
    .bottom-nav { 
        position: fixed; bottom: 0; left: 0; width: 100%; height: 75px; 
        background: #0a0a0a;
        display: flex; justify-content: space-between; align-items: center; 
        z-index: 999; padding: 0 5px 8px 5px; 
        box-shadow: 0 -10px 30px rgba(0,0,0,0.9);
    }

    /* الشريط الذهبي المتحرك أعلى البار */
    .bottom-nav::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
        background: linear-gradient(90deg, transparent, var(--gold), var(--gold-light), var(--gold), transparent);
        background-size: 200% auto;
        animation: goldFlow 3s linear infinite;
        box-shadow: 0 0 10px var(--gold);
    }

    .nav-item { 
        text-align: center; color: #555; 
        text-decoration: none; font-size: 0.6rem; 
        flex: 1; 
        position: relative; transition: 0.4s; 
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        height: 100%; min-width: 0;
    }
    
    .nav-item i { 
        font-size: 1.2rem; display: block; margin-bottom: 4px; 
        transition: 0.3s;
    }

    /* الحالة النشطة - توهج */
    .nav-item.active { color: #fff; }
    .nav-item.active i { 
        transform: translateY(-5px); 
        color: var(--gold);
        filter: drop-shadow(0 0 8px rgba(212, 175, 55, 0.8));
    }
    /* نقطة ذهبية تحت النشط */
    .nav-item.active::after {
        content: ''; position: absolute; bottom: 8px; width: 4px; height: 4px;
        background: var(--gold); border-radius: 50%;
        box-shadow: 0 0 8px var(--gold);
    }

    /* زر الخروج - لمسة حمراء خفيفة */
    .nav-item.logout-nav:active i { color: var(--danger); transform: scale(0.9); }
    .nav-item.logout-nav.active i { color: var(--danger); filter: drop-shadow(0 0 5px var(--danger)); }

    /* --- الزر العائم (FAB) --- */
    .fab-wrapper { position: relative; width: 55px; display: flex; justify-content: center; flex-shrink: 0; }
    .fab { 
        position: absolute; top: -40px; 
        width: 60px; height: 60px; 
        background: linear-gradient(135deg, var(--gold-dark), var(--gold), var(--gold-light)); 
        background-size: 200% 200%;
        animation: goldFlow 4s ease infinite, pulseGold 2s infinite;
        border-radius: 50%; 
        display: flex; align-items: center; justify-content: center; 
        color: #000; font-size: 1.5rem; 
        border: 5px solid var(--bg-body); 
        cursor: pointer; transition: 0.3s; z-index: 1000; 
        box-shadow: 0 5px 20px rgba(0,0,0,0.6);
    }
    .fab:active { transform: scale(0.95); }

    /* --- القائمة المنبثقة (Add Menu) --- */
    .add-menu-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); z-index: 2000; display: none; align-items: flex-end; justify-content: center; opacity: 0; transition: opacity 0.3s; }
    .add-menu-content { width: 100%; max-width: 600px; background: #151515; border-radius: 25px 25px 0 0; padding: 30px 20px; border-top: 2px solid var(--gold); transform: translateY(100%); transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    .add-menu-content.show { transform: translateY(0); }
    
    .action-btn { 
        display: flex; align-items: center; gap: 15px; 
        background: #202020; padding: 18px; 
        border-radius: 16px; border: 1px solid #333; 
        text-decoration: none; margin-bottom: 15px; transition: 0.3s; position: relative; overflow: hidden;
    }
    .action-btn::before { content: ''; position: absolute; left: 0; top: 0; width: 3px; height: 100%; background: var(--gold); opacity: 0; transition: 0.3s; }
    .action-btn:hover { background: #252525; transform: translateX(-5px); border-color: var(--gold); }
    .action-btn:hover::before { opacity: 1; }
    
    .ab-icon { width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
    .ab-text h4 { margin: 0 0 5px 0; color: #fff; font-size: 1rem; }
    .ab-text p { margin: 0; color: #888; font-size: 0.8rem; }
    
    .close-menu-btn { width: 100%; padding: 12px; background: transparent; color: var(--text-muted); border: 1px solid #444; border-radius: 50px; font-weight: bold; cursor: pointer; margin-top: 15px; transition: 0.3s; }
    .close-menu-btn:hover { border-color: var(--danger); color: var(--danger); }
</style>
`;
document.head.insertAdjacentHTML("beforeend", royalStyle);

document.addEventListener("DOMContentLoaded", () => {
    const path = window.location.pathname;
    if(path.includes('login.html') || path.includes('register.html')) return; 

    renderLayout();
    highlightActiveLink();
    updateUserData();
});

function renderLayout() {
    // 1. الهيدر (صورة وترحيب فقط)
    const headerPlaceholder = document.getElementById('app-header');
    if(headerPlaceholder) {
        headerPlaceholder.innerHTML = `
        <div class="header">
            <div class="user-info">
                <h2 id="layout_u_name">مرحباً بك</h2>
                <p>بوابة عملاء الصفوة</p>
            </div>
            <div class="header-actions">
                <img src="assets/images/icon.png" class="user-avatar" id="layout_u_avatar" alt="User" onerror="this.src='https://ui-avatars.com/api/?name=User&background=d4af37&color=000'">
            </div>
        </div>`;
    }

    // 2. البار السفلي الملكي (7 عناصر)
    const navHTML = `
    <div class="bottom-nav">
        <a href="dashboard.html" class="nav-item" data-page="dashboard">
            <i class="fa-solid fa-house-chimney"></i> الرئيسية
        </a>
        <a href="orders.html" class="nav-item" data-page="orders">
            <i class="fa-solid fa-box-open"></i> الطلبات
        </a>
        <a href="quotes.html" class="nav-item" data-page="quotes">
            <i class="fa-solid fa-file-invoice"></i> العروض
        </a>
        
        <div class="fab-wrapper">
            <button onclick="toggleAddMenu()" class="fab"><i class="fa-solid fa-plus"></i></button>
        </div>
        
        <a href="invoices.html" class="nav-item" data-page="invoices">
            <i class="fa-solid fa-wallet"></i> المالية
        </a>
        <a href="profile.html" class="nav-item" data-page="profile">
            <i class="fa-solid fa-user-shield"></i> حسابي
        </a>
        <a href="#" onclick="confirmLogout()" class="nav-item logout-nav">
            <i class="fa-solid fa-power-off"></i> خروج
        </a>
    </div>

    <div id="addMenuModal" class="add-menu-overlay" onclick="toggleAddMenu()">
        <div id="addMenuContent" class="add-menu-content" onclick="event.stopPropagation()">
            <h3 style="color:var(--gold); margin:0 0 25px 0; text-align:center; font-size:1.2rem; font-weight:800;">
                <i class="fa-regular fa-star"></i> إنشاء جديد
            </h3>
            
            <a href="new_order.html?type=order" class="action-btn">
                <div class="ab-icon" style="background:rgba(46, 204, 113, 0.1); color:#2ecc71;"><i class="fa-solid fa-layer-group"></i></div>
                <div class="ab-text"><h4>أمر تشغيل جديد</h4><p>طباعة، كرتون، بلاستيك، ويب...</p></div>
                <i class="fa-solid fa-arrow-left" style="color:#444; margin-right:auto;"></i>
            </a>

            <a href="new_order.html?type=quote" class="action-btn">
                <div class="ab-icon" style="background:rgba(241, 196, 15, 0.1); color:#f1c40f;"><i class="fa-solid fa-file-signature"></i></div>
                <div class="ab-text"><h4>طلب عرض سعر</h4><p>استفسار عن تكلفة مشروع مخصص</p></div>
                <i class="fa-solid fa-arrow-left" style="color:#444; margin-right:auto;"></i>
            </a>

            <button onclick="toggleAddMenu()" class="close-menu-btn">إغلاق القائمة</button>
        </div>
    </div>`;
    
    document.body.insertAdjacentHTML('beforeend', navHTML);
}

function toggleAddMenu() {
    const modal = document.getElementById('addMenuModal');
    const content = document.getElementById('addMenuContent');
    
    if (modal.style.display === 'flex') {
        content.classList.remove('show');
        modal.style.opacity = '0';
        setTimeout(() => modal.style.display = 'none', 300);
    } else {
        modal.style.display = 'flex';
        setTimeout(() => {
            modal.style.opacity = '1';
            content.classList.add('show');
        }, 10);
    }
}

function highlightActiveLink() {
    const path = window.location.pathname;
    const page = path.split("/").pop().split('?')[0].replace('.html', '');
    
    document.querySelectorAll('.nav-item').forEach(link => {
        if(link.dataset.page === page || (page === '' && link.dataset.page === 'dashboard')) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

function confirmLogout() {
    if(confirm('هل أنت متأكد من رغبتك في تسجيل الخروج؟')) {
        window.location.href = 'api/logout.php';
    }
}

async function updateUserData() {
    try {
        const response = await fetch('api/dashboard_data.php?t=' + new Date().getTime());
        const result = await response.json();
        
        if (result.status === 'success') {
            const nEl = document.getElementById('layout_u_name');
            const iEl = document.getElementById('layout_u_avatar');
            if(nEl) nEl.innerText = result.data.name;
            // تحديث الصورة إن وجدت
            if(iEl && result.data.avatar) iEl.src = result.data.avatar;
        } else if (result.message === 'unauthorized') {
            window.location.href = 'login.html';
        }
    } catch(e) {}
}